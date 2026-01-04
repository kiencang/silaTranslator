<?php
// File: functions/callGeminiApi.php 
// Hàm gọi API AI của Gemini // PHP thuần, không dùng thư viện

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------   
/**
 * @param string $set Thư mục chứa file markdown, chứa SI & prompt, nó là kiểu của nhóm lệnh, ví dụ setEV là prompt & SI cho dịch Anh - Việt
 * @param string $type Để biết kiểu file cần phải lấy, rỗng thì lấy mặc định (dịch markdown), các lựa chọn khác là dịch plaint text hoặc dịch đầy đủ thẻ html
 * @return string nội dung prompt.
 */
// Hàm đọc prompt mặc định
function getDefaultPrompt(string $set, string $type = ''): string {
    $promptContent = ''; // Gán giá trị mặc định của prompt là rỗng
    
    // __DIR__ là đường dẫn tuyệt đối đến thư mục chứa file này 
    $projectBasePath = dirname(__DIR__); // Lấy vị trí của thư mục gốc, dựa trên ví trí đặt trong file của hàm này

    // 2. Định nghĩa tên các file markdown cần đọc
    $promptFileName = 'prompt.md'; // lấy prompt markdown mặc định
    
    // Hoặc tùy theo kiểu mà lấy prompt tương ứng
    if ($type == 'plain') {
        $promptFileName = 'prompt_plain.md'; // Prompt dành cho dịch văn bản gần như thuần túy
    }
    
    // Hiện ngoài kiểu mặc định chỉ xử lý thêm plain & html
    if ($type == 'all_html_tags') {
        $promptFileName = 'prompt_all_html_tags.md'; // Prompt dành cho dịch giữ lại toàn bộ thẻ HTML
    }     
                    
    // Từ đường dẫn gốc, đi vào thư mục 'markdown', rồi đến tên file
    $promptFilePath = $projectBasePath . DIRECTORY_SEPARATOR . 'default_prompt' . DIRECTORY_SEPARATOR . $set . DIRECTORY_SEPARATOR . $promptFileName;
    
    // Nếu đường dẫn tồn taị
    if (file_exists($promptFilePath)) { // Cố gắng đọc file       
        $promptContentFG = file_get_contents($promptFilePath);
        
        // Nếu nó có nội dung
        if ($promptContentFG !== false && $promptContentFG != '') {
             $promptContent = $promptContentFG; // lấy được thông tin prompt tùy chỉnh
        }
    }

    return $promptContent; // Trả về kết quả với prompt tương ứng với $set và $type
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------   



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * @param string $set Thư mục chứ file, nó là kiểu của nhóm lệnh
 * @param string $type Để biết kiểu file cần phải lấy, rỗng thì lấy mặc định (markdown)
 * @return string nội dung systemInstructions.
 */
// Hàm đọc systemInstructions mặc định
function getDefaultSystemIn(string $set, string $type = ''): string {
    $systemContent = ''; // Gán giá trị mặc định ban đâu là rỗng
    
    // __DIR__ là đường dẫn tuyệt đối đến thư mục chứa file này 
    $projectBasePath = dirname(__DIR__); // Lấy thư mục gốc

    // 2. Định nghĩa tên các file markdown cần đọc
    $systemFileName = 'system_instructions.md'; // SI mặc định
    
    // Nếu kiểu dịch là plain, gán tên file cần lấy
    if ($type == 'plain') {
        $systemFileName = 'system_instructions_plain.md'; // Dành cho dạng chỉ dịch văn bản gần như thuần túy
    }
    
    // Nếu kiểu dịch là html, gán tên file cần lấy
    if ($type == 'all_html_tags') {
        $systemFileName = 'system_instructions_all_html_tags.md'; // Dành cho dạng dịch toàn bộ HTML
    }    
                    
    // Từ đường dẫn gốc, đi vào thư mục 'markdown', rồi đến tên file
    $systemFilePath = $projectBasePath . DIRECTORY_SEPARATOR . 'default_prompt' . DIRECTORY_SEPARATOR . $set . DIRECTORY_SEPARATOR . $systemFileName;
    
    // Nếu đường dẫn tồn tại
    if (file_exists($systemFilePath)) {// Cố gắng đọc file
        $systemContentFG = file_get_contents($systemFilePath); // Lấy nội dung
        
        // Nếu nội dung tồn tại và khác rỗng
        if ($systemContentFG !== false && $systemContentFG != '') {
             $systemContent = $systemContentFG; // lấy được thông tin SI tùy chỉnh
        }
    }

    return $systemContent; // Trả về chuỗi kết quả cuối cùng
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Gọi API Gemini để dịch văn bản.
 * @param string $textToTranslate Văn bản cần dịch.
 * @param string $plainTextSelect Để quyết định xem đây có phải là kiểu dịch văn bản thuần túy hay không.
 * @param string $setPrSy Xem cần lấy prompt & system ở thư mục nào, mặc định lấy ở setA, đang là thư mục chứa các prompt/systemInstructions tốt nhất
 * @param bool $maxTrans Để biết có sử dụng dịch tăng cường hay không, tức là tạo SI & prompt đặc biệt phù hợp với nội dung cần dịch
 * @return string Văn bản đã dịch.
 * @throws Exception Nếu có lỗi khi gọi API.
 */
//  *   **Nội dung bên trong các khối mã (code blocks):** Bất kỳ văn bản nào nằm giữa ` ``` ` hoặc được thụt lề 4 dấu cách phải được giữ nguyên 100%.
function callGeminiApi(string $textToTranslate, int $plainTextSelect = 0, string $sysInsContent = '', string $promptContent = '', bool $all_html_tags = false, string $setPrSy = 'setEV', bool $maxTrans = false): array {    
    // Khi là dịch giữ nguyên định dạng
    if ($plainTextSelect !== 1) {
        // Với dịch giữ nguyên định dạng, có 2 kiểu là markdown & html, chúng ta cần biết điều này qua tham số $all_html_tags

        // Kiểu markdown mặc định
        if (!$all_html_tags) { // Lấy thông tin từ thư mục tương ứng
            $curent_systemIn = getDefaultSystemIn($setPrSy);
            $curent_prompt = getDefaultPrompt($setPrSy);  
        }

        // Nếu là kiểu dịch toàn bộ thẻ HTML
        if ($all_html_tags) { 
            $type_prompt_system = 'all_html_tags';
            $curent_systemIn = getDefaultSystemIn($setPrSy, $type_prompt_system);
            $curent_prompt = getDefaultPrompt($setPrSy, $type_prompt_system);      
        } 
        
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------     
// Kiểm tra xem có cần đưa thêm ví dụ không // Chỉ có trong trường hợp dịch Anh Việt
        if ($setPrSy == 'setEV') {
            if (defined('EXAMPLE_FOR_TRANSLATION') && EXAMPLE_FOR_TRANSLATION === true) {
                // Lấy được nội dung các ví dụ
                $example_trans = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'default_prompt' . DIRECTORY_SEPARATOR . $setPrSy . DIRECTORY_SEPARATOR . 'example.md');
                $curent_systemIn = $curent_systemIn . "\n\n" . $example_trans; // nối thêm các ví dụ vào
            }
        }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------          

        // Nếu có yêu cầu nâng cấp chất lượng dịch
        if ($maxTrans) {
            $resultArrayPS = callGeminiApiDynamic($textToTranslate, $curent_prompt, $curent_systemIn, $plainTextSelect, $setPrSy);

            if ($resultArrayPS !== null) { // chống lỗi
                // Xóa hàng rào markdown đầu chuỗi trước, sau đó xóa tiếp những dấu như [] ở đầu và cuối chuỗi
                $curent_prompt = removeLeadingMarkdownFence(removeExtraCharacters($resultArrayPS['new_prompt'])); // gán lại
                $curent_systemIn = removeLeadingMarkdownFence(removeExtraCharacters($resultArrayPS['new_system_instruction'])); // gán lại
            }        
        }    

        // Tạo thành kết quả cuối cùng       
        $systemInstruction = $curent_systemIn; // system
        $prompt = $curent_prompt . "\n" . $textToTranslate;
    }
// E ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 



// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
    if ($plainTextSelect === 1) {
        $type_prompt_system = 'plain';

        $curent_systemIn = getDefaultSystemIn($setPrSy, $type_prompt_system); // lấy system md trong thư mục tương ứng, với tên có hậu tố plain
        $curent_prompt = getDefaultPrompt($setPrSy, $type_prompt_system); // lấy prompt md trong thư mục tương ứng, với tên có hậu tố plain

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------     
// Kiểm tra xem có cần đưa thêm ví dụ vào cho AI không // Chỉ có trong trường hợp dịch Anh Việt
        if ($setPrSy == 'setEV') {
            if (defined('EXAMPLE_FOR_TRANSLATION') && EXAMPLE_FOR_TRANSLATION === true) {
                // Lấy được nội dung các ví dụ
                $example_trans = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'default_prompt' . DIRECTORY_SEPARATOR . $setPrSy . DIRECTORY_SEPARATOR . 'example.md');
                $curent_systemIn = $curent_systemIn . "\n\n" . $example_trans; // nối thêm các ví dụ vào
            }
        }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------        
        
        // Kiểm tra xem có prompt và system tùy chỉnh không, nếu không có thì dùng mặc định
        if ($sysInsContent == '' || $promptContent == '') { 
            // Nếu có yêu cầu nâng cấp chất lượng dịch
            if ($maxTrans) {
                $resultArrayPS = callGeminiApiDynamic($textToTranslate, $curent_prompt, $curent_systemIn, $plainTextSelect, $setPrSy);

                if ($resultArrayPS !== null) { // chống lỗi
                    $curent_prompt = removeExtraCharacters(removeLeadingMarkdownFence($resultArrayPS['new_prompt'])); // gán lại
                    $curent_systemIn = removeExtraCharacters(removeLeadingMarkdownFence($resultArrayPS['new_system_instruction'])); // gán lại
                }        
            }         

            $systemInstruction = $curent_systemIn; // gán
            // Tạo thành prompt, kiểu text
            $prompt = $curent_prompt . "\n\n" . $textToTranslate; // prompt tổng hợp
        } else { // Nếu có chỉ thị tùy chỉnh thì phải lấy chỉ thị đó làm prompt và systemInstructions
            // Lưu ý prompt và system chỉ áp dụng cho việc dịch văn bản thuần túy
            $systemInstruction = $sysInsContent;
            $prompt = $promptContent . "\n\n" . $textToTranslate;       
        }  
    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------    

    
    
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------     
// Lấy thông tin về temperature và topP
// --- 1. Định nghĩa cấu hình mặc định ---
    // Đây là cấu hình sẽ được sử dụng nếu có bất kỳ vấn đề nào với file config
    $defaultConfig = [
        'temperature' => 0.3, // Giá trị mặc định an toàn
        'topP'        => 0.9, // Giá trị mặc định an toàn
        // Thêm các cấu hình mặc định khác nếu cần
    ];

// --- 2. Xác định đường dẫn file cấu hình ---
    // Sử dụng đường dẫn tuyệt đối để ổn định hơn // Một mảng sẵn trong PHP được sử dụng để lưu các tham số nhiệt độ và topP
    $configFilePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'myself'. DIRECTORY_SEPARATOR .'ai_temp_top_config.php';

// --- 3. Khởi tạo cấu hình hiện tại bằng giá trị mặc định ---
    // Bắt đầu bằng cách giả định rằng chúng ta sẽ dùng mặc định.
    // Điều này đảm bảo $currentConfig luôn là một mảng hợp lệ.
    $currentConfig = $defaultConfig;
    $configLoadMessage = null; // Biến để lưu thông báo về việc tải config (tùy chọn)

// --- 4. Cố gắng tải và hợp nhất (merge) cấu hình người dùng ---
    // Bước này sẽ chỉ cập nhật $currentConfig nếu file config hợp lệ.
    if (file_exists($configFilePath)) {
        // File tồn tại, thử nạp nó
        try {
            // Sử dụng require vì nó trả về giá trị từ lệnh return trong file.
            // Nếu file không có 'return', hoặc return giá trị không phải mảng,
            // hoặc có lỗi runtime (không phải syntax error), nó sẽ được xử lý.
            // Chú ý: Lỗi cú pháp (Syntax Error) trong ai_config.php vẫn sẽ là Fatal Error
            // và dừng script tại đây, điều này khó tránh khỏi với require/include.
            $loadedConfig = require $configFilePath;

            // --- KIỂM TRA QUAN TRỌNG: Kết quả có phải là mảng không? ---
            if (is_array($loadedConfig)) {
                // THÀNH CÔNG: File hợp lệ và trả về một mảng.
                // Gộp mảng đã tải vào mảng mặc định.
                // Các giá trị trong $loadedConfig sẽ ghi đè lên giá trị trong $defaultConfig.
                $currentConfig = array_merge($defaultConfig, $loadedConfig);
                $configLoadMessage = "Thông tin: Đã tải thành công cấu hình từ '{$configFilePath}'.";
            } else {
                // CẢNH BÁO: File tồn tại nhưng không trả về mảng hợp lệ.
                // $currentConfig vẫn giữ nguyên giá trị mặc định đã gán ban đầu.
                $configLoadMessage = "Cảnh báo: File cấu hình '{$configFilePath}' không trả về mảng hợp lệ. Sử dụng cài đặt mặc định.";
                // Ghi log lỗi ở đây nếu muốn:
                error_log("Configuration file '{$configFilePath}' không trả về một mảng hợp lệ.");
            }
        } catch (\Throwable $e) {
            // LỖI RUNTIME: Bắt các lỗi có thể xảy ra trong quá trình thực thi file config
            // (Ví dụ: lỗi logic bên trong file config, dù hiếm gặp với file đơn giản).
            // $currentConfig vẫn giữ nguyên giá trị mặc định.
             $configLoadMessage = "Lỗi: Có lỗi khi thực thi file cấu hình '{$configFilePath}': " . $e->getMessage() . ". Sử dụng cài đặt mặc định.";
            // Ghi log lỗi chi tiết:
            error_log("Lỗi khi thực thi configuration file '{$configFilePath}': " . $e->getMessage());
        }
    } else {
        // THÔNG BÁO: File không tồn tại.
        // $currentConfig vẫn giữ nguyên giá trị mặc định.
        $configLoadMessage = "Thông tin: File cấu hình '{$configFilePath}' không tìm thấy. Sử dụng cài đặt mặc định.";
        // Ghi log nếu cần
        error_log("Configuration file '{$configFilePath}' không tìm thấy. Sử dụng các giá trị mặc định dự phòng");
    }

// --- 5. Lấy giá trị cuối cùng từ cấu hình hiện tại ---
    // Tại thời điểm này, $currentConfig *luôn luôn* là một mảng hợp lệ
    // (hoặc là mảng mặc định, hoặc là mảng đã được gộp).
    // Sử dụng ?? để tăng thêm độ an toàn phòng trường hợp key bị thiếu (dù không nên xảy ra nếu default đủ).
    $temperature = $currentConfig['temperature'] ?? $defaultConfig['temperature'];
    $topP = $currentConfig['topP'] ?? $defaultConfig['topP'];

    // Payload cần chuẩn xác theo tài liệu Gemini
    // Các thông tin thiết lập là chung cho cả 2 kiểu dịch // Phần này bổ sung thêm có sử dụng công cụ tìm kiếm nữa hay không?
    // Đặc biệt hữu ích trong trường hợp cần kiểm tra tính chính xác của các sự kiện & thường chỉ cần khi sử dụng kiểu dịch nâng cao
    // Nhìn chung không cần bổ sung công cụ tìm kiếm trong đa số trường hợp
    // Rất tốt token với kiểu dịch sử dụng kèm hỗ trợ công cụ tìm kiếm
    // Đang loại bỏ //'topP' => $topP, [chỉ để $temperature kiểm soát chất lượng dịch]
    
    if (defined('SEARCH_ENGINE_GEMINI') && SEARCH_ENGINE_GEMINI) {
        // Có sự hỗ trợ thêm từ máy tìm kiếm
        $payload_data = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
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
                'responseMimeType' => 'text/plain' 
            ],
            'tools' => [ 
                [
                  'googleSearch' => (object)[]
                ]
            ]
        ];
    } 
    else { // Không có sự hỗ trợ thêm từ máy tìm kiếm
        $payload_data = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
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
                'responseMimeType' => 'text/plain' 
            ]
        ];    
    }
    
    $payload = json_encode($payload_data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Nếu cấu hình $payload_data không chuẩn nó sẽ ném ra lỗi bên dưới
        throw new Exception("Lỗi khi tạo JSON payload cho API: " . json_last_error_msg());
    }


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GEMINI_API_URL); // Đảm bảo GEMINI_API_URL được định nghĩa đúng
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Lưu phản hồi vào biến thay vì in ra màn hình
    curl_setopt($ch, CURLOPT_POST, true); // Sử dụng phương thức POST để gửi dữ liệu
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); // Biến $payload được gửi trong phần thân của yêu cầu POST
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Thông báo cho máy chủ biết định dạng dữ liệu được gửi đi là JSON
    curl_setopt($ch, CURLOPT_TIMEOUT, defined('CURL_TIMEOUT_API') ? CURL_TIMEOUT_API + 30 : 900); // Tăng timeout cho API call // CURL_TIMEOUT_API đã được định nghĩa trong config.php nhưng thêm dự phòng cho chắc // Lưu ý là API dịch tốn rất nhiều thời gian
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Xác minh chứng chỉ SSL của API
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Vẫn là biện pháp tăng cường trong xác minh SSL, đảm bảo hostname khớp với chứng chỉ SSL được liệt kê, một biện pháp tăng cường thêm để phòng giả mạo

    $response = curl_exec($ch); // Kết quả phản hồi
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Mã trạng thái http cuối cùng, ví dụ 200 OK, 404 Not Found, 500 Internet Server Error
    
    $error = curl_error($ch); // Trả về chuỗi mô tả lỗi, nếu không có lỗi, trả về chuỗi rỗng
    $errno = curl_errno($ch); // Trả về mã lỗi cuối cùng, nếu không có lỗi, trả về 0
    curl_close($ch); // Đóng phiên & giải phóng tài nguyên hệ thống

    if ($errno) {
        throw new Exception("Lỗi cURL khi gọi API Gemini (Code $errno): " . $error);
    }

    $responseData = json_decode($response, true); // decode phản hồi JSON

    if ($httpCode >= 400) {
        $errorMessage = "Lỗi API Gemini (HTTP $httpCode).";
        // Cố gắng lấy thông tin lỗi chi tiết từ response JSON
        if (isset($responseData['error']['message'])) {
            $errorMessage .= " Message: " . $responseData['error']['message'];
        } elseif (!empty($response)) {
             // Nếu response không phải JSON hoặc không có message, hiển thị raw response
             $errorMessage .= " Raw Response: " . substr(htmlspecialchars($response), 0, 500) . "..."; // Giới hạn độ dài
        }
        
        throw new Exception($errorMessage);
    }

    // Kiểm tra cấu trúc response theo tài liệu Gemini mới nhất
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        // Trả về text đã dịch
        // Trong callGeminiApi, thay vì return chỉ text, trả về một mảng:
        return [
            'translated_text' => $responseData['candidates'][0]['content']['parts'][0]['text'],
            'used_temperature' => $temperature,
            'used_topP' => $topP,
            'used_prompt' => $prompt, 
            'used_system_instruction' => $systemInstruction 
        ];
    } elseif (isset($responseData['candidates'][0]['finishReason']) && $responseData['candidates'][0]['finishReason'] !== 'STOP') {
         // Kiểm tra xem có bị dừng vì lý do khác không (vd: SAFETY, MAX_TOKENS)
         throw new Exception("API Gemini dừng không thành công. Lý do: " . $responseData['candidates'][0]['finishReason']);
    }
    else {
        // Log lại cấu trúc lạ để debug
        error_log("Cấu trúc response Gemini không mong đợi: " . $response);
        throw new Exception("Không nhận được dữ liệu dịch hợp lệ từ API Gemini. Cấu trúc response không đúng hoặc bị trống.");
    }
}