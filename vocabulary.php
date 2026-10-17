<?php
/**
 * Chức năng B: Quản lý từ vựng (Vocabulary Management)
 * Korean Vocab Master - Giai đoạn 1
 */

define('APP_INIT', true);
$pageTitle = 'Quản Lý Từ Vựng';
$extraScripts = ['assets/js/vocabulary.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6 mb-3 mb-md-0">
        <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
            <span class="text-primary"><i class="bi bi-book-half"></i></span>
            Quản Lý Danh Sách Từ Vựng
        </h4>
        <p class="text-muted small mb-0">Thêm, sửa, xóa, tìm kiếm và phân loại các từ vựng tiếng Hàn</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold" id="btnOpenAddModal">
            <i class="bi bi-plus-lg me-1"></i> Thêm từ vựng mới
        </button>
        <a href="import.php" class="btn btn-outline-success rounded-pill px-3 shadow-sm ms-2 fw-medium">
            <i class="bi bi-file-earmark-arrow-up me-1"></i> Nhập CSV/Excel
        </a>
    </div>
</div>

<!-- Thanh công cụ tìm kiếm và bộ lọc -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="row g-2">
            <!-- Ô tìm kiếm -->
            <div class="col-lg-5 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input 
                        type="text" 
                        id="filterSearch" 
                        class="form-control border-start-0 bg-light" 
                        placeholder="Tìm theo tiếng Hàn, tiếng Việt hoặc phát âm..."
                    >
                    <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" title="Xóa tìm kiếm">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Bộ lọc chủ đề -->
            <div class="col-lg-3 col-md-3">
                <select id="filterTopic" class="form-select bg-light">
                    <option value="all">Tất cả chủ đề</option>
                </select>
            </div>

            <!-- Bộ lọc loại từ -->
            <div class="col-lg-3 col-md-3">
                <select id="filterWordType" class="form-select bg-light">
                    <option value="all">Tất cả loại từ</option>
                    <option value="Danh từ">Danh từ</option>
                    <option value="Động từ">Động từ</option>
                    <option value="Tính từ">Tính từ</option>
                    <option value="Phó từ">Phó từ</option>
                    <option value="Đại từ">Đại từ</option>
                    <option value="Cụm từ">Cụm từ</option>
                    <option value="Câu chào">Câu chào</option>
                    <option value="Câu cảm ơn">Câu cảm ơn</option>
                    <option value="Câu xin lỗi">Câu xin lỗi</option>
                    <option value="Khác">Khác</option>
                </select>
            </div>

            <!-- Nút làm mới bộ lọc -->
            <div class="col-lg-1 col-md-12 text-end">
                <button id="btnResetFilters" class="btn btn-light border w-100" title="Đặt lại bộ lọc">
                    <i class="bi bi-arrow-repeat"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bảng hiển thị danh sách từ vựng -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 custom-table">
            <thead>
                <tr>
                    <th style="width: 60px;" class="text-center">#</th>
                    <th style="min-width: 150px;">Tiếng Hàn</th>
                    <th style="min-width: 170px;">Nghĩa Tiếng Việt</th>
                    <th style="min-width: 130px;">Phiên âm</th>
                    <th style="width: 120px;">Loại từ</th>
                    <th style="width: 130px;">Chủ đề</th>
                    <th style="min-width: 140px;">Đáp án khác</th>
                    <th style="width: 110px;" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody id="vocabTableBody">
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        Đang tải danh sách từ vựng...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Phân trang và Thống kê tổng số -->
    <div class="card-footer bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="text-muted small" id="paginationInfo">
            Hiển thị 0 từ vựng
        </div>
        <nav aria-label="Vocabulary pagination">
            <ul class="pagination pagination-sm mb-0 justify-content-end" id="paginationList">
                <!-- Nút phân trang được tạo bằng JS -->
            </ul>
        </nav>
    </div>
</div>

<!-- MODAL: Thêm / Sửa từ vựng -->
<div class="modal fade" id="vocabModal" tabindex="-1" aria-labelledby="vocabModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="vocabModalLabel">
                    <i class="bi bi-pencil-square text-primary"></i>
                    <span>Thêm từ vựng mới</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="vocabForm" autocomplete="off">
                <input type="hidden" id="vocabId" name="id" value="">
                
                <div class="modal-body py-3">
                    <div class="row g-3">
                        
                        <!-- Từ tiếng Hàn -->
                        <div class="col-md-6">
                            <label for="inputKorean" class="form-label fw-semibold small">
                                Từ tiếng Hàn (Hangul) <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="inputKorean" 
                                name="korean" 
                                class="form-control font-korean fs-5 fw-bold" 
                                placeholder="Ví dụ: 감사합니다" 
                                required
                            >
                        </div>

                        <!-- Nghĩa tiếng Việt -->
                        <div class="col-md-6">
                            <label for="inputVietnamese" class="form-label fw-semibold small">
                                Nghĩa tiếng Việt <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="inputVietnamese" 
                                name="vietnamese" 
                                class="form-control fs-6" 
                                placeholder="Ví dụ: Cảm ơn" 
                                required
                            >
                        </div>

                        <!-- Phiên âm / Cách đọc -->
                        <div class="col-md-4">
                            <label for="inputPronunciation" class="form-label fw-semibold small">
                                Phiên âm / Romanization
                            </label>
                            <input 
                                type="text" 
                                id="inputPronunciation" 
                                name="pronunciation" 
                                class="form-control font-korean" 
                                placeholder="Ví dụ: gam-sa-ham-ni-da"
                            >
                        </div>

                        <!-- Loại từ -->
                        <div class="col-md-4">
                            <label for="inputWordType" class="form-label fw-semibold small">
                                Loại từ
                            </label>
                            <select id="inputWordType" name="word_type" class="form-select">
                                <option value="Danh từ">Danh từ</option>
                                <option value="Động từ">Động từ</option>
                                <option value="Tính từ">Tính từ</option>
                                <option value="Phó từ">Phó từ</option>
                                <option value="Đại từ">Đại từ</option>
                                <option value="Cụm từ">Cụm từ</option>
                                <option value="Câu chào">Câu chào</option>
                                <option value="Câu cảm ơn">Câu cảm ơn</option>
                                <option value="Câu xin lỗi">Câu xin lỗi</option>
                                <option value="Khác">Khác</option>
                            </select>
                        </div>

                        <!-- Chủ đề / Bài học -->
                        <div class="col-md-4">
                            <label for="inputTopic" class="form-label fw-semibold small">
                                Chủ đề / Bài học
                            </label>
                            <input 
                                type="text" 
                                id="inputTopic" 
                                name="topic" 
                                class="form-control" 
                                list="topicDataList" 
                                placeholder="Ví dụ: Giao tiếp"
                            >
                            <datalist id="topicDataList"></datalist>
                        </div>

                        <!-- Nhiều đáp án hợp lệ khác -->
                        <div class="col-12">
                            <label for="inputAlternateAnswers" class="form-label fw-semibold small d-flex justify-content-between">
                                <span>Đáp án tiếng Hàn hợp lệ bổ sung (cách nhau bởi dấu phẩy)</span>
                                <span class="text-muted fw-normal">Tùy chọn</span>
                            </label>
                            <input 
                                type="text" 
                                id="inputAlternateAnswers" 
                                name="alternate_answers" 
                                class="form-control font-korean" 
                                placeholder="Ví dụ: 고맙습니다, 고마워 (nếu chấp nhận nhiều cách trả lời)"
                            >
                            <div class="form-text small">
                                Người học gõ bất kỳ đáp án nào trong danh sách này đều sẽ được tính là trả lời đúng.
                            </div>
                        </div>

                        <!-- Câu ví dụ tiếng Hàn -->
                        <div class="col-12">
                            <label for="inputExample" class="form-label fw-semibold small">
                                Câu ví dụ tiếng Hàn
                            </label>
                            <textarea 
                                id="inputExample" 
                                name="example" 
                                class="form-control font-korean" 
                                rows="2" 
                                placeholder="Ví dụ: 도와주셔서 진심으로 감사합니다."
                            ></textarea>
                        </div>

                        <!-- Dịch nghĩa câu ví dụ -->
                        <div class="col-12">
                            <label for="inputExampleTranslation" class="form-label fw-semibold small">
                                Dịch nghĩa câu ví dụ (tiếng Việt)
                            </label>
                            <textarea 
                                id="inputExampleTranslation" 
                                name="example_translation" 
                                class="form-control" 
                                rows="2" 
                                placeholder="Ví dụ: Tôi chân thành cảm ơn vì đã giúp đỡ."
                            ></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" id="btnSaveVocab">
                        <i class="bi bi-check-lg me-1"></i> Lưu từ vựng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Xem chi tiết ví dụ -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <span class="font-korean text-primary fs-4" id="detailKorean"></span>
                    <span class="badge bg-secondary-subtle text-secondary small" id="detailWordType"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <div class="text-muted small">Nghĩa tiếng Việt:</div>
                    <div class="fs-5 fw-bold text-dark" id="detailVietnamese"></div>
                </div>
                <div class="mb-3" id="detailPronBox">
                    <div class="text-muted small">Phát âm:</div>
                    <div class="font-korean text-secondary" id="detailPron"></div>
                </div>
                <div class="mb-3" id="detailAltAnsBox">
                    <div class="text-muted small">Các đáp án hợp lệ khác:</div>
                    <div class="font-korean text-dark" id="detailAltAns"></div>
                </div>
                <div class="p-3 bg-light rounded-3 border" id="detailExampleBox">
                    <div class="text-muted small mb-1 fw-bold">Ví dụ minh họa:</div>
                    <div class="font-korean text-dark fw-medium mb-1" id="detailExample"></div>
                    <div class="text-muted fst-italic small" id="detailExampleTrans"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center p-3">
            <div class="modal-body py-3">
                <div class="display-6 text-danger mb-2">
                    <i class="bi bi-trash3-fill"></i>
                </div>
                <h6 class="fw-bold text-dark mb-2">Xác nhận xóa từ vựng?</h6>
                <p class="text-muted small mb-3">
                    Bạn có chắc chắn muốn xóa từ <strong class="font-korean text-dark" id="deleteWordLabel"></strong> khỏi hệ thống? Hành động này không thể hoàn tác.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-semibold" id="btnConfirmDelete">
                        Đồng ý xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
