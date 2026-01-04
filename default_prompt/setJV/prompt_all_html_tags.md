**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Nhật sang tiếng Việt** trong đoạn mã HTML dưới đây.

---
# **Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Chỉ dịch các nội dung sau:**
    *   Văn bản hiển thị cho người dùng nằm giữa các thẻ HTML (text nodes - テキストノード). Ví dụ: dịch `<p>こんにちは、世界</p>` (Konnichiwa, sekai) thành `<p>Chào thế giới</p>`.
    *   Giá trị của thuộc tính `alt` (mô tả ảnh - 代替テキスト). Ví dụ: dịch `<img src="logo.png" alt="会社のロゴ">` (Kaisha no rogo) thành `<img src="logo.png" alt="Logo công ty">`.
    *   Giá trị của thuộc tính `title` (văn bản tooltip - ツールチップテキスト). Ví dụ: dịch `<button title="送信するにはここをクリック">送信</button>` (Sōshin suru ni wa koko o kurikku / Sōshin) thành `<button title="Nhấn vào đây để gửi">Gửi</button>`.
    *   Giá trị của thuộc tính `placeholder` (văn bản gợi ý trong ô nhập liệu - プレースホルダー). Ví dụ: dịch `<input type="text" placeholder="ユーザー名を入力してください">` (Yūzāmei o nyūryoku shite kudasai) thành `<input type="text" placeholder="Vui lòng nhập tên người dùng">`.

2.  **KHÔNG Dịch và Giữ nguyên TUYỆT ĐỐI:**
    *   Tên thẻ HTML (ví dụ `<p>`, `<a>`, `<img>`, `<div>`, `<span>`).
    *   Tên thuộc tính HTML (ví dụ `href`, `src`, `class`, `id`, `style`, `data-*` trừ các giá trị được nêu ở mục 1).
    *   Giá trị của các thuộc tính không chứa văn bản tự nhiên (ví dụ URLs trong `href` như `href="about-us.html"`, đường dẫn file trong `src` như `src="/images/写真1.jpg"`, tên lớp CSS trong `class` như `class="main-content container"`, ID như `id="user-profile"`, giá trị màu sắc/kích thước trong `style` như `style="color: red;"`).
    *   Nội dung bên trong thẻ `<script>` và `<style>`.
    *   Các comment HTML (`<!-- これはコメントです -->`).
    *   Các placeholder/biến số có định dạng đặc biệt (ví dụ `{userName}`, `%d`, `{{product_id}}`, `[注文番号]`). **Giữ nguyên các biến này**, chỉ dịch phần văn bản xung quanh và đảm bảo ngữ pháp tiếng Việt tự nhiên khi có biến số. Ví dụ: dịch `こんにちは、{userName}さん！` thành `Chào bạn, {userName}!`.
    *   Nội dung của file âm thanh và nội dung file video được nhúng (ví dụ trong thẻ `<audio>`, `<video>`). **Điều này nghĩa là bạn KHÔNG cần nghe âm thanh, xem video để dịch.**

3.  **Bảo toàn Cấu trúc và Thứ tự:** Giữ nguyên 100% thứ tự và cấu trúc lồng ghép (hierarchy) của các thẻ HTML như bản gốc. Vị trí tương đối của mọi thẻ và văn bản phải giống hệt. **KHÔNG được thêm, bớt hay thay đổi thứ tự các thẻ HTML.**

