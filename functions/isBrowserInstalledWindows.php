<?php
// Dùng trong file small_settings.php
/**
 * Kiểm tra xem Chrome hoặc Firefox có được cài đặt trên Windows hay không.
 * Hàm này chạy trên localhost và sử dụng các phương pháp kiểm tra của Windows.
 *
 * @param string $browserName Tên trình duyệt ('chrome' hoặc 'firefox')
 * @return bool True nếu có vẻ đã cài đặt, False nếu không.
 */
function isBrowserInstalledWindows(string $browserName): bool
{
    // Đảm bảo chỉ chạy trên Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        // echo "Hàm này chỉ dành cho Windows.\n"; // Thông báo nếu cần
        return false;
    }
    
    $browserNameLower = strtolower($browserName);
    $found = false;

    // --- 1. Kiểm tra các đường dẫn cài đặt phổ biến trên Windows ---
    $programFiles = getenv('ProgramFiles');
    $programFilesX86 = getenv('ProgramFiles(x86)');
    $localAppData = getenv('LOCALAPPDATA');
    $pathsToCheck = [];

    if ($browserNameLower === 'chrome') {
        if ($programFiles) $pathsToCheck[] = $programFiles . '\\Google\\Chrome\\Application\\chrome.exe';
        if ($programFilesX86) $pathsToCheck[] = $programFilesX86 . '\\Google\\Chrome\\Application\\chrome.exe';
        if ($localAppData) $pathsToCheck[] = $localAppData . '\\Google\\Chrome\\Application\\chrome.exe';
    } elseif ($browserNameLower === 'firefox') {
        if ($programFiles) $pathsToCheck[] = $programFiles . '\\Mozilla Firefox\\firefox.exe';
        if ($programFilesX86) $pathsToCheck[] = $programFilesX86 . '\\Mozilla Firefox\\firefox.exe';
        // Firefox ít khi cài vào AppData nhưng vẫn kiểm tra cho chắc
        if ($localAppData) $pathsToCheck[] = $localAppData . '\\Mozilla Firefox\\firefox.exe';
    }

    foreach ($pathsToCheck as $path) {
        if (file_exists($path)) {
            // echo "Tìm thấy tại đường dẫn: $path\n"; // Debug
            $found = true;
            break; // Thoát vòng lặp ngay khi tìm thấy
        }
    }

    // --- 2. Thử thực thi lệnh 'where' (nếu chưa tìm thấy và shell_exec khả dụng) ---
    if (!$found && function_exists('shell_exec')) {
        $executable = '';
        if ($browserNameLower === 'chrome') {
            $executable = 'chrome.exe';
        } elseif ($browserNameLower === 'firefox') {
            $executable = 'firefox.exe';
        }

        if ($executable) {
            // Lệnh 'where' tìm file trong PATH và thư mục hiện tại.
            // 2> NUL để ẩn lỗi nếu không tìm thấy.
            $command = 'where ' . escapeshellarg($executable) . ' 2> NUL';
            // echo "Đang thử lệnh: $command\n"; // Debug
            $output = @shell_exec($command);
            // Nếu lệnh trả về kết quả (không phải null, false hoặc chuỗi rỗng) tức là tìm thấy
            if ($output !== null && $output !== false && trim($output) !== '') {
                 // echo "Tìm thấy qua lệnh 'where': $output\n"; // Debug
                $found = true;
            }
        }
    }

    // --- 3. Thử truy vấn Registry (nếu vẫn chưa tìm thấy và shell_exec khả dụng) ---
    // Đây thường là cách đáng tin cậy để kiểm tra ứng dụng đã đăng ký
    if (!$found && function_exists('shell_exec')) {
        $regKeyBase = 'HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths\\';
        $regKey = '';

        if ($browserNameLower === 'chrome') {
            $regKey = $regKeyBase . 'chrome.exe';
        } elseif ($browserNameLower === 'firefox') {
            $regKey = $regKeyBase . 'firefox.exe';
        }

        if ($regKey) {
            // Lệnh 'reg query' kiểm tra sự tồn tại của key.
            // /ve để truy vấn giá trị mặc định (thường là đường dẫn đầy đủ).
            // 2> NUL để ẩn lỗi nếu key không tồn tại.
            $command = 'reg query "' . $regKey . '" /ve 2> NUL';
            // echo "Đang thử lệnh: $command\n"; // Debug
            $output = @shell_exec($command);
             // Nếu lệnh trả về kết quả và chứa tên file thực thi (phòng trường hợp key tồn tại nhưng rỗng)
            if ($output !== null && $output !== false && stripos($output, ($browserNameLower === 'chrome' ? 'chrome.exe' : 'firefox.exe')) !== false) {
                // echo "Tìm thấy qua Registry: HKLM\n"; // Debug
                $found = true;
            }

            // Có thể kiểm tra thêm HKEY_CURRENT_USER nếu cần, nhưng HKLM thường đủ cho cài đặt phổ biến
            // if (!$found) {
            //     $regKeyCurrentUser = 'HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\App Paths\\' . ($browserNameLower === 'chrome' ? 'chrome.exe' : 'firefox.exe');
            //     $commandCU = 'reg query "' . $regKeyCurrentUser . '" /ve 2> NUL';
            //     $outputCU = @shell_exec($commandCU);
            //     if ($outputCU !== null && $outputCU !== false && stripos($outputCU, ($browserNameLower === 'chrome' ? 'chrome.exe' : 'firefox.exe')) !== false) {
            //         // echo "Tìm thấy qua Registry: HKCU\n"; // Debug
            //         $found = true;
            //     }
            // }
        }
    }

    return $found;
}
