<?php
// Tất cả các hàm liên quan đến update hoặc loại bỏ các tag HTML nằm ở đây
// Việc loại bỏ hoặc update các tag HTML rất quan trọng, liên quan đến chất lượng dịch cũng như mức độ đầy đủ hoặc chỉ tập trung vào phần nội dung chính để dịch
// Là các hàm rất quan trọng, cần kiểm tra mức độ chính xác 
// Thống nhất về chung một phương pháp, xem xét loại bỏ các hàm gần giống nhau


/**
 * Loại bỏ các thẻ inline cụ thể bên trong thẻ <p> HOẶC <div> mà không xóa nội dung text của chúng.
 * Nội dung của các thẻ inline sẽ được "unwrap" và trở thành một phần trực tiếp của thẻ cha gần nhất (<p> hoặc <div> hoặc thẻ khác bên trong chúng).
 * Thích hợp để loại bỏ các thẻ định dạng như strong, em, span, mark, etc., mà không ảnh hưởng đến nội dung.
 * Lưu ý: Thẻ `<code>` KHÔNG được loại bỏ mặc định.
 *
 * @param string $html Chuỗi HTML đầu vào (dự kiến UTF-8).
 * @param array $tagsToRemove Mảng các tên thẻ inline cần loại bỏ.
 *                           Mặc định: ['a', 'strong', 'b', 'em', 'i', 'span', 'u', 'mark'].
 * @return string Chuỗi HTML đã được xử lý.
 */
function unwrapInlineTagsInParagraphsAndDivs(string $html, array $tagsToRemove = ['a', 'strong', 'b', 'em', 'i', 'span', 'u', 'mark']): string // Tên hàm rõ ràng hơn
{
    // Trim đầu vào và kiểm tra các điều kiện cơ bản
    $html = trim($html);
    if (empty($html) || empty($tagsToRemove)) {
        return $html;
    }

    // Tạo đối tượng DOMDocument
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // Tạo đối tượng DOMXPath
    $xpath = new DOMXPath($dom);

    // Tạo biểu thức XPath để tìm các thẻ cần loại bỏ
    // KHI chúng là con cháu (descendant) của thẻ <p> HOẶC <div>
    $queryParts = [];
    foreach ($tagsToRemove as $tag) {
        $tagName = trim(strtolower($tag));
        if (ctype_alpha($tagName)) {
            // Thêm điều kiện cho cả <p> và <div>
            $queryParts[] = "//p//{$tagName}"; // Tìm trong <p>
            $queryParts[] = "//div//{$tagName}"; // Tìm trong <div>
        }
    }

    if (empty($queryParts)) {
        return $html;
    }

    // Ghép các phần query lại bằng OR (|)
    // Ví dụ: //p//a | //div//a | //p//strong | //div//strong ...
    $query = implode(' | ', $queryParts);

    // Thực hiện truy vấn XPath
    $nodesToUnwrap = $xpath->query($query);

    // Duyệt ngược và unwrap (logic unwrap giữ nguyên)
    for ($i = $nodesToUnwrap->length - 1; $i >= 0; $i--) {
        $node = $nodesToUnwrap->item($i);
        // Kiểm tra node và parentNode trước khi thao tác
        if ($node && $node->parentNode) {
            $parentNode = $node->parentNode; // Parent trực tiếp của thẻ inline
            // Di chuyển con của node ra ngoài
            while ($node->hasChildNodes()) {
                $child = $node->firstChild;
                $parentNode->insertBefore($child, $node);
            }
            // Xóa node (thẻ inline rỗng)
            $parentNode->removeChild($node);
        }
    }

    // Lấy lại HTML đã xử lý từ body
    $cleanedHtml = '';
    $body = $dom->getElementsByTagName('body')->item(0);
    if ($body) {
        foreach ($body->childNodes as $child) {
            $cleanedHtml .= $dom->saveHTML($child);
        }
    } else {
        $cleanedHtml = $dom->saveHTML();
        $cleanedHtml = str_replace('<?xml encoding="UTF-8">', '', $cleanedHtml);
    }

    return trim($cleanedHtml);
}


