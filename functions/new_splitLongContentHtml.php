<?php

/**
 * Ước tính số từ trong một chuỗi văn bản thuần túy.
 * Đã được đơn giản hóa, có thể cần cải thiện cho các ngôn ngữ phức tạp hơn.
 *
 * @param string $text Văn bản thuần túy (nên là UTF-8).
 * @return int Số lượng từ ước tính.
 */
function estimateWordCountText(string $text): int
{
    if (empty(trim($text))) {
        return 0;
    }
    // Chuẩn hóa khoảng trắng (bao gồm cả non-breaking space UTF-8 và các loại khác)
    // và loại bỏ dấu câu ở đầu/cuối để tránh đếm sai
    $normalizedText = preg_replace('/[\s\p{Z}\p{C}]+/u', ' ', trim($text));
    // Giữ lại dấu câu trong quá trình chuẩn hóa, chỉ loại bỏ ở biên cuối cùng
    // $normalizedText = trim($normalizedText, " \t\n\r\0\x0B.,;:!?()[]{}<>\"'"); // Loại bỏ tạm thời để đếm từ đơn giản hơn
    if (empty($normalizedText)) {
        return 0;
    }
    // Đếm từ bằng cách tách theo khoảng trắng
    $words = preg_split('/[\s\p{Z}\p{P}]+/u', $normalizedText, -1, PREG_SPLIT_NO_EMPTY); // Tách bằng khoảng trắng hoặc dấu câu
    return count($words); // Đếm số phần tử không rỗng
}


/**
 * Đếm số từ ước tính trong một nút DOM và các nút con của nó (đệ quy).
 * Chỉ đếm các nút văn bản, bỏ qua script, style.
 *
 * @param DOMNode $node Nút bắt đầu đếm.
 * @return int Tổng số từ ước tính.
 */
function countWordsInNodeRecursive(DOMNode $node): int
{
    $wordCount = 0;

    // Không đếm trong script hoặc style
    if ($node instanceof DOMElement && ($node->nodeName === 'script' || $node->nodeName === 'style')) {
        return 0;
    }

    // Nếu là text node không phải là khoảng trắng thuần túy, đếm từ trong đó
    if ($node instanceof DOMText && !trim($node->nodeValue) == '') {
        $wordCount += estimateWordCountText($node->nodeValue);
    }

    // Đệ quy đếm cho các nút con
    if ($node->hasChildNodes()) {
        foreach ($node->childNodes as $child) {
            $wordCount += countWordsInNodeRecursive($child);
        }
    }

    return $wordCount;
}


/**
 * Hàm đệ quy duyệt cây DOM để tìm điểm chia.
 * Nó sửa đổi các biến được truyền vào bằng tham chiếu.
 *
 * @param DOMNode $currentNode Nút hiện tại đang được duyệt.
 * @param int $targetWordCount Ngưỡng số từ để tìm điểm chia.
 * @param int &$wordCountSoFar Tham chiếu: Tổng số từ đã duyệt đến hiện tại.
 * @param DOMNode|null &$lastGoodSplitPoint Tham chiếu: Nút block/br cuối cùng gặp phải trước khi vượt ngưỡng.
 * @param DOMNode|null &$foundSplitPointNode Tham chiếu: Nút kết quả (nút cần chia *trước* nó).
 * @param array $blockTags Danh sách các thẻ block ưu tiên.
 */
