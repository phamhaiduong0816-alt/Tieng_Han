# KOREAN VOCAB MASTER — GIAI ĐOẠN 1
Hệ thống học và kiểm tra từ vựng tiếng Hàn dành cho người Việt Nam

---

## 1. Giới thiệu

**Korean Vocab Master** là ứng dụng web toàn diện chạy trên máy cục bộ (localhost) sử dụng PHP 8 và MySQL/MariaDB (thông qua XAMPP). Ứng dụng tập trung vào 3 chức năng cốt lõi:
1. **Kiểm tra từ vựng tiếng Hàn (Study / Quiz):** Hiển thị nghĩa tiếng Việt, người học tự gõ tiếng Hàn (Hangul) và nhấn Enter/Kiểm tra. Phản hồi tức thì Đúng (✓) / Sai (✗), không tự chuyển câu khi sai, gợi ý thông minh 3 cấp độ.
2. **Quản lý từ vựng (Vocabulary Management):** Thêm, sửa, xóa, tìm kiếm, phân loại theo chủ đề, loại từ, ví dụ minh họa và nhiều đáp án đúng bổ sung.
3. **Nhập từ vựng hàng loạt (CSV & Excel .xlsx):** Kéo thả file, xem trước dữ liệu mẫu, ánh xạ các cột linh hoạt, phát hiện trùng lặp, thông báo lỗi cụ thể theo từng dòng và nạp an toàn bằng transaction.

---

## 2. Cấu trúc thư mục

```text
korean-vocab/
├── index.php                  # Trang chủ, tổng quan và hướng dẫn
├── study.php                  # Chức năng A: Học & kiểm tra từ vựng tiếng Hàn
├── vocabulary.php             # Chức năng B: Quản lý từ vựng (CRUD)
├── import.php                 # Chức năng C: Nhập từ vựng từ CSV / Excel
├── config/
│   └── database.php           # Cấu hình kết nối MySQL PDO tập trung
├── includes/
│   ├── header.php             # Header chung, thanh điều hướng & trạng thái DB
│   ├── footer.php             # Footer chung, Toast container & nạp script
│   └── FileParser.php         # Lớp đọc file CSV (UTF-8) và Excel (.xlsx)
├── api/
│   ├── vocabulary.php         # API CRUD & lấy từ vựng theo chủ đề
│   ├── check_answer.php       # API kiểm tra câu trả lời & gợi ý 3 cấp độ
│   ├── import_preview.php     # API đọc và xem trước file tải lên
│   ├── import_confirm.php     # API xác nhận nạp dữ liệu vào MySQL
│   └── download_template.php  # API tải file CSV mẫu chuẩn UTF-8 BOM
├── assets/
│   ├── css/
│   │   └── style.css          # Tùy biến giao diện, phông Hangul, hiệu ứng hoạt họa
│   └── js/
│       ├── app.js             # Hàm tiện ích dùng chung (Toast, escapeHtml)
│       ├── study.js           # Xử lý kiểm tra, gõ Hangul, gợi ý, tính điểm
│       ├── vocabulary.js      # Xử lý CRUD, bảng phân trang, tìm kiếm
│       └── import.js          # Xử lý kéo thả, xem trước và nạp file
├── database/
│   └── korean_vocab.sql       # Script khởi tạo cơ sở dữ liệu và dữ liệu mẫu
├── templates/
│   └── vocabulary_template.csv# File CSV mẫu có sẵn dữ liệu chuẩn UTF-8
└── uploads/
    └── temp/                  # Thư mục lưu file tạm an toàn khi nhập dữ liệu
```

---

## 3. Hướng dẫn cài đặt và chạy trên Windows với XAMPP

### Bước 1: Khởi động XAMPP
1. Mở **XAMPP Control Panel** trên máy tính Windows.
2. Nhấn **Start** ở cả 2 dòng: **Apache** và **MySQL**.

