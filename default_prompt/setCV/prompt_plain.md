**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Trung sang tiếng Việt**. Văn bản này có thể chứa một số định dạng Markdown cơ bản (`**bold**`, `*italic*`, danh sách `*` hoặc `1.`) và đôi khi là URLs dạng văn bản hoặc placeholders.

---
# **Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Dịch toàn bộ nội dung văn bản tiếng Trung sang tiếng Việt**, ngoại trừ các yếu tố sau đây phải được **giữ nguyên tuyệt đối**:
    *   Các **URLs** nếu chúng xuất hiện dưới dạng văn bản (ví dụ: "example.com", "https://beispiel.de").
    *   Các dấu **định dạng Markdown** cho nhấn mạnh: `**` và `*`. Ví dụ: dịch `**重要**` thành `**quan trọng**`, dịch `*新功能*` thành `*tính năng mới*`.
    *   Các dấu **định dạng Markdown** cho danh sách: `*`, `1.`, `2.`, v.v. ở đầu dòng. Ví dụ: dịch `* 列表项` thành `* Mục danh sách`.
    *   Các **placeholder/biến số** có định dạng đặc biệt (ví dụ `{userName}`, `%d`, `{{product_id}}`, `[TÊN_BIẾN]`). Chỉ dịch văn bản xung quanh.
    *   Giữ nguyên các ký tự đặc biệt hoặc mã không phải là văn bản thông thường (ví dụ: biểu tượng cảm xúc, ký tự đặc biệt không dịch được).

2.  **Bảo toàn Cấu trúc Đoạn và Cú pháp Tối thiểu:**
    *   **Giữ nguyên các dấu xuống dòng tạo ra đoạn văn mới (`\n\n`)**. Các đoạn văn phải được phân tách rõ ràng như trong văn bản gốc.
    *   Các dấu **định dạng Markdown (`**`, `*`, `*`, `1.`)** phải được giữ nguyên vị trí so với phần ý nghĩa tương đương trong bản dịch. Ví dụ: `**重要文本**` -> `**Văn bản quan trọng**`.
    *   **LƯU Ý QUAN TRỌNG (Tái cấu trúc & Xuống dòng đơn):** Yêu cầu bảo toàn cú pháp Markdown và dấu ngắt đoạn (`\n\n`) **KHÔNG** có nghĩa là phải giữ nguyên cấu trúc/trật tự câu gốc tiếng Trung. Do sự khác biệt lớn về ngữ pháp, bạn **được phép và BẮT BUỘC** phải tái cấu trúc câu tiếng Việt một cách **TỰ DO và MẠNH MẼ** để đảm bảo tính tự nhiên. **Đừng để các dấu xuống dòng đơn (`\n`) bên trong một đoạn văn (nếu có) ngăn cản việc tái cấu trúc câu cho mượt mà.** Hãy coi toàn bộ nội dung giữa hai dấu ngắt đoạn (`\n\n`) là một khối văn bản cần dịch và tái cấu trúc tự do.

---
# **Ưu tiên CHẤT LƯỢNG DỊCH THUẬT (Quan trọng NHẤT và BẮT BUỘC)**:

1.  **ƯU TIÊN SỐ 1 (TUYỆT ĐỐI): CHÍNH XÁC VỀ Ý NGHĨA (Accuracy and Fidelity).**
    *   Đảm bảo bản dịch truyền tải đầy đủ, chính xác và chỉ những thông tin có trong văn bản gốc tiếng Trung. Không thêm/bớt/sai lệch ý nghĩa. Đặc biệt chú ý đến các thành ngữ, cấu trúc ngữ pháp đặc thù, và sắc thái ý nghĩa tinh tế của tiếng Trung.

