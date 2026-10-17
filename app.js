/**
 * Korean Vocab Master - Global JavaScript Utilities
 * Giai đoạn 1
 */

// Toast notification helper
function showToast(message, type = 'info') {
    const toastEl = document.getElementById('liveToast');
    if (!toastEl) return;

    const toastBody = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');

    // Reset classes
    toastEl.className = 'toast align-items-center border-0 shadow-lg';
    toastIcon.className = 'bi fs-5';

    switch (type) {
        case 'success':
            toastEl.classList.add('text-bg-success');
            toastIcon.className = 'bi bi-check-circle-fill fs-5';
            break;
        case 'danger':
        case 'error':
            toastEl.classList.add('text-bg-danger');
            toastIcon.className = 'bi bi-x-circle-fill fs-5';
            break;
        case 'warning':
            toastEl.classList.add('text-bg-warning', 'text-dark');
            toastIcon.className = 'bi bi-exclamation-triangle-fill fs-5';
            break;
        default:
            toastEl.classList.add('text-bg-primary');
            toastIcon.className = 'bi bi-info-circle-fill fs-5';
            break;
    }

    if (toastBody) {
        toastBody.textContent = message;
    }

    const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 });
    toast.show();
}

// Escape HTML helper
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Khởi tạo tooltips
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
