<?php

/**
 * Chuyển đổi một URL tương đối thành URL tuyệt đối dựa trên URL gốc.
 *
 * @param string $relativeUrl URL tương đối cần chuyển đổi (đã trim).
 * @param string $baseUrl URL gốc của trang (ví dụ: https://example.com/path/to/page.html).
 * @return string URL tuyệt đối hoặc URL gốc nếu không thể chuyển đổi hoặc đã là tuyệt đối/data URI.
 */
function convertToAbsoluteUrlX(string $relativeUrl, string $baseUrl): string
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

function testConvertToAbsoluteUrl(string $relativeUrl, string $baseUrl, string $expectedUrl, string $description) {
    $actualUrl = convertToAbsoluteUrlX($relativeUrl, $baseUrl);
    if ($actualUrl === $expectedUrl) {
        echo "[PASS] $description<br>";
        echo "       Input: relative='$relativeUrl', base='$baseUrl'<br>";
        echo "       Output: '$actualUrl'<br>";
    } else {
        echo "[FAIL] $description<br>";
        echo "       Input: relative='$relativeUrl', base='$baseUrl'<br>";
        echo "       Expected: '$expectedUrl'<br>";
        echo "       Actual:   '$actualUrl'<br>";
    }
    echo "--------------------------------------------------<br>";
}

$relativeUrl = 'https://miro.medium.com/v2/resize:fit:720/format:webp/1*74oCTJ59ZRG_AnHE2_OnEg.png';

$baseUrl = 'https://medium.muz.li/best-practices-for-minimalist-website-design-9e8ea07e17c2';

$actualUrl = convertToAbsoluteUrlX($relativeUrl, $baseUrl);

echo $actualUrl;
/*
// 1. URL đã là tuyệt đối hoặc đặc biệt
testConvertToAbsoluteUrl(
    "https://anothersite.com/page.html",
    "https://example.com/path/to/index.html",
    "https://anothersite.com/page.html",
    "Đã là HTTP/HTTPS"
);
testConvertToAbsoluteUrl(
    "//cdn.example.net/script.js",
    "https://example.com/path/to/index.html",
    "https://cdn.example.net/script.js",
    "Protocol-relative, base là HTTPS"
);
testConvertToAbsoluteUrl(
    "//cdn.example.net/style.css",
    "http://example.com/path/to/index.html",
    "http://cdn.example.net/style.css",
    "Protocol-relative, base là HTTP"
);
testConvertToAbsoluteUrl(
    "data:image/png;base64,iVBORw0K...",
    "https://example.com/path/to/index.html",
    "data:image/png;base64,iVBORw0K...",
    "Data URI"
);

// 2. URL tương đối bắt đầu bằng /
testConvertToAbsoluteUrl(
    "/images/logo.png",
    "https://example.com/path/to/index.html",
    "https://example.com/images/logo.png",
    "Đường dẫn từ gốc"
);
testConvertToAbsoluteUrl(
    "/css/style.css",
    "http://example.com:8080/news/article.php",
    "http://example.com:8080/css/style.css",
    "Đường dẫn từ gốc, base có port"
);
testConvertToAbsoluteUrl(
    "/contact.html",
    "https://example.com",
    "https://example.com/contact.html",
    "Đường dẫn từ gốc, base không có path"
);

// 3. URL tương đối so với thư mục hiện tại
testConvertToAbsoluteUrl(
    "script.js",
    "https://example.com/path/to/index.html",
    "https://example.com/path/to/script.js",
    "File trong cùng thư mục, base là file"
);
testConvertToAbsoluteUrl(
    "image.jpg",
    "https://example.com/path/to/gallery/",
    "https://example.com/path/to/gallery/image.jpg",
    "File trong cùng thư mục, base là thư mục (có / cuối)"
);
testConvertToAbsoluteUrl(
    "./another.html",
    "https://example.com/path/to/index.html",
    "https://example.com/path/to/another.html",
    "Bắt đầu bằng ./"
);

// 4. URL tương đối với ../
testConvertToAbsoluteUrl(
    "../style.css",
    "https://example.com/path/to/index.html",
    "https://example.com/path/style.css",
    "Lên một cấp"
);
testConvertToAbsoluteUrl(
    "../../../global.css",
    "https://example.com/one/two/index.html",
    "https://example.com/global.css",
    "Lên cấp vượt quá root của path"
);
testConvertToAbsoluteUrl(
    "../other.html",
    "https://example.com/",
    "https://example.com/other.html",
    "Lên cấp từ root path"
);
testConvertToAbsoluteUrl(
    "./sub/../another-sub/./file.txt",
    "https://example.com/base/dir/page.html",
    "https://example.com/base/dir/another-sub/file.txt",
    "Kết hợp . và .."
);


// 5. Chỉ là Fragment hoặc Query String
testConvertToAbsoluteUrl(
    "#section2",
    "https://example.com/page.html",
    "https://example.com/page.html#section2",
    "Chỉ fragment"
);
testConvertToAbsoluteUrl(
    "#new-section",
    "https://example.com/page.html#old-section",
    "https://example.com/page.html#new-section",
    "Chỉ fragment, base đã có fragment"
);
testConvertToAbsoluteUrl(
    "?param=value&foo=bar",
    "https://example.com/search.php",
    "https://example.com/search.php?param=value&foo=bar",
    "Chỉ query string"
);
testConvertToAbsoluteUrl(
    "?new=1",
    "https://example.com/product.php?id=123",
    "https://example.com/product.php?new=1",
    "Chỉ query string, base đã có query"
);
testConvertToAbsoluteUrl(
    "otherpage.html",
    "https://example.com/current/page.html?query=1#frag",
    "https://example.com/current/otherpage.html",
    "Query/fragment trên base, relative là path"
);


// 6. Các trường hợp biên
testConvertToAbsoluteUrl(
    "",
    "https://example.com/index.html",
    "",
    "relativeUrl rỗng"
);
testConvertToAbsoluteUrl(
    "page.html",
    "",
    "page.html",
    "baseUrl rỗng"
);
testConvertToAbsoluteUrl(
    "image.png",
    "example.com/path/index.html",
    "image.png",
    "baseUrl không có scheme"
);
testConvertToAbsoluteUrl(
    "sibling.html",
    "https://example.com/index.html",
    "https://example.com/sibling.html",
    "baseUrl là file trong root"
);
testConvertToAbsoluteUrl(
    "about.html",
    "https://example.com",
    "https://example.com/about.html",
    "baseUrl không có path, relative là file"
);
testConvertToAbsoluteUrl(
    "item.html",
    "https://example.com/products", // Path là /products
    "https://example.com/item.html", // dirname("/products") là "/"
    "Base URL không có trailing slash cho thư mục (lưu ý hành vi)"
);
testConvertToAbsoluteUrl(
    "item.html",
    "https://example.com/products/", // Path là /products/
    "https://example.com/products/item.html",
    "Base URL có trailing slash cho thư mục"
);
testConvertToAbsoluteUrl(
    "/folder//file.html",
    "https://example.com/",
    "https://example.com/folder/file.html",
    "Nhiều dấu gạch chéo trong relativeUrl"
);
testConvertToAbsoluteUrl(
    "file.html",
    "https://example.com//double-slash-path/",
    "https://example.com/double-slash-path/file.html",
    "Nhiều dấu gạch chéo trong baseUrl path"
);
*/