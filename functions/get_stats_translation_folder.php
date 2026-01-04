<?php
// get_stats.php

// --- Phần cấu hình ---

// 1. Đường dẫn đến file chứa hàm getHtmlDirectoryStats
require_once 'getHtmlDirectoryStats.php'; // <<<=== THAY ĐỔI ĐƯỜNG DẪN NÀY

// 2. Đường dẫn đến thư mục 'translations' cần thống kê
//    **CẢNH BÁO:** Tránh dùng đường dẫn tuyệt đối cứng như C:\...
//    Cách tốt hơn là dùng đường dẫn tương đối từ vị trí file get_stats.php này.
//    Ví dụ: Nếu 'translations' là thư mục con của thư mục chứa get_stats.php:
//    $directory = __DIR__ . '/translations';
//    Ví dụ: Nếu 'translations' ngang cấp với thư mục chứa get_stats.php:
//    $directory = dirname(__DIR__) . '/translations';

//    Cách xác định đường dẫn tương đối từ gốc web (thường dùng):
$directory = dirname(__DIR__)  . '/translations'; // Giả sử silaTranslator nằm ở gốc web

// Lấy thông tin ở thư mục nào?
if (isset($_GET['folder']) && $_GET['folder'] = 'pdf_html_show') {
    $directory = dirname(__DIR__)  . '/pdf_html_show';
}
// --- Kết thúc phần cấu hình ---


// Kiểm tra xem hàm đã tồn tại chưa (để chắc chắn require_once thành công)
if (!function_exists('getHtmlDirectoryStats')) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Hàm thống kê không tồn tại.']);
    exit;
}

// Gọi hàm thống kê
$stats = getHtmlDirectoryStats($directory);

// Đặt header để trình duyệt biết đây là JSON
header('Content-Type: application/json');

// Xuất kết quả dưới dạng JSON
echo json_encode($stats);

exit; // Dừng thực thi để tránh output thừa