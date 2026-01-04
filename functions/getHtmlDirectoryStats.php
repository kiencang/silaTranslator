<?php

/**
 * Lấy thông tin thống kê về các file HTML trong một thư mục.
 *
 * Đếm số lượng file .html và tính tổng dung lượng của chúng.
 * Chỉ kiểm tra các file trực tiếp trong thư mục, không duyệt thư mục con.
 *
 * @param string $directory Đường dẫn đến thư mục cần kiểm tra.
 * @return array Mảng chứa thông tin thống kê:
 * 'count' => (int) Số lượng file .html tìm thấy.
 * 'size_mb' => (float) Tổng dung lượng các file .html (tính bằng Megabytes).
 *  Trả về ['count' => 0, 'size_mb' => 0.0] nếu thư mục không hợp lệ hoặc không đọc được.
 * // Ví dụ mẫu: echo ' | ' . $stats_trans_dir['count'].' file | Dung lượng: '.number_format($stats_trans_dir['size_mb'], 2).'MB' ?>
 */
function getHtmlDirectoryStats(string $directory): array {
    $htmlFileCount = 0;
    $totalSizeInBytes = 0;

    // Kiểm tra xem thư mục có tồn tại và có thể đọc được không
    if (!is_dir($directory) || !is_readable($directory)) {
        // Ghi log lỗi nếu cần
        // error_log("Thư mục không tồn tại hoặc không thể đọc: " . $directory);
        return ['count' => 0, 'size_mb' => 0.0];
    }

    // Mở thư mục để đọc
    if ($handle = opendir($directory)) {
        // Đọc từng entry trong thư mục
        while (false !== ($entry = readdir($handle))) {
            // Bỏ qua '.' và '..'
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $filePath = $directory . '/' . $entry;

            // Kiểm tra xem entry có phải là file và có đuôi .html không
            if (is_file($filePath) && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'html') {
                // Tăng bộ đếm file
                $htmlFileCount++;

                // Lấy kích thước file (sử dụng @ để chặn warning nếu file bị xóa giữa chừng)
                // và cộng vào tổng dung lượng
                $fileSize = @filesize($filePath);
                if ($fileSize !== false) { // Chỉ cộng nếu lấy được kích thước
                    $totalSizeInBytes += $fileSize;
                }
            }
        }
        // Đóng thư mục sau khi đọc xong
        closedir($handle);
    } else {
        // Ghi log lỗi nếu không thể mở thư mục
        // error_log("Không thể mở thư mục để đọc: " . $directory);
        return ['count' => 0, 'size_mb' => 0.0];
    }

    // Chuyển đổi tổng dung lượng từ Bytes sang Megabytes (MB)
    // 1 MB = 1024 * 1024 Bytes = 1,048,576 Bytes
    $totalSizeInMB = $totalSizeInBytes / (1024 * 1024);

    // Trả về kết quả thống kê
    return [
        'count' => $htmlFileCount,
        'size_mb' => $totalSizeInMB // Trả về dạng float để có thể định dạng sau
    ];
}
