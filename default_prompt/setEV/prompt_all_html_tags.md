**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Anh sang tiếng Việt** trong đoạn mã HTML dưới đây.

---
# **Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Chỉ dịch các nội dung sau:**
    *   Văn bản hiển thị cho người dùng nằm giữa các thẻ HTML (text nodes).
    *   Giá trị của thuộc tính `alt` (mô tả ảnh).
    *   Giá trị của thuộc tính `title` (văn bản tooltip).
    *   Giá trị của thuộc tính `placeholder` (văn bản gợi ý trong ô nhập liệu).

2.  **KHÔNG Dịch và Giữ nguyên TUYỆT ĐỐI:**
    *   Tên thẻ HTML (ví dụ `<p>`, `<a>`, `<img>`, `<div>`, `<span>`).
    *   Tên thuộc tính HTML (ví dụ `href`, `src`, `class`, `id`, `style`, `data-*` trừ các giá trị được nêu ở mục 1).
    *   Giá trị của các thuộc tính không chứa văn bản tự nhiên (ví dụ URLs trong `href`, đường dẫn file trong `src`, tên lớp CSS trong `class`, ID, giá trị màu sắc/kích thước trong `style`).
    *   Nội dung bên trong thẻ `<script>` và `<style>`.
    *   Các comment HTML (`<!-- ... -->`).
    *   Các placeholder/biến số có định dạng đặc biệt (ví dụ `{userName}`, `%d`, `{{product_id}}`). **Giữ nguyên các biến này**, chỉ dịch phần văn bản xung quanh và đảm bảo ngữ pháp tiếng Việt tự nhiên khi có biến số.
    *   Nội dung của file âm thanh và nội dung file video. **Điều này nghĩa là bạn KHÔNG cần nghe âm thanh, xem video để dịch.**

3.  **Bảo toàn Cấu trúc và Thứ tự:** Giữ nguyên 100% thứ tự và cấu trúc lồng ghép (hierarchy) của các thẻ HTML như bản gốc. Vị trí tương đối của mọi thẻ và văn bản phải giống hệt.

---
# **Ưu tiên Chất lượng Dịch thuật (Rất quan trọng):**

    *   Áp dụng tất cả các nguyên tắc dịch thuật từ Hướng dẫn Hệ thống (`systemInstruction`).
    *   **ĐẶC BIỆT CHÚ TRỌNG:** Đảm bảo bản dịch tiếng Việt **NGHE TỰ NHIÊN VÀ LƯU LOÁT**, ngay cả khi câu bị ngắt bởi các thẻ inline (như `<strong>`, `<em>`, `<a>`, `<span>`). Hãy đọc thầm cả câu hoàn chỉnh để chắc chắn nó mạch lạc và truyền tải đúng ý nghĩa.
    *   **Nguyên tắc Ưu tiên khi Xung đột:**
        1.  **Bảo toàn HTML (Ưu tiên 1):** Yêu cầu về bảo toàn cấu trúc và thuộc tính HTML (Mục 2 & 3 ở trên) là **không thể thay đổi**.
        2.  **Chính xác & Ý nghĩa (Ưu tiên 2):** Bản dịch phải truyền tải đúng ý nghĩa gốc.
        3.  **Tiếng Việt tự nhiên (Ưu tiên 3):** Trong giới hạn của hai ưu tiên trên, hãy làm cho bản dịch tự nhiên và trôi chảy nhất có thể. Chấp nhận rằng đôi khi cấu trúc HTML cố định có thể làm giảm một chút độ mượt mà, nhưng cố gắng giảm thiểu sự gượng gạo.

---
# **KIỂM TRA CUỐI CÙNG (Bắt buộc):**

Sau khi hoàn thành bản dịch, hãy thực hiện các bước sau **TRƯỚC KHI** đưa ra kết quả cuối cùng:
1.  **Đọc lại TOÀN BỘ bản dịch tiếng Việt.**
2.  **Tự hỏi:** "Câu văn/đoạn văn này nghe có giống người Việt viết không? Hay vẫn còn lấn cấn, gượng gạo, hoặc mang âm hưởng cấu trúc tiếng Anh?"
3.  **Nếu chưa tự nhiên:** **BẮT BUỘC** phải sửa lại câu/đoạn đó, tái cấu trúc mạnh mẽ hơn nữa cho đến khi hoàn toàn hài lòng về độ tự nhiên (nhưng vẫn phải đảm bảo ưu tiên số 1 **Chính xác về ý nghĩa**), ngay cả khi phải thay đổi nhiều so với bản dịch ban đầu. **Đừng ngại sửa nhiều lần.**

---
**Định dạng Output:** **Chỉ trả về đoạn mã HTML đã được dịch.** Không thêm bất kỳ lời giải thích, ghi chú hay văn bản nào khác vào phần trả lời.      

---
**BẮT ĐẦU MÃ HTML CẦN DỊCH:**