function traverseAndFindSplitPoint(
    DOMNode $currentNode,
    int $targetWordCount,
    int &$wordCountSoFar,
    ?DOMNode &$lastGoodSplitPoint,
    ?DOMNode &$foundSplitPointNode,
    array $blockTags
): void {
    // Nếu đã tìm thấy điểm chia, không cần duyệt tiếp nhánh này
    if ($foundSplitPointNode !== null) {
        return;
    }

    $nodeWordCount = 0;
    $isBlockOrBr = false;

    // 1. Xử lý nút hiện tại
    if ($currentNode instanceof DOMText && !trim($currentNode->nodeValue) == '') {
        $nodeWordCount = estimateWordCountText($currentNode->nodeValue);
    } elseif ($currentNode instanceof DOMElement) {
        $nodeNameLower = strtolower($currentNode->nodeName);
        // Bỏ qua script/style hoàn toàn
        if ($nodeNameLower === 'script' || $nodeNameLower === 'style') {
            return; // Không duyệt con của script/style
        }
        if (in_array($nodeNameLower, $blockTags) || $nodeNameLower === 'br') {
            $isBlockOrBr = true;
            // Chỉ coi là điểm chia *tiềm năng* nếu đã có nội dung trước đó
            if ($wordCountSoFar > 0) {
                 $lastGoodSplitPoint = $currentNode;
            }
        }
    }

    // 2. Kiểm tra ngưỡng *trước* khi thêm word count của nút hiện tại (nếu là text)
    // Hoặc ngay khi gặp thẻ block/br (nếu nó được chọn làm điểm chia)
    // Mục tiêu là chia *trước* nút làm vượt ngưỡng hoặc thẻ block/br
    if ($wordCountSoFar > 0 && // Chỉ chia nếu đã có nội dung
        ( ($nodeWordCount > 0 && ($wordCountSoFar + $nodeWordCount) > $targetWordCount) // Thêm text node này sẽ vượt ngưỡng
          || ($isBlockOrBr && $wordCountSoFar >= $targetWordCount * 0.6) // Hoặc gặp thẻ block khi đã đủ một lượng từ kha khá (vd: 60% target)
                                                                           // để tránh chia quá sớm chỉ vì 1 thẻ <br> hoặc <p> trống
        )
       )
    {
        // Đã đến lúc chia
        if ($lastGoodSplitPoint !== null) {
            // Ưu tiên chia trước thẻ block/br gần nhất đã gặp
             // Kiểm tra xem lastGoodSplitPoint có phải là tổ tiên của currentNode không
             // Nếu là tổ tiên, việc chia trước nó không hợp lý, nên dùng currentNode
             $tempNode = $currentNode;
             $isAncestor = false;
             while($tempNode->parentNode) {
                 if ($tempNode->parentNode === $lastGoodSplitPoint) {
                     $isAncestor = true;
                     break;
                 }
                 $tempNode = $tempNode->parentNode;
                 // Dừng nếu lên đến gốc hoặc quá cao
                 if (!$tempNode || $tempNode instanceof DOMDocument) break;
             }

             if (!$isAncestor) {
                $foundSplitPointNode = $lastGoodSplitPoint;
             } else {
                 // Nếu lastGoodSplitPoint là tổ tiên, chia trước nút hiện tại có vẻ hợp lý hơn
                 $foundSplitPointNode = $currentNode;
             }

        } else {
            // Nếu không có thẻ block nào trước đó, chia trước nút text hiện tại
            $foundSplitPointNode = $currentNode;
        }
        return; // Dừng duyệt nhánh này
    }

    // 3. Cộng dồn word count (chỉ từ text node)
    if ($nodeWordCount > 0) {
        $wordCountSoFar += $nodeWordCount;
    }

    // 4. Nếu nút hiện tại là thẻ block/br, cập nhật nó là điểm chia tiềm năng mới nhất
    // (Ghi đè lên lastGoodSplitPoint cũ)
    if ($isBlockOrBr) {
         $lastGoodSplitPoint = $currentNode; // Cập nhật sau khi kiểm tra ngưỡng
    }


    // 5. Duyệt các nút con (đệ quy)
    if ($currentNode->hasChildNodes()) {
        foreach ($currentNode->childNodes as $child) {
            traverseAndFindSplitPoint($child, $targetWordCount, $wordCountSoFar, $lastGoodSplitPoint, $foundSplitPointNode, $blockTags);
            // Nếu lệnh gọi đệ quy đã tìm thấy điểm chia, dừng duyệt các anh em còn lại
            if ($foundSplitPointNode !== null) {
                return;
            }
        }
    }
}


/**
 * Tìm nút DOM mà *ngay trước nó* nên thực hiện việc chia tách.
 * Sử dụng hàm duyệt đệ quy `traverseAndFindSplitPoint`.
 *
 * @param DOMNode $containerNode Nút chứa nội dung cần duyệt (thường là body).
 * @param int $targetWordCount Ngưỡng số từ để tìm điểm chia (ví dụ: maxWords / 2).
 * @return DOMNode|null Nút mà việc chia nên xảy ra *trước* nó, hoặc null nếu không tìm thấy điểm chia hợp lý.
 */
