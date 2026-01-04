**Nhiệm vụ Chính**: Dựa trên vai trò và các nguyên tắc hoạt động đã được định nghĩa chi tiết trong System Instructions (SI) nâng cao của bạn, hãy thực hiện:

1.  **Dịch thuật Anh-Việt**: Dịch **cực kỳ chính xác về ý nghĩa** và **tuyệt đối tự nhiên về văn phong tiếng Việt** toàn bộ **nội dung văn bản có thể đọc được** và các thành phần có ngữ nghĩa (như alt text cho ảnh) từ tài liệu PDF tiếng Anh được cung cấp. **TUÂN THỦ NGHIÊM NGẶT Ưu tiên #1 và #2** trong SI, đặc biệt là yêu cầu **tái cấu trúc câu/đoạn một cách quyết liệt và sáng tạo**.
2.  **Tái tạo sang HTML**: Trình bày bản dịch dưới dạng một **đoạn mã HTML thô hoàn chỉnh và ngữ nghĩa**. **Nỗ lực tối đa (best-effort)** để **bảo toàn layout và định dạng gốc** của tài liệu PDF này dưới dạng HTML/CSS, nhưng **TUYỆT ĐỐI KHÔNG VI PHẠM Ưu tiên #3 (Tránh vỡ layout/Đảm bảo đọc được)** và luôn tuân thủ **Thứ tự Ưu tiên**, **Quy tắc Giải quyết Xung đột**, và các hướng dẫn xử lý yếu tố đặc biệt (ảnh, bảng, cột, header/footer, accessibility) trong SI.

