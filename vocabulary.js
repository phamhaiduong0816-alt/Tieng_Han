/**
 * JavaScript Quản lý Từ vựng (CRUD, Tìm kiếm, Phân trang)
 * Korean Vocab Master - Giai đoạn 1
 */

document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements
    const vocabTableBody = document.getElementById('vocabTableBody');
    const paginationList = document.getElementById('paginationList');
    const paginationInfo = document.getElementById('paginationInfo');
    const filterSearch = document.getElementById('filterSearch');
    const filterTopic = document.getElementById('filterTopic');
    const filterWordType = document.getElementById('filterWordType');
    const btnClearSearch = document.getElementById('btnClearSearch');
    const btnResetFilters = document.getElementById('btnResetFilters');
    const btnOpenAddModal = document.getElementById('btnOpenAddModal');
    const topicDataList = document.getElementById('topicDataList');

    // Modals
    const vocabModal = new bootstrap.Modal(document.getElementById('vocabModal'));
    const vocabModalLabel = document.getElementById('vocabModalLabel');
    const vocabForm = document.getElementById('vocabForm');
    const vocabId = document.getElementById('vocabId');
    const inputKorean = document.getElementById('inputKorean');
    const inputVietnamese = document.getElementById('inputVietnamese');
    const inputPronunciation = document.getElementById('inputPronunciation');
    const inputWordType = document.getElementById('inputWordType');
    const inputTopic = document.getElementById('inputTopic');
    const inputAlternateAnswers = document.getElementById('inputAlternateAnswers');
    const inputExample = document.getElementById('inputExample');
    const inputExampleTranslation = document.getElementById('inputExampleTranslation');
    const btnSaveVocab = document.getElementById('btnSaveVocab');

    // Detail Modal
    const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    const detailKorean = document.getElementById('detailKorean');
    const detailWordType = document.getElementById('detailWordType');
    const detailVietnamese = document.getElementById('detailVietnamese');
    const detailPron = document.getElementById('detailPron');
    const detailPronBox = document.getElementById('detailPronBox');
    const detailAltAns = document.getElementById('detailAltAns');
    const detailAltAnsBox = document.getElementById('detailAltAnsBox');
    const detailExample = document.getElementById('detailExample');
    const detailExampleTrans = document.getElementById('detailExampleTrans');
    const detailExampleBox = document.getElementById('detailExampleBox');

    // Delete Modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteWordLabel = document.getElementById('deleteWordLabel');
    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
    let deletingVocabId = null;

    // State
    let currentPage = 1;
    let searchDebounceTimeout = null;

    // 1. Tải danh sách chủ đề cho bộ lọc và datalist
    async function loadTopics() {
        try {
            const res = await fetch('api/vocabulary.php?action=topics');
            const data = await res.json();
            if (data.success && Array.isArray(data.topics)) {
                // Populate Filter Topic
                filterTopic.innerHTML = '<option value="all">Tất cả chủ đề</option>';
                topicDataList.innerHTML = '';

                data.topics.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t;
                    opt.textContent = t;
                    filterTopic.appendChild(opt);

                    const dlOpt = document.createElement('option');
                    dlOpt.value = t;
                    topicDataList.appendChild(dlOpt);
                });
            }
        } catch (e) {
            console.warn('Lỗi khi tải danh sách chủ đề:', e);
        }
    }

    // 2. Tải danh sách từ vựng theo trang và bộ lọc
    async function loadVocabulary(page = 1) {
        currentPage = page;
        const search = filterSearch.value.trim();
        const topic = filterTopic.value;
        const wordType = filterWordType.value;

        vocabTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Đang tải danh sách từ vựng...
                </td>
            </tr>
        `;

        try {
            const params = new URLSearchParams({
                page: page,
                limit: 15,
                search: search,
                topic: topic,
                word_type: wordType
            });

            const res = await fetch(`api/vocabulary.php?${params.toString()}`);
            const data = await res.json();

            if (!data.success) {
                vocabTableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i> ${escapeHtml(data.message || 'Lỗi khi tải dữ liệu')}
                        </td>
                    </tr>
                `;
                paginationInfo.textContent = '0 từ vựng';
                paginationList.innerHTML = '';
                return;
            }

            renderTableRows(data.data);
            renderPagination(data.pagination);

        } catch (e) {
            console.error('Lỗi kết nối API vocabulary:', e);
            vocabTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger">
                        <i class="bi bi-x-circle me-1"></i> Không thể kết nối đến máy chủ. Vui lòng kiểm tra MySQL và cấu hình kết nối.
                    </td>
                </tr>
            `;
        }
    }

    // 3. Render bảng danh sách từ vựng
    function renderTableRows(records) {
        if (!records || records.length === 0) {
            vocabTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div class="display-6 mb-2">🔍</div>
                        <p class="mb-2 fw-medium text-dark">Không tìm thấy từ vựng nào phù hợp.</p>
                        <small>Hãy thử thay đổi từ khóa tìm kiếm hoặc nhấn nút "Thêm từ vựng mới".</small>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        records.forEach((row, index) => {
            const stt = (currentPage - 1) * 15 + index + 1;
            const answers = row.all_answers ? row.all_answers.split(', ') : [row.korean];
            const altAnswers = answers.filter(a => a !== row.korean);

            html += `
                <tr>
                    <td class="text-center text-muted small">${stt}</td>
                    <td>
                        <span class="font-korean fw-bold fs-6 text-dark">${escapeHtml(row.korean)}</span>
                    </td>
                    <td>
                        <span class="fw-medium text-primary">${escapeHtml(row.vietnamese)}</span>
                    </td>
                    <td>
                        <span class="text-muted small font-korean">${row.pronunciation ? escapeHtml(row.pronunciation) : '<span class="text-black-50">&mdash;</span>'}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">${escapeHtml(row.word_type || 'Danh từ')}</span>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">${escapeHtml(row.topic || 'Cơ bản')}</span>
                    </td>
                    <td>
                        ${altAnswers.length > 0 
                            ? `<span class="badge bg-light text-dark border font-korean" title="${escapeHtml(altAnswers.join(', '))}">${escapeHtml(altAnswers.join(', '))}</span>` 
                            : '<span class="text-black-50 small">&mdash;</span>'}
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-info btn-view-detail" data-id="${row.id}" title="Xem chi tiết ví dụ">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-edit-vocab" data-id="${row.id}" title="Sửa từ vựng">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete-vocab" data-id="${row.id}" data-korean="${escapeHtml(row.korean)}" title="Xóa từ vựng">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        vocabTableBody.innerHTML = html;
        attachRowEventListeners();
    }

    // 4. Gắn sự kiện cho các nút trên từng dòng
    function attachRowEventListeners() {
        // Nút xem chi tiết
        document.querySelectorAll('.btn-view-detail').forEach(btn => {
            btn.addEventListener('click', () => showDetail(btn.dataset.id));
        });

        // Nút sửa
        document.querySelectorAll('.btn-edit-vocab').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(btn.dataset.id));
        });

        // Nút xóa
        document.querySelectorAll('.btn-delete-vocab').forEach(btn => {
            btn.addEventListener('click', () => {
                deletingVocabId = btn.dataset.id;
                deleteWordLabel.textContent = `"${btn.dataset.korean}"`;
                deleteModal.show();
            });
        });
    }

    // 5. Render thanh phân trang
    function renderPagination(pagination) {
        const { current_page, total_pages, total_records } = pagination;
        paginationInfo.textContent = `Tổng cộng ${total_records} từ vựng (Trang ${current_page} / ${total_pages || 1})`;

        if (total_pages <= 1) {
            paginationList.innerHTML = '';
            return;
        }

        let html = '';

        // Nút Trước
        html += `
            <li class="page-item ${current_page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${current_page - 1}" aria-label="Previous">
                    &laquo;
                </a>
            </li>
        `;

        // Các nút số trang
        for (let i = 1; i <= total_pages; i++) {
            if (i === 1 || i === total_pages || (i >= current_page - 2 && i <= current_page + 2)) {
                html += `
                    <li class="page-item ${i === current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === current_page - 3 || i === current_page + 3) {
                html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
            }
        }

        // Nút Tiếp
        html += `
            <li class="page-item ${current_page >= total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${current_page + 1}" aria-label="Next">
                    &raquo;
                </a>
            </li>
        `;

        paginationList.innerHTML = html;

        // Gắn sự kiện click phân trang
        paginationList.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page && page !== currentPage) {
                    loadVocabulary(page);
                }
            });
        });
    }

    // 6. Mở Modal Thêm mới
    btnOpenAddModal.addEventListener('click', () => {
        vocabForm.reset();
        vocabId.value = '';
        vocabModalLabel.innerHTML = '<i class="bi bi-plus-circle text-primary"></i> <span>Thêm từ vựng mới</span>';
        btnSaveVocab.innerHTML = '<i class="bi bi-check-lg me-1"></i> Lưu từ vựng';
        vocabModal.show();
        setTimeout(() => inputKorean.focus(), 300);
    });

    // 7. Mở Modal Chỉnh sửa
    async function openEditModal(id) {
        try {
            const res = await fetch(`api/vocabulary.php?id=${id}`);
            const data = await res.json();

            if (!data.success || !data.vocabulary) {
                showToast(data.message || 'Không tìm thấy thông tin từ vựng', 'error');
                return;
            }

            const v = data.vocabulary;
            vocabId.value = v.id;
            inputKorean.value = v.korean;
            inputVietnamese.value = v.vietnamese;
            inputPronunciation.value = v.pronunciation || '';
            inputWordType.value = v.word_type || 'Danh từ';
            inputTopic.value = v.topic || '';
            inputExample.value = v.example || '';
            inputExampleTranslation.value = v.example_translation || '';

            // Lấy các đáp án thay thế
            if (Array.isArray(v.answers)) {
                const alternates = v.answers
                    .filter(a => a.answer !== v.korean)
                    .map(a => a.answer);
                inputAlternateAnswers.value = alternates.join(', ');
            } else {
                inputAlternateAnswers.value = '';
            }

            vocabModalLabel.innerHTML = '<i class="bi bi-pencil-square text-primary"></i> <span>Chỉnh sửa từ vựng</span>';
            btnSaveVocab.innerHTML = '<i class="bi bi-check-lg me-1"></i> Cập nhật';
            vocabModal.show();

        } catch (e) {
            console.error('Lỗi khi nạp thông tin từ vựng:', e);
            showToast('Không thể kết nối đến máy chủ', 'error');
        }
    }

    // 8. Xem chi tiết ví dụ & phát âm
    async function showDetail(id) {
        try {
            const res = await fetch(`api/vocabulary.php?id=${id}`);
            const data = await res.json();

            if (!data.success || !data.vocabulary) {
                showToast('Không tìm thấy thông tin chi tiết', 'error');
                return;
            }

            const v = data.vocabulary;
            detailKorean.textContent = v.korean;
            detailWordType.textContent = v.word_type || 'Danh từ';
            detailVietnamese.textContent = v.vietnamese;

            if (v.pronunciation) {
                detailPron.textContent = `[${v.pronunciation}]`;
                detailPronBox.classList.remove('d-none');
            } else {
                detailPronBox.classList.add('d-none');
            }

            // Đáp án thay thế
            if (Array.isArray(v.answers) && v.answers.length > 1) {
                const alts = v.answers.filter(a => a.answer !== v.korean).map(a => a.answer);
                if (alts.length > 0) {
                    detailAltAns.textContent = alts.join(', ');
                    detailAltAnsBox.classList.remove('d-none');
                } else {
                    detailAltAnsBox.classList.add('d-none');
                }
            } else {
                detailAltAnsBox.classList.add('d-none');
            }

            // Ví dụ
            if (v.example) {
                detailExample.textContent = v.example;
                detailExampleTrans.textContent = v.example_translation || '';
                detailExampleBox.classList.remove('d-none');
            } else {
                detailExampleBox.classList.add('d-none');
            }

            detailModal.show();

        } catch (e) {
            console.error('Lỗi khi xem chi tiết:', e);
        }
    }

    // 9. Lưu từ vựng (Thêm mới hoặc Cập nhật)
    vocabForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const korean = inputKorean.value.trim();
        const vietnamese = inputVietnamese.value.trim();

        if (!korean || !vietnamese) {
            showToast('Vui lòng điền đầy đủ từ tiếng Hàn và nghĩa tiếng Việt!', 'warning');
            return;
        }

        const id = vocabId.value;
        const isEdit = id !== '';

        const payload = {
            id: id,
            korean: korean,
            vietnamese: vietnamese,
            pronunciation: inputPronunciation.value.trim(),
            word_type: inputWordType.value,
            topic: inputTopic.value.trim() || 'Cơ bản',
            alternate_answers: inputAlternateAnswers.value.trim(),
            example: inputExample.value.trim(),
            example_translation: inputExampleTranslation.value.trim()
        };

        btnSaveVocab.disabled = true;
        btnSaveVocab.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang lưu...';

        try {
            const url = 'api/vocabulary.php';
            const method = isEdit ? 'PUT' : 'POST';

            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (data.success) {
                vocabModal.hide();
                showToast(data.message || (isEdit ? 'Cập nhật từ vựng thành công!' : 'Thêm từ vựng thành công!'), 'success');
                loadTopics(); // Cập nhật lại danh sách chủ đề nếu có chủ đề mới
                loadVocabulary(isEdit ? currentPage : 1);
            } else {
                showToast(data.message || 'Không thể lưu từ vựng', 'error');
            }

        } catch (err) {
            console.error('Lỗi khi lưu từ vựng:', err);
            showToast('Lỗi kết nối máy chủ', 'error');
        } finally {
            btnSaveVocab.disabled = false;
            btnSaveVocab.innerHTML = isEdit ? '<i class="bi bi-check-lg me-1"></i> Cập nhật' : '<i class="bi bi-check-lg me-1"></i> Lưu từ vựng';
        }
    });

    // 10. Xóa từ vựng
    btnConfirmDelete.addEventListener('click', async function () {
        if (!deletingVocabId) return;

        btnConfirmDelete.disabled = true;
        btnConfirmDelete.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang xóa...';

        try {
            const res = await fetch(`api/vocabulary.php?id=${deletingVocabId}`, {
                method: 'DELETE'
            });

            const data = await res.json();

            if (data.success) {
                deleteModal.hide();
                showToast(data.message || 'Đã xóa từ vựng thành công!', 'success');
                loadTopics();
                loadVocabulary(currentPage);
            } else {
                showToast(data.message || 'Không thể xóa từ vựng', 'error');
            }
        } catch (e) {
            console.error('Lỗi khi xóa từ vựng:', e);
            showToast('Lỗi kết nối máy chủ', 'error');
        } finally {
            btnConfirmDelete.disabled = false;
            btnConfirmDelete.innerHTML = 'Đồng ý xóa';
            deletingVocabId = null;
        }
    });

    // 11. Xử lý tìm kiếm và bộ lọc
    filterSearch.addEventListener('input', function () {
        clearTimeout(searchDebounceTimeout);
        searchDebounceTimeout = setTimeout(() => {
            loadVocabulary(1);
        }, 350);
    });

    btnClearSearch.addEventListener('click', function () {
        filterSearch.value = '';
        loadVocabulary(1);
    });

    filterTopic.addEventListener('change', () => loadVocabulary(1));
    filterWordType.addEventListener('change', () => loadVocabulary(1));

    btnResetFilters.addEventListener('click', function () {
        filterSearch.value = '';
        filterTopic.value = 'all';
        filterWordType.value = 'all';
        loadVocabulary(1);
    });

    // Khởi tạo ban đầu
    loadTopics();
    loadVocabulary(1);
});