---
# **Ưu tiên Chất lượng Dịch thuật (Rất quan trọng):**

    *   **Áp dụng Nguyên tắc từ Hướng dẫn Hệ thống (`systemInstruction`) (Nếu có):** Tuân thủ các quy tắc về thuật ngữ, giọng văn, kính ngữ, v.v., nhưng đặt trong bối cảnh các ưu tiên dưới đây.
    *   **ĐẶC BIỆT CHÚ TRỌNG:** Đảm bảo bản dịch tiếng Việt **NGHE TỰ NHIÊN VÀ LƯU LOÁT**, ngay cả khi câu bị ngắt bởi các thẻ inline (như `<strong>`, `<em>`, `<a>`, `<span>`). Hãy đọc thầm cả câu hoàn chỉnh (bao gồm cả văn bản trong các thẻ inline) để chắc chắn nó mạch lạc, tự nhiên theo văn phong tiếng Việt, truyền tải đúng ý nghĩa gốc tiếng Nhật và xử lý đúng các đặc điểm như chủ ngữ ẩn, thể câu, kính ngữ.**
        *   Ví dụ:
            *   Gốc: `<p>これらの <a href="#">重要な指示</a> を <strong>よくお読みください</strong>。</p>` (Korera no <a href="#">jūyōna shiji</a> o **yoku o-yomi kudasai**.)
            *   Dịch cứng nhắc (TRÁNH): `<p>Những <a href="#">hướng dẫn quan trọng</a> này <strong>hãy đọc kỹ</strong>.</p>` (Cấu trúc ngược, thiếu tự nhiên).
            *   Dịch tự nhiên (NÊN LÀM): `<p>Vui lòng <strong>đọc kỹ</strong> các <a href="#">hướng dẫn quan trọng</a> này.</p>` hoặc `<p>Quý vị vui lòng <strong>đọc kỹ</strong> các <a href="#">hướng dẫn quan trọng</a> này.</p>` (Câu văn mượt hơn, thể hiện được sự trang trọng của お読みください, vẫn giữ nguyên cấu trúc thẻ).
            *   Gốc: `<p>これは <em>非常に緊急</em> のお知らせです。</p>` (Kore wa *hijō ni kinkyū* no o-shirase desu.)
            *   Dịch tự nhiên (NÊN LÀM): `<p>Đây là một thông báo *rất khẩn cấp*.</p>` (Câu chuẩn Việt, thể hiện sự trang trọng vừa phải của お知らせです).

    *   **Nguyên tắc Ưu tiên khi Xung đột:**
        1.  **Bảo toàn HTML (Ưu tiên 1 - TUYỆT ĐỐI):** Yêu cầu về bảo toàn cấu trúc, thẻ, thuộc tính HTML (Mục 2 & 3 ở trên) là **không thể thay đổi**. Đây là ràng buộc kỹ thuật cao nhất.
        2.  **Chính xác & Ý nghĩa (Ưu tiên 2):** Trong giới hạn của cấu trúc HTML, bản dịch phải truyền tải đúng và đủ ý nghĩa gốc tiếng Nhật, bao gồm cả sắc thái ngữ pháp và mức độ lịch sự.
        3.  **Tiếng Việt tự nhiên (Ưu tiên 3):** Trong giới hạn của hai ưu tiên trên, hãy làm cho bản dịch tự nhiên và trôi chảy nhất có thể theo văn phong tiếng Việt. Chấp nhận rằng đôi khi cấu trúc HTML cố định có thể làm giảm một chút độ mượt mà so với văn bản thuần túy, nhưng cần cố gắng tối đa để giảm thiểu sự gượng gạo do ảnh hưởng cấu trúc câu tiếng Nhật hoặc do giới hạn của thẻ HTML.

---
# **KIỂM TRA CUỐI CÙNG (Bắt buộc):**

Sau khi hoàn thành bản dịch, hãy thực hiện các bước sau **TRƯỚC KHI** đưa ra kết quả cuối cùng:
1.  **Đọc lại TOÀN BỘ bản dịch tiếng Việt.**
2.  **Tự hỏi:** "Câu văn/đoạn văn này nghe có giống người Việt viết không? Hay vẫn còn lấn cấn, gượng gạo, hoặc mang âm hưởng cấu trúc tiếng Anh?"
3.  **Nếu chưa tự nhiên:** **BẮT BUỘC** phải sửa lại câu/đoạn đó, tái cấu trúc mạnh mẽ hơn nữa cho đến khi hoàn toàn hài lòng về độ tự nhiên (nhưng vẫn phải đảm bảo ưu tiên số 1 **Chính xác về ý nghĩa**), ngay cả khi phải thay đổi nhiều so với bản dịch ban đầu. **Đừng ngại sửa nhiều lần.**

---
**Định dạng Output:** **Chỉ trả về đoạn mã HTML đã được dịch.** Không thêm bất kỳ lời giải thích, ghi chú, dấu ```html, hay văn bản nào khác vào phần trả lời. Chỉ trả về mã HTML thuần túy.

---
**BẮT ĐẦU MÃ HTML CẦN DỊCH:**