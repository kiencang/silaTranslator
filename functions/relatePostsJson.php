<?php

/**
 * Ghi thông tin bản dịch vào file JSON, giới hạn số lượng bản ghi.
 *
 * Hàm này đảm bảo tính toàn vẹn dữ liệu bằng cách:
 * - Sử dụng file locking để tránh xung đột ghi đồng thời.
 * - Xử lý lỗi đọc/ghi file và giải mã/mã hóa JSON.
 * - Đảm bảo dữ liệu đầu vào (đặc biệt là tiêu đề) hợp lệ UTF-8 trước khi mã hóa.
 * - Giữ số lượng bản ghi không vượt quá giới hạn $maxRecords.
 *
 * @param string $originalUrl URL của bài viết gốc.
 * @param string $translatedUrl URL của bài viết đã dịch.
 * @param string $translatedTitle Tiêu đề của bài viết đã dịch (Nên là UTF-8).
 * @param int $timestamp Unix timestamp của thời điểm dịch.
 * @param string $filePath Đường dẫn đến file JSON lưu trữ lịch sử.
 * @param int $maxRecords Số lượng bản ghi tối đa được lưu trữ (mặc định 500).
 * @return bool Trả về true nếu ghi thành công, false nếu có lỗi.
 */
function addTranslationRecord(
    string $originalUrl,
    string $translatedUrl,
    string $translatedTitle,
    int $timestamp,
    string $filePath,
    int $maxRecords = 500
): bool {
    // --- Validation đầu vào cơ bản ---
    if (empty(trim($originalUrl)) || empty(trim($translatedUrl)) || empty(trim($translatedTitle))) {
        error_log("Lỗi ghi lịch sử dịch: Thiếu thông tin URL hoặc tiêu đề.");
        return false;
    }
    if ($maxRecords <= 0) {
        error_log("Lỗi ghi lịch sử dịch: maxRecords phải là số dương.");
        return false;
    }
    // Đảm bảo thư mục chứa file có thể ghi
    $dir = dirname($filePath);
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
         error_log("Lỗi ghi lịch sử dịch: Không thể tạo thư mục: " . $dir);
         return false;
    }
     if (!is_writable($dir)) {
        error_log("Lỗi ghi lịch sử dịch: Thư mục không có quyền ghi: " . $dir);
        return false;
    }
    // Kiểm tra tiêu đề có phải là UTF-8 hợp lệ không (quan trọng cho json_encode)
    if (!mb_check_encoding($translatedTitle, 'UTF-8')) {
        // Cố gắng chuyển đổi hoặc báo lỗi tùy theo yêu cầu
        // $translatedTitle = mb_convert_encoding($translatedTitle, 'UTF-8', 'auto'); // Tùy chọn: Cố gắng sửa
         error_log("Lỗi ghi lịch sử dịch: Tiêu đề không phải là chuỗi UTF-8 hợp lệ.");
         return false; // An toàn nhất là từ chối nếu không chắc chắn
    }


    // --- Xử lý File và Locking ---
    $fp = fopen($filePath, 'c+'); // Mở để đọc/ghi, tạo nếu chưa có, con trỏ ở đầu

    if (!$fp) {
        error_log("Lỗi ghi lịch sử dịch: Không thể mở file: " . $filePath);
        return false;
    }

    // **Khóa file độc quyền (quan trọng!)**
    if (flock($fp, LOCK_EX)) {
        try {
            // Đọc nội dung hiện có
            $filesize = filesize($filePath);
            $content = $filesize > 0 ? fread($fp, $filesize) : '';
            $history = [];

            // Giải mã JSON
            if (!empty($content)) {
                $decoded = json_decode($content, true); // true để lấy mảng liên hợp
                // Kiểm tra lỗi giải mã JSON
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Lỗi ghi lịch sử dịch: File JSON bị hỏng hoặc không hợp lệ: " . $filePath . " - Lỗi: " . json_last_error_msg());
                    // Quyết định xử lý: có thể tạo lại file rỗng hoặc báo lỗi
                    // Ở đây ta chọn báo lỗi và không ghi đè file hỏng
                    flock($fp, LOCK_UN); // Mở khóa trước khi return
                    fclose($fp);
                    return false;
                }
                // Chỉ gán nếu giải mã thành công và là mảng
                 if (is_array($decoded)) {
                    $history = $decoded;
                } else {
                     // Nếu JSON hợp lệ nhưng không phải array (ví dụ: null, "string", 123)
                     error_log("Lỗi ghi lịch sử dịch: Nội dung JSON không phải là một mảng: " . $filePath);
                     // Có thể reset thành mảng rỗng nếu muốn
                     // $history = [];
                     flock($fp, LOCK_UN);
                     fclose($fp);
                     return false;
                 }
            }

            // Tạo bản ghi mới
            $newRecord = [
                'original_url' => $originalUrl,
                'translated_url' => $translatedUrl,
                'translated_title' => $translatedTitle, // Đã kiểm tra UTF-8
                'timestamp' => $timestamp
            ];

            // Thêm bản ghi mới vào cuối
            $history[] = $newRecord;

            // Quản lý giới hạn số lượng bản ghi
            $currentCount = count($history);
            if ($currentCount > $maxRecords) {
                // Loại bỏ các bản ghi cũ nhất từ đầu mảng
                $history = array_slice($history, $currentCount - $maxRecords);
            }

            // Mã hóa lại thành JSON
            // JSON_UNESCAPED_UNICODE để giữ ký tự tiếng Việt
            // JSON_PRETTY_PRINT để dễ đọc file (có thể bỏ nếu ưu tiên kích thước file nhỏ nhất)
            $newJsonContent = json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($newJsonContent === false) {
                error_log("Lỗi ghi lịch sử dịch: Không thể mã hóa dữ liệu thành JSON. Lỗi: " . json_last_error_msg());
                flock($fp, LOCK_UN);
                fclose($fp);
                return false;
            }

            // Ghi lại toàn bộ file
            ftruncate($fp, 0);   // Xóa trắng nội dung file hiện tại
            rewind($fp);        // Đưa con trỏ về đầu file
            $bytesWritten = fwrite($fp, $newJsonContent);

            if ($bytesWritten === false || $bytesWritten < strlen($newJsonContent)) {
                 error_log("Lỗi ghi lịch sử dịch: Không thể ghi đầy đủ nội dung vào file: " . $filePath);
                 // Cố gắng khôi phục file gốc? (phức tạp) Hoặc chấp nhận file có thể bị lỗi
                 flock($fp, LOCK_UN);
                 fclose($fp);
                 return false;
            }

            fflush($fp); // Đảm bảo dữ liệu được ghi xuống đĩa

            // Ghi thành công
            flock($fp, LOCK_UN); // **Mở khóa file**
            fclose($fp);
            return true;

        } catch (\Throwable $e) {
            // Xử lý các lỗi không mong muốn khác
            error_log("Lỗi không xác định khi ghi lịch sử dịch: " . $e->getMessage());
            flock($fp, LOCK_UN); // Đảm bảo mở khóa nếu có lỗi
            fclose($fp);
            return false;
        }
    } else {
        // Không thể khóa file (có thể tiến trình khác đang ghi)
        error_log("Lỗi ghi lịch sử dịch: Không thể khóa file để ghi: " . $filePath);
        fclose($fp);
        return false;
    }
}