function findSplitPointNodeRecursive(DOMNode $containerNode, int $targetWordCount): ?DOMNode
{
    $wordCountSoFar = 0;
    $lastGoodSplitPoint = null;
    $foundSplitPointNode = null;
    $blockTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'section', 'article', 'aside', 'figure', 'figcaption', 'blockquote', 'ul', 'ol', 'li', 'table', 'hr', 'pre']; // Danh sách thẻ block ưu tiên

    // Bắt đầu duyệt đệ quy từ các con của containerNode
     if ($containerNode->hasChildNodes()) {
        foreach ($containerNode->childNodes as $child) {
            traverseAndFindSplitPoint($child, $targetWordCount, $wordCountSoFar, $lastGoodSplitPoint, $foundSplitPointNode, $blockTags);
            if ($foundSplitPointNode !== null) {
                break; // Dừng ngay khi tìm thấy điểm chia đầu tiên
            }
        }
    }

    return $foundSplitPointNode;
}


// --- Các hàm còn lại giữ nguyên: ---
// moveNodesAfter
// saveInnerHtml
// splitHtmlOnceDOM (sẽ gọi findSplitPointNodeRecursive thay vì findSplitPointNode)
// splitHtmlRecursively (sẽ gọi countWordsInNodeRecursive thay vì countWordsInNode)

/**
 * Di chuyển tất cả các nút từ một điểm bắt đầu ($startNode) đến cuối tài liệu nguồn
 * sang một tài liệu đích. Hàm này sửa đổi cả $sourceDoc và $targetDoc.
 * Nó cố gắng bảo tồn cấu trúc cơ bản khi di chuyển.
 *
 * @param DOMDocument $sourceDoc Tài liệu nguồn.
 * @param DOMDocument $targetDoc Tài liệu đích.
 * @param DOMNode $startNode Nút đầu tiên cần di chuyển (nút này và các nút sau nó).
 */
function moveNodesAfter(DOMDocument $sourceDoc, DOMDocument $targetDoc, DOMNode $startNode): bool
{
    // Tìm hoặc tạo nút gốc hợp lệ trong tài liệu đích (ví dụ: body)
    $targetBody = $targetDoc->getElementsByTagName('body')->item(0);
    if (!$targetBody) {
        // Thử tìm html tag trước
        $targetHtml = $targetDoc->getElementsByTagName('html')->item(0);
        if (!$targetHtml) {
            $targetHtml = $targetDoc->createElement('html');
            $targetDoc->appendChild($targetHtml);
        }
        $targetBody = $targetDoc->createElement('body');
        $targetHtml->appendChild($targetBody);
    }

    $currentNode = $startNode;
    $movedSomething = false;

    // Vòng lặp chính: Di chuyển nút hiện tại và các anh em của nó
    while ($currentNode) {
        $parentNode = $currentNode->parentNode; // Lấy parent TRƯỚC KHI di chuyển
        $nextNode = $currentNode->nextSibling; // Lấy node tiếp theo cùng cấp TRƯỚC KHI di chuyển

        try {
            // Quan trọng: Import node vào tài liệu đích
            $importedNode = $targetDoc->importNode($currentNode, true); // Deep import
            // Nối vào body đích. Nếu cần giữ cấu trúc, logic sẽ phức tạp hơn.
            $targetBody->appendChild($importedNode);
            $movedSomething = true;

            // Xóa nút gốc khỏi tài liệu nguồn
            if ($parentNode) {
                $parentNode->removeChild($currentNode);
            }
        } catch (\DOMException $e) {
            error_log("DOMException while moving node: " . $e->getMessage() . " Node name: " . ($currentNode->nodeName ?? 'N/A'));
            // Bỏ qua node lỗi và thử tiếp tục
        } catch (\Throwable $e) {
             error_log("General error while moving node: " . $e->getMessage());
             // Bỏ qua node lỗi và thử tiếp tục
        }

        $currentNode = $nextNode; // Di chuyển đến nút anh em kế tiếp
    }

    // Logic di chuyển các cấp cha (nếu cần) phức tạp và có thể không cần thiết
    // nếu việc chọn điểm chia đã tốt.

    return $movedSomething;
}


/**
 * Lưu nội dung HTML từ một nút DOM (thường là body) thành chuỗi.
 * Tránh thêm thẻ html/head/body không mong muốn.
 *
 * @param DOMDocument $doc Tài liệu DOM.
 * @param string $containerTag Tên thẻ chứa nội dung (thường là 'body').
 * @return string Chuỗi HTML.
 */
