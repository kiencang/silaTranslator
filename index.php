<?php
// File: index.php
set_time_limit(2400); // 40 phút chạy hàm tối đa // Giới hạn này thường đủ tốt
date_default_timezone_set('Asia/Ho_Chi_Minh'); // Thiết lập giờ theo giờ Việt Nam
ini_set('memory_limit', '1024M'); // Đảm bảo bộ nhớ


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Gỡ lỗi, kiểm tra, quan trọng trước khi phân phối sản phẩm đến người dùng cuối
// Báo cáo TẤT CẢ các loại lỗi
//error_reporting(E_ALL); 

// Hiển thị lỗi ra màn hình (thay vì chỉ ghi vào log)
//ini_set('display_errors', 1); 

// Hiển thị cả các lỗi xảy ra trong quá trình khởi động PHP
//ini_set('display_startup_errors', 1); 

//require_once __DIR__ . DIRECTORY_SEPARATOR . 'error_config.php'; // ghi lỗi vào file để theo dõi
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// cmd đến thư mục lập trình cd C:\xampp\htdocs\silaTranslator
// READABILITYPHP mặc định để lấy nội dung thu gọn 
// READABILITYJS là giải pháp dự phòng chất lượng cao với người dùng hệ điều hành phù hợp
// Phụ thuộc quan trọng là Pandoc để chuyển đổi html sang markdown và ngược lại, mặc dù cũng có sẵn các thư viện PHP tương thích cao cho việc này
// Panther giải pháp chất lượng cao để lấy nội dung trực tuyến từ URL, nhằm đỡ bị hiểu nhầm là bot và do đó bị các website khó tính chặn
// Gemini cho nhiệm vụ dịch
// composer show // Tất cả các gói trong vender, cmd vào thư mục lập trình để check
// composer license // Kiểm tra giấy phép các thư viện bên trong đang dùng
// Kiểm tra logic lần cuối 10/05/2025
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//# Luồng công việc
//#1. Từ URL -> Lấy nội dung HTML (sử dụng cURL hoặc Panther)
//#2. Từ nội dung HTML -> Lấy phần nội dung chính thông qua (READABILITYPHP / mặc định) hoặc nếu Hệ điều hành phù hợp (Windows 64-bit/Phần lớn hợp) thì dùng READABILITYJS (Tùy chọn, không áp đặt)
//      #2.1 Có tùy chọn lọc thêm bằng thư viện htmlpurifier hay không, thư viện giúp chuẩn hóa mã nguồn html và loại bỏ độc tính, đang cân nhắc đây là tùy chọn hay mặc định, và nên đặt cả đầu và cuối hay không?
//#3. Từ nội dung chính HTML -> Markdown thông qua thư viện league/html-to-markdown (mặc định, thư viện của PHP) hoặc nếu Hệ điều hành phù hợp (Windows 64-bit/Phần lớn hợp) thì dùng PANDOC (Tùy chọn, không áp đặt)
//      #3.1 Nếu người dùng tùy chọn chuyển sang plain text để dịch thì bỏ qua phần liên quan đến markdown  
//#4. Gửi cho API Gemini để dịch
//$5. Lấy nội dung dịch, chuyển từ Markdown -> HTML thông qua thư viện league/commonmark (mặc định, thư viện của PHP) hoặc nếu Hệ điều hành phù hợp (Windows 64-bit/Phần lớn hợp) thì dùng PANDOC (Tùy chọn, không áp đặt)
//      #5.1 Nếu bước #3 đã là plain text (văn bản thuần) thì bước 5 này cũng bỏ qua  
//#6. Bọc nội dung HTML hoàn chỉnh hiển thị cho người dùng (trang được tùy biến để tối ưu cho việc đọc & các tùy chỉnh khác để hợp nhu cầu / html, css, js)
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Đảm bảo file config.php tồn tại và đúng đường dẫn
// Viết đường dẫn kiểu này để dự phòng chương trình chạy đa nền tảng, mặc dù hiện tại chỉ hướng đến người dùng Windows
$config_Path = __DIR__ . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'config.php'; // Đường dẫn quan trọng của file config.php

