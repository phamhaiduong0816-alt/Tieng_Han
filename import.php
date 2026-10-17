<?php
/**
 * Chức năng C: Nhập từ vựng từ CSV và Excel
 * Korean Vocab Master - Giai đoạn 1
 */

define('APP_INIT', true);
$pageTitle = 'Nhập Từ Vựng Từ File';
$extraScripts = ['assets/js/import.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        
        <!-- Header & Tải file mẫu -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                    <span class="text-success"><i class="bi bi-file-earmark-arrow-up-fill"></i></span>
                    Nhập Từ Vựng Hàng Loạt
                </h4>
                <p class="text-muted small mb-0">Hỗ trợ nhập danh sách từ vựng từ file CSV (UTF-8) hoặc bảng tính Excel (.xlsx)</p>
            </div>
            <div>
                <a href="api/download_template.php" class="btn btn-outline-primary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-medium">
                    <i class="bi bi-download"></i>
                    <span>Tải file CSV mẫu (UTF-8)</span>
                </a>
            </div>
        </div>

        <!-- BƯỚC 1: Chọn hoặc Kéo Thả File -->
        <div id="stepUpload" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle">1</span>
                <span>Tải lên file dữ liệu (.csv hoặc .xlsx)</span>
            </h5>

            <!-- Drag & Drop Zone -->
            <div id="dropZone" class="dropzone mb-3">
                <div class="dropzone-icon">
                    <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Kéo thả file vào đây hoặc nhấn để chọn file</h5>
                <p class="text-muted small mb-3">Hỗ trợ các file định dạng: <strong>.csv</strong> hoặc <strong>.xlsx</strong> (Dung lượng tối đa: 10MB)</p>
                
                <input type="file" id="fileInput" class="d-none" accept=".csv, .xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, text/csv">
                
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnBrowseFile">
                    <i class="bi bi-folder2-open me-1"></i> Chọn file từ máy tính
                </button>
            </div>

            <!-- Lưu ý về định dạng file -->
            <div class="alert alert-info border-0 rounded-3 mb-0 small">
                <div class="fw-bold mb-1"><i class="bi bi-lightbulb-fill text-info me-1"></i> Lưu ý quan trọng:</div>
                <ul class="mb-0 ps-3">
                    <li>Cần có ít nhất 2 cột bắt buộc: <strong>Từ tiếng Hàn</strong> và <strong>Nghĩa tiếng Việt</strong>.</li>
                    <li>Đảm bảo file lưu bằng bảng mã <strong>UTF-8</strong> để không bị lỗi ký tự tiếng Hàn (Hangul) và dấu tiếng Việt.</li>
                    <li>Đối với file Excel (.xlsx), nếu máy chủ chưa cài thư viện, bạn có thể chạy lệnh <code>composer require phpoffice/phpspreadsheet</code> trong PowerShell hoặc lưu file dưới dạng CSV (UTF-8) để nhập tức thì.</li>
                </ul>
            </div>
        </div>

        <!-- BƯỚC 2: Xem Trước Dữ Liệu & Xác Nhận Ánh Xạ Cột -->
        <div id="stepPreview" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white d-none">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-circle">2</span>
                    <span>Xem trước dữ liệu & Cấu hình cột</span>
                </h5>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnCancelPreview">
                    <i class="bi bi-arrow-left me-1"></i> Chọn file khác
                </button>
            </div>

            <!-- Tóm tắt file tải lên -->
            <div class="p-3 bg-light rounded-3 border mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="text-muted small">File đã tải:</span>
                    <strong class="text-dark ms-1" id="previewFileName">data.csv</strong>
                </div>
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fw-bold" id="previewTotalRowsBadge">
                        0 dòng dữ liệu
                    </span>
                </div>
            </div>

            <!-- Cấu hình ánh xạ cột -->
            <h6 class="fw-bold text-dark mb-2">Xác nhận các cột tương ứng:</h6>
            <div class="row g-3 mb-4 p-3 bg-light rounded-3 border">
                
                <!-- Cột tiếng Hàn -->
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-bold small text-danger">
                        Từ tiếng Hàn (Hangul) *
                    </label>
                    <select id="mapKorean" class="form-select form-select-sm" required></select>
                </div>

                <!-- Cột tiếng Việt -->
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-bold small text-danger">
                        Nghĩa tiếng Việt *
                    </label>
                    <select id="mapVietnamese" class="form-select form-select-sm" required></select>
                </div>

                <!-- Cột phiên âm -->
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">
                        Phiên âm / Cách đọc
                    </label>
                    <select id="mapPronunciation" class="form-select form-select-sm"></select>
                </div>

                <!-- Cột loại từ -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">
                        Loại từ
                    </label>
                    <select id="mapWordType" class="form-select form-select-sm"></select>
                </div>

                <!-- Cột câu ví dụ -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">
                        Câu ví dụ tiếng Hàn
                    </label>
                    <select id="mapExample" class="form-select form-select-sm"></select>
                </div>

                <!-- Cột dịch câu ví dụ -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">
                        Dịch câu ví dụ
                    </label>
                    <select id="mapExampleTranslation" class="form-select form-select-sm"></select>
                </div>

                <!-- Cột chủ đề -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">
                        Chủ đề / Bài học
                    </label>
                    <select id="mapTopic" class="form-select form-select-sm"></select>
                </div>

                <!-- Tùy chọn xử lý trùng lặp & chủ đề mặc định -->
                <div class="col-md-6 mt-3">
                    <label class="form-label fw-semibold small text-dark">
                        Xử lý khi từ vựng đã tồn tại:
                    </label>
                    <select id="duplicateMode" class="form-select form-select-sm">
                        <option value="skip" selected>Bỏ qua từ bị trùng (Giữ nguyên từ cũ)</option>
                        <option value="update">Cập nhật thông tin mới đè lên từ cũ</option>
                    </select>
                </div>

                <div class="col-md-6 mt-3">
                    <label class="form-label fw-semibold small text-dark">
                        Chủ đề mặc định (nếu cột chủ đề trống):
                    </label>
                    <input type="text" id="defaultTopicInput" class="form-control form-control-sm" value="Cơ bản" placeholder="Ví dụ: Cơ bản">
                </div>

            </div>

            <!-- Bảng xem trước dữ liệu mẫu (10 dòng đầu) -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">Bảng xem trước (Các dòng đầu tiên):</h6>
                <small class="text-muted">Kiểm tra hiển thị chữ Hàn & tiếng Việt</small>
            </div>
            
            <div class="table-responsive border rounded-3 mb-4" style="max-height: 360px;">
                <table class="table table-sm table-striped table-hover mb-0 align-middle">
                    <thead class="table-light sticky-top" id="previewTableHead"></thead>
                    <tbody id="previewTableBody"></tbody>
                </table>
            </div>

            <!-- Nút xác nhận nhập -->
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light rounded-pill px-4" id="btnCancelImport">
                    Hủy bỏ
                </button>
                <button type="button" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm" id="btnConfirmImport">
                    <i class="bi bi-check-circle-fill me-1"></i> Xác nhận nhập dữ liệu
                </button>
            </div>
        </div>

        <!-- BƯỚC 3: Kết Quả Nhập Dữ Liệu -->
        <div id="stepResult" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white d-none">
            <div class="text-center py-3">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1" id="resultHeaderTitle">Nhập Dữ Liệu Hoàn Tất!</h4>
                <p class="text-muted small mb-4" id="resultHeaderSub">Kết quả thực hiện quá trình nhập từ vựng vào hệ thống</p>
                
                <!-- Thống kê số lượng -->
                <div class="row justify-content-center g-3 mb-4">
                    <div class="col-sm-3 col-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fs-3 fw-bold text-success" id="statImported">0</div>
                            <div class="small text-muted">Nhập thành công</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fs-3 fw-bold text-warning" id="statDuplicate">0</div>
                            <div class="small text-muted">Trùng lặp (Bỏ qua/Cập nhật)</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fs-3 fw-bold text-danger" id="statErrors">0</div>
                            <div class="small text-muted">Dòng lỗi / Thiếu dữ liệu</div>
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fs-3 fw-bold text-primary" id="statTotal">0</div>
                            <div class="small text-muted">Tổng số dòng xử lý</div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách chi tiết lỗi / cảnh báo theo từng dòng -->
                <div id="errorReportBox" class="text-start mb-4 d-none">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> 
                        Chi tiết cảnh báo & lỗi theo từng dòng:
                    </h6>
                    <div class="p-3 bg-light border rounded-3" style="max-height: 200px; overflow-y: auto;">
                        <ul class="mb-0 ps-3 small text-muted" id="errorList"></ul>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="study.php" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-mortarboard-fill me-1"></i> Bắt đầu học ngay
                    </a>
                    <a href="vocabulary.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-medium">
                        <i class="bi bi-book me-1"></i> Xem danh sách từ vựng
                    </a>
                    <button type="button" class="btn btn-light px-4 py-2 rounded-pill" id="btnImportAnother">
                        <i class="bi bi-file-earmark-plus me-1"></i> Nhập file khác
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
