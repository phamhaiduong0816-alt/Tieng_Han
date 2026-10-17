<?php
/**
 * Trang chủ Korean Vocab Master - Giai đoạn 1
 */

define('APP_INIT', true);
$pageTitle = 'Trang Chủ - Học & Kiểm Tra Từ Vựng Tiếng Hàn';

require_once __DIR__ . '/config/database.php';

$dbStatus = checkDbConnection();
$totalWords = 0;
$totalTopics = 0;
$recentWords = [];

if ($dbStatus['connected'] && $dbStatus['tables_ready']) {
    try {
        $pdo = getDbConnection();
        $totalWords = (int)$pdo->query("SELECT COUNT(*) FROM vocabulary")->fetchColumn();
        $totalTopics = (int)$pdo->query("SELECT COUNT(DISTINCT topic) FROM vocabulary WHERE topic IS NOT NULL AND topic != ''")->fetchColumn();
        
        $recentStmt = $pdo->query("SELECT id, korean, vietnamese, pronunciation, word_type, topic FROM vocabulary ORDER BY id DESC LIMIT 6");
        $recentWords = $recentStmt->fetchAll();
    } catch (Exception $e) {
        // Không để trang bị lỗi nếu có ngoại lệ
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<div class="row align-items-center mb-5 py-3">
    <div class="col-lg-7 mb-4 mb-lg-0">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-primary-subtle text-primary border border-primary-subtle rounded-pill small fw-bold mb-3">
            <span>🇰🇷 Phiên bản Giai đoạn 1</span>
            <span>&bull;</span>
            <span>Sẵn sàng trên Localhost</span>
        </div>
        <h1 class="display-5 fw-bold text-dark lh-sm mb-3">
            Làm Chủ Từ Vựng Tiếng Hàn <br>
            <span class="text-primary">Chính Xác &amp; Ghi Nhớ Sâu</span>
        </h1>
        <p class="lead text-muted mb-4 fs-6">
            Website học và kiểm tra từ vựng tiếng Hàn thiết kế chuẩn mực dành riêng cho người Việt Nam. Tự động kiểm tra phát âm, hỗ trợ gõ Hangul, nhập hàng loạt từ Excel/CSV và quản lý kho từ linh hoạt.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <a href="study.php" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-play-circle-fill fs-5"></i>
                <span>Bắt đầu kiểm tra từ vựng</span>
            </a>
            <a href="vocabulary.php" class="btn btn-outline-secondary btn-lg rounded-pill px-4 fw-medium">
                <i class="bi bi-book me-1"></i> Quản lý từ vựng
            </a>
            <a href="import.php" class="btn btn-outline-success btn-lg rounded-pill px-3 fw-medium">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Nhập file
            </a>
        </div>
    </div>
    
    <div class="col-lg-5">
        <!-- Thẻ thống kê tổng quan -->
        <div class="card border-0 shadow-lg rounded-4 p-4 bg-white position-relative overflow-hidden">
            <div class="position-absolute top-0 end-0 p-3 opacity-10">
                <span style="font-size: 7rem;">🇰🇷</span>
            </div>
            <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary"></i>
                <span>Tổng quan kho dữ liệu</span>
            </h5>
            
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small">Từ vựng hiện có</div>
                        <div class="display-6 fw-bold text-primary"><?= number_format($totalWords) ?></div>
                        <div class="small text-muted">từ vựng</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small">Chủ đề phân loại</div>
                        <div class="display-6 fw-bold text-success"><?= number_format($totalTopics) ?></div>
                        <div class="small text-muted">bài học / chủ đề</div>
                    </div>
                </div>
            </div>

            <div class="p-3 rounded-3 <?= $dbStatus['connected'] && $dbStatus['tables_ready'] ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?> d-flex align-items-center gap-2 small">
                <i class="bi <?= $dbStatus['connected'] && $dbStatus['tables_ready'] ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning' ?> fs-5"></i>
                <div>
                    <strong>Cơ sở dữ liệu:</strong> <?= htmlspecialchars($dbStatus['message']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3 Khối chức năng cốt lõi -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover bg-white">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-4 mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                <i class="bi bi-check2-circle"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">1. Kiểm Tra Từ Vựng</h5>
            <p class="text-muted small mb-4">
                Lấy từ vựng theo chủ đề, gõ từ tiếng Hàn với phím Enter, nhận phản hồi đúng/sai tức thì (✓/✗) và gợi ý từng bước thông minh.
            </p>
            <a href="study.php" class="btn btn-sm btn-outline-primary rounded-pill mt-auto fw-semibold">
                Bắt đầu học <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover bg-white">
            <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-4 mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                <i class="bi bi-card-checklist"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">2. Quản Lý Từ Vựng</h5>
            <p class="text-muted small mb-4">
                Thêm từ mới, nhiều đáp án hợp lệ, câu ví dụ, phát âm, sửa, xóa và tìm kiếm nhanh chóng. Dữ liệu thêm mới có thể kiểm tra ngay.
            </p>
            <a href="vocabulary.php" class="btn btn-sm btn-outline-success rounded-pill mt-auto fw-semibold">
                Quản lý kho từ <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover bg-white">
            <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle text-info-emphasis rounded-4 mb-3" style="width: 52px; height: 52px; font-size: 1.5rem;">
                <i class="bi bi-file-earmark-spreadsheet"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">3. Nhập File CSV &amp; Excel</h5>
            <p class="text-muted small mb-4">
                Kéo thả file .csv hoặc .xlsx, xem trước bảng dữ liệu, cấu hình ghép cột linh hoạt, phát hiện trùng lặp và nhập an toàn bằng transaction.
            </p>
            <a href="import.php" class="btn btn-sm btn-outline-info rounded-pill mt-auto fw-semibold text-info-emphasis">
                Nhập từ vựng <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Từ vựng mới cập nhật -->
<?php if (!empty($recentWords)): ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mb-5 bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-stars text-warning"></i>
            <span>Từ vựng gần đây trong kho</span>
        </h5>
        <a href="vocabulary.php" class="btn btn-sm btn-link text-decoration-none">Xem tất cả &rarr;</a>
    </div>
    
    <div class="row g-3">
        <?php foreach ($recentWords as $rw): ?>
        <div class="col-lg-4 col-md-6">
            <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="font-korean fs-5 fw-bold text-primary"><?= htmlspecialchars($rw['korean']) ?></span>
                        <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($rw['word_type'] ?? 'Danh từ') ?></span>
                    </div>
                    <div class="fw-medium text-dark mb-1"><?= htmlspecialchars($rw['vietnamese']) ?></div>
                    <?php if (!empty($rw['pronunciation'])): ?>
                        <div class="small text-muted font-korean">[<?= htmlspecialchars($rw['pronunciation']) ?>]</div>
                    <?php endif; ?>
                </div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary-subtle text-primary small"><?= htmlspecialchars($rw['topic'] ?? 'Cơ bản') ?></span>
                    <a href="study.php?topic=<?= urlencode($rw['topic'] ?? 'all') ?>" class="small text-decoration-none fw-semibold">Luyện từ này &rarr;</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Hướng dẫn thiết lập XAMPP & Import Database (Mục neo #db-guide) -->
<div id="db-guide" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-terminal-split text-primary"></i>
        <span>Hướng dẫn chạy Website trên localhost với XAMPP (Windows)</span>
    </h5>
    
    <div class="row g-4">
        <div class="col-md-6">
            <h6 class="fw-bold text-primary mb-2">Bước 1: Bật XAMPP Control Panel</h6>
            <ol class="small text-muted ps-3 mb-3">
                <li class="mb-1">Mở <strong>XAMPP Control Panel</strong> trên máy tính Windows.</li>
                <li class="mb-1">Nhấn <strong>Start</strong> tại cả 2 dòng: <strong>Apache</strong> và <strong>MySQL</strong>.</li>
            </ol>

            <h6 class="fw-bold text-primary mb-2">Bước 2: Import Cơ sở dữ liệu</h6>
            <ol class="small text-muted ps-3 mb-0">
                <li class="mb-1">Mở trình duyệt, truy cập <a href="http://localhost/phpmyadmin" target="_blank" class="fw-bold">http://localhost/phpmyadmin</a>.</li>
                <li class="mb-1">Nhấn thẻ <strong>Import (Nhập)</strong> ở thanh trên cùng.</li>
                <li class="mb-1">Chọn file <code>database/korean_vocab.sql</code> trong thư mục dự án và nhấn <strong>Import (Thực hiện)</strong>.</li>
            </ol>
        </div>

        <div class="col-md-6">
            <h6 class="fw-bold text-primary mb-2">Bước 3: Vị trí thư mục &amp; Cấu hình kết nối</h6>
            <ul class="small text-muted ps-3 mb-3">
                <li class="mb-1">Đặt project vào thư mục: <code>C:\xampp\htdocs\korean-vocab\</code></li>
                <li class="mb-1">File cấu hình đặt tại: <code>config/database.php</code></li>
                <li class="mb-1">Thông số mặc định XAMPP: Host: <code>localhost</code>, Database: <code>korean_vocab</code>, User: <code>root</code>, Password: để trống <code>""</code>.</li>
            </ul>

            <h6 class="fw-bold text-primary mb-2">Bước 4: Mở ứng dụng trong trình duyệt</h6>
            <div class="p-2 bg-light rounded-3 border text-center">
                <a href="http://localhost/korean-vocab/" target="_blank" class="fw-bold text-primary text-decoration-none fs-6">
                    http://localhost/korean-vocab/
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