/**
 * Loại bỏ các thẻ HTML không mong muốn (figure, img, figcaption, table, video, audio, iframe)
 * khỏi một chuỗi HTML sử dụng phương pháp DOMDocument.
 * Mục đích: Chuẩn bị HTML cho việc xử lý/trích xuất văn bản thuần túy (ví dụ: dịch máy,
 * chuyển đổi sang text), nơi các nội dung đa phương tiện, bảng biểu, hoặc nội dung nhúng
 * (và text liên quan như alt, caption) cần được loại bỏ.
 * Hàm này đảm bảo xử lý đúng encoding UTF-8 và cố gắng tránh thêm các thẻ không mong muốn (html, body).
 *
 * @param string $htmlContent Nội dung HTML gốc (dự kiến là UTF-8).
 * @return string Chuỗi HTML đã được xử lý, loại bỏ các thẻ được chỉ định. Trả về chuỗi rỗng nếu đầu vào rỗng.
 */
function removeSpecifiedHtmlTags(string $htmlContent): string // Đã đổi tên hàm cho rõ ràng hơn
{
    // Trim và kiểm tra xem có nội dung không
    $htmlContent = trim($htmlContent);
    if (empty($htmlContent)) {
        return ''; // Trả về rỗng nếu không có gì để xử lý
    }

    // Danh sách các tên thẻ cần loại bỏ
    $tagsToRemove = ['img', 'figcaption', 'table', 'video', 'audio', 'iframe', 'figure'];

    $dom = new DOMDocument();
    // Tắt báo lỗi libxml và tự xử lý lỗi (quan trọng)
    libxml_use_internal_errors(true);

    // Tải HTML:
    // - Thêm khai báo encoding UTF-8 để DOMDocument xử lý đúng ký tự.
    // - Sử dụng flags LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD để hạn chế
    //   việc DOMDocument tự động thêm thẻ <html>, <head>, <body> nếu không cần thiết.
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Xóa các lỗi parsing đã được lưu lại (vì chúng ta đã tắt hiển thị lỗi)
    libxml_clear_errors();

    // Mảng để chứa tất cả các node cần xóa
    $nodesToRemove = [];

    // Thu thập tất cả các node cần xóa từ danh sách $tagsToRemove
    foreach ($tagsToRemove as $tagName) {
        $elements = $dom->getElementsByTagName($tagName);
        // Quan trọng: Không xóa trực tiếp ở đây. Chỉ thu thập.
        // Vì $elements là DOMNodeList "live", cần duyệt qua nó trước khi thay đổi DOM.
        // Cách đơn giản nhất là thêm vào mảng $nodesToRemove.
        foreach ($elements as $element) {
            $nodesToRemove[] = $element;
        }
        // Giải phóng biến tạm (không bắt buộc, chỉ để rõ ràng)
        unset($elements);
    }

    /*
    // Cách khác dùng XPath (ngắn gọn hơn nhưng cần biết XPath):
    $xpath = new DOMXPath($dom);
    $query = '//' . implode(' | //', $tagsToRemove); // Tạo query dạng: //img | //figcaption | //table ...
    $elementsToRemove = $xpath->query($query);
    foreach ($elementsToRemove as $element) {
        $nodesToRemove[] = $element;
    }
    */

    // Duyệt qua mảng và xóa các node đã thu thập
    // Việc này an toàn vì đang duyệt qua một mảng tĩnh, không phải DOMNodeList "live".
    foreach ($nodesToRemove as $node) {
        // Kiểm tra xem node còn tồn tại và có parent không (đề phòng trường hợp node đã bị xóa gián tiếp
        // ví dụ: xóa thẻ figure chứa figcaption trước khi xóa figcaption)
        if ($node && $node->parentNode) {
             $node->parentNode->removeChild($node);
        }
    }

    // Lấy lại chuỗi HTML đã được chỉnh sửa 
    $processedHtml = '';
    $body = $dom->getElementsByTagName('body')->item(0);
    if ($body) {
        foreach ($body->childNodes as $child) {
            $processedHtml .= $dom->saveHTML($child);
        }
    } else {
        $processedHtml = $dom->saveHTML();
        $processedHtml = str_replace('<?xml encoding="utf-8" ?>', '', $processedHtml);
    }

    // Trả về kết quả đã được trim khoảng trắng thừa
    return trim($processedHtml);
}


/**
 * Hàm nhận một chuỗi HTML và bọc tất cả các thẻ <table> tìm thấy
 * bên trong một thẻ <div class="table-container-sila-trans">.
 * Mục đích là để CSS bảng, tránh nó tràn khung chứa (vì quá rộng) làm hỏng bố cục.
 *
 * @param string $html_content Nội dung HTML đầu vào.
 * @param string $wrapper_class Class CSS cho thẻ div bọc ngoài (mặc định: 'table-container-sila-trans').
 * @return string Nội dung HTML đã được xử lý.
 */
