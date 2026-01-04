<?php
// File: functions/callGeminiApiDynamic.php
// Cải thiện chất lượng dịch bằng biện pháp tạo SI & prompt mới dựa trên nội dung cần dịch và SI, prompt mẫu

/**
 * Gọi API Gemini để tạo ra prompt mới và SI mới từ mẫu và nội dung cần dịch.
 * Trả về một mảng chứa prompt và system instruction mới.
 *
 * @param string $textToTranslate Văn bản gốc cần dịch.
 * @param string $promptInput Là prompt mẫu.
 * @param string $systemInput Là SI mẫu.
 * @param string $setPrSy để biết kiểu dịch là Anh - Việt hoặc kiểu nào?.
 * @return ?array Mảng kết hợp chứa ['new_prompt' => string, 'new_system_instruction' => string], hoặc null nếu có lỗi.
 * @throws Exception (Có thể cân nhắc nếu muốn dừng hẳn khi lỗi nghiêm trọng thay vì trả về null)
 */
function callGeminiApiDynamic(string $textToTranslate, string $promptInput, string $systemInput, int $plainTextSelect = 0, string $setPrSy = 'setEV'): ?array { // Thay đổi return type thành ?array
    // 1. Làm sạch và loại bỏ HTML
    // Nên dùng strip_tags nếu $textToTranslate có thể chứa HTML
    // $cleanedText = trim(strip_tags($textToTranslate));
    $cleanedText = trim($textToTranslate); // Giữ nguyên nếu chắc chắn không có HTML

    if (empty($cleanedText)) {
        error_log("Input text trống sau khi làm sạch.");
        return null; // Trả về null khi input trống
    }

    $retain_information = '"**Định dạng Output:** Chỉ trả về đoạn mã Markdown đã được dịch. Không thêm bất kỳ giải thích hay văn bản nào khác." & "**BẮT ĐẦU NỘI DUNG MARKDOWN CẦN DỊCH:**"';
 
    if ($plainTextSelect === 1) {
        $retain_information = '"**Định dạng Output:** Chỉ trả về văn bản thuần túy (plain text) tiếng Việt đã được dịch, giữ nguyên các dấu định dạng Markdown cơ bản, URLs dạng văn bản, placeholders và cấu trúc đoạn (`\n\n`) theo yêu cầu. Không thêm bất kỳ giải thích hay văn bản nào khác." & "**BẮT ĐẦU VĂN BẢN CẦN DỊCH:**"';
    }
    
    if (ALL_HTML_TAGS) {
        $retain_information = '"**Định dạng Output:** **Chỉ trả về đoạn mã HTML đã được dịch.** Không thêm bất kỳ lời giải thích, ghi chú hay văn bản nào khác vào phần trả lời." & "**BẮT ĐẦU MÃ HTML CẦN DỊCH:**"';
    }
    
    // Viết lại ví dụ
    $rewrite_examples = ''; 
    if (defined('EXAMPLE_FOR_TRANSLATION') && EXAMPLE_FOR_TRANSLATION === true && $setPrSy == 'setEV') {
        $rewrite_examples = '\n' . '9.  Viết lại các ví dụ trong **Phụ lục A: Ví Dụ Minh Họa Tái Cấu Trúc Câu & Dịch Tự Nhiên** để nó phù hợp hơn với nội dung cần dịch (`source_content`). Cung cấp từ 3 đến 6 ví dụ, số lượng cụ thể linh động theo nội dung cần dịch. Đảm bảo **các ví dụ PHẢI có chất lượng rất cao**. Các ví dụ đưa ra cần minh họa mạnh mẽ, rõ ràng cách tái cấu trúc câu để đảm bảo mục tiêu quan trọng nhất: giúp AI **tạo ra bản dịch tự nhiên, trôi chảy hơn**.'.'\n'.
                '10.  Viết lại các ví dụ trong **Phụ lục B: Ví dụ Minh họa Thích ứng Văn hóa (Cultural Adaptation)** để nó phù hợp hơn với nội dung cần dịch (`source_content`). Đảm bảo **các ví dụ PHẢI có chất lượng rất cao**. Các ví dụ chỉ NÊN tập trung vào nội dung cần dịch. Cung cấp từ 3 đến 6 ví dụ, số lượng cụ thể linh động theo nội dung cần dịch.';
    }

    
    // 2. Định nghĩa System Instruction và Prompt (Không đổi)
    $systemInstruction = 'Bạn là chuyên gia AI, có khả năng **tạo ra các prompt & system instructions có chất lượng rất cao** phục vụ cho mục đích **dịch thuật từ ngôn ngữ khác sang tiếng Việt**.';
    $prompt = '**Giả sử bạn có nội dung sau (gọi là `source_content`) cần dịch sang tiếng Việt:**
```'. $cleanedText . '```

---
**Giả sử dưới đây là `prompt mẫu` để thực hiện việc dịch nội dung trên:**
```' . $promptInput . '```

---
**Giả sử dưới đây là `system instructions mẫu` tương ứng cho `prompt mẫu`:**
```' . $systemInput . '```

---
**MỤC TIÊU:** **Dựa trên** định dạng, cấu trúc, nội dung của `prompt mẫu` và `system instructions mẫu` để tạo ra `prompt mới` và `system instructions mới` tương ứng. Mục đích là để `prompt mới` và `system instructions mới` PHẢI **thực sự được thiết kế riêng (tailor-made/highly specific) cho nội dung cần dịch (`source_content`)**.

**Các yêu cầu quan trọng khác:**
1.  `system instructions mới` và `prompt mới` PHẢI phải hướng tới **mục đích cho chất lượng dịch sang tiếng Việt cao nhất có thể**.
2.  `system instructions mới` và `prompt mới` PHẢI **hỗ trợ lẫn nhau** (work in conjunction), phối hợp hoàn hảo để cùng nhau tạo ra bộ hướng dẫn giúp AI tạo ra bản dịch tốt hơn. 
3.  `system instructions mới` nên cung cấp bộ nguyên tắc chung & định vị vai trò chuyên gia của AI, còn `prompt mới` dựa trên các nguyên tắc đó để làm rõ các ưu tiên cụ thể hơn cho nhiệm vụ.
4.  `system instructions mới` & `prompt mới` PHẢI được viết dưới dạng markdown chuẩn xác & rõ ràng. Hãy tận dụng tối đa cách trình bày của markdown để giúp AI hiểu đúng & đầy đủ chi tiết nhiệm vụ.
5.  `system instructions mới` & `prompt mới` nên ngắn gọn súc tích, tuy vậy CHẤT LƯỢNG cao vẫn là tiêu chí quan trọng hơn. Nói cách khác: `system instructions mới` & `prompt mới` có thể dài, miễn là điều đó giúp ích cho việc dịch thuật.
6.  **Tôn trọng tuyệt đối nội dung gốc cần dịch**: Bạn KHÔNG cần đặt giả định về các sự kiện gần đây trong bài (nếu nó có), hãy CHỈ tập trung vào việc phân tích văn bản gốc với mục đích duy nhất là để cải thiện chất lượng bản dịch cuối cùng.
7.  **HẾT SỨC LƯU Ý** `prompt mới` chỉ bao gồm các hướng dẫn, TUYỆT ĐỐI KHÔNG gắn thêm toàn bộ nội dung cần dịch `source_content` vào `prompt mới`.
8.  Giữ lại 2 thông tin quan trọng này trong `prompt mẫu` để đưa vào `prompt mới`: ' . $retain_information . $rewrite_examples .
            
'Nhiệm vụ này RẤT QUAN TRỌNG với tôi, vì vậy hãy tận dụng toàn bộ khả năng tốt nhất của bạn để xử lý yêu cầu. Nên nhớ là bạn có **TOÀN QUYỀN chỉnh sửa**, KHÔNG có bất cứ ràng buộc nào, ngoài ràng buộc **cải thiện hơn nữa chất lượng dịch**. Trong quá trình bạn chỉnh sửa, thêm bớt yêu cầu hãy **luôn tự đặt câu hỏi & suy nghĩ cẩn thận** những mục dưới đây: 

1.  Chỉnh sửa đó có giúp ích gì cho AI để nó dịch tốt hơn so với `prompt mẫu` & `system instructions mẫu` không? 
2.  Nó có giúp AI hiểu đúng ngữ cảnh, đúng đối tượng, đúng phong cách dịch hơn không? 
3.  Nó có nhắc nhở cho AI biết các từ, cụm từ hoặc thuật ngữ chuyên ngành khó dịch mà cần phải lưu ý & nhất quán trong toàn bộ bản dịch không?
4.  (**QUAN TRỌNG NHẤT**) Liệu nó có giúp AI tạo ra bản dịch **CHUẨN XÁC** (accuracy and fidelity) và **TRÔI CHẢY** (natural-sounding translation) hơn không?
5.  **Nguyên tắc ưu tiên khi chỉnh sửa:** Luôn ghi nhớ rằng CHUẨN XÁC là ưu tiên quan trọng nhất, TRÔI CHẢY là ưu tiên quan trọng thứ hai, rồi mới đến các lựa chọn khác.  

---
**Định dạng Output:** TUÂN THỦ nghiêm ngặt cấu trúc output như dưới đây.
1.  Đầu tiên, trả về toàn bộ nội dung của `prompt mới`.
2.  Tiếp theo, trên một dòng riêng biệt, chỉ chứa chuỗi sau: `<<<SYSTEM_INSTRUCTION_SEPARATOR>>>`
3.  Cuối cùng, trả về toàn bộ nội dung của `system instruction mới`.
4.  KHÔNG thêm bất kỳ lời giải thích hay văn bản nào khác.

Nhắc lại lần nữa, output PHẢI có cấu trúc như sau:
Toàn bộ nội dung của `prompt mới`
<<<SYSTEM_INSTRUCTION_SEPARATOR>>>
Toàn bộ nội dung của `system instruction mới`
';

    // 3. Chuẩn bị Payload (Không đổi)
    // Đang loại bỏ //'topP' => $topP, [chỉ để $temperature kiểm soát chất lượng dịch]
    // Viết lại SI & prompt nên ứng dụng để temperature rất thấp nhằm tăng độ chính xác
    if (defined('SEARCH_ENGINE_GEMINI') && SEARCH_ENGINE_GEMINI) { // Sử dụng Google Search   
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
                'temperature' => 0.3,
                'responseMimeType' => 'text/plain'
            ],
            'tools' => [ 
                [
                  'googleSearch' => (object)[]
                ]
            ]
        ];
    }
    else { // Không cần dùng đến Google Search
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
                'temperature' => 0.3,
                'responseMimeType' => 'text/plain'
            ]
        ];
    }    

    $payload = json_encode($payload_data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Lỗi khi tạo JSON payload cho API Gemini: " . json_last_error_msg() . " - Input: " . substr($cleanedText, 0, 100));
        return null; // Trả về null khi có lỗi payload
    }

    // 4. Thực hiện cURL Request (Không đổi)
    $ch = curl_init();
    // Đảm bảo GEMINI_API_URL và CURL_TIMEOUT_API được định nghĩa
    if (!defined('GEMINI_API_URL')) {
         error_log("Lỗi: Hằng số GEMINI_API_URL chưa được định nghĩa.");
         return null; // Trả về null
    }
    curl_setopt($ch, CURLOPT_URL, GEMINI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, defined('CURL_TIMEOUT_API') ? CURL_TIMEOUT_API + 30 : 900);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    
    curl_close($ch);

    // 5. Xử lý Response và Lỗi (Cập nhật giá trị trả về khi lỗi)
    if ($errno) {
        error_log("Lỗi cURL khi gọi API Gemini (Code $errno): " . $error . " - Input: " . substr($cleanedText, 0, 100));
        return null; // Trả về null khi có lỗi cURL
    }

    $responseData = json_decode($response, true); // Decode response gốc của API

    if ($httpCode >= 400) {
        $errorMessage = "Lỗi API Gemini (HTTP $httpCode).";
        if (isset($responseData['error']['message'])) {
            $errorMessage .= " Message: " . $responseData['error']['message'];
        } elseif (!empty($response)) {
             $errorMessage .= " Raw Response: " . substr(htmlspecialchars($response), 0, 200) . "...";
        }
        error_log($errorMessage . " - Input: " . substr($cleanedText, 0, 100));
        return null; // Trả về null khi có lỗi API
    }

    // 6. Trích xuất, Decode JSON nội dung và trả về Mảng PHP
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $outputString = $responseData['candidates'][0]['content']['parts'][0]['text'];

        // Tách kết quả trả về
        $partsPS = explode("<<<SYSTEM_INSTRUCTION_SEPARATOR>>>", $outputString, 2);

        // **** THÊM KIỂM TRA NÀY ****
        if (is_array($partsPS) && count($partsPS) === 2) {
            // Gán chỉ khi có đủ 2 phần
            $newPrompt = trim($partsPS[0]);
            $newSI = trim($partsPS[1]);

            // (Optional) Kiểm tra thêm xem các phần có rỗng không nếu cần
            if (!empty($newPrompt) && !empty($newSI)) {
                 // Thành công, trả về mảng PHP
                 return ['new_prompt' => $newPrompt, 'new_system_instruction' => $newSI];
            } else {
                 error_log("API Gemini trả về nội dung nhưng prompt hoặc SI trống sau khi tách. Raw Output: " . substr($outputString, 0, 200));
                 return null;
            }

        } else {
            // Ghi log lỗi khi không tìm thấy delimiter hoặc chỉ có 1 phần
            error_log("API Gemini trả về nội dung nhưng không chứa delimiter '<<<SYSTEM_INSTRUCTION_SEPARATOR>>>' hợp lệ. Raw Output: " . substr($outputString, 0, 200));
            return null; // Trả về null khi không tách được
        }
        // **** KẾT THÚC KIỂM TRA ****    

    } elseif (isset($responseData['candidates'][0]['finishReason']) && $responseData['candidates'][0]['finishReason'] !== 'STOP') {
         error_log("API Gemini dừng không thành công. Lý do: " . $responseData['candidates'][0]['finishReason'] . " - Input: " . substr($cleanedText, 0, 100));
         return null; // Trả về null nếu bị dừng không mong muốn
    }
    else {
        error_log("Cấu trúc response Gemini không mong đợi hoặc bị trống. Response: " . $response . " - Input: " . substr($cleanedText, 0, 100));
        return null; // Trả về null nếu cấu trúc response lạ
    }
}
