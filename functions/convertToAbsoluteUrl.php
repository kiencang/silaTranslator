<?php
// Các hàm phục vụ cho việc chuyển link từ tương đối sang tuyệt đối nằm ở đây
// Các hàm này cũng rất quan trọng để đảm bảo ảnh hiển thị, và link bấm được đối với các website để link tương đối
// Lên trang en.wikipedia.org để test tính năng này, vì ở đây có nhiều ảnh và url tương đối
// Đã kiểm tra và hoạt động tốt với cả link tương đối là thẻ a và ảnh.
// Chưa áp dụng với audio và video

/**
 * Chuyển đổi một URL tương đối thành URL tuyệt đối dựa trên URL gốc.
 *
 * @param string $relativeUrl URL tương đối cần chuyển đổi (đã trim).
 * @param string $baseUrl URL gốc của trang (ví dụ: https://example.com/path/to/page.html).
 * @return string URL tuyệt đối hoặc URL gốc nếu không thể chuyển đổi hoặc đã là tuyệt đối/data URI.
 */
function convertToAbsoluteUrl(string $relativeUrl, string $baseUrl): string
{
    $relativeUrl = trim($relativeUrl);
    
    // $relativeUrl = trim($relativeUrl);
    if (empty($relativeUrl) || empty($baseUrl)) {
        return $relativeUrl;
    }

    // 1. Kiểm tra nếu đã là URL tuyệt đối hoặc không cần xử lý (data URI)
    // Hỗ trợ http, https, data, và protocol-relative (//)
    if (preg_match('~^(?:[a-z]+:)|(?:data:)|(?://)~i', $relativeUrl)) {
        // Nếu là protocol-relative (bắt đầu bằng //)
        if (strpos($relativeUrl, '//') === 0) {
            $baseParsed = parse_url($baseUrl);
            // Cố gắng thêm scheme từ base URL
            if (isset($baseParsed['scheme'])) {
                return $baseParsed['scheme'] . ':' . $relativeUrl;
            }
            // Không xác định được scheme, trả về nguyên trạng (trình duyệt sẽ tự xử lý)
            return $relativeUrl;
        }
        // Đã là tuyệt đối (có scheme) hoặc data URI
        return $relativeUrl;
    }

    // 2. Phân tích URL gốc
    $baseParsed = parse_url($baseUrl);
    // Cần scheme và host để tạo URL tuyệt đối
    if ($baseParsed === false || !isset($baseParsed['scheme']) || !isset($baseParsed['host'])) {
        return $relativeUrl; // URL gốc không hợp lệ để làm cơ sở
    }
    $scheme = $baseParsed['scheme'];
    $host = $baseParsed['host'];
    $port = isset($baseParsed['port']) ? ':' . $baseParsed['port'] : '';
    // Lấy path, mặc định là / nếu không có
    $basePath = isset($baseParsed['path']) ? $baseParsed['path'] : '/';

    // 3. Xử lý URL tương đối
    if ($relativeUrl[0] === '/') {
        // 3.1. Đường dẫn tuyệt đối từ gốc domain (ví dụ: /images/logo.png)
        // Chỉ cần ghép scheme, host, port với relativeUrl
        $absoluteUrl = $scheme . '://' . $host . $port . $relativeUrl;
    } else if ($relativeUrl[0] === '#') {
        // 3.1b. Chỉ là fragment identifier (ví dụ: #section1)
        // Trả về URL gốc cộng với fragment
        return rtrim($baseUrl, '#') . $relativeUrl;
    } else if ($relativeUrl[0] === '?') {
         // 3.1c. Chỉ là query string (ví dụ: ?param=value)
         // Trả về phần URL gốc trước query/fragment cộng với query mới
         $baseWithoutQuery = strtok($baseUrl, '?#');
         return $baseWithoutQuery . $relativeUrl;
    }
    else {
        // 3.2. Đường dẫn tương đối so với thư mục hiện tại (ví dụ: ../images/logo.png hoặc logo.png)

        // --- Logic tính baseDir được đơn giản hóa ---
        // Lấy đường dẫn thư mục của URL gốc, đảm bảo kết thúc bằng /
        // Ví dụ: /path/to/page.html -> /path/to/
        // Ví dụ: /path/to/          -> /path/to/
        // Ví dụ: /                  -> /
        $baseDir = rtrim(dirname($basePath), '/') . '/';

        // Ghép đường dẫn thư mục gốc với URL tương đối
        $targetPath = $baseDir . $relativeUrl;

        // 3.3. Chuẩn hóa đường dẫn (xử lý ../ và ./) - Thuật toán chuẩn
        $parts = explode('/', $targetPath);
        $absolutes = [];
        foreach ($parts as $part) {
            // Bỏ qua các thành phần rỗng (do // hoặc path rỗng) hoặc '.'
            if ($part === '.' || $part === '') {
                continue;
            }
            // Nếu là '..', đi lên một cấp bằng cách xóa phần tử cuối của kết quả
            if ($part === '..') {
                array_pop($absolutes);
            } else {
                // Ngược lại, thêm thành phần này vào kết quả
                $absolutes[] = $part;
            }
        }
        // Nối lại các thành phần hợp lệ, bắt đầu bằng /
        $resolvedPath = '/' . implode('/', $absolutes);

        // Ghép thành URL tuyệt đối cuối cùng
        $absoluteUrl = $scheme . '://' . $host . $port . $resolvedPath;
    }

    // Bước 4 không còn cần thiết vì chuẩn hóa ở 3.3 đã xử lý ./

    return $absoluteUrl;
}

