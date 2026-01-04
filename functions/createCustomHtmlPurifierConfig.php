<?php

/**
 * Tạo cấu hình HTMLPurifier tùy chỉnh, cân bằng giữa bảo mật và giữ định dạng.
 *
 * Hàm này tạo một đối tượng cấu hình được tinh chỉnh cho việc lọc HTML
 * SAU KHI đã qua các bước như Readability và dịch thuật.
 * Nó nghiêm ngặt về bảo mật nhưng linh hoạt hơn cấu hình mặc định
 * trong việc cho phép các thuộc tính CSS và thẻ định dạng cơ bản.
 *
 * @return HTMLPurifier_Config Đối tượng cấu hình đã được thiết lập.
 */
function createCustomHtmlPurifierConfig(): HTMLPurifier_Config
{
    // 1. Bắt đầu với cấu hình mặc định
    $config = HTMLPurifier_Config::createDefault();

    // --- A. Cấu hình Cơ bản và Bắt buộc ---
    // (Giữ nguyên các cấu hình cache, doctype, etc. của bạn)
    $cachePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'purifier-cache'; // do vị trí của file này, nằm trong thư mục functions
    if (!is_dir($cachePath)) {
        if (!mkdir($cachePath, 0755, true) && !is_dir($cachePath)) {
             throw new RuntimeException('Không thể tạo thư mục cache HTMLPurifier: ' . $cachePath);
        }
    }
    if (!is_writable($cachePath)) {
        throw new RuntimeException('Thư mục cache HTMLPurifier không có quyền ghi: ' . $cachePath);
    }
    $config->set('Cache.SerializerPath', $cachePath);
    $config->set('Cache.SerializerPermissions', 0755);
    // $config->set('HTML.Doctype', 'HTML 4.01 Transitional'); // Đặt nếu cần, hoặc bỏ qua

    // --- B. Thiết lập các tùy chỉnh cơ bản khác (không liên quan đến định nghĩa HTML) ---
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('HTML.Nofollow', true);
    $config->set('HTML.TargetBlank', true); // Ví dụ: có cần target blank cho mọi link không
    $config->set('HTML.TargetNoopener', true); // Bảo mật cho target
    $config->set('HTML.TargetNoreferrer', true); // Bảo mật cho target
    $config->set('HTML.ForbiddenAttributes', array('onabort', 'onafterprint', 'onbeforeprint', 'onbeforeunload', 'onblur', 'onchange', 'onclick', 'ondblclick', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onfocus', 'onhashchange', 'onload', 'onmessage', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onoffline', 'ononline', 'onpagehide', 'onpageshow', 'onpopstate', 'onresize', 'onscroll', 'onsearch', 'onselect', 'onsubmit', 'onunload'));

    // *** ĐỀ XUẤT THÊM HTML.MaxImgLength Ở ĐÂY ***
    // Giới hạn kích thước tối đa (chiều rộng/cao) cho thuộc tính width/height của thẻ <img>
    // Giúp ngăn chặn tấn công "image crash" bằng cách dùng ảnh kích thước quá lớn.
    // Giá trị là số nguyên (pixel). Đặt là null để tắt.
    $config->set('HTML.MaxImgLength', 1600); // Ví dụ: giới hạn 1600px    
    
    // --- C. Tùy chỉnh CSS Được phép ---
    // (Giữ nguyên cấu hình CSS của bạn)
    $allowedCssProperties = [
        'font-weight', 'font-style', 'text-decoration', 'color', 'background-color',
        'text-align', 'text-indent',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'list-style-type',
        'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
        'border-width', 'border-style', 'border-color',
        'border-collapse', 'border-spacing', 'caption-side', 'vertical-align',
    ];
    $config->set('CSS.AllowedProperties', $allowedCssProperties);
    
    // *** ĐỀ XUẤT THÊM CSS.MaxImgLength Ở ĐÂY ***
    // Giới hạn kích thước tối đa cho thuộc tính CSS width/height KHI áp dụng cho thẻ <img>.
    // Cũng giúp ngăn chặn "image crash attack" qua CSS (ví dụ: style="width: 99999px").
    // Giá trị là chuỗi có đơn vị (px, pt, cm, in,...). Chỉ các đơn vị tuyệt đối và px được phép.
    // Đặt là null để tắt. Nên đặt giá trị tương ứng với HTML.MaxImgLength.
    $config->set('CSS.MaxImgLength', '1600px'); // Ví dụ: giới hạn 1600px    
    
    // $config->set('CSS.AllowImportant', false); // Giữ mặc định
    // $config->set('CSS.AllowTricky', false); // Giữ mặc định

    // --- D. Xử lý URI và Nội dung nhúng ---
    // (Giữ nguyên)
    // Ví dụ iframe (nếu cần kích hoạt HTML.SafeIframe trước)
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%'); // chấp nhận các video từ YouTube & Vimeo


    // --- E. Chuẩn hóa và Đầu ra ---
    $config->set('HTML.TidyLevel', 'medium');
    // $config->set('Output.TidyFormat', true); // Bật nếu muốn Tidy định dạng output (cần Tidy extension)

    // --- F. *** QUAN TRỌNG: Tùy chỉnh Định nghĩa HTML Nâng cao *** ---
    // 1. Đặt ID và Revision để Purifier biết định nghĩa đã bị thay đổi
    //    Điều này RẤT QUAN TRỌNG để caching hoạt động đúng.
    //    Thay đổi DefinitionRev mỗi khi bạn chỉnh sửa phần tùy chỉnh này.
    // HTML.DefinitionRev phải được tăng lên mỗi khi phần định nghĩa trong if ($def = ...) thay đổi, để đảm bảo cache được cập nhật
    $config->set('HTML.DefinitionID', 'my-custom-html-def-v1'); // Đặt ID duy nhất
    $config->set('HTML.DefinitionRev', 2); // Bắt đầu với phiên bản 1

    // 2. Lấy đối tượng Definition (đánh dấu là có thể bị thay đổi)
    //    Việc gọi getHTMLDefinition() SAU KHI đặt DefinitionID/Rev là an toàn.
    if ($def = $config->maybeGetRawHTMLDefinition()) {
        // 3. Thêm các phần tử mong muốn vào định nghĩa

        // figure: Là thẻ block, cho phép chứa 'Flow' content (hầu hết các thẻ block và inline),
        // thuộc nhóm thuộc tính 'Common' (id, class, style, title, lang, dir).
        $def->addElement('figure', 'Block', 'Flow', 'Common');

        // figcaption: Là thẻ block, chỉ nên chứa 'Inline' content (text và các thẻ inline khác),
        // thuộc nhóm 'Common'.
        $def->addElement('figcaption', 'Block', 'Inline', 'Common');

        // details: Là thẻ block, nội dung cho phép là một 'summary' bắt buộc, theo sau bởi 'Flow' content.
        // Thuộc nhóm 'Common', và có thêm thuộc tính 'open' (boolean).
        $details = $def->addElement('details', 'Block', 'Required: summary | Flow', 'Common');
        $details->attr['open'] = 'Bool'; // Thêm thuộc tính 'open'

        // summary: Là thẻ block (theo ngữ cảnh của details), cho phép chứa 'Heading' hoặc 'Inline' content.
        // Thuộc nhóm 'Common'.
        // Lưu ý: Mô hình nội dung của summary phức tạp hơn một chút,
        // nhưng 'Inline' là đủ cho hầu hết trường hợp.
        $def->addElement('summary', 'Block', 'Inline', 'Common');

        // (Tùy chọn) Bạn cũng có thể thêm các thuộc tính tùy chỉnh vào thẻ hiện có tại đây
        // Ví dụ: Cho phép thuộc tính 'data-custom' trên thẻ <p>
        // $p_tag = $def->info['p'];
        // $p_tag->attr['data-custom'] = 'Text';
    } 

    return $config;
}