function wrapTablesInDiv(string $html_content, string $wrapper_class = 'table-container-sila-trans'): string
{
    // Nếu nội dung trống thì trả về luôn
    if (empty(trim($html_content))) {
        return $html_content;
    }

    // Tạo đối tượng DOMDocument
    $dom = new DOMDocument();

    // Tắt các lỗi/cảnh báo khi parse HTML không chuẩn (rất phổ biến khi lấy từ web)
    libxml_use_internal_errors(true);

    // QUAN TRỌNG: Thêm tiền tố khai báo encoding UTF-8 để DOMDocument::loadHTML
    // xử lý đúng các ký tự multi-byte (như tiếng Việt).
    // Sử dụng @ để tránh warning nếu HTML có vấn đề.
    // Các cờ LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD cố gắng ngăn loadHTML
    // tự động thêm các thẻ <html>, <body>, <!DOCTYPE> không cần thiết khi xử lý fragment (HTML không hoàn chính).
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Xóa các lỗi đã ghi nhận trong quá trình parse
    libxml_clear_errors();

    // Tạo đối tượng DOMXPath để truy vấn
    $xpath = new DOMXPath($dom);

    // Tìm tất cả các thẻ <table> trong tài liệu (kể cả table lồng nhau)
    $tables = $xpath->query('//table');

    // Lặp qua từng thẻ <table> tìm được
    // QUAN TRỌNG: Lặp ngược (từ cuối về đầu) để việc thay đổi cấu trúc DOM
    // (di chuyển table vào div) không làm ảnh hưởng đến chỉ số của các node
    // còn lại trong $tables NodeList trong các lần lặp tiếp theo.
    for ($i = $tables->length - 1; $i >= 0; $i--) {
        $tableNode = $tables->item($i);

        // Kiểm tra xem node có tồn tại và có parent không (phòng trường hợp hiếm)
        if ($tableNode && $tableNode->parentNode) {
            $parentNode = $tableNode->parentNode;

            // Tạo thẻ div mới
            $divWrapper = $dom->createElement('div');
            $divWrapper->setAttribute('class', $wrapper_class); // Đặt class cho div

            // Chèn thẻ div mới vào *trước* thẻ table trong cây DOM
            $parentNode->insertBefore($divWrapper, $tableNode);

            // Di chuyển thẻ table vào *bên trong* thẻ div vừa tạo.
            // Thao tác appendChild này sẽ tự động gỡ bỏ $tableNode khỏi vị trí cũ
            // và thêm nó làm con của $divWrapper.
            $divWrapper->appendChild($tableNode);
        }
    }

    // Lấy lại chuỗi HTML đã được chỉnh sửa.
    // Gọi saveHTML() trực tiếp trên $dom thường sẽ trả về cả document đầy đủ
    // (có thể bao gồm DOCTYPE, <html>, <head>, <body> mà loadHTML đã tự thêm vào,
    // ngay cả khi đã dùng flags để chống thao tác này).
    // Cách tốt hơn là chỉ lấy nội dung bên trong thẻ <body> (thường được tạo tự động).
    $processedHtml = '';
    // Tìm thẻ body (loadHTML thường tự tạo ra nó cho fragments)
    $body = $dom->getElementsByTagName('body')->item(0);
    if ($body) {
        // Lặp qua từng node con trực tiếp của body
        foreach ($body->childNodes as $child) {
            // Xuất HTML của từng node con này. Điều này giúp lấy lại nội dung
            // bên trong body mà không bao gồm chính thẻ <body>.
            // Kết quả thường là UTF-8 và không có <?xml encoding.. declaration.
            $processedHtml .= $dom->saveHTML($child);
        }
    } else {
        // Fallback: Trường hợp rất hiếm khi loadHTML không tạo body
        // (ví dụ: input chỉ là text thuần túy). Thử saveHTML toàn bộ document.
        // Lưu ý: Kết quả này có thể chứa các thẻ bao ngoài không mong muốn.
        $processedHtml = $dom->saveHTML();
        // Cố gắng loại bỏ khai báo XML có thể đã thêm vào đầu
        $processedHtml = str_replace('<?xml encoding="utf-8" ?>', '', $processedHtml);
        // Có thể cần thêm các bước làm sạch khác nếu thẻ <html>, <body> là vấn đề.
    }

    // Trả về kết quả đã được trim khoảng trắng thừa ở đầu/cuối
    return trim($processedHtml);
}