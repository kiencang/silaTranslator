<?php
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler; // Cần dùng để kiểm tra ảnh
use Symfony\Component\Panther\Exception\WebDriverException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy; // Thêm để chọn phần tử
use Facebook\WebDriver\WebDriverExpectedCondition; // Thêm để đợi điều kiện

/**
 * Sử dụng Symfony Panther để lấy nội dung HTML từ một URL,
 * cải thiện xử lý lazy loading ảnh và kiểm soát cuộn trang vô hạn.
 *
 * @param string $url URL mục tiêu để lấy HTML.
 * @param string $browser Chọn trình duyệt ('chrome' hoặc 'firefox').
 * @param bool $headless Chạy ở chế độ headless (true) hay có giao diện (false). Mặc định là true.
 * @param int $initialWaitInSeconds Số giây chờ SAU KHI yêu cầu trang ban đầu. Mặc định là 5.
 * @param bool $simulateScroll Có mô phỏng cuộn không. Mặc định là true.
 * @param int $maxScrollInteractions Số lần cuộn xuống tối đa. Hữu ích cho trang cuộn vô hạn. Đặt là 0 để cố gắng cuộn hết. Mặc định là 10.
 * @param float $scrollIncrementRatio Tỷ lệ chiều cao viewport sẽ cuộn trong mỗi lần. Ví dụ: 0.8 = 80%. Nhỏ hơn giúp lazy load tốt hơn. Mặc định 0.8.
 * @param int $scrollWaitInMs Số mili giây chờ SAU MỖI LẦN cuộn nhỏ. Tăng lên để chờ lazy load. Mặc định là 1000ms (1 giây).
 * @param int $postScrollWaitInMs Số mili giây chờ SAU KHI cuộn xong toàn bộ (hoặc đạt maxScrollInteractions). Tăng lên để chờ các xử lý cuối. Mặc định là 5000ms (5 giây).
 * @param bool $waitForImages Tùy chọn nâng cao: Cố gắng đợi các ảnh trong viewport tải xong sau mỗi lần cuộn (có thể chậm). Mặc định false.
 * @param int $imageWaitTimeoutMs Thời gian tối đa (ms) để đợi ảnh tải xong trong mỗi lần kiểm tra (nếu $waitForImages là true).
 *
 * @return string|false Trả về chuỗi HTML nếu thành công, ngược lại trả về false.
 */
