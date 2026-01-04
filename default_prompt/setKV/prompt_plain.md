**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Hàn sang tiếng Việt**. Văn bản này có thể chứa một số định dạng Markdown cơ bản (`**bold**`, `*italic*`, danh sách `*` hoặc `1.`) và đôi khi là URLs dạng văn bản.

---
# **Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Dịch toàn bộ nội dung văn bản tiếng Hàn sang tiếng Việt**, ngoại trừ các yếu tố sau đây phải được **giữ nguyên tuyệt đối**:
    *   Các **URLs** nếu chúng xuất hiện dưới dạng văn bản (ví dụ: "example.com", "naver.com").
    *   Các dấu **định dạng Markdown** cho nhấn mạnh: `**` và `*`. Ví dụ: dịch `**중요 공지**` thành `**Thông báo quan trọng**`.
    *   Các dấu **định dạng Markdown** cho danh sách: `*`, `1.`, `2.`, v.v. ở đầu dòng. Ví dụ: dịch `* 목록 항목` thành `* Mục danh sách`.
    *   Các **placeholder/biến số** có định dạng đặc biệt (ví dụ `{userName}`, `%d`, `{{product_id}}`, `#{count}`). Chỉ dịch văn bản xung quanh. Ví dụ: `총 **#{count}개** 항목` -> `Tổng cộng **#{count}** mục`.
    *   Giữ nguyên kiểu viết hoa/thường như gốc (trừ khi ngữ pháp tiếng Việt yêu cầu thay đổi).

2.  **Bảo toàn Cấu trúc Đoạn và Cú pháp Tối thiểu:**
    *   **Giữ nguyên các dấu xuống dòng tạo ra đoạn văn mới (`\n\n`)**.
    *   Các dấu **định dạng Markdown (`**`, `*`, `* `, `1. `)** phải được giữ nguyên vị trí so với phần ý nghĩa tương đương trong bản dịch. Ví dụ: `**굵은 텍스트**` -> `**Văn bản đậm**`.
    *   **LƯU Ý QUAN TRỌNG (Tái cấu trúc & Xuống dòng đơn):** Yêu cầu bảo toàn cú pháp Markdown và dấu ngắt đoạn (`\n\n`) **KHÔNG** có nghĩa là phải giữ nguyên cấu trúc/trật tự câu gốc tiếng Hàn. Bạn **được phép và BẮT BUỘC** phải tái cấu trúc câu tiếng Việt một cách **TỰ DO và MẠNH MẼ** để đảm bảo tính tự nhiên. **Đừng để các dấu xuống dòng đơn (`\n`) bên trong một đoạn văn ngăn cản việc tái cấu trúc câu cho mượt mà.**

---
# **Ưu tiên CHẤT LƯỢNG DỊCH THUẬT (Quan trọng NHẤT và BẮT BUỘC)**:

1.  **ƯU TIÊN SỐ 1 (TUYỆT ĐỐI): CHÍNH XÁC VỀ Ý NGHĨA (Accuracy and Fidelity).**
    *   Đảm bảo bản dịch truyền tải đầy đủ và chỉ những thông tin có trong văn bản gốc. Không thêm/bớt/sai lệch ý nghĩa, đặc biệt chú ý đến các tiểu từ, đuôi câu thể hiện sắc thái trong tiếng Hàn.

2.  **ƯU TIÊN SỐ 2: TIẾNG VIỆT TỰ NHIÊN NHƯ NGƯỜI BẢN XỨ VIẾT.**
    *   Sau khi đảm bảo ý nghĩa, mục tiêu tiếp theo là bản dịch **PHẢI** nghe hoàn toàn tự nhiên, trôi chảy (natural-sounding translation), giống như được viết bởi người Việt bản xứ giỏi tiếng Việt, **KHÔNG được có dấu vết của cấu trúc câu tiếng Hàn (ví dụ: trật tự Chủ-Tân-Động, lạm dụng cấu trúc định ngữ dài dòng, sử dụng bị động không cần thiết).**
    *   **BẮT BUỘC PHẢI** tái cấu trúc câu, thay đổi trật tự từ, dùng từ nối, tách hoặc gộp câu một cách **TỰ DO và MẠNH MẼ** so với câu gốc để đạt được sự tự nhiên này.
    *   **ĐỪNG NGẦN NGẠI** thay đổi hoàn toàn cấu trúc câu gốc nếu điều đó giúp câu tiếng Việt hay hơn và tự nhiên hơn. **Sự tự nhiên là yếu tố quyết định.**
    *   **VÍ DỤ MINH HỌA VIỆC TÁI CẤU TRÚC MẠNH MẼ (Tiếng Hàn -> Tiếng Việt):** *Lưu ý: Đây là ví dụ, hãy linh hoạt áp dụng.*
        *   Gốc (Tiếng Hàn): `시작하기 전에 요구 사항을 **주의 깊게 검토**해야 합니다.`
        *   Dịch cứng nhắc (TRÁNH): `Trước khi bắt đầu, yêu cầu **phải xem xét cẩn thận**.`
        *   Dịch tự nhiên (NÊN LÀM): `Trước khi bắt đầu, bạn **cần xem xét kỹ** các yêu cầu.` hoặc `Việc **xem xét kỹ** các yêu cầu trước khi bắt đầu là rất quan trọng.`
        *   Gốc (Tiếng Hàn): `*여러 사용자 요청을 해결하는* 이 새로운 기능은\n이제 사용할 수 있습니다.` (Có dấu xuống dòng đơn `\n` ở giữa)
        *   Dịch cứng nhắc (TRÁNH): `Tính năng mới này, *cái mà giải quyết nhiều yêu cầu người dùng*,\n giờ đã có sẵn.` (Giữ nguyên cấu trúc định ngữ và dấu xuống dòng đơn không tự nhiên)
        *   Dịch tự nhiên (NÊN LÀM): `Tính năng mới này hiện đã khả dụng. *Nó giải quyết nhiều yêu cầu từ người dùng*.` (Tách câu, loại bỏ cấu trúc định ngữ, bỏ qua dấu xuống dòng đơn `\n` để câu liền mạch, Markdown vẫn đúng chỗ).

3.  **ƯU TIÊN SỐ 3: BẢO TOÀN CÚ PHÁP TỐI THIỂU & CẤU TRÚC ĐOẠN.**
    *   Yêu cầu này **CHỈ** quan trọng sau khi đã đảm bảo Ưu tiên 1 (Ý nghĩa) và Ưu tiên 2 (Tự nhiên).
    *   Áp dụng đúng các dấu Markdown (`**`, `*`, `* `, `1. `) vào phần ý nghĩa tương đương.
    *   Giữ nguyên các dấu ngắt đoạn (`\n\n`).

4.  **Áp dụng Nguyên tắc từ Hướng dẫn Hệ thống (`systemInstruction`):**
    *   Vẫn tuân thủ các nguyên tắc khác về thuật ngữ, giọng văn, v.v., đặt trong bối cảnh các ưu tiên trên.

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