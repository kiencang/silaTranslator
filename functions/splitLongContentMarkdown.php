<?php

/**
 * Ước tính số từ gần đúng trong một chuỗi Markdown.
 * Cố gắng loại bỏ các ký tự định dạng Markdown phổ biến trước khi đếm.
 * Lưu ý: Đây là ước tính, không hoàn toàn chính xác với mọi cú pháp Markdown phức tạp.
 *
 * @param string $markdownContent Nội dung Markdown.
 * @return int Số lượng từ ước tính.
 */
function estimateMarkdownWordCount(string $markdownContent): int
{
    // 1. Loại bỏ các yếu tố Markdown không nên tính là từ hoặc làm sai lệch việc tách từ
    // Sử dụng phương pháp đơn giản hơn, ước chừng để tăng tốc độ xử lý
    $text = $markdownContent;
    // - Link và Image: Giữ lại alt text/link text, loại bỏ URL và dấu ngoặc
    //$text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '$1', $text); // Images -> Alt text
    //$text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);  // Links -> Link text
    // - Code spans và code blocks (đơn giản)
    //$text = preg_replace('/`{1,3}[^`]+`{1,3}/', ' ', $text); // Thay code bằng khoảng trắng
    // - Heading markers
    //$text = preg_replace('/^#+\s+/m', '', $text); // Xóa # ở đầu dòng
    // - List markers
    //$text = preg_replace('/^\s*[-*+]\s+/m', '', $text); // Xóa -, *, + ở đầu dòng
    //$text = preg_replace('/^\s*\d+\.\s+/m', '', $text); // Xóa 1. 2. ở đầu dòng
    // - Blockquotes
    //$text = preg_replace('/^>\s+/m', '', $text); // Xóa > ở đầu dòng
    // - Horizontal rules
    //$text = preg_replace('/^[-*_]{3,}\s*$/m', '', $text); // Xóa dòng kẻ ngang
    // - Bold/Italic markers (giữ lại nội dung bên trong)
    //$text = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $text); // **bold__
    //$text = preg_replace('/(\*|_)(.*?)\1/', '$2', $text);   // *italic_

    // 2. Chuẩn hóa khoảng trắng và đếm
    $text = preg_replace('/\s+/', ' ', trim($text)); // Thay nhiều khoảng trắng bằng 1 khoảng trắng
    if (empty($text)) {
        return 0;
    }

    // 3. Đếm từ bằng cách tách theo khoảng trắng
    // str_word_count có thể nhạy cảm với locale và dấu câu trong từ
    // explode là cách đơn giản và nhất quán hơn trong trường hợp này
    return count(explode(' ', $text));
}

/**
 * Tìm vị trí của dòng bắt đầu tiêu đề Markdown (## hoặc ###) gần nhất với điểm giữa.
 *
 * @param string $markdownContent Nội dung Markdown.
 * @param int $midpoint Vị trí điểm giữa (tính theo ký tự).
 * @return int Vị trí bắt đầu của dòng tiêu đề gần nhất (vị trí ký tự '#'), hoặc -1 nếu không tìm thấy.
 */
function findClosestMarkdownHeadingPosition(string $markdownContent, int $midpoint): int
{
    $bestPos = -1;
    $minDistance = PHP_INT_MAX;

    // Regex tìm dòng bắt đầu bằng ## hoặc ### (H2 hoặc H3)
    // /m = multiline mode (xét ^ cho từng dòng)
    $headingPattern = '/^#{2,3}\s+/m';

    if (preg_match_all($headingPattern, $markdownContent, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $pos = $match[1]; // Vị trí bắt đầu của thẻ (vị trí của '#')
            $distance = abs($pos - $midpoint);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestPos = $pos;
            }
        }
    }

    return $bestPos;
}

/**
 * Tìm vị trí bắt đầu của một đoạn văn bản (sau một dòng trống) gần nhất với điểm giữa.
 * Đoạn văn được định nghĩa là bắt đầu sau ít nhất một dòng trống.
 *
 * @param string $markdownContent Nội dung Markdown.
 * @param int $midpoint Vị trí điểm giữa (tính theo ký tự).
 * @return int Vị trí bắt đầu của ký tự đầu tiên của đoạn văn gần nhất, hoặc -1 nếu không tìm thấy.
 */
