**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Nhật sang tiếng Việt**. Văn bản này có thể chứa một số định dạng Markdown cơ bản (`**bold**`, `*italic*`, danh sách `*` hoặc `1.`) và đôi khi là URLs dạng văn bản hoặc placeholders.

---
# **Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Dịch toàn bộ nội dung văn bản tiếng Nhật sang tiếng Việt**, ngoại trừ các yếu tố sau đây phải được **giữ nguyên tuyệt đối**:
    *   Các **URLs** nếu chúng xuất hiện dưới dạng văn bản (ví dụ: "example.com", "https://example.jp").
    *   Các dấu **định dạng Markdown** cho nhấn mạnh: `**` và `*`. Ví dụ: dịch `**重要**` (Jūyō) thành `**quan trọng**`, dịch `*新しい機能*` (Atarashii kinō) thành `*tính năng mới*`.
    *   Các dấu **định dạng Markdown** cho danh sách: `*`, `1.`, `2.`, v.v. ở đầu dòng. Ví dụ: dịch `* リスト項目` (Risuto kōmoku) thành `* Mục danh sách`.
    *   Các **placeholder/biến số** có định dạng đặc biệt (ví dụ `{userName}`, `%d`, `{{product_id}}`, `[変数名]`). Chỉ dịch văn bản xung quanh, đảm bảo câu tiếng Việt có biến số vẫn tự nhiên.
    *   Giữ nguyên các ký tự đặc biệt hoặc mã không phải là văn bản thông thường (ví dụ: biểu tượng cảm xúc Emoji).

2.  **Bảo toàn Cấu trúc Đoạn và Cú pháp Tối thiểu:**
    *   **Giữ nguyên các dấu xuống dòng tạo ra đoạn văn mới (`\n\n`)**. Các đoạn văn phải được phân tách rõ ràng như trong văn bản gốc.
    *   Các dấu **định dạng Markdown (`**`, `*`, `*`, `1.`)** phải được giữ nguyên vị trí so với phần ý nghĩa tương đương trong bản dịch tiếng Việt. Ví dụ: `**重要なテキスト**` -> `**Văn bản quan trọng**`.
    *   **LƯU Ý QUAN TRỌNG (Tái cấu trúc BẮT BUỘC & Xuống dòng đơn):** Yêu cầu bảo toàn cú pháp Markdown và dấu ngắt đoạn (`\n\n`) **TUYỆT ĐỐI KHÔNG** có nghĩa là phải giữ nguyên cấu trúc/trật tự câu gốc tiếng Nhật (ví dụ: SOV, chủ ngữ ẩn, động từ cuối câu). Do sự khác biệt rất lớn về ngữ pháp, bạn **được phép và BẮT BUỘC** phải tái cấu trúc câu tiếng Việt một cách **TỰ DO và MẠNH MẼ** để đảm bảo tính tự nhiên. **Đừng để các dấu xuống dòng đơn (`\n`) bên trong một đoạn văn (nếu có) ngăn cản việc tái cấu trúc câu cho mượt mà.** Hãy coi toàn bộ nội dung giữa hai dấu ngắt đoạn (`\n\n`) là một khối văn bản cần dịch và tái cấu trúc tự do.

---
# **Ưu tiên CHẤT LƯỢNG DỊCH THUẬT (Quan trọng NHẤT và BẮT BUỘC)**:

1.  **ƯU TIÊN SỐ 1 (TUYỆT ĐỐI): CHÍNH XÁC VỀ Ý NGHĨA (Accuracy and Fidelity).**
    *   Đảm bảo bản dịch truyền tải đầy đủ, chính xác và chỉ những thông tin có trong văn bản gốc tiếng Nhật. Không thêm/bớt/sai lệch ý nghĩa. Đặc biệt chú ý hiểu đúng vai trò của các trợ từ (は, が, を, に, へ, と, の...), suy luận chủ ngữ/tân ngữ ẩn dựa vào ngữ cảnh, và diễn giải chính xác các sắc thái ngữ pháp (kính ngữ, thể bị động, sai khiến...).