/**
 * Kiểm tra nhanh xem một chuỗi HTML có khả năng chứa ít nhất một URL hình ảnh tương đối
 * trong thuộc tính src hoặc srcset của thẻ img hoặc source hay không.
 *
 * Hàm này sử dụng regex để kiểm tra nhanh, nhanh hơn DOM parsing nhưng có thể
 * không chính xác 100% trong mọi trường hợp HTML phức tạp hoặc không chuẩn.
 * Nó được thiết kế để giảm việc gọi các hàm xử lý DOM nặng khi không cần thiết.
 *
 * URL tương đối được coi là các URL:
 * - Không bắt đầu bằng "http://", "https://"
 * - Không bắt đầu bằng "//" (protocol-relative)
 * - Không bắt đầu bằng "data:" (data URI)
 *
 * @param string $htmlContent Nội dung HTML đầu vào.
 * @return bool True nếu có khả năng chứa URL hình ảnh tương đối, False nếu không tìm thấy
 *              hoặc chuỗi đầu vào rỗng.
 */
function hasPotentialRelativeImageUrls(string $htmlContent): bool
{
    // Bỏ qua nếu nội dung rỗng
    if (empty(trim($htmlContent))) {
        return false;
    }

    /**
     * Regex cải tiến:
     * \s(?:...) : Bắt đầu bằng khoảng trắng, dùng non-capturing group cho các lựa chọn
     *
     * Lựa chọn 1: src="..."
     * src\s*=\s*                 # Tìm src=
     * ["\']                      # Dấu nháy mở
     * (?!https?:|\/\/|data:)     # Lookahead: KHÔNG phải absolute/data URI ngay sau dấu nháy
     * [^"\'\s,\#]                # Phải có ít nhất một ký tự hợp lệ của URL tương đối
     *
     * Lựa chọn 2: srcset="..."
     * |                           # HOẶC
     * srcset\s*=\s*              # Tìm srcset=
     * ["\']                      # Dấu nháy mở
     * (?:                         # Non-capturing group cho các khả năng trong srcset
     *     [^"\'<>]*?              # Khớp bất kỳ ký tự nào (non-greedy) cho đến khi gặp...
     *     (?:                     # Non-capturing group cho điểm bắt đầu của URL tương đối
     *        ^                      # ... hoặc là đầu chuỗi bên trong dấu nháy
     *        |                    # HOẶC
     *        ,\s*                 # ... hoặc là dấu phẩy và khoảng trắng
     *     )
     *     (?!https?:|\/\/|data:) # Lookahead: KHÔNG phải absolute/data URI tại điểm này
     *     [^"\'\s,]                # Phải có ít nhất một ký tự hợp lệ của URL tương đối sau đó
     * )
     * /i                          : Cờ không phân biệt hoa thường
     */
    $pattern = '/\s(?:src\s*=\s*["\'](?!https?:|\/\/|data:)[^"\'\s,\#]|srcset\s*=\s*["\'](?:[^"\'<>]*?(?:^|,\s*)(?!https?:|\/\/|data:)[^"\'\s,]))/i';


    // Dùng preg_match để kiểm tra sự tồn tại
    return preg_match($pattern, $htmlContent) === 1;
}

