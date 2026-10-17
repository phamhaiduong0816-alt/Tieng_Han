<?php
/**
 * API Xác nhận và Nhập dữ liệu Từ vựng vào Database
 * Korean Vocab Master - Giai đoạn 1
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/FileParser.php';

// Chỉ nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yêu cầu phương thức POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

$tempToken = basename($input['temp_token'] ?? '');
$duplicateMode = $input['duplicate_mode'] ?? 'skip'; // 'skip' hoặc 'update'
$defaultTopic = trim($input['default_topic'] ?? 'Cơ bản');
if ($defaultTopic === '') $defaultTopic = 'Cơ bản';

$mappingRaw = $input['mapping'] ?? [];
if (is_string($mappingRaw)) {
    $mapping = json_decode($mappingRaw, true) ?: [];
} else {
    $mapping = (array)$mappingRaw;
}

// Kiểm tra mã token file tạm
$tempPath = __DIR__ . '/../uploads/temp/' . $tempToken;
if (empty($tempToken) || !file_exists($tempPath)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Phiên tải file đã hết hạn hoặc file tạm không tồn tại. Vui lòng tải lại file.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra cột bắt buộc (tiếng Hàn & tiếng Việt)
$koreanIdx = isset($mapping['korean']) && $mapping['korean'] !== '' ? (int)$mapping['korean'] : null;
$vietnameseIdx = isset($mapping['vietnamese']) && $mapping['vietnamese'] !== '' ? (int)$mapping['vietnamese'] : null;

if ($koreanIdx === null || $vietnameseIdx === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng xác nhận và chỉ định cột Tiếng Hàn và cột Tiếng Việt.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pronIdx = isset($mapping['pronunciation']) && $mapping['pronunciation'] !== '' ? (int)$mapping['pronunciation'] : null;
$typeIdx = isset($mapping['word_type']) && $mapping['word_type'] !== '' ? (int)$mapping['word_type'] : null;
$exampleIdx = isset($mapping['example']) && $mapping['example'] !== '' ? (int)$mapping['example'] : null;
$exampleTransIdx = isset($mapping['example_translation']) && $mapping['example_translation'] !== '' ? (int)$mapping['example_translation'] : null;
$topicIdx = isset($mapping['topic']) && $mapping['topic'] !== '' ? (int)$mapping['topic'] : null;

try {
    $pdo = getDbConnection();
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $parsed = FileParser::parse($tempPath, $tempToken);
    $rows = $parsed['rows'];

    $importedCount = 0;
    $duplicateCount = 0;
    $errorCount = 0;
    $errors = [];
    $seenInFile = [];

    // Bắt đầu Transaction để đảm bảo tính toàn vẹn dữ liệu
    $pdo->beginTransaction();

    // Chuẩn bị các prepared statements
    $checkStmt = $pdo->prepare("SELECT id FROM vocabulary WHERE korean = :korean LIMIT 1");

    $insertVocabStmt = $pdo->prepare("INSERT INTO vocabulary (korean, vietnamese, pronunciation, word_type, example, example_translation, topic) 
                                      VALUES (:korean, :vietnamese, :pronunciation, :word_type, :example, :example_translation, :topic)");

    $insertAnsStmt = $pdo->prepare("INSERT INTO vocabulary_answers (vocab_id, answer, is_primary) 
                                    VALUES (:vocab_id, :answer, 1) 
                                    ON DUPLICATE KEY UPDATE is_primary = 1");

    $updateVocabStmt = $pdo->prepare("UPDATE vocabulary SET 
                                      vietnamese = :vietnamese, 
                                      pronunciation = :pronunciation, 
                                      word_type = :word_type, 
                                      example = :example, 
                                      example_translation = :example_translation, 
                                      topic = :topic 
                                      WHERE id = :id");

    foreach ($rows as $index => $row) {
        $lineNumber = $index + 2; // Dòng 1 là header

        $korean = isset($row[$koreanIdx]) ? trim((string)$row[$koreanIdx]) : '';
        $vietnamese = isset($row[$vietnameseIdx]) ? trim((string)$row[$vietnameseIdx]) : '';
        $pronunciation = $pronIdx !== null && isset($row[$pronIdx]) ? trim((string)$row[$pronIdx]) : '';
        $wordType = $typeIdx !== null && isset($row[$typeIdx]) ? trim((string)$row[$typeIdx]) : 'Danh từ';
        if ($wordType === '') $wordType = 'Danh từ';
        $example = $exampleIdx !== null && isset($row[$exampleIdx]) ? trim((string)$row[$exampleIdx]) : '';
        $exampleTrans = $exampleTransIdx !== null && isset($row[$exampleTransIdx]) ? trim((string)$row[$exampleTransIdx]) : '';
        $topic = $topicIdx !== null && isset($row[$topicIdx]) ? trim((string)$row[$topicIdx]) : '';
        if ($topic === '') $topic = $defaultTopic;

        // 1. Kiểm tra dữ liệu trống bắt buộc
        if ($korean === '' && $vietnamese === '') {
            continue; // Bỏ qua dòng trống hoàn toàn
        }

        if ($korean === '') {
            $errorCount++;
            $errors[] = "Dòng {$lineNumber}: Thiếu từ tiếng Hàn.";
            continue;
        }

        if ($vietnamese === '') {
            $errorCount++;
            $errors[] = "Dòng {$lineNumber}: Thiếu nghĩa tiếng Việt cho từ '{$korean}'.";
            continue;
        }

        // 2. Kiểm tra trùng lặp ngay trong file hiện tại
        if (isset($seenInFile[$korean])) {
            $duplicateCount++;
            $errors[] = "Dòng {$lineNumber}: Từ '{$korean}' bị trùng lặp với dòng {$seenInFile[$korean]} trong cùng file.";
            continue;
        }
        $seenInFile[$korean] = $lineNumber;

        // 3. Kiểm tra trùng lặp trong cơ sở dữ liệu
        $checkStmt->execute([':korean' => $korean]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $duplicateCount++;
            if ($duplicateMode === 'update') {
                // Chế độ cập nhật
                $vocabId = (int)$existing['id'];
                $updateVocabStmt->execute([
                    ':vietnamese' => $vietnamese,
                    ':pronunciation' => $pronunciation,
                    ':word_type' => $wordType,
                    ':example' => $example,
                    ':example_translation' => $exampleTrans,
                    ':topic' => $topic,
                    ':id' => $vocabId
                ]);
                $errors[] = "Dòng {$lineNumber}: Đã cập nhật lại thông tin cho từ '{$korean}'.";
                $importedCount++;
            } else {
                // Chế độ bỏ qua
                $errors[] = "Dòng {$lineNumber}: Từ '{$korean}' đã tồn tại trong database (đã bỏ qua).";
            }
            continue;
        }

        // 4. Thêm từ mới vào database
        $insertVocabStmt->execute([
            ':korean' => $korean,
            ':vietnamese' => $vietnamese,
            ':pronunciation' => $pronunciation,
            ':word_type' => $wordType,
            ':example' => $example,
            ':example_translation' => $exampleTrans,
            ':topic' => $topic
        ]);

        $newVocabId = (int)$pdo->lastInsertId();

        // Thêm đáp án chính vào bảng vocabulary_answers
        $insertAnsStmt->execute([
            ':vocab_id' => $newVocabId,
            ':answer' => $korean
        ]);

        $importedCount++;
    }

    $pdo->commit();

    // Xóa file tạm sau khi đã xử lý xong
    @unlink($tempPath);

    echo json_encode([
        'success' => true,
        'imported_count' => $importedCount,
        'duplicate_count' => $duplicateCount,
        'error_count' => $errorCount,
        'total_processed' => count($rows),
        'errors' => $errors,
        'message' => "Hoàn thành nhập dữ liệu! Đã thêm {$importedCount} từ, {$duplicateCount} từ trùng lặp, {$errorCount} lỗi."
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    @unlink($tempPath);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi nhập dữ liệu vào cơ sở dữ liệu: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
