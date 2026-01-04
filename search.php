<?php 
// Kiểu tìm kiếm scholar
$is_scholar_mode = false;
if (isset($_GET['scholar']) && $_GET['scholar'] == 'true') {
    $is_scholar_mode = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm Thông minh | silaTranslator</title> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/search.css?v=6">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png"> 
    <style>
        
    </style>
</head>
<body>
    <!-- === Thanh Bên Trái Cố Định === -->
    <aside id="sticky-left-sidebar">
        <ul>
            <li>
                <a href="index.php" title="Dịch trang web">
                    <svg width="24" height="24" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                      <polygon 
                        points="25,2  30,18  48,18  34,30  40,48  25,38  10,48  16,30  2,18  20,18"
                        fill="#777" 
                      />
                    </svg>
                </a>
            </li>
            <li>
                <a href="translation_PDF_HTML.php" title="Dịch file PDF">
                    <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#999" d="M14.25 0H6C4.34315 0 3 1.34315 3 3V21C3 22.6569 4.34315 24 6 24H18C19.6569 24 21 22.6569 21 21V6.75L14.25 0ZM14.25 1.5V5.25C14.25 5.66421 14.5858 6 15 6H18.75L14.25 1.5Z"/>
                    </svg>
                </a>
            </li> 
            <li>
                <a href="search.php<?php if (!$is_scholar_mode) {echo '?scholar=true';} ?>" title="<?php if (!$is_scholar_mode) {echo 'Chuyển sang tìm kiếm PDF';} else {echo 'Chuyển sang tìm kiếm trên web';} ?>">
                    <svg width="200" height="50" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"
                         style="color: steelblue;"> 

                      <defs>
                        <marker
                          id="arrowhead"
                          viewBox="0 0 10 10"    
                          refX="5"              
                          refY="5"              
                          markerWidth="8"        
                          markerHeight="8"       
                          orient="auto-start-reverse"
                          fill="#777">   
                          <path d="M 0 0 L 5 5 L 0 10 z" />
                        </marker>
                      </defs>

                      <!-- Đường thẳng chính -->
                      <line
                        x1="30" y1="25"          
                        x2="170" y2="25"         
                        stroke="#777"    
                        stroke-width="7"        
                        marker-start="url(#arrowhead)"
                        marker-end="url(#arrowhead)" 
                      />
                    </svg>
                </a>
            </li>             
        </ul>     
    </aside>
    <!-- === Kết Thúc Thanh Bên Trái === -->    
    
    <div class="search-wrapper">
        <?php if (!$is_scholar_mode) { // Hiển thị tùy theo mode?>
            <h1>Tìm kiếm nội dung tiếng Anh bằng tiếng Việt</h1>
                <!-- === NÚT KÍCH HOẠT HIỂN THỊ Ý NGHĨA === -->
                <button type="button" id="toggle-news-button" class="toggle-news-button">
                    Xem ý nghĩa của phần này ▼ 
                </button> 
                <!-- === HẾT NÚT KÍCH HOẠT HIỂN THỊ Ý NGHĨA === -->            
            <p class="subtitle" id="google-news-container">Nhập truy vấn bằng tiếng Việt, kết quả sẽ mở trên Google bằng truy vấn tiếng Anh! Truy vấn cố gắng được dịch theo cách người bản xứ tìm kiếm nên có thể không đúng ngữ pháp theo tiêu chuẩn khắt khe. 
                Mục đích là để giúp bạn tìm các bài viết (URL) tiếng Anh phục vụ cho việc <strong><a href="index.php">dịch sang tiếng Việt</a></strong>. 
                Mặc định kết quả trả về sẽ mô phỏng vị trí tìm kiếm tại Hoa Kỳ và chỉ tìm các website tiếng Anh.  
                Bạn toàn quyền chọn <a href="myself/query_setting.php">Model API</a> để dịch truy vấn, nó không nhất thiết phải giống với model dùng cho việc dịch cả bài viết.</p>
        <?php } ?>
        
        <?php if ($is_scholar_mode) { ?>
            <h1>Tìm kiếm <span id="pdf_docs_h1" style="background-color: yellow; padding: 5px 7px;">tài liệu PDF</span> tiếng Anh bằng tiếng Việt</h1>
                <!-- === NÚT KÍCH HOẠT HIỂN THỊ Ý NGHĨA === -->
                <button type="button" id="toggle-news-button" class="toggle-news-button">
                    Xem ý nghĩa của phần này ▼ 
                </button> 
                <!-- === HẾT NÚT KÍCH HOẠT HIỂN THỊ Ý NGHĨA === -->             
            <p class="subtitle" id="google-news-container">Nhập truy vấn bằng tiếng Việt, kết quả sẽ mở trên <strong><a href="https://scholar.google.com/" target="_blank">Google Scholar</a></strong> (trang chuyên tìm tài liệu nghiên cứu, thường là PDF) bằng truy vấn tiếng Anh! Truy vấn cố gắng được dịch theo cách người bản xứ tìm kiếm nên có thể không đúng ngữ pháp theo tiêu chuẩn khắt khe. 
                Mục đích là để giúp bạn tìm các tài liệu PDF tiếng Anh phục vụ cho việc <strong><a href="translation_PDF_HTML.php">dịch sang tiếng Việt</a></strong>. 
                Bạn toàn quyền chọn <a href="myself/query_setting.php">Model API</a> để dịch truy vấn, nó không nhất thiết phải giống với model dùng cho việc dịch file PDF.</p>
        <?php } ?>        
        <!-- Form tìm kiếm -->
        <form action="convert_search_query.php<?php if ($is_scholar_mode) {echo '?scholar=true';}?>" method="POST" class="search-form">
            <div class="search-input-container">
                <input
                    type="text" 
                    id="search-query" 
                    name="vietnamese_query"   
                    class="search-input" 
                    placeholder="Nhập truy vấn tìm kiếm tiếng Việt của bạn..." 
                    required 
                    autofocus 
                    aria-label="Truy vấn tìm kiếm tiếng Việt" 
                >
                <button type="submit" class="search-button" aria-label="Tìm kiếm">
                    <!-- Biểu tượng kính lúp SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="search-icon" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.098zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
    <script>
        const toggleButton = document.getElementById('toggle-news-button');
        const newsContainer = document.getElementById('google-news-container');

        // Kiểm tra xem nút và container có tồn tại không
        if (toggleButton && newsContainer) {
            toggleButton.addEventListener('click', function() {
                // Kiểm tra trạng thái hiện tại của container
                if (newsContainer.style.display === 'none' || newsContainer.style.display === '') {
                    // Nếu đang ẩn, thì hiện ra
                    newsContainer.style.display = 'block';
                    // Đổi nội dung nút/link thành "Ẩn tin tức" và mũi tên lên
                    toggleButton.innerHTML = 'Ẩn giải thích ý nghĩa ▲';
                } else {
                    // Nếu đang hiện, thì ẩn đi
                    newsContainer.style.display = 'none';
                    // Đổi nội dung nút/link trở lại ban đầu
                    toggleButton.innerHTML = 'Xem ý nghĩa của phần này ▼';
                }
            });
        }  
    </script>
</body>
</html>