function findClosestMarkdownParagraphPosition(string $markdownContent, int $midpoint): int
{
    $bestPos = -1;
    $minDistance = PHP_INT_MAX;

    // Regex tìm vị trí bắt đầu của một dòng không trống sau một hoặc nhiều dòng trống.
    // (\n\s*){2,} : Tìm ít nhất 2 dấu xuống dòng, có thể có khoảng trắng ở giữa (đảm bảo có ít nhất 1 dòng trống)
    // ([^\s\n]) : Bắt ký tự đầu tiên không phải khoảng trắng và không phải xuống dòng của đoạn mới.
    // Sử dụng PREG_OFFSET_CAPTURE để lấy vị trí của group 2 (ký tự đầu tiên).
    // /m = multiline mode
    $paragraphPattern = '/(?:\n\s*){2,}([^\s\n])/m'; // Thay đổi: dùng (?: ) non-capturing group

    // Cần tìm vị trí bắt đầu của đoạn *sau* dòng trống
    // Pattern này tìm 2+ newline (\n) theo sau bởi ký tự không phải whitespace (\S)
    // Vị trí của \S chính là điểm bắt đầu đoạn mới.
    // $paragraphPattern = '/\n\s*\n(\S)/m'; // Cách khác đơn giản hơn: dòng trống + ký tự đầu đoạn


    if (preg_match_all($paragraphPattern, $markdownContent, $matches, PREG_OFFSET_CAPTURE)) {
        // Chúng ta muốn vị trí của ký tự đầu tiên của đoạn mới (capture group 1)
        foreach ($matches[1] as $match) {
            $pos = $match[1]; // Vị trí của ký tự đầu tiên không trống
            $distance = abs($pos - $midpoint);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestPos = $pos;
            }
        }
    }

    return $bestPos;
}


/**
 * Tìm vị trí kết thúc câu gần nhất với điểm giữa trong nội dung Markdown.
 * Ưu tiên các dấu '.', '!', '?'. Cần cẩn thận hơn để tránh split trong URL, code...
 * Lưu ý: Regex này đơn giản, có thể không hoàn hảo trong mọi trường hợp Markdown phức tạp.
 *
 * @param string $markdownContent Nội dung Markdown.
 * @param int $midpoint Vị trí điểm giữa (tính theo ký tự).
 * @return int Vị trí *sau* dấu kết thúc câu gần nhất, hoặc -1 nếu không tìm thấy.
 */
function findClosestMarkdownSentenceEndPosition(string $markdownContent, int $midpoint): int
{
    $bestPos = -1;
    $minDistance = PHP_INT_MAX;

    // Regex tìm dấu kết thúc câu (. ! ?) theo sau bởi khoảng trắng hoặc cuối dòng/chuỗi.
    // Tránh các trường hợp phổ biến như dấu chấm trong URL hoặc tên file bằng cách
    // yêu cầu phải có khoảng trắng hoặc hết dòng/chuỗi ngay sau đó.
    // (?<=[.!?]) : Lookbehind - Đảm bảo vị trí là sau dấu câu.
    // (?=\s|[\r\n]|\Z) : Lookahead - Đảm bảo theo sau là khoảng trắng, newline hoặc cuối chuỗi (\Z).
    // /m = multiline
    $sentenceEndPattern = '/(?<=[.!?])(?=\s|[\r\n]|\Z)/m';

    // Lấy text thuần túy để giảm sai sót khi tìm dấu câu trong link/code
    // Đây là một cách trade-off, vị trí có thể lệch nhẹ so với gốc nếu có nhiều formatting bị xóa
    // $plainText = preg_replace('/\[([^\]]+)\]\([^)]+\)|`[^`]+`/', ' ', $markdownContent);
    // if (preg_match_all($sentenceEndPattern, $plainText, $matches, PREG_OFFSET_CAPTURE)) { ... }
    // Quyết định: Chạy trên markdown gốc để giữ vị trí chính xác, chấp nhận rủi ro nhỏ regex bắt nhầm.

    if (preg_match_all($sentenceEndPattern, $markdownContent, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            // Vị trí trả về bởi lookbehind đã là *sau* dấu câu
            $pos = $match[1];
            $distance = abs($pos - $midpoint);

            // Kiểm tra đơn giản tránh tách ngay sau số (ví dụ: 1.) hoặc trong tên viết tắt (ví dụ: U.S.A.)
            // Kiểm tra ký tự trước dấu câu (tại pos - 2)
            if ($pos >= 2) {
                $charBeforePunct = $markdownContent[$pos - 2];
                // Nếu là số hoặc chữ cái viết hoa (khả năng là viết tắt) -> bỏ qua điểm tách này
                if (is_numeric($charBeforePunct) || (ctype_upper($charBeforePunct) && isset($markdownContent[$pos - 3]) && $markdownContent[$pos - 3] === '.')) {
                   continue;
                }
            }


            if ($distance < $minDistance) {
                $minDistance = $distance;
                $bestPos = $pos;
            }
        }
    }
    return $bestPos;
}


