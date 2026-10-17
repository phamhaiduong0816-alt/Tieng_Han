<?php
/**
 * Chức năng A: Kiểm tra từ vựng tiếng Hàn (Study / Quiz)
 * Korean Vocab Master - Giai đoạn 1
 */

define('APP_INIT', true);
$pageTitle = 'Học & Kiểm Tra Từ Vựng';
$extraScripts = ['assets/js/study.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        
        <!-- Header & Bộ chọn chủ đề -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                    <span class="text-primary"><i class="bi bi-mortarboard-fill"></i></span>
                    Kiểm Tra Từ Vựng Tiếng Hàn
                </h4>
                <p class="text-muted small mb-0">Nhập đúng từ tiếng Hàn tương ứng với nghĩa tiếng Việt hiển thị</p>
            </div>

            <!-- Tùy chọn lọc chủ đề & khởi động lại -->
            <div class="d-flex align-items-center gap-2">
                <select id="topicSelect" class="form-select form-select-sm shadow-sm" style="min-width: 150px;">
                    <option value="all">Tất cả chủ đề</option>
                </select>
                <button id="btnRestart" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm" title="Khởi động lại lượt học">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span class="d-none d-sm-inline">Làm lại</span>
                </button>
            </div>
        </div>

        <!-- Trạng thái: Chưa có dữ liệu hoặc DB chưa kết nối -->
        <div id="emptyVocabState" class="card border-0 shadow-sm rounded-4 p-4 text-center d-none">
            <div class="my-4">
                <div class="display-3 text-muted mb-3">📭</div>
                <h5 class="fw-bold text-dark">Chưa có từ vựng nào trong hệ thống!</h5>
                <p class="text-muted mb-4 mx-auto" style="max-width: 480px;">
                    Hệ thống chưa tìm thấy từ vựng nào để bắt đầu lượt học. Bạn có thể thêm từ vựng thủ công hoặc nhập danh sách từ file CSV / Excel.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="vocabulary.php" class="btn btn-primary px-4 rounded-pill">
                        <i class="bi bi-plus-circle me-1"></i> Thêm từ vựng mới
                    </a>
                    <a href="import.php" class="btn btn-outline-success px-4 rounded-pill">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Nhập file CSV / Excel
                    </a>
                </div>
            </div>
        </div>

        <!-- Trạng thái: Màn hình kết thúc lượt học -->
        <div id="finishState" class="card border-0 shadow-sm rounded-4 p-4 text-center d-none">
            <div class="py-4">
                <div class="display-3 mb-3">🎉</div>
                <h3 class="fw-bold text-success mb-2">Chúc Mừng Bạn Đã Hoàn Thành Lượt Học!</h3>
                <p class="text-muted mb-4">Bạn đã trả lời xuất sắc tất cả các từ vựng trong lượt học này.</p>
                
                <div class="row justify-content-center mb-4">
                    <div class="col-sm-4 col-6 mb-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fs-3 fw-bold text-primary" id="summaryTotalWords">0</div>
                            <div class="small text-muted">Số từ đã hoàn thành</div>
                        </div>
                    </div>
                    <div class="col-sm-4 col-6 mb-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fs-3 fw-bold text-success" id="summaryAccuracy">100%</div>
                            <div class="small text-muted">Độ chuẩn xác</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button id="btnPlayAgain" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-play-circle-fill me-1"></i> Học lượt mới
                    </button>
                    <a href="vocabulary.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-medium">
                        <i class="bi bi-book me-1"></i> Xem danh sách từ vựng
                    </a>
                </div>
            </div>
        </div>

        <!-- Màn hình học tập chính -->
        <div id="studyActiveState">
            
            <!-- Thanh tiến trình học tập -->
            <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">
                        <i class="bi bi-flag-fill text-primary me-1"></i> Tiến độ học tập
                    </span>
                    <span id="progressText" class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-bold fs-7">
                        0 / 0 từ
                    </span>
                </div>
                <div class="progress-container">
                    <div id="studyProgressBar" class="progress-bar-custom h-100" style="width: 0%;"></div>
                </div>
            </div>

            <!-- Thẻ câu hỏi chính (Flashcard) -->
            <div class="study-card mb-4 position-relative">
                
                <!-- Huy hiệu loại từ và chủ đề -->
                <div class="study-prompt-container text-center">
                    <div class="d-flex justify-content-center gap-2 mb-2 flex-wrap">
                        <span id="badgeTopic" class="badge bg-primary-subtle text-primary border border-primary-subtle badge-tag">
                            Chủ đề
                        </span>
                        <span id="badgeWordType" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle badge-tag">
                            Loại từ
                        </span>
                        <span id="badgeCharCount" class="badge bg-info-subtle text-info-emphasis border border-info-subtle badge-tag">
                            0 ký tự
                        </span>
                    </div>

                    <!-- Nghĩa Tiếng Việt Nổi Bật -->
                    <div class="vietnamese-prompt my-2" id="vietnamesePrompt">
                        Đang tải từ vựng...
                    </div>
                    <div class="text-muted small">Hãy gõ từ tiếng Hàn tương ứng vào ô bên dưới:</div>
                </div>

                <!-- Thân thẻ: Ô nhập và Phản hồi -->
                <div class="p-4 p-md-5">
                    
                    <!-- Form Nhập câu trả lời -->
                    <form id="studyForm" autocomplete="off" onsubmit="return false;">
                        <div class="mb-3">
                            <input 
                                type="text" 
                                id="koreanInput" 
                                class="form-control korean-input font-korean" 
                                placeholder="Gõ từ tiếng Hàn (Hangul)..."
                                autofocus
                                lang="ko"
                            >
                        </div>

                        <!-- Hộp phản hồi Đúng / Sai -->
                        <div id="feedbackContainer" class="feedback-box d-flex align-items-center justify-content-center gap-2 p-3 mb-3 d-none">
                            <span id="feedbackIcon" class="mark-icon"></span>
                            <span id="feedbackMessage" class="fw-bold fs-6"></span>
                        </div>

                        <!-- Khung chi tiết bổ sung (Hiển thị khi trả lời đúng) -->
                        <div id="extraInfoContainer" class="p-3 mb-3 bg-light rounded-3 border d-none">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-success">Chuẩn xác:</span>
                                <span class="fw-bold fs-5 font-korean text-primary" id="correctKoreanText"></span>
                                <span class="text-muted small font-korean" id="pronunciationText"></span>
                            </div>
                            <div id="exampleBox" class="small text-muted mt-2 pt-2 border-top">
                                <div class="font-korean fw-medium text-dark" id="exampleKorean"></div>
                                <div class="fst-italic text-secondary" id="exampleVietnamese"></div>
                            </div>
                        </div>

                        <!-- Khung Gợi ý -->
                        <div id="hintContainer" class="p-3 mb-3 bg-warning-subtle text-warning-emphasis rounded-3 border border-warning-subtle d-none">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-lightbulb-fill text-warning fs-5"></i>
                                <span id="hintContent" class="fw-medium font-korean"></span>
                            </div>
                        </div>

                        <!-- Các nút bấm điều khiển -->
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-2">
                            <div class="d-flex gap-2">
                                <!-- Nút Gợi ý -->
                                <button type="button" id="btnHint" class="btn btn-outline-warning text-dark d-flex align-items-center gap-1 shadow-sm px-3 rounded-pill fw-medium">
                                    <i class="bi bi-lightbulb"></i>
                                    <span>Gợi ý (<span id="hintLevelText">1/3</span>)</span>
                                </button>

                                <!-- Nút Kết thúc sớm -->
                                <button type="button" id="btnEndEarly" class="btn btn-outline-danger d-flex align-items-center gap-1 shadow-sm px-3 rounded-pill fw-medium">
                                    <i class="bi bi-stop-circle"></i>
                                    <span>Kết thúc</span>
                                </button>
                            </div>

                            <div class="d-flex gap-2 ms-auto">
                                <!-- Nút Kiểm tra (Mặc định) -->
                                <button type="button" id="btnCheck" class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2">
                                    <span>Kiểm tra</span>
                                    <i class="bi bi-arrow-return-left small opacity-75"></i>
                                </button>

                                <!-- Nút Từ tiếp theo (Hiện khi đã trả lời đúng) -->
                                <button type="button" id="btnNext" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm d-flex align-items-center gap-2 d-none">
                                    <span>Từ tiếp theo</span>
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                </div>

            </div>

            <!-- Mẹo gõ tiếng Hàn cho người học -->
            <div class="card border-0 bg-transparent text-muted small px-3">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <i class="bi bi-keyboard"></i>
                    <span>Mẹo: Bạn có thể nhấn phím <kbd class="bg-white text-dark border px-1">Enter</kbd> để kiểm tra đáp án hoặc chuyển sang từ tiếp theo khi đã làm đúng.</span>
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
