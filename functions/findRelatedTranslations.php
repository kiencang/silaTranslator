<?php

/**
 * Tìm các bản dịch liên quan từ cùng tên miền trong file JSON, giới hạn số lượng kết quả.
 *
 * @param string $originalUrl URL của bài viết gốc tiếng Anh.
 * @param string $jsonFilePath Đường dẫn đến file JSON chứa bản ghi dịch thuật.
 * @param int $limit Số lượng kết quả tối đa muốn trả về. Mặc định là 5.
 * @param bool $mostRecent Nếu true, sẽ tìm tất cả, sắp xếp và trả về $limit bài gần nhất.
 *                        Nếu false (mặc định), trả về $limit bài đầu tiên tìm thấy.
 * @return array|false Mảng chứa thông tin các bài viết liên quan (translated_title, translated_url, timestamp)
 *                     hoặc false nếu không tìm thấy hoặc có lỗi.
 */
function findRelatedTranslations(
    string $originalUrl,
    string $jsonFilePath = 'translationRecord.json',
    int $limit = 5,
    bool $mostRecent = false
): array|false {
    // --- 1. Phân tích URL đầu vào để lấy tên miền ---
    $parsedUrl = parse_url($originalUrl);
    if ($parsedUrl === false || !isset($parsedUrl['host'])) {
        error_log("findRelatedTranslations: Invalid input URL: " . $originalUrl);
        return false;
    }
    $inputDomain = $parsedUrl['host'];
    $inputDomain = preg_replace('/^www\./i', '', $inputDomain); // Chuẩn hóa

    // --- 2. Đọc và giải mã file JSON ---
    if (!file_exists($jsonFilePath)) {
        error_log("findRelatedTranslations: JSON file not found: " . $jsonFilePath);
        return false;
    }
    $jsonData = @file_get_contents($jsonFilePath);
    if ($jsonData === false) {
        error_log("findRelatedTranslations: Could not read JSON file: " . $jsonFilePath);
        return false;
    }
    $translations = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("findRelatedTranslations: Error decoding JSON: " . json_last_error_msg());
        return false;
    }
     if (!is_array($translations)) {
         error_log("findRelatedTranslations: Invalid JSON structure. Expected an array.");
         return false;
     }

    // --- 3. Tìm kiếm các bản ghi liên quan ---
    $relatedTranslations = [];
    $count = 0; // Biến đếm số lượng kết quả đã tìm thấy

    foreach ($translations as $entry) {
        if (!isset($entry['original_url'], $entry['translated_url'], $entry['translated_title'], $entry['timestamp'])) {
            continue; // Bỏ qua entry không hợp lệ
        }

        $entryParsedUrl = parse_url($entry['original_url']);
        if ($entryParsedUrl === false || !isset($entryParsedUrl['host'])) {
            continue; // Bỏ qua entry có original_url không hợp lệ
        }
        $entryDomain = $entryParsedUrl['host'];
        $entryDomain = preg_replace('/^www\./i', '', $entryDomain); // Chuẩn hóa

        // So sánh tên miền VÀ không phải là chính URL đầu vào
        if ($entryDomain === $inputDomain && $entry['original_url'] !== $originalUrl) {
            $relatedTranslations[] = [
                'translated_title' => $entry['translated_title'],
                'translated_url'   => $entry['translated_url'],
                'timestamp'        => $entry['timestamp']
                // 'original_url' => $entry['original_url'] // Thêm nếu cần
            ];

            // Nếu không cần tìm bài gần nhất, dừng sớm khi đủ limit
            if (!$mostRecent) {
                $count++;
                if ($count >= $limit) {
                    break; // Đã đủ số lượng, thoát vòng lặp
                }
            }
        }
    }

    // --- 4. Xử lý kết quả cuối cùng ---

    // Nếu không tìm thấy gì
    if (empty($relatedTranslations)) {
        return false;
    }

    // Nếu yêu cầu bài gần nhất, sắp xếp và lấy $limit bài
    if ($mostRecent) {
        // Sắp xếp mảng theo timestamp giảm dần (mới nhất trước)
        usort($relatedTranslations, function($a, $b) {
            // So sánh timestamp, $b trước $a để sắp xếp giảm dần
            return $b['timestamp'] <=> $a['timestamp']; // PHP 7+ spaceship operator
            // Hoặc cho PHP < 7:
            // if ($a['timestamp'] == $b['timestamp']) { return 0; }
            // return ($a['timestamp'] < $b['timestamp']) ? 1 : -1;
        });

        // Chỉ lấy số lượng $limit phần tử đầu tiên sau khi sắp xếp
        return array_slice($relatedTranslations, 0, $limit);
    } else {
        // Trả về các kết quả đã tìm thấy (tối đa $limit do break ở trên)
        return $relatedTranslations;
    }
}


$inputUrl = 'https://theconversation.com/some-new-article-link-123456';
$limit = 2; // Chỉ muốn lấy tối đa 3 bài

echo "--- Lấy tối đa {$limit} bài liên quan đầu tiên tìm thấy ---<br>";
$relatedArticlesFirstN = findRelatedTranslations($inputUrl, 'translationRecord.json', $limit, false); // $mostRecent = false

if ($relatedArticlesFirstN) {
    echo "Tìm thấy " . count($relatedArticlesFirstN) . " bài viết:<br>";
    foreach ($relatedArticlesFirstN as $article) {
        echo "  - Tiêu đề: " . $article['translated_title'] . "<br>";
        echo "    URL Dịch: " . $article['translated_url'] . "<br>";
        echo "    Thời gian: " . date('Y-m-d H:i:s', $article['timestamp']) . "<br>";
    }
} else {
    echo "Không tìm thấy bài viết liên quan nào.<br>";
}

echo "\n--- Lấy tối đa {$limit} bài liên quan MỚI NHẤT ---<br>";
$relatedArticlesMostRecent = findRelatedTranslations($inputUrl, 'translationRecord.json', $limit, true); // $mostRecent = true

if ($relatedArticlesMostRecent) {
    echo "Tìm thấy " . count($relatedArticlesMostRecent) . " bài viết:<br>";
    foreach ($relatedArticlesMostRecent as $article) {
        echo "  - Tiêu đề: " . $article['translated_title'] . " (Timestamp: " . $article['timestamp'] . ")<br>";
        // echo "    URL Dịch: " . $article['translated_url'] . "\n";
        // echo "    Thời gian: " . date('Y-m-d H:i:s', $article['timestamp']) . "\n";
    }
} else {
    echo "Không tìm thấy bài viết liên quan nào.\n";
}

