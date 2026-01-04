<?php
// File: include/rss_display_v3.php



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
/**
 * Hiển thị RSS Feed (Đồng bộ Fetch RSS, Async Dịch với Guzzle & Gemini API)
 * Lấy dữ liệu từ cache nếu hợp lệ, fetch mới nếu cần.
 * Nếu cần fetch mới và dịch, các yêu cầu dịch sẽ được gửi đồng loạt bằng Guzzle.
 * Trang sẽ đợi tất cả yêu cầu dịch hoàn tất trước khi hiển thị.
 *
 * Yêu cầu: Composer, GuzzleHttp, PHP 7.4+ (khuyến nghị 8.0+)
 * Cấu hình: ../myself/rss_config.php (đường dẫn có thể thay đổi)
 */
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Error Reporting (Khuyến nghị khi phát triển) ---
error_reporting(E_ALL);
ini_set('display_errors', 1); // Tắt cái này trên production
ini_set('log_errors', 1); // Bật log lỗi trên production

// ini_set('error_log', __DIR__ . '/php_error.log'); // Đường dẫn file log
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Sử dụng các lớp cần thiết từ Guzzle ---
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Phần Hàm Hỗ Trợ ---
if (!function_exists('limit_words_rss')) {
    function limit_words_rss($string, $word_limit) {
         $cleaned_string = strip_tags(html_entity_decode($string ?? ''));
         $words = preg_split("/\s+/", $cleaned_string);
         // Lọc bỏ các phần tử rỗng có thể xuất hiện do nhiều khoảng trắng liên tiếp
         $words = array_filter($words, function($word) { return trim($word) !== ''; });
         if (count($words) > $word_limit) {
             return implode(" ", array_slice($words, 0, $word_limit)) . '...';
         }
         // Trả về chuỗi đã được nối lại từ các từ hợp lệ
         return implode(" ", $words);
    }
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
define('RSS_GEMINI_API_KEY', GEMINI_API_KEY); // Gán RSS_GEMINI_API_KEY vào GEMINI_API_KEY
define('CACHE_DIR_RSS', __DIR__ . '/cache_rss'); // Định nghĩa nơi lưu trữ cache RSS
define('CACHE_EXPIRE_RSS', (defined('RSS_CACHE_DURATION') ? intval(RSS_CACHE_DURATION) : 6) * 3600); // Tính bằng giây // Định nghĩa thời gian cache

// --- Kiểm tra và include config nếu chưa có ---
$config_loaded = true; // Config đã được load từ trước (có thể do include ở file khác)

// --- Hết phần Config ---

// --- Kiểm tra cấu hình cần thiết cho Dịch (nếu bật) ---
$should_translate = (defined('RSS_TRANSLATE_CONTENT') && RSS_TRANSLATE_CONTENT === true);
$translation_possible = true; // Khả năng có thể dịch hay không, mặc định là có
$translation_error_config = '';
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
if ($should_translate) {
    if (!class_exists(Client::class)) {
         $translation_error_config .= "<p class='error'>Lỗi cấu hình: Tính năng dịch được bật nhưng thư viện GuzzleHttp không khả dụng (class 'GuzzleHttp\Client' not found).</p>";
         $translation_possible = false;
    }
    
    // Kiểm tra Endpoint, cùng cấu trúc với dịch nội dung
    if (!defined('RSS_GEMINI_MODEL_ENDPOINT') || empty(RSS_GEMINI_MODEL_ENDPOINT) || !filter_var(RSS_GEMINI_MODEL_ENDPOINT, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
        // Kiểm tra URL hợp lệ và phải có path (không chỉ là domain)
        $translation_error_config .= "<p class='error'>Lỗi cấu hình: 'RSS_GEMINI_MODEL_ENDPOINT' không được định nghĩa, trống hoặc không phải URL hợp lệ cho model API trong config.</p>";
        $translation_possible = false;
    }
    
    if (!defined('RSS_GEMINI_API_KEY') || empty(RSS_GEMINI_API_KEY)) { // Trùng với khóa dịch nội dung nói chung
        $translation_error_config .= "<p class='error'>Lỗi cấu hình: 'RSS_GEMINI_API_KEY' không được định nghĩa hoặc trống trong config.</p>";
        $translation_possible = false;
    }
    
    if (!defined('RSS_TARGET_LANGUAGE') || empty(RSS_TARGET_LANGUAGE)) { // Để biết dịch sang ngôn ngữ nào, và để cache
        $translation_error_config .= "<p class='error'>Lỗi cấu hình: 'RSS_TARGET_LANGUAGE' không được định nghĩa hoặc trống trong config.</p>";
        $translation_possible = false;
    }

    // Kiểm tra cuối xem có dịch được hay không
    if (!$translation_possible) {
        echo $translation_error_config; // Hiển thị tất cả lỗi cấu hình dịch
        $should_translate = false; // Tắt dịch nếu cấu hình lỗi
        echo "<p class='warning'>Cảnh báo: Đã tắt tính năng dịch do lỗi cấu hình.</p>";
    }
}
// --- Hết phần Kiểm tra cấu hình Dịch ---
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Phần Cấu hình Cache ---
if (!defined('CACHE_DIR_RSS')) {
    define('CACHE_DIR_RSS', __DIR__ . '/cache_rss');
}

if (!defined('CACHE_EXPIRE_RSS')) {
    define('CACHE_EXPIRE_RSS', (defined('RSS_CACHE_DURATION') ? intval(RSS_CACHE_DURATION) : 6) * 3600); // Tính bằng giây
}
// --- Hết phần Cache ---
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Khởi tạo biến ---
$rss_url = defined('RSS_LINK') ? RSS_LINK : '';
$items_data = null;
$error_message = ''; // Sử dụng chuỗi để nối các lỗi/cảnh báo

// --- Bắt đầu Logic Chính ---

// 1. Kiểm tra URL RSS
if (empty($rss_url)) {
    if ($config_loaded) { // Chỉ báo lỗi nếu config đã load mà URL vẫn trống
       $error_message .= "<p class='error'>Lỗi: URL RSS ('RSS_LINK') chưa được cấu hình.</p>";
    }
    // Nếu config không load được, lỗi đã báo ở trên, không cần báo lại
} elseif (!filter_var($rss_url, FILTER_VALIDATE_URL)) {
    $error_message .= "<p class='error'>Lỗi: URL RSS được cung cấp không hợp lệ.</p>";
} else {
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------    
   
    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // 2. Tạo tên file cache (bao gồm trạng thái dịch)
    $cache_key_suffix = $should_translate ? '_translated_' . strtolower(str_replace(' ', '_', RSS_TARGET_LANGUAGE)) : '';
    $cache_key = md5($rss_url . $cache_key_suffix);
    $cache_file = CACHE_DIR_RSS . '/' . $cache_key . '.cache';
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
    


// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // 3. Kiểm tra thư mục cache
    $cache_dir_ok = false;
    if (!is_dir(CACHE_DIR_RSS)) {
        if (!@mkdir(CACHE_DIR_RSS, 0755, true)) {
            $error_message .= "<p class='error'>Lỗi: Không thể tạo thư mục cache tại '" . htmlspecialchars(CACHE_DIR_RSS) . "'. Vui lòng kiểm tra quyền ghi của thư mục cha.</p>";
        } else {
            $cache_dir_ok = true;
        }
    } elseif (!is_writable(CACHE_DIR_RSS)) {
        $error_message .= "<p class='error'>Lỗi: Thư mục cache '" . htmlspecialchars(CACHE_DIR_RSS) . "' không có quyền ghi.</p>";
    } else {
         $cache_dir_ok = true;
    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
    


// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // 4. Kiểm tra cache file nếu thư mục OK
    if ($cache_dir_ok && empty($error_message)) { // Chỉ đọc cache nếu không có lỗi nghiêm trọng trước đó
        if (file_exists($cache_file) && (time() - @filemtime($cache_file)) < CACHE_EXPIRE_RSS) {
            $cached_content = @file_get_contents($cache_file);
            if ($cached_content !== false) {
                // Dùng @ để tránh lỗi unserialize nếu dữ liệu cache không hợp lệ
                $items_data = @unserialize($cached_content);
                if ($items_data === false || !is_array($items_data)) {
                    $error_message .= "<p class='warning'>Cảnh báo: Dữ liệu cache bị hỏng hoặc không hợp lệ. Đang tải lại...</p>";
                    $items_data = null;
                    @unlink($cache_file); // Xóa cache hỏng
                } else {
                     // error_log("RSS: Loaded from cache: " . $cache_file);
                }
            } else {
                 $error_message .= "<p class='warning'>Cảnh báo: Không thể đọc file cache '" . htmlspecialchars($cache_file) . "' dù nó tồn tại. Kiểm tra quyền đọc.</p>";
                 $items_data = null; // Fetch mới
            }
        } else {
            // Cache không tồn tại hoặc đã hết hạn
            // error_log("RSS: Cache expired or not found: " . $cache_file);
            $items_data = null; // Đảm bảo sẽ fetch mới
        }
    }
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
   
    

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // 5. Fetch mới nếu cần (chưa có lỗi nghiêm trọng và chưa có dữ liệu từ cache)
    if (empty($error_message) && $items_data === null) {
        // error_log("RSS: Fetching new data for: " . $rss_url);

        // Cấu hình context cho file_get_contents (chỉ để lấy RSS feed)
        $contextOptions = [
            'http' => [
                'method' => "GET", 'timeout' => 15, 'ignore_errors' => true,
                'header' => "User-Agent: RSSFetcherGuzzle/1.1\r\nAccept: application/xml, text/xml;q=0.9, */*;q=0.8\r\n"
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false], // Bảo mật hơn, có thể cần CA cert bundle
        ];
        // Tắt verify SSL nếu server không cấu hình đúng (KHÔNG KHUYẾN KHÍCH)
        // $contextOptions['ssl'] = ['verify_peer' => false, 'verify_peer_name' => false];

        $context = stream_context_create($contextOptions);
        $xml_content = @file_get_contents($rss_url, false, $context);
        $http_status = $http_response_header ?? []; // Dùng ?? cho PHP 7.0+
        $status_line = $http_status[0] ?? 'HTTP/1.1 0 Connection Error'; // Mặc định lỗi kết nối

        // Phân tích status code từ status line
        $status_code = 0;
        if (preg_match('{HTTP/\d\.\d\s+(\d{3})}', $status_line, $match)) {
            $status_code = intval($match[1]);
        }

        if ($xml_content === false || $status_code !== 200) {
             if ($status_code === 404) { $error_message .= "<p class='error'>Lỗi Fetch RSS: Không tìm thấy nguồn cấp (404 Not Found).</p>"; }
             elseif ($status_code === 403) { $error_message .= "<p class='error'>Lỗi Fetch RSS: Bị từ chối truy cập (403 Forbidden).</p>"; }
             elseif ($status_code >= 500) { $error_message .= "<p class='error'>Lỗi Fetch RSS: Máy chủ nguồn gặp sự cố ({$status_code}).</p>"; }
             elseif ($xml_content === false && $status_code === 0) { $error_message .= "<p class='error'>Lỗi Fetch RSS: Không thể kết nối đến máy chủ hoặc URL không hợp lệ.</p>"; }
             else { $error_message .= "<p class='error'>Lỗi Fetch RSS: Không thể tải dữ liệu. Mã lỗi HTTP: {$status_code}.</p>"; }
             error_log("RSS Fetch Error: HTTP {$status_code} for {$rss_url}. Status line: {$status_line}");
        } else {
            // Fetch thành công, xử lý XML
            libxml_use_internal_errors(true);
            $rss = simplexml_load_string($xml_content);
            $xml_errors = libxml_get_errors();
            libxml_clear_errors();

            if ($rss === false) {
                $error_message .= "<p class='error'>Lỗi: Không thể phân tích cú pháp XML của RSS. Định dạng có thể không hợp lệ.</p>";
                 if (!empty($xml_errors)) { foreach ($xml_errors as $xml_error) { error_log("RSS XML Parse Error: " . trim($xml_error->message) . " on line " . $xml_error->line); } }
            } elseif (!isset($rss->channel->item) || count($rss->channel->item) === 0) {
                 // XML hợp lệ nhưng không có item
                 $error_message .= "<p class='info'>Nguồn cấp RSS hợp lệ nhưng không tìm thấy tin bài nào.</p>";
                 // Vẫn tạo mảng rỗng để cache kết quả "không có gì"
                 $items_data = [];
            } else {
                // --- Trích xuất dữ liệu gốc ---
                $items = $rss->channel->item;
                $extracted_items = [];
                $item_count = 0;
                $max_items = 10; // Giới hạn số lượng tin lấy

                foreach ($items as $item) {
                    if ($item_count >= $max_items) break;

                    // Sử dụng namespace nếu có (ví dụ: dc:creator)
                    $namespaces = $item->getNamespaces(true);
                    // $dc = $item->children($namespaces['dc'] ?? null); // Ví dụ

                    $title_original = trim((string)($item->title ?? ''));
                    $link = trim((string)($item->link ?? ''));
                    $description_original_raw = trim((string)($item->description ?? ''));
                    $pubDate_raw = trim((string)($item->pubDate ?? ''));

                    // Cố gắng lấy tên nguồn
                    $source_name = 'N/A';
                    if (isset($item->source)) {
                         $source_attributes = $item->source->attributes();
                         if (isset($source_attributes['url'])) {
                             $source_url_parts = parse_url((string)$source_attributes['url']);
                             if(isset($source_url_parts['host'])) {
                                 $source_name = $source_url_parts['host'];
                                 $source_name = preg_replace('/^www\./i', '', $source_name); // Bỏ www.
                             } else { $source_name = trim((string)$item->source); } // Lấy text nếu không parse được host
                         } else { $source_name = trim((string)$item->source); } // Lấy text nếu không có attribute url
                    } elseif(isset($rss->channel->title) && !empty(trim((string)$rss->channel->title))) {
                         // Lấy title kênh làm fallback
                         $source_name = trim((string)$rss->channel->title);
                    }
                     // Ưu tiên lấy tên nguồn từ config nếu có
                    if (defined('RSS_LINK_NAME') && !empty(RSS_LINK_NAME) && RSS_LINK_NAME !== 'RSS Feed') {
                         $source_name = RSS_LINK_NAME;
                    }

                    // Chỉ thêm item nếu có tiêu đề hoặc mô tả
                    if (!empty($title_original) || !empty($description_original_raw)) {
                        $extracted_items[] = [
                            'title' => $title_original,
                            'link' => $link,
                            'description_raw' => $description_original_raw,
                            'source_name' => $source_name,
                            'pubDate_raw' => $pubDate_raw,
                            'title_translated' => null,
                            'description_translated' => null
                        ];
                        $item_count++;
                    }
                } // Hết vòng lặp foreach item

                // Nếu không trích xuất được item nào (do thiếu title/desc)
                if (empty($extracted_items)) {
                     $error_message .= "<p class='info'>Đã phân tích RSS thành công nhưng không tìm thấy tiêu đề hoặc mô tả trong các tin bài.</p>";
                     $items_data = []; // Cache kết quả rỗng
                }
                // --- Hết phần Trích xuất dữ liệu gốc ---

                $api_key_invalid = false; // Cờ để kiểm tra lỗi API Key                
                
                // --- Phần Dịch Thuật Đồng Loạt (nếu cần và có item để dịch) ---
                if ($should_translate && !empty($extracted_items)) {
                    error_log("RSS: Starting concurrent translation for " . count($extracted_items) . " items to " . RSS_TARGET_LANGUAGE);
                    $client = new Client([
                        'timeout'  => 90.0, // Tăng timeout cho API dịch
                        'connect_timeout' => 10.0,
                        'headers' => [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ]
                    ]);

                    $promises = [];
                    $requestUrl = RSS_GEMINI_MODEL_ENDPOINT . ':generateContent?key=' . RSS_GEMINI_API_KEY;
                    $targetLanguage = RSS_TARGET_LANGUAGE;
                    $translatePromptPrefix = "Bạn là một chuyên gia dịch thuật, chuyên dịch các tiêu đề và mô tả ngắn từ các nguồn cấp tin tức RSS. Hãy dịch đoạn văn bản dưới đây sang tiếng Việt sao cho thật chính xác, giữ nguyên ý nghĩa gốc và văn phong tự nhiên, phù hợp với tin tức. Quan trọng: Chỉ cung cấp duy nhất nội dung bản dịch tiếng Việt, không kèm theo bất kỳ thông tin nào khác, kể cả dấu ngoặc kép bao quanh nếu không có trong ý nghĩa gốc. Dịch đoạn văn bản sau sang tiếng Việt:\n---\n";
                    $translatePromptSuffix = "\n---";
                    $max_desc_chars = 500; // Giới hạn ký tự gửi đi dịch

                    // Chuẩn bị các promises
                    foreach ($extracted_items as $index => $item) {
                        // Tạo payload chung
                        $createPayload = function($text) use ($translatePromptPrefix, $translatePromptSuffix) {
                             if (empty(trim($text))) return null;
                             return [
                                'contents' => [['parts' => [['text' => $translatePromptPrefix . $text . $translatePromptSuffix]]]],
                                // Thêm generationConfig nếu muốn kiểm soát output tốt hơn
                                // 'generationConfig' => [ 'temperature' => 0.7, 'maxOutputTokens' => 200, 'stopSequences' => ["\nTranslation:"] ]
                             ];
                        };

                        // Promise cho tiêu đề
                        $payloadTitle = $createPayload($item['title']);
                        if ($payloadTitle) {
                            $promises["{$index}_title"] = $client->postAsync($requestUrl, ['json' => $payloadTitle]);
                        }

                        // Promise cho mô tả (làm sạch, giới hạn)
                        $cleaned_desc = trim(strip_tags($item['description_raw']));
                        $desc_to_translate = $cleaned_desc;
                        if (mb_strlen($cleaned_desc, 'UTF-8') > $max_desc_chars) {
                            $desc_to_translate = mb_substr($cleaned_desc, 0, $max_desc_chars, 'UTF-8') . '...';
                        }
                        $payloadDesc = $createPayload($desc_to_translate);
                         if ($payloadDesc) {
                             $promises["{$index}_description"] = $client->postAsync($requestUrl, ['json' => $payloadDesc]);
                         }
                    } // end foreach chuẩn bị promises

                    // Gửi đồng loạt và đợi kết quả nếu có promises
                    if (!empty($promises)) {
                        try {
                            $results = Promise\Utils::settle($promises)->wait();
                            $translation_errors = 0;

                            // Xử lý kết quả
                            foreach ($results as $key => $result) {
                                list($index, $field) = explode('_', $key, 2);
                                $index = (int)$index;

                                if ($result['state'] === 'fulfilled') {
                                    try {
                                        /** @var \Psr\Http\Message\ResponseInterface $response */
                                        $response = $result['value'];
                                        $responseBody = $response->getBody()->getContents();
                                        $responseData = json_decode($responseBody, true);

                                        // Trích xuất text từ Gemini response
                                        $translation = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

                                        // Kiểm tra các trường hợp lỗi hoặc không có nội dung từ API
                                        if (isset($responseData['promptFeedback']['blockReason'])) {
                                             $reason = $responseData['promptFeedback']['blockReason'];
                                             error_log("RSS: Translation blocked for item $index, field $field. Reason: " . $reason);
                                             $translation_errors++;
                                        } elseif ($translation !== null) {
                                            // Làm sạch kết quả (xóa prompt thừa)
                                            $translation = trim($translation);
                                            // Xóa phần tiền tố và hậu tố của prompt nếu nó bị lặp lại trong kết quả
                                            if (stripos($translation, trim($translatePromptSuffix)) !== false) {
                                                $translation = trim(substr($translation, 0, stripos($translation, trim($translatePromptSuffix))));
                                            }
                                             if (stripos($translation, trim($translatePromptPrefix)) === 0) {
                                                  $translation = trim(substr($translation, strlen(trim($translatePromptPrefix))));
                                            }
                                            // Gán kết quả
                                            $extracted_items[$index][$field . '_translated'] = $translation;
                                            // error_log("RSS: Translated item $index, field $field successfully.");
                                        } else {
                                            error_log("RSS: Translation API response for item $index, field $field missing expected 'text' structure. Body: " . $responseBody);
                                            $translation_errors++;
                                        }
                                    } catch (\Throwable $e) { // Bắt cả Error và Exception
                                         error_log("RSS: Error processing successful translation response for item $index, field $field: " . $e->getMessage() . " | Response Body: " . ($responseBody ?? 'N/A'));
                                         $translation_errors++;
                                    }
                                } else { // state === 'rejected'
                                    $reason = $result['reason'];
                                    $errorMessageText = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                                    error_log("RSS: Translation failed for item $index, field $field. Reason: " . $errorMessageText);

                                    if ($reason instanceof ConnectException) {
                                        error_log("RSS: Connection error during translation API call: " . $reason->getMessage());
                                        // Có thể lỗi mạng hoặc DNS
                                    } elseif ($reason instanceof RequestException && $reason->hasResponse()) {
                                         /** @var \Psr\Http\Message\ResponseInterface $response */
                                         $response = $reason->getResponse();
                                         $statusCode = $response->getStatusCode();
                                         $errorBody = $response->getBody()->getContents();
                                         error_log("RSS: Translation API Response Status: {$statusCode}. Body: " . $errorBody);
                                         // Kiểm tra lỗi API Key
                                         if ($statusCode === 400 && strpos($errorBody, 'API key not valid') !== false) {
                                             $api_key_invalid = true;
                                         }
                                         // Kiểm tra lỗi Rate Limit
                                         if ($statusCode === 429) {
                                              error_log("RSS: Translation API rate limit exceeded (429).");
                                              // Cân nhắc dừng hoặc giảm tốc độ
                                         }
                                    }
                                    $translation_errors++;
                                }
                            } // end foreach results

                            if ($api_key_invalid) {
                                // Thêm lỗi nghiêm trọng vào đầu $error_message để hiển thị rõ
                                $error_message = "<p class='error'>Lỗi API Key: API Key không hợp lệ hoặc đã bị chặn. Vui lòng kiểm tra cấu hình 'RSS_GEMINI_API_KEY'.</p>" . $error_message;
                            } elseif ($translation_errors > 0) {
                                 $error_message .= "<p class='warning'>Cảnh báo: Đã xảy ra lỗi khi dịch {$translation_errors} phần nội dung. Một số bản dịch có thể bị thiếu.</p>";
                                 error_log("RSS: Finished concurrent translation with {$translation_errors} errors.");
                            } else {
                                 error_log("RSS: Finished concurrent translation successfully.");
                            }

                        } catch (\Throwable $e) { // Bắt lỗi nghiêm trọng khi xử lý promises
                             $error_message = "<p class='error'>Lỗi hệ thống: Đã xảy ra lỗi không mong muốn trong quá trình dịch đồng loạt.</p>" . $error_message;
                             error_log("RSS: Fatal error during Guzzle promise settlement: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                             // $items_data sẽ không có dữ liệu dịch
                        }
                    } else {
                        error_log("RSS: No valid content found in items to send for translation.");
                    }
                }
                // --- Hết Phần Dịch Thuật ---

                // Gán dữ liệu cuối cùng (có thể đã dịch)
                $items_data = $extracted_items;

                // Lưu vào cache nếu thư mục cache OK và không có lỗi API Key nghiêm trọng
                if ($cache_dir_ok && empty($error_message) && isset($api_key_invalid) && !$api_key_invalid) {
                    if (!empty($items_data)) { // Chỉ cache nếu có dữ liệu (dù là rỗng sau khi lọc)
                        $cache_data = serialize($items_data);
                        // Ghi file với khóa độc quyền
                        if (@file_put_contents($cache_file, $cache_data, LOCK_EX) === false) {
                             error_log("Cảnh báo: Không thể ghi file cache tại '" . htmlspecialchars($cache_file) . "'");
                             // Không báo lỗi cho user ở đây, vì đã có dữ liệu rồi
                        } else {
                            // error_log("RSS: Saved to cache: " . $cache_file);
                        }
                    } else {
                         // Cache kết quả rỗng nếu RSS không có item phù hợp hoặc sau khi lọc
                         @file_put_contents($cache_file, serialize([]), LOCK_EX);
                         error_log("RSS: Cached empty result set to: " . $cache_file);
                    }
                }

            } // end else xử lý XML thành công
        } // end else fetch thành công
    } // end if fetch mới
} // end else URL hợp lệ
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Phần Hiển thị Kết Quả ---
// 6. Hiển thị lỗi tích lũy (nếu có)
if (!empty($error_message)) {
    echo "<div class='rss-errors'>" . $error_message . "</div>"; // Bọc lỗi trong div
}

// 7. Hiển thị dữ liệu nếu có và không bị lỗi nghiêm trọng trước đó ngăn cản việc có dữ liệu
if (!empty($items_data)) { // Kiểm tra items_data có phải mảng và không rỗng
    echo "<ul class='rss-feed-list'>"; // Bắt đầu danh sách tin
    $displayed_count = 0;
    $max_items_display = 10; // Có thể lấy từ config

    // Chỉ dùng cho việc quyết định hiển thị cái nào (gốc/dịch)
    $should_translate_display = $should_translate; // Dùng biến đã kiểm tra config ở trên

    foreach ($items_data as $item_info) {
        if ($displayed_count >= $max_items_display) break;

        // Lấy thông tin từ mảng (an toàn với ?? null coalescing operator)
        $title_original = $item_info['title'] ?? '';
        $description_original_raw = $item_info['description_raw'] ?? '';
        $title_translated = $item_info['title_translated'] ?? null;
        $description_translated = $item_info['description_translated'] ?? null;
        $link = filter_var($item_info['link'] ?? '', FILTER_SANITIZE_URL); // Lọc URL
        $source_name = htmlspecialchars($item_info['source_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');

        // Định dạng ngày tháng
        $pubDate_display = 'N/A';
        if (!empty($item_info['pubDate_raw'])) {
             try {
                 // Thử parse với các định dạng phổ biến
                 $date = new DateTime($item_info['pubDate_raw']);
                 // Điều chỉnh múi giờ nếu cần, ví dụ sang giờ Việt Nam
                 // $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
                 $pubDate_display = $date->format('H:i d/m/Y'); // Giờ:Phút Ngày/Tháng/Năm
             } catch (\Exception $e) {
                 // Nếu không parse được, hiển thị gốc (đã escape)
                 $pubDate_display = htmlspecialchars($item_info['pubDate_raw'], ENT_QUOTES, 'UTF-8');
                 error_log("RSS Date Parse Warning: Could not parse date '{$item_info['pubDate_raw']}'. Error: " . $e->getMessage());
             }
        }

        // --- Xác định Tiêu đề và Mô tả hiển thị + Tooltips ---
        $display_title = $title_original;
        $title_tooltip_attr = ''; // Thuộc tính title cho thẻ a
        if ($should_translate_display && !empty($title_translated)) {
            $display_title = $title_translated;
            if ($title_translated != $title_original) { // Chỉ thêm tooltip nếu khác gốc
                 $title_tooltip_attr = ' title="Gốc: ' . htmlspecialchars($title_original, ENT_QUOTES, 'UTF-8') . '"';
            }
        } elseif (!$should_translate_display && !empty($title_translated) && $title_translated != $title_original) {
              $title_tooltip_attr = ' title="Đã dịch: ' . htmlspecialchars($title_translated, ENT_QUOTES, 'UTF-8') . '"';
        }
        // Escape tiêu đề hiển thị cuối cùng
        $display_title_escaped = htmlspecialchars($display_title, ENT_QUOTES, 'UTF-8');


        $display_description_processed = ''; // Mô tả đã xử lý (rút gọn, escape)
        $description_tooltip_attr = ''; // Thuộc tính title cho thẻ p
        $description_to_process = $description_original_raw;

        if ($should_translate_display && !empty($description_translated)) {
            $description_to_process = $description_translated;
            if ($description_translated != $description_original_raw) {
                $desc_tooltip_text = 'Gốc: ' . htmlspecialchars(limit_words_rss($description_original_raw, 50), ENT_QUOTES, 'UTF-8');
                $description_tooltip_attr = ' title="' . $desc_tooltip_text . '"';
            }
        } elseif (!$should_translate_display && !empty($description_translated) && $description_translated != $description_original_raw) {
             $desc_tooltip_text = 'Đã dịch: ' . htmlspecialchars(limit_words_rss($description_translated, 50), ENT_QUOTES, 'UTF-8');
             $description_tooltip_attr = ' title="' . $desc_tooltip_text . '"';
        }

        // Luôn rút gọn và escape description được chọn để hiển thị
        if (!empty(trim(strip_tags($description_to_process)))) { // Chỉ hiển thị nếu có nội dung text
            $word_limit_display = 25;
            $limited_description = limit_words_rss($description_to_process, $word_limit_display);
            // Escape lần nữa để đảm bảo an toàn
            $display_description_processed = htmlspecialchars($limited_description, ENT_QUOTES, 'UTF-8');
        }
         // --- Hết phần xác định hiển thị ---

        // Xuất HTML cho một item (Kiểm tra link trước khi tạo thẻ a)
        echo "<li class='rss-feed-item'>";
        if (!empty($link)) {
            echo "<a href='{$link}' target='_blank' rel='noopener noreferrer' class='news-title'{$title_tooltip_attr}>{$display_title_escaped}</a>";
        } else {
            echo "<span class='news-title'{$title_tooltip_attr}>{$display_title_escaped}</span>"; // Không có link thì dùng span
        }
        if (!empty($display_description_processed)) {
            echo "<p class='news-description'{$description_tooltip_attr}>{$display_description_processed}</p>";
        }
        echo "<p class='news-meta'><span class='news-source'>Nguồn: {$source_name}</span> <span class='news-separator'>•</span> <span class='news-date'>Xuất bản: {$pubDate_display}</span></p>";
        echo "</li>";

        $displayed_count++;
    } // Hết vòng lặp foreach items_data
    echo "</ul>"; // Kết thúc danh sách tin
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// 8. Trường hợp không có lỗi nghiêm trọng, URL đã cấu hình, nhưng không có dữ liệu (items_data rỗng hoặc null)
elseif (empty($error_message) && !empty($rss_url) && empty($items_data)) {
    // Thông báo này chỉ hiển thị nếu không có lỗi nào được ghi nhận trước đó
    // và $items_data cuối cùng là rỗng (do RSS trống, hoặc lọc hết item, hoặc cache rỗng)
    echo "<p class='info'>Hiện không có tin bài nào để hiển thị từ nguồn RSS đã cấu hình.</p>";
}
// Trường hợp URL chưa cấu hình thì lỗi đã hiển thị ở mục 6
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------