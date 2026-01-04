<?php
// Bật lỗi (chỉ khi dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Kiểu tìm kiếm scholar
$is_scholar_mode = false;
if (isset($_GET['scholar']) && $_GET['scholar'] == 'true') {
    $is_scholar_mode = true;
}


// --- PHẦN NẠP FILE VÀ KIỂM TRA LỖI NGHIÊM TRỌNG ---
$config_Path = __DIR__ . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'config.php';
$geminiFunctionPath = __DIR__ . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'callGeminiApiForSearch.php';
$criticalErrors = []; // Mảng lưu trữ lỗi nghiêm trọng


// Kiểm tra và nạp file config
if (!file_exists($config_Path)) {
    $criticalErrors[] = "Lỗi hệ thống nghiêm trọng: Không tìm thấy file cấu hình cần thiết (config.php).";
    // error_log("CRITICAL: config.php not found at " . $config_Path);
} else {
    require_once $config_Path; // Chỉ require nếu file tồn tại
}

// Kiểm tra và nạp file hàm Gemini (chỉ nếu chưa có lỗi nghiêm trọng)
if (empty($criticalErrors) && !file_exists($geminiFunctionPath)) {
    $criticalErrors[] = "Lỗi hệ thống nghiêm trọng: Không tìm thấy chức năng dịch thuật cần thiết.";
    // error_log("CRITICAL: callGeminiApiforSearch.php not found at " . $geminiFunctionPath);
} elseif (empty($criticalErrors)) {
     // Chỉ require nếu file tồn tại và chưa có lỗi
    require_once $geminiFunctionPath;
}


// --- ĐỊNH NGHĨA CÁC HÀM TIỆN ÍCH ---
// Hàm để mở cửa sổ trình duyệt mới
/**
 * @param string $url là chuỗi URL cần mở
 * @return bool để biết có mở thành công cửa sổ mới hay không.
 */
function openUrlInDefaultBrowser(string $url): bool {
    $os = strtoupper(PHP_OS);
    $command = null;
    $safeUrl = escapeshellarg($url);
    // Chỉ kiểm tra với WIN là đủ
    if (strpos($os, 'WIN') === 0) { $command = "start \"\" " . $safeUrl; }
    //elseif (strpos($os, 'DARWIN') === 0) { $command = "open " . $safeUrl; }
    //elseif (strpos($os, 'LINUX') === 0) { $command = "xdg-open " . $safeUrl; }
    else { error_log("Không có OS hỗ trợ bật tự động: " . $os); return false; }

    if ($command) {
        try {
            if (strpos($os, 'WIN') === 0) { pclose(popen("start /B ". $command, "r")); }
            else { exec($command . ' > /dev/null 2>&1 &'); }
            return true;
        } catch (\Throwable $e) { error_log("Lỗi khi thực hiện lệnh trình duyệt: " . $e->getMessage()); return false; }
    }
    return false;
}


// Hàm để xác định đường dẫn URL cần mở
/**
 * @param string $query là truy vấn
 * @param bool $scholar để biết kiểu đường dẫn là Google tìm kiếm hay Google Scholar
 * @return string chuỗi cụ thể cần mở.
 */
function buildGoogleSearchUrl(string $query, bool $scholar = false): string {
    $baseUrl = 'https://www.google.com/search?q=';
    //$encodedQuery = urlencode($query);
    $encodedQuery = str_replace(' ', '+', $query);// Giải pháp đơn giản và thực tế hơn
    
    if (!$scholar) { // Google tìm kiếm, location Hoa Kỳ, giao diện vẫn tiếng Việt, chỉ tìm website tiếng Anh
        return $baseUrl . $encodedQuery . '&hl=vi&gl=US&lr=lang_en'; // Hiển thị với các ràng buộc giao diện tiếng Anh (hl=en, có thể thay thế thành vi để thân thiện hơn), địa lý ở Hoa Kỳ (gl / Geographic Location), và kết quả chỉ tập trung vào tiếng Anh (lr / Language Restrict)
    }
    
    if ($scholar) { // Google Scholar
        $baseUrl = 'https://scholar.google.com/scholar?hl=vi&q=';
        //$encodedQuery = urlencode($query);
        $encodedQuery = str_replace(' ', '+', $query);// Giải pháp đơn giản và thực tế hơn
        return $baseUrl . $encodedQuery;
    }
}

// --- KIỂM TRA PHƯƠNG THỨC REQUEST ---
// Thực hiện kiểm tra này TRƯỚC KHI bắt đầu in bất kỳ HTML nào của trang kết quả

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Nếu không phải POST, chuyển hướng người dùng về trang form
    header('Location: search.php?error=invalid_access'); // Thêm một tham số để có thể hiển thị lỗi (tùy chọn)
    exit; // Dừng thực thi script ngay lập tức
}