**Đầu vào**: Tài liệu PDF tiếng Anh.
**Đầu ra**: Một đoạn mã HTML thô hoàn chỉnh, **không chứa bất kỳ văn bản nào khác ngoài mã HTML đó** (không lời dẫn, không giải thích, không markdown bao quanh như ```html).

---
**## Yêu cầu TUYỆT ĐỐI (Không được vi phạm):**

1.  **Phạm vi Dịch & Xử lý**: Tuân thủ mục "Phạm vi Xử lý" trong SI. Chỉ dịch text đọc được, tạo alt text Việt hóa cho ảnh, giữ nguyên code/URL, bỏ qua metadata ẩn...
2.  **Định dạng Output**: **CHỈ MÃ HTML THÔ.**
3.  **Chất lượng Dịch thuật (NHẮC LẠI ƯU TIÊN CAO NHẤT):**
    *   **#1: CHÍNH XÁC Ý NGHĨA.**
    *   **#1A: CHÍNH XÁC VÀ CHUẨN HÓA THUẬT NGỮ CHUYÊN NGÀNH (Đặc biệt cho tài liệu khoa học):** Tuân thủ nghiêm ngặt các hướng dẫn chi tiết trong Mục 3 của SI về xử lý thuật ngữ (ưu tiên thuật ngữ Việt chuẩn hóa, giữ nguyên gốc nếu không chắc/không có, giải thích lần đầu nếu cần, nhất quán tuyệt đối).
    *   **#2: TIẾNG VIỆT TỰ NHIÊN TUYỆT ĐỐI (Bao gồm TÁI CẤU TRÚC MẠNH MẼ).**
        *   **Ưu tiên giọng chủ động**: Ưu tiên **chuyển đổi câu bị động tiếng Anh sang câu chủ động tiếng Việt** nếu phù hợp và làm tăng tính tự nhiên, trừ khi ngữ cảnh yêu cầu giữ sắc thái bị động.
        *   *Ví dụ Tái cấu trúc (Nhấn mạnh lại tầm quan trọng: Bạn hãy thấm nhuần tư duy này và áp dụng một cách sáng tạo, quyết liệt cho TOÀN BỘ bản dịch. Hãy thoát ly hoàn toàn khỏi cấu trúc câu tiếng Anh gốc, ưu tiên hàng đầu cho sự mạch lạc, tự nhiên và dễ hiểu trong tiếng Việt):*

            1.  `Gốc`: `Data was obtained by distributing questionnaires to, and conducting interviews with 200 random online motorcycle taxi passengers in Yogyakarta City, Indonesia.`
                *   `Tự nhiên (Khuyến khích)`:
                    *   `Để thu thập dữ liệu, chúng tôi đã phân phát bảng hỏi và phỏng vấn ngẫu nhiên 200 hành khách sử dụng xe ôm công nghệ tại thành phố Yogyakarta, Indonesia.`
                    *   `Dữ liệu nghiên cứu được thu thập thông qua việc phân phát bảng hỏi và phỏng vấn 200 hành khách ngẫu nhiên sử dụng xe ôm công nghệ ở thành phố Yogyakarta, Indonesia.`

            2.  `Gốc`: `Application-based transportation company Uber has 3.9 million drivers around the world, while Grab, with 36 million passengers collectively using services as many as 2.5 billion times, is served by 2.8 million drivers and Lyft, another app based company, has 1.4 million drivers who have served 23 million passengers in over one billion trips (Iqbal, 2019).`
                *   `Tự nhiên (Khuyến khích)`:
                    *   `Các công ty vận tải ứng dụng công nghệ như Uber có 3,9 triệu tài xế toàn cầu; Grab phục vụ 36 triệu hành khách với tổng cộng 2,5 tỷ chuyến đi, được hỗ trợ bởi 2,8 triệu tài xế; còn Lyft, một công ty ứng dụng khác, có 1,4 triệu tài xế đã phục vụ 23 triệu hành khách qua hơn một tỷ chuyến đi (Iqbal, 2019).`
                    *   `Theo Iqbal (2019), các công ty vận tải ứng dụng công nghệ lớn như Uber hiện có 3,9 triệu tài xế trên toàn thế giới. Trong khi đó, Grab phục vụ 36 triệu hành khách, thực hiện tổng cộng 2,5 tỷ chuyến đi nhờ 2,8 triệu tài xế, và Lyft, một công ty ứng dụng khác, có 1,4 triệu tài xế đã phục vụ 23 triệu hành khách qua hơn một tỷ chuyến đi.`

            3.  `Gốc`: `This study aims to determine the characteristics of passengers as well as their importance and performance on online motorcycle taxis.`
                *   `Tự nhiên (Khuyến khích)`:
                    *   `Nghiên cứu này nhằm xác định đặc điểm của hành khách, cũng như đánh giá mức độ quan trọng và hiệu suất dịch vụ xe ôm công nghệ theo quan điểm của họ.`
                    *   `Mục tiêu của nghiên cứu là tìm hiểu đặc điểm của hành khách sử dụng xe ôm công nghệ, đồng thời đánh giá tầm quan trọng và hiệu quả hoạt động của loại hình dịch vụ này.`

            4.  `Gốc`: `According to IPA, most of the online motorcycle taxi services were perceived to be good by the passengers.`
                *   `Tự nhiên (Khuyến khích)`:
                    *   `Theo phân tích IPA, phần lớn hành khách đánh giá dịch vụ xe ôm công nghệ ở mức tốt.`
                    *   `Kết quả phân tích IPA cho thấy, hầu hết hành khách đều có cái nhìn tích cực về chất lượng dịch vụ xe ôm công nghệ.`

            5.  `Gốc`: `Government policies that ultimately allow the use of motorcycle taxi online cannot be separated from the demands of the wider community to obtain the desired transportation services.`
                *   `Tự nhiên (Khuyến khích)`:
                    *   `Chính sách của chính phủ cho phép xe ôm công nghệ hoạt động là kết quả tất yếu từ nhu cầu của cộng đồng về một dịch vụ vận tải phù hợp.`
                    *   `Việc chính phủ cho phép xe ôm công nghệ hoạt động trực tuyến không thể tách rời khỏi nhu cầu của cộng đồng về một dịch vụ vận tải đáp ứng mong muốn.`
        *   **Lưu ý khi AI áp dụng (nhắc lại và nhấn mạnh thêm):**
            *   **Ưu tiên tuyệt đối ý nghĩa gốc:** Mọi sự tái cấu trúc phải xuất phát từ việc hiểu chính xác 100% ý định và sắc thái của câu gốc.
            *   **Mạnh dạn và sáng tạo:** Đừng ngần ngại phá vỡ hoàn toàn cấu trúc ngữ pháp Anh. Hãy tư duy như một người viết tiếng Việt thực thụ, tìm cách diễn đạt ý tưởng đó một cách tự nhiên và hiệu quả nhất trong tiếng Việt.
            *   **Ngữ điệu và dòng chảy:** Chú ý đến nhịp điệu, sự trôi chảy của câu văn tiếng Việt. Đôi khi việc tách một câu dài thành hai câu ngắn hoặc nối hai câu ngắn lại có thể giúp cải thiện điều này.
            *   **Lựa chọn từ đồng nghĩa/gần nghĩa:** Cân nhắc các từ đồng nghĩa hoặc gần nghĩa để tìm ra từ phù hợp nhất với ngữ cảnh và văn phong của tài liệu. Ví dụ: "determine" có thể dịch là "xác định", "tìm hiểu", "đánh giá" tùy sắc thái.
            *   **Tránh lặp từ/cấu trúc:** Nếu một cấu trúc câu tiếng Anh lặp lại nhiều lần, hãy cố gắng đa dạng hóa cách diễn đạt trong tiếng Việt.
            *   **Kiểm tra lại sau khi dịch:** Luôn đọc lại bản dịch tiếng Việt một cách độc lập để đảm bảo nó thực sự tự nhiên, dễ hiểu và không còn "dấu vết" của câu gốc tiếng Anh.

4.  **Xử lý Layout, Định dạng & Cấu trúc HTML (NHẮC LẠI ƯU TIÊN KỸ THUẬT):**
    *   Luôn nhớ **Ưu tiên #3 (Không vỡ layout/Đọc được)** > **Ưu tiên #4 (Cố gắng bảo toàn layout)**.
    *   **ĐẢM BẢO KÍCH THƯỚC FONT CHỮ ĐỌC ĐƯỢC:** Kích thước font chữ của **nội dung chính** (các đoạn văn bản `<p>`) phải đủ lớn để đọc thoải mái trên web (khuyến nghị tối thiểu 14px-16px), ngay cả khi phải **tăng kích thước so với PDF gốc**.
    *   **BẮT BUỘC** chuyển thân văn bản chính nhiều cột thành **1 cột** trong HTML.
    *   Xử lý **Hình ảnh (`<img>`)**: Đảm bảo có thẻ `<img>` với `src` hợp lý (hoặc placeholder) và **thuộc tính `alt` tiếng Việt có ý nghĩa** (hoặc `alt=""` nếu trang trí).
    *   Xử lý **Văn bản trong Ảnh/Biểu đồ**: Định vị CSS khéo léo, không che khuất.
    *   Xử lý **Bảng biểu (`<table>`)**: Đảm bảo **cấu trúc ngữ nghĩa** (`thead`, `tbody`, `th`, `td`), đơn giản hóa nếu cần để dễ đọc.
    *   Xử lý **Header/Footer**: Tích hợp hợp lý, không gây xáo trộn.
    *   Đảm bảo **HTML ngữ nghĩa** (semantic HTML) và tuân thủ **accessibility cơ bản** (cấu trúc heading, alt text, table headers).

5.  **Xử lý Tài liệu Tham khảo (References/Bibliography)**: **TUÂN THỦ NGHIÊM NGẶT** các quy tắc trong SI: **KHÔNG dịch** tên tác giả, tiêu đề (bài báo, sách, tạp chí), nhà xuất bản, thông tin xuất bản, DOI, URL. **Chỉ dịch** các ghi chú/bình luận do tác giả gốc viết thêm (nếu có). **Bảo toàn định dạng gốc** (đậm, nghiêng, cấu trúc list) và **đảm bảo DOI/URL là link hoạt động**.

---
**## Quy trình Thực hiện & KIỂM TRA CUỐI CÙNG (Bắt buộc):**

1.  **Phân tích PDF Toàn diện**: Hiểu rõ nội dung, cấu trúc logic, layout, định dạng, hình ảnh, bảng biểu.
2.  **Dịch thuật Tập trung (Ưu tiên #1, #1A & #2)**: Tạo bản dịch tiếng Việt chuẩn xác, tự nhiên nhất, tái cấu trúc không khoan nhượng. Xác định nội dung cho `alt` text của ảnh.
3.  **Tạo HTML/CSS Ngữ nghĩa & Accessibile (Ưu tiên #3 & #4)**: Xây dựng cấu trúc HTML ngữ nghĩa, nhúng bản dịch, tích hợp ảnh (`<img>` với `alt`), bảng (`<table>`), áp dụng CSS để cố gắng tái tạo giao diện gốc. **Luôn kiểm soát chặt chẽ** để không vỡ layout và đảm bảo khả năng truy cập cơ bản. Xử lý các yếu tố đặc biệt (ảnh, cột, header/footer, bảng phức tạp) theo đúng hướng dẫn SI. Giải quyết xung đột theo đúng thứ tự ưu tiên và quy tắc trong SI.
4.  **KIỂM TRA CHẤT LƯỢNG TOÀN DIỆN (CỰC KỲ QUAN TRỌNG):**
    *   Đọc lại **TOÀN BỘ nội dung tiếng Việt** trong mã HTML được tạo ra (bao gồm cả `alt` text nếu có thể xem được).
    *   **Tự vấn nghiêm khắc dựa trên các Nguyên tắc và Yêu cầu trong SI & Prompt**:
        *   "Ý nghĩa có **chính xác 100%** so với gốc không?" (Ưu tiên #1)
        *   "Các **thuật ngữ chuyên ngành** đã được xử lý **chính xác, chuẩn hóa, và nhất quán** theo Mục 3 của SI chưa (ví dụ: dùng từ Việt chuẩn, giữ nguyên gốc kèm giải thích nếu cần)?" (Ưu tiên #1A)
        *   "Văn phong có **hoàn toàn tự nhiên, mượt mà như người Việt viết** không? Còn chút gượng gạo nào không?" (Ưu tiên #2)
        *   "Giọng văn, cách diễn đạt chung (ngoài thuật ngữ) có **nhất quán và phù hợp** không?"
        *   "Bố cục HTML có **rõ ràng, dễ đọc, không bị lỗi hiển thị** (chồng chéo, tràn, ẩn...) không?" (Ưu tiên #3)
        *   "Kích thước font chữ của **nội dung chính** có **đảm bảo dễ đọc** không (ví dụ: ít nhất 14px-16px)? Đã ưu tiên yếu tố này hơn việc chỉ sao chép font nhỏ từ PDF chưa?"
        *   "Layout và định dạng có được bảo toàn ở mức **hợp lý và tốt nhất có thể** mà không gây lỗi không?" (Ưu tiên #4)
        *   "Cấu trúc HTML (thẻ ngữ nghĩa, heading, list, table...) có **phản ánh đúng logic** của tài liệu gốc và **tuân thủ accessibility cơ bản** không?"
        *   "Hình ảnh (`<img>`) có được xử lý đúng cách với **`alt` text tiếng Việt ý nghĩa** không?"
        *   "Bảng biểu (`<table>`) có **cấu trúc ngữ nghĩa đúng** và dễ hiểu không?"
        *   "Các yếu tố đặc biệt (text trong ảnh, cột, header/footer) đã được xử lý **đúng theo chỉ dẫn** chưa?"
        *   "Phần Tài liệu Tham khảo (References) đã được xử lý đúng cách chưa? Các yếu tố cốt lõi (tác giả, tiêu đề, tạp chí, DOI, URL...) có được **giữ nguyên gốc 100%** không? Chỉ các ghi chú của tác giả gốc (nếu có) mới được dịch?"
            *   "Định dạng (đậm, nghiêng, list) và các liên kết (DOI/URL) trong References có được **bảo toàn chính xác** không?"
    *   **Nếu phát hiện bất kỳ vấn đề nào (đặc biệt về độ tự nhiên, lỗi hiển thị, cấu trúc sai, thiếu alt text, xử lý sai thuật ngữ...)**: **BẮT BUỘC phải sửa lại** trực tiếp trong HTML. Tái cấu trúc bản dịch thêm nữa nếu cần, điều chỉnh CSS, đơn giản hóa layout/bảng nếu cần, bổ sung/sửa `alt` text, sửa lại thuật ngữ, cho đến khi **hoàn toàn hài lòng** về mọi mặt theo đúng thứ tự ưu tiên. **Đừng ngại thay đổi đáng kể so với lần tạo mã đầu tiên.**
5.  **Kiểm tra Kỹ thuật Cuối**: Đảm bảo mã HTML cơ bản hợp lệ, không có lỗi cú pháp và chỉ chứa mã HTML.

---
**Định dạng Output Cuối cùng:** **Chỉ trả về mã HTML thô.** Hãy đảm bảo kết quả cuối cùng thể hiện năng lực tốt nhất của bạn dựa trên những chỉ dẫn cực kỳ chi tiết này.