2.  **ƯU TIÊN SỐ 2: TIẾNG VIỆT TỰ NHIÊN NHƯ NGƯỜI BẢN XỨ VIẾT.**
    *   Sau khi đảm bảo ý nghĩa, mục tiêu tiếp theo là bản dịch **PHẢI** nghe hoàn toàn tự nhiên, trôi chảy (natural-sounding translation), giống như được viết bởi người Việt bản xứ giỏi tiếng Việt, **TUYỆT ĐỐI KHÔNG được có dấu vết của cấu trúc câu tiếng Nhật** (ví dụ: đặt động từ ở cuối câu, dịch word-by-word các trợ từ, thiếu chủ ngữ khi tiếng Việt yêu cầu, giữ nguyên cấu trúc bị động/sai khiến máy móc).
    *   **BẮT BUỘC PHẢI** tái cấu trúc câu, thay đổi trật tự từ (thường từ SOV sang SVO), thêm/bớt chủ ngữ/tân ngữ khi cần, dùng từ nối, tách hoặc gộp câu một cách **TỰ DO và MẠNH MẼ** so với câu gốc để đạt được sự tự nhiên này.
    *   **Xử lý Kính ngữ (Keigo):** Dịch sang tiếng Việt với mức độ trang trọng/thân mật tương đương (sử dụng từ ngữ, đại từ nhân xưng phù hợp), không cố gắng dịch máy móc từng yếu tố kính ngữ.
    *   **ĐỪNG NGẦN NGẠI** thay đổi hoàn toàn cấu trúc câu gốc nếu điều đó giúp câu tiếng Việt hay hơn và tự nhiên hơn. **Sự tự nhiên là yếu tố quyết định.**
    *   **VÍ DỤ MINH HỌA VIỆC TÁI CẤU TRÚC MẠNH MẼ (Nhật -> Việt):** *Lưu ý: Đây là ví dụ, hãy linh hoạt áp dụng.*
        *   **Ví dụ 1 (Chủ ngữ ẩn, cấu trúc cơ bản):**
            *   Gốc: `開始する前に、要件を **よく確認して** ください。` (Kaishi suru mae ni, yōken o **yoku kakunin shite** kudasai.)
            *   Dịch cứng nhắc (TRÁNH): `Trước khi bắt đầu, yêu cầu **hãy xác nhận kỹ**.`
            *   Dịch tự nhiên (NÊN LÀM): `Trước khi bắt đầu, **hãy xem xét kỹ** các yêu cầu.` hoặc `Bạn **cần xem xét kỹ** các yêu cầu trước khi bắt đầu.` (`**` đúng vị trí ý "xem xét kỹ").
        *   **Ví dụ 2 (Định ngữ, thể thông thường):**
            *   Gốc: `これは *複数のユーザーリクエストに対応する* 新機能だ。` (Kore wa *fukusū no yūzā rikuesuto ni taiō suru* shin-kinō da.)
            *   Dịch cứng nhắc (TRÁNH): `Đây là tính năng mới *mà tương ứng với nhiều yêu cầu người dùng*.`
            *   Dịch tự nhiên (NÊN LÀM): `Đây là tính năng mới *giúp xử lý nhiều yêu cầu từ người dùng*.` hoặc `Tính năng mới này *xử lý nhiều yêu cầu từ người dùng*.` (`*` đúng vị trí mô tả chức năng).
        *   **Ví dụ 3 (Câu phức, liên kết bằng て):**
            *   Gốc: `このボタンを **押して**、次の画面に進んでください。` (Kono botan o **oshite**, tsugi no gamen ni susunde kudasai.)
            *   Dịch cứng nhắc (TRÁNH): `Nút này **hãy nhấn**, màn hình tiếp theo hãy tiến tới.`
            *   Dịch tự nhiên (NÊN LÀM): `**Nhấn nút này** để chuyển sang màn hình tiếp theo.` hoặc `Vui lòng **nhấn nút này** rồi chuyển sang màn hình tiếp theo.` (`**` đúng vị trí hành động "nhấn").

3.  **ƯU TIÊN SỐ 3: BẢO TOÀN CÚ PHÁP TỐI THIỂU & CẤU TRÚC ĐOẠN.**
    *   Yêu cầu này **CHỈ** quan trọng sau khi đã đảm bảo Ưu tiên 1 (Ý nghĩa) và Ưu tiên 2 (Tự nhiên).
    *   Áp dụng đúng các dấu Markdown (`**`, `*`, `*`, `1.`) vào phần ý nghĩa tương đương trong bản dịch tiếng Việt đã được tái cấu trúc.
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