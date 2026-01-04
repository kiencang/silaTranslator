<?php
// Trường hợp muốn điều chỉnh CSS cho dịch PDF thì dùng thêm mã này
// ĐANG KHÔNG DÙNG
                    // --- Sử dụng DOMDocument để thêm CSS ---
                    $dom = new DOMDocument();
                    // Sử dụng @ để ẩn lỗi/cảnh báo nếu HTML từ API không hoàn toàn chuẩn
                    // LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD cố gắng ngăn DOMDocument tự thêm thẻ bao ngoài
                    // nếu Gemini chỉ trả về một đoạn snippet. Quan trọng!
                    // Thêm encoding UTF-8 vào đầu để xử lý đúng ký tự tiếng Việt
                    libxml_use_internal_errors(true); // Bật chế độ bắt lỗi nội bộ của libxml
                    if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $geminiHtmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
                         // Nếu loadHTML thất bại hoàn toàn, có thể log lỗi và dùng nội dung gốc
                         error_log("DOMDocument loadHTML failed for file: " . $originalFilename);
                         $finalHtmlToSave = $geminiHtmlContent; // Dự phòng: Lưu nội dung gốc
                         libxml_clear_errors(); // Xóa lỗi đã bắt
                    } else {
                         libxml_clear_errors(); // Xóa lỗi nếu có (ví dụ lỗi HTML không chuẩn nhưng vẫn parse được)
                         $headTags = $dom->getElementsByTagName('head');

                         if ($headTags->length > 0) {
                             $headNode = $headTags->item(0); // Lấy thẻ <head> đầu tiên

                             // Tạo thẻ <style> mới
                             $styleNode = $dom->createElement('style');

                             // Định nghĩa CSS tùy chỉnh cần thêm
                            $customCSS = <<<CSS
                                    
                                  /* --- CSS tùy chỉnh được thêm bởi PHP --- */
                                  body p {
                                    font-size: 20px !important; /* tăng kích cỡ font chữ */
                                  } 
                                    
                                  body ul li {
                                    font-size: 20px !important; /* tăng kích cỡ font chữ */
                                  }  
                                      
                                  body ol li {
                                    font-size: 20px !important; /* tăng kích cỡ font chữ */
                                  }                                    
                                    
                                  body h2 {
                                    font-size: 34px !important; /* tăng kích cỡ font chữ */
                                  }
                                      
                                  body h3 {
                                    font-size: 28px !important; /* tăng kích cỡ font chữ */
                                  }  
                                      
                            CSS;
                            
                            // Thêm nội dung CSS vào thẻ <style>
                            $styleNode->appendChild($dom->createTextNode($customCSS));

                            // Chèn thẻ <style> mới vào *cuối* thẻ <head> hiện có
                            $headNode->appendChild($styleNode);
                             
                            // Lấy lại toàn bộ nội dung HTML đã được sửa đổi
                            $htmlWithEntities = $dom->saveHTML();

                            // Loại bỏ phần <?xml encoding="UTF-8"> đã thêm lúc load
                            $htmlWithEntities = str_replace('<?xml encoding="UTF-8">', '', $htmlWithEntities);
                            // Đảm bảo có DOCTYPE nếu bị mất
                            if (stripos($htmlWithEntities, '<!DOCTYPE html>') === false) {
                                $htmlWithEntities = "<!DOCTYPE html>\n" . $htmlWithEntities;
                            }

                            // **** GIẢI MÃ HTML ENTITIES ****
                            // Sử dụng ENT_QUOTES để giải mã cả dấu nháy đơn/kép
                            // ENT_HTML5 là tốt nhất cho HTML hiện đại
                            $finalHtmlToSave = html_entity_decode($htmlWithEntities, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                         } else {
                            // Trường hợp không tìm thấy thẻ <head> (Gemini chỉ trả về phần body, rất hiếm khi xảy ra)
                            // -> Quay lại phương án tạo cấu trúc HTML hoàn chỉnh
                            error_log("Không tìm thấy thẻ <head> trong HTML từ Gemini cho file: " . $originalFilename . ". Tạo cấu trúc HTML mới.");

                            $pageTitle = htmlspecialchars($originalFilename);
                            $cssBlock = <<<CSS
                                <style>
                                  body p {
                                    font-size: 20px !important; /* tăng kích cỡ font chữ */
                                  } 
                                    
                                  body h2 {
                                    font-size: 34px !important; /* tăng kích cỡ font chữ */
                                  }
                                      
                                  body h3 {
                                    font-size: 28px !important; /* tăng kích cỡ font chữ */
                                  } 
                                </style>
                            CSS;

                            $finalHtmlToSave = <<<HTML
                            <!DOCTYPE html>
                            <html lang="vi">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>{$pageTitle}</title>
                            {$cssBlock}
                            </head>
                            <body>
                            {$geminiHtmlContent}
                            </body>
                            </html>
                            HTML;
                         }
                    }
                    // --- Kết thúc xử lý DOM ---     
