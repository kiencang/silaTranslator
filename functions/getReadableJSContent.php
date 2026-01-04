<?php

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Hàm này rất quan trọng dùng để trích xuất nội dung chính để đưa cho API AI dịch
// Sử dụng kiểu JS chính của Mozilla
// Chạy câu lệnh để tạo file exe mới tương ứng
// cmd đến thư mục lập trình cd C:\xampp\htdocs\silaTranslator
// pkg node_scripts/parse_simple.js --target node18-win-x64 --output bin/parser64bit_v22node.exe
// Lệnh npx mới chạy do đã điều chỉnh cách cài cục bộ cho dự án
// npx pkg node_scripts/parse_simple.js --target node22-win-x64 --output bin/parser64bit_v22node.exe
// C:\xampp\htdocs\silaTranslator>pkg node_scripts/parse_simple.js --target node18-win-x64 --output bin/parser64.exe
// C:\xampp\htdocs\silaTranslator>pkg node_scripts/parse_simple.js --target node22-win-x64 --output bin/parser64.exe
function getReadableJSContent(string $htmlInput): string|false {
    // !!! THAY ĐỔI Ở ĐÂY: Đường dẫn đến file thực thi đã đóng gói bằng pkg !!!
    // Giả sử file PHP này nằm ở C:\xampp\htdocs\silaTranslator\
    // Và file parser64.exe nằm ở C:\xampp\htdocs\silaTranslator\bin\parser64bit_v22node.exe
    $parserExecutablePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'parser64bit_v22node.exe';

    $tempHtmlFile = null; // file tạm
    $cleanedHtml = false; // HTML đã lọc

    try {
        if (empty(trim($htmlInput))) { // input HTML rỗng
            return false;
        }

        // 1. Kiểm tra file thực thi đã đóng gói
        if (!file_exists($parserExecutablePath)) { // Lỗi đường dẫn hoặc không tìm thấy file exe cần dùng
            error_log("getReadableContent: Không tìm thấy file parser đã đóng gói tại: $parserExecutablePath");
            return false;
        }
        // Trên Linux/macOS có thể cần kiểm tra quyền thực thi: !is_executable($parserExecutablePath)
        // Tạm thời chỉ có exe cho hệ điều hành Windows 64bit
        // 2. Tạo và ghi file tạm (như cũ)
        $tempHtmlFile = tempnam(sys_get_temp_dir(), 'readability_pkg_');
        if (!$tempHtmlFile)
            return false;

        // "\xEF\xBB\xBF" là UTF-8 BOM (Byte Order Mark) dùng để nhận diện bảng mã UTF-8 của file
        if (file_put_contents($tempHtmlFile, "\xEF\xBB\xBF" . $htmlInput) === false)
            return false;

        // 3. Xây dựng và thực thi lệnh gọi file parser.exe
        // Chỉ cần truyền đường dẫn file tạm làm tham số
        $command = escapeshellarg($parserExecutablePath) . ' '
                . escapeshellarg($tempHtmlFile);

        // Thực thi câu lệnh
        $output = shell_exec($command);

        // 4. Kiểm tra và trả về kết quả (như cũ)
        if ($output !== null && trim($output) !== '') {
            $cleanedHtml = $output;
        } else {
            $cleanedHtml = false; // Trường hợp lỗi
        }
    } catch (\Throwable $e) {
        error_log("Lỗi trong getReadableContent (pkg): " . $e->getMessage());
        $cleanedHtml = false;
    } finally {
        if ($tempHtmlFile && file_exists($tempHtmlFile)) {
            unlink($tempHtmlFile);
        }
    }

    // Trả về nội dung đã được lọc bằng ReadableJS
    return $cleanedHtml;
}

