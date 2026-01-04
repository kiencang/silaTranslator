<?php
// File: translation_PDF_HTML.php // Dịch tài liệu PDF từ tiếng Anh sang tiếng Việt
set_time_limit(2400); // 40 phút chạy hàm tối đa // Giới hạn này thường đủ tốt
date_default_timezone_set('Asia/Ho_Chi_Minh'); // Thiết lập giờ theo giờ Việt Nam
ini_set('memory_limit', '1024M'); // Đảm bảo bộ nhớ đủ để xử lý



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Gỡ lỗi, kiểm tra, quan trọng trước khi phân phối sản phẩm đến người dùng cuối
// Báo cáo TẤT CẢ các loại lỗi
//error_reporting(E_ALL); 

// Hiển thị lỗi ra màn hình (thay vì chỉ ghi vào log)
//ini_set('display_errors', 1); 

// Hiển thị cả các lỗi xảy ra trong quá trình khởi động PHP
//ini_set('display_startup_errors', 1); 

// require_once __DIR__ . DIRECTORY_SEPARATOR . 'error_config.php'; // ghi lỗi vào file để theo dõi
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Phần cấu hình và nạp thư viện (Giữ nguyên) ---
// Đảm bảo file config.php tồn tại và đúng đường dẫn
$config_Path = __DIR__ . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'config.php';