if (file_exists($config_Path)) {
    require_once $config_Path;
} else {
    // Hoặc dừng thực thi và báo lỗi nghiêm trọng
    die("Lỗi nghiêm trọng: Không tìm thấy file cấu hình config.php trong myself!");
}

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Kết nối các hàm cần dùng
require_once __DIR__ . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'link_portal_config.php'; // Thông tin các cổng web
require_once __DIR__ . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'rss_config.php'; // Thông tin RSS
require_once __DIR__ . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'what_lang_translate.php'; // Ngôn ngữ cần dịch
require_once __DIR__ . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'config_ads.php'; // Dùng để cấu hình xem có xóa nội dung quảng cáo còn rơi rớt không

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'fetchHtmlContentFinal.php'; // Kiểu dựa vào các hàm sẵn có của PHP // cURL
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'fetchHtmlWithPantherImproved.php'; // Mô phỏng trình duyệt thực sự, tránh bị chặn bởi các website có hệ thống tường lửa quá nhạy cảm

require_once __DIR__ . DIRECTORY_SEPARATOR . 'markdown_functions' . DIRECTORY_SEPARATOR . 'convertHtmlToMarkdownPHP.php'; // Bản thư viện PHP
require_once __DIR__ . DIRECTORY_SEPARATOR . 'markdown_functions' . DIRECTORY_SEPARATOR . 'convertMarkdownToHtmlPHP.php'; // Bản thư viện PHP
require_once __DIR__ . DIRECTORY_SEPARATOR . 'markdown_functions' . DIRECTORY_SEPARATOR . 'convertHtmlToMarkdownPandoc.php'; // Lọc html thành markdown, đây có thể là giải pháp cực mạnh để lọc & nhờ vậy đảm bảo chất lượng dịch tốt hơn
require_once __DIR__ . DIRECTORY_SEPARATOR . 'markdown_functions' . DIRECTORY_SEPARATOR . 'convertMarkdownToHtmlPandoc.php'; // Chuyển ngược từ markdown về html, để hiển thị cho người dùng cuối

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'smallFunctions.php'; // Các hàm nhỏ
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'createCustomHtmlPurifierConfig.php'; // Cấu hình của HtmlPurifier
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'updateRemoveTagsHtml.php'; // Các hàm liên quan đến update hoặc loại bỏ tag HTML
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'convertToAbsoluteUrl.php'; // Các hàm liên quan đến chuyển link từ tương đối sang tuyệt đối, để đảm bảo hiển thị ảnh và các liên kết chính xác trên local

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'callGeminiApi.php'; // Hàm gọi API Gemini để dịch
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'callGeminiApiDynamic.php'; // Hàm cải thiện chất lượng prompt & system instructions
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'wrapRawContentInHtml.php'; // Hàm chuyển nội dung HTML thô thành trang hoàn chỉnh, với nhiều tùy biến cho người dùng cuối
// require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'relatePostsJson.php'; // Hàm lưu thông tin các bài đã dịch, đường dẫn, url gốc, có thể xem xét mức độ hữu ích để triển khai chính thức

// require_once 'functions/splitLongContent.php'; // Chia thông minh khi nội dung quá dài, chỉ cho html
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'new_splitLongContentHtml.php'; // Chia thông minh khi nội dung quá dài
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'splitLongContentMarkdown.php'; // Chia thông minh khi nội dung quá dài
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'getReadableJSContent.php'; // Lấy nội dung chính bằng ReadableJS của Mozilla
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Điều chỉnh chất lượng dịch, có thay đổi thành chất lượng dịch tối đa không
$current_state_trans_quality = false; // Giá trị mặc định nếu không đọc được file

// Đọc trạng thái hiện tại từ config.php một cách an toàn
// Kiểm tra xem hằng số đã được định nghĩa và có giá trị true không
if (defined('MAXIMIZE_TRANSLATION_QUALITY') && MAXIMIZE_TRANSLATION_QUALITY === true) {
    $current_state_trans_quality = true;
}

// Lấy URL hiện tại (bao gồm path và query string, ví dụ: /myapp/index.php?html=true)
// REQUEST_URI là lựa chọn tốt nhất vì nó giữ nguyên query string
$current_request_uri = $_SERVER['REQUEST_URI'];

