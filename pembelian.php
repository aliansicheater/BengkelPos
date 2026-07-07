<?php
$pageTitle = 'Pembelian / Stok Masuk';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/auth.php';
requireRole(['owner','admin','gudang']);
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-truck mr-2" style="color:#0ea5e9"></i>Pembelian / Stok Masuk</h1>
        <small style="color:#64748b">Pencatatan pembelian barang dari supplier</small>
    </div>
    <div class="mt-2 mt-md-0">
        <button class="btn btn-primary btn-sm" onclick="openScannerModal()">
            <i class="fas fa-camera mr-1"></i> Scan Barcode
        </button>
        <button class="btn btn-secondary btn-sm ml-1" onclick="loadRiwayatPembelian()">
            <i class="fas fa-history mr-1"></i> Riwayat
        </button>
    </div>
</div>

<div class="row">
    <!-- LEFT: Cart -->
    <div class="col-lg-8 mb-4">
        <!-- Supplier + Search -->
        <div class="card mb-3 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-5 mb-2">
                        <label style="color:#94a3b8;font-size:0.8rem">Supplier *</label>
                        <select class="form-control" id="supplier-select" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                            <option value="">-- Pilih Supplier --</option>
                        </select>
                    </div>
                    <div class="col-md-7 mb-2">
                        <label style="color:#94a3b8;font-size:0.8rem">Cari Barang</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem;color:#64748b"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="search-barang" placeholder="Kode/nama atau scan barcode..." autocomplete="off" style="border-radius:0 0.75rem 0.75rem 0">
                        </div>
                        <div id="search-results" class="mt-2" style="display:none">
                            <div style="background:#1e293b;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;max-height:300px;overflow-y:auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Table -->
        <div class="card animate-fade-in-up" style="animation-delay:0.2s;opacity:0">
            <div class="card-header" style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.06)">
                <h6 class="font-bold mb-0" style="color:#f1f5f9"><i class="fas fa-clipboard-list mr-1" style="color:#0ea5e9"></i> Daftar Barang Dibeli</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabel-keranjang">
                        <thead>
                            <tr>
                                <th style="width:40px">No</th>
                                <th>Barang</th>
                                <th style="width:120px" class="text-center">Harga Beli</th>
                                <th style="width:120px" class="text-center">Qty</th>
                                <th style="width:110px" class="text-right">Subtotal</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="keranjang-body">
                            <tr id="keranjang-empty">
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-truck mb-3" style="font-size:3rem;color:#334155"></i>
                                    <p class="mb-0" style="color:#64748b">Belum ada barang</p>
                                    <small style="color:#475569">Cari atau scan barcode barang yang dibeli</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Summary -->
    <div class="col-lg-4">
        <!-- Info -->
        <div class="card mb-3 animate-fade-in-up" style="animation-delay:0.3s;opacity:0">
            <div class="card-header" style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.06)">
                <h6 class="font-bold mb-0" style="color:#f1f5f9"><i class="fas fa-info-circle mr-1" style="color:#0ea5e9"></i> Info Pembelian</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label style="color:#94a3b8;font-size:0.8rem">Keterangan</label>
                    <textarea class="form-control" id="keterangan" rows="2" placeholder="Catatan pembelian..." style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)"></textarea>
                </div>
                <div class="mb-3">
                    <label style="color:#94a3b8;font-size:0.8rem">Metode Bayar</label>
                    <select class="form-control" id="metode-bayar" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                        <option value="tunai">Tunai</option>
                        <option value="kredit">Kredit (Hutang)</option>
                    </select>
                </div>
                <div class="mb-3" id="jatuh-tempo-group" style="display:none">
                    <label style="color:#94a3b8;font-size:0.8rem">Jatuh Tempo</label>
                    <input type="date" class="form-control" id="jatuh-tempo" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="card mb-3 animate-fade-in-up" style="animation-delay:0.4s;opacity:0">
            <div class="card-header" style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.06)">
                <h6 class="font-bold mb-0" style="color:#f1f5f9"><i class="fas fa-calculator mr-1" style="color:#f59e0b"></i> Ringkasan</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span style="color:#94a3b8">Subtotal</span>
                    <span class="font-bold" id="summary-subtotal" style="color:#f1f5f9">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span style="color:#94a3b8">Diskon</span>
                    <div>
                        <input type="number" id="diskon-input" value="0" min="0" class="form-control form-control-sm text-right" style="width:100px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                        <select id="diskon-type" class="form-control form-control-sm" style="width:55px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                            <option value="rp">Rp</option>
                            <option value="%">%</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span style="color:#94a3b8">Pajak</span>
                    <div>
                        <input type="number" id="pajak-input" value="0" min="0" class="form-control form-control-sm text-right" style="width:100px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                        <select id="pajak-type" class="form-control form-control-sm" style="width:55px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                            <option value="rp">Rp</option>
                            <option value="%">%</option>
                        </select>
                    </div>
                </div>
                <div class="line-double my-3" style="border-top:2px solid #0ea5e9"></div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="font-bold" style="color:#f1f5f9;font-size:1.1rem">GRAND TOTAL</span>
                    <span class="font-bold" id="summary-grandtotal" style="color:#0ea5e9;font-size:1.1rem">Rp 0</span>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="card animate-fade-in-up" style="animation-delay:0.5s;opacity:0">
            <div class="card-body">
                <button class="btn btn-primary btn-block font-bold" id="btn-simpan" onclick="simpanPembelian()" disabled style="font-size:1.1rem;padding:12px">
                    <i class="fas fa-save mr-1"></i> SIMPAN PEMBELIAN
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scanner Modal -->
<div class="modal fade" id="scanner-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-camera mr-1" style="color:#0ea5e9"></i> Scan Barcode</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="scanner-reader" style="width:100%;min-height:300px"></div>
                <div class="text-center mt-3">
                    <small style="color:#64748b">Arahkan kamera ke barcode barang</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Modal -->
