<?php
/**
 * API Xem trước file nhập từ vựng (CSV / XLSX)
 * Korean Vocab Master - Giai đoạn 1
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/FileParser.php';

// Chỉ nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yêu cầu phương thức POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Không tìm thấy file tải lên hoặc quá trình tải lên bị lỗi.';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'Dung lượng file vượt quá giới hạn cho phép của máy chủ.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'Vui lòng chọn một file CSV hoặc Excel.';
                break;
        }
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $errorMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

$uploadedFile = $_FILES['file'];
$fileName = $uploadedFile['name'];
$fileSize = $uploadedFile['size'];
$tmpPath = $uploadedFile['tmp_name'];

// Kiểm tra kích thước file (tối đa 10MB)
if ($fileSize > 10 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dung lượng file không được vượt quá 10MB.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra định dạng đuôi file
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'xlsx'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file định dạng .csv hoặc .xlsx'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tạo thư mục lưu file tạm an toàn
$tempDir = __DIR__ . '/../uploads/temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Dọn dẹp các file tạm cũ hơn 2 giờ để tránh đầy ổ đĩa
$files = glob($tempDir . '/*');
$now = time();
if ($files) {
    foreach ($files as $f) {
        if (is_file($f) && ($now - filemtime($f) > 7200)) {
            @unlink($f);
        }
    }
}

// Lưu file tạm với tên bảo mật
$tempToken = bin2hex(random_bytes(16)) . '.' . $ext;
$targetTempPath = $tempDir . '/' . $tempToken;

if (!move_uploaded_file($tmpPath, $targetTempPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu file tạm để xử lý.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $parsed = FileParser::parse($targetTempPath, $fileName);
    $headers = $parsed['headers'];
    $rows = $parsed['rows'];
    $totalRows = $parsed['total_rows'];

    if ($totalRows === 0) {
        @unlink($targetTempPath);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File không chứa bất kỳ dòng dữ liệu nào.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Tự động nhận diện mapping các cột dựa trên tên tiêu đề
    $suggestedMapping = [
        'korean' => null,
        'vietnamese' => null,
        'pronunciation' => null,
        'word_type' => null,
        'example' => null,
        'example_translation' => null,
        'topic' => null
    ];

    foreach ($headers as $index => $header) {
        $h = mb_strtolower(trim($header), 'UTF-8');

        // Tiếng Hàn
        if ($suggestedMapping['korean'] === null && (
            str_contains($h, 'korean') || str_contains($h, 'hàn') || str_contains($h, 'hangul') || 
            str_contains($h, '한국어') || str_contains($h, '단어') || $h === 'word'
        )) {
            $suggestedMapping['korean'] = $index;
            continue;
        }

        // Tiếng Việt / Nghĩa
        if ($suggestedMapping['vietnamese'] === null && (
            str_contains($h, 'vietnamese') || str_contains($h, 'việt') || str_contains($h, 'nghĩa') || 
            str_contains($h, 'meaning') || str_contains($h, '베트남어')
        )) {
            $suggestedMapping['vietnamese'] = $index;
            continue;
        }

        // Phiên âm
        if ($suggestedMapping['pronunciation'] === null && (
            str_contains($h, 'pronunciation') || str_contains($h, 'phiên âm') || str_contains($h, 'phát âm') || 
            str_contains($h, 'cách đọc') || str_contains($h, 'roman') || str_contains($h, '발음')
        )) {
            $suggestedMapping['pronunciation'] = $index;
            continue;
        }

        // Loại từ
        if ($suggestedMapping['word_type'] === null && (
            str_contains($h, 'type') || str_contains($h, 'loại từ') || str_contains($h, 'từ loại') || 
            str_contains($h, 'pos') || str_contains($h, '품사')
        )) {
            $suggestedMapping['word_type'] = $index;
            continue;
        }

        // Câu ví dụ
        if ($suggestedMapping['example'] === null && (
            (str_contains($h, 'example') && !str_contains($h, 'trans') && !str_contains($h, 'dịch')) || 
            (str_contains($h, 'ví dụ') && !str_contains($h, 'dịch') && !str_contains($h, 'nghĩa')) || 
            str_contains($h, 'sentence') || str_contains($h, 'câu mẫu') || str_contains($h, '예문')
        )) {
            $suggestedMapping['example'] = $index;
            continue;
        }

        // Dịch ví dụ
        if ($suggestedMapping['example_translation'] === null && (
            str_contains($h, 'example_translation') || str_contains($h, 'dịch ví dụ') || 
            str_contains($h, 'nghĩa ví dụ') || str_contains($h, 'dịch câu') || str_contains($h, '해석')
        )) {
            $suggestedMapping['example_translation'] = $index;
            continue;
        }

        // Chủ đề
        if ($suggestedMapping['topic'] === null && (
            str_contains($h, 'topic') || str_contains($h, 'chủ đề') || str_contains($h, 'bài học') || 
            str_contains($h, 'bài') || str_contains($h, 'lesson') || str_contains($h, 'category') || str_contains($h, '주제')
        )) {
            $suggestedMapping['topic'] = $index;
            continue;
        }
    }

    // Nếu không khớp theo tên, tự động fallback theo vị trí mặc định nếu có >= 2 cột
    if ($suggestedMapping['korean'] === null && isset($headers[0])) $suggestedMapping['korean'] = 0;
    if ($suggestedMapping['vietnamese'] === null && isset($headers[1])) $suggestedMapping['vietnamese'] = 1;
    if ($suggestedMapping['pronunciation'] === null && isset($headers[2])) $suggestedMapping['pronunciation'] = 2;
    if ($suggestedMapping['word_type'] === null && isset($headers[3])) $suggestedMapping['word_type'] = 3;
    if ($suggestedMapping['example'] === null && isset($headers[4])) $suggestedMapping['example'] = 4;
    if ($suggestedMapping['example_translation'] === null && isset($headers[5])) $suggestedMapping['example_translation'] = 5;
    if ($suggestedMapping['topic'] === null && isset($headers[6])) $suggestedMapping['topic'] = 6;

    // Lấy tối đa 15 dòng đầu tiên để xem trước
    $previewRows = array_slice($rows, 0, 15);

    echo json_encode([
        'success' => true,
        'temp_token' => $tempToken,
        'filename' => htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8'),
        'headers' => $headers,
        'sample_rows' => $previewRows,
        'total_rows' => $totalRows,
        'suggested_mapping' => $suggestedMapping
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    @unlink($targetTempPath);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