### Bước 2: Đặt thư mục dự án
Sao chép thư mục `korean-vocab` vào thư mục `htdocs` của XAMPP:
`C:\xampp\htdocs\korean-vocab\`

### Bước 3: Import cơ sở dữ liệu vào MySQL
1. Mở trình duyệt web, truy cập: `http://localhost/phpmyadmin/`
2. Nhấn vào thẻ **Import** (hoặc **Nhập**) trên thanh menu trên cùng.
3. Nhấn **Choose File** (Chọn tệp) và trỏ tới file:
   `C:\xampp\htdocs\korean-vocab\database\korean_vocab.sql`
4. Nhấn nút **Import** (Thực hiện) ở cuối trang.
5. phpMyAdmin sẽ tự động tạo cơ sở dữ liệu `korean_vocab` và 4 bảng liên quan kèm 20 từ vựng mẫu.

### Bước 4: Kiểm tra cấu hình kết nối
File `config/database.php` đã được cấu hình mặc định tương thích 100% với XAMPP:
- Host: `127.0.0.1` (hoặc `localhost`)
- Port: `3306`
- Database: `korean_vocab`
- Username: `root`
- Password: `""` (để trống)

Nếu bạn có đặt mật khẩu cho tài khoản `root` trong MySQL, hãy mở file `config/database.php` và điền mật khẩu tại dòng `DB_PASS`.

### Bước 5: Mở website
Truy cập đường dẫn sau trên trình duyệt:
`http://localhost/korean-vocab/`

---

## 4. Hỗ trợ đọc file Excel (.xlsx)

Dự án hỗ trợ 2 cơ chế đọc file:
1. **File CSV (Khuyên dùng và hỗ trợ 100% ngay lập tức):** Sử dụng hàm phân tích UTF-8 tích hợp, tự động xử lý dấu phẩy, chấm phẩy, tab và xóa BOM.
2. **File Excel (.xlsx):**
   - Nếu bạn muốn dùng thư viện **PhpSpreadsheet**, mở PowerShell tại thư mục `korean-vocab` và chạy:
     ```powershell
     composer require phpoffice/phpspreadsheet
     ```
   - Nếu chưa cài Composer, bạn chỉ cần mở file Excel và chọn **Save As -> CSV UTF-8 (Comma delimited) (*.csv)** để nhập dữ liệu ngay lập tức mà không cần cài thêm bất kỳ thư viện nào!

---

## 5. Hướng dẫn kiểm tra từng chức năng

1. **Kiểm tra từ vựng (`study.php`):**
   - Thử nhập từ sai: Hệ thống hiển thị dấu ✗ đỏ, câu hỏi được giữ nguyên, cho phép thử lại vô hạn.
   - Nhấn nút "Gợi ý": Cấp 1 hiển thị chữ đầu và độ dài; cấp 2 hiển thị loại từ/phát âm; cấp 3 hiển thị nửa từ.
   - Thử nhập từ đúng: Hệ thống hiển thị dấu ✓ xanh, thông báo "Chính xác! Rất tốt!", mở khóa nút "Từ tiếp theo" và tăng tiến trình.
   - Nhấn phím Enter: Tự động kiểm tra hoặc chuyển từ tiếp theo khi đã làm đúng.

2. **Quản lý từ vựng (`vocabulary.php`):**
   - Nhấn "Thêm từ vựng mới", điền từ tiếng Hàn, tiếng Việt, phiên âm, chủ đề và đáp án phụ.
   - Sau khi lưu, quay lại trang `study.php`, từ mới thêm sẽ xuất hiện ngay trong bài học!
   - Thử chức năng tìm kiếm và lọc theo loại từ, chủ đề.

3. **Nhập file (`import.php`):**
   - Tải file CSV mẫu bằng nút "Tải file CSV mẫu (UTF-8)".
   - Kéo thả file vào khung tải lên -> Xem trước các dòng dữ liệu -> Kiểm tra các cột -> Nhấn "Xác nhận nhập dữ liệu".
