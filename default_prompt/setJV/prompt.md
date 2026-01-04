**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Nhật sang tiếng Việt** trong đoạn mã Markdown dưới đây.

---
# **Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Chỉ dịch các nội dung sau:**
    *   **Nội dung văn bản hiển thị cho người đọc:** Bao gồm văn bản trong các đoạn văn, tiêu đề (見出し - midashi), danh sách (リスト - risuto), trích dẫn (引用 - in'yō), phần mô tả của liên kết, v.v.
    *   Văn bản mô tả thay thế (alt text - 代替テキスト) trong cú pháp hình ảnh Markdown (`![alt text](url)`).

2.  **Bảo toàn Cấu trúc và Cú pháp:**
    *   Đảm bảo cấu trúc tổng thể (thứ tự tiêu đề, danh sách, đoạn văn, khối mã, v.v.) được bảo toàn.
    *   **Giữ nguyên vị trí chính xác** của tất cả các ký tự cú pháp Markdown so với văn bản mà chúng định dạng. Ví dụ: `**重要なテキスト**` (Jūyōna tekisuto) phải được dịch thành `**Văn bản quan trọng**`, không phải `** Văn bản quan trọng **` hay `**Văn bản** quan trọng`.
    *   **LƯU Ý QUAN TRỌNG (TÁI CẤU TRÚC BẮT BUỘC):** Yêu cầu bảo toàn vị trí Markdown này **TUYỆT ĐỐI KHÔNG** có nghĩa là phải giữ nguyên cấu trúc hoặc trật tự từ/thành phần câu (ví dụ: SOV, chủ ngữ ẩn) của câu gốc tiếng Nhật. Do sự khác biệt rất lớn về ngữ pháp (trật tự từ, trợ từ, thể kính ngữ, chủ ngữ thường ẩn...), bạn **được phép, cần thiết và BẮT BUỘC PHẢI** tái cấu trúc câu tiếng Việt một cách **TỰ DO và MẠNH MẼ** để đảm bảo tính tự nhiên và lưu loát. Miễn là cú pháp Markdown vẫn áp dụng đúng vào **phần ý nghĩa tương đương** trong bản dịch tiếng Việt đã được tái cấu trúc. Xem thêm **VÍ DỤ MINH HỌA VIỆC TÁI CẤU TRÚC MẠNH MẼ:** bên dưới để hiểu rõ hơn yêu cầu này.

---
# **Ưu tiên CHẤT LƯỢNG DỊCH THUẬT (Quan trọng NHẤT và BẮT BUỘC)**:

1.  **ƯU TIÊN SỐ 1 (TUYỆT ĐỐI): CHÍNH XÁC VỀ Ý NGHĨA (Accuracy and Fidelity).**
    *   Đảm bảo bản dịch truyền tải đầy đủ, chính xác và chỉ những thông tin có trong văn bản gốc tiếng Nhật. Không thêm các diễn giải, bình luận cá nhân hoặc bỏ sót các chi tiết quan trọng. Đặc biệt chú ý:
        *   **Hiểu đúng vai trò của các trợ từ (助詞 - joshi):** は (wa), が (ga), を (o), に (ni), へ (e), と (to), の (no), v.v. để xác định đúng chủ thể, đối tượng, địa điểm, mục đích...
        *   **Suy luận chủ ngữ/tân ngữ ẩn:** Xác định các thành phần câu bị lược bỏ dựa vào ngữ cảnh.
        *   **Diễn giải đúng sắc thái ý nghĩa:** Bao gồm cả mức độ lịch sự (kính ngữ - 敬語 keigo), các trạng thái bị động (受身 ukemi), sai khiến (使役 shieki), và các cấu trúc ngữ pháp phức tạp khác.

2.  **ƯU TIÊN SỐ 2: TIẾNG VIỆT TỰ NHIÊN NHƯ NGƯỜI BẢN XỨ VIẾT.**
    *   Mục tiêu tiếp theo là bản dịch **PHẢI** nghe hoàn toàn tự nhiên, trôi chảy (natural-sounding translation), giống như được viết bởi người Việt bản xứ giỏi tiếng Việt, **TUYỆT ĐỐI KHÔNG được có dấu vết của cấu trúc câu tiếng Nhật** (ví dụ: đặt động từ ở cuối câu, dịch word-by-word các trợ từ, giữ nguyên cấu trúc câu bị động/sai khiến một cách máy móc, thiếu chủ ngữ khi tiếng Việt yêu cầu).
    *   **BẮT BUỘC PHẢI** tái cấu trúc câu, thay đổi trật tự từ (thường là từ SOV sang SVO), thêm/bớt chủ ngữ/tân ngữ, dùng từ nối, tách hoặc gộp câu một cách **TỰ DO và MẠNH MẼ** so với câu gốc để đạt được sự tự nhiên này. Tiếng Nhật thường ẩn thông tin, nên cần làm rõ trong tiếng Việt nếu cần.
    *   **Xử lý Kính ngữ (Keigo):** Dịch sang tiếng Việt với mức độ trang trọng/thân mật tương đương (sử dụng từ ngữ, đại từ nhân xưng phù hợp), **không** cố gắng dịch từng yếu tố kính ngữ một cách máy móc.
    *   **ĐỪNG NGẦN NGẠI** thay đổi hoàn toàn cấu trúc câu gốc nếu điều đó giúp câu tiếng Việt hay hơn và tự nhiên hơn. **Sự tự nhiên là yếu tố quyết định.**

3.  **Áp dụng Nguyên tắc từ Hướng dẫn Hệ thống (`systemInstruction`):**
    *   Vẫn tuân thủ các nguyên tắc khác về thuật ngữ (nếu có quy định), giọng văn, v.v., nhưng luôn đặt trong bối cảnh ưu tiên số 1 là sự **Chính xác về Ý nghĩa**. Nếu không có `systemInstruction`, hãy tập trung vào các yêu cầu còn lại.

4.  **Bảo toàn Cú pháp Markdown:**
    *   Yêu cầu này **CHỈ** quan trọng sau khi đã đảm bảo tính *Chính xác về Ý nghĩa* & tính *Tự nhiên*.
    *   **TUYỆT ĐỐI KHÔNG** để việc bảo toàn vị trí Markdown ngăn cản bạn tái cấu trúc câu. Hãy tạo ra câu tiếng Việt tự nhiên trước, sau đó tìm cách áp dụng Markdown vào **phần ý nghĩa tương đương** trong câu mới đó.
    *   **VÍ DỤ MINH HỌA VIỆC TÁI CẤU TRÚC MẠNH MẼ (Nhật -> Việt):** *Lưu ý: Đây là ví dụ, hãy linh hoạt áp dụng.*
        *   **Ví dụ 1 (Cấu trúc cơ bản, Chủ ngữ ẩn):**
            *   Gốc: `開始する前に、要件を **よく確認して** ください。` (Kaishi suru mae ni, yōken o **yoku kakunin shite** kudasai.)
            *   Dịch cứng nhắc (TRÁNH): `Trước khi bắt đầu, yêu cầu **hãy xác nhận kỹ**.` (Thiếu chủ ngữ, cấu trúc hơi ngược).
            *   Dịch tự nhiên (NÊN LÀM): `Trước khi bắt đầu, **hãy xem xét kỹ** các yêu cầu.` hoặc `Bạn **cần xem xét kỹ** các yêu cầu trước khi bắt đầu.` (Thêm chủ ngữ, đảo cấu trúc, Markdown `**...**` vẫn đúng chỗ ý "xem xét kỹ").
        *   **Ví dụ 2 (Định ngữ với の, Kính ngữ):**
            *   Gốc: `これは **お客様の多様なご要望にお応えする** 新機能でございます。` (Kore wa **okyakusama no tayōna go-yōbō ni o-kotae suru** shin-kinō de gozaimasu.)
            *   Dịch cứng nhắc (TRÁNH): `Đây là tính năng mới **mà đáp ứng các yêu cầu đa dạng của quý khách**.` (Dịch cấu trúc định ngữ với の máy móc, hơi gượng).
            *   Dịch tự nhiên (NÊN LÀM): `Đây là tính năng mới **giúp đáp ứng các yêu cầu đa dạng của quý khách**.` (Dùng "giúp đáp ứng" tự nhiên hơn). Hoặc tốt hơn: `Tính năng mới này hiện đã có. **Nó đáp ứng các yêu cầu đa dạng từ quý khách**.` (Tách câu, thay đổi cấu trúc hoàn toàn, giữ được sự trang trọng qua "quý khách", Markdown `**...**` vẫn đúng chỗ mô tả chức năng).
        *   **Ví dụ 3 (Thể bị động - Ukemi):**
            *   Gốc: `この情報は*定期的に更新されます*。` (Kono jōhō wa *teikiteki ni kōshin saremasu*.)
            *   Dịch cứng nhắc (TRÁNH): `Thông tin này *được cập nhật định kỳ* là.` (Sai ngữ pháp Việt).
            *   Dịch tự nhiên (NÊN LÀM): `Thông tin này *được cập nhật định kỳ*.` (Dùng bị động chuẩn Việt). Hoặc tùy ngữ cảnh: `Chúng tôi *cập nhật thông tin này định kỳ*.` (Chuyển sang chủ động nếu phù hợp, Markdown `*...*` vẫn đúng chỗ "cập nhật định kỳ").
        *   **Ví dụ 4 (Câu dài, nhiều mệnh đề phụ):**
            *   Gốc: `マニュアルに記載されている **指示に従って**、ソフトウェアをインストールしてください。` (Manyuaru ni kisai sarete iru **shiji ni shitagatte**, sofutowea o insutōru shite kudasai.)
            *   Dịch cứng nhắc (TRÁNH): `Theo **chỉ dẫn** được ghi trong hướng dẫn sử dụng, phần mềm hãy cài đặt.` (Giữ cấu trúc gốc, động từ cuối câu, rất khó hiểu).
            *   Dịch tự nhiên (NÊN LÀM): `Vui lòng cài đặt phần mềm **theo hướng dẫn** được ghi trong tài liệu.` Hoặc: `Hãy cài đặt phần mềm **theo các chỉ dẫn** trong tài liệu hướng dẫn.` (Đảo cấu trúc SVO, dùng từ nối tự nhiên, Markdown `**...**` đúng chỗ "theo hướng dẫn/chỉ dẫn").

5.  **Nguyên tắc Ưu tiên khi Xung đột (Nhấn mạnh lại):**
    *   **Ưu tiên 1: Chính xác & Đủ ý nghĩa.**
    *   **Ưu tiên 2: Tiếng Việt tự nhiên & Lưu loát.**
    *   **Ưu tiên 3: Bảo toàn Cú pháp Markdown.**

---
# **KIỂM TRA CUỐI CÙNG (Bắt buộc):**

Sau khi hoàn thành bản dịch Markdown, hãy thực hiện các bước sau **TRƯỚC KHI** đưa ra kết quả cuối cùng:
1.  **Đọc lại TOÀN BỘ bản dịch tiếng Việt.**
2.  **Tự hỏi:** "Câu văn/đoạn văn này nghe có giống người Việt viết không? Hay vẫn còn lấn cấn, gượng gạo, hoặc mang âm hưởng cấu trúc tiếng Anh?"
3.  **Nếu chưa tự nhiên:** **BẮT BUỘC** phải sửa lại câu/đoạn đó, tái cấu trúc mạnh mẽ hơn nữa cho đến khi hoàn toàn hài lòng về độ tự nhiên (nhưng vẫn phải đảm bảo ưu tiên số 1 **Chính xác về ý nghĩa**), ngay cả khi phải thay đổi nhiều so với bản dịch ban đầu. **Đừng ngại sửa nhiều lần.**

---
**Định dạng Output:** **Chỉ trả về đoạn mã Markdown đã được dịch.** Không thêm bất kỳ lời giải thích, ghi chú hay văn bản nào khác vào phần trả lời.

---
**BẮT ĐẦU NỘI DUNG MARKDOWN CẦN DỊCH:**