/**
 * Chia nội dung Markdown dài thành hai phần một cách thông minh.
 *
 * Ưu tiên chia trước tiêu đề ## hoặc ### gần điểm giữa nhất.
 * Nếu không có, chia trước đoạn văn mới (sau dòng trống) gần điểm giữa nhất.
 * Nếu không có, chia sau dấu câu (.!?) gần điểm giữa nhất.
 * Nếu không thể chia thông minh, sẽ trả về mảng chứa nội dung gốc.
 *
 * @param string $markdownContent Nội dung Markdown đầu vào.
 * @param int $wordThreshold Ngưỡng số từ ước tính để bắt đầu chia (mặc định 5000).
 * @return array Mảng chứa hai phần nội dung đã chia, hoặc mảng chứa một phần tử là nội dung gốc nếu không cần chia hoặc không thể chia thông minh.
 */
function splitMarkdownContentIntelligently(string $markdownContent, int $wordThreshold = 5000): array
{
    $wordCount = estimateMarkdownWordCount($markdownContent);

    // 1. Kiểm tra xem có cần chia không
    if ($wordCount <= $wordThreshold) {
        return [$markdownContent]; // Trả về mảng chứa nội dung gốc
    }

    $contentLength = strlen($markdownContent);
    // Tránh lỗi nếu nội dung quá ngắn dù word count cao (ít khả năng)
    if ($contentLength < 10) { // Ngưỡng ký tự tối thiểu tùy ý
        return [$markdownContent];
    }
    $midpoint = (int)($contentLength / 2);
    $splitPos = -1;

    // 2. Ưu tiên 1: Tìm tiêu đề ## hoặc ### gần điểm giữa nhất
    $splitPos = findClosestMarkdownHeadingPosition($markdownContent, $midpoint);

    // 3. Ưu tiên 2: Nếu không có heading, tìm đoạn văn mới gần điểm giữa nhất
    if ($splitPos === -1) {
        $splitPos = findClosestMarkdownParagraphPosition($markdownContent, $midpoint);
    }

    // 4. Ưu tiên 3: Nếu không có điểm chia cấu trúc, tìm dấu câu gần điểm giữa nhất
    if ($splitPos === -1) {
        $sentenceEndPos = findClosestMarkdownSentenceEndPosition($markdownContent, $midpoint);
        // Đảm bảo vị trí hợp lệ (không phải ngay đầu hoặc cuối)
        if ($sentenceEndPos > 0 && $sentenceEndPos < $contentLength) {
             $splitPos = $sentenceEndPos;
        }
    }

    // 5. Thực hiện chia nếu tìm được vị trí hợp lệ
    if ($splitPos > 0 && $splitPos < $contentLength) {
        // Kiểm tra xem vị trí tách có quá gần đầu/cuối không
        $minLengthRatio = 0.20; // Yêu cầu mỗi phần ít nhất 20% độ dài
        if ($splitPos < $contentLength * $minLengthRatio || $splitPos > $contentLength * (1 - $minLengthRatio)) {
            // Vị trí tách quá gần lề, có thể không tối ưu. Trả về nội dung gốc.
             return [$markdownContent];
        }

        $part1 = substr($markdownContent, 0, $splitPos);
        $part2 = substr($markdownContent, $splitPos);

        // Dọn dẹp khoảng trắng/dòng trống thừa ở đầu phần 2
        $part2 = ltrim($part2, " \t\n\r\0\x0B"); // Xóa cả các loại newline

        // Dọn dẹp khoảng trắng/dòng trống thừa ở cuối phần 1
        $part1 = rtrim($part1, " \t\n\r\0\x0B");

        // Đảm bảo cả hai phần đều có nội dung thực sự sau khi xóa các định dạng cơ bản
        // và không chỉ là khoảng trắng
        $checkPart1 = trim(preg_replace('/^[#>\s*-+]+|[\[\]()*_`!]/m', '', $part1));
        $checkPart2 = trim(preg_replace('/^[#>\s*-+]+|[\[\]()*_`!]/m', '', $part2));

        if (!empty($checkPart1) && !empty($checkPart2)) {
             // Thêm dòng trống vào cuối phần 1 để đảm bảo tách đoạn đúng cách
             // nếu phần 2 không bắt đầu bằng heading hoặc list
             if (!preg_match('/^\s*(#|\*|-|\d+\.)/', $part2)) {
                $part1 .= "\n\n";
             }
             return [$part1, $part2];
        }
    }

    // 6. Nếu không tìm được điểm chia thông minh nào, trả về nội dung gốc
    return [$markdownContent];
}