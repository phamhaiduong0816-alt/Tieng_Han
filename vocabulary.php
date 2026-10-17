<?php
/**
 * API Quản lý Từ vựng (CRUD & Query)
 * Korean Vocab Master - Giai đoạn 1
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Xử lý ghi đè phương thức (PUT, DELETE) từ form POST
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

try {
    $pdo = getDbConnection();
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo);
        break;
    case 'PUT':
        handlePut($pdo);
        break;
    case 'DELETE':
        handleDelete($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ'], JSON_UNESCAPED_UNICODE);
        break;
}

/**
 * Xử lý truy vấn GET: danh sách từ, chi tiết từ, chủ đề, hoặc từ để học
 */
function handleGet(PDO $pdo): void {
    $action = $_GET['action'] ?? 'list';

    // 1. Lấy danh sách các chủ đề (topics) có sẵn
    if ($action === 'topics') {
        $stmt = $pdo->query("SELECT DISTINCT topic FROM vocabulary WHERE topic IS NOT NULL AND topic != '' ORDER BY topic ASC");
        $topics = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'topics' => $topics], JSON_UNESCAPED_UNICODE);
        return;
    }

    // 2. Lấy danh sách từ vựng phục vụ lượt kiểm tra (Study / Quiz)
    if ($action === 'study') {
        $topic = trim($_GET['topic'] ?? '');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        if ($limit <= 0 || $limit > 100) $limit = 20;

        $params = [];
        $sql = "SELECT id, korean, vietnamese, pronunciation, word_type, example, example_translation, topic 
                FROM vocabulary";

        if ($topic !== '' && $topic !== 'all') {
            $sql .= " WHERE topic = :topic";
            $params[':topic'] = $topic;
        }

        $sql .= " ORDER BY RAND() LIMIT " . $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $words = $stmt->fetchAll();

        // Không trả về danh sách đáp án thô để tránh gian lận phía client,
        // nhưng trả về độ dài ký tự và loại từ để hiển thị
        $studyWords = array_map(function($word) {
            return [
                'id' => (int)$word['id'],
                'vietnamese' => $word['vietnamese'],
                'pronunciation' => $word['pronunciation'] ?? '',
                'word_type' => $word['word_type'] ?? 'Từ vựng',
                'example' => $word['example'] ?? '',
                'example_translation' => $word['example_translation'] ?? '',
                'topic' => $word['topic'] ?? 'Cơ bản',
                'char_length' => mb_strlen(trim($word['korean']), 'UTF-8')
            ];
        }, $words);

        echo json_encode([
            'success' => true,
            'total' => count($studyWords),
            'words' => $studyWords
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // 3. Lấy chi tiết 1 từ vựng theo ID
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM vocabulary WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $vocab = $stmt->fetch();

        if (!$vocab) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy từ vựng này'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Lấy các đáp án hợp lệ kèm theo
        $ansStmt = $pdo->prepare("SELECT answer, is_primary FROM vocabulary_answers WHERE vocab_id = :id ORDER BY is_primary DESC, id ASC");
        $ansStmt->execute([':id' => $id]);
        $answers = $ansStmt->fetchAll();

        $vocab['answers'] = $answers;
        echo json_encode(['success' => true, 'vocabulary' => $vocab], JSON_UNESCAPED_UNICODE);
        return;
    }

    // 4. Lấy danh sách từ vựng (Quản lý - có phân trang, tìm kiếm và lọc)
    $search = trim($_GET['search'] ?? '');
    $topic = trim($_GET['topic'] ?? '');
    $wordType = trim($_GET['word_type'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 15)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(korean LIKE :search OR vietnamese LIKE :search OR pronunciation LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if ($topic !== '' && $topic !== 'all') {
        $where[] = "topic = :topic";
        $params[':topic'] = $topic;
    }

    if ($wordType !== '' && $wordType !== 'all') {
        $where[] = "word_type = :word_type";
        $params[':word_type'] = $wordType;
    }

    $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    // Đếm tổng số bản ghi
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM vocabulary {$whereSql}");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    // Lấy dữ liệu theo trang
    $sql = "SELECT v.*, 
            (SELECT GROUP_CONCAT(answer SEPARATOR ', ') FROM vocabulary_answers WHERE vocab_id = v.id) as all_answers
            FROM vocabulary v 
            {$whereSql} 
            ORDER BY v.id DESC 
            LIMIT {$limit} OFFSET {$offset}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $records,
        'pagination' => [
            'current_page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Xử lý thêm từ vựng mới (POST)
 */
function handlePost(PDO $pdo): void {
    // Đọc dữ liệu từ form data hoặc JSON
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }

    $korean = trim($input['korean'] ?? '');
    $vietnamese = trim($input['vietnamese'] ?? '');
    $pronunciation = trim($input['pronunciation'] ?? '');
    $wordType = trim($input['word_type'] ?? 'Danh từ');
    $example = trim($input['example'] ?? '');
    $exampleTranslation = trim($input['example_translation'] ?? '');
    $topic = trim($input['topic'] ?? 'Cơ bản');
    if ($topic === '') $topic = 'Cơ bản';

    // Xử lý các đáp án hợp lệ bổ sung
    $alternateAnswers = $input['alternate_answers'] ?? [];
    if (is_string($alternateAnswers)) {
        $alternateAnswers = array_filter(array_map('trim', explode(',', $alternateAnswers)));
    }

    // Kiểm tra dữ liệu bắt buộc
    if ($korean === '' || $vietnamese === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập đầy đủ từ tiếng Hàn và nghĩa tiếng Việt.'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    // Bắt đầu transaction
    $pdo->beginTransaction();
    try {
        // Kiểm tra xem từ tiếng Hàn đã tồn tại chưa
        $checkStmt = $pdo->prepare("SELECT id FROM vocabulary WHERE korean = :korean LIMIT 1");
        $checkStmt->execute([':korean' => $korean]);
        if ($checkStmt->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => "Từ tiếng Hàn '{$korean}' đã tồn tại trong hệ thống."
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Thêm vào bảng vocabulary
        $stmt = $pdo->prepare("INSERT INTO vocabulary (korean, vietnamese, pronunciation, word_type, example, example_translation, topic) 
                               VALUES (:korean, :vietnamese, :pronunciation, :word_type, :example, :example_translation, :topic)");
        $stmt->execute([
            ':korean' => $korean,
            ':vietnamese' => $vietnamese,
            ':pronunciation' => $pronunciation,
            ':word_type' => $wordType,
            ':example' => $example,
            ':example_translation' => $exampleTranslation,
            ':topic' => $topic
        ]);

        $vocabId = (int)$pdo->lastInsertId();

        // Thêm đáp án chính vào bảng vocabulary_answers
        $ansStmt = $pdo->prepare("INSERT INTO vocabulary_answers (vocab_id, answer, is_primary) VALUES (:vocab_id, :answer, 1)");
        $ansStmt->execute([
            ':vocab_id' => $vocabId,
            ':answer' => $korean
        ]);

        // Thêm các đáp án thay thế (nếu có)
        if (!empty($alternateAnswers)) {
            $altStmt = $pdo->prepare("INSERT INTO vocabulary_answers (vocab_id, answer, is_primary) VALUES (:vocab_id, :answer, 0)");
            foreach ($alternateAnswers as $alt) {
                $alt = trim($alt);
                if ($alt !== '' && $alt !== $korean) {
                    $altStmt->execute([
                        ':vocab_id' => $vocabId,
                        ':answer' => $alt
                    ]);
                }
            }
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => "Thêm từ '{$korean}' thành công!",
            'vocab_id' => $vocabId
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi lưu từ vựng: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Xử lý cập nhật từ vựng (PUT)
 */
function handlePut(PDO $pdo): void {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }

    $id = (int)($input['id'] ?? 0);
    $korean = trim($input['korean'] ?? '');
    $vietnamese = trim($input['vietnamese'] ?? '');
    $pronunciation = trim($input['pronunciation'] ?? '');
    $wordType = trim($input['word_type'] ?? 'Danh từ');
    $example = trim($input['example'] ?? '');
    $exampleTranslation = trim($input['example_translation'] ?? '');
    $topic = trim($input['topic'] ?? 'Cơ bản');
    if ($topic === '') $topic = 'Cơ bản';

    $alternateAnswers = $input['alternate_answers'] ?? [];
    if (is_string($alternateAnswers)) {
        $alternateAnswers = array_filter(array_map('trim', explode(',', $alternateAnswers)));
    }

    if ($id <= 0 || $korean === '' || $vietnamese === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ. Vui lòng cung cấp ID, từ tiếng Hàn và nghĩa tiếng Việt.'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    $pdo->beginTransaction();
    try {
        // Cập nhật bảng vocabulary
        $stmt = $pdo->prepare("UPDATE vocabulary SET 
            korean = :korean,
            vietnamese = :vietnamese,
            pronunciation = :pronunciation,
            word_type = :word_type,
            example = :example,
            example_translation = :example_translation,
            topic = :topic
            WHERE id = :id");

        $stmt->execute([
            ':korean' => $korean,
            ':vietnamese' => $vietnamese,
            ':pronunciation' => $pronunciation,
            ':word_type' => $wordType,
            ':example' => $example,
            ':example_translation' => $exampleTranslation,
            ':topic' => $topic,
            ':id' => $id
        ]);

        // Cập nhật lại các đáp án hợp lệ: xóa đáp án cũ và nạp lại
        $delAns = $pdo->prepare("DELETE FROM vocabulary_answers WHERE vocab_id = :id");
        $delAns->execute([':id' => $id]);

        // Thêm đáp án chính
        $ansStmt = $pdo->prepare("INSERT INTO vocabulary_answers (vocab_id, answer, is_primary) VALUES (:vocab_id, :answer, 1)");
        $ansStmt->execute([
            ':vocab_id' => $id,
            ':answer' => $korean
        ]);

        // Thêm các đáp án thay thế
        if (!empty($alternateAnswers)) {
            $altStmt = $pdo->prepare("INSERT INTO vocabulary_answers (vocab_id, answer, is_primary) VALUES (:vocab_id, :answer, 0)");
            foreach ($alternateAnswers as $alt) {
                $alt = trim($alt);
                if ($alt !== '' && $alt !== $korean) {
                    $altStmt->execute([
                        ':vocab_id' => $id,
                        ':answer' => $alt
                    ]);
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Cập nhật từ '{$korean}' thành công!"
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi cập nhật từ vựng: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Xử lý xóa từ vựng (DELETE)
 */
function handleDelete(PDO $pdo): void {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    }

    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID từ vựng không hợp lệ'], JSON_UNESCAPED_UNICODE);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM vocabulary WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa từ vựng thành công!'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy từ vựng cần xóa'], JSON_UNESCAPED_UNICODE);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa từ vựng: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}