2.  **ƯU TIÊN SỐ 2: TIẾNG VIỆT TỰ NHIÊN NHƯ NGƯỜI BẢN XỨ VIẾT.**
    *   Sau khi đảm bảo ý nghĩa, mục tiêu tiếp theo là bản dịch **PHẢI** nghe hoàn toàn tự nhiên, trôi chảy (natural-sounding translation), giống như được viết bởi người Việt bản xứ giỏi tiếng Việt, **TUYỆT ĐỐI KHÔNG được có dấu vết của cấu trúc câu tiếng Trung** (ví dụ: dịch cứng nhắc theo trật tự từ, lạm dụng cấu trúc định ngữ `的` (`de`), dịch word-by-word).
    *   **BẮT BUỘC PHẢI** tái cấu trúc câu, thay đổi trật tự từ, dùng từ nối, tách hoặc gộp câu một cách **TỰ DO và MẠNH MẼ** so với câu gốc để đạt được sự tự nhiên này. Tiếng Trung thường cô đọng, có thể cần thêm từ nối hoặc diễn giải nhẹ trong tiếng Việt.
    *   **ĐỪNG NGẦN NGẠI** thay đổi hoàn toàn cấu trúc câu gốc nếu điều đó giúp câu tiếng Việt hay hơn và tự nhiên hơn. **Sự tự nhiên là yếu tố quyết định.**
    *   **VÍ DỤ MINH HỌA VIỆC TÁI CẤU TRÚC MẠNH MẼ (Trung -> Việt):** *Lưu ý: Đây là ví dụ, hãy linh hoạt áp dụng.*
        *   Gốc: `请 **仔细检查** 要求后再开始。` (Qǐng **zǐxì jiǎnchá** yāoqiú hòu zài kāishǐ.)
        *   Dịch cứng nhắc (TRÁNH): `Xin hãy **kiểm tra cẩn thận** yêu cầu sau đó mới bắt đầu.`
        *   Dịch tự nhiên (NÊN LÀM): `Trước khi bắt đầu, bạn **cần xem xét kỹ** các yêu cầu.` hoặc `Bạn **cần xem xét kỹ** các yêu cầu trước khi bắt đầu.` (Cấu trúc thay đổi, `**` vẫn đúng chỗ).
        *   Gốc: `这项新功能，*解决了多个用户请求*，现已可用。` (Zhè xiàng xīn gōngnéng, *jiějuéle duō ge yònghù qǐngqiú*, xiàn yǐ kěyòng.)
        *   Dịch cứng nhắc (TRÁNH): `Tính năng mới này, *mà giải quyết nhiều yêu cầu người dùng*, giờ đã có sẵn.` (Cấu trúc gượng gạo).
        *   Dịch tự nhiên (NÊN LÀM): `Tính năng mới này hiện đã khả dụng. *Nó giải quyết nhiều yêu cầu từ người dùng*.` (Tách câu, diễn đạt tự nhiên, `*` vẫn đúng chỗ). Hoặc: `Tính năng mới này, *giúp giải quyết nhiều yêu cầu từ người dùng*, hiện đã khả dụng.`

3.  **ƯU TIÊN SỐ 3: BẢO TOÀN CÚ PHÁP TỐI THIỂU & CẤU TRÚC ĐOẠN.**
    *   Yêu cầu này **CHỈ** quan trọng sau khi đã đảm bảo Ưu tiên 1 (Ý nghĩa) và Ưu tiên 2 (Tự nhiên).
    *   Áp dụng đúng các dấu Markdown (`**`, `*`, `*`, `1.`) vào phần ý nghĩa tương đương trong bản dịch tiếng Việt.
    *   Giữ nguyên các dấu ngắt đoạn (`\n\n`).
    *   Giữ nguyên URLs và placeholders.

4.  **Áp dụng Nguyên tắc từ Hướng dẫn Hệ thống (`systemInstruction`) (Nếu có):**
    *   Vẫn tuân thủ các nguyên tắc khác về thuật ngữ (nếu có quy định), giọng văn, v.v., nhưng luôn đặt trong bối cảnh các ưu tiên trên. Nếu không có `systemInstruction`, hãy tập trung vào các yêu cầu còn lại.

5.  **Nguyên tắc Ưu tiên khi Xung đột (Nhấn mạnh lại):**
    *   **Ưu tiên 1: Chính xác & Đủ ý nghĩa.**
    *   **Ưu tiên 2: Tiếng Việt tự nhiên & Lưu loát.**
    *   **Ưu tiên 3: Bảo toàn Cú pháp Tối thiểu & Cấu trúc đoạn.**

---
# **KIỂM TRA CUỐI CÙNG (Bắt buộc):**

Sau khi hoàn thành bản dịch, hãy thực hiện các bước sau **TRƯỚC KHI** đưa ra kết quả cuối cùng:
1.  **Đọc lại TOÀN BỘ bản dịch tiếng Việt.**
2.  **Tự hỏi:** "Câu văn/đoạn văn này nghe có giống người Việt viết không? Hay vẫn còn lấn cấn, gượng gạo, hoặc mang âm hưởng cấu trúc tiếng Anh?"
3.  **Nếu chưa tự nhiên:** **BẮT BUỘC** phải sửa lại câu/đoạn đó, tái cấu trúc mạnh mẽ hơn nữa cho đến khi hoàn toàn hài lòng về độ tự nhiên (nhưng vẫn phải đảm bảo ưu tiên số 1 **Chính xác về ý nghĩa**), ngay cả khi phải thay đổi nhiều so với bản dịch ban đầu. **Đừng ngại sửa nhiều lần.**

---
**Định dạng Output:** **Chỉ trả về văn bản thuần túy (plain text) tiếng Việt đã được dịch**, giữ nguyên các dấu định dạng Markdown (`**`, `*`, `*`, `1.`), URLs dạng văn bản, placeholders và cấu trúc đoạn (`\n\n`) như yêu cầu. Không thêm bất kỳ lời giải thích hay ghi chú nào khác.

---
**BẮT ĐẦU VĂN BẢN CẦN DỊCH:**