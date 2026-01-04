<?php
// --- CẤU HÌNH ---
// Đảm bảo file config.php tồn tại và đúng đường dẫn
// Viết đường dẫn kiểu này để dự phòng chương trình chạy đa nền tảng, mặc dù hiện tại chỉ hướng đến người dùng Windows
$config_Path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'myself' . DIRECTORY_SEPARATOR . 'config.php'; // Đường dẫn quan trọng của file config.php

if (file_exists($config_Path)) {
    require_once $config_Path;
} else {
    // Hoặc dừng thực thi và báo lỗi nghiêm trọng
    die("Lỗi nghiêm trọng: Không tìm thấy file cấu hình config.php trong myself!");
}

$user_question = '';
$ai_answer = '';
$error_message = '';

// --- XỬ LÝ FORM KHI GỬI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['question'])) {
        $user_question = trim($_POST['question']);

        // Chuẩn bị dữ liệu gửi đến API
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $user_question]
                    ]
                ]
            ],
            'generationConfig' => [
               'temperature' => 1,
               'topP' => 0.95,
               'maxOutputTokens' => 1000,
            ],
            'tools' => [
                [
                    'googleSearch' => (object)[]
                ]
            ]
        ];
        
        $json_data = json_encode($data);

        // Khởi tạo cURL
        $ch = curl_init(GEMINI_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, defined('CURL_TIMEOUT_API') ? CURL_TIMEOUT_API + 30 : 900); // Tăng timeout cho API call
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bỏ comment nếu gặp lỗi SSL trên localhost

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error_message = 'Lỗi cURL: ' . curl_error($ch);
        } else {
            if ($http_code == 200) {
                $decoded_response = json_decode($response, true);
                if (isset($decoded_response['candidates'][0]['content']['parts'][0]['text'])) {
                    $ai_answer = $decoded_response['candidates'][0]['content']['parts'][0]['text'];
                } elseif (isset($decoded_response['error']['message'])) {
                     $error_message = 'Lỗi API Gemini: ' . $decoded_response['error']['message'];
                } else {
                    $error_message = 'Không thể phân tích phản hồi từ Gemini. Phản hồi thô: ' . htmlspecialchars($response);
                }
            } else {
                 $error_message = "Lỗi HTTP {$http_code} từ API Gemini. Phản hồi: " . htmlspecialchars($response);
            }
        }
        curl_close($ch);
    } else {
        $error_message = 'Vui lòng nhập câu hỏi.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hỏi AI Gemini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">     
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .card {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 10px;
        }
        .card-header {
            background-color: #0d6efd; /* Bootstrap primary blue */
            color: white;
            font-size: 1.5rem;
            font-weight: 500;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
        }
        .card-header i {
            margin-right: 10px;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 0.5rem 1.5rem;
            font-size: 1rem;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .response-area {
            margin-top: 25px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .response-area h5 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .response-area pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap; /* Giữ nguyên định dạng xuống dòng */
            word-wrap: break-word; /* Tự động ngắt từ dài */
            font-size: 0.95rem;
            color: #212529;
        }
        .user-question {
            color: #555;
            font-style: italic;
            margin-bottom: 10px;
        }
         .alert-custom {
            border-left: 5px solid;
        }
        .alert-custom.alert-info { border-left-color: #0dcaf0; }
        .alert-custom.alert-success { border-left-color: #198754; }
        .alert-custom.alert-danger { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-robot"></i> Trò chuyện với AI Gemini
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="question" class="form-label">Nhập câu hỏi của bạn:</label>
                        <textarea class="form-control" id="question" name="question" rows="4" required placeholder="Ví dụ: PHP là gì?"><?php echo htmlspecialchars($user_question); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-paper-plane"></i> Gửi câu hỏi
                    </button>
                </form>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-custom mt-4" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($ai_answer && !$error_message): ?>
                    <div class="response-area mt-4">
                        <div class="alert alert-info alert-custom" role="alert">
                            <h5 class="alert-heading"><i class="fas fa-question-circle"></i> Câu hỏi của bạn:</h5>
                            <p class="user-question mb-0"><?php echo htmlspecialchars($user_question); ?></p>
                        </div>

                        <div class="alert alert-success alert-custom mt-3" role="alert">
                            <h5 class="alert-heading"><i class="fas fa-lightbulb"></i> Gemini trả lời:</h5>
                            <pre><?php echo $ai_answer; // nl2br(htmlspecialchars($ai_answer)); ?></pre>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted text-center small py-3">
                Powered by Google Gemini & PHP
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>