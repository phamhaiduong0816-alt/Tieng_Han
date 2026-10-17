<?php
/**
 * Cấu hình kết nối Cơ sở dữ liệu MySQL (XAMPP / MariaDB)
 * Korean Vocab Master - Giai đoạn 1
 */

require_once __DIR__ . '/../includes/mb_compat.php';

// Cấu hình thông số kết nối mặc định của XAMPP
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'korean_vocab');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Lấy đối tượng kết nối PDO tới MySQL
 * @return PDO
 * @throws PDOException
 */
function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Ghi log chi tiết lỗi cho lập trình viên
        error_log("Database connection error: " . $e->getMessage());
        throw new PDOException("Không thể kết nối đến cơ sở dữ liệu '" . DB_NAME . "'. Vui lòng kiểm tra MySQL trong XAMPP đã được bật và đã import database/korean_vocab.sql chưa.", (int)$e->getCode());
    }
}

/**
 * Kiểm tra trạng thái kết nối MySQL và sự tồn tại của database
 * @return array
 */
function checkDbConnection(): array {
    try {
        $pdo = getDbConnection();
        // Kiểm tra xem bảng vocabulary đã tồn tại chưa
        $stmt = $pdo->query("SHOW TABLES LIKE 'vocabulary'");
        $tableExists = $stmt->rowCount() > 0;

        if (!$tableExists) {
            return [
                'connected' => true,
                'tables_ready' => false,
                'message' => "Đã kết nối MySQL thành công nhưng chưa tìm thấy bảng 'vocabulary'. Vui lòng import file 'database/korean_vocab.sql' trong phpMyAdmin."
            ];
        }

        // Lấy số lượng từ vựng hiện có
        $countStmt = $pdo->query("SELECT COUNT(*) AS total FROM vocabulary");
        $count = $countStmt->fetchColumn();

        return [
            'connected' => true,
            'tables_ready' => true,
            'vocab_count' => (int)$count,
            'message' => "Kết nối cơ sở dữ liệu thành công! Hiện có " . $count . " từ vựng."
        ];
    } catch (PDOException $e) {
        return [
            'connected' => false,
            'tables_ready' => false,
            'vocab_count' => 0,
            'message' => $e->getMessage()
        ];
    }
}