<div class="modal fade" id="riwayat-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history mr-1" style="color:#0ea5e9"></i> Riwayat Pembelian</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Faktur</th>
                                <th>Supplier</th>
                                <th>Tanggal</th>
                                <th>Grand Total</th>
                                <th>Status Bayar</th>
                            </tr>
                        </thead>
                        <tbody id="riwayat-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.search-result-item {
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: all 0.2s;
}
.search-result-item:hover {
    background: rgba(14,165,233,0.1);
}
.search-result-item:last-child {
    border-bottom: none;
}
.cart-item-enter {
    animation: cartItemSlide 0.3s ease;
}
@keyframes cartItemSlide {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
let cartItems = [];
let grandTotalVal = 0;

// Show/hide jatuh tempo
document.getElementById('metode-bayar').addEventListener('change', function() {
    document.getElementById('jatuh-tempo-group').style.display = this.value === 'kredit' ? 'block' : 'none';
});

// Load supplier
async function loadSupplier() {
    const res = await apiRequest('getSupplier');
    if (res.status === 'success') {
        const sel = document.getElementById('supplier-select');
        res.data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.nama;
            sel.appendChild(opt);
        });
    }
}

// Search barang
let searchTimeout;
document.getElementById('search-barang').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const val = this.value.trim();
    if (val.length < 2) {
        document.getElementById('search-results').style.display = 'none';
        return;
    }
    searchTimeout = setTimeout(() => searchBarang(val), 300);
});

document.getElementById('search-barang').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = this.value.trim();
        if (val) searchBarang(val);
    }
});

async function searchBarang(query) {
    const fetchRes = await fetch(`${APP_URL}/api/index.php?action=searchBarang&search=${encodeURIComponent(query)}`);
    const data = await fetchRes.json();
    
    const container = document.getElementById('search-results');
    const inner = container.querySelector('div');
    
    if (data.status === 'success' && data.data.length > 0) {
        inner.innerHTML = data.data.map(b => `
            <div class="search-result-item" onclick="addToCart(${b.id}, '${escapeHtml(b.nama)}', ${b.harga_modal}, ${b.stok}, '${escapeHtml(b.kode_barang)}', '${escapeHtml(b.barcode||'')}', '${escapeHtml(b.satuan)}')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="font-bold" style="color:#f1f5f9;font-size:0.9rem">${escapeHtml(b.nama)}</div>
                        <small style="color:#64748b">${escapeHtml(b.kode_barang)} ${b.barcode ? '| ' + escapeHtml(b.barcode) : ''}</small>
                    </div>
                    <div class="text-right">
                        <div style="color:#0ea5e9;font-weight:bold">${formatRupiah(b.harga_modal)}</div>
                        <small style="color:#94a3b8">Stok: ${b.stok} ${escapeHtml(b.satuan)}</small>
                    </div>
                </div>
            </div>
        `).join('');
        container.style.display = 'block';
    } else {
        inner.innerHTML = '<div class="p-3 text-center" style="color:#64748b">Barang tidak ditemukan</div>';
        container.style.display = 'block';
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#search-barang') && !e.target.closest('#search-results')) {
        document.getElementById('search-results').style.display = 'none';
    }
});

