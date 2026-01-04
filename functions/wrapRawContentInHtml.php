<?php
/**
 * Lấy dòng đầu tiên của văn bản làm tiêu đề, giới hạn số từ. Dùng trong trường hợp không lấy được tiêu đề thông qua thẻ h1
 *
 * Hàm này trích xuất dòng đầu tiên từ một chuỗi văn bản.
 * Dòng được xác định bằng ký tự xuống dòng đầu tiên (`\n`).
 * Tiêu đề sẽ được cắt ngắn nếu có nhiều hơn số từ tối đa cho phép.
 * Khoảng trắng thừa ở đầu và cuối dòng sẽ bị loại bỏ.
 *
 * @param string|null $text Văn bản đầu vào. Có thể là null.
 * @param int $maxWords Số lượng từ tối đa cho phép trong tiêu đề (mặc định là 20).
 * @param string $ellipsis Chuỗi được thêm vào cuối nếu tiêu đề bị cắt ngắn (mặc định là '...').
 * @return string Tiêu đề đã được xử lý, hoặc chuỗi rỗng nếu văn bản đầu vào trống/null hoặc dòng đầu tiên trống.
 */
function getFirstLineAsTitle(?string $text, int $maxWords = 20, string $ellipsis = '...'): string
{
    // 1. Kiểm tra đầu vào rỗng hoặc null
    if ($text === null || trim($text) === '') {
        return ''; // Trả về rỗng nếu không có văn bản
    }

    // 2. Tìm vị trí ký tự xuống dòng đầu tiên (\n)
    $newlinePos = strpos($text, "\n");

    // 3. Trích xuất dòng đầu tiên
    if ($newlinePos !== false) {
        // Nếu tìm thấy ký tự xuống dòng, lấy phần văn bản trước nó
        $firstLine = substr($text, 0, $newlinePos);
    } else {
        // Nếu không có ký tự xuống dòng, toàn bộ văn bản là dòng đầu tiên
        $firstLine = $text;
    }

    // 4. Loại bỏ khoảng trắng thừa ở đầu và cuối dòng
    $firstLine = trim($firstLine);
    
     // 5. Loại bỏ thẻ HTML khỏi dòng đầu tiên
    $firstLine = strip_tags($firstLine);   

    // Nếu sau khi trim, dòng đầu tiên rỗng thì trả về chuỗi rỗng
    if ($firstLine === '') {
        return '';
    }

    // 5. Đếm số từ trong dòng đầu tiên
    // Sử dụng preg_split để xử lý tốt hơn các trường hợp có nhiều khoảng trắng
    $words = preg_split('/\s+/', $firstLine, -1, PREG_SPLIT_NO_EMPTY);
    $wordCount = count($words);

    // 6. Kiểm tra và cắt ngắn nếu cần
    if ($wordCount > $maxWords) {
        // Lấy $maxWords từ đầu tiên
        $limitedWords = array_slice($words, 0, $maxWords);
        // Ghép lại thành chuỗi và thêm dấu '...'
        return implode(' ', $limitedWords) . $ellipsis;
    } else {
        // Nếu số từ không vượt quá giới hạn, trả về nguyên dòng đã trim
        return $firstLine;
    }
}



/**
 * Bọc nội dung HTML thô vào một cấu trúc trang HTML hoàn chỉnh với CSS cơ bản.
 *
 * Hàm này nhận một đoạn mã HTML (không có thẻ bao ngoài như html, head, body)
 * và trả về một chuỗi HTML5 đầy đủ, bao gồm thẻ <head> với CSS nội tuyến
 * để định dạng nội dung theo phong cách bài viết cơ bản.
 *
 * @param string $rawContent Nội dung HTML gốc (ví dụ: bắt đầu bằng <h1>, <p>...).
 * @param string $url Là URL nguồn của bài viết, để hiển thị cho người dùng biết
 * @param string $current_model Model AI đang dùng, để người dùng biết họ đang dùng model AI nào để dịch
 * @param string $pageTitle Tiêu đề mong muốn cho trang (sẽ hiển thị trên tab trình duyệt).
 * Nếu để trống, hàm sẽ cố gắng tự động lấy nội dung từ thẻ <h1> đầu tiên hoặc dòng đầu tiên thông qua hàm getFirstLineAsTitle nếu không tìm thấy h1.
 * @param string $langCode Mã ngôn ngữ cho thẻ <html> (ví dụ: 'vi', 'en'). Mặc định là 'vi' vì đây là bản dịch.
 * @param string $prompt là prompt dùng cho bài dịch
 * @param string $systemInstruction là systemInstructions dùng cho bài dịch
 * @param int $tokenEnglish Là số token gửi lên API dùng để ước tính chi phí
 * @param int $tokenVietnamese Là số token API trả về, dùng để ước tính chi phí
 * @param float $topP là thông tin về topP dùng để chỉnh thông số AI
 * @param float $tempe Là thông tin 'nhiệt độ' dùng để chỉnh thông số AI 
 * @param bool $maxTrans Để biết có bật chế độ dịch tăng cường hay không, nếu bật sẽ thêm một bước điều chỉnh prompt & SI để nó phù hợp hơn nữa với nội dung cần dịch
 * @return string Chuỗi HTML hoàn chỉnh, sẵn sàng để hiển thị hoặc lưu.
 */
