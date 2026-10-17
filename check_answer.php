<?php
/**
 * API Kiểm tra Đáp án & Cung cấp Gợi ý
 * Korean Vocab Master - Giai đoạn 1
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDbConnection();
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'check');

if ($action === 'hint') {
    handleHint($pdo);
} else {
    handleCheckAnswer($pdo);
}

/**
 * Chuẩn hóa chuỗi tiếng Hàn (loại bỏ khoảng trắng thừa, chuẩn hóa Unicode NFC nếu có)
 */
function normalizeKoreanString(string $str): string {
    $str = trim($str);
    // Chuẩn hóa Unicode NFC nếu extension intl khả dụng
    if (function_exists('normalizer_normalize') && class_exists('Normalizer')) {
        $normalized = normalizer_normalize($str, Normalizer::FORM_C);
        if ($normalized !== false) {
            $str = $normalized;
        }
    }
    return $str;
}

/**
 * Kiểm tra câu trả lời của người học
 */
function handleCheckAnswer(PDO $pdo): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Yêu cầu phương thức POST'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }

    $vocabId = (int)($input['vocab_id'] ?? 0);
    $userAnswerRaw = (string)($input['user_answer'] ?? '');
    $userAnswer = normalizeKoreanString($userAnswerRaw);

    if ($vocabId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mã từ vựng không hợp lệ'], JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($userAnswer === '') {
        echo json_encode([
            'success' => true,
            'is_correct' => false,
            'message' => 'Vui lòng nhập từ tiếng Hàn trước khi kiểm tra!'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Lấy thông tin từ vựng và các đáp án hợp lệ từ cơ sở dữ liệu
    $stmt = $pdo->prepare("SELECT id, korean, vietnamese, pronunciation, word_type, example, example_translation 
                           FROM vocabulary WHERE id = :id");
    $stmt->execute([':id' => $vocabId]);
    $vocab = $stmt->fetch();

    if (!$vocab) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy từ vựng này'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Lấy danh sách tất cả các đáp án hợp lệ cho từ này
    $ansStmt = $pdo->prepare("SELECT answer FROM vocabulary_answers WHERE vocab_id = :id");
    $ansStmt->execute([':id' => $vocabId]);
    $answers = $ansStmt->fetchAll(PDO::FETCH_COLUMN);

    // Luôn đảm bảo từ gốc trong bảng vocabulary cũng là đáp án hợp lệ
    $allValidAnswers = array_unique(array_merge([$vocab['korean']], $answers));

    $isCorrect = false;
    foreach ($allValidAnswers as $validAns) {
        if (normalizeKoreanString($validAns) === $userAnswer) {
            $isCorrect = true;
            break;
        }
    }

    if ($isCorrect) {
        // Đúng: Trả về thông báo thành công và chi tiết để củng cố kiến thức
        echo json_encode([
            'success' => true,
            'is_correct' => true,
            'message' => 'Chính xác! Rất tốt!',
            'details' => [
                'korean' => $vocab['korean'],
                'vietnamese' => $vocab['vietnamese'],
                'pronunciation' => $vocab['pronunciation'] ?? '',
                'word_type' => $vocab['word_type'] ?? '',
                'example' => $vocab['example'] ?? '',
                'example_translation' => $vocab['example_translation'] ?? ''
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Sai: Chỉ báo chưa chính xác, KHÔNG tiết lộ đáp án đúng
        echo json_encode([
            'success' => true,
            'is_correct' => false,
            'message' => 'Chưa chính xác, hãy thử lại!'
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Xử lý yêu cầu gợi ý thông minh
 * Tiết lộ từng phần: Ký tự đầu tiên, độ dài từ, loại từ; không hiển thị toàn bộ
 */
function handleHint(PDO $pdo): void {
    $vocabId = (int)($_GET['vocab_id'] ?? ($_POST['vocab_id'] ?? 0));
    $level = max(1, min(3, (int)($_GET['level'] ?? ($_POST['level'] ?? 1))));

    if ($vocabId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mã từ vựng không hợp lệ'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $stmt = $pdo->prepare("SELECT korean, word_type, pronunciation FROM vocabulary WHERE id = :id");
    $stmt->execute([':id' => $vocabId]);
    $vocab = $stmt->fetch();

    if (!$vocab) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy từ vựng'], JSON_UNESCAPED_UNICODE);
        return;
    }

    $korean = $vocab['korean'];
    $charCount = mb_strlen($korean, 'UTF-8');

    $hintText = '';
    $hintType = '';

    if ($level === 1) {
        // Cấp độ 1: Hiển thị chữ cái đầu tiên và số lượng ký tự
        $firstChar = mb_substr($korean, 0, 1, 'UTF-8');
        $masked = $firstChar;
        for ($i = 1; $i < $charCount; $i++) {
            $char = mb_substr($korean, $i, 1, 'UTF-8');
            $masked .= ($char === ' ') ? ' ' : ' _';
        }
        $hintText = "Gợi ý 1: Từ bắt đầu bằng \"{$firstChar}\" (Tổng cộng {$charCount} ký tự): {$masked}";
        $hintType = 'level_1';
    } elseif ($level === 2) {
        // Cấp độ 2: Hiển thị thêm phiên âm (nếu có) và loại từ
        $pron = !empty($vocab['pronunciation']) ? " | Phiên âm: {$vocab['pronunciation']}" : '';
        $hintText = "Gợi ý 2: Loại từ: [{$vocab['word_type']}]{$pron}";
        $hintType = 'level_2';
    } else {
        // Cấp độ 3: Hiển thị nửa số ký tự đầu tiên
        $revealCount = max(1, (int)floor($charCount / 2));
        $prefix = mb_substr($korean, 0, $revealCount, 'UTF-8');
        $masked = $prefix;
        for ($i = $revealCount; $i < $charCount; $i++) {
            $char = mb_substr($korean, $i, 1, 'UTF-8');
            $masked .= ($char === ' ') ? ' ' : ' _';
        }
        $hintText = "Gợi ý 3: Nửa đầu từ vựng: {$masked}";
        $hintType = 'level_3';
    }

    echo json_encode([
        'success' => true,
        'level' => $level,
        'hint' => $hintText,
        'type' => $hintType
    ], JSON_UNESCAPED_UNICODE);
}