/**
 * Chuyển đổi tất cả các URL hình ảnh tương đối (trong img src, srcset và source srcset)
 * trong một chuỗi HTML thành URL tuyệt đối.
 *
 * @param string $htmlContent Nội dung HTML đầu vào (dự kiến UTF-8).
 * @param string $baseUrl URL gốc của trang chứa HTML này. Phải là URL hợp lệ.
 * @return string Nội dung HTML đã được cập nhật với URL tuyệt đối hoặc nội dung gốc nếu lỗi.
 */
function convertRelativeImageUrlsToAbsolute(string $htmlContent, string $baseUrl): string
{
    // Trim để đảm bảo kiểm tra empty hoạt động đúng
    $htmlContent = trim($htmlContent);
    // Kiểm tra input cơ bản và tính hợp lệ của Base URL
    if (empty($htmlContent) || empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        return $htmlContent;
    }

    $doc = new DOMDocument();
    // Tắt lỗi libxml để tự xử lý
    libxml_use_internal_errors(true);

    // Sử dụng mb_convert_encoding để loadHTML xử lý UTF-8 tốt hơn
    // Dùng @ để chặn warning nếu HTML có vấn đề nghiêm trọng
    // Giữ cờ NOIMPLIED/NODEFDTD để linh hoạt hơn nếu gặp fragment, nhưng có thể bỏ nếu chắc chắn là full doc
    @$doc->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    // Xóa lỗi đã lưu
    libxml_clear_errors();

    $xpath = new DOMXPath($doc); // Có thể dùng XPath nếu cần query phức tạp hơn, nhưng getElementsByTagName là đủ ở đây

    // --- Xử lý thẻ <img> ---
    $images = $doc->getElementsByTagName('img');
    foreach ($images as $img) {
        // Xử lý thuộc tính src
        if ($img->hasAttribute('src')) {
            $originalSrc = $img->getAttribute('src');
            // Chỉ chuyển đổi nếu nó không phải là data URI
            if (strpos(trim($originalSrc), 'data:') !== 0) {
                $absoluteSrc = convertToAbsoluteUrl(trim($originalSrc), $baseUrl); // Trim URL trước khi xử lý
                if ($absoluteSrc !== $originalSrc) {
                    $img->setAttribute('src', $absoluteSrc);
                }
            }
        }

        // Xử lý thuộc tính srcset
        if ($img->hasAttribute('srcset')) {
            $originalSrcset = $img->getAttribute('srcset');
            $newSrcsetParts = [];
            $srcsetPairs = explode(',', $originalSrcset);
            foreach ($srcsetPairs as $pair) {
                $trimmedPair = trim($pair);
                if (empty($trimmedPair)) continue;

                $parts = preg_split('/\s+/', $trimmedPair, 2);
                $url = trim($parts[0]); // Trim URL trong cặp
                $descriptor = isset($parts[1]) ? ' ' . trim($parts[1]) : ''; // Giữ lại descriptor

                // Chỉ chuyển đổi nếu không phải data URI
                if (!empty($url) && strpos($url, 'data:') !== 0) {
                    $absoluteUrl = convertToAbsoluteUrl($url, $baseUrl);
                    $newSrcsetParts[] = $absoluteUrl . $descriptor;
                } else {
                    $newSrcsetParts[] = $trimmedPair; // Giữ nguyên nếu là data URI hoặc rỗng
                }
            }
            $newSrcset = implode(', ', $newSrcsetParts);
            if ($newSrcset !== $originalSrcset) {
                $img->setAttribute('srcset', $newSrcset);
            }
        }
    }

    // --- Xử lý thẻ <source> (thường bên trong <picture>) ---
    $sources = $doc->getElementsByTagName('source');
    foreach ($sources as $source) {
        // Chỉ xử lý srcset nếu có (source có thể dùng src cho các loại khác)
        if ($source->hasAttribute('srcset')) {
            // Logic tương tự như xử lý srcset của <img>
            $originalSrcset = $source->getAttribute('srcset');
            $newSrcsetParts = [];
            $srcsetPairs = explode(',', $originalSrcset);
            foreach ($srcsetPairs as $pair) {
                $trimmedPair = trim($pair);
                if (empty($trimmedPair)) continue;

                $parts = preg_split('/\s+/', $trimmedPair, 2);
                $url = trim($parts[0]);
                $descriptor = isset($parts[1]) ? ' ' . trim($parts[1]) : '';

                if (!empty($url) && strpos($url, 'data:') !== 0) {
                     $absoluteUrl = convertToAbsoluteUrl($url, $baseUrl);
                     $newSrcsetParts[] = $absoluteUrl . $descriptor;
                } else {
                     $newSrcsetParts[] = $trimmedPair;
                }
            }
            $newSrcset = implode(', ', $newSrcsetParts);
             if ($newSrcset !== $originalSrcset) {
                 $source->setAttribute('srcset', $newSrcset);
             }
        }
    }

    // --- Lưu lại HTML đã được cập nhật (SỬA ĐỔI QUAN TRỌNG) ---
    $updatedHtml = '';
    // Cố gắng lấy nội dung từ bên trong thẻ body nếu có
    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
        foreach ($body->childNodes as $child) {
            $updatedHtml .= $doc->saveHTML($child);
        }
    } else {
        // Fallback: Nếu không có body (ví dụ: fragment chỉ chứa thẻ img đơn lẻ)
        // Hoặc nếu dùng cờ NOIMPLIED, có thể cần save từ documentElement
        if ($doc->documentElement) {
             foreach ($doc->documentElement->childNodes as $child) {
                 $updatedHtml .= $doc->saveHTML($child);
             }
        } else {
             // Fallback cuối cùng: save toàn bộ (có thể chứa thẻ bao ngoài)
             $updatedHtml = $doc->saveHTML();
             
             // Loại bỏ khai báo XML nếu có
             $updatedHtml= str_replace('<?xml encoding="UTF-8">', '', $updatedHtml);
        }
    }


    // Decode lại các HTML entities mà loadHTML/saveHTML có thể đã tạo ra
    // để khôi phục các ký tự UTF-8 gốc.
    return html_entity_decode($updatedHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}