function fetchHtmlWithPantherImproved(
    string $url,
    string $browser,
    bool $headless = false,
    int $initialWaitInSeconds = 5,
    bool $simulateScroll = true,
    int $maxScrollInteractions = 3, // Giới hạn số lần cuộn
    float $scrollIncrementRatio = 0.8, // Cuộn 80% viewport mỗi lần
    int $scrollWaitInMs = 1000,     // Tăng thời gian chờ sau mỗi cuộn nhỏ
    int $postScrollWaitInMs = 2000, // Tăng thời gian chờ sau khi cuộn xong
    bool $waitForImages = false,     // Tùy chọn đợi ảnh tải xong
    int $imageWaitTimeoutMs = 2000   // Timeout khi đợi ảnh
): string|false {
    //Các echo debug
    //echo "=============================================\n";
    //echo "Hàm fetchHtmlWithPantherImproved được gọi cho URL: " . $url . "\n";
    //echo "Trình duyệt: " . ucfirst($browser) . " | Headless: " . ($headless ? 'Bật' : 'Tắt') . "\n";
    //echo "Chờ ban đầu: " . $initialWaitInSeconds . "s\n";
    //echo "Mô phỏng cuộn: " . ($simulateScroll ? 'Bật' : 'Tắt') . "\n";
    //if ($simulateScroll) {
        //echo "Giới hạn cuộn: " . ($maxScrollInteractions > 0 ? $maxScrollInteractions . " lần" : "Không giới hạn") . "\n";
        //echo "Bước cuộn: " . ($scrollIncrementRatio * 100) . "% viewport\n";
        //echo "Chờ sau mỗi cuộn nhỏ: " . $scrollWaitInMs . "ms\n";
        //echo "Chờ sau khi cuộn xong: " . $postScrollWaitInMs . "ms\n";
        //echo "Chờ ảnh tải xong: " . ($waitForImages ? 'Bật (Timeout: ' . $imageWaitTimeoutMs . 'ms)' : 'Tắt') . "\n";
    //}
    //echo "=============================================\n";


    $client = null;
    $htmlContent = false;

    try {
        // --- Xác định đường dẫn WebDriver (giữ nguyên) ---
        $driverExecutable = ($browser === 'chrome') ? 'chromedriver.exe' : 'geckodriver.exe';
        $driverDir = realpath(dirname(__DIR__ ) . '/drivers');
        if ($driverDir === false) {
             throw new \Exception("LỖI: Thư mục driver không tồn tại hoặc không truy cập được: " . dirname( __DIR__ ) . '/drivers');
        }
        $driverPath = $driverDir . DIRECTORY_SEPARATOR . $driverExecutable;
        if (!file_exists($driverPath)) {
            throw new \Exception("LỖI: Không tìm thấy driver '" . $driverExecutable . "' tại: " . $driverPath);
        }
        //echo "[DEBUG] Đường dẫn Driver đã xác thực: " . $driverPath . "\n";


        // --- Khởi tạo Client (giữ nguyên, có thể tăng chiều cao cửa sổ nếu cần) ---
        $arguments = [];
        $options = [];
        $windowHeight = 1200; // Có thể tăng chiều cao để thấy nhiều ảnh hơn ban đầu

        if ($browser === 'chrome') {
             if ($headless) {
                 $arguments = ['--headless=new', '--disable-gpu', '--no-sandbox', '--window-size=1200,'.$windowHeight];
             } else {
                 $arguments = ['--start-maximized']; // Mở full màn hình nếu không headless
                 $options = ['--window-size=1200,'.$windowHeight]; // Dự phòng nếu maximize lỗi
             }
             $client = Client::createChromeClient($driverPath, $arguments, $options);

        } elseif ($browser === 'firefox') {
             if ($headless) {
                 $arguments = ['-headless', '-width=1200', '-height='.$windowHeight];
             } else {
                  $arguments = ['-width=1200', '-height='.$windowHeight];
             }
              $client = Client::createFirefoxClient($driverPath, $arguments);
        } else {
             throw new \Exception("Trình duyệt không hợp lệ được chọn: " . $browser);
        }


        // --- Thực hiện lấy HTML ---
        //echo "[INFO] Đang truy cập URL: " . $url . "\n";
        $crawler = $client->request('GET', $url);

        //echo "[INFO] Chờ trang tải ban đầu trong " . $initialWaitInSeconds . " giây...\n";
        if ($initialWaitInSeconds > 0) {
             // Thay vì $client->wait, sử dụng wait for navigation/dom ready có thể ổn định hơn
             try {
                // Chờ cho đến khi document.readyState là 'complete' hoặc hết timeout
                $client->wait($initialWaitInSeconds * 1000, 500)->until(function ($client) {
                    return $client->executeScript('return document.readyState === "complete";');
                });
                //echo "[DEBUG] Trang đã hoàn tất tải (document.readyState === 'complete').\n";
             } catch (TimeoutException $e) {
                 //echo "[WARN] Trang chưa đạt readyState 'complete' sau " . $initialWaitInSeconds . " giây. Vẫn tiếp tục...\n";
             }
        }

        // === LOGIC CUỘN CẢI TIẾN ===
        if ($simulateScroll) {
            //echo "[INFO] Đang mô phỏng cuộn trang...\n";
            try {
                $scrollCount = 0;
                $lastHeight = -1;

                // Điều kiện lặp: hoặc không giới hạn cuộn ($maxScrollInteractions <= 0) HOẶC chưa đạt giới hạn
                while ($maxScrollInteractions <= 0 || $scrollCount < $maxScrollInteractions) {
                    $currentHeight = (int)$client->executeScript('return document.body.scrollHeight');
                    $viewportHeight = (int)$client->executeScript('return window.innerHeight');
                    $currentScrollY = (int)$client->executeScript('return window.pageYOffset');

                    // Kiểm tra xem có thể cuộn thêm không
                    // Điều kiện dừng: Chiều cao không đổi VÀ đã cuộn gần hết HOẶC chiều cao không đổi trong nhiều lần lặp (ít hữu ích hơn)
                    if ($currentHeight === $lastHeight && ($currentScrollY + $viewportHeight) >= $currentHeight) {
                        //echo "[DEBUG] Đã cuộn hết hoặc không còn thay đổi chiều cao đáng kể.\n";
                        break;
                    }
                     if ($currentHeight < $lastHeight) {
                          //echo "[DEBUG] Chiều cao trang giảm? Dừng cuộn. Height hiện tại: $currentHeight, trước đó: $lastHeight\n";
                          break; // Dừng nếu chiều cao bị giảm (có thể do lỗi hoặc trang thay đổi bất thường)
                     }


                    $lastHeight = $currentHeight; // Lưu chiều cao trước khi cuộn

                    // Tính toán vị trí cuộn tiếp theo (cuộn một phần viewport)
                    $scrollAmount = $viewportHeight * $scrollIncrementRatio;
                    $scrollTo = $currentScrollY + $scrollAmount;
                    if ($scrollTo > $currentHeight) {
                        $scrollTo = $currentHeight; // Không cuộn quá cuối trang
                    }

                    //echo "[DEBUG] Lần cuộn " . ($scrollCount + 1) . ": Cuộn tới ~" . round($scrollTo) . "/" . $currentHeight . "\n";
                    $client->executeScript("window.scrollTo(0, $scrollTo);");


                    // Đợi sau mỗi lần cuộn nhỏ - **QUAN TRỌNG CHO LAZY LOAD**
                    if ($scrollWaitInMs > 0) {
                        //echo "[DEBUG] Chờ " . $scrollWaitInMs . "ms sau khi cuộn...\n";
                        usleep($scrollWaitInMs * 1000); // Sử dụng usleep cho độ chính xác mili giây tốt hơn $client->wait
                    }

                    // *** [TÙY CHỌN NÂNG CAO] Đợi ảnh trong viewport tải xong ***
                    if ($waitForImages) {
                        //echo "[DEBUG] Đang kiểm tra và chờ ảnh tải xong (Timeout: {$imageWaitTimeoutMs}ms)...\n";
                        try {
                            // Script JS để kiểm tra tất cả ảnh trong viewport hiện tại
                            $jsCheckImagesInView = <<<JS
                                return (function() {
                                    var images = document.querySelectorAll('img');
                                    var viewportHeight = window.innerHeight;
                                    var allLoaded = true;
                                    for (var i = 0; i < images.length; i++) {
                                        var img = images[i];
                                        var rect = img.getBoundingClientRect();
                                        var isInView = (rect.top < viewportHeight && rect.bottom >= 0); // Ảnh đang trong viewport

                                        // Chỉ kiểm tra ảnh trong viewport VÀ chưa hoàn thành tải
                                        // Lưu ý: img.complete có thể true ngay cả khi src là placeholder hoặc bị lỗi 404
                                        // naturalWidth > 0 là dấu hiệu tốt hơn cho ảnh đã thực sự được render
                                        if (isInView && (!img.complete || img.naturalWidth === 0)) {
                                            // Kiểm tra thêm nếu src có vẻ là placeholder (tùy chỉnh nếu cần)
                                            // var src = img.currentSrc || img.src;
                                            // if (src && src.includes('placeholder')) {
                                            //     allLoaded = false;
                                            //     // console.log('Image in view not loaded (placeholder?):', src);
                                            //     break;
                                            // }

                                            // Chỉ cần check complete và naturalWidth là đủ cơ bản
                                            allLoaded = false;
                                            // console.log('Image in view not loaded:', img.src || img.currentSrc);
                                            break;
                                        }
                                    }
                                    return allLoaded;
                                })();
JS;
                            // Sử dụng wait của client để lặp lại kiểm tra JS
                            $client->wait($imageWaitTimeoutMs, 250)->until(
                                fn($client) => $client->executeScript($jsCheckImagesInView)
                            );
                            //echo "[DEBUG] Tất cả ảnh trong viewport có vẻ đã tải xong.\n";
                        } catch (TimeoutException $imgTimeout) {
                            //echo "[WARN] Hết thời gian chờ ảnh tải xong trong viewport sau {$imageWaitTimeoutMs}ms.\n";
                            // Không dừng lại, tiếp tục cuộn
                        } catch (\Throwable $imgError) {
                             //echo "[WARN] Lỗi khi thực thi script kiểm tra ảnh: " . $imgError->getMessage() . "\n";
                        }
                    }
                    // **********************************************************


                    $scrollCount++;
                    // Kiểm tra lại điều kiện dừng nếu đã đạt maxScrollInteractions
                    if ($maxScrollInteractions > 0 && $scrollCount >= $maxScrollInteractions) {
                         //echo "[INFO] Đã đạt giới hạn số lần cuộn tối đa ($maxScrollInteractions).\n";
                         break;
                    }

                    // Kiểm tra lại xem thực sự đã chạm đáy chưa (phòng trường hợp tính toán sai)
                    $currentScrollYAfterWait = (int)$client->executeScript('return window.pageYOffset');
                    $currentHeightAfterWait = (int)$client->executeScript('return document.body.scrollHeight');
                    if (($currentScrollYAfterWait + $viewportHeight) >= $currentHeightAfterWait) {
                         //echo "[DEBUG] Đã chạm đáy trang sau khi cuộn và đợi.\n";
                         break;
                    }


                } // Kết thúc while

                //echo "[INFO] Đã kết thúc quá trình cuộn. Đang chờ thêm " . $postScrollWaitInMs . "ms để hoàn tất xử lý cuối cùng...\n";
                if ($postScrollWaitInMs > 0) {
                    usleep($postScrollWaitInMs * 1000); // Chờ lần cuối
                }
                //echo "[INFO] Đã chờ xong sau khi cuộn.\n";

            } catch (\Throwable $scrollError) {
                //echo "[WARN] Lỗi trong quá trình mô phỏng cuộn trang: " . $scrollError->getMessage() . "\n";
                // Vẫn tiếp tục để cố gắng lấy HTML
            }
        }
        // ================================

        // --- [THỬ NGHIỆM] Cuộn về đầu trang ---
        // Uncomment đoạn này nếu bạn muốn thử nghiệm cuộn về đầu trang sau khi cuộn xuống.
        // Lưu ý: Có thể không cần thiết hoặc gây tác dụng phụ. 
        // Đã test và không có ích mấy, vẫn có sai sót
        //if ($simulateScroll) {
            //try {
                //echo "[DEBUG] Đang cuộn về đầu trang...";
                //$client->executeScript("window.scrollTo(0, 0);");
                //usleep(1000000); // Chờ nửa giây sau khi cuộn lên
                //echo " Xong.\n";
            //} catch (\Throwable $e) {
                //echo "[WARN] Lỗi khi cuộn về đầu trang: " . $e->getMessage() . "\n";
            //}
        //}


        //echo "[INFO] Đang lấy nội dung HTML cuối cùng...\n";
        $fetchedHtml = $client->getPageSource(); // Lấy HTML

        if (empty($fetchedHtml)) {
             //echo "[WARN] Nội dung HTML lấy về bị rỗng cho URL: " . $url . "\n";
             return false;
        } else {
            //echo "[SUCCESS] Đã lấy nội dung HTML thành công!\n";
            // Bạn có thể thêm logic kiểm tra sự tồn tại của ảnh lazy load ở đây nếu muốn
            // Ví dụ: $crawler = new Crawler($fetchedHtml);
            // $images = $crawler->filter('img[data-src]'); // Tìm các ảnh có thể là lazy load
            // if ($images->count() > 0) {
            //     echo "[INFO] Tìm thấy " . $images->count() . " ảnh có thể là lazy load (có data-src). Hãy kiểm tra src thực tế.\n";
            // }
        }

        $htmlContent = $fetchedHtml;

    } catch (\Throwable $e) {
        // --- Xử lý lỗi (giữ nguyên) ---
        //echo "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
        //echo "[FATAL ERROR] Lỗi trong hàm fetchHtmlWithPantherImproved khi xử lý URL: " . $url . "\n";
        //echo $e->getMessage() . "\n";
        //echo "File: " . $e->getFile() . " - Line: " . $e->getLine() . "\n";
        // In thêm stack trace nếu cần debug sâu
        // echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        //echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
        $htmlContent = false;

    } finally {
        // --- Đóng trình duyệt (giữ nguyên) ---
        if ($client) {
             //echo "[INFO] Đang đóng trình duyệt...\n";
             try {
                 $client->quit();
             } catch (\Throwable $quitError) {
                  //echo "[WARN] Lỗi khi đóng trình duyệt: " . $quitError->getMessage() . "\n";
             }
             //echo "[INFO] Đã đóng trình duyệt.\n";
        }
        //echo "=============================================\n";
        //echo "Kết thúc xử lý cho URL: " . $url . "\n";
        //echo "=============================================\n";
    }

    return $htmlContent;
}