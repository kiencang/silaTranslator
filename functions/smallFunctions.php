<?php
// File: myself/smallFunctions.php
// Chứa các hàm không quá phức tạp, xử lý một tác vụ nhỏ nào đó
// Nếu nó là một hàm rất quan trọng nên tách ra file riêng để tiện kiểm tra chỉnh sửa, các hàm ở đây thường đơn giản và ổn định



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Tạo số nguyên dương ngẫu nhiên có 6 chữ số để tránh trùng tên file
 * Thay thế cho rand() nếu dùng PHP 7+
 * @return int
 */
function generateRandomSixDigitNumber(): int {
    try {
        return random_int(100000, 999999);
    } catch (\Exception $e) {
        // Fallback nếu random_int lỗi (hiếm)
        return rand(100000, 999999);
    }
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Kiểm tra xem một URL có bắt đầu bằng 'http://' hoặc 'https://' hay không.
 *
 * @param string $url Chuỗi URL cần kiểm tra (thường lấy từ $_POST['url'] hoặc $_GET['url']).
 * @return bool Trả về true nếu URL bắt đầu hợp lệ, false nếu không.
 */
function isValidUrlStart(string $url): bool
{
    // Loại bỏ khoảng trắng thừa ở đầu và cuối chuỗi URL
    $url = trim($url);

    // Kiểm tra xem chuỗi có rỗng không sau khi loại bỏ khoảng trắng
    if (empty($url)) {
        return false;
    }

    // Sử dụng regex để kiểm tra xem chuỗi có bắt đầu bằng 'http://' hoặc 'https://' không
    // ^     : khớp với vị trí bắt đầu của chuỗi
    // http  : khớp với các ký tự 'http'
    // s?    : khớp với ký tự 's' 0 hoặc 1 lần (làm cho 's' tùy chọn)
    // :\/\/ : khớp với các ký tự '://' (dấu / cần được escape bằng \)
    // i     : modifier để không phân biệt chữ hoa/thường (ví dụ: chấp nhận cả Https://)
    if (preg_match('/^https?:\/\//i', $url) === 1) {
        return true; // Khớp thành công
    } else {
        return false; // Không khớp
    }
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/** 
 * Lấy thông tin model AI đang dùng để end-user biết
 * @return string
 */
function modelAIis() {
    $current_model = 'Không tìm thấy model.';
    // --- Lấy model của trình dịch ---
    // Lấy vị trí của file từ vị trí cụ thể đặt hàm fuction này chứ không phụ thuộc vào vị trí của file gọi hàm này
    if (file_exists(dirname(__DIR__).'/myself/config.php')) { // xem file có tồn tại hay không?
        $config_content = file_get_contents(dirname(__DIR__).'/myself/config.php'); // Lấy nội dung file setting để biết model

        // Regex này tìm kiếm define IS_USING_MODEL_ID, tốt hơn regex trước vì cầu trúc đơn giản, chắc chắn hơn
        $regex_model = "/define\(\s*'IS_USING_MODEL_ID'\s*,\s*'(.*?)'\s*\);/i";
        if (preg_match($regex_model, $config_content, $matches_model)) {
            $current_model = $matches_model[1]; // Group 1 chứa tên model
        }
    }
    
return $current_model;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Xóa ký tự dư thừa đặc thù của bản dịch trả về, đôi khi xảy ra
 * Các bản dịch thường xuyên xuất hiện các dấu sau ở đầu và cuối nội dung trả về [bản dịch]; ```html bản dịch ```; và ``` bản dịch ```. 
 * Nguyên nhân có thể do prompt, mặc dù các dấu này là markdown, và đáng ra phải được diễn giải khác. 
 * @param string $strO
 * @return string
 */
function removeExtraCharacters($strO) {    
    $startHtmlx = "[";
    $endHtmlx = "]";    

    $startHtmly = "```html";
    $endHtmly = "```";

    $startHtmlz = "```markdown";
    $endHtmlz = "```";

    $startHtmlt = "```";
    $endHtmlt = "```";   

    // Xóa khoảng trắng ở đầu và cuối chuỗi
    $str = trim($strO);

    if (str_starts_with($str, $startHtmlx) && str_ends_with($str, $endHtmlx)) {
      $str = trim(substr($str, strlen($startHtmlx), -strlen($endHtmlx))); // cắt gọt
    } 

    if (str_starts_with($str, $startHtmly) && str_ends_with($str, $endHtmly)) {
      $str = trim(substr($str, strlen($startHtmly), -strlen($endHtmly))); // cắt gọt
    }   

    if (str_starts_with($str, $startHtmlz) && str_ends_with($str, $endHtmlz)) {
      $str =  trim(substr($str, strlen($startHtmlz), -strlen($endHtmlz))); // cắt gọt
    }     

    if (str_starts_with($str, $startHtmlt) && str_ends_with($str, $endHtmlt)) {
      $str =  trim(substr($str, strlen($startHtmlt), -strlen($endHtmlt))); // cắt gọt
    } 
    
    if (str_starts_with($str, $startHtmlz)) {
      $str =  trim(substr($str, strlen($startHtmlz))); // cắt gọt ```markdown đầu chuỗi
    } 
    
    if (str_starts_with($str, $startHtmly)) {
      $str =  trim(substr($str, strlen($startHtmly))); // cắt gọt ```html đầu chuỗi
    }     

  return $str; // trả về kết quả
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Loại bỏ dấu hàng rào mã Markdown (```) không mong muốn ở đầu chuỗi.
 *
 * Hàm này kiểm tra xem chuỗi đầu vào có bắt đầu bằng một dòng
 * định nghĩa khối mã Markdown (ví dụ: "```markdown\n", "```\n", hoặc "   ```php\n")
 * hay không. Nếu có, toàn bộ dòng đó sẽ bị loại bỏ khỏi phần đầu của chuỗi.
 *
 * Điều này đặc biệt hữu ích để làm sạch văn bản trước khi gửi đến AI,
 * đảm bảo AI không hiểu nhầm toàn bộ nội dung là một khối mã
 * do dấu hàng rào còn sót lại từ các bước xử lý trước (ví dụ: html-to-markdown).
 *
 * Lưu ý: Nó chỉ xóa dấu hàng rào ở ĐẦU chuỗi. Các khối mã hợp lệ
 * bên trong nội dung sẽ không bị ảnh hưởng.
 *
 * @param string $text Chuỗi văn bản Markdown có thể chứa dấu hàng rào mã ở đầu.
 * @return string Chuỗi văn bản đã loại bỏ dấu hàng rào mã ở đầu (nếu có).
 */
function removeLeadingMarkdownFence(string $text): string
{
    // Kiểm tra cơ bản đầu vào
    if (trim($text) === '') {
        return $text;
    }

    // Biểu thức chính quy (Regex) để tìm dòng hàng rào mã ở đầu chuỗi:
    // ^          Khớp với vị trí bắt đầu của chuỗi.
    // \s*        Khớp với 0 hoặc nhiều ký tự khoảng trắng (để xử lý thụt lề có thể có trước ```).
    // ```        Khớp với đúng 3 dấu backtick.
    // [^\n\r]*   Khớp với 0 hoặc nhiều ký tự bất kỳ KHÔNG phải là ký tự xuống dòng
    //            (dùng để khớp với tên ngôn ngữ tùy chọn như 'php', 'markdown', v.v.).
    // [\r\n]+    Khớp với 1 hoặc nhiều ký tự xuống dòng (đảm bảo toàn bộ dòng bị xóa).
    // /          Ký tự kết thúc biểu thức chính quy.
    $pattern = '/^\s*```[^\n\r]*[\r\n]+/';

    // Thực hiện thay thế: Thay thế mẫu tìm thấy bằng chuỗi rỗng '', chỉ 1 lần duy nhất.
    // Tham số thứ 4 là 1 (limit) đảm bảo chỉ thay thế lần xuất hiện đầu tiên (tại đầu chuỗi).
    $cleanedText = preg_replace($pattern, '', $text, 1);

    // preg_replace có thể trả về null nếu có lỗi trong pattern hoặc subject
    if ($cleanedText === null) {
        // Ghi log lỗi hoặc xử lý tùy ý, tạm thời trả về bản gốc để an toàn
        error_log("Lỗi regex trong hàm removeLeadingMarkdownFence.");
        return $text;
    }

    return $cleanedText;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Dựa trên số ký tự (heuristic rất thô: 1 token ~ 4 ký tự)
 * Ước tính này tuy đơn giản nhưng đạt được mức độ gần đúng trong nhiều trường hợp
 * @param string $html
 * @return int
 */
function estimateTokensByChars(string $html): int {
    return empty(trim($html)) ? 0 : (int) ceil(strlen($html) / 4);
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Lấy tên file cơ sở từ URL, làm sạch và chuẩn hóa để an toàn sử dụng làm tên file.
 * Ví dụ:
 * - /path/to/some-page.html -> some-page
 * - /blog/ -> blog
 * - http://www.example.com/ -> example.com
 * - http://example.com/path/file.php?query=1 -> file
 * Quan trọng cho mục đích đặt tên file để lưu lại bản dịch hoặc cache.
 *
 * @param string $url URL đầu vào.
 * @return string Tên file cơ sở đã được làm sạch, viết thường, giới hạn độ dài và an toàn cho hệ thống file.
 *                Trả về 'invalid_url_page' nếu URL không hợp lệ.
 *                Trả về 'page' nếu không thể xác định tên file hợp lý sau khi làm sạch.
 */
function getBaseFilenameFromUrl(string $url): string {
    // 1. Phân tích URL thành các thành phần
    $parsedUrl = parse_url($url);
    if ($parsedUrl === false) {
        // Trường hợp URL không thể phân tích cú pháp
        return 'invalid_url_page';
    }

    // Lấy path và host từ URL đã phân tích. Mặc định là chuỗi rỗng nếu không tồn tại.
    $path = isset($parsedUrl['path']) ? trim($parsedUrl['path']) : ''; // Trim path để xử lý path '/'
    $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';

    // Biến để lưu trữ tên file cuối cùng
    $filename = '';

    // 2. Xác định nguồn gốc cho tên file (ưu tiên path, sau đó đến host)
    if (!empty($path) && $path !== '/') {
        // Nếu có path và path không phải là gốc '/'
        // Ưu tiên lấy tên file từ path (không bao gồm extension)
        $filename = pathinfo($path, PATHINFO_FILENAME);

        if (empty($filename)) {
            // Trường hợp path không có phần filename rõ ràng (thường là thư mục, vd: /blog/ hoặc /images)
            // Lấy thành phần cuối cùng của path làm tên file (vd: 'blog' từ '/blog/')
            $basename = basename($path);
            // Đảm bảo basename không phải là '.' (từ path như /./) hoặc rỗng
            // Nếu là thư mục, dùng tên thư mục đó, nếu không xác định được thì dùng 'index'
            $filename = ($basename !== '.' && $basename !== '') ? $basename : 'index';
        }
    } elseif (!empty($host)) {
        // Nếu không có path cụ thể (hoặc path là '/') và có host
        // Sử dụng host làm tên file cơ sở, loại bỏ 'www.' nếu có (không phân biệt hoa thường)
        $filename = preg_replace('/^www\./i', '', $host);
    }

    // 3. Dọn dẹp và chuẩn hóa tên file để đảm bảo an toàn
    if (!empty($filename)) {
        // Chỉ giữ lại chữ cái (a-z, A-Z), số (0-9), dấu gạch ngang (-) và gạch dưới (_)
        // Tất cả các ký tự khác (dấu cách, chấm, ký tự đặc biệt, unicode...) sẽ bị thay bằng dấu gạch dưới
        $filename = preg_replace('/[^a-zA-Z0-9\-_]+/', '_', $filename);

        // Loại bỏ dấu gạch nối hoặc gạch dưới có thể xuất hiện ở đầu hoặc cuối tên file sau khi thay thế
        $filename = trim($filename, '_-');

        // Giới hạn độ dài tên file để tránh lỗi trên một số hệ thống file (ví dụ: 100 ký tự)
        $filename = substr($filename, 0, 100);

        // Chuyển toàn bộ tên file thành chữ thường để đảm bảo tính nhất quán
        // giữa các hệ điều hành (Windows case-insensitive, Linux case-sensitive)
        $filename = strtolower($filename);
    }

    // 4. Trả về kết quả cuối cùng
    // Nếu sau tất cả các bước, $filename vẫn rỗng (ví dụ: URL chỉ chứa ký tự đặc biệt)
    // thì trả về một tên mặc định là 'page'.
    return empty($filename) ? 'page' : $filename;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Lấy danh sách các bản dịch gần đây nhất từ thư mục (Tối ưu hóa).
 *
 * @param int $limit Số lượng bản dịch tối đa cần lấy. Mặc định lấy 5 nếu không có biến đầu vào cụ thể
 * @return array Mảng chứa thông tin các bản dịch.
 */
function getRecentTranslations($limit = 5, $type = 'web'): array {
    if ($type == 'web') {
        // Đảm bảo hằng số TRANSLATIONS_DIR đã được định nghĩa
        if (!defined('TRANSLATIONS_DIR')) {
            // error_log("Hằng số TRANSLATIONS_DIR chưa được định nghĩa.");
            return [];
        }        
        $translations_dir = TRANSLATIONS_DIR;
    }
    
    if ($type == 'pdf') {
        // Đảm bảo hằng số TRANSLATIONS_DIR_PDF đã được định nghĩa
        if (!defined('TRANSLATIONS_DIR_PDF')) {
            // error_log("Hằng số TRANSLATIONS_DIR_PDF chưa được định nghĩa.");
            return [];
        }        
        $translations_dir = TRANSLATIONS_DIR_PDF;
    }    

    $fileInfo = []; // Mảng tạm chứa filename và timestamp

    // --- LƯỢT 1: Thu thập filename và timestamp, sắp xếp, lấy top $limit ---
    if (is_dir($translations_dir)) {
        $files = scandir($translations_dir);
        if ($files === false) {
            // error_log("Không thể đọc thư mục: " . $translations_dir);
            return [];
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || strpos($file, '.') === 0) {
                continue;
            }

            $filePath = $translations_dir . '/' . $file;

            if (is_file($filePath)) {
                $fileInfo[] = [
                    'filename' => $file,
                    'timestamp' => filemtime($filePath) // Chỉ lấy timestamp ở lượt này
                ];
            }
        }

        // Sắp xếp mảng tạm theo timestamp giảm dần
        if (!empty($fileInfo)) {
            usort($fileInfo, function($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });
        }

        // Lấy $limit file mới nhất
        $latestFiles = array_slice($fileInfo, 0, $limit);

    } else {
        // error_log("Thư mục dịch không tồn tại: " . $translations_dir);
        return [];
    }

    // --- LƯỢT 2: Đọc và xử lý chỉ $limit file đã chọn ---
    $translations = [];
    foreach ($latestFiles as $fileData) {
        $filename = $fileData['filename'];
        $timestamp = $fileData['timestamp'];
        $filePath = $translations_dir . '/' . $filename;

        $vietnameseTitle = '';
        $englishTitleFormatted = '';

        // Đọc nội dung và lấy title (chỉ cho các file trong $latestFiles)
        $htmlContent = @file_get_contents($filePath);
        if ($htmlContent !== false) {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent);
            libxml_clear_errors();

            $titleNodes = $dom->getElementsByTagName('title');
            if ($titleNodes->length > 0) {
                $vietnameseTitle = trim($titleNodes->item(0)->nodeValue);
            }
        }

        if (empty($vietnameseTitle)) {
             $vietnameseTitle = pathinfo($filename, PATHINFO_FILENAME);
        }

        // Định dạng tên tiếng Anh
        $englishBaseName = pathinfo($filename, PATHINFO_FILENAME);
        // xóa hậu tố không cần thiết khi hiển thị
        $englishBaseName = preg_replace('/\d{6}-vietnamese$/i', '', $englishBaseName);
        $englishBaseName = str_replace(['-', '_'], ' ', $englishBaseName);
        $englishTitleFormatted = ucfirst($englishBaseName);

        // Thêm vào kết quả cuối cùng
        $translations[] = [
            'filename' => $filename,
            'englishTitleFormatted' => $englishTitleFormatted,
            'vietnameseTitle' => $vietnameseTitle,
            'timestamp' => $timestamp
        ];
    }

    // Mảng $translations bây giờ đã đúng thứ tự do $latestFiles đã được sắp xếp
    return $translations;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Hàm giới hạn số từ và làm sạch cơ bản
// Dùng để hiển thị tin RSS
function limit_words_rss($string, $word_limit) {
    // 1. Decode HTML entities (như  , &, ") thành ký tự thực
    $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 2. Loại bỏ thẻ HTML
    $string = strip_tags($string);

    // 3. Thay thế nhiều khoảng trắng (bao gồm cả non-breaking space đã decode) bằng một khoảng trắng duy nhất
    $string = preg_replace('/\s+/u', ' ', $string); // Thêm 'u' để hỗ trợ Unicode

    // 4. Loại bỏ khoảng trắng đầu cuối
    $string = trim($string);

    // 5. Tách chuỗi thành mảng các từ
    $words = preg_split('/\s+/', $string);

    // 6. Giới hạn số từ
    if (count($words) > $word_limit) {
        return implode(' ', array_slice($words, 0, $word_limit)) . '...';
    }

    // 7. Trả về chuỗi đã xử lý (nếu không cần cắt bớt)
    return $string;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/** // ĐANG KHÔNG DÙNG
 * Kiểm tra xem mã nguồn HTML có khả năng cao đang sử dụng MathJax để hiển thị công thức toán học hay không.
 * Hàm này tập trung vào độ tin cậy cao, giảm thiểu cả âm tính giả và dương tính giả.
 *
 * @param string $htmlContent Nội dung mã nguồn HTML của trang web.
 * @return bool True nếu có độ tin cậy cao là trang sử dụng MathJax, False nếu không.
 */
function isUsingMathJax(string $htmlContent): bool
{
    if (empty($htmlContent)) {
        return false;
    }

    // --- Các dấu hiệu chính cần kiểm tra ---

    // Dấu hiệu 1: Nhúng thư viện MathJax (Rất quan trọng)
    // Kiểm tra các URL CDN phổ biến hoặc đường dẫn chứa 'mathjax'.
    // Sử dụng regex không phân biệt chữ hoa chữ thường (i) và linh hoạt với khoảng trắng (\s*).
    $mathjaxScriptPattern = '/<\s*script[^>]+src\s*=\s*["\'][^"\']*mathjax[^"\']*["\']/i';
    $foundMathJaxScript = preg_match($mathjaxScriptPattern, $htmlContent);

    // Dấu hiệu 2: Cấu hình MathJax (Quan trọng)
    // Tìm kiếm các khối script định nghĩa đối tượng cấu hình MathJax (MathJax = {...} hoặc window.MathJax = {...})
    $mathjaxConfigPattern = '/<\s*script[^>]*>\s*(?:window\s*\.\s*)?MathJax\s*=\s*\{/i';
    $foundMathJaxConfig = preg_match($mathjaxConfigPattern, $htmlContent);

    // Dấu hiệu 3: Sự hiện diện của các dấu phân cách (Delimiters) toán học phổ biến (Cần thiết để xác nhận ý định sử dụng)
    // Chỉ tìm kiếm các dấu phân cách này thôi là KHÔNG ĐỦ (dễ bị dương tính giả, ví dụ: $ trong giá tiền).
    // Chúng ta cần kết hợp dấu hiệu này với dấu hiệu 1 hoặc 2.
    // Các dấu phân cách phổ biến nhất và ít gây nhầm lẫn nhất:
    // - LaTeX/TeX: \[ ... \], \( ... \), $$ ... $$
    // - MathML: <math> ... </math>
    // Lưu ý: Dấu $...$ rất phổ biến nhưng cũng dễ nhầm lẫn, nên có thể bỏ qua nếu ưu tiên độ tin cậy tuyệt đối,
    // hoặc chỉ xem xét nếu có bằng chứng mạnh từ Dấu hiệu 1 hoặc 2.
    // AsciiMath (`...`) cũng dễ nhầm với code markdown.

    // Sử dụng strpos cho hiệu năng tốt hơn khi chỉ kiểm tra sự tồn tại
    $foundTexDisplayDelimiterStart = strpos($htmlContent, '\[') !== false;
    $foundTexDisplayDelimiterEnd = strpos($htmlContent, '\]') !== false;
    $foundTexInlineDelimiterStart = strpos($htmlContent, '\(') !== false;
    $foundTexInlineDelimiterEnd = strpos($htmlContent, '\)') !== false;
    $foundTexDoubleDollar = strpos($htmlContent, '$$') !== false; // Ít nhất một lần xuất hiện $$
    $foundMathMLTag = strpos($htmlContent, '<math') !== false; // Chỉ cần thẻ mở <math

    $foundAnyReliableDelimiter =
        ($foundTexDisplayDelimiterStart && $foundTexDisplayDelimiterEnd) || // Cần cả mở và đóng \[ \]
        ($foundTexInlineDelimiterStart && $foundTexInlineDelimiterEnd) ||   // Cần cả mở và đóng \( \)
        $foundTexDoubleDollar ||                                           // $$ thường dùng theo cặp, nhưng chỉ cần 1 cũng là dấu hiệu mạnh
        $foundMathMLTag;                                                  // <math> là dấu hiệu rất mạnh

    // --- Logic quyết định với độ tin cậy cao ---

    // Điều kiện cốt lõi: Phải có bằng chứng về việc nhúng hoặc cấu hình MathJax *VÀ* bằng chứng về sự hiện diện của công thức toán học (thông qua delimiters).
    // Lý do:
    // - Chỉ có script/config: Trang có thể nhúng thư viện nhưng không dùng (ví dụ: code cũ, template dùng chung). -> Tránh dương tính giả.
    // - Chỉ có delimiters: Các ký tự (như $) có thể xuất hiện vì lý do khác, hoặc trang dùng thư viện khác (KaTeX), hoặc trình duyệt tự render MathML. -> Tránh dương tính giả.

    if (($foundMathJaxScript || $foundMathJaxConfig) && $foundAnyReliableDelimiter) {
        // Trường hợp tin cậy cao nhất: Có cả thư viện/cấu hình VÀ dấu hiệu công thức.
        return true;
    }

    // --- Xem xét các trường hợp khác (thận trọng hơn) ---

    // Trường hợp 1: Chỉ tìm thấy Script hoặc Config nhưng KHÔNG có delimiters đáng tin cậy.
    // => Rủi ro dương tính giả cao. Quyết định là FALSE.

    // Trường hợp 2: Chỉ tìm thấy Delimiters đáng tin cậy (đặc biệt là <math> hoặc \[ \]) nhưng KHÔNG có Script/Config MathJax rõ ràng.
    // Có thể trang đang dùng MathML gốc, KaTeX, hoặc MathJax được load động/ẩn đi.
    // Vì yêu cầu là phát hiện *MathJax*, nên nếu không thấy dấu hiệu của MathJax (script/config), chúng ta nên trả về FALSE để đảm bảo độ đặc hiệu cho MathJax.
    // Nếu muốn phát hiện "trang có công thức toán" nói chung thì logic sẽ khác.
    // => Quyết định là FALSE.

    return false; // Mặc định là không sử dụng MathJax nếu không thỏa mãn điều kiện tin cậy cao.
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Giả sử các hàm fetchHtmlContentFinal, fetchHtmlWithPanther và các lớp Exception đã được định nghĩa.
/**
 * Lấy HTML từ URL, thử phương pháp nhanh (cURL) trước,
 * nếu thất bại do bị chặn hoặc nội dung không phù hợp thì chuyển sang dùng Panther.
 * Sử dụng trigger_error thay vì echo để ghi log.
 *
 * @param string $url URL cần lấy.
 * @param array $curlOptions Tùy chọn cho fetchHtmlContentFinal.
 * @param array $pantherOptions Tùy chọn cho fetchHtmlWithPanther (ví dụ: ['browser' => 'chrome', 'headless' => true, ...]).
 * @param callable|null $contentValidator Hàm callback để kiểm tra nội dung từ cURL (tùy chọn).
 *                                         Hàm này nhận nội dung HTML (string) và trả về true nếu hợp lệ, false nếu không.
 *
 * @return string|false HTML content hoặc false nếu cả hai phương pháp đều thất bại.
 * @throws \Exception Nếu có lỗi không mong muốn xảy ra và không được xử lý nội bộ.
 */
// Hàm này fetch nội dung thông minh hơn bằng cách sử dụng biện pháp cURL đơn giản trước, sau đó mới sử dụng biện pháp giải lập người truy cập sau
// Giả lập người truy cập thật có thể làm tốn thêm 30 - 40s
function fetchHtmlSmart(
    string $url,    
    array $curlOptions = [],
    array $pantherOptions = [],        
    ?callable $contentValidator = null
): string|false {
    $htmlContent = false;
    $lastError = null;
    $usedMethod = '';

    // --- Bước 1: Thử với fetchHtmlContentFinal (cURL) ---
    try {
        //trigger_error("fetchHtmlSmart: Thử lấy HTML bằng cURL cho: " . $url, E_USER_NOTICE);
        $usedMethod = 'cURL (fetchHtmlContentFinal)';
        $htmlContent = fetchHtmlContentFinal($url, $curlOptions);

        // --- (Tùy chọn) Kiểm tra nội dung ---
        if ($htmlContent && $contentValidator !== null) {
            if (!$contentValidator($htmlContent)) {
                //trigger_error("fetchHtmlSmart: cURL thành công nhưng nội dung không hợp lệ. Chuyển sang Panther. URL: " . $url, E_USER_WARNING);
                $htmlContent = false; // Đánh dấu để chuyển sang Panther
                $lastError = new FetchContentException("Nội dung từ cURL không vượt qua kiểm tra validator.");
            } else {
                //trigger_error("fetchHtmlSmart: Lấy HTML bằng cURL thành công và nội dung hợp lệ. URL: " . $url, E_USER_NOTICE);
                return $htmlContent; // Thành công, trả về luôn
            }
        } elseif ($htmlContent) {
            //trigger_error("fetchHtmlSmart: Lấy HTML bằng cURL thành công. URL: " . $url, E_USER_NOTICE);
            return $htmlContent; // Thành công (không có validator), trả về luôn
        }
        // Nếu $htmlContent là false từ fetchHtmlContentFinal (vd: do lỗi nhưng đã retry hết)
        // thì sẽ tự động đi xuống phần fallback mà không cần gán lại $htmlContent = false;
        // Tuy nhiên, cần đảm bảo fetchHtmlContentFinal ném exception nếu thất bại cuối cùng.


    } catch (FetchContentHttpException $e) {
        $httpCode = $e->getCode();
        //trigger_error("fetchHtmlSmart: Lỗi HTTP khi dùng cURL (Code " . $httpCode . "): " . $e->getMessage() . ". URL: " . $url, E_USER_WARNING);
        $lastError = $e;
        $shouldFallback = in_array($httpCode, [403, 401, 429]); // Các mã lỗi thường gặp khi bị chặn

        if ($shouldFallback) {
            //trigger_error("fetchHtmlSmart: Mã lỗi HTTP (" . $httpCode . ") cho thấy có thể bị chặn. Chuyển sang Panther. URL: " . $url, E_USER_NOTICE);
            $htmlContent = false; // Đảm bảo chuyển sang Panther
        } else {
             //trigger_error("fetchHtmlSmart: Lỗi HTTP (" . $httpCode . ") từ cURL không thuộc diện fallback. Thất bại. URL: " . $url, E_USER_WARNING);
             return false; // Không fallback cho lỗi này
        }
    } catch (FetchContentCurlException $e) {
        //trigger_error("fetchHtmlSmart: Lỗi cURL không thể khắc phục: " . $e->getMessage() . ". Chuyển sang Panther. URL: " . $url, E_USER_WARNING);
        $lastError = $e;
        $htmlContent = false;
    } catch (FetchContentException $e) {
        //trigger_error("fetchHtmlSmart: Lỗi FetchContent chung từ cURL: " . $e->getMessage() . ". Chuyển sang Panther. URL: " . $url, E_USER_WARNING);
        $lastError = $e;
        $htmlContent = false;
    } catch (\Throwable $e) {
        // Lỗi không mong muốn khác khi dùng cURL
        //trigger_error("fetchHtmlSmart: Lỗi không xác định khi dùng cURL: " . $e->getMessage() . ". Thử chuyển sang Panther. URL: " . $url, E_USER_WARNING);
        $lastError = $e;
        $htmlContent = false;
    }

    // --- Bước 2: Fallback sang fetchHtmlWithPanther ---
    // Điều kiện $htmlContent === false bao gồm cả trường hợp cURL trả về false/rỗng mà không ném exception,
    // và các trường hợp exception ở trên đã gán $htmlContent = false.
    if ($htmlContent === false) {
        if (empty($pantherOptions['browser'])) {
             trigger_error("fetchHtmlSmart: Thiếu cấu hình 'browser' cho Panther. Không thể fallback. URL: " . $url, E_USER_ERROR); // Lỗi nghiêm trọng, dừng lại
             return false; // Hoặc trả về false nếu E_USER_ERROR không dừng script
        }

        try {
            //trigger_error("fetchHtmlSmart: Chuyển sang lấy HTML bằng Panther cho: " . $url, E_USER_NOTICE);
            $usedMethod = 'Panther (fetchHtmlWithPantherImproved)';
            $pantherHtml = fetchHtmlWithPantherImproved(
                $url,
                $pantherOptions['browser']
            );

            if ($pantherHtml !== false && $pantherHtml !== '') { // Kiểm tra cả rỗng
                //trigger_error("fetchHtmlSmart: Lấy HTML bằng Panther thành công. URL: " . $url, E_USER_NOTICE);
                return $pantherHtml;
            } else {
                 // Panther trả về false hoặc rỗng
                 $errorMessage = "Panther cũng thất bại hoặc trả về nội dung rỗng.";
                 if ($lastError) { // Nối thêm thông tin lỗi trước đó nếu có
                      $errorMessage .= " Lỗi trước đó (cURL): " . $lastError->getMessage();
                 }
                 //trigger_error("fetchHtmlSmart: " . $errorMessage . " URL: " . $url, E_USER_WARNING);
                 $lastError = new \RuntimeException("Panther failed or returned empty content for URL: " . $url); // Cập nhật lastError
                 return false;
            }
        } catch (\Throwable $e) {
            trigger_error("fetchHtmlSmart: Lỗi nghiêm trọng khi chạy Panther: " . $e->getMessage() . ". URL: " . $url, E_USER_WARNING); // Dùng WARNING thay vì ERROR để hàm có thể trả về false
            $lastError = $e; // Ghi lại lỗi Panther
            return false;
        }
    }

    // Trường hợp logic không nên đến đây, nhưng để phòng ngừa
    trigger_error("fetchHtmlSmart: Không thể lấy HTML cho URL: " . $url . " bằng cả cURL và Panther (trạng thái không mong muốn).", E_USER_WARNING);
    
    if ($lastError) {
        trigger_error("fetchHtmlSmart: Lỗi cuối cùng gặp phải (" . $usedMethod . "): " . $lastError->getMessage() . ". URL: " . $url, E_USER_WARNING);
    }
    
    return false;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Đếm số từ gần đúng trong một chuỗi HTML.
 * Loại bỏ các thẻ HTML trước khi đếm.
 *
 * @param string $htmlContent Nội dung HTML.
 * @return int Số lượng từ ước tính.
 */
function estimateWordCountHtml(string $htmlContent): int
{
    // Loại bỏ thẻ HTML
    $text = strip_tags($htmlContent);
    // Loại bỏ các khoảng trắng thừa và xuống dòng để đếm chính xác hơn
    $text = preg_replace('/\s+/', ' ', trim($text));
    if (empty($text)) {
        return 0;
    }
    // Đếm từ bằng cách tách theo khoảng trắng
    return count(explode(' ', $text));
    // Hoặc sử dụng str_word_count nếu locale phù hợp
    // return str_word_count($text);
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Loại bỏ các dòng khỏi văn bản Markdown dựa trên một mảng cấu hình chi tiết cho từng marker.
 * Cho phép xác định chuỗi con marker, độ dài tối đa tùy chọn của dòng chứa marker để xóa,
 * và tùy chọn phân biệt chữ hoa/thường cho từng marker.
 * Bảo toàn các dòng trống để giữ cấu trúc đoạn Markdown gốc.
 *
 * @param string $markdownContent Nội dung Markdown đầu vào.
 * @param array<array{marker: string, maxLength?: int|null, caseSensitive?: bool}> $markerConfigs
 *        Mảng chứa các cấu hình marker. Mỗi cấu hình là một mảng kết hợp:
 *        - 'marker' (string, bắt buộc): Chuỗi con cần tìm.
 *        - 'maxLength' (int|null, tùy chọn): Nếu được đặt, chỉ xóa dòng nếu độ dài của dòng (đã trim)
 *                                            NHỎ HƠN giá trị này. Nếu null hoặc không có, không áp dụng kiểm tra độ dài.
 *        - 'caseSensitive' (bool, tùy chọn): True (mặc định) để phân biệt hoa/thường khi tìm marker,
 *                                              False để không phân biệt.
 * @return string Nội dung Markdown sau khi đã loại bỏ các dòng theo cấu hình.
 */
function removeLinesByMarkerConfig(string $markdownContent, array $markerConfigs): string
{
    // 1. Xử lý trường hợp đầu vào rỗng hoặc cấu hình rỗng
    if (trim($markdownContent) === '' || empty($markerConfigs)) {
        return $markdownContent;
    }

    // 2. Lọc bỏ các cấu hình không hợp lệ (thiếu 'marker' hoặc marker rỗng)
    $validConfigs = array_filter($markerConfigs, function ($cfg) {
        return isset($cfg['marker']) && is_string($cfg['marker']) && $cfg['marker'] !== '';
    });

    if (empty($validConfigs)) {
        // Nếu không còn cấu hình hợp lệ nào sau khi lọc
        return $markdownContent;
    }

    // 3. Tách văn bản thành các dòng, bao gồm cả dòng trống
    $lines = preg_split('/\R/', $markdownContent);
    $filteredLines = []; // Mảng để lưu các dòng được giữ lại

    // 4. Lặp qua từng dòng gốc
    foreach ($lines as $line) {
        $trimmedLine = trim($line); // Trim để kiểm tra dòng trống và tính độ dài

        // 5. Luôn giữ lại các dòng trống (hoặc chỉ chứa khoảng trắng)
        if ($trimmedLine === '') {
            $filteredLines[] = $line; // Giữ lại dòng gốc
            continue; // Chuyển sang dòng tiếp theo
        }

        // 6. Kiểm tra dòng không trống với từng cấu hình marker
        $shouldRemoveLine = false; // Cờ quyết định xóa dòng
        foreach ($validConfigs as $config) {
            $marker = $config['marker'];
            $maxLength = $config['maxLength'] ?? null; // Lấy maxLength hoặc null nếu không có
            $caseSensitive = $config['caseSensitive'] ?? true; // Mặc định phân biệt hoa/thường

            // 7. Thực hiện tìm kiếm marker (phân biệt hoặc không phân biệt hoa/thường)
            $found = false;
            if ($caseSensitive) {
                // str_contains là PHP 8+, phân biệt hoa thường
                $found = str_contains($line, $marker);
                // Nếu dùng PHP < 8: $found = (strpos($line, $marker) !== false);
            } else {
                // stripos không phân biệt hoa thường
                $found = (stripos($line, $marker) !== false);
            }

            // 8. Xử lý nếu tìm thấy marker
            if ($found) {
                // Kiểm tra điều kiện độ dài nếu được cấu hình
                if ($maxLength !== null) {
                    // Có ràng buộc độ dài. Chỉ đánh dấu xóa nếu dòng đủ ngắn.
                    // Sử dụng mb_strlen để đếm ký tự chính xác (quan trọng cho multi-byte)
                    if (mb_strlen($trimmedLine) < $maxLength) {
                        $shouldRemoveLine = true;
                        break; // Đã tìm thấy lý do xóa, không cần check config khác
                    } else {
                        // Dòng quá dài, không xóa dựa trên config này.
                        // TIẾP TỤC vòng lặp để kiểm tra các config marker khác
                        // (có thể một marker khác không có giới hạn độ dài khớp)
                        continue;
                    }
                } else {
                    // Không có ràng buộc độ dài => Đánh dấu xóa luôn
                    $shouldRemoveLine = true;
                    break; // Đã tìm thấy lý do xóa, không cần check config khác
                }
            }
            // Nếu không tìm thấy marker ($found == false), tự động chuyển sang config tiếp theo
        } // Kết thúc vòng lặp qua các config marker

        // 9. Quyết định giữ lại dòng
        if (!$shouldRemoveLine) {
            $filteredLines[] = $line; // Giữ lại dòng gốc nếu không bị đánh dấu xóa
        }
    } // Kết thúc vòng lặp qua các dòng

    // 10. Nối các dòng đã lọc lại, bảo toàn cấu trúc dòng trống
    return implode("\n", $filteredLines);
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Loại bỏ các dòng khỏi văn bản Markdown dựa trên một mảng cấu hình chi tiết cho từng marker.
 * Cho phép xác định chuỗi con marker, độ dài tối đa tùy chọn của dòng chứa marker để xóa,
 * và tùy chọn phân biệt chữ hoa/thường cho từng marker.
 * Bảo toàn các dòng trống để giữ cấu trúc đoạn Markdown gốc.
 * Đảm bảo xử lý chuỗi an toàn với UTF-8 (quan trọng cho tiếng Trung, Nhật, Hàn,...).
 *
 * @param string $markdownContent Nội dung Markdown đầu vào. **Quan trọng: Chuỗi này PHẢI là UTF-8 hợp lệ.**
 *                                Hàm này không sửa lỗi encoding đầu vào.
 * @param array<array{marker: string, maxLength?: int|null, caseSensitive?: bool}> $markerConfigs
 *        Mảng chứa các cấu hình marker. Mỗi cấu hình là một mảng kết hợp:
 *        - 'marker' (string, bắt buộc): Chuỗi con cần tìm (phải là UTF-8 hợp lệ).
 *        - 'maxLength' (int|null, tùy chọn): Nếu được đặt, chỉ xóa dòng nếu độ dài *ký tự* (dùng mb_strlen) của dòng (đã trim UTF-8)
 *                                            NHỎ HƠN giá trị này. Nếu null hoặc không có, không áp dụng kiểm tra độ dài.
 *        - 'caseSensitive' (bool, tùy chọn): True (mặc định) để phân biệt hoa/thường khi tìm marker,
 *                                              False để không phân biệt (sử dụng mb_stripos).
 *
 * @return string Nội dung Markdown sau khi đã loại bỏ các dòng theo cấu hình, giữ nguyên UTF-8.
 *                Trả về chuỗi đầu vào nếu có lỗi hoặc không có gì để xử lý.
 *
 * @throws \Exception Nếu extension mbstring không khả dụng (cần cho mb_strlen, mb_stripos, mb_check_encoding).
 * @throws \Exception Nếu extension PCRE không hỗ trợ UTF-8 (rất hiếm).
 */
function removeLinesByMarkerConfig_UTF8_Safe(string $markdownContent, array $markerConfigs): string
{
    // --- Kiểm tra phụ thuộc ---
    if (!extension_loaded('mbstring')) {
        throw new \Exception("Extension 'mbstring' là yêu cầu bắt buộc để [UTF-8 safe operation].");
    }
    // Kiểm tra hỗ trợ UTF-8 của PCRE (thường luôn bật)
    if (@preg_match('/\pL/u', 'a') !== 1) {
         throw new \Exception("PCRE extension không hỗ trợ UTF-8 ('u' modifier).");
    }

    // --- Input Validation ---
    // 1. Kiểm tra encoding đầu vào (rất quan trọng!)
    if (!mb_check_encoding($markdownContent, 'UTF-8')) {
        // Ghi log hoặc xử lý lỗi: Đầu vào không phải UTF-8 hợp lệ!
        // Hàm này không thể sửa lỗi encoding, trả về nguyên gốc để tránh làm hỏng thêm.
        error_log("Cảnh báo: Nội dung markdown đầu vào không phải là UTF-8 hợp lệ. Trả lại nội dung gốc.");
        return $markdownContent;
    }

    // 2. Xử lý trường hợp nội dung gần như rỗng (chỉ chứa khoảng trắng) hoặc cấu hình rỗng
    // Sử dụng regex trim để xử lý khoảng trắng Unicode an toàn
    $trimmedInput = preg_replace('/^\s+|\s+$/u', '', $markdownContent);
    if ($trimmedInput === '' || empty($markerConfigs)) {
        return $markdownContent;
    }

    // 3. Lọc bỏ các cấu hình không hợp lệ (thiếu 'marker', marker không phải string/rỗng, hoặc marker không phải UTF-8)
    $validConfigs = array_filter($markerConfigs, function ($cfg) {
        return isset($cfg['marker'])
            && is_string($cfg['marker'])
            && $cfg['marker'] !== ''
            && mb_check_encoding($cfg['marker'], 'UTF-8'); // Đảm bảo marker cũng là UTF-8
    });

    if (empty($validConfigs)) {
        // Nếu không còn cấu hình hợp lệ nào sau khi lọc
        return $markdownContent;
    }

    // --- Processing ---
    // 4. Tách văn bản thành các dòng bằng regex với modifier 'u' (UTF-8)
    //    \R khớp với các loại dấu xuống dòng Unicode (LF, CRLF, CR, ...)
    $lines = preg_split('/\R/u', $markdownContent);
    if ($lines === false) {
        // Lỗi preg_split hiếm gặp
        error_log("Lỗi: preg_split không thành công trong quá trình tách dòng.");
        return $markdownContent; // Trả về nguyên gốc để an toàn
    }

    $filteredLines = []; // Mảng để lưu các dòng được giữ lại

    // 5. Lặp qua từng dòng gốc
    foreach ($lines as $line) {
        // Trim dòng bằng regex UTF-8 safe để kiểm tra và tính độ dài
        $trimmedLine = preg_replace('/^\s+|\s+$/u', '', $line);
        // Lưu ý: $line vẫn là dòng gốc, chưa trim, để giữ lại khoảng trắng nếu dòng được giữ

        // 6. Luôn giữ lại các dòng trống (hoặc chỉ chứa khoảng trắng Unicode)
        if ($trimmedLine === '') {
            $filteredLines[] = $line; // Giữ lại dòng gốc bao gồm cả khoảng trắng ban đầu
            continue; // Chuyển sang dòng tiếp theo
        }

        // 7. Kiểm tra dòng không trống với từng cấu hình marker
        $shouldRemoveLine = false; // Cờ quyết định xóa dòng
        foreach ($validConfigs as $config) {
            $marker = $config['marker'];
            $maxLength = $config['maxLength'] ?? null;
            $caseSensitive = $config['caseSensitive'] ?? true;

            // 8. Thực hiện tìm kiếm marker (sử dụng hàm mbstring nếu không phân biệt hoa thường)
            $found = false;
            if ($caseSensitive) {
                // str_contains là UTF-8 safe trong PHP 8+
                // Nếu dùng PHP < 8, nên dùng mb_strpos: $found = (mb_strpos($line, $marker, 0, 'UTF-8') !== false);
                if (function_exists('str_contains')) {
                     $found = str_contains($line, $marker);
                } else {
                     // Fallback cho PHP < 8, dùng mb_strpos
                     $found = (mb_strpos($line, $marker, 0, 'UTF-8') !== false);
                }
            } else {
                // mb_stripos không phân biệt hoa thường, an toàn với UTF-8
                $found = (mb_stripos($line, $marker, 0, 'UTF-8') !== false);
            }

            // 9. Xử lý nếu tìm thấy marker
            if ($found) {
                // Kiểm tra điều kiện độ dài nếu được cấu hình
                if ($maxLength !== null) {
                    // Có ràng buộc độ dài. Chỉ đánh dấu xóa nếu dòng đủ ngắn.
                    // Sử dụng mb_strlen để đếm ký tự chính xác (quan trọng cho multi-byte)
                    if (mb_strlen($trimmedLine, 'UTF-8') < $maxLength) {
                        $shouldRemoveLine = true;
                        break; // Đã tìm thấy lý do xóa, không cần check config khác
                    } else {
                        // Dòng quá dài, không xóa dựa trên config này.
                        // TIẾP TỤC vòng lặp config để kiểm tra marker khác.
                        continue;
                    }
                } else {
                    // Không có ràng buộc độ dài => Đánh dấu xóa luôn
                    $shouldRemoveLine = true;
                    break; // Đã tìm thấy lý do xóa, không cần check config khác
                }
            }
            // Nếu không tìm thấy marker ($found == false), tự động chuyển sang config tiếp theo
        } // Kết thúc vòng lặp qua các config marker

        // 10. Quyết định giữ lại dòng
        if (!$shouldRemoveLine) {
            $filteredLines[] = $line; // Giữ lại dòng gốc nếu không bị đánh dấu xóa
        }
    } // Kết thúc vòng lặp qua các dòng

    // 11. Nối các dòng đã lọc lại, sử dụng "\n" làm dấu nối (chuẩn cho nhiều hệ thống)
    // Đảm bảo output cũng là UTF-8 hợp lệ vì input và các thao tác đều là UTF-8.
    $result = implode("\n", $filteredLines);

    // Kiểm tra lại output cuối cùng (đề phòng, nhưng ít khả năng bị lỗi ở bước này nếu input đúng)
    if (!mb_check_encoding($result, 'UTF-8')) {
         error_log("Lỗi nghiêm trọng: Chuỗi đầu ra trở thành UTF-8 không hợp lệ sau khi xử lý!");
         // Có thể trả về $markdownContent gốc hoặc throw Exception tùy chiến lược xử lý lỗi
         return $markdownContent;
    }

    return $result;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Kiểm tra nhanh xem có bất kỳ marker nào từ cấu hình tồn tại trong nội dung hay không.
 * Hàm này nhanh hơn nhiều so với việc phân tích từng dòng, dùng để quyết định
 * xem có cần gọi hàm xử lý đầy đủ removeLinesByMarkerConfig_UTF8_Safe hay không.
 * **Lưu ý:** Hàm này KHÔNG kiểm tra điều kiện maxLength.
 *
 * @param string $content Nội dung cần kiểm tra (PHẢI là UTF-8 hợp lệ).
 * @param array<array{marker: string, caseSensitive?: bool}> $markerConfigs Mảng cấu hình marker.
 *
 * @return bool True nếu tìm thấy ít nhất một marker, False nếu không tìm thấy marker nào.
 *
 * @throws \Exception Nếu extension mbstring không khả dụng.
 */
function checkIfAnyMarkerExists_UTF8_Safe(string $content, array $markerConfigs): bool
{
    // --- Dependency Check ---
    if (!extension_loaded('mbstring')) {
        // Hoặc log lỗi và return false/true tùy theo logic mong muốn khi thiếu mbstring
        throw new \Exception("Extension 'mbstring' is required for UTF-8 safe operation.");
    }

    // --- Input Validation ---
     // 1. Kiểm tra encoding đầu vào
    if (!mb_check_encoding($content, 'UTF-8')) {
        error_log("Warning: Input content for pre-check is not valid UTF-8.");
        // Quyết định trả về false (coi như không tìm thấy để tránh xử lý sai)
        // hoặc true (để hàm chính xử lý và log lỗi sau) tùy thuộc vào yêu cầu.
        // Trả về false có vẻ an toàn hơn ở bước pre-check.
        return false;
    }

    // 2. Xử lý trường hợp nội dung/cấu hình rỗng
    // Dùng trim thường vì không cần regex phức tạp ở đây, chỉ cần check rỗng cơ bản
    if (trim($content) === '' || empty($markerConfigs)) {
        return false; // Không có gì để tìm hoặc không có gì để tìm trong đó
    }

    // --- Marker Checking ---
    foreach ($markerConfigs as $config) {
        // Đảm bảo marker hợp lệ trước khi tìm kiếm
        if (!isset($config['marker']) || !is_string($config['marker']) || $config['marker'] === '' || !mb_check_encoding($config['marker'], 'UTF-8')) {
            continue; // Bỏ qua cấu hình không hợp lệ
        }

        $marker = $config['marker'];
        $caseSensitive = $config['caseSensitive'] ?? true;

        $found = false;
        if ($caseSensitive) {
            // str_contains (PHP 8+) là tối ưu nhất và UTF-8 safe
            if (function_exists('str_contains')) {
                 $found = str_contains($content, $marker);
            } else {
                 // Fallback cho PHP < 8, dùng mb_strpos (nhanh hơn mb_strstr)
                 $found = (mb_strpos($content, $marker, 0, 'UTF-8') !== false);
            }
        } else {
            // mb_stripos cho tìm kiếm không phân biệt hoa thường, UTF-8 safe
            $found = (mb_stripos($content, $marker, 0, 'UTF-8') !== false);
        }

        // Nếu tìm thấy BẤT KỲ marker nào, dừng ngay và trả về true
        if ($found) {
            return true;
        }
    }

    // Nếu duyệt hết các marker mà không tìm thấy cái nào
    return false;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
* Loại bỏ các khoảng xuống dòng thừa trong chuỗi Markdown.
* Thay thế 3 hoặc nhiều ký tự xuống dòng liên tiếp bằng 2 ký tự xuống dòng.
*
* @param string $markdownContent Nội dung Markdown đầu vào.
* @return string Nội dung Markdown đã xử lý.
*/
function removeExcessiveNewlines(string $markdownContent): string {
    // \R khớp với bất kỳ ký tự xuống dòng nào (bao gồm \n, \r\n, \r)
    // {3,} khớp với 3 hoặc nhiều lần xuất hiện liên tiếp của ký tự trước đó (\R)
    // Thay thế chuỗi khớp được bằng "\n\n" (hai ký tự xuống dòng)
    $cleanedContent = preg_replace('/\R{3,}/', "\n\n", $markdownContent);

    // Loại bỏ khoảng trắng hoặc xuống dòng thừa ở đầu và cuối chuỗi
    $cleanedContent = trim($cleanedContent); 

    return $cleanedContent;
}



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Kiểm tra nhanh một link có phải là PDF hay không, cái này giúp tránh người dùng nhập nhầm vào ô dịch web một đường link PDF
// Đã cố gắng hạn chế dương tính giả
/**
 * @param string $url
 * @return bool
 */
function isLikelyPdfLink(string $url): bool
{
    // 1. Kiểm tra URL có hợp lệ về cấu trúc không
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    // 2. Phân tích URL để lấy phần path và query
    $path = parse_url($url, PHP_URL_PATH);
    $queryString = parse_url($url, PHP_URL_QUERY);

    // 3. Ưu tiên kiểm tra phần mở rộng của file trong path
    // Đây là dấu hiệu mạnh nhất và phổ biến nhất
    if ($path) {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (strtolower($extension) === 'pdf') {
            return true;
        }
    }

    // 4. Kiểm tra trong query string (ít phổ biến hơn nhưng vẫn có thể)
    // Đặc biệt là các tham số liên quan đến 'content-disposition' hoặc 'filename'
    if ($queryString) {
        parse_str($queryString, $queryParams); // Tự động urldecode các giá trị

        foreach ($queryParams as $key => $value) {
            // Chuyển key và value về chữ thường để so sánh không phân biệt hoa thường
            $lowerKey = strtolower($key);
            $lowerValue = is_string($value) ? strtolower($value) : '';

            // A. Kiểm tra các key phổ biến cho content disposition hoặc filename
            if (strpos($lowerKey, 'disposition') !== false || strpos($lowerKey, 'filename') !== false) {
                // Kiểm tra value có chứa 'filename=' và kết thúc bằng '.pdf'
                // Ví dụ: response-content-disposition=inline; filename=MyFile.pdf
                // Hoặc: attachment; filename="document.PDF"
                if (preg_match('/filename\s*=\s*["\']?([^"\';]+\.pdf)/i', $value)) {
                    return true;
                }
            }

            // B. Đôi khi tên file PDF nằm trực tiếp trong giá trị của một tham số
            // Ví dụ: ?file=report.pdf hoặc &document=summary.PDF
            // Cần cẩn thận với trường hợp này để tránh dương tính giả
            // Chỉ xem xét nếu key có vẻ liên quan đến tên file
            if (in_array($lowerKey, ['file', 'filename', 'document', 'doc', 'path', 'src']) &&
                is_string($value) && // Đảm bảo value là string
                strlen($value) > 4 && // Ngắn hơn ".pdf" thì không phải
                strtolower(substr(trim($value), -4)) === '.pdf'
            ) {
                // Thêm kiểm tra để tránh trường hợp giá trị chỉ là ".pdf"
                if (strlen(trim($value)) > strlen('.pdf')) {
                     return true;
                }
            }
        }
    }

    return false;
}



/**
 * Kiểm tra và cố gắng chuyển đổi một chuỗi sang UTF-8 hợp lệ.
 *
 * @param string $string Chuỗi đầu vào.
 * @param bool $force_fix Nếu true, sẽ cố gắng sửa lỗi UTF-8 ngay cả khi không phát hiện được encoding gốc.
 * @return string Chuỗi đã được đảm bảo là UTF-8 hoặc chuỗi gốc nếu không thể chuyển đổi.
 * // ensure_utf8(string $string, bool $force_fix = true)
 */
function fix_input_ensure_utf8(string $string, bool $force_fix = true): string
{
    // 1. Kiểm tra xem có phải là UTF-8 hợp lệ không
    if (mb_check_encoding($string, 'UTF-8')) {
        // Đôi khi, mb_check_encoding trả về true ngay cả khi có một vài byte lỗi nhỏ.
        // Bước "làm sạch" này có thể hữu ích để loại bỏ chúng.
        // Nó sẽ thay thế các byte không hợp lệ bằng ký tự thay thế của Unicode (U+FFFD).
        // return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return $string;
    }

    // 2. Cố gắng phát hiện encoding gốc
    // mb_detect_order() có thể được tùy chỉnh nếu bạn biết các encoding có khả năng cao
    // ví dụ: mb_detect_order(['ASCII', 'ISO-8859-1', 'Windows-1252', 'UTF-8']);
    $current_encoding = mb_detect_encoding($string, mb_detect_order(), true); // true cho chế độ strict

    if ($current_encoding) {
        // 3. Chuyển đổi sang UTF-8 từ encoding đã phát hiện
        $converted_string = mb_convert_encoding($string, 'UTF-8', $current_encoding);
        if ($converted_string !== false && mb_check_encoding($converted_string, 'UTF-8')) {
            return $converted_string;
        }
    }

    // 4. Nếu không phát hiện được hoặc chuyển đổi thất bại, và $force_fix là true
    if ($force_fix) {
        // Phương án này có thể làm mất dữ liệu hoặc thay thế ký tự không mong muốn.
        // Nó giả định đầu vào có thể là ISO-8859-1 (Latin-1) hoặc Windows-1252 bị đọc sai.
        // 'iconv' với //IGNORE hoặc //TRANSLIT có thể hữu ích ở đây.
        // //IGNORE: loại bỏ các ký tự không thể chuyển đổi.
        // //TRANSLIT: cố gắng phiên âm các ký tự không thể chuyển đổi.
        $cleaned_string_iconv = iconv('ISO-8859-1', 'UTF-8//IGNORE', $string); // Thử với ISO-8859-1 làm nguồn phổ biến
        if ($cleaned_string_iconv !== false && mb_check_encoding($cleaned_string_iconv, 'UTF-8')) {
            return $cleaned_string_iconv;
        }

        // Phương án cuối cùng: "làm sạch" bằng cách ép nó thành UTF-8,
        // điều này sẽ thay thế các byte không hợp lệ bằng ký tự thay thế U+FFFD (�).
        // Đây là cách mà mb_convert_encoding($string, 'UTF-8', 'UTF-8') ở trên thực hiện.
        // Tuy nhiên, nếu đầu vào là encoding hoàn toàn khác (ví dụ Shift_JIS nhưng không được phát hiện),
        // kết quả sẽ là một mớ hỗn độn các ký tự �.
        return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
    }

    // Nếu không force_fix và không thể chuyển đổi, trả về chuỗi gốc để người dùng tự xử lý
    // hoặc bạn có thể throw một Exception ở đây.
    // trigger_error("Không thể chuyển đổi chuỗi sang UTF-8 hợp lệ.", E_USER_WARNING);
    return $string;
}