// --- BẮT ĐẦU TRANG HTML PHẢN HỒI (CHỈ KHI LÀ POST REQUEST HỢP LỆ) ---
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tìm kiếm | silaTranslator</title>
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png"> 
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&family=Roboto&display=swap" rel="stylesheet"> 
    <style>
        body {font-family: "Be Vietnam Pro", 'Roboto', sans-serif;}
        .message-container { max-width: 690px; margin: 20px auto; padding: 15px 50px; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .message { padding: 12px 15px; margin-bottom: 15px; border-radius: 5px; border: 1px solid transparent; }
        .success { background-color: #dff0d8; border-color: #d6e9c6; color: #3c763d; }
        .error { background-color: #f2dede; border-color: #ebccd1; color: #a94442; }
        .info { background-color: #d9edf7; border-color: #bce8f1; color: #31708f; }
        .query-display { font-style: italic; color: #555; font-weight: bold; }
        .link-button { display: inline-block; margin-top: 10px; padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 0.9em; }
        .link-button:hover { background-color: #0056b3; }
        .back-link { display: block; margin-top: 20px; text-align: center; font-size: 1.5em;}
    </style>
</head>
<body>
    <div class="message-container">
        <h1 style="text-align: center;">Kết quả xử lý tìm kiếm</h1>

        <?php
        // --- LOGIC XỬ LÝ CHÍNH (Chỉ chạy khi là POST và đã qua kiểm tra ở trên) ---

        // Hiển thị lỗi nghiêm trọng nếu có và dừng lại
        if (!empty($criticalErrors)) {
            foreach ($criticalErrors as $msg) {
                echo "<div class='message error'>{$msg}</div>";
            }
        } else {
            // Chỉ xử lý form nếu không có lỗi nghiêm trọng

            // Kiểm tra dữ liệu 'vietnamese_query' (đã chắc chắn là POST)
            if (isset($_POST['vietnamese_query'])) {

                $vietnameseQueryRaw = $_POST['vietnamese_query'];
                $vietnameseQuery = trim($vietnameseQueryRaw);

                // Kiểm tra truy vấn có rỗng không
                if (!empty($vietnameseQuery)) {

                    echo "<div class='message info'>Đang xử lý truy vấn tiếng Việt: <span class='query-display'>\"" . htmlspecialchars($vietnameseQuery) . "\"</span></div>";

                    // Gọi API để dịch (trong try...catch)
                    try {
                        // Giả định hàm không cần $config
                        $englishQueryResult = callGeminiApiforSearch($vietnameseQuery);
                        $englishQuery = trim($englishQueryResult);

                        // Kiểm tra kết quả dịch
                        if (!empty($englishQuery)) {
                            echo "<div class='message success'>Dịch thành công sang tiếng Anh: <span class='query-display'>\"" . htmlspecialchars($englishQuery) . "\"</span></div>";

                            // Xây dựng URL
                            $googleUrl = buildGoogleSearchUrl($englishQuery, $is_scholar_mode);
                            echo "<div class='message info'>Đang chuẩn bị mở URL tìm kiếm...</div>";

                            // Mở trình duyệt
                            if (openUrlInDefaultBrowser($googleUrl)) {
                                echo "<div class='message success'>Hoàn tất! Lệnh mở trình duyệt đã được gửi đi. Vui lòng kiểm tra cửa sổ trình duyệt mới.</div>";
                            } else {
                                echo "<div class='message error'>Không thể tự động mở trình duyệt.";
                                
                                if (!$is_scholar_mode) {
                                    echo " Bạn có thể mở thủ công: <a href='" . htmlspecialchars($googleUrl) . "' target='_blank' class='link-button'>Tìm trên Google</a></div>";
                                }
                                
                                if ($is_scholar_mode) {
                                    echo " Bạn có thể mở thủ công: <a href='" . htmlspecialchars($googleUrl) . "' target='_blank' class='link-button'>Tìm trên Google Scholar</a></div>";
                                }                                
                            }

                        } else {
                            echo "<div class='message error'>Lỗi: Dịch vụ dịch thuật không trả về kết quả hợp lệ cho truy vấn này.</div>";
                        }

                    } catch (\Throwable $e) {
                        error_log("Lỗi khi gọi API Gemini: " . $e->getMessage());
                        echo "<div class='message error'>Lỗi: Đã xảy ra sự cố khi kết nối đến dịch vụ dịch thuật. Vui lòng thử lại sau.</div>";
                    }

                } else {
                    echo "<div class='message error'>Lỗi: Truy vấn tìm kiếm không được để trống.</div>";
                }

            } else {
                // Trường hợp này ít xảy ra nếu form có 'required', nhưng vẫn nên kiểm tra
                echo "<div class='message error'>Lỗi: Dữ liệu tìm kiếm không được gửi đúng cách.</div>";
            }
        } // Kết thúc else của kiểm tra lỗi nghiêm trọng

        // Luôn hiển thị nút quay lại sau khi xử lý
        if (!$is_scholar_mode) {
            echo '<p class="back-link"><a href="search.php" class="link-button">« Quay lại trang tìm kiếm</a></p>';
        }

        if ($is_scholar_mode) {
            echo '<p class="back-link"><a href="search.php?scholar=true" class="link-button">« Quay lại trang tìm kiếm</a></p>';
        }    
        ?>
    </div> <!-- End of div message-container -->
</body>
</html>