<?php

/**
 * Gọi API Gemini để dịch query
 * @return string Văn bản đã dịch.
 * @throws Exception Nếu có lỗi khi gọi API.
 */
function callGeminiApiforSearch(string $querySearch, string $lang = 'en'): string {
// Dọn dẹp khoảng trắng trước và sau chuỗi để dữ liệu sạch hơn
$querySearchX = trim($querySearch);

$systemInstruction = 'Bạn là một AI chuyên dịch truy vấn tìm kiếm (search queries) từ tiếng Việt sang tiếng Anh. Nhiệm vụ DUY NHẤT của bạn là trả về MỘT (1) truy vấn tìm kiếm tiếng Anh hiệu quả nhất, dựa trên đánh giá của bạn về ý định (search intent) và cách tìm kiếm phổ biến nhất trong tiếng Anh.

QUY TẮC BẮT BUỘC TUÂN THỦ:
1.  **CHỈ MỘT KẾT QUẢ:** Luôn luôn và chỉ luôn trả về DUY NHẤT MỘT chuỗi văn bản là bản dịch truy vấn tốt nhất. KHÔNG được đưa ra nhiều lựa chọn.
2.  **CHỈ VĂN BẢN THUẦN TÚY:** Kết quả trả về CHỈ BAO GỒM văn bản tiếng Anh đã dịch. TUYỆT ĐỐI KHÔNG thêm bất kỳ lời chào, lời giải thích, ghi chú, dấu ngoặc kép bao quanh, định dạng markdown, hoặc bất kỳ ký tự/từ ngữ nào khác ngoài chính truy vấn đã dịch.
3.  **ƯU TIÊN HIỆU QUẢ TÌM KIẾM:** Mục tiêu là tạo ra truy vấn mà người dùng tiếng Anh thực sự sẽ gõ vào máy tìm kiếm. Ưu tiên từ khóa cốt lõi, ý định, sự ngắn gọn, và các cụm từ tìm kiếm phổ biến (how to, best, near me, price, review, etc.).
4.  **ĐỘ CHÍNH XÁC VỀ Ý ĐỊNH:** Nắm bắt chính xác nhất ý định đằng sau truy vấn gốc tiếng Việt. Nếu mơ hồ, hãy chọn cách diễn giải phổ biến hoặc khả năng cao nhất.
5.  **ĐỊNH DẠNG ĐẦU RA:** Đảm bảo đầu ra là một chuỗi văn bản thuần túy (plain text string) duy nhất, sẵn sàng để sao chép và dán trực tiếp vào thanh tìm kiếm.';

$prompt = 'Provide the single best English search query translation for the following Vietnamese query. Output ONLY the raw English text, nothing else:
['.$querySearchX.']';


// Payload cần chuẩn xác theo tài liệu Gemini
// Các thông tin thiết lập là chung cho cả 2 kiểu dịch
// Đang loại bỏ //'topP' => $topP, [chỉ để $temperature kiểm soát chất lượng dịch]
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
    
    $payload = json_encode($payload_data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Lỗi khi tạo JSON payload cho API: " . json_last_error_msg());
    }


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GEMINI_API_URL_SEARCH); // Đảm bảo GEMINI_API_URL_SEARCH được định nghĩa đúng
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, defined('CURL_TIMEOUT_API_SEARCH') ? CURL_TIMEOUT_API_SEARCH + 30 : 60); // Tăng timeout cho API call
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno) {
        throw new Exception("Lỗi cURL khi gọi API Gemini (Code $errno): " . $error);
    }

    $responseData = json_decode($response, true);

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
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
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

