<?php
/**
 * API Tải file CSV mẫu với mã hóa UTF-8 BOM
 * Đảm bảo hiển thị đúng 100% tiếng Hàn và tiếng Việt khi mở bằng Microsoft Excel trên Windows
 */

$templatePath = __DIR__ . '/../templates/vocabulary_template.csv';

if (!file_exists($templatePath)) {
    http_response_code(404);
    die('File mẫu không tồn tại.');
}

$content = file_get_contents($templatePath);

// Đảm bảo có UTF-8 BOM (\xEF\xBB\xBF) ở đầu file để Excel Windows tự động nhận diện UTF-8
if (substr($content, 0, 3) !== "\xEF\xBB\xBF") {
    $content = "\xEF\xBB\xBF" . $content;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="mau_nhap_tu_vung_tieng_han.csv"');
header('Content-Length: ' . strlen($content));
header('Pragma: no-cache');
header('Expires: 0');

echo $content;
exit;