// Kiểm tra nhanh xem có link tương đối không, nếu có mới tiến hành phân tích DOM
// Dùng regex để tìm nhanh và đưa vào nhiều ràng buộc
function hasPotentiallyRelativeLinks(string $htmlContent): bool {
    if (empty($htmlContent)) {
        return false;
    }

    // Regex để tìm thẻ <a ... href="..."> mà giá trị href KHÔNG bắt đầu bằng:
    // - http: hoặc https: (scheme tuyệt đối)
    // - // (scheme-relative)
    // - mailto:
    // - tel:
    // - javascript:
    // - data:
    // - # (anchor link)
    // - và không phải là rỗng hoặc chỉ chứa khoảng trắng
    // Nó sẽ khớp với các href bắt đầu bằng / , .. , . , hoặc một ký tự tên file/thư mục.
    // Dùng cờ 'i' để không phân biệt hoa thường cho thẻ 'a' và 'href'.
    // Dùng 's' để dấu chấm (.) khớp với cả ký tự xuống dòng (nếu có trong giá trị href phức tạp).
    // [\s\S]*? dùng thay cho .*? để khớp với mọi thứ kể cả xuống dòng một cách không tham lam giữa các phần.
    // (?!...) là negative lookahead assertion.
    $pattern = '/<a\s+[\s\S]*?href\s*=\s*([\'"])\s*(?!https?:|\/\/|mailto:|tel:|javascript:|data:|#|\s*\\1)([^\\1]+?)\s*\\1[\s\S]*?>/is';

    // preg_match trả về 1 nếu tìm thấy ít nhất một kết quả khớp, 0 nếu không, false nếu lỗi.
    return preg_match($pattern, $htmlContent) === 1;
}



