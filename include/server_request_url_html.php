<?php
// Hàm quan trọng dùng để lọc nội dung chính trên website, bỏ header, footer, sidebar, quảng cáo, vân vân
// Dùng kiểu PHP tăng tương thích, đây là phiên bản PHP của phiên bản JS của Mozilla
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------

// cờ để biết là có đang dùng prompt tùy chỉnh không
$promptContent = '';
$sysInsContent = '';
$custom_prompt_system = false; 
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
if(defined('USE_USER_PROMPT_SYSTEM') && USE_USER_PROMPT_SYSTEM) { // nếu nó được định nghĩa và có giá trị True
    // Lấy thông tin Prompt và System Instructions tùy chỉnh
    // 1. Xác định đường dẫn gốc của dự án 
    // __DIR__ là thư mục hiện tại, nên mặc dù include, nó vẫn phải leo lên thư mục cha rồi mới trỏ đến file gốc được
    $projectBasePath = dirname(__DIR__);

    // 2. Định nghĩa tên các file markdown cần đọc
    $promptFileName = 'prompt.md';
    $systemInstructionsFileName = 'system_instructions.md';

    // 3. Xây dựng đường dẫn tuyệt đối đến các file markdown
    // Từ đường dẫn gốc, đi vào thư mục 'markdown_client_prompt', rồi đến tên file
    $promptFilePath = $projectBasePath . DIRECTORY_SEPARATOR . 'markdown_client_prompt' . DIRECTORY_SEPARATOR . $promptFileName;
    $systemInstructionsFilePath = $projectBasePath . DIRECTORY_SEPARATOR . 'markdown_client_prompt' . DIRECTORY_SEPARATOR . $systemInstructionsFileName;

    if (file_exists($promptFilePath)) {
        // Cố gắng đọc file
        $promptContentFG = file_get_contents($promptFilePath);
        if ($promptContentFG !== false && $promptContentFG != '') {
            $promptContent = $promptContentFG; // lấy được thông tin prompt tùy chỉnh
        }
    }

    if (file_exists($systemInstructionsFilePath)) {
        // Cố gắng đọc file
        $sysInsContentFG = file_get_contents($systemInstructionsFilePath);
        if ($sysInsContentFG !== false && $sysInsContentFG != '') {
            $sysInsContent = $sysInsContentFG; // Lấy được thông tin system instructions tùy chỉnh
        } 
    }
                    
    if ($promptContent != '' && $sysInsContent != '') {
        $custom_prompt_system = true; // Đến công đoạn này là phải biết $custom_prompt_system là true hay false
    }
}                    
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------   



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Biến lưu trạng thái và kết quả, thông báo lỗi, cờ báo các quá trình xử lý ---
$status_message = ''; // Nội dung thông báo lỗi
$status_type = ''; // 'success' (thành công) hoặc 'error' (lỗi)
$translated_file_link = ''; // Link file đã dịch
$processing_complete = false; // Cờ để biết quá trình xử lý POST đã hoàn tất (thành công hoặc lỗi)
$submitted_url = ''; // Lưu lại URL đã nhập
$submitted_html_preview = ''; // Lưu lại một phần HTML đã nhập để hiển thị (tránh XSS)
$translation_duration = 0; // Biến lưu thời gian dịch 
$url = false; // Gán cờ lỗi mặc định
$htmlContent = ''; // Gán là chuỗi rỗng lúc ban đầu
$readability = ''; // Gán là chuỗi rỗng lúc ban đầu
$plainTextSelect = 0; // Gán mặc định, mặc định là dịch giữ nguyên định dạng, chứ không phải là dịch văn bản thuần túy
$none_pdf_file = true; // Giả định không phải là file PDF
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// --- Xử lý khi form được gửi đi (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Người dùng bấm nút dịch 
    if (GEMINI_API_KEY != '') { // Phải có API KEY mới thực hiện gọi hàm
        $start_time = microtime(true); // Ghi lại thời gian bắt đầu, để đo thời gian dịch

        $submitted_url = isset($_POST['url']) ? trim($_POST['url']) : ''; // Làm sạch trường dữ liệu URL, xóa khoảng trắng dư đầu và cuối

        
        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------         
        $pdf_link = isLikelyPdfLink($submitted_url); // Kiểm tra xem đường link nhập vào có phải là file PDF không

        if ($pdf_link) {
            $status_message = 'Lỗi: Liên kết bạn nhập vào dường như là một file PDF. Nếu muốn dịch PDF vui lòng truy cập vào mục <a href="translation_PDF_HTML.php" style="color: blue;">Dịch file PDF</a> trên chương trình này.';
            $status_type = 'error';
            $can_proceed = false; // Cờ ngừng xử lý
            $none_pdf_file = false; // Cờ nói rằng đường link là file PDF, dùng để điều chỉnh thông báo trong catch khi $can_proceed = false 
        } 
// E ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
        
        
        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------         
        // Nếu không có prompt và system tùy chỉnh thì mới xét đến chuyện xác định plaintext hay không
        // Còn nếu đã có chỉ có kiểu dịch plaintext cho người dùng
        // Lý do quan trọng cho việc này là prompt và system sẽ dễ viết hơn cho người dùng cuối -> Kết quả tùy chỉnh sẽ tích cực hơn
        if (!$custom_prompt_system) {
            // Lấy lựa chọn chế độ dịch từ form, mặc định là 0 (dịch kiểu markdown) nếu không có hoặc không hợp lệ
            // (int) dùng để ép kiểu số nguyên trong PHP, điều này là có ý nghĩa, vì giá trị trong value được gửi đi được xem là chuỗi chứ không phải số
            // Do vậy để so sánh tường minh, ta phải ép kiểu về đúng mục đích ở đây là số nguyên, sau đó trong hàm if có thể dùng phép so sánh nghiêm ngặt ===, tức là có giá trị & kiểu dữ liệu
            // Để việc so sánh đúng như ý muốn của ta
            $plainTextSelect = isset($_POST['translation_mode']) ? (int)$_POST['translation_mode'] : 0;
            // ===================== KẾT THÚC PHẦN THÊM =====================        
        } else {
            $plainTextSelect = 1; // Nếu có prompt và system tùy chỉnh thì mặc định gán kiểu dịch là plaintext
        }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------         
 
        
        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------        
        // Yêu cầu bắt buộc phải bắt đầu là http hoặc https, để xác định nó là địa chỉ trang web
        // Ràng buộc này nhìn chung có thể tồn tại rất lâu vì các trình duyệt đều phân biệt rõ 2 kết nối này đến website
        if (isValidUrlStart($submitted_url)) {
            // Nếu đúng là url nó trả về chuỗi url, nếu sai nó trả về false
            // Ràng buộc có sẵn của PHP
            $url = filter_var($submitted_url, FILTER_VALIDATE_URL); // Chuẩn hóa URL bằng hàm sẵn có
        }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------        

        
        
        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------        
        // Kiểm tra các tàng buộc liên quan đến tạo thư mục và khả năng ghi thư mục
        if (!$url) { // Nếu không phải là URL thì gửi thông báo lỗi
            $status_message = 'Lỗi: URL không hợp lệ. Vui lòng nhập lại. Cách đơn giản nhất để khắc phục điều này là *copy toàn bộ địa chỉ website* (Ctr + A, Ctr + C, rồi Ctrl + V) bạn muốn dịch rồi đưa vào ô trên. Hình bên dưới cho biết địa chỉ website nằm ở đâu, và trông thế nào trên trình duyệt.'
                    . '<img src="images/url-la-gi.png">'; // Nội dung thông báo
            $status_type = 'error'; // Gán cho kiểu thông báo, cái này được dùng làm class trong div, tiện cho việc CSS, lỗi sẽ được đưa vào div có class status type tương ứng
        } else {
            // --- Đảm bảo thư mục lưu trữ tồn tại và có thể ghi ---
            $saveDir = TRANSLATION_SAVE_PATH; // Sử dụng biến cục bộ để dễ đọc (lưu ở config)
            $can_proceed = true; // Cờ kiểm soát việc tiếp tục
            
            // Bước này sẽ gắn cờ kiểm soát là false, nếu việc tạo, ghi thư mục có vấn đề
            // Tạo ghi quan trọng vì nếu không làm được thì kết quả file dịch không lưu được
            // Chưa gặp vấn đề với cái này lần nào!
            if (!is_dir($saveDir)) { // Nếu thư mục chưa có
                // Cố gắng tạo thư mục (recursive)
                if (!mkdir($saveDir, 0775, true)) {
                     $status_message = 'Lỗi: Không thể tạo thư mục lưu trữ: ' . htmlspecialchars($saveDir) . '. Vui lòng kiểm tra quyền của thư mục cha.';
                     $status_type = 'error';
                     $can_proceed = false;
                }
                 // Sau khi tạo, kiểm tra lại quyền ghi (quan trọng)
                elseif (!is_writable($saveDir)) {
                     $status_message = 'Lỗi: Đã tạo thư mục ' . htmlspecialchars($saveDir) . ' nhưng web server không có quyền ghi vào đó. Vui lòng cấp quyền ghi.';
                     $status_type = 'error';
                     $can_proceed = false;
                }
            } elseif (!is_writable($saveDir)) { // Có sẵn thư mục nhưng vẫn không ghi được
                 $status_message = 'Lỗi: Thư mục lưu trữ ' . htmlspecialchars($saveDir) . ' không có quyền ghi. Vui lòng cấp quyền ghi cho web server.';
                 $status_type = 'error';
                 $can_proceed = false;
            }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if ($is_html_mode) {
               // Lấy nội dung HTML từ textarea
               $submitted_html = isset($_POST['html_content']) ? trim($_POST['html_content']) : '';
               // Lấy URL gốc tùy chọn để tạo tên file và ngữ cảnh
               
               // Lưu một phần nhỏ HTML để hiển thị lại trong form (escape để tránh XSS)
               $submitted_html_preview = htmlspecialchars(substr($submitted_html, 0, 200)) . (strlen($submitted_html) > 200 ? '...' : '');               

               if (empty($submitted_html)) { // Người dùng không nhập mã nguồn và nhấn submit
                   $status_message = 'Lỗi: Vui lòng nhập mã nguồn HTML cần dịch vào ô bên trên. Hãy xem hướng dẫn nhanh cách lấy mã nguồn:' . 
                                       '<img src="images/ctrl-u.png">' .
                                       '<img src="images/copy-ma-nguon.png">';
                   $status_type = 'error';
                   $can_proceed = false;
               } else {
                   $htmlContent = $submitted_html;
               } 
            }  
           
           
 
            // Thư mục đã tạo sẵn & có quyền ghi nên không phải trường hợp hiếm hoi nó sẽ đi thẳng tới bước này
            if ($can_proceed) { // Chỉ tiếp tục nếu không có lỗi thư mục
                try {
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
                if (!$is_html_mode) { // nếu không phải là chế độ nhập mã nguồn
                    if (defined('ONLY_FETCH_HTML_WITH_PANTHER') && ONLY_FETCH_HTML_WITH_PANTHER) { // Chỉ lấy nội dung bằng Panther, bỏ qua cURL
                        // Mặc định dùng trình duyệt Chrome để mô phỏng
                        $browser = 'chrome';                          
                        
                        // Lấy nội dung HTML
                        $htmlContent = fetchHtmlWithPantherImproved($url, $browser);
                    }
                    
                    else {
                        // Nếu bật FETCH_HTML_WITH_PANTHER là kiểu dùng cụ thể
                        if (defined('FETCH_HTML_WITH_PANTHER') && FETCH_HTML_WITH_PANTHER) { // Bật kiểu hỗn hợp
                            // Song kiếm hợp bích, kết hợp cả 2 kiểu lấy nội dung
                            if (USER_PREFERRED_BROWSER_PANTHER == 'chrome' || USER_PREFERRED_BROWSER_PANTHER == 'firefox') {
                                $pantherOptions = [
                                    'browser' => USER_PREFERRED_BROWSER_PANTHER, // Lấy thông tin trình duyệt ưa thích của người dùng
                                ];
                            } else { // Lốp dự phòng
                                $pantherOptions = [
                                    'browser' => 'chrome', // Chắc không bao giờ dùng đến
                                ];                            
                            }

                            // Hàm dùng để kiểm tra HTML có hợp lệ không, nói chung là khó để biết được
                            // Có thể giản hóa bằng cách bỏ qua // Tạm thời để vậy rồi kiểm tra sau
                            $validator_html_fetch = function (string $html): bool {
                                if(empty($html)) { // Trang rỗng
                                    return false;
                                }
                                if (!empty($html)) { // Không rỗng nhưng có thể là thông báo chặn truy cập
                                    if (100 > estimateWordCountHtml($html)) { // Dấu hiệu một trang không cần lấy
                                        return false;
                                    }
                                }
                                return true;
                            };

                            $curlOptions = [
                                'timeout' => CURL_TIMEOUT_FETCH,
                                'connectTimeout' => 30,
                                'maxRetries' => 2,
                                'retryDelay' => 3,
                            ];
                            // Gọi hàm thông minh // kết hợp 2 phương pháp
                            $htmlContent = fetchHtmlSmart($url, $curlOptions, $pantherOptions, $validator_html_fetch); 
                        } 

                        else { // Nếu tắt sẽ chỉ dùng kiểu lấy nội dung dựa trên cURL 
                            $curlOptions = [
                                'timeout' => CURL_TIMEOUT_FETCH,
                                'connectTimeout' => 30,
                                'maxRetries' => 2,
                                'retryDelay' => 3,
                            ];

                            $htmlContent = fetchHtmlContentFinal($url, $curlOptions);
                        }
                    }    

                    if ($htmlContent == false) { // Nếu lỗi
                        $status_message = "[MAIN ERROR] $url không thể lấy được HTML. Hãy thử lần nữa hoặc sử dụng phương pháp Dịch qua mã nguồn HTML.";
                        $status_type = 'error';
                    }
                }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    

                    
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    /*
                    // Kiểu lọc nội dung chính
                    if (defined('USE_ADVANCED_READABILITY') && USE_ADVANCED_READABILITY) {
                        // Gọi hàm getReadableJSContent($htmlContent); // Thư viện nodejs chất lượng của Mozilla
                        $readability = getReadableJSContent($htmlContent); // Lấy nội dung quan trọng bằng Readability JS chính chủ
                    } else { // Hoặc sử dụng thư viện của PHP
                        // 1. Tạo đối tượng
                        $readabilityObject = new Readability(new Configuration());

                        // 2. Phân tích HTML (kết quả lưu vào bên trong $readabilityObject)
                        $readabilityObject->parse($htmlContent);

                        // 3. Lấy chuỗi HTML đã lọc bằng cách gọi phương thức getContent() TRÊN đối tượng
                        $readability_content = $readabilityObject->getContent(); // Lấy nội dung
                        $readability_title = $readabilityObject->getTitle(); // Lấy tiêu đề (text thuần túy)

                        $readability = '<h1 id="title_content_sila_trans">' . htmlspecialchars($readability_title) . '</h1>' . $readability_content;
                        // Bây giờ, $readability_raw mới thực sự là chuỗi HTML đã được lọc.
                        // Biến $readabilityObject vẫn là đối tượng.
                    }
                    */
                // # Vì rủi ro tiềm ẩn của READABILITY PHP trong việc lấy nội dung chính nên mặc định bây giờ chuyển thành sử dụng READABILITY Mozilla
                // # Nó có thể khiến ứng dụng gia tăng thêm 10 - 20s xử lý, nhưng đảm bảo chất lượng ở mức cao hơn đáng kể trên tổng thể, nhất là với trường hợp người dùng thông thường, thiếu khả năng nhận định lỗi để xử lý
                // Án lệ: https://www.themarshallproject.org/2025/05/22/trans-lawsuit-trump-prisons-order (mặc định PHP có thể gặp lỗi với nội dung kiểu này)
    
                // Nếu yêu cầu chỉ sử dụng bộ lọc của Mozilla // Tức là nó thành bộ lọc chính, không chỉ là dự phòng
                if (defined('ONLY_ADVANCED_READABILITY') && ONLY_ADVANCED_READABILITY) {
                    try {
                        // Nếu Mozilla là bộ lọc chính
                        $readability = getReadableJSContent($htmlContent);
                    } catch (\Exception $e) {
                        // Bạn cũng có thể ghi log lỗi tại đây để theo dõi
                        error_log("Lỗi Readability: " . $e->getMessage());
                    }
                }  
                else {
                    // Nếu yêu cầu chỉ dùng getReadableJSContent của Mozilla ngay từ đầu
                    if (defined('USE_ADVANCED_READABILITY') && USE_ADVANCED_READABILITY) {
                        // Thiết lập dự phòng với getReadableJSContent của Mozilla
                        try { // Nếu dùng thư viện của PHP trước
                            // 1. Tạo đối tượng Readability
                            $readabilityObject = new Readability(new Configuration());

                            // 2. Phân tích HTML
                            $readabilityObject->parse($htmlContent);

                            // 3. Lấy nội dung và tiêu đề nếu phân tích thành công
                            $readability_content = $readabilityObject->getContent();
                            $readability_title = $readabilityObject->getTitle();

                            if ($readability_content !== null && $readability_content !== '') {
                                $readability = '<h1 id="title_content_sila_trans">' . htmlspecialchars($readability_title) . '</h1>' . $readability_content;
                            } else {
                                // Nếu Readability không thể trích xuất nội dung, gọi hàm dự phòng
                                $readability = getReadableJSContent($htmlContent);
                            }

                        } catch (\Exception $e) {
                            // Nếu có exception xảy ra trong quá trình phân tích, gọi hàm dự phòng
                            $readability = getReadableJSContent($htmlContent);
                            // Bạn cũng có thể ghi log lỗi tại đây để theo dõi
                            error_log("Lỗi Readability: " . $e->getMessage());
                        }
                    } 
                    else { // Khi tắt chế độ dự phòng
                        // 1. Tạo đối tượng Readability
                        $readabilityObject = new Readability(new Configuration());

                        // 2. Phân tích HTML
                        $readabilityObject->parse($htmlContent);

                        // 3. Lấy nội dung và tiêu đề nếu phân tích thành công
                        $readability_content = $readabilityObject->getContent();
                        $readability_title = $readabilityObject->getTitle();

                        $readability = '<h1 id="title_content_sila_trans">' . htmlspecialchars($readability_title) . '</h1>' . $readability_content;
                    }
                }    
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    

                
                
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                     
/////// Chuẩn hóa mã HTML trước khi chuyển sang markdown // Có thể không cần thiết với các trang có cấu trúc HTML ổn định, ví dụ các trang lớn
// Tuy vậy nếu phòng thủ cao thì nên bật, dĩ nhiên bật sẽ tốn thêm thời gian phân tích
// Rủi ro bật gây ảnh hưởng tệ rất thấp, kể cả về mặt hiệu suất cũng không quá lớn do HTML đã được lọc qua READABILITY
// Rủi ro lỗi HTML rồi gửi đến chuyển đổi sang markdown có thể rất lớn, mặc dù thư viện markdown có khả năng tiền xử lý các lỗi cấu trúc HTML ở mức độ vừa phải
// Kết luận là nên bật, trên tổng thể sẽ cho kết quả tốt hơn
// Plaintext cũng vẫn qua lọc HTML_PURIFIER trước

                    // Kiểm tra xem có lọc qua lần cuối với HTML_PURIFIER không, thường có ích hơn là có hại
                    if (defined('USE_HTML_PURIFIER') && USE_HTML_PURIFIER) {
                        try {
                            // Lấy cấu hình tùy chỉnh chất lượng cao
                            $config_HTMLPurifier = createCustomHtmlPurifierConfig();

                            // Tạo đối tượng HTML Purifier với cấu hình đã tạo
                            $purifier_silaTrans = new HTMLPurifier($config_HTMLPurifier);

                            // Làm sạch HTML sau khi tinh chỉnh
                            $readability = $purifier_silaTrans->purify($readability); // Thực hiện lọc sớm để có dữ liệu chuẩn
                        } catch (Exception $e) {
                            // Xử lý lỗi nếu cấu hình Purifier thất bại (ví dụ: không ghi được cache)
                            error_log("Lỗi cấu hình HTMLPurifier: " . $e->getMessage());
                            // Quyết định xử lý tiếp theo: Dùng HTML chưa lọc hoặc báo lỗi?
                            // Ví dụ: Dùng HTML chưa lọc nhưng ghi log cảnh báo
                        }
                        ////////////////////// Hết lọc _HTMLPurifier
                    } 

/////// Hết chuẩn hóa mã HTML  
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    

                        
                        
                    // Xem có cần phải xóa thẻ Audio và Video
                    // Phần này không cần lo nữa, vì đã nhắc rất rõ trong prompt là không cần nghe audio và xem video để dịch
                    // if (defined('REMOVE_AUVI_HTML_TAGS') && REMOVE_AUVI_HTML_TAGS) {
                        // $tagsToStrip = ['audio', 'video']; // danh sách các thẻ cần loại bỏ, bao gồm cả nội dung bên trong của nó
                        // $readability_raw = removeTagsFromHtml_NoImplied($readability_raw, $tagsToStrip); // loại bỏ các thẻ này và nội dung bên trong nó
                    // }

                    
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    // Trên các trang web thông thường, việc loại bỏ hầu hết các thẻ này sẽ khiến trang khá giống với plain text
                    // Đây là chọn lựa có thể phù hợp trên các trang dày đặc link & các thẻ inline, chẳng hạn như Wikipedia
                    // Được cho là sẽ giúp AI dịch tốt hơn vì giảm nhiễu quanh văn bản, và nội dung cần dịch có tính văn bản thuần túy cao hơn
                    if ($plainTextSelect != 1) { // chỉ cần thiết khi dịch kiểu không phải plain text
                        // Cái này là tùy chỉnh, không chắc luôn loại inline sẽ tốt hơn, chỉ một số trang quá nhiều inline thì mới nên bật
                        if (defined('REMOVE_INLINE_HTML_TAGS') && REMOVE_INLINE_HTML_TAGS) {
                            // Loại bỏ các thẻ inline Mặc định: ['a', 'strong', 'b', 'em', 'i', 'span', 'u', 'mark'] trong p và div - có thể giúp chất lượng dịch có thể được cải thiện
                            $readability = unwrapInlineTagsInParagraphsAndDivs($readability);
                        } 
                    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    

                    
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // Có bảng mới tìm cách bọc div bên ngoài
                    // Việc bọc bảng bằng div nhằm mục đích CSS bảng để tránh các bảng quá to tràn ra khung, 
                    // đây là việc nên làm để đảm bảo giao diện cuối cùng có chất lượng tốt
                    if ($plainTextSelect != 1) { // Vì plain text loại bảng rồi thì mất công lắp div vào làm chi
                        // Phải dùng kiểm tra chặt, gồm cả kiểu dữ liệu, phòng trường hợp table xuất hiện ngay đầu HTML -> vị trí 0 -> so sánh lỏng lẻo thì 0 sẽ là false -> sai dự định
                        if (stripos($readability, '<table') !== false) { // Tìm nhanh xem trong HTML có thẻ table hay không?
                            // Bọc thẻ table bằng div thích hợp nếu có
                            $readability = wrapTablesInDiv($readability);
                        }
                    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    


                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    //////////////////// Nếu là dịch plain text
                    // \Html2Text\Html2Text là hàm khá mạnh để chuyển HTML sang text
                    // Tuy nhiên \Html2Text\Html2Text sẽ trích xuất text trong ảnh và bảng
                    // Do đó để kết quả tốt nhất cần chấp nhận hy sinh các nội dung không trọng tâm để tránh văn bản lộn xộn
                    // removeSpecifiedHtmlTags sẽ loại bỏ tất cả các tag HTML không phải hướng đến text hoặc làm rối văn bản thuần túy
                    if ($plainTextSelect == 1) { // nếu người dùng lựa chọn phương pháp dịch văn bản thuần túy
                        if ($readability && !empty(trim($readability))) { // Tồn tại và không rỗng

                            // --- Cấu hình Html2Text/Html2Text tối ưu cho AI Translation ---
                            $optionsPlain = [
                                'ignore_errors' => true, // bỏ qua lỗi cú pháp html
                                'width' => 0, // để ngắt dòng tự nhiên
                                'do_links' => 'none', // không giữ link
                                'no_automatic_links' => true, // không tự động thêm link ngay cả khi văn bản là dạng link
                            ];

                            try {
                                // Với trường hợp dịch văn bản, cần tiền xử lý ảnh img và figcaption và một số thẻ audio, video, iframe, để thư viện trích xuất text không tạo ra -->
                                // --> các đoạn văn lạc lõng từ ảnh, bảng và iframe đều được loại bỏ, hy sinh tính đầy đủ để có được nội dung thuần văn bản hơn
                                $readabilityCrowd = removeSpecifiedHtmlTags($readability);
                                
                                // Khởi tạo với HTML và các tùy chọn đã định nghĩa
                                $htmlObject = new \Html2Text\Html2Text($readabilityCrowd, $optionsPlain); // Khởi tạo đối tượng

                                // Lấy văn bản thuần túy đã được cấu hình
                                $readability = $htmlObject->getText(); // Chỉ lấy văn bản, gán trở lại

                            } catch (\Exception $e) {
                                echo "Lỗi khi chuyển đổi HTML sang text: " . $e->getMessage();
                            }
                        }
                    }                   
                    //////////////////// Hết plain text 
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    


                                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
////// XỬ LÝ CÁC ĐƯỜNG DẪN TƯƠNG ĐỐI, RẤT QUAN TRỌNG VỚI KIỂU DỊCH GIỮ LẠI ĐỊNH DẠNG--------------------------------------------------------------------------------------
                    // Đây là nhiệm vụ cần phải với kiểu dịch không plain để đảm bảo các ảnh và link là tuyệt đối thay vì tương đối
                    // Nếu không làm các link ảnh tương đối sẽ không hiển thị, các link text tương đối sẽ trỏ về localhost -> hỏng 
                    if ($plainTextSelect != 1) { // Chỉ cần trong kiểu dịch giữ nguyên vẹn định dạng, kiểu kia xóa hết ảnh và link rồi
                        // --- BƯỚC QUAN TRỌNG: Chuyển đổi URL hình ảnh tương đối thành tuyệt đối ---
                        // Nên thực hiện ngay sau khi lấy được content tiếng Anh, mục đích là để hạn chế lỗi
                        // Đã có HTML đầy đủ, hoàn chỉnh
                        if ($url) { // Bước 1: Chỉ thực hiện nếu có URL gốc hợp lệ
                            // --- TỐI ƯU HÓA: Kiểm tra nhanh xem có thẻ ảnh không trước khi phân tích DOM ---
                            // stripos() nhanh hơn nhiều so với việc load toàn bộ DOM 
                            // if (stripos($readability, '<img') !== false || stripos($readability, '<picture') !== false) {quá đơn giản nên đã đổi sang regex
                            // Regex thi thoảng ca khó vẫn dương tính giả, nhưng không sao, vẫn còn hơn âm tính giả
                            if (hasPotentialRelativeImageUrls($readability)) { // hàm regex mới giúp kiểm tra nhanh xem có khả năng có đường dẫn ảnh tương đối không rồi mới chạy DOM
                                // Bước 2: Chỉ chạy hàm phân tích DOM tốn kém nếu phát hiện có thể có thẻ img hoặc picture
                                // Đảm bảo hàm convertToAbsoluteUrl và convertRelativeImageUrlsToAbsolute đã được include hoặc định nghĩa ở trên
                                try {
                                    $readability = convertRelativeImageUrlsToAbsolute($readability, $url);
                                    // Ghi log (tùy chọn) để biết khi nào việc chuyển đổi được thực hiện
                                    // error_log("Performed image URL conversion for: " . $url_for_context);
                                } catch (Exception $domError) {
                                    // Ghi log lỗi nếu có vấn đề khi phân tích DOM hoặc chuyển đổi URL, nhưng không dừng script
                                    error_log("Error during image URL conversion for " . $url . ": " . $domError->getMessage());
                                    // Bạn có thể quyết định không thay đổi $readability_vn_full_html nếu có lỗi
                                }
                            }

                            // Chuyển đổi link a từ dạng tương đối thành tuyệt đối
                            // Nên test với trang dày link như en.wikipedia để kiểm tra độ chính xác
                            if(!REMOVE_INLINE_HTML_TAGS) { // nếu không loại bỏ inline tag thì mới cần chuyển đổi
                                $readability = convertRelativeLinkUrlsToAbsolute($readability, $url); // Vì nếu loại bỏ HTML inline rồi thì cũng không còn link tương đối để mà chuyển đổi
                            }
                        }
                    }                       
/////// HẾT XỬ LÝ ĐƯỜNG DẪN TƯƠNG ĐỐI---------------------------------------------------------------------------------------------------------
// E ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
 
 

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    $must_use_all_html_tags = ALL_HTML_TAGS;                  
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------


        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                         
/////// Chuyển sang html sang markdown với kiểu dịch giữ lại định dạng------------------------------------------------------------------------
// Thư viện PHP chuyển html sang markdown nhìn chung có chất lượng cao, xử lý các trang có độ phức tạp trung bình đến khá rất tốt, nên để là mặc định
// Pandoc không phải lúc nào cũng cho chuyển đổi tốt hơn, nó chỉ tỏ ra hữu dụng khi chuyển trên các trang có HTML rối rắm, phức tạp
                    // CHUYỂN SANG MARKDOWN, CÓ 2 CÁCH CHUYỂN, THỨ NHẤT LÀ DÙNG PANDOC, THỨ HAI LÀ SỬ DỤNG THƯ VIỆN CỦA PHP
                        // PANDOC TỐT HƠN TRÊN TRANG PHỨC TẠP NHƯNG CHẬM HƠN VÀ CÓ THỂ KHÔNG TƯƠNG THÍCH HỆ ĐIỀU HÀNH
                        // THƯ VIỆN PHP THÌ CHẮC CHẮN HƠN
                    if ($plainTextSelect != 1) {
                        if (!$must_use_all_html_tags) { // Khi mà ALL_HTML_TAGS là false thì sẽ sử chuyển sang markdown // Phần lớn sẽ là trường hợp này
                            if (defined('USE_HTML_TO_MARKDOWN_PANDOC') && USE_HTML_TO_MARKDOWN_PANDOC) {
                                $readability = convertHtmlToMarkdownPandoc($readability); // dùng Pandoc
                            } else {
                                $readability = convertHtmlToMarkdownPhp($readability); // dùng thư viện của PHP
                            }
                        }    
                    }
//////// Plain text còn thô hơn cả markdown rồi nên không cần chuyển-------------------------------------------------------------------------- 
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                     

                    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                                  
                    // --- Áp dụng logic loại bỏ quảng cáo ---
                    // Cấu hình chi tiết cho các marker
                    // Đang code này trực tiếp (để đảm bảo an toàn), có thể phát triển thêm tính năng tùy chỉnh sau này để linh động hơn cho một số người dùng
                    // Kiểu dịch HTML nói chung sẽ cố gắng bảo tồn mọi thứ nên sẽ không loại bỏ dữ liệu
                    // Loại bỏ luôn phải cẩn thận, hàm này tiền xử lý markdown tiếng Anh sau khi nó đã được chuyển đổi từ HTML sang                  
                    if (REMOVE_ADS_CONTENT) { // Kiểm tra hằng số từ config.php
                        // Đường dẫn đến file cấu hình marker
                        $markerConfigFile = dirname(__DIR__) . '/myself/marker_remove.php'; // Do vị trí của file mà cần dùng dirname

                        // Chỉ thực hiện nếu không phải chế độ HTML đầy đủ
                        if (!$must_use_all_html_tags) {
                            $markerConfigurations = [];
                            if (file_exists($markerConfigFile)) {
                                // Tải cấu hình marker động từ file
                                $loadedConfig = include $markerConfigFile;
                                if (is_array($loadedConfig)) {
                                    $markerConfigurations = $loadedConfig;
                                } else {
                                    error_log("Lỗi: File marker_remove.php không trả về mảng hợp lệ.");
                                }
                            } else {
                                 error_log("Thông báo: File marker_remove.php không tìm thấy, không có marker nào được áp dụng.");
                            }

                            // Chỉ gọi hàm nếu có cấu hình marker
                            if (!empty($markerConfigurations)) {
                                if (checkIfAnyMarkerExists_UTF8_Safe($readability, $markerConfigurations)) { // Chỉ tiến hành loại bỏ nếu kiểm tra nhanh có sự tồn tại của marker 
                                    // Tiến hành loại bỏ các thành phần đáp ứng tiêu chí
                                    $readability = removeLinesByMarkerConfig_UTF8_Safe($readability, $markerConfigurations);
                                }
                            }
                        } 
                    }                    
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                 
                    
    
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // --- KIỂM TRA GIỚI HẠN TOKEN--- 
                    // Cái này cần kiểm tra ngay trước hàm dịch
                    // Sau khi lấy được văn bản đầu vào và áp các bộ lọc theo yêu cầu, chuẩn bị đẩy lên API mới dùng đến hàm đếm Token này
                    $tokenEnglish = estimateTokensByChars($readability); // Ra số token ước tính của bản tiếng Anh

                    if ($tokenEnglish > MAX_INPUT_TOKENS_ALLOWED) {
                        // Ném lỗi nếu vượt quá giới hạn, ngăn chặn việc gọi API
                        throw new Exception(
                                        "Nội dung gốc quá dài để dịch (ước tính " . number_format($tokenEnglish) .
                                        " tokens, giới hạn đầu vào là " . number_format(MAX_INPUT_TOKENS_ALLOWED) .
                                        "). Vui lòng thử với URL chứa nội dung ngắn hơn."
                                );
                    }
                    // --- KẾT THÚC KIỂM TRA GIỚI HẠN TOKEN---  
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    

                    
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                   
                    $readability = removeLeadingMarkdownFence($readability); // * Loại bỏ dấu hàng rào mã Markdown (```) không mong muốn ở đầu chuỗi.                  
                    
                    // Xóa xuống dòng dư thừa
                    $readability = removeExcessiveNewlines($readability);
                    
                    // Đảm bảo là UTF-8, chuyển đổi đầu vào để tránh lỗi không phải UTF-8 khi gửi lên AI
                    $readability = fix_input_ensure_utf8($readability);
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------  

                   
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // Gọi API của Gemini để dịch, nhanh, chất lượng hơn vì chỉ dịch dữ liệu thô
                    // Đảm bảo chọn mô hình chất lượng cao và có prompt cũng như system instructions phù hợp
                   
                    $setPS = LANG_TRANSLATE; // Để biết kiểu dịch là gì (Anh Việt, Trung Việt, Hàn Việt, Nhật Việt)
                    $maxTrans = MAXIMIZE_TRANSLATION_QUALITY; // Có tăng tối đa chất lượng dịch không
                    // Ước tính số lượng từ dựa trên kiểu markdown hay html
                    // $wordThresholdForTrans Ngưỡng cao vì niềm tin rằng AI ngày càng tiến bộ hơn
                    if (!$must_use_all_html_tags) {
                        $estimateWordCount = estimateMarkdownWordCount($readability);
                        $wordThresholdForTrans = 20000; // đặt ngưỡng chia đôi để dịch
                    } else {
                        $estimateWordCount = estimateWordCountHtml($readability);
                        $wordThresholdForTrans = 10000; // đặt ngưỡng chia đôi để dịch
                    }
                    
                    // Đếm số từ theo kiểu markdown
                    if ($wordThresholdForTrans > $estimateWordCount) { // Số lượng từ vừa phải thì dịch thẳng 
                        // Chọn prompt dạng nào là do $plainTextSelect, prompt & system instructions sẽ được tối ưu theo dạng đó
                        $readability_trans_arr = callGeminiApi($readability, $plainTextSelect, $sysInsContent, $promptContent, $must_use_all_html_tags, $setPS, $maxTrans); // Lấy kết quả dịch sang tiếng Việt
                        $readability_trans_one = $readability_trans_arr['translated_text'];
                    } else {
                        // Hàm chia tách với những văn bản rất dài
                        // Chia tách thông minh để không làm câu cụt ngủn
                        // Chia tách dựa trên kiểu Markdown hay HTML
                        if (!$must_use_all_html_tags) {
                            // Chia theo kiểu thông thường, tức chỉ chia làm đôi
                            $splitParts = splitMarkdownContentIntelligently($readability, $wordThresholdForTrans); // markdown vẫn chỉ chia đôi để tránh mất ngữ cảnh dịch quá nhiều
                        } else {
                            // Chia cho đến khi không phần nào vượt ngưỡng $wordThresholdForTrans
                            // Chỉ HTML mới chia sâu vì nó hay có token rất lớn do giữ lại các thẻ HTML
                            // Ngoài ra đây không phải là phương pháp dịch mặc định nên sẽ ít ảnh hưởng
                            // Chủ yếu dùng khi chia tách các trang web có bao gồm công thức toán học mà thôi
                            // Ngưỡng của HTML chỉ nên là 5 ngàn, vì 5 ngàn là số từ, tính thêm thẻ nữa có thể lên đến 10 ngàn
                            $splitParts = splitHtmlRecursively($readability, 5000); // Hàm chia tách sâu hơn, tránh bất cứ phần nào vượt ngưỡng $wordThresholdForTrans
                        }
                        
                        // Sau quá trình tách
                        if (count($splitParts) > 1) { // Nếu có sự chia tách diễn ra  
                            $maxTrans = false; // Vì đã chia ra rồi thì không cần tinh chỉnh prompt và SI
                            $translatedParts = []; // Mảng chứa kết quả dịch
                            foreach ($splitParts as $index => $partText) {
                                // Giả sử callGeminiApi trả về chuỗi dịch đã được trim() hoặc có thể có whitespace không mong muốn
                                $readability_trans_arr = callGeminiApi($partText, $plainTextSelect, $sysInsContent, $promptContent, $must_use_all_html_tags, $setPS, $maxTrans); // Dùng false để ngăn tính năng cải thiện chất lượng dịch khi dịch kiểu chia tách, ngăn vì bối cảnh văn bản mất sẽ hầu như không còn tác dụng như dự kiến
                                $readability_part_txt = $readability_trans_arr['translated_text'];
                                if (!empty(trim($readability_part_txt))) { // Chỉ thêm nếu phần dịch không rỗng
                                    $translatedParts[] = $readability_part_txt;
                                }
                            }
                            // Quyết định ký tự nối:
                            // - Với Markdown, "\n\n" thường là lựa chọn tốt để đảm bảo tách đoạn.
                            // - Với HTML, một khoảng trắng " " có thể an toàn hơn là không có gì,
                            //   nhưng có thể thừa nếu nối giữa các thẻ block. Nối rỗng "" có thể ổn nếu chia đúng thẻ.
                            // => Cần thử nghiệm! Ví dụ dùng "\n\n" cho Markdown hoặc " " cho HTML:
                            $separator = ($must_use_all_html_tags) ? " " : "\n\n"; // kiểm tra biến $must_use_all_html_tags
                            $readability_trans_one = implode($separator, $translatedParts);                            
                        } else { // Nếu sau chia không có gì diễn ra
                            $readability_trans_arr = callGeminiApi($readability, $plainTextSelect, $sysInsContent, $promptContent, $must_use_all_html_tags, $setPS, $maxTrans); // Nghĩa là không chia tách gì được thì lại phải dịch thẳng thôi
                            $readability_trans_one = $readability_trans_arr['translated_text'];
                        }
                    }                   
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------

                    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    $tokenVietnamese = estimateTokensByChars($readability_trans_one); // Ra số token ước tính của bản dịch tiếng Việt
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    

                                                                       
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // Tiến hành định dạng văn bản trước khi hiển thị trên HTML, để tránh nó hiển thị thành một khối dính liền do dịch thuần văn bản
                    // Văn bản có dấu xuống dòng \n, nhưng cần dùng hàm trong PHP điều chỉnh để quy tắc đó được chuyển đổi trong HTML
                    if ($plainTextSelect == 1) { // Với dạng plain text
                        // Thêm dấu xuống dòng thích hợp để có định dạng nhất định khi hiển thị trong HTML
                        $clean_html_readability_trans = '<div id="cleanTextHTML2Text">' . nl2br(htmlspecialchars($readability_trans_one, ENT_QUOTES, 'UTF-8')) . '</div>';
                    }
                    
                    // Chuyển lại từ markdown về html, lúc này là markdown đã được dịch
                    if ($plainTextSelect != 1) {
                        $clean_html_readability_trans = removeExtraCharacters($readability_trans_one);
                        if (!$must_use_all_html_tags) { // Khi không dùng kiểu dịch HTML thì chuyển lại sang markdown
                            $clean_html_readability_trans = removeExtraCharacters($readability_trans_one); // Loại bỏ ký tự dư thừa, chỉ xảy ra khi không là plain text

                            if (defined('USE_HTML_TO_MARKDOWN_PANDOC') && USE_HTML_TO_MARKDOWN_PANDOC) { // Sử dụng Pandoc hay hàm PHP tương thích cao
                                $clean_html_readability_trans = convertMarkdownToHtmlPandoc($clean_html_readability_trans); // Pandoc
                            } else {
                                $clean_html_readability_trans = convertMarkdownToHtmlPhp($clean_html_readability_trans); // PHP
                            }
                        }
                    }    
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------    
       
          
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                     
                    // Gọi hàm để tạo HTML hoàn chỉnh, tự động lấy tiêu đề từ H1, ngôn ngữ 'vi'
                    // Bao nội dung để hiển thị, chung cho cả plain text lẫn hiển thị thông thường
                    $what_prompt = $readability_trans_arr['used_prompt']; // Thông tin về prompt
                    $what_systemIn = $readability_trans_arr['used_system_instruction']; // Thông tin về systemInstructions
                    $what_topP = $readability_trans_arr['used_topP']; // thông tin topP
                    $what_tempe = $readability_trans_arr['used_temperature']; // Thông tin temperature
                    
                    $readability_vn_full_html = wrapRawContentInHtml($clean_html_readability_trans, $url, $current_model_AI, '', 'vi', $tokenEnglish, $tokenVietnamese, $what_prompt, $what_systemIn, $what_topP, $what_tempe, $maxTrans);  // đóng gói thành trang HTML hoàn chỉnh
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------           

                    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                     
// Tiến hành lưu file, trước tiên là lấy tên file dựa trên tên file gốc, thông qua $url
                    // Lấy trên file gốc
                    $originalFilename = getBaseFilenameFromUrl($url);

                    // Tạo số ngẫu nhiên có 6 chữ số đưa vào tên file để tránh trùng lặp
                    $randomNumber = generateRandomSixDigitNumber();

                    // Hậu tố vietnamese ý chỉ là bản dịch tiếng Việt
                    // Cấu trúc: tên gốc-số ngẫu nhiên-vietnamese
                    $translatedFilename = $originalFilename . '-'. $randomNumber . '-vietnamese.html';

                    // Đảm bảo đường dẫn đúng chuẩn hệ điều hành đang sử dụng, nhất quán, tránh dư dấu phân cách
                    $saveFilePath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $saveDir), DIRECTORY_SEPARATOR)
                                    . DIRECTORY_SEPARATOR . $translatedFilename;

                    // Thực hiện ghi file và kiểm tra luôn có ghi thành công hay không
                    if (file_put_contents($saveFilePath, $readability_vn_full_html) === false) { // Lưu file đồng thời kiểm tra lỗi
                        // Lấy thông tin lỗi hệ thống nếu có thể
                        $error_details = error_get_last();
                        $error_msg = isset($error_details['message']) ? $error_details['message'] : 'Không rõ nguyên nhân.';
                        throw new Exception("Không thể lưu file đã dịch vào: " . htmlspecialchars($saveFilePath) . ". Lý do: " . $error_msg . " Kiểm tra lại quyền ghi.");
                    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    

                    
                    
                    /*
                    // * Ghi thông tin ra các bài đã dịch ra file ngoài, có thể là cách hay nếu không muốn đọc thông tin từ ổ cứng
                    // Có thể có hiệu suất tốt hơn?
                    //$pageTitle = 'Tiêu đề bài viết'; // Phòng lỗi
                    
                    //if (preg_match('/<h1.*?>(.*?)<\/h1>/is', $clean_html_readability_trans, $matches)) {
                        // Lấy nội dung bên trong H1, loại bỏ các thẻ HTML khác có thể có bên trong
                        //$extractedTitle = trim(strip_tags($matches[1]));
                        //if (!empty($extractedTitle)) {
                            //$pageTitle = $extractedTitle; // Nếu không rỗng thì gán vào
                        //}
                    //}    
                    
                    //if (isset($url)) { // kiểm tra sự tồn tại trước
                        // Địa chỉ ghi file
                        //$logTransFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'translationRecord.json';

                        // Ghi lại thông tin bài
                        //addTranslationRecord($url, $saveFilePath, $pageTitle, time(), $logTransFile);
                    //}
                    */
                    
                    
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // Ghi lại thời gian kết thúc và tính toán thời gian dịch
                    $end_time = microtime(true);
                    $translation_duration = round($end_time - $start_time, 1); // Làm tròn đến 1 chữ số thập phân                

                    // --- Bước 2.3: Xuất ra đường link ---
                    // Tạo link tương đối hoặc tuyệt đối tùy cấu hình web server
                    $translated_file_link = rtrim(TRANSLATION_PUBLIC_URL, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $translatedFilename;

                    // Chỉ đặt là success nếu thực sự có dịch thuật diễn ra
                    if ($status_type !== 'warning') {
                        // $status_message = 'Thành công! Trang đã được dịch và lưu tại: ' . htmlspecialchars($saveFilePath) . '<br>Thời gian xử lý: <strong>' . $translation_duration . ' giây</strong>'; // Thông báo dịch thành công, kèm thông tin thời gian dịch
                        // Dạng thông báo đơn giản hơn
                        $status_message = 'Dịch thành công! Thời gian xử lý: <strong>' . $translation_duration . ' giây</strong>'; // Thông báo dịch thành công, kèm thông tin thời gian dịch
                        $status_type = 'success';
                    }


                } catch (ParseException $e) {
                     // Lỗi cụ thể từ Readability (thường do HTML quá tệ)
                    if ($none_pdf_file) {
                        $status_message = 'Lỗi khi xử lý HTML: Không thể phân tích cấu trúc nội dung. Mã HTML có thể không hợp lệ hoặc quá phức tạp. Lỗi gốc: ' . htmlspecialchars($e->getMessage());
                        $status_type = 'error';
                        error_log("Readability Parse Error: " . $e->getMessage() . " | Context URL: " . ($url_for_context ?? 'N/A'));
                    } 
                } catch (Exception $e) {
                    $status_message = 'Đã xảy ra lỗi: ' . htmlspecialchars($e->getMessage()); // Escape thông báo lỗi
                    $status_type = 'error';
                    // Ghi log lỗi chi tiết hơn cho admin xem
                    error_log("Translation Error: " . $e->getMessage() . " | URL: " . $url);
                }
            }
        }
        
        $processing_complete = true; // Đánh dấu đã xử lý xong POST request
    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------     

    

// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
    // Khi API Key của người dùng là rỗng
    else {
        $status_message = '<p id="none_API_KEY">Bạn chưa có API KEY, chương trình không thể chạy được. Vào phần <a href="myself/setting.php" target="_blank">setting này</a> để nhập API KEY.'
                . ' <strong>Lưu ý</strong>: Chúng tôi không bán API KEY, bạn phải tự mua nó thông qua Google. Xem hướng dẫn trên mạng về cách lấy API KEY của Gemini.</p>';
        $status_type = 'error';
    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------     
}