// Xác định class CSS cho button dựa trên trạng thái
$button_class_trans_quality = $current_state_trans_quality ? 'toggle-button-on-mtq' : 'toggle-button-off-mtq';
$button_title_trans_quality = $current_state_trans_quality ? 'Nhấn để Tắt chế độ dịch tăng cường (chuyển sang màu xám) | Nên chọn trong đa số trường hợp nội dung không quá phức tạp' : 'Nhấn để Bật chế độ dịch tăng cường (chuyển sang màu xanh dương) | *Có thể* giúp tăng chất lượng dịch với bài dài & phức tạp, nhưng thường khiến bạn tốn gấp đôi chi phí!';
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Có đưa thêm ví dụ vào để AI có mẫu không
$current_example_trans = false; // Giá trị mặc định nếu không đọc được file

// Đọc trạng thái hiện tại từ config.php một cách an toàn
// Kiểm tra xem hằng số đã được định nghĩa và có giá trị true không
if (defined('EXAMPLE_FOR_TRANSLATION') && EXAMPLE_FOR_TRANSLATION === true) {
    $current_example_trans = true;
}

// Xác định class CSS cho button dựa trên trạng thái
$button_class_example_trans = $current_example_trans ? 'toggle-button-on-mtq' : 'toggle-button-off-mtq';
$button_title_example_trans = $current_example_trans ? 'Nhấn để Tắt đưa ví dụ vào chỉ dẫn cho AI' : 'Nhấn để Bật đưa thêm ví dụ vào chỉ dẫn cho AI | Có thể có lợi ích nhỏ, chỉ áp dụng với dịch Anh - Việt';
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Kiểm tra các extension cần thiết ---
if (!function_exists('curl_init')) {
    die("Lỗi: Phần mở rộng PHP cURL chưa được cài đặt hoặc kích hoạt.");
}
if (!class_exists('DOMDocument')) {
    die("Lỗi: Phần mở rộng PHP XML (DOM) chưa được cài đặt hoặc kích hoạt.");
}
// Lấy mô hình AI đang dùng // Lấy từ file myself/config.php
$current_model_AI = modelAIis(); 

