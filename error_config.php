<?php
// File: config/error_config.php

// Tắt hiển thị lỗi ra trình duyệt
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Bật ghi lỗi vào file
ini_set('log_errors', 1);

// Chỉ định file log (đảm bảo đường dẫn tồn tại và có quyền ghi)
// Sử dụng __DIR__ để có đường dẫn tuyệt đối an toàn hơn
// Giả sử thư mục errorLog ngang hàng với thư mục chứa file config này
ini_set('error_log', __DIR__ . '/errorLog/php-error.log'); // Điều chỉnh đường dẫn nếu cần

// Đặt mức độ báo cáo lỗi
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);