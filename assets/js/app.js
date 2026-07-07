/**
 * Bengkel Pro V1 — Core JavaScript
 * AdminLTE 3 compatible
 */
const APP_URL = '/Aplikasi Bengkel Pro V1';

// ============ Toast Notifications ============
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-times-circle',
        warning: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle',
    };
    const colors = {
        success: 'background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);color:#22c55e',
        error: 'background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#ef4444',
        warning: 'background:rgba(245,158,11,0.15);border:1px solid rgba(245,158,11,0.3);color:#f59e0b',
        info: 'background:rgba(14,165,233,0.15);border:1px solid rgba(14,165,233,0.3);color:#0ea5e9',
    };

    const toast = document.createElement('div');
    toast.className = 'toast rounded-xl px-4 py-3 d-flex align-items-center shadow-lg';
    toast.style.cssText = `${colors[type]};pointer-events:auto;backdrop-filter:blur(12px);margin-bottom:8px`;
    toast.innerHTML = `<i class="${icons[type] || icons.info} mr-2"></i><span class="font-medium" style="font-size:0.875rem">${message}</span>`;
    
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ============ API Request ============
async function apiRequest(action, data = {}, method = 'POST') {
    try {
        const options = { method, headers: { 'Content-Type': 'application/json' } };
        if (method === 'POST') options.body = JSON.stringify(data);
        const url = `${APP_URL}/api/index.php?action=${action}`;
        const res = await fetch(url, options);
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        showToast('Terjadi kesalahan koneksi', 'error');
        return { status: 'error', message: err.message };
    }
}

// ============ Formatters ============
function formatRupiah(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const dt = new Date(dateStr);
    return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ============ Animations ============
function animateNumber(element, target, duration = 1000) {
    if (!element) return;
    const startTime = performance.now();
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        element.textContent = formatRupiah(Math.floor(target * eased));
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
}

// ============ Modals (AdminLTE compatible) ============
function showModal(title, content) {
    const id = 'modal-' + Date.now();
    const html = `
    <div class="modal fade" id="${id}" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
                </div>
                <div class="modal-body">${content}</div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    $(`#${id}`).modal('show');
    $(`#${id}`).on('hidden.bs.modal', function() { $(this).remove(); });
    return id;
}

function closeModal(id) {
    $(`#${id}`).modal('hide');
}

// ============ Confirm Delete ============
function confirmDelete(message = 'Yakin ingin menghapus data ini?') {
    return new Promise(resolve => {
        const id = showModal('Konfirmasi Hapus', `
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size:3rem"></i>
                <p class="mb-4">${message}</p>
                <button class="btn btn-secondary mr-2" data-dismiss="modal" onclick="$(this).closest('.modal').modal('hide');setTimeout(()=>resolve(false),200)">Batal</button>
                <button class="btn btn-danger" onclick="$(this).closest('.modal').modal('hide');setTimeout(()=>resolve(true),200)">Hapus</button>
            </div>
        `);
        // Resolve false on close
        $(`#${id}`).on('hidden.bs.modal', function() { resolve(false); });
    });
}

// ============ Loading ============
function showLoading(container) {
    container.innerHTML = Array(5).fill('').map(() => `
        <div class="d-flex align-items-center p-3 mb-2" style="background:#1e293b;border-radius:0.75rem">
            <div class="skeleton" style="width:40px;height:40px;border-radius:0.5rem"></div>
            <div class="ml-3 flex-grow-1">
                <div class="skeleton mb-1" style="height:12px;width:30%"></div>
                <div class="skeleton" style="height:10px;width:50%"></div>
            </div>
            <div class="skeleton" style="width:60px;height:24px;border-radius:9999px"></div>
        </div>
    `).join('');
}

// ============ Print ============
function printPage() { window.print(); }

// ============ Barcode Generation ============
function generateBarcode(elementId, value, options = {}) {
    const defaults = {
        format: 'CODE128',
        width: 1.5,
        height: 40,
        displayValue: true,
        fontSize: 12,
        margin: 5,
        lineColor: '#0f172a',
        background: 'transparent',
    };
    try {
        JsBarcode(`#${elementId}`, value, { ...defaults, ...options });
    } catch (e) {
        console.warn('Barcode generation failed:', e);
    }
}

// ============ Scanner ============
let html5QrCode = null;
function startScanner(elementId, onSuccess) {
    if (html5QrCode) { html5QrCode.stop().catch(()=>{}); }
    html5QrCode = new Html5Qrcode(elementId);
    html5QrCode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 250, height: 150 } },
        (decodedText) => { onSuccess(decodedText); },
        () => {} // ignore errors
    ).catch(err => {
        console.warn('Camera not available:', err);
        showToast('Kamera tidak tersedia, silakan input manual', 'warning');
    });
}
function stopScanner() {
    if (html5QrCode) { html5QrCode.stop().catch(()=>{}); html5QrCode = null; }
}

// ============ Table Sort (AdminLTE DataTreeTable compatible) ============
function searchTable(input, tableId) {
    const filter = input.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}