// Kiểu dịch mã nguồn HTML
$is_html_mode = false;
if (isset($_GET['html']) && $_GET['html'] == 'true') {
    $is_html_mode = true;
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
require_once 'include/server_request_url_html.php'; // Các hàm thực thi quan trọng nhất
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
// Dùng để hiển thị các bài viết đã dịch
// Định nghĩa đường dẫn đến thư mục translations
define('TRANSLATIONS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'translations');

// Định nghĩa URL công khai đến thư mục translations (điều này có thể khác tùy thuộc vào cấu hình server của bạn)
define('TRANSLATION_PUBLIC_URL_SHOW', 'translations/');

$url_input_lang_title = 'tiếng Anh';
$url_input_placeholder = 'https://example.com/english-page.html';
// Lấy danh sách các bản dịch gần nhất
// Có thể thay đổi con số này // Lấy từ file config.php
$recentTranslations = getRecentTranslations(RECENT_TRANSLATIONS_NUMBER);
// E ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>silaTranslator: Dịch website bằng AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&family=Roboto&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png"> 
    <link rel="stylesheet" href="css/reset.css?v=3">
    <link rel="stylesheet" href="css/style.css?v=39">
    <style>
        .footerP {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 0.8em; 
            color: #999; 
            text-align: center;
        }
        
        .footerP p {
            color: #999; 
        }
        
        #client-prompt-h, .other-lang-h {
            padding: 3px 7px;
            background-color: yellow;
        }
        
        .input-group .centered-label {
            font-size: 14px;
            color: #999;
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
    <!-- === Thanh Bên TRÁI Cố Định === -->
    <?php if (defined('LEFT_SIDEBAR') && LEFT_SIDEBAR) { // Có bật tùy chọn hiển thị thanh sidebar bên trái không ?>
        <aside id="sticky-left-sidebar" style="left: 0;">
            <ul>
                <li>
                    <a href="<?php echo PORTAL_ONE ?>" title="<?php echo PORTAL_ONE ?>" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#999" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo PORTAL_TWO ?>" title="<?php echo PORTAL_TWO ?>" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#777" xmlns="http://www.w3.org/2000/svg">
                            <polygon points="12,2 13.5,11.2 22,12 13.5,12.8 12,22 10.5,12.8 2,12 10.5,11.2" />

                            <circle cx="16" cy="8" r="1.2"/>

                            <circle cx="16" cy="16" r="1.2"/>

                            <circle cx="8" cy="16" r="1.2"/>

                            <circle cx="8" cy="8" r="1.2"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo PORTAL_THREE ?>" title="<?php echo PORTAL_THREE ?>" target="_blank">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#777" xmlns="http://www.w3.org/2000/svg">
                            <path d="
                                  M12 3   
                                  L7 9      
                                  L10.5 9   
                                  L10.5 21  
                                  L13.5 21  
                                  L13.5 9   
                                  L17 9     
                                  L12 3     
                                  Z
                                  "/>
                            <polygon points="3,11 10,10.5 10,13 3,12.5" />
                            <polygon points="14,10.5 21,11 21,12.5 14,13" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="myself/update_portals.php" title="Chỉnh sửa liên kết">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="#777" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M17.2071 2.29289C16.8166 1.90237 16.1834 1.90237 15.7929 2.29289L2.29289 15.7929C1.90237 16.1834 1.90237 16.8166 2.29289 17.2071L6.79289 21.7071C7.18342 22.0976 7.81658 22.0976 8.20711 21.7071L21.7071 8.20711C22.0976 7.81658 22.0976 7.18342 21.7071 6.79289L17.2071 2.29289ZM15.0858 4.41421L19.5858 8.91421L8.91421 19.5858L4.41421 15.0858L15.0858 4.41421ZM7.5 16.5L16.5 7.5L18 9L9 18L7.5 16.5Z" />
                        </svg>                    
                    </a>
                </li>              
            </ul>     
        </aside>
        <!-- === Kết Thúc Thanh Bên TRÁI === -->
    <?php } ?>
    
    <!-- === Thanh Bên PHẢI Cố Định === -->
    <aside id="sticky-left-sidebar" style="right: 0;">
        <ul>
            <li>
                <a href="search.php" target="_blank" title="Tìm kiếm (từ khóa tiếng Việt chuyển thành từ khóa tiếng Anh)">
                    <svg width="24" height="24" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <circle 
                            cx="13" cy="13"
                            r="8"              
                            fill="#fff"    
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
                <a href="translation_PDF_HTML.php" title="Dịch tài liệu PDF">
                    <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#999" d="M14.25 0H6C4.34315 0 3 1.34315 3 3V21C3 22.6569 4.34315 24 6 24H18C19.6569 24 21 22.6569 21 21V6.75L14.25 0ZM14.25 1.5V5.25C14.25 5.66421 14.5858 6 15 6H18.75L14.25 1.5Z"/>
                    </svg>
                </a>
            </li>
            <?php if (defined('MULTI_LINGUAL') && MULTI_LINGUAL) { // Có bật dịch thêm các ngôn ngữ khác không ?>
                <li>
                    <a href="myself/lang_settings_form.php" title="Lựa chọn ngôn ngữ dịch">️
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g stroke="#777" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>

                                <line x1="2" y1="12" x2="22" y2="12"/>

                                <ellipse cx="12" cy="12" rx="4" ry="10"/>
                            </g>
                        </svg>
                    </a>
                </li>
            <?php } ?>
            <li>
                <form action="example_for_ai.php" method="post" style="display: inline;">
                    <!-- Input ẩn chứa URL hiện tại để chuyển hướng về -->
                    <input type="hidden" name="redirect_uri_ex" value="<?php echo htmlspecialchars($current_request_uri) ?>">
                    <button type="submit"
                            class="toggle-button-mtq <?php echo $button_class_example_trans ?>"
                            title="<?= htmlspecialchars($button_title_example_trans) ?>" style="color:#fff;">@
                    </button>
                </form>
            </li>                
            <li>
                <form action="toggle_quality.php?typemax=web" method="post" style="display: inline;">
                    <!-- Input ẩn chứa URL hiện tại để chuyển hướng về -->
                    <input type="hidden" name="redirect_uri" value="<?php echo htmlspecialchars($current_request_uri) ?>">
                    <button type="submit"
                            class="toggle-button-mtq <?php echo $button_class_trans_quality ?>"
                            title="<?= htmlspecialchars($button_title_trans_quality) ?>">
                    </button>
                </form>
            </li>    
        </ul>     
    </aside>
    <!-- === Kết Thúc Thanh Bên PHẢI === -->
     
    <div class="translator-container">
        <div class="nameApp"><img src="images/favicon-32x32.png"><span id="titleST">silaTranslator</span></div>
        
        <?php if ($custom_prompt_system) { ?>
            <h1>Dịch trang web với <span id="client-prompt-h">Prompt của bạn</span></h1>
        <?php } elseif (LANG_TRANSLATE == 'setEV') { $url_input_lang_title = 'tiếng Anh'; $url_input_placeholder = 'https://example.com/english-page.html'; ?>     
            <h1>Dịch trang web từ Anh sang Việt</h1>
        <?php } elseif (LANG_TRANSLATE == 'setCV') { $url_input_lang_title = 'tiếng Trung'; $url_input_placeholder = 'https://example.com/chinese-page.html'; ?>
            <h1>Dịch trang web từ <span class="other-lang-h">Trung</span> sang Việt</h1>
        <?php } elseif (LANG_TRANSLATE == 'setJV') { $url_input_lang_title = 'tiếng Nhật'; $url_input_placeholder = 'https://example.com/japanese-page.html'; ?> 
            <h1>Dịch trang web từ <span class="other-lang-h">Nhật</span> sang Việt</h1> 
        <?php } elseif (LANG_TRANSLATE == 'setKV') { $url_input_lang_title = 'tiếng Hàn'; $url_input_placeholder = 'https://example.com/korean-page.html'; ?> 
            <h1>Dịch trang web từ <span class="other-lang-h">Hàn</span> sang Việt</h1>
        <?php } ?>
            
        <p id="model-info-display" style="display: none; margin-top: 15px; font-size: 0.9em; color: #555;"> <!-- Thêm ID và style ẩn -->
            Sử dụng model: <strong><a href="myself/setting.php" target="_blank" title="Nhấn để thay đổi model AI sử dụng"><?php echo isset($current_model_AI) ? htmlspecialchars($current_model_AI) : 'Mặc định'; ?></a></strong>
        </p>
        
        <?php if ($is_html_mode) { ?>
            <p style="text-align: left;">Phương pháp này dùng khi <strong>KHÔNG</strong> thể lấy nội dung tự động bằng URL (do website chặn hoặc vì bất kỳ lý do nào khác).</p>
            <p style="text-align: left;"><strong>Cách dùng:</strong> Truy cập trang web cần dịch, nhấn Ctrl + U (hoặc chuột phải, chọn xem nguồn trang), copy (Ctrl + A, Ctrl + C) toàn bộ mã nguồn rồi dán (Ctrl + V) vào ô bên dưới.</p>        
        <?php } ?> 
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" id="translate-form" novalidate> <!-- novalidate để JS xử lý loading -->
            <?php if ($is_html_mode) {?>
            <div class="input-group">
                <label for="html-input">Nhập mã nguồn HTML cần dịch:</label>
                <!-- Sử dụng textarea thay vì input -->
                <textarea id="html-input" name="html_content" placeholder="<!DOCTYPE html>..." required><?php echo $submitted_html_preview; // Hiển thị lại một phần nếu có lỗi, đã escape ?></textarea>
            </div>
            <?php } ?>
            <div class="input-group">
                <label for="url-input" class="centered-label">Nhập URL trang web cần dịch (<?php echo $url_input_lang_title; ?>)<span class="info-icon" data-tooltip="Chỉ nhập URL từ website mà bạn tin tưởng. Và nên nhập một bài viết cụ thể thay vì cả trang chủ.">i</span></label>
                <!-- Sử dụng htmlspecialchars cho value để tránh XSS nếu URL được giữ lại sau POST -->
                <input type="url" id="url-input" name="url" placeholder="<?php echo $url_input_placeholder; ?>" required value="<?php echo htmlspecialchars($submitted_url); ?>">
            </div>
            
            <!-- ===================== Lựa chọn chế độ dịch ===================== -->
            <div class="input-group" id="translation-mode-options" style="margin-top: 10px; margin-bottom: 20px; text-align: center; <?php if ($custom_prompt_system) {?>display: none;<?php } ?>">
                <label style="margin-right: 30px; cursor: pointer; font-weight: normal; font-size: 0.9em;">
                    <input type="radio" name="translation_mode" value="0" <?php echo (!isset($_POST['translation_mode']) || $_POST['translation_mode'] == '0') ? 'checked' : ''; ?> style="vertical-align: middle; margin-right: 4px;">
                    giữ nguyên định dạng
                    <!-- Biểu tượng Info cho tùy chọn 1 -->
                    <span class="info-icon" data-tooltip="Dịch cả nội dung và giữ lại các định dạng (như liên kết, in đậm, tiêu đề, ảnh, v.v). Giữ bố cục nội dung chính của trang web giống bản gốc nhất có thể. Đây là lựa chọn *phù hợp hơn* cho *phần lớn trường hợp*.">?</span>
                </label>
                <label style="cursor: pointer; font-weight: normal; font-weight: normal; font-size: 0.9em;">
                    <input type="radio" name="translation_mode" value="1" <?php echo (isset($_POST['translation_mode']) && $_POST['translation_mode'] == '1') ? 'checked' : ''; ?> style="vertical-align: middle; margin-right: 4px;">
                    chỉ dịch văn bản
                    <!-- Biểu tượng Info cho tùy chọn 2 -->
                    <span class="info-icon" data-tooltip="Chỉ lấy *nội dung văn bản thuần túy* để dịch. Có thể giúp AI dịch tốt hơn (ví dụ với *văn bản có mức độ khó cao*), nhưng sẽ mất cách trình bày như trên website gốc (không có ảnh, không có bảng biểu). Kết quả hiển thị dưới dạng văn bản liền mạch có xuống dòng. Lợi ích phụ: tốc độ dịch cao hơn & tiết kiệm hơn.">?</span>
                </label>
            </div>
            <!-- ===================== E Lựa chọn chế độ dịch ===================== -->            

            <button type="submit" id="translate-button">
                <span class="button-text"><?php if(!$is_html_mode) { ?> Dịch Trang Web <?php } else { ?> Dịch Mã Nguồn HTML <?php } ?></span>
                <span class="spinner" style="display: none;"></span>
            </button>
        </form>

        <!-- Chỉ hiển thị status area nếu có thông báo hoặc đang xử lý POST -->
        <div id="status-area" style="<?php echo ($processing_complete || !empty($status_message)) ? 'display: block;' : 'display: none;'; ?>">
             <!-- Hiển thị thông báo từ PHP -->
            <?php if (!empty($status_message)): ?>
                <div class="<?php echo htmlspecialchars($status_type); ?>">
                    <?php echo $status_message; // Message đã được escape trong PHP ?>
                    <?php if ($status_type === 'success' && !empty($translated_file_link)): ?>
                        <br><br>
                        <!-- Escape link trong href -->
                        <a id="translated-link-button" href="<?php echo htmlspecialchars($translated_file_link); ?>" target="_blank">Nhấn vào đây để đọc bài dịch</a>
                        <p style="font-size: 0.8em; margin-top: 10px; color: #666; text-align: center;">(File được lưu thư mục: '<?php echo htmlspecialchars(basename(TRANSLATION_PUBLIC_URL)); ?>' trong ứng dụng)</p>
                    <?php endif; ?>
                        
                    <?php if ($status_type === 'warning' && !empty($translated_file_link)): // Cho trường hợp lưu file gốc ?>
                        <br><br>
                        <a href="<?php echo htmlspecialchars($translated_file_link); ?>" target="_blank">Nhấn vào đây để xem/tải file gốc đã lưu</a>
                        <p style="font-size: 0.8em; margin-top: 10px; color: #666; text-align: center;">(File được lưu thư mục: '<?php echo htmlspecialchars(basename(TRANSLATION_PUBLIC_URL)); ?>' trong ứng dụng)</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
             <!-- Placeholder cho JS loading message -->
             <div id="js-loading-message" class="loading" style="display: none;">Đang xử lý yêu cầu của bạn, vui lòng đợi khoảng 60 - 120 giây. Có thể cần 180 - 300 giây với nội dung dài hoặc/và kết hợp với model cao cấp.</div>
        </div>
            <?php if (RSS_LINK != '') { // Phải kiểm tra sự tồn tại thì mới hiển thị, tránh rối giao diện?>
                <!-- === NÚT KÍCH HOẠT HIỂN THỊ TIN TỨC === -->
                <button type="button" id="toggle-news-button" class="toggle-news-button">
                    Xem 10 tin mới nhất từ <?php if (RSS_LINK_NAME != '') {echo RSS_LINK_NAME;} else {echo 'RSS';}; ?> ▼ 
                </button> 
                <!-- === HẾT NÚT KÍCH HOẠT HIỂN THỊ TIN TỨC === -->
               
                <div id="google-news-container">
                    <h2>10 Tin tức mới nhất từ <?php if (RSS_LINK_NAME != '') {echo RSS_LINK_NAME;} else {echo 'RSS';}; ?> <span style="font-size: 10px;">(<a href="myself/update_RSS_link.php" target="_blank">chỉnh sửa</a>)</span></h2>
                    <?php include_once 'include/rss_display_v3.php'; ?>
                </div> <!-- /container google-news --> 
            <?php } ?>
                
            <?php if (!empty($recentTranslations)): ?>
            <div class="recent-translations" id="recent-translations-list" style="display: none;">
                    <h3>Các trang đã dịch gần nhất</h3>
                    <ul>
                        <?php foreach ($recentTranslations as $translation): ?>
                            <li>
                                <?php
                                    // Kiểm tra xem hằng số URL có tồn tại không (an toàn hơn)
                                    $baseUrl = defined('TRANSLATION_PUBLIC_URL_SHOW') ? TRANSLATION_PUBLIC_URL_SHOW : 'translations/'; // Cung cấp giá trị mặc định nếu cần
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
                     <?php if (defined('TRANSLATION_PUBLIC_URL_SHOW')): // Chỉ hiển thị nếu hằng số được định nghĩa ?>
                        <p style="font-size: 0.8em; color: #666;">(Các file được lưu tại thư mục: <?php echo TRANSLATIONS_DIR; ?>)</p>
                     <?php endif; ?>
                    
                        <!-- ===== Thêm thống kê thư mục ===== -->
                        <div style="margin-top: 15px;">
                            <button id="stats-button-translations" class = "utility-button" type="button" style="font-size:0.9em; color: #777;">Thống kê thư mục translations</button>
                            <div id="stats-result-translations" style="margin-top: 10px; font-weight: bold; font-size:0.9em; color: #666;"></div>
                        </div>
                        <!-- ===== E Thêm thống kê thư mục ===== -->
            </div> <!-- Thẻ đóng của .recent-translations --> 
            <?php else: ?>
                <div class="recent-translations">
                    <p>Chưa có trang nào được dịch gần đây.</p>
                </div>
            <?php endif; ?>            
        
        <div class="footerP">
            <p>Dịch tự động bằng AI có thể có sai sót. Hãy thuê người dịch với văn bản quan trọng. <br>Tham khảo thêm <a href="https://silatranslator.gitbook.io/silatranslator-docs/" target="_blank" class="link-for-customer">Hướng dẫn sử dụng</a> và <a href="https://silatranslator.gitbook.io/silatranslator-docs/thong-tin-khac/dieu-khoan-su-dung-cua-silatranslator" target="_blank" class="link-for-customer">Điều khoản sử dụng</a> của silaTranslator.<br> Version 1.15.10.25 - Sản phẩm thử nghiệm.</p>
        </div>
        
        <?php if (!empty($recentTranslations)) { ?>
            <!-- Thêm nút bấm Toggle ngay TRƯỚC div recent-translations -->
            <button type="button" id="toggle-recent-btn" class="utility-button" style="margin-top: 5px; margin-bottom: -10px; display: block; margin-left: auto; margin-right: auto;">
                Hiện các bài đã dịch
            </button> 
        <?php } // Nếu có bài dịch gần đây thì mới hiện nút này?>           
        
            <!-- ===================== Nút Tiện Ích Phụ ===================== -->
            <div class="utility-buttons">
                <a href="myself/setting.php" class="utility-button">Chọn Model</a>
                <a href="myself/runAI_settings.php" class="utility-button">Chỉnh tham số API</a>
                <a href="<?php if (!$is_html_mode) { ?>index.php?html=true<?php } else { ?>index.php<?php } ?>" class="utility-button"><?php if (!$is_html_mode) { ?>Dịch HTML<?php } else { ?>Dịch trang web<?php } ?></a>
                <a href="myself/small_settings.php" class="utility-button">Cài đặt Nhỏ</a>
                <a href="myself/model_config.php" class="utility-button">Thêm bớt Model</a>
            </div>
            <!-- ===================== E Nút Tiện Ích Phụ ===================== -->          
        </div> <!-- Thẻ đóng của .translator-container --> 

    <script>
        const form = document.getElementById('translate-form');
        const button = document.getElementById('translate-button');
        const buttonText = button.querySelector('.button-text');
        const spinner = button.querySelector('.spinner');
        const statusArea = document.getElementById('status-area');
        const jsLoadingMessage = document.getElementById('js-loading-message');
        const phpStatusDiv = statusArea.querySelector('div:not(#js-loading-message)'); // Lấy div trạng thái từ PHP (nếu có)

        form.addEventListener('submit', function(event) {
            const urlInput = document.getElementById('url-input');
            // Kiểm tra URL cơ bản phía client trước khi submit
            if (!urlInput.value || urlInput.validity.typeMismatch || !urlInput.validity.valid) {
                 // Ngăn chặn submit nếu URL không hợp lệ (trình duyệt hỗ trợ type="url")
                 // Có thể thêm thông báo lỗi JS ở đây nếu muốn
                 // event.preventDefault(); // Không cần prevent nếu chỉ muốn hiển thị lỗi HTML5 mặc định
                 // return;
                 // Cho phép submit để PHP xử lý lỗi URL nếu muốn (như hiện tại)
            }
            
            // --- THÊM CODE ĐỂ HIỂN THỊ THÔNG TIN MODEL ---
            const modelInfoParagraph = document.getElementById('model-info-display');
            if (modelInfoParagraph) {
                modelInfoParagraph.style.display = 'block'; // Hoặc 'inherit' nếu cần
            }
            // --- KẾT THÚC PHẦN THÊM ---            

            // Hiển thị trạng thái loading bằng JS
            button.disabled = true;
            buttonText.style.display = 'none';
            spinner.style.display = 'inline-block';
            statusArea.style.display = 'block'; // Đảm bảo khu vực trạng thái hiển thị

            // Ẩn thông báo cũ từ PHP (nếu có) và hiển thị loading message
            if (phpStatusDiv) {
                phpStatusDiv.style.display = 'none';
            }
            jsLoadingMessage.style.display = 'block';

            // Form sẽ submit bình thường sau đó
        });

        // Logic này chỉ cần thiết nếu bạn muốn reset button ngay cả khi trang chưa tải lại hoàn toàn
        // Thường thì sau khi submit và trang tải lại, PHP sẽ render trạng thái button đúng
        // (không disable nếu $processing_complete là true)
        <?php if ($processing_complete): // Chỉ chạy nếu POST request đã được xử lý ?>
        document.addEventListener('DOMContentLoaded', function() {
             // Khôi phục trạng thái button sau khi trang tải lại
             button.disabled = false;
             buttonText.style.display = 'inline-block';
             spinner.style.display = 'none';
             jsLoadingMessage.style.display = 'none'; // Ẩn loading message của JS

             // Hiển thị lại thông báo từ PHP nếu có
             if (phpStatusDiv) {
                 phpStatusDiv.style.display = 'block';
             } else {
                 // Nếu không có thông báo từ PHP và quá trình đã xong, ẩn cả status area
                 // statusArea.style.display = 'none';
             }
        });
        <?php endif; ?>
    </script>
    
    <script src="js/index.js?v=3"></script>
</body>
</html>