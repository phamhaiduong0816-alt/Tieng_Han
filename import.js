/**
 * JavaScript Xử lý Nhập File CSV / XLSX
 * Korean Vocab Master - Giai đoạn 1
 */

document.addEventListener('DOMContentLoaded', function () {
    // DOM Elements - Steps
    const stepUpload = document.getElementById('stepUpload');
    const stepPreview = document.getElementById('stepPreview');
    const stepResult = document.getElementById('stepResult');

    // DOM Elements - Upload
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const btnBrowseFile = document.getElementById('btnBrowseFile');

    // DOM Elements - Preview
    const previewFileName = document.getElementById('previewFileName');
    const previewTotalRowsBadge = document.getElementById('previewTotalRowsBadge');
    const previewTableHead = document.getElementById('previewTableHead');
    const previewTableBody = document.getElementById('previewTableBody');
    const btnCancelPreview = document.getElementById('btnCancelPreview');
    const btnCancelImport = document.getElementById('btnCancelImport');
    const btnConfirmImport = document.getElementById('btnConfirmImport');

    // Column Mapping Selects
    const mapKorean = document.getElementById('mapKorean');
    const mapVietnamese = document.getElementById('mapVietnamese');
    const mapPronunciation = document.getElementById('mapPronunciation');
    const mapWordType = document.getElementById('mapWordType');
    const mapExample = document.getElementById('mapExample');
    const mapExampleTranslation = document.getElementById('mapExampleTranslation');
    const mapTopic = document.getElementById('mapTopic');
    const duplicateMode = document.getElementById('duplicateMode');
    const defaultTopicInput = document.getElementById('defaultTopicInput');

    // DOM Elements - Results
    const statImported = document.getElementById('statImported');
    const statDuplicate = document.getElementById('statDuplicate');
    const statErrors = document.getElementById('statErrors');
    const statTotal = document.getElementById('statTotal');
    const errorReportBox = document.getElementById('errorReportBox');
    const errorList = document.getElementById('errorList');
    const btnImportAnother = document.getElementById('btnImportAnother');

    // State Variables
    let currentTempToken = '';
    let parsedHeaders = [];
    let isUploading = false;

    // 1. Kích hoạt chọn file
    btnBrowseFile.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('click', (e) => {
        if (e.target !== btnBrowseFile && !btnBrowseFile.contains(e.target)) {
            fileInput.click();
        }
    });

    // 2. Kéo thả file (Drag & Drop)
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            handleFileUpload(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
            handleFileUpload(e.target.files[0]);
        }
    });

    // 3. Tải file lên và đọc xem trước (Preview)
    async function handleFileUpload(file) {
        if (isUploading) return;

        const ext = file.name.split('.').pop().toLowerCase();
        if (!['csv', 'xlsx'].includes(ext)) {
            showToast('Chỉ chấp nhận file định dạng .csv hoặc .xlsx', 'error');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            showToast('Dung lượng file vượt quá giới hạn 10MB', 'error');
            return;
        }

        isUploading = true;
        const originalContent = dropZone.innerHTML;
        dropZone.innerHTML = `
            <div class="py-4 text-center">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <h5 class="fw-bold text-dark">Đang đọc và phân tích file...</h5>
                <p class="text-muted small">Vui lòng đợi trong giây lát</p>
            </div>
        `;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const res = await fetch('api/import_preview.php', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (!data.success) {
                dropZone.innerHTML = originalContent;
                showToast(data.message || 'Lỗi khi đọc file', 'error');
                return;
            }

            // Lưu dữ liệu xem trước
            currentTempToken = data.temp_token;
            parsedHeaders = data.headers;

            // Chuyển sang bước 2: Xem trước & Ánh xạ cột
            setupPreviewStep(data);

        } catch (err) {
            console.error('Lỗi upload file:', err);
            dropZone.innerHTML = originalContent;
            showToast('Không thể kết nối đến máy chủ để đọc file', 'error');
        } finally {
            isUploading = false;
            fileInput.value = '';
        }
    }

    // 4. Thiết lập giao diện Bước 2 (Xem trước & Ánh xạ)
    function setupPreviewStep(data) {
        previewFileName.textContent = data.filename;
        previewTotalRowsBadge.textContent = `${data.total_rows} dòng dữ liệu`;

        // Đổ danh sách tiêu đề vào các select ánh xạ cột
        const selects = [
            mapKorean, mapVietnamese, mapPronunciation, 
            mapWordType, mapExample, mapExampleTranslation, mapTopic
        ];

        selects.forEach(sel => {
            sel.innerHTML = '<option value="">-- Bỏ qua cột này --</option>';
            data.headers.forEach((header, idx) => {
                const opt = document.createElement('option');
                opt.value = idx;
                opt.textContent = `[Cột ${idx + 1}] ${header}`;
                sel.appendChild(opt);
            });
        });

        // Áp dụng gợi ý ánh xạ tự động
        const map = data.suggested_mapping || {};
        if (map.korean !== null && map.korean !== undefined) mapKorean.value = map.korean;
        if (map.vietnamese !== null && map.vietnamese !== undefined) mapVietnamese.value = map.vietnamese;
        if (map.pronunciation !== null && map.pronunciation !== undefined) mapPronunciation.value = map.pronunciation;
        if (map.word_type !== null && map.word_type !== undefined) mapWordType.value = map.word_type;
        if (map.example !== null && map.example !== undefined) mapExample.value = map.example;
        if (map.example_translation !== null && map.example_translation !== undefined) mapExampleTranslation.value = map.example_translation;
        if (map.topic !== null && map.topic !== undefined) mapTopic.value = map.topic;

        // Render bảng xem trước
        let theadHtml = '<tr><th style="width: 50px;">#</th>';
        data.headers.forEach((h, idx) => {
            theadHtml += `<th><span class="badge bg-secondary-subtle text-secondary me-1">Cột ${idx + 1}</span> ${escapeHtml(h)}</th>`;
        });
        theadHtml += '</tr>';
        previewTableHead.innerHTML = theadHtml;

        let tbodyHtml = '';
        data.sample_rows.forEach((row, rowIdx) => {
            tbodyHtml += `<tr><td class="text-muted small">${rowIdx + 1}</td>`;
            data.headers.forEach((_, colIdx) => {
                const val = row[colIdx] !== undefined ? row[colIdx] : '';
                tbodyHtml += `<td class="font-korean">${escapeHtml(val)}</td>`;
            });
            tbodyHtml += '</tr>';
        });
        previewTableBody.innerHTML = tbodyHtml;

        // Chuyển view
        stepUpload.classList.add('d-none');
        stepResult.classList.add('d-none');
        stepPreview.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // 5. Xác nhận Nhập dữ liệu (Confirm Import)
    btnConfirmImport.addEventListener('click', async function () {
        const koreanCol = mapKorean.value;
        const vietnameseCol = mapVietnamese.value;

        if (koreanCol === '' || vietnameseCol === '') {
            showToast('Vui lòng chọn cột Tiếng Hàn và cột Tiếng Việt!', 'warning');
            return;
        }

        if (koreanCol === vietnameseCol) {
            showToast('Cột Tiếng Hàn và Tiếng Việt không thể trùng nhau!', 'warning');
            return;
        }

        btnConfirmImport.disabled = true;
        btnConfirmImport.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang nhập dữ liệu...';

        const payload = {
            temp_token: currentTempToken,
            duplicate_mode: duplicateMode.value,
            default_topic: defaultTopicInput.value.trim() || 'Cơ bản',
            mapping: {
                korean: koreanCol,
                vietnamese: vietnameseCol,
                pronunciation: mapPronunciation.value,
                word_type: mapWordType.value,
                example: mapExample.value,
                example_translation: mapExampleTranslation.value,
                topic: mapTopic.value
            }
        };

        try {
            const res = await fetch('api/import_confirm.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!data.success) {
                showToast(data.message || 'Lỗi khi nhập dữ liệu', 'error');
                btnConfirmImport.disabled = false;
                btnConfirmImport.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Xác nhận nhập dữ liệu';
                return;
            }

            // Hiển thị kết quả (Bước 3)
            showResultStep(data);

        } catch (err) {
            console.error('Lỗi khi xác nhận nhập:', err);
            showToast('Không thể kết nối đến máy chủ khi lưu dữ liệu', 'error');
            btnConfirmImport.disabled = false;
            btnConfirmImport.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Xác nhận nhập dữ liệu';
        }
    });

    // 6. Hiển thị Bước 3: Kết quả
    function showResultStep(data) {
        statImported.textContent = data.imported_count || 0;
        statDuplicate.textContent = data.duplicate_count || 0;
        statErrors.textContent = data.error_count || 0;
        statTotal.textContent = data.total_processed || 0;

        // Báo cáo chi tiết từng dòng
        if (Array.isArray(data.errors) && data.errors.length > 0) {
            errorList.innerHTML = '';
            data.errors.forEach(err => {
                const li = document.createElement('li');
                li.className = 'mb-1';
                li.textContent = err;
                errorList.appendChild(li);
            });
            errorReportBox.classList.remove('d-none');
        } else {
            errorReportBox.classList.add('d-none');
        }

        showToast(data.message || 'Nhập từ vựng thành công!', 'success');

        stepPreview.classList.add('d-none');
        stepResult.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // 7. Hủy bỏ và trở lại
    function resetToUpload() {
        stepPreview.classList.add('d-none');
        stepResult.classList.add('d-none');
        stepUpload.classList.remove('d-none');
        currentTempToken = '';
        btnConfirmImport.disabled = false;
        btnConfirmImport.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Xác nhận nhập dữ liệu';
        location.reload();
    }

    btnCancelPreview.addEventListener('click', resetToUpload);
    btnCancelImport.addEventListener('click', resetToUpload);
    btnImportAnother.addEventListener('click', resetToUpload);
});