function saveInnerHtml(DOMDocument $doc, string $containerTag = 'body'): string
{
    $container = $doc->getElementsByTagName($containerTag)->item(0);
    $html = '';
    if ($container && $container->hasChildNodes()) {
        foreach ($container->childNodes as $child) {
            // saveHTML(child) sẽ trả về HTML của node đó và con của nó
             $html .= $doc->saveHTML($child);
        }
    }
    return trim($html);
}

/**
 * Chia nội dung HTML một lần tại một điểm chia tự nhiên dựa trên DOM và word count.
 *
 * @param string $htmlContent HTML đầu vào (phải là UTF-8 hợp lệ).
 * @param int $targetWordCount Ngưỡng số từ ước tính để tìm điểm chia.
 *
 * @return array Mảng chứa hai chuỗi HTML đã chia, hoặc mảng chỉ chứa HTML gốc nếu không thể chia.
 */
function splitHtmlOnceDOM(string $htmlContent, int $targetWordCount): array
{
     if (empty(trim($htmlContent)) || $targetWordCount <= 0) {
        return [$htmlContent];
    }

    $doc1 = new DOMDocument();
    $doc2 = new DOMDocument(); // Tài liệu cho phần thứ hai

    // Đảm bảo xử lý UTF-8 đúng cách
    $htmlToLoad = mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'); // Chuyển đổi entities để DOM xử lý tốt hơn
    // Thêm cấu trúc cơ bản nếu thiếu để loadHTML ổn định
    if (stripos($htmlToLoad, '<!DOCTYPE') === false && stripos($htmlToLoad, '<html') === false) {
         $htmlToLoad = "<!DOCTYPE html><html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head><body>" . $htmlToLoad . "</body></html>";
    }


    libxml_use_internal_errors(true);
    // Thử load với các cờ mặc định hơn, bỏ NOBLANKS có thể giữ lại các text node quan trọng
    if (!$doc1->loadHTML($htmlToLoad, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED)) {
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        error_log("Không thể phân tích cú pháp HTML đầu vào bằng DOMDocument. Lỗi: " . print_r($errors, true));
        return [$htmlContent];
    }
    libxml_clear_errors();
    libxml_use_internal_errors(false);

    // Tìm nút body để bắt đầu duyệt
    $bodyNode = $doc1->getElementsByTagName('body')->item(0);
    if (!$bodyNode) {
        error_log("Không tìm thấy thẻ body trong DOM sau khi load.");
        // Thử lấy documentElement nếu không có body
        $bodyNode = $doc1->documentElement;
        if (!$bodyNode) {
             return [$htmlContent];
        }
    }

    // --- Tìm điểm chia bằng phương pháp đệ quy mới ---
    $splitBeforeNode = findSplitPointNodeRecursive($bodyNode, $targetWordCount);

    if (!$splitBeforeNode) {
        // Không tìm thấy điểm chia hợp lý
        return [$htmlContent]; // Không chia
    }
     // Đảm bảo splitBeforeNode có parentNode hợp lệ trước khi di chuyển
     if (!$splitBeforeNode->parentNode) {
          error_log("Điểm chia được tìm thấy không có nút cha hợp lệ. Không thể chia.");
          return [$htmlContent];
     }

    // --- Thực hiện chia DOM ---
    try {
        $moved = moveNodesAfter($doc1, $doc2, $splitBeforeNode);
        if (!$moved) {
             // Không di chuyển được node nào, có thể là lỗi hoặc điểm chia ở cuối
             error_log("moveNodesAfter không di chuyển được node nào.");
             return [$htmlContent];
        }

    } catch (\Throwable $e) {
        error_log("Lỗi trong quá trình di chuyển node DOM: " . $e->getMessage());
        return [$htmlContent]; // Trả về gốc nếu có lỗi
    }

    // --- Lưu kết quả từ DOM về chuỗi HTML ---
    // Cần đảm bảo lưu đúng encoding UTF-8
    $doc1->encoding = 'UTF-8'; // Đặt encoding cho saveHTML
    $doc2->encoding = 'UTF-8';
    $part1Html = saveInnerHtml($doc1, 'body');
    $part2Html = saveInnerHtml($doc2, 'body');

    // Kiểm tra an toàn cuối cùng
    $part1Text = trim(strip_tags($part1Html));
    $part2Text = trim(strip_tags($part2Html));

    if (!empty($part1Text) && !empty($part2Text)) {
        return [trim($part1Html), trim($part2Html)];
    } elseif (!empty($part1Text)) {
        // Chỉ phần 1 có nội dung text
        return [trim($part1Html)];
    } else {
         // Cả hai phần đều trống text (có thể chỉ có thẻ rỗng, comment...)
         // Hoặc việc chia đã thất bại, trả về gốc
         error_log("Cả hai phần sau khi chia đều có vẻ trống nội dung text.");
        return [$htmlContent];
    }
}

