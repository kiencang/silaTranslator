**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Trung sang tiếng Việt** trong đoạn mã Markdown dưới đây.

---
# **Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Chỉ dịch các nội dung sau:**
    *   **Nội dung văn bản hiển thị cho người đọc:** Bao gồm văn bản trong các đoạn văn, tiêu đề, danh sách, trích dẫn, phần mô tả của liên kết, v.v.
    *   Văn bản mô tả thay thế (alt text) trong cú pháp hình ảnh Markdown (`![alt text](url)`).

2.  **Bảo toàn Cấu trúc và Cú pháp:**
    *   Đảm bảo cấu trúc tổng thể (thứ tự tiêu đề, danh sách, đoạn văn, khối mã, v.v.) được bảo toàn.
    *   **Giữ nguyên vị trí chính xác** của tất cả các ký tự cú pháp Markdown so với văn bản mà chúng định dạng. Ví dụ: `**重要文本**` phải được dịch thành `**Văn bản quan trọng**`, không phải `** Văn bản quan trọng **` hay `**Văn bản** quan trọng`.
    *   **LƯU Ý QUAN TRỌNG:** Yêu cầu bảo toàn vị trí Markdown này **KHÔNG** có nghĩa là phải giữ nguyên cấu trúc hoặc trật tự từ/ký tự của câu gốc tiếng Trung. Do sự khác biệt lớn về ngữ pháp và cách diễn đạt, bạn **được phép, cần thiết và nên** tái cấu trúc câu tiếng Việt một cách tự do và mạnh mẽ để đảm bảo tính tự nhiên và lưu loát, miễn là cú pháp Markdown vẫn áp dụng đúng vào **phần ý nghĩa tương đương** trong bản dịch. Xem thêm **VÍ DỤ MINH HỌA VIỆC TÁI CẤU TRÚC MẠNH MẼ:** bên dưới để hiểu rõ hơn yêu cầu này.

---
# **Ưu tiên CHẤT LƯỢNG DỊCH THUẬT (Quan trọng NHẤT và BẮT BUỘC)**:

1.  **ƯU TIÊN SỐ 1 (TUYỆT ĐỐI): CHÍNH XÁC VỀ Ý NGHĨA (Accuracy and Fidelity).**
    *   Đảm bảo bản dịch truyền tải đầy đủ, chính xác và chỉ những thông tin có trong văn bản gốc tiếng Trung. Không thêm các diễn giải, bình luận cá nhân hoặc bỏ sót các chi tiết quan trọng. Đặc biệt chú ý đến các thành ngữ, cấu trúc ngữ pháp đặc thù (như cấu trúc 把 `bǎ`, 被 `bèi`, các bổ ngữ phức tạp), và các từ đa nghĩa của tiếng Trung để diễn đạt đúng ý nghĩa trong tiếng Việt.

2.  **ƯU TIÊN SỐ 2: TIẾNG VIỆT TỰ NHIÊN NHƯ NGƯỜI BẢN XỨ VIẾT.**
    *   Mục tiêu tiếp theo là bản dịch **PHẢI** nghe hoàn toàn tự nhiên, trôi chảy (natural-sounding translation), giống như được viết bởi người Việt bản xứ giỏi tiếng Việt, **TUYỆT ĐỐI KHÔNG được có dấu vết của cấu trúc câu tiếng Trung** (ví dụ: dịch cứng nhắc theo trật tự "chủ-trạng-động-tân", lạm dụng cấu trúc định ngữ dài dòng với chữ "的" (`de`), hoặc dịch word-by-word các cấu trúc đặc thù).
    *   **BẮT BUỘC PHẢI** tái cấu trúc câu, thay đổi trật tự từ, dùng từ nối, tách hoặc gộp câu một cách **TỰ DO và MẠNH MẼ** so với câu gốc để đạt được sự tự nhiên này. Tiếng Trung thường cô đọng và có thể ẩn chủ ngữ, nên có thể cần thêm từ nối, chủ ngữ giả định (nếu phù hợp ngữ cảnh) hoặc diễn giải nhẹ nhàng trong tiếng Việt để câu văn mạch lạc.
    *   **ĐỪNG NGẦN NGẠI** thay đổi hoàn toàn cấu trúc câu gốc nếu điều đó giúp câu tiếng Việt hay hơn và tự nhiên hơn. **Sự tự nhiên là yếu tố quyết định.**

3.  **Áp dụng Nguyên tắc từ Hướng dẫn Hệ thống (`systemInstruction`) (Nếu có):**
    *   Vẫn tuân thủ các nguyên tắc khác về thuật ngữ (nếu có quy định), giọng văn, v.v., nhưng luôn đặt trong bối cảnh ưu tiên số 1 là sự **Chính xác về Ý nghĩa**. Nếu không có `systemInstruction`, hãy tập trung vào các yêu cầu còn lại.

