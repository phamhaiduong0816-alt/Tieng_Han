<?php
/**
 * Header giao diện chung
 * Korean Vocab Master - Giai đoạn 1
 */

require_once __DIR__ . '/../config/database.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$dbStatus = checkDbConnection();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Korean Vocab Master' : 'Korean Vocab Master - Học & Kiểm Tra Từ Vựng Tiếng Hàn' ?></title>
    
    <!-- Google Fonts: Be Vietnam Pro & Noto Sans KR -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Noto+Sans+KR:wght@400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/korean-vocab/assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <span class="brand-badge bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm">
                    🇰🇷
                </span>
                <span>Korean Vocab <span class="badge bg-warning text-dark fs-7 px-2 py-1 rounded-pill">Master</span></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-house-door me-1"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'study.php' ? 'active' : '' ?>" href="study.php">
                            <i class="bi bi-mortarboard me-1"></i> Học từ vựng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'vocabulary.php' ? 'active' : '' ?>" href="vocabulary.php">
                            <i class="bi bi-book me-1"></i> Quản lý từ vựng
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'import.php' ? 'active' : '' ?>" href="import.php">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Nhập file
                        </a>
                    </li>
                </ul>

                <!-- Trạng thái kết nối Database -->
                <div class="d-flex align-items-center ms-lg-3 mt-2 mt-lg-0">
                    <?php if ($dbStatus['connected'] && $dbStatus['tables_ready']): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill d-flex align-items-center gap-1" title="<?= htmlspecialchars($dbStatus['message']) ?>">
                            <span class="status-dot bg-success"></span>
                            <span class="d-none d-sm-inline"><?= $dbStatus['vocab_count'] ?> từ vựng</span>
                            <span class="d-inline d-sm-none">Sẵn sàng</span>
                        </span>
                    <?php else: ?>
                        <a href="index.php#db-guide" class="badge bg-danger text-white text-decoration-none px-3 py-2 rounded-pill d-flex align-items-center gap-1 shadow-sm pulse-badge" title="Nhấn để xem hướng dẫn kết nối">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>Chưa kết nối DB</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Thông báo nếu Database chưa sẵn sàng -->
    <?php if (!$dbStatus['connected'] || !$dbStatus['tables_ready']): ?>
    <div class="alert alert-warning border-0 rounded-0 shadow-sm mb-0 py-2">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill text-warning fs-5"></i>
                <small class="fw-medium text-dark">
                    <?= htmlspecialchars($dbStatus['message']) ?>
                </small>
            </div>
            <a href="index.php#db-guide" class="btn btn-sm btn-dark px-3 rounded-pill fw-semibold">
                Xem hướng dẫn import SQL
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Container -->
    <main class="flex-grow-1 py-4">
        <div class="container">