/**
 * Chuyển đổi tất cả các URL liên kết tương đối (trong a href)
 * trong một chuỗi HTML thành URL tuyệt đối.
 *
 * @param string $htmlContent Nội dung HTML đầu vào.
 * @param string $baseUrl URL gốc của trang chứa HTML này (nên bao gồm cả scheme và host).
 * @return string Nội dung HTML đã được cập nhật với URL tuyệt đối.
 */
function convertRelativeLinkUrlsToAbsolute(string $htmlContent, string $baseUrl): string
{
    if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
    // Có thể ghi log lỗi ở đây nếu cần
    // error_log("Base URL không hợp lệ khi gọi convertRelativeLinkUrlsToAbsolute: " . $baseUrl);
    return $htmlContent;
    }
    
    // --- KIỂM TRA NHANH BẰNG REGEX ---
    if (!hasPotentiallyRelativeLinks($htmlContent)) {
        // Nếu regex không tìm thấy dấu hiệu của link tương đối,
        // trả về ngay lập tức để tiết kiệm tài nguyên.
        return $htmlContent;
    }
    // --- KẾT THÚC KIỂM TRA NHANH ---

    // --- CHỈ THỰC HIỆN PHÂN TÍCH DOM KHI CẦN ---
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $loadSuccess = @$doc->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    if (!$loadSuccess) {
        // error_log("Không thể phân tích cú pháp HTML.");
        return $htmlContent; // Không thể phân tích HTML, trả về gốc
    }

    $links = $doc->getElementsByTagName('a');
    $changed = false; // Cờ để theo dõi thay đổi thực sự

    foreach ($links as $link) {
        if ($link->hasAttribute('href')) {
            $originalHref = $link->getAttribute('href');

            // Gọi hàm trợ giúp để chuyển đổi URL (hàm này sẽ tự xử lý các link đã tuyệt đối)
            $absoluteHref = convertToAbsoluteUrl($originalHref, $baseUrl);

            if ($absoluteHref !== $originalHref) {
                $link->setAttribute('href', $absoluteHref);
                $changed = true;
            }
        }
    }

    // Nếu không có thay đổi nào *thực sự* xảy ra sau khi phân tích DOM
    // (ví dụ: regex có false positive), vẫn nên trả về HTML gốc để tránh
    // chi phí không cần thiết của saveHTML và decode entities.
    
    if (!$changed) {
        return $htmlContent; // Nếu không có gì thay đổi thi không cần làm gì cả
    }
    
    // --- Lưu lại HTML đã được cập nhật (SỬA ĐỔI QUAN TRỌNG) ---
    $updatedHtml = '';
    // Cố gắng lấy nội dung từ bên trong thẻ body nếu có
    $body = $doc->getElementsByTagName('body')->item(0);
    if ($body) {
        foreach ($body->childNodes as $child) {
            $updatedHtml .= $doc->saveHTML($child);
        }
    } else {
        // Fallback: Nếu không có body (ví dụ: fragment chỉ chứa thẻ a đơn lẻ)
        // Hoặc nếu dùng cờ NOIMPLIED, có thể cần save từ documentElement
        if ($doc->documentElement) {
             foreach ($doc->documentElement->childNodes as $child) {
                 $updatedHtml .= $doc->saveHTML($child);
             }
        } else {
             // Fallback cuối cùng: save toàn bộ (có thể chứa thẻ bao ngoài)
             $updatedHtml = $doc->saveHTML();
             
             // Loại bỏ khai báo XML nếu có
             $updatedHtml= str_replace('<?xml encoding="UTF-8">', '', $updatedHtml);
        }
    }


    // Decode lại các HTML entities mà loadHTML/saveHTML có thể đã tạo ra
    // để khôi phục các ký tự UTF-8 gốc.
    return html_entity_decode($updatedHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8'); 
}
