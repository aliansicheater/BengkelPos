// ============================================================
// assets/js/app.js - BengkelPOS Main JavaScript
// ============================================================

// Utility: format angka ke rupiah
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(angka);
}

// Utility: format angka saja (tanpa Rp)
function formatNumber(angka) {
    return parseInt(angka).toLocaleString('id-ID');
}

// Utility: parse input rupiah ke angka
function parseRupiah(value) {
    return parseInt(value.replace(/[^0-9]/g, '')) || 0;
}

// Utility: format input dengan separator ribu
function formatInputRupiah(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        input.value = parseInt(value).toLocaleString('id-ID');
    }
}

// Auto format rupiah on input
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.input-rupiah').forEach(function(el) {
        el.addEventListener('keyup', function(e) {
            formatInputRupiah(this);
        });
    });
});

// Toast Notification
function showToast(message, type = 'success') {
    const colors = {
        success: { bg: '#ECFDF5', text: '#065F46', border: '#10B981', icon: 'fa-check-circle' },
        error: { bg: '#FEF2F2', text: '#991B1B', border: '#EF4444', icon: 'fa-exclamation-circle' },
        warning: { bg: '#FFFBEB', text: '#92400E', border: '#F59E0B', icon: 'fa-exclamation-triangle' },
        info: { bg: '#EFF6FF', text: '#1E40AF', border: '#3B82F6', icon: 'fa-info-circle' }
    };
    const c = colors[type] || colors.success;

    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        background: ${c.bg}; color: ${c.text}; border-left: 4px solid ${c.border};
        padding: 12px 20px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        display: flex; align-items: center; gap: 10px;
        font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 500;
        animation: slideDown 0.3s ease; max-width: 400px;
    `;
    toast.innerHTML = `<i class="fas ${c.icon}"></i> ${message}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Live search for tables
function initLiveSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('keyup', debounce(function() {
        const keyword = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(keyword) ? '' : 'none';
        });
    }, 300));
}