4.  **Bảo toàn Cú pháp Markdown:**
    *   Yêu cầu này **CHỈ** quan trọng sau khi đã đảm bảo tính *Chính xác về Ý nghĩa* & tính *Tự nhiên*.
    *   **TUYỆT ĐỐI KHÔNG** để việc bảo toàn vị trí Markdown ngăn cản bạn tái cấu trúc câu. Hãy tạo ra câu tiếng Việt tự nhiên trước, sau đó tìm cách áp dụng Markdown vào **phần ý nghĩa tương đương** trong câu mới đó.
    *   **VÍ DỤ MINH HỌA VIỆC TÁI CẤU TRÚC MẠNH MẼ (Trung -> Việt):** *Lưu ý: Đây là ví dụ, hãy linh hoạt áp dụng.*
        *   **Ví dụ 1:**
            *   Gốc: `请 **仔细检查** 要求后再开始。` (Qǐng **zǐxì jiǎnchá** yāoqiú hòu zài kāishǐ.)
            *   Dịch cứng nhắc (TRÁNH): `Xin hãy **kiểm tra cẩn thận** yêu cầu sau đó mới bắt đầu.` (Giữ cấu trúc "làm A sau đó làm B", thiếu tự nhiên).
            *   Dịch tự nhiên (NÊN LÀM): `Trước khi bắt đầu, bạn **cần xem xét kỹ** các yêu cầu.` hoặc `Bạn **cần xem xét kỹ** các yêu cầu trước khi bắt đầu.` (Đảo trật tự, dùng từ nối tiếng Việt). Markdown `**...**` vẫn bao quanh ý "xem xét kỹ".
        *   **Ví dụ 2:**
            *   Gốc: `这个 **解决了多个用户请求的** 新功能现已可用。` (Zhège **jiějuéle duō ge yònghù qǐngqiú de** xīn gōngnéng xiàn yǐ kěyòng.)
            *   Dịch cứng nhắc (TRÁNH): `Cái tính năng mới **mà giải quyết nhiều yêu cầu người dùng này** giờ đã có sẵn.` (Dịch sai cấu trúc định ngữ dài với `的` (`de`), rất gượng gạo).
            *   Dịch tự nhiên (NÊN LÀM): `Tính năng mới này hiện đã khả dụng. **Nó giải quyết nhiều yêu cầu từ người dùng**.` (Tách thành 2 câu, bỏ hoàn toàn cấu trúc `的`). Hoặc: `Tính năng mới **giúp giải quyết nhiều yêu cầu từ người dùng** hiện đã khả dụng.` (Biến định ngữ thành mệnh đề/cụm từ tự nhiên hơn trong tiếng Việt). Markdown `**...**` bao quanh phần mô tả chức năng.
        *   **Ví dụ 3:**
            *   Gốc: `这个项目 **必须在本周内完成**。` (Zhège xiàngmù **bìxū zài běn zhōu nèi wánchéng**.)
            *   Dịch cứng nhắc (TRÁNH): `Dự án này **phải ở trong tuần này hoàn thành**.` (Dịch word-by-word, sai ngữ pháp Việt). Hoặc: `Dự án này **phải được hoàn thành trong tuần này**.` (Hơi cứng, dù đúng ngữ pháp).
            *   Dịch tự nhiên (NÊN LÀM): `**Cần phải hoàn thành** dự án này trong tuần này.` (Dùng cấu trúc "Cần phải + Động từ"). Hoặc: `Dự án này **cần được hoàn thành** trong tuần này.` (Dùng "cần được" tự nhiên hơn). Markdown `**...**` bám vào ý "phải/cần hoàn thành".
        *   **Ví dụ 4:**
            *   Gốc: `他 **用心地** 把每个细节都做好了。` (Tā **yòngxīnde** bǎ měi ge xìjié dōu zuò hǎo le.)
            *   Dịch cứng nhắc (TRÁNH): `Anh ấy **một cách tận tâm** đem từng chi tiết đều làm tốt rồi.` (Dịch cấu trúc `把` (`bǎ`) và trạng từ `用心` (`yòngxīn`) một cách máy móc).
            *   Dịch tự nhiên (NÊN LÀM): `Anh ấy đã hoàn thành mọi chi tiết **một cách cẩn thận/tỉ mỉ**.` (Loại bỏ cấu trúc `把`, diễn đạt lại bằng câu chủ động thông thường, đặt trạng từ đúng vị trí tiếng Việt). Hoặc: `Mọi chi tiết đều được anh ấy hoàn thành **rất cẩn thận/tỉ mỉ**.` (Dùng cấu trúc bị động tự nhiên). Markdown `**...**` bám vào trạng thái "cẩn thận/tỉ mỉ".

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