function addToCart(id, nama, hargaModal, stok, kode, barcode, satuan) {
    const existing = cartItems.find(i => i.barang_id === id);
    if (existing) {
        existing.qty++;
        existing.subtotal = existing.qty * existing.harga;
    } else {
        cartItems.push({
            barang_id: id, nama, harga: hargaModal, stok, kode, barcode, satuan,
            qty: 1, subtotal: hargaModal
        });
    }
    
    document.getElementById('search-barang').value = '';
    document.getElementById('search-results').style.display = 'none';
    renderCart();
    showToast(`${nama} ditambahkan`, 'success');
}

function updateQty(index, delta) {
    const item = cartItems[index];
    const newQty = item.qty + delta;
    if (newQty <= 0) { removeFromCart(index); return; }
    item.qty = newQty;
    item.subtotal = item.qty * item.harga;
    renderCart();
}

function updateHarga(index, val) {
    cartItems[index].harga = parseFloat(val) || 0;
    cartItems[index].subtotal = cartItems[index].qty * cartItems[index].harga;
    renderCart();
}

function removeFromCart(index) {
    cartItems.splice(index, 1);
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('keranjang-body');
    
    if (cartItems.length === 0) {
        tbody.innerHTML = `
            <tr id="keranjang-empty">
                <td colspan="6" class="text-center py-5">
                    <i class="fas fa-truck mb-3" style="font-size:3rem;color:#334155"></i>
                    <p class="mb-0" style="color:#64748b">Belum ada barang</p>
                    <small style="color:#475569">Cari atau scan barcode barang yang dibeli</small>
                </td>
            </tr>`;
        document.getElementById('btn-simpan').disabled = true;
        recalcSummary();
        return;
    }
    
    tbody.innerHTML = cartItems.map((item, i) => `
        <tr class="cart-item-enter" style="animation-delay:${i * 0.05}s">
            <td>${i + 1}</td>
            <td>
                <div class="font-bold" style="color:#f1f5f9;font-size:0.9rem">${escapeHtml(item.nama)}</div>
                <small style="color:#64748b">${escapeHtml(item.kode)}</small>
            </td>
            <td>
                <input type="number" value="${item.harga}" min="0" class="form-control form-control-sm text-right" onchange="updateHarga(${i}, this.value)" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);font-size:0.85rem">
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger" onclick="updateQty(${i}, -1)" style="padding:2px 8px;border-radius:6px">-</button>
                <span class="mx-2 font-bold" style="color:#f1f5f9;min-width:30px;display:inline-block;text-align:center">${item.qty}</span>
                <button class="btn btn-sm btn-outline-primary" onclick="updateQty(${i}, 1)" style="padding:2px 8px;border-radius:6px">+</button>
            </td>
            <td class="text-right font-bold" style="color:#0ea5e9">${formatRupiah(item.subtotal)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${i})" style="padding:2px 6px;border-radius:6px">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('btn-simpan').disabled = false;
    recalcSummary();
}

function recalcSummary() {
    const subtotal = cartItems.reduce((sum, i) => sum + i.subtotal, 0);
    const diskonType = document.getElementById('diskon-type').value;
    const diskonVal = parseFloat(document.getElementById('diskon-input').value) || 0;
    const pajakType = document.getElementById('pajak-type').value;
    const pajakVal = parseFloat(document.getElementById('pajak-input').value) || 0;
    
    const diskon = diskonType === '%' ? subtotal * diskonVal / 100 : diskonVal;
    const afterDiskon = subtotal - diskon;
    const pajak = pajakType === '%' ? afterDiskon * pajakVal / 100 : pajakVal;
    grandTotalVal = afterDiskon + pajak;
    
    document.getElementById('summary-subtotal').textContent = formatRupiah(subtotal);
    document.getElementById('summary-grandtotal').textContent = formatRupiah(grandTotalVal);
}

async function simpanPembelian() {
    if (cartItems.length === 0) return;
    
    const supplierId = document.getElementById('supplier-select').value;
    if (!supplierId) {
        showToast('Pilih supplier terlebih dahulu!', 'warning');
        return;
    }
    
    const subtotal = cartItems.reduce((sum, i) => sum + i.subtotal, 0);
    const diskonType = document.getElementById('diskon-type').value;
    const diskonVal = parseFloat(document.getElementById('diskon-input').value) || 0;
    const pajakType = document.getElementById('pajak-type').value;
    const pajakVal = parseFloat(document.getElementById('pajak-input').value) || 0;
    const diskon = diskonType === '%' ? subtotal * diskonVal / 100 : diskonVal;
    const afterDiskon = subtotal - diskon;
    const pajak = pajakType === '%' ? afterDiskon * pajakVal / 100 : pajakVal;
    
    const data = {
        supplier_id: supplierId,
        subtotal: subtotal,
        diskon: diskon,
        pajak: pajak,
        grand_total: grandTotalVal,
        metode_bayar: document.getElementById('metode-bayar').value,
        jatuh_tempo: document.getElementById('jatuh-tempo').value || null,
        keterangan: document.getElementById('keterangan').value,
        items: cartItems.map(i => ({
            barang_id: i.barang_id,
            qty: i.qty,
            harga: i.harga,
            diskon: 0,
            subtotal: i.subtotal
        }))
    };
    
    document.getElementById('btn-simpan').disabled = true;
    document.getElementById('btn-simpan').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
    
    const res = await apiRequest('addPembelian', data);
    
    if (res.status === 'success') {
        showToast('Pembelian berhasil! Stok telah ditambahkan.', 'success');
        cartItems = [];
        document.getElementById('supplier-select').value = '';
        document.getElementById('keterangan').value = '';
        document.getElementById('diskon-input').value = '0';
        document.getElementById('pajak-input').value = '0';
        document.getElementById('metode-bayar').value = 'tunai';
        document.getElementById('jatuh-tempo-group').style.display = 'none';
        renderCart();
    } else {
        showToast(res.message || 'Gagal menyimpan', 'error');
        document.getElementById('btn-simpan').disabled = false;
        document.getElementById('btn-simpan').innerHTML = '<i class="fas fa-save mr-1"></i> SIMPAN PEMBELIAN';
    }
}

// Scanner
function openScannerModal() {
    $('#scanner-modal').modal('show');
    setTimeout(() => {
        startScanner('scanner-reader', (decodedText) => {
            document.getElementById('search-barang').value = decodedText;
            searchBarang(decodedText);
            stopScanner();
            $('#scanner-modal').modal('hide');
        });
    }, 500);
}
$('#scanner-modal').on('hidden.bs.modal', function() { stopScanner(); });

// Riwayat
async function loadRiwayatPembelian() {
    const fetchRes = await fetch(`${APP_URL}/api/index.php?action=getPembelian&page=1&limit=50`);
    const data = await fetchRes.json();
    const tbody = document.getElementById('riwayat-body');
    if (data.status === 'success' && data.data.length > 0) {
        tbody.innerHTML = data.data.map((p, i) => `
            <tr>
                <td>${i + 1}</td>
                <td class="font-bold">${p.no_faktur}</td>
                <td>${escapeHtml(p.supplier_nama || '-')}</td>
                <td>${formatDate(p.tanggal)}</td>
                <td>${formatRupiah(p.grand_total)}</td>
                <td><span class="badge badge-${p.status_bayar === 'lunas' ? 'success' : 'warning'}">${p.status_bayar === 'lunas' ? 'Lunas' : 'Belum'}</span></td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="color:#64748b">Belum ada riwayat</td></tr>';
    }
    $('#riwayat-modal').modal('show');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// Init
loadSupplier();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