/**
 * Chia nội dung HTML thành nhiều phần nhỏ hơn một cách đệ quy.
 *
 * @param string $htmlContent Nội dung HTML cần chia (UTF-8).
 * @param int $maxWordCountPerPart Ngưỡng số từ tối đa cho mỗi phần kết quả.
 * @return array Mảng chứa các phần nội dung HTML đã được chia, theo đúng thứ tự.
 */
function splitHtmlRecursively(string $htmlContent, int $maxWordCountPerPart = 5000): array
{
    if (empty(trim($htmlContent)) || $maxWordCountPerPart <= 0) {
        return [];
    }

    // --- Ước tính tổng số từ ban đầu bằng cách load DOM ---
    $docCheck = new DOMDocument();
    $htmlToCheck = mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8');
     if (stripos($htmlToCheck, '<!DOCTYPE') === false && stripos($htmlToCheck, '<html') === false) {
         $htmlToCheck = "<!DOCTYPE html><html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'></head><body>" . $htmlToCheck . "</body></html>";
    }

    $initialWordCount = 0; // Giá trị mặc định
    libxml_use_internal_errors(true);
    if ($docCheck->loadHTML($htmlToCheck, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED)) {
        libxml_clear_errors();
         libxml_use_internal_errors(false);
        $bodyNodeCheck = $docCheck->getElementsByTagName('body')->item(0);
         if ($bodyNodeCheck) {
             // Sử dụng hàm đếm đệ quy mới
             $initialWordCount = countWordsInNodeRecursive($bodyNodeCheck);
         } else {
             // Fallback nếu không có body
              $initialWordCount = estimateWordCountText(strip_tags($htmlContent));
         }
    } else {
         $errors = libxml_get_errors();
         libxml_clear_errors();
         libxml_use_internal_errors(false);
         $initialWordCount = estimateWordCountText(strip_tags($htmlContent)); // Ước tính thô
         error_log("Split check: Failed to load HTML for initial word count. Using rough estimate. Errors: " . print_r($errors, true));
    }
    unset($docCheck); // Giải phóng bộ nhớ


    // --- Trường hợp cơ sở của đệ quy ---
    if ($initialWordCount <= $maxWordCountPerPart) {
        return [trim($htmlContent)];
    }

    // --- Bước đệ quy ---
    // Gọi splitHtmlOnceDOM với target là maxWordCountPerPart.
    $splitResult = splitHtmlOnceDOM($htmlContent, $maxWordCountPerPart);

    // Xử lý kết quả chia
    if (count($splitResult) === 1) {
        // Không thể chia được nữa.
        trigger_error("splitHtmlRecursively: Could not split HTML chunk further despite exceeding word count. Word count: {$initialWordCount}, Max: {$maxWordCountPerPart}", E_USER_NOTICE);
        return $splitResult; // Trả về [$htmlContent]
    } else {
        // Chia thành công thành hai phần: $part1 và $part2
        $part1 = $splitResult[0];
        $part2 = $splitResult[1];

        // Đảm bảo cả hai phần không rỗng trước khi gọi đệ quy
        $finalParts = [];
        if (!empty(trim($part1))) {
             $finalParts = array_merge($finalParts, splitHtmlRecursively($part1, $maxWordCountPerPart));
        } else {
             error_log("Phần 1 bị trống sau khi chia, bỏ qua.");
        }

         if (!empty(trim($part2))) {
             $finalParts = array_merge($finalParts, splitHtmlRecursively($part2, $maxWordCountPerPart));
        } else {
             error_log("Phần 2 bị trống sau khi chia, bỏ qua.");
        }

        return $finalParts;
    }
}