if (file_exists($config_Path)) {
    require_once $config_Path;
} else {
    die("Lỗi nghiêm trọng: Không tìm thấy file cấu hình config.php trong myself!");
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Bắt buộc phải có dòng này để nạp thư viện Guzzle đã cài bằng Composer
require 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


require_once 'functions/smallFunctions.php'; // Các hàm nhỏ
require_once 'functions/callGeminiApiPdfDynamic.php'; // Gọi hàm để cải tiến prompt & system instructions // Dù mặc định thường cũng đủ tốt
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Cấu hình ---
// Đường dẫn hoàng chỉnh
$geminiApiUrl = GEMINI_API_URL; // Đã có đủ model và key ở đường dẫn này
$maxFileSize = 15 * 1024 * 1024; // Giới hạn kích thước file tải lên (15 MB)
$outputDirName = 'pdf_html_show'; // Tên thư mục lưu file HTML bản dịch
$outputDir = __DIR__ . DIRECTORY_SEPARATOR . $outputDirName; // Đường dẫn tuyệt đối


// Biến lưu trữ kết quả hoặc lỗi
$errorText = null; // Thông báo lỗi
$successLink = null; // Link đến file HTML đã lưu
$savedHtmlPath = null; // Đường dẫn file HTML đã lưu trên server
$savedHtmlFilename = null; // Tên file HTML đã lưu
$processing_complete = false; // Đã xử lý xong chưa?
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Xử lý khi form được submit (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (GEMINI_API_KEY != '') { // Phải có API KEY mới thực hiện gọi hàm
        $start_time = microtime(true); // Ghi lại thời gian bắt đầu, để đo thời gian dịch
        
        // Biến lưu trữ kết quả hoặc lỗi
        $errorText = null;
        $successLink = null; // Link đến file HTML đã lưu
        $savedHtmlPath = null; // Đường dẫn file HTML đã lưu trên server
        $savedHtmlFilename = null; // Tên file HTML đã lưu    
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        
        
        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------        
        // 1. Kiểm tra file tải lên
        if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) { // Kiểm tra xem tệp có tải lên thành công hay không
            $file = $_FILES['pdfFile'];
            $originalFilename = basename($file['name']); // Lấy tên file gốc, loại bỏ path traversal
            $filenameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
               
            // 2. Kiểm tra loại file và kích thước
            if ($file['type'] !== 'application/pdf') { // Kiểm tra định dạng của tệp
                $errorText = "Lỗi: Chỉ chấp nhận file định dạng PDF.";
            } elseif ($file['size'] > $maxFileSize) { // Kiểm tra dung lưỡng ngưỡng được phép, ngưỡng này nên thấp hơn ngưỡng cho phép của Gemini
                $errorText = "Lỗi: Kích thước file vượt quá giới hạn cho phép (" . ($maxFileSize / 1024 / 1024) . " MB).";
            } else {
                // 4. Đọc file và Base64
                $pdfFilePath = $file['tmp_name'];
                try {
                    $pdfData = file_get_contents($pdfFilePath); // Lấy nội dung
                    if ($pdfData === false) {
                        throw new Exception("Không thể đọc file PDF tạm thời.");
                    }
                    // Chuyển sang dạng base64
                    $base64Pdf = base64_encode($pdfData); // Sẽ truyền thông tin này lên AI
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
 
                    
                    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    // Lấy thông tin prompt & system instructions
                    $prompt = ''; 
                    // Lấy đường dẫn file prompt
                    $promptFilePath = 'default_prompt' . DIRECTORY_SEPARATOR . 'setPDFtoHTML' . DIRECTORY_SEPARATOR . 'prompt.md';

                    if (file_exists($promptFilePath)) {
                        // Cố gắng đọc file
                        $promptContentFG = file_get_contents($promptFilePath);
                        if ($promptContentFG !== false && $promptContentFG != '') {
                             $prompt = $promptContentFG; // lấy được thông tin prompt tùy chỉnh
                        }
                    }

                    $systemInstruction = '';
                    // Lấy đường dẫn file SI
                    $systemFilePath = 'default_prompt' . DIRECTORY_SEPARATOR . 'setPDFtoHTML' . DIRECTORY_SEPARATOR . 'system_instructions.md';

                    if (file_exists($systemFilePath)) {
                        // Cố gắng đọc file
                        $systemContentFG = file_get_contents($systemFilePath);
                        if ($systemContentFG !== false && $systemContentFG != '') {
                             $systemInstruction = $systemContentFG; // lấy được thông tin prompt tùy chỉnh
                        }
                    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
              
            
            
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // Thực hiện tạo prompt và SI tùy chỉnh // Khá tốn kém & hiệu quả không cao được như với trường hợp dịch web
                    // Chỉ số này độc lập với bên dịch web, dù kiểu thiết kế khá giống nhau
                    if (MAXIMIZE_TRANSLATION_QUALITY_PDF) { // Nâng cao hơn nữa chất lượng prompt / SI cho PDF
                        $newPS = callGeminiApiPdfDynamic($base64Pdf, $prompt, $systemInstruction);

                        if ($newPS !== null) {
                            // Gán trở lại
                            $prompt =  removeLeadingMarkdownFence(removeExtraCharacters($newPS['new_prompt']));
                            $systemInstruction = removeLeadingMarkdownFence(removeExtraCharacters($newPS['new_system_instruction']));

                            // Lưu file
                            $prompt_filename = "pr.md"; // Để xem lại khi cần thiết, chứ không dùng, có thể bỏ mà không ảnh hưởng gì
                            $si_filename = "si.md";

                            // --- Lưu biến $prompt vào file prompt.txt ---
                            // file_put_contents(đường dẫn file, dữ liệu cần ghi)
                            // Hàm này sẽ tạo file mới nếu nó chưa tồn tại hoặc ghi đè lên file cũ nếu nó đã có.
                            $result_prompt = file_put_contents($prompt_filename, $prompt);

                            // --- Lưu biến $systemInstruction vào file si.txt ---
                            $result_si = file_put_contents($si_filename, $systemInstruction);                   
                        }
                    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------

                    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // Đây là cấu hình sẽ được sử dụng nếu có bất kỳ vấn đề nào với file config // Tempe và topP
                    $defaultConfig = [
                        'temperature' => 0.3, // Giá trị mặc định an toàn
                        'topP'        => 0.9, // Giá trị mặc định an toàn
                        // Thêm các cấu hình mặc định khác nếu cần
                    ];                    
                    
                    $configFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'myself'. DIRECTORY_SEPARATOR .'ai_temp_top_config.php';
                    $loadedConfig = require $configFilePath;
                    
                    // $loadedConfig sẽ ghi đè giá trị lên $defaultConfig
                    $currentConfig = array_merge($defaultConfig, $loadedConfig); // Gộp để phòng lỗi với file ai_temp_top_config.php
                    
                    // Lấy thông tin tham số
                    $temperature = $currentConfig['temperature'];
                    $topP = $currentConfig['topP'];
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------

                    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                    // 5. Chuẩn bị JSON payload (Giữ nguyên) // Cấu hình gửi API
                    if (defined('SEARCH_ENGINE_GEMINI') && SEARCH_ENGINE_GEMINI) { // Có sự hỗ trợ từ máy tìm kiếm
                        $requestBody = [
                            'contents' => [
                                [
                                    'role' => 'user',
                                    'parts' => [
                                        ['inlineData' => ['mimeType' => 'application/pdf', 'data' => $base64Pdf]],
                                        ['text' => $prompt]
                                    ]
                                ]
                            ],
                            'systemInstruction' => [
                                'parts' => [
                                    ['text' => $systemInstruction]
                                ]
                            ],                     
                            'generationConfig' => [
                                'temperature' => $temperature,
                                'maxOutputTokens' => 65500,
                                'responseMimeType' => 'text/plain'
                            ],
                            'tools' => [
                                [
                                    'googleSearch' => (object) []
                                ]
                            ]
                        ];
                    } 
                    else { // Không cần sự hỗ trợ từ máy tìm kiếm
                        $requestBody = [
                            'contents' => [
                                [
                                    'role' => 'user',
                                    'parts' => [
                                        ['inlineData' => ['mimeType' => 'application/pdf', 'data' => $base64Pdf]],
                                        ['text' => $prompt]
                                    ]
                                ]
                            ],
                            'systemInstruction' => [
                                'parts' => [
                                    ['text' => $systemInstruction]
                                ]
                            ],                     
                            'generationConfig' => [
                                'temperature' => $temperature,
                                'maxOutputTokens' => 65500,
                                'responseMimeType' => 'text/plain'
                            ]
                        ];                        
                    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    
                    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                     
                    // 6. Gửi yêu cầu API (Giữ nguyên)
                    $client = new Client(['timeout' => CURL_TIMEOUT_API]);
                    $response = $client->post($geminiApiUrl, [
                        'headers' => ['Content-Type' => 'application/json'],
                        'json' => $requestBody
                    ]);

                    // 7. Xử lý kết quả
                    $responseBody = $response->getBody()->getContents();
                    $result = json_decode($responseBody, true);          

                    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                        $geminiHtmlContent = $result['candidates'][0]['content']['parts'][0]['text']; // Đổi tên biến cho rõ ràng

                        // Cố gắng loại bỏ ```html nếu AI vẫn thêm vào, điều này có thể xảy ra vì chúng ta yêu cầu kết quả trả về là dạng html
                        // Đây là một dự phòng tốt
                        $geminiHtmlContent = preg_replace('/^```html\s*/i', '', trim($geminiHtmlContent));
                        $geminiHtmlContent = preg_replace('/\s*```$/', '', trim($geminiHtmlContent));

                        // Tạo tên file mới
                        $safeFilenameBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filenameWithoutExt); // Những ký tự không an toàn sẽ được thay thế bằng dấu gạch dưới
                        $randomSuffix = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT); // Tạo một số ngẫu nhiên có 6 chữ số, nếu dưới 1 triệu nó sẽ độn thêm các số 0 đằng trước cho đủ 6 số
                        $savedHtmlFilename = $safeFilenameBase . '_' . $randomSuffix . '.html'; // Kết hợp để tạo thành file hoàn chỉnh, chống trùng
                        $savedHtmlPath = $outputDir . DIRECTORY_SEPARATOR . $savedHtmlFilename; // Đường dẫn để lưu file

                        // Tạo thư mục nếu chưa tồn tại
                        if (!is_dir($outputDir)) {
                            if (!mkdir($outputDir, 0755, true)) {
                                 throw new Exception("Không thể tạo thư mục lưu trữ: " . htmlspecialchars($outputDirName));
                            }
                        }

                        // Lưu file HTML cuối cùng
                        if (file_put_contents($savedHtmlPath, $geminiHtmlContent) === false) {
                            throw new Exception("Không thể lưu file HTML: " . htmlspecialchars($savedHtmlFilename));
                        }
                       
                        // Tính toán thời gian dịch xong
                        $end_time = microtime(true);
                        $translation_duration = round($end_time - $start_time, 1); // Làm tròn đến 1 chữ số thập phân                            

                        // **** (2) CHUẨN BỊ LINK HIỂN THỊ (Giữ nguyên logic) ****
                        $successLink = $outputDirName . '/' . rawurlencode($savedHtmlFilename);
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
                        
                        
                        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                        
                    } elseif(isset($result['candidates'][0]['finishReason']) && $result['candidates'][0]['finishReason'] != 'STOP') {
                        // Xử lý lỗi API dừng
                         $errorText = "API dừng xử lý với lý do: " . $result['candidates'][0]['finishReason'] . ". Phản hồi thô: " . htmlspecialchars($responseBody);

                    } else {
                         // Lỗi không nhận được nội dung
                        $errorText = "Không nhận được nội dung HTML từ API hoặc định dạng phản hồi không mong đợi. Phản hồi thô: " . htmlspecialchars($responseBody);
                    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    
                    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------                    
                } catch (RequestException $e) {
                    $errorText = "Lỗi khi gọi API Gemini: " . $e->getMessage();
                    if ($e->hasResponse()) {
                        $errorText .= "\nChi tiết lỗi từ API: " . $e->getResponse()->getBody()->getContents();
                    }
                } catch (Exception $e) { // Bắt cả lỗi Exception chung (vd: tạo thư mục, lưu file)
                    $errorText = "Đã xảy ra lỗi hệ thống: " . $e->getMessage();
                }
            }        
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------  
            
            
            
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------            
        } elseif (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Xử lý các lỗi upload khác 
            switch ($_FILES['pdfFile']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorText = "Lỗi: Kích thước file vượt quá giới hạn cho phép.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorText = "Lỗi: File chỉ được tải lên một phần.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorText = "Lỗi: Thiếu thư mục tạm.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorText = "Lỗi: Không thể ghi file vào đĩa.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorText = "Lỗi: Một extension PHP đã chặn việc tải lên file.";
                    break;
                default:
                    $errorText = "Lỗi không xác định khi tải file lên.";
                    break;
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $errorText = "Lỗi: Bạn chưa chọn file PDF nào để tải lên.";
        }
    } 
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------

    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------    
    // Khi API Key của người dùng là rỗng
    else {
        $processing_complete = true;
        $errorText = '<p id="none_API_KEY">Bạn chưa có API KEY, chương trình không thể chạy được. Vào phần <a href="myself/setting.php" target="_blank">setting này</a> để nhập API KEY.'
                . ' Lưu ý: Chúng tôi không bán API KEY, bạn phải tự mua nó thông qua Google. Xem hướng dẫn trên mạng về cách lấy API KEY của Gemini.</p>';
    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------    
} // Đóng của button gửi đi dịch



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Cấu hình cho phần thư mục lưu file HTML dịch xong
define('TRANSLATIONS_DIR_PDF', __DIR__ . DIRECTORY_SEPARATOR . 'pdf_html_show');

// Định nghĩa URL công khai đến thư mục translations (điều này có thể khác tùy thuộc vào cấu hình server của bạn)
define('TRANSLATION_PUBLIC_URL_PDF_SHOW', 'pdf_html_show/');
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// REQUEST_URI là lựa chọn tốt nhất vì nó giữ nguyên query string
$current_request_uri = $_SERVER['REQUEST_URI'];
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
$type = 'pdf'; // Tùy theo kiểu để kiểm tra thư mục tương ứng
$recentTranslations = getRecentTranslations(RECENT_TRANSLATIONS_NUMBER, $type); // Hiển thị số lượng bài dịch gần đây
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Điều chỉnh chất lượng dịch, có thay đổi thành chất lượng dịch tối đa không
$current_state_trans_quality = false; // Giá trị mặc định nếu không đọc được file

// Đọc trạng thái hiện tại từ config.php một cách an toàn
// Kiểm tra xem hằng số đã được định nghĩa và có giá trị true không
if (defined('MAXIMIZE_TRANSLATION_QUALITY_PDF') && MAXIMIZE_TRANSLATION_QUALITY_PDF === true) {
    $current_state_trans_quality = true;
}

// Xác định class CSS cho button dựa trên trạng thái
$button_class_trans_quality = $current_state_trans_quality ? 'toggle-button-on-mtq' : 'toggle-button-off-mtq';
$button_title_trans_quality = $current_state_trans_quality ? 'Nhấn để Tắt chế độ dịch tăng cường (chuyển sang màu vàng) | Nên chọn trong đa số trường hợp nội dung không quá phức tạp' : 'Nhấn để Bật chế độ dịch tăng cường (chuyển sang màu đỏ) | *Có thể* giúp tăng chất lượng dịch với bài dài & phức tạp, nhưng thường khiến bạn tốn gấp đôi chi phí!';
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch PDF sang tiếng Việt | silaTranslator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&family=Roboto&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png"> <!-- === favicon === -->
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="css/reset.css?v=3"> <!-- === Reset cho CSS thống nhất giữa các trình duyệt === -->
    <link rel="stylesheet" href="css/translation_pdf_html.css?v=21"> <!-- === CSS thêm ?v=number vào để nó khỏi cache mỗi khi cập nhật === -->
    <style>
        .footerP {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 0.8em; 
            color: #9E9E9E; 
            text-align: center;
        }
        
        .footerP p {
            color: #9E9E9E; 
        }
        
        .link-for-customer {
            color:#607D8B;
            text-decoration: none;
        }
        
        .link-for-customer:hover {
            color:#2980b9;
            text-decoration: underline dotted;
        }        
    </style>    
</head>
<body>
    <!-- === Thanh Bên Trái Cố Định === -->
    <aside id="sticky-left-sidebar">
        <ul>
            <li>
                <a href="search.php?scholar=true" target="_blank" title="Tìm kiếm tài liệu nghiên cứu">
                    <svg width="24" height="24" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                      <circle 
                        cx="13" cy="13"
                        r="8"              
                        fill="#ccc"    
                        stroke="currentColor" 
                        stroke-width="2"   
                      />

                      <line 
                        x1="18.3" y1="18.3" 
                        x2="25" y2="25"   
                        stroke="currentColor" 
                        stroke-width="3"   
                        stroke-linecap="round" 
                      />
                    </svg>
                </a>
            </li>
            <li>
                <a href="index.php" title="Dịch trang web">
                    <svg width="24" height="24" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                        <polygon 
                            points="25,2  30,18  48,18  34,30  40,48  25,38  10,48  16,30  2,18  20,18"
                            fill="#777" 
                        />
                    </svg>
                </a>
            </li>
            <!--
            <li>
                
                <a href="https://scholar.google.com/" target="_blank" title="Google Scholar">
                    <svg width="24" height="24" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" 
                         fill="none" 
                         stroke="#333" 
                         stroke-width="1.5" 
                         stroke-linecap="round" 
                         stroke-linejoin="round">

                      <path d="M4,5.5 C4,5.5 7,3 16,3 C25,3 28,5.5 28,5.5 V26.5 C28,26.5 25,29 16,29 C7,29 4,26.5 4,26.5 Z"/>
                      <line x1="16" y1="3" x2="16" y2="29"/>

                      <line x1="7" y1="8" x2="14" y2="8" stroke-width="1.2"/>
                      <line x1="7" y1="12" x2="14" y2="12" stroke-width="1.2"/>
                      <line x1="7" y1="16" x2="12" y2="16" stroke-width="1.2"/>
                      <line x1="7" y1="20" x2="14" y2="20" stroke-width="1.2"/>

                      <line x1="18" y1="8" x2="25" y2="8" stroke-width="1.2"/>
                      <line x1="18" y1="12" x2="25" y2="12" stroke-width="1.2"/>
                      <line x1="18" y1="16" x2="23" y2="16" stroke-width="1.2"/>
                      <line x1="18" y1="20" x2="25" y2="20" stroke-width="1.2"/>
                    </svg>
                </a>
            </li>
            -->
            <li>
                <form action="toggle_quality.php?typemax=pdf" method="post" style="display: inline;">
                    <!-- Input ẩn chứa URL hiện tại để chuyển hướng về -->
                    <input type="hidden" name="redirect_uri" value="<?= htmlspecialchars($current_request_uri) ?>">
                    <button type="submit"
                            class="toggle-button-mtq <?= $button_class_trans_quality ?>"
                            title="<?= htmlspecialchars($button_title_trans_quality) ?>">
                    </button>
                    <!-- Không cần input hidden vì toggle_quality.php sẽ tự đọc file config -->
                </form>
            </li>             
        </ul>     
    </aside>
    <!-- === Kết Thúc Thanh Bên Trái === -->

    <div class="container-box form-container">
        <!-- <h1>Dịch file PDF tiếng Anh sang tiếng Việt</h1> -->
        <!-- <p class="description">Tải lên file PDF tiếng Anh, hệ thống sẽ sử dụng Gemini API để dịch nội dung sang tiếng Việt và lưu kết quả thành file để bạn có thể đọc ngay trên trình duyệt.</p> -->
        <div class="nameApp"><img src="images/favicon-32x32.png"><span id="titleST">silaTranslator</span></div>
        <form action="" method="post" enctype="multipart/form-data" id="pdfForm">

            <label for="pdfFile" style="text-align: center; font-size: 2em;">Dịch file <span style="color:#2980b9;">PDF</span> từ Anh sang Việt</label>
            <div class="file-input-wrapper">
                <input type="file" id="pdfFile" name="pdfFile" accept="application/pdf" required>
                <label for="pdfFile" class="file-input-label">Kéo thả file vào đây hoặc <span>nhấn để chọn</span></label>
                <div id="fileNameDisplay">[chưa có file nào được chọn]</div>
            </div>

            <button type="submit" id="translate-button">Dịch PDF</button>
        </form>
        <div class="loader" id="loader"></div>
        
        <?php if ($errorText): ?>
            <div class="error-container">
                <strong>Đã xảy ra lỗi:</strong>
                <pre><?php if (!$processing_complete) {echo htmlspecialchars($errorText);} else {echo $errorText;} ?></pre>
            </div>
        <?php endif; ?> 
        
        <?php if ($successLink): ?>
            <hr>
            <div class="success-container" style="<?php echo ($successLink) ? 'display: block;' : 'display: none;'; ?>">
                 <p>✅ File PDF đã được dịch và lưu thành công!</p>
                 <p>Thời gian xử lý: <strong><?php echo $translation_duration; ?> giây</strong></p>
                 <p>Nhấp vào liên kết dưới đây để xem kết quả (mở trong tab mới):</p>
                 <p id="open-link-result">
                     <a href="<?php echo htmlspecialchars($successLink); ?>" target="_blank" class="result-link">
                         Mở file: <?php echo htmlspecialchars($savedHtmlFilename); ?>
                     </a>
                 </p>
                 <small>File được lưu tại thư mục <code><?php echo htmlspecialchars($outputDirName); ?></code> trong ứng dụng.</small>
            </div>
        <?php endif; ?>        
        
        <?php if (!empty($recentTranslations)): ?>
            <div class="recent-translations" id="recent-translations-list" style="display: none;">
                    <h3>Các file đã dịch gần nhất</h3>
                    <ul>
                        <?php foreach ($recentTranslations as $translation): ?>
                            <li>
                                <?php
                                    // Kiểm tra xem hằng số URL có tồn tại không (an toàn hơn)
                                    $baseUrl = defined('TRANSLATION_PUBLIC_URL_PDF_SHOW') ? TRANSLATION_PUBLIC_URL_PDF_SHOW : 'pdf_html_show/'; // Cung cấp giá trị mặc định nếu cần
                                    // Lấy các giá trị từ mảng $translation
                                    $fileName = $translation['filename'];
                                    $vietnameseTitle = $translation['vietnameseTitle'];
                                    $englishTitleFormatted = $translation['englishTitleFormatted'];
                                    $timestamp = $translation['timestamp'];
                                    $fullUrl = $baseUrl . $fileName;
                                    $formattedDate = date('d/m/Y', $timestamp);
                                    $fullTimestamp = date('d/m/Y H:i:s', $timestamp);
                                ?>
                                <a href="<?php echo htmlspecialchars($fullUrl); ?>" target="_blank" title="Trang gốc: <?php echo htmlspecialchars($englishTitleFormatted); ?> | Đã dịch vào: <?php echo $fullTimestamp; ?>">
                                    <?php echo htmlspecialchars($vietnameseTitle); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                     <?php if (defined('TRANSLATION_PUBLIC_URL_PDF_SHOW')): // Chỉ hiển thị nếu hằng số được định nghĩa ?>
                        <p style="font-size: 0.8em; color: #666;">(Các file được lưu tại thư mục: <?php echo TRANSLATIONS_DIR_PDF; ?>)</p>
                     <?php endif; ?>
                        
                            <!-- ===== Thêm thống kê thư mục ===== -->
                            <div style="margin-top: 15px;">
                                <button id="stats-button-translations" class = "utility-button" type="button" style="font-size:0.9em; color: #777;">Thống kê thư mục pdf_html_show</button>
                                <div id="stats-result-translations" style="margin-top: 10px; font-weight: bold; font-size:0.9em; color: #666;"></div>
                            </div>
                            <!-- ===== E Thêm thống kê thư mục ===== -->                        
            </div> <!-- Thẻ đóng của .recent-translations --> 
            
        <?php else: ?>
            <div class="recent-translations">
                <p>Chưa có file PDF nào được dịch gần đây.</p>
            </div>
        <?php endif; ?> 
            
        <div class="footerP">
            <p>Dịch tự động bằng AI có thể có sai sót. Hãy thuê người dịch với văn bản quan trọng. <br>Tham khảo thêm <a href="https://silatranslator.gitbook.io/silatranslator-docs/" target="_blank" class="link-for-customer">Hướng dẫn sử dụng</a> và <a href="https://silatranslator.gitbook.io/silatranslator-docs/thong-tin-khac/dieu-khoan-su-dung-cua-silatranslator" target="_blank" class="link-for-customer">Điều khoản sử dụng</a> của silaTranslator.<br> Version 1.15.10.25 - Sản phẩm thử nghiệm.</p>
        </div>

        <?php if (!empty($recentTranslations)) { ?>
            <!-- Thêm nút bấm Toggle ngay TRƯỚC div recent-translations -->
            <button type="button" id="toggle-recent-btn" class="utility-button" style="margin-top: 5px; margin-bottom: -10px; display: block; margin-left: auto; margin-right: auto;">
                Hiện các file PDF đã dịch
            </button> 
        <?php } // Nếu có bài dịch gần đây thì mới hiện nút này?>              
            
            <!-- ===================== Nút Tiện Ích Phụ ===================== -->
            <div class="utility-buttons">
                <a href="myself/setting.php" class="utility-button">Chọn Model</a>
                <a href="myself/runAI_settings.php" class="utility-button">Chỉnh tham số API</a>
                <a href="index.php" class="utility-button">Dịch trang web</a>
                <a href="myself/small_settings.php" class="utility-button">Cài đặt Nhỏ</a>
                <a href="myself/model_config.php" class="utility-button">Thêm bớt Model</a>
            </div>
            <!-- ===================== E Nút Tiện Ích Phụ ===================== -->              
    </div> <!-- Thẻ đóng của .container-box .form-container -->

<script>
        const pdfForm = document.getElementById('pdfForm');
        const fileInput = document.getElementById('pdfFile');
        const loader = document.getElementById('loader');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const fileInputLabel = document.querySelector('.file-input-label');
        const errorContainer = document.querySelector('.error-container'); // Thêm dòng này
        const successContainer = document.querySelector('.success-container'); // Thêm dòng này

        // Hiển thị tên file khi được chọn
        fileInput.addEventListener('change', function() {
            if (fileInput.files.length > 0) {
                fileNameDisplay.textContent = 'Đã chọn: ' + fileInput.files[0].name;
            } else {
                fileNameDisplay.textContent = '[chưa có file nào được chọn]';
            }
        });

        // Xử lý khi submit form
        pdfForm.addEventListener('submit', function(event) {
            // Ẩn các thông báo cũ ngay lập tức
            if (errorContainer) {
                errorContainer.style.display = 'none';
            }
            if (successContainer) {
                successContainer.style.display = 'none';
            }

            // Kiểm tra lại xem có file không trước khi hiển thị loader và cho phép submit
            if (fileInput.files.length > 0) {
                // Kiểm tra kích thước file phía client
                const maxFileSizeJS = <?php echo $maxFileSize; ?>;

                if (fileInput.files[0].size > maxFileSizeJS) {
                    alert('Lỗi: Kích thước file vượt quá ' + (maxFileSizeJS / 1024 / 1024) + ' MB.');
                    event.preventDefault(); // Ngăn submit
                    return;
                }

                // Kiểm tra loại file phía client
                if (fileInput.files[0].type !== 'application/pdf') {
                    alert('Lỗi: Vui lòng chỉ chọn file định dạng PDF.');
                    event.preventDefault(); // Ngăn submit
                    return;
                }

                 loader.style.display = 'block'; // Hiển thị loader
                 // Không cần preventDefault() ở đây nữa nếu các kiểm tra trên đã pass
                 // Form sẽ tự động submit sau khi hàm này kết thúc
            } else {
                 alert('Vui lòng chọn một file PDF.');
                 event.preventDefault(); // Ngăn form submit nếu chưa chọn file
            }
        });

        // (Tùy chọn) Có thể thêm logic để ẩn loader nếu có lỗi ngay từ phía client
        // Ví dụ: đặt loader.style.display = 'none'; trong các khối alert và return ở trên.
    
        // --- BẮT ĐẦU CODE MỚI CHO TOGGLE RECENT TRANSLATIONS ---
        const toggleBtn = document.getElementById('toggle-recent-btn');
        const recentListDiv = document.getElementById('recent-translations-list');

        // Chỉ thêm listener nếu cả nút và div đều tồn tại
        if (toggleBtn && recentListDiv) {
            toggleBtn.addEventListener('click', function() { // Lắng nghe sự kiện click vào nút toggle-recent-btn
                // Kiểm tra trạng thái hiển thị hiện tại của div
                const isHidden = recentListDiv.style.display === 'none' || recentListDiv.offsetParent === null; // Kiểm tra cả display:none và trường hợp ẩn do CSS khác

                if (isHidden) { // Nếu đang ẩn thì hiển thị ra
                    // Nếu đang ẩn -> Hiện ra
                    recentListDiv.style.display = 'block'; // Hoặc 'flex', 'grid' tùy vào cách bạn muốn hiển thị
                    toggleBtn.textContent = 'Ẩn các file đã dịch';
                } else { // Còn nếu đang hiển thị ra thì ẩn đi
                    // Nếu đang hiện -> Ẩn đi
                    recentListDiv.style.display = 'none'; // Điều khiển ẩn hiện bằng thuộc tính display của div có id là recent-translations-list
                    toggleBtn.textContent = 'Hiện các file PDF đã dịch';
                }
            });
        }
    
    
        const statsButton = document.getElementById('stats-button-translations');
        const statsResultDiv = document.getElementById('stats-result-translations');

        if (statsButton && statsResultDiv) {
            statsButton.addEventListener('click', function() {
                statsResultDiv.innerHTML = 'Đang thống kê...'; // Thông báo đang xử lý

                // Gọi file PHP xử lý AJAX
                fetch('functions/get_stats_translation_folder.php?folder=pdf_html_show') // Đảm bảo đường dẫn này đúng
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.statusText);
                        }
                        return response.json(); // Chuyển đổi response thành JSON
                    })
                    .then(data => {
                        // Kiểm tra dữ liệu trả về hợp lệ
                        if (data && typeof data.count !== 'undefined' && typeof data.size_mb !== 'undefined') {
                            const count = data.count;
                            // Định dạng số MB thành 2 chữ số thập phân
                            const sizeMb = parseFloat(data.size_mb).toFixed(2);
                            // Hiển thị kết quả
                            statsResultDiv.innerHTML = `Số lượng bài đã dịch: <strong>${count}</strong> | Tổng dung lượng: <strong>${sizeMb}</strong> MB`;
                        } else {
                            // Xử lý trường hợp dữ liệu không mong đợi
                            statsResultDiv.innerHTML = 'Lỗi: Dữ liệu trả về không hợp lệ.';
                            console.error('Invalid data received:', data);
                        }
                    })
                    .catch(error => {
                        // Xử lý lỗi nếu fetch thất bại
                        console.error('Lỗi khi lấy thống kê:', error);
                        statsResultDiv.innerHTML = 'Đã xảy ra lỗi khi thống kê. Vui lòng thử lại.';
                    });
            });
        } else {
             console.error('Không tìm thấy nút #stats-button-translations hoặc div #stats-result-translations');
        }         
        // --- KẾT THÚC CODE MỚI ---      
</script>

</body>
</html>