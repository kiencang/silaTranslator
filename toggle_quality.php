<?php
// Chỉ xử lý nếu là yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); // Chuyển hướng mặc định nếu truy cập trực tiếp
    exit;
}


// --- Xác định URL để chuyển hướng về ---
$default_redirect_uri = 'index.php'; // URI mặc định nếu không có gì được gửi
$redirect_uri = $default_redirect_uri;


// Phần này hơi thừa
/*
if (isset($_POST['redirect_uri']) && !empty($_POST['redirect_uri'])) {
    $potential_uri = $_POST['redirect_uri'];
    // Kiểm tra cơ bản để đảm bảo URI trông hợp lệ và là URI tương đối
    // Tránh chuyển hướng đến domain khác hoặc sử dụng các scheme nguy hiểm (javascript:, data:, etc.)
    // Chỉ cho phép bắt đầu bằng '/' hoặc tên file index.php hoặc chỉ là query string '?'
    if (preg_match('/^(\/|index\.php|\?)/', $potential_uri)) {
        // Lọc thêm để loại bỏ các ký tự không mong muốn (an toàn hơn)
        // FILTER_SANITIZE_URL loại bỏ các ký tự không hợp lệ trong URL
        $cleaned_uri = filter_var($potential_uri, FILTER_SANITIZE_URL);
        // Chỉ sử dụng nếu sau khi lọc nó vẫn bắt đầu hợp lệ (tránh trường hợp filter loại bỏ quá nhiều)
        if ($cleaned_uri && preg_match('/^(\/|index\.php|\?)/', $cleaned_uri)) {
             $redirect_uri = $cleaned_uri;
        } else {
             error_log("Cảnh báo: redirect_uri không hợp lệ sau khi lọc: " . $potential_uri);
             // Giữ lại URI mặc định
        }
    } else {
        error_log("Cảnh báo: redirect_uri không hợp lệ nhận được: " . $potential_uri);
        // Giữ lại URI mặc định
    }
}
*/


// Như thế này là đủ, vì đầu vào đã rất chuẩn
if (isset($_POST['redirect_uri']) && !empty($_POST['redirect_uri'])) {
    $redirect_uri = $_POST['redirect_uri'];
}


// --- Hàm tiện ích để thêm tham số lỗi vào URI ---
// Hàm này đảm bảo tham số lỗi được nối đúng cách vào URI đã có hoặc chưa có query string
function add_query_param_to_uri($uri, $key, $value) {
    $uri_parts = parse_url($uri);
    $query_params = [];
    if (isset($uri_parts['query'])) {
        parse_str($uri_parts['query'], $query_params);
    }
    $query_params[$key] = $value; // Thêm hoặc ghi đè tham số
    $new_query_string = http_build_query($query_params);

    $path = $uri_parts['path'] ?? '';
    // Nếu path rỗng và có query (ví dụ URI là '?a=b'), dùng index.php làm path
    if (empty($path) && !empty($new_query_string) && strpos($uri, '?') === 0) {
         $path = 'index.php';
    } elseif (empty($path)) {
        // Nếu path rỗng và không có query (ví dụ URI là '/'), giữ nguyên hoặc dùng index.php
         $path = '/'; // Giả định trang gốc là hợp lệ
         // Hoặc nếu muốn luôn trỏ về file cụ thể: $path = 'index.php';
    }

    return $path . '?' . $new_query_string;
}


// --- Xử lý file config (logic tương tự như trước) ---
$config_path = __DIR__ . '/myself/config.php';

// Kiểm tra file tồn tại và quyền (sử dụng URI chuyển hướng đã xác định khi có lỗi)
if (!file_exists($config_path)) {
    error_log("Lỗi nghiêm trọng: File cấu hình không tồn tại tại " . $config_path);
    header('Location: ' . add_query_param_to_uri($redirect_uri, 'toggle_error', 'config_not_found'));
    exit;
}


if (!is_readable($config_path) || !is_writable($config_path)) {
    error_log("Lỗi nghiêm trọng: Không có quyền đọc hoặc ghi file cấu hình tại " . $config_path);
    header('Location: ' . add_query_param_to_uri($redirect_uri, 'toggle_error', 'config_permission'));
    exit;
}


// Đọc nội dung file
$content = file_get_contents($config_path);
if ($content === false) {
    error_log("Lỗi nghiêm trọng: Không thể đọc nội dung file cấu hình tại " . $config_path);
    header('Location: ' . add_query_param_to_uri($redirect_uri, 'toggle_error', 'config_read_failed'));
    exit;
}


// Nếu là kiểu web
if (isset($_GET['typemax']) && $_GET['typemax'] == 'web') {
    // Tìm và thay thế giá trị define
    $pattern = '/define\(\s*([\'"])MAXIMIZE_TRANSLATION_QUALITY\1\s*,\s*(true|false)\s*\);/i';
    $found = false;
    $new_content = preg_replace_callback(
        $pattern,
        function ($matches) use (&$found) {
            $found = true;
            $current_value_str = strtolower($matches[2]);
            $new_value_str = ($current_value_str === 'true') ? 'false' : 'true';
            $quote = $matches[1];
            return "define({$quote}MAXIMIZE_TRANSLATION_QUALITY{$quote}, {$new_value_str});";
        },
        $content,
        1 // Chỉ thay thế 1 lần
    );


    // Kiểm tra nếu không tìm thấy định nghĩa
    if (!$found) {
        error_log("Lỗi: Không tìm thấy dòng 'define(\'MAXIMIZE_TRANSLATION_QUALITY\', ...);' trong " . $config_path);
        header('Location: ' . add_query_param_to_uri($redirect_uri, 'toggle_error', 'definition_not_found'));
        exit;
    }
}


// Nếu là kiểu PDF
if (isset($_GET['typemax']) && $_GET['typemax'] == 'pdf') {
    // Tìm và thay thế giá trị define
    $pattern = '/define\(\s*([\'"])MAXIMIZE_TRANSLATION_QUALITY_PDF\1\s*,\s*(true|false)\s*\);/i';
    $found = false;
    $new_content = preg_replace_callback(
        $pattern,
        function ($matches) use (&$found) {
            $found = true;
            $current_value_str = strtolower($matches[2]);
            $new_value_str = ($current_value_str === 'true') ? 'false' : 'true';
            $quote = $matches[1];
            return "define({$quote}MAXIMIZE_TRANSLATION_QUALITY_PDF{$quote}, {$new_value_str});";
        },
        $content,
        1 // Chỉ thay thế 1 lần
    );


    // Kiểm tra nếu không tìm thấy định nghĩa
    if (!$found) {
        error_log("Lỗi: Không tìm thấy dòng 'define(\'MAXIMIZE_TRANSLATION_QUALITY_PDF\', ...);' trong " . $config_path);
        header('Location: ' . add_query_param_to_uri($redirect_uri, 'toggle_error', 'definition_not_found'));
        exit;
    }
}


// Ghi lại nội dung mới vào file
if (file_put_contents($config_path, $new_content) === false) {
    error_log("Lỗi nghiêm trọng: Không thể ghi nội dung mới vào file cấu hình tại " . $config_path);
    header('Location: ' . add_query_param_to_uri($redirect_uri, 'toggle_error', 'config_write_failed'));
    exit;
}


// Xóa cache opcode (rất quan trọng)
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($config_path, true);
} elseif (function_exists('apc_compile_file')) {
    @apc_compile_file($config_path); // Thử với APC cũ hơn
}


// --- Chuyển hướng người dùng trở lại URI gốc (thành công) ---
header('Location: ' . $redirect_uri);
exit; // Dừng script sau khi chuyển hướng