// Chú ý các biến đầu vào bắt buộc phải được khai báo trước các biến không bắt buộc (được cho sẵn giá trị mặc định), ví dụ $langCode = 'vi' nếu muốn dùng phải để ở cuối
function wrapRawContentInHtml(string $rawContent, string $url, string $current_model, string $pageTitle, string $langCode, int $tokenEnglish, int $tokenVietnamese, string $prompt, string $systemIn, float $topP, float $tempe, bool $maxTrans = false): string
{
    // --- Xử lý Tiêu đề Trang ---
    if (empty(trim($pageTitle))) {
        // Cố gắng trích xuất tiêu đề từ thẻ H1 đầu tiên trong nội dung thô
        if (preg_match('/<h1.*?>(.*?)<\/h1>/is', $rawContent, $matches)) {
            // Lấy nội dung bên trong H1, loại bỏ các thẻ HTML khác có thể có bên trong
            $extractedTitle = trim(strip_tags($matches[1]));
            if (!empty($extractedTitle)) {
                $pageTitle = $extractedTitle; // Nếu không rỗng thì gán vào
            } else {
                // Lấy dòng đầu tiên làm tiêu đề
                $pageTitle = getFirstLineAsTitle($rawContent); // Tiêu đề mặc định nếu H1 rỗng
            }
        } else {
            $pageTitle = getFirstLineAsTitle($rawContent); // Tiêu đề mặc định nếu không tìm thấy H1
        }
    }
    
    // Nếu vẫn rỗng
    if ($pageTitle == '') {$pageTitle = 'Nội dung trang';}
    
    // Đảm bảo tiêu đề an toàn khi đưa vào HTML
    $safePageTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
    
    // Lấy ngày tháng năm
    $date_month_year = date('d/m/Y');
    
    // Lấy giờ phút giây (kiểu 24h)
    $hour_minute_second = date('H:i:s');
    
    // Thông báo nguồn gốc bài tiếng Anh ban đầu
    $urlORI = "<p id='url_ori_sila_trans'><strong>URL bài gốc</strong>: <a href='$url' target='_blank' id='ahref_ori_sila_trans'>$url</a></p>";
    
    // Thông báo ngày giờ dịch
    $dateTrans = "<p id='date_sila_trans'><strong>Thời điểm bản dịch được tạo</strong>. Ngày: $date_month_year | Giờ: $hour_minute_second</p>";
    
    // Thông báo mô hình AI được sử dụng
    $modelAI = '<p id="model_ai_sila_trans"><strong>Model AI</strong>: ' . $current_model . '<span class="info-icon-sila-trans" data-tooltip="Model AI có ảnh hưởng lớn nhất đến chất lượng bản dịch, quy tắc chung là bạn nên chọn model cao nhất trong khả năng.">i</span></p>';
    
    // Thông báo token tiếng Anh
    $tokenEnglishP = '<p id="tokenEnglishP"><strong>Số lượng token input [đầu vào, chưa bao gồm khoảng 6 - 15 ngàn token dành cho system instructions/prompt]</strong> (ước tính)<span class="info-icon-sila-trans" data-tooltip="Dựa trên hàm có tính ước chừng, có sai số, do vậy để chắc chắn, bạn luôn cần kiểm soát chi phí dựa vào trang cung cấp API Key chính thức.">i</span>: '. $tokenEnglish . '</p>';

    // Thông báo token tiếng Việt
    $tokenVietnameseP = '<p id="tokenVietnameseP"><strong>Số lượng token output [đầu ra]</strong> (ước tính)<span class="info-icon-sila-trans" data-tooltip="Dựa trên hàm có tính ước chừng, có sai số, do vậy để chắc chắn, bạn luôn cần kiểm soát chi phí dựa vào trang cung cấp API Key chính thức.">i</span>: '. $tokenVietnamese . '</p>';
    
    // Thông báo về top-P
    $topPP = '<p id="topP_sila_trans"><strong>topP:</strong> '. $topP . '</p>'; // Hiện không còn dùng top-P để kiểm soát chất lượng dịch
    
    // Thông báo về nhiệt độ
    $tempeP = '<p id="tempe_sila_trans"><strong>Temperature:</strong> '. $tempe . '</p>';    
    
    // Thông báo giới hạn của AI
    $AImistakesDiv = "<div id='AImistakesDiv'>Dịch bằng AI có thể có sai sót. Hãy thuê người dịch với văn bản quan trọng.</div>";
    
    $maxTransP = '';
    if ($maxTrans) {
        $maxTransP = '<p id="maxTrans_sila_trans"><strong>Bài này dùng chế độ dịch nâng cao</strong>: Số lượng token đã dùng thường gấp đôi con số bên trên.</p>';
    }
    // Khối điều khiển mới
    $controlFontWidth = '<!-- === Thanh tiến trình đọc === -->
    <div id="progress-bar-container"><div id="progress-bar"></div></div>

    <!-- === KHỐI ĐIỀU KHIỂN CẬP NHẬT === -->
    <div id="accessibility-controls">
        <strong>Tùy chỉnh hiển thị:</strong>
        <!-- Cỡ chữ -->
        <div>
            <button id="decrease-font" title="Giảm cỡ chữ" aria-label="Giảm cỡ chữ">-</button>
            <span>Cỡ chữ</span>
            <button id="increase-font" title="Tăng cỡ chữ" aria-label="Tăng cỡ chữ">+</button>
        </div>
        <!-- Chiều rộng -->
        <div>
            <button id="decrease-width" title="Giảm chiều rộng" aria-label="Giảm chiều rộng">-</button>
            <span>Chiều rộng</span>
            <button id="increase-width" title="Tăng chiều rộng" aria-label="Tăng chiều rộng">+</button>
        </div>
         <!-- Giãn dòng (MỚI) -->
        <div>
            <button id="decrease-line-height" title="Giảm giãn dòng" aria-label="Giảm giãn dòng">-</button>
            <span>Giãn dòng</span>
            <button id="increase-line-height" title="Tăng giãn dòng" aria-label="Tăng giãn dòng">+</button>
        </div>
        <!-- Font chữ -->
        <div>
            <label for="font-select">Font chữ:</label>
            <select id="font-select" name="font-select" title="Chọn kiểu chữ">
                <option value="Be Vietnam Pro" selected>Mặc định (Be VN Pro)</option>
                <option value="Lexend" selected>Lexend</option>
                <option value="Roboto">Roboto</option>
                <option value="Inter">Inter</option>
                <option value="Source Sans 3">Source Sans 3</option>
                <option value="Merriweather">Merriweather (Có chân)</option>
            </select>
        </div>
        <!-- Chế độ Sáng/Tối
        <div>
            <button id="toggle-dark-mode" title="Chuyển chế độ Sáng/Tối" aria-label="Chuyển chế độ Sáng/Tối">🌙</button>
            <span id="dark-mode-status">Tối</span>
        </div>
        -->
       
        <!-- Chế độ Sáng/Tối/Sepia/Tương phản cao (CẬP NHẬT) -->
        <div>
            <button id="toggle-light-mode" title="Chuyển chế độ Sáng" aria-label="Chuyển chế độ Sáng" aria-pressed="false">☀️</button>
            <button id="toggle-dark-mode" title="Chuyển chế độ Tối" aria-label="Chuyển chế độ Tối" aria-pressed="false">🌙</button>
            <button id="toggle-sepia-mode" title="Chuyển chế độ Sepia" aria-label="Chuyển chế độ Sepia" aria-pressed="false">📜</button>
            <button id="toggle-high-contrast-mode" title="Chuyển chế độ Tương phản cao" aria-label="Chuyển chế độ Tương phản cao" aria-pressed="false">HC</button> <!-- Nút HCM MỚI -->
        </div>        
        <!-- Về Như Cũ -->
        <button id="reset-settings" title="Đặt lại tất cả tùy chỉnh hiển thị về mặc định">Về Như Cũ</button>
    </div>
    <!-- === KẾT THÚC KHỐI ĐIỀU KHIỂN === -->';
    
    $left_bar_links = '<!-- === Thanh Bên Trái Cố Định === -->
                <aside id="sticky-left-sidebar">
                    <ul>
                        <li>
                            <a href="../index.php" title="Trang dịch web" target="_blank">
                                <svg width="24" height="24" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                                  <polygon 
                                    points="25,2  30,18  48,18  34,30  40,48  25,38  10,48  16,30  2,18  20,18"
                                    fill="#777" 
                                  />
                                </svg>
                            </a>
                        </li>
                        <li><a href="../search.php" target="_blank" title="Tìm kiếm (từ khóa tiếng Việt chuyển thành từ khóa tiêng Anh)">
                                <svg
                                  xmlns="http://www.w3.org/2000/svg"
                                  width="24"
                                  height="24"
                                  viewBox="0 0 24 24"
                                  fill="none"
                                  stroke="currentColor"
                                  stroke-width="2"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  class="search-icon"
                                >
                                  <title>Tìm kiếm (từ khóa tiếng Việt chuyển thành từ khóa tiếng Anh)</title>
                                  <circle cx="11" cy="11" r="5"></circle>
                                  <line x1="21" y1="21" x2="15.65" y2="15.65"></line>
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a href="#top" title="Lên đầu trang">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2 L7 8 H10 V20 H14 V8 H17 L12 2 Z"/>
                            </svg>
                            ️</a>
                        </li> 
                    </ul>
                </aside>
                <!-- === Kết Thúc Thanh Bên Trái === -->';
    
    // Khối JS để điều khiển
    // $controlJS;
    
    // --- Định nghĩa CSS (Sử dụng cú pháp HEREDOC cho dễ đọc) ---
    // $inlineCss;
    
    $fontAPI = '<link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;700&family=Inter:wght@400;700&family=Lexend:wght@400;700&family=Merriweather:wght@400;700&family=Roboto:wght@400;700&family=Source+Sans+3:wght@400;700&display=swap" rel="stylesheet">';
    
    $html_systemIn = '<h5>System Instructions</h5><pre id="system_content_sila_trans">' . htmlspecialchars($systemIn) . '</pre>'; // Lấy system để hiển thị
    $html_prompt = '<h5>Prompt</h5><pre id="prompt_content_sila_trans">' . htmlspecialchars($prompt) . '</pre>'; // Lấy prompt để hiển thị
    
        // --- Ghép nối tạo thành HTML hoàn chỉnh (Sử dụng HEREDOC) ---
        $fullHtml = <<<HTML
            <!DOCTYPE html>
            <html lang="{$langCode}">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>{$safePageTitle}</title>
                <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-X-16x16.png">
                <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-X-32x32.png">
                <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-X-icon.png">
                {$fontAPI}
                <link rel="stylesheet" href="../css/reset.css">
                <link rel="stylesheet" href="../css_trans/css.css?v=4">
            </head>
            <body>
                    {$controlFontWidth}
                    {$left_bar_links}
                <article itemprop="articleBody">
                    <div id="metadata-section-sila-trans">    
                        {$urlORI}
                        {$dateTrans}
                        {$modelAI}
                        {$tempeP}                       
                        {$tokenEnglishP}
                        {$tokenVietnameseP}
                        {$maxTransP}
                        {$AImistakesDiv}
                        <!-- === <button id="toggle-metadata" title="Ẩn/Hiện thông tin bài viết">Ẩn thông tin</button> === -->
                    </div>
                    
                    <button id="toggleButtonPromptSystem">Xem System Instructions / Prompt</button>    
                    <div id="prompt_system_sila_trans">    
                        {$html_systemIn}
                        {$html_prompt}
                    </div>                         
                    
                    {$rawContent}
                </article>
                    <script src="../js_trans/js.js?v=3"></script>
            </body>
            </html>
        HTML;

    // Trả về chuỗi HTML hoàn chỉnh
    return $fullHtml;
}