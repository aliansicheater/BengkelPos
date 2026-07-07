<?php
$pageTitle = 'Penjualan / Kasir';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/auth.php';
requireRole(['owner','admin','kasir']);
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-shopping-cart mr-2" style="color:#22c55e"></i>Penjualan / Kasir</h1>
        <small style="color:#64748b">Transaksi penjualan barang</small>
    </div>
    <div class="mt-2 mt-md-0">
        <button class="btn btn-success btn-sm" onclick="openScannerModal()">
            <i class="fas fa-camera mr-1"></i> Scan Barcode
        </button>
        <button class="btn btn-secondary btn-sm ml-1" onclick="loadRiwayat()">
            <i class="fas fa-history mr-1"></i> Riwayat
        </button>
    </div>
</div>

<div class="row">
    <!-- LEFT: Cart -->
    <div class="col-lg-8 mb-4">
        <!-- Search Input -->
        <div class="card mb-3 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
            <div class="card-body p-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem;color:#64748b"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" class="form-control" id="search-barang" placeholder="Cari kode/nama barang atau scan barcode..." autocomplete="off" style="border-radius:0 0.75rem 0.75rem 0;font-size:1rem;padding:0.75rem">
                </div>
                <!-- Search Results Dropdown -->
                <div id="search-results" class="mt-2" style="display:none">
                    <div style="background:#1e293b;border:1px solid rgba(255,255,255,0.1);border-radius:0.75rem;max-height:300px;overflow-y:auto"></div>
                </div>
            </div>
        </div>

        <!-- Cart Table -->
        <div class="card animate-fade-in-up" style="animation-delay:0.2s;opacity:0">
            <div class="card-header" style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.06)">
                <h6 class="font-bold mb-0" style="color:#f1f5f9"><i class="fas fa-shopping-basket mr-1" style="color:#22c55e"></i> Keranjang Belanja</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabel-keranjang">
                        <thead>
                            <tr>
                                <th style="width:40px">No</th>
                                <th>Barang</th>
                                <th style="width:100px" class="text-center">Harga</th>
                                <th style="width:130px" class="text-center">Qty</th>
                                <th style="width:110px" class="text-right">Subtotal</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="keranjang-body">
                            <tr id="keranjang-empty">
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-shopping-basket mb-3" style="font-size:3rem;color:#334155"></i>
                                    <p class="mb-0" style="color:#64748b">Keranjang masih kosong</p>
                                    <small style="color:#475569">Cari barang atau scan barcode untuk menambah item</small>
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
        <!-- Pelanggan -->
        <div class="card mb-3 animate-fade-in-up" style="animation-delay:0.3s;opacity:0">
            <div class="card-header" style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.06)">
                <h6 class="font-bold mb-0" style="color:#f1f5f9"><i class="fas fa-user mr-1" style="color:#0ea5e9"></i> Pelanggan (Opsional)</h6>
            </div>
            <div class="card-body">
                <select class="form-control" id="pelanggan-select" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                    <option value="">-- Umum --</option>
                </select>
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
                        <input type="number" id="diskon-input" value="0" min="0" class="form-control form-control-sm text-right" style="width:120px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                        <select id="diskon-type" class="form-control form-control-sm" style="width:60px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                            <option value="rp">Rp</option>
                            <option value="%">%</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span style="color:#94a3b8">Pajak</span>
                    <div>
                        <input type="number" id="pajak-input" value="0" min="0" class="form-control form-control-sm text-right" style="width:120px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                        <select id="pajak-type" class="form-control form-control-sm" style="width:60px;display:inline-block;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" onchange="recalcSummary()">
                            <option value="rp">Rp</option>
                            <option value="%">%</option>
                        </select>
                    </div>
                </div>
                <div class="line-double my-3" style="border-top:2px solid #0ea5e9"></div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="font-bold" style="color:#f1f5f9;font-size:1.1rem">GRAND TOTAL</span>
                    <span class="font-bold" id="summary-grandtotal" style="color:#22c55e;font-size:1.1rem">Rp 0</span>
                </div>
            </div>
        </div>

        <!-- Pembayaran -->
        <div class="card mb-3 animate-fade-in-up" style="animation-delay:0.5s;opacity:0">
            <div class="card-header" style="background:rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.06)">
                <h6 class="font-bold mb-0" style="color:#f1f5f9"><i class="fas fa-money-bill-wave mr-1" style="color:#22c55e"></i> Pembayaran</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label style="color:#94a3b8;font-size:0.8rem">Metode Bayar</label>
                    <select class="form-control" id="metode-bayar" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                        <option value="tunai">Tunai</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS</option>
                        <option value="kartu">Kartu Debit/Kredit</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label style="color:#94a3b8;font-size:0.8rem">Bayar</label>
                    <input type="number" class="form-control" id="bayar-input" placeholder="Masukkan jumlah bayar" style="font-size:1.2rem;font-weight:bold;background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)" oninput="recalcKembali()">
                </div>
                <div class="d-flex justify-content-between">
                    <span style="color:#94a3b8">Kembali</span>
                    <span class="font-bold" id="summary-kembali" style="color:#f59e0b;font-size:1.2rem">Rp 0</span>
                </div>
                <button class="btn btn-success btn-block mt-3 font-bold" id="btn-bayar" onclick="prosesBayar()" disabled style="font-size:1.1rem;padding:12px">
                    <i class="fas fa-check-circle mr-1"></i> PROSES BAYAR
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
                <h5 class="modal-title"><i class="fas fa-camera mr-1" style="color:#22c55e"></i> Scan Barcode</h5>
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
                <h5 class="modal-title"><i class="fas fa-history mr-1" style="color:#0ea5e9"></i> Riwayat Penjualan</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tabel-riwayat">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Trx</th>
                                <th>Tanggal</th>
                                <th>Grand Total</th>
                                <th>Bayar</th>
                                <th>Kembali</th>
                                <th>Aksi</th>
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
let pelangganList = [];
let grandTotalVal = 0;

// Load pelanggan
async function loadPelanggan() {
    const res = await apiRequest('getPelanggan');
    if (res.status === 'success') {
        pelangganList = res.data;
        const sel = document.getElementById('pelanggan-select');
        pelangganList.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.nama + (p.plat_nomor ? ' (' + p.plat_nomor + ')' : '');
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

// Also handle enter key for barcode scanning
document.getElementById('search-barang').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = this.value.trim();
        if (val) searchBarang(val);
    }
});

async function searchBarang(query) {
    const res = await apiRequest('searchBarang', { search: query }, 'GET');
    // Try as GET parameter
    const res2 = await fetch(`${APP_URL}/api/index.php?action=searchBarang&search=${encodeURIComponent(query)}`);
    const data = await res2.json();
    
    const container = document.getElementById('search-results');
    const inner = container.querySelector('div');
    
    if (data.status === 'success' && data.data.length > 0) {
        inner.innerHTML = data.data.map(b => `
            <div class="search-result-item" onclick="addToCart(${b.id}, '${escapeHtml(b.nama)}', ${b.harga_jual}, ${b.stok}, '${escapeHtml(b.kode_barang)}', '${escapeHtml(b.barcode||'')}', '${escapeHtml(b.satuan)}')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="font-bold" style="color:#f1f5f9;font-size:0.9rem">${escapeHtml(b.nama)}</div>
                        <small style="color:#64748b">${escapeHtml(b.kode_barang)} ${b.barcode ? '| ' + escapeHtml(b.barcode) : ''}</small>
                    </div>
                    <div class="text-right">
                        <div style="color:#22c55e;font-weight:bold">${formatRupiah(b.harga_jual)}</div>
                        <small style="color:${b.stok <= 0 ? '#ef4444' : '#94a3b8'}">Stok: ${b.stok} ${escapeHtml(b.satuan)}</small>
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

// Close search results on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#search-barang') && !e.target.closest('#search-results')) {
        document.getElementById('search-results').style.display = 'none';
    }
});

function addToCart(id, nama, harga, stok, kode, barcode, satuan) {
    // Check if already in cart
    const existing = cartItems.find(i => i.barang_id === id);
    if (existing) {
        if (existing.qty >= stok) {
            showToast('Stok tidak cukup!', 'warning');
            return;
        }
        existing.qty++;
        existing.subtotal = existing.qty * existing.harga;
    } else {
        if (stok <= 0) {
            showToast('Stok habis!', 'error');
            return;
        }
        cartItems.push({
            barang_id: id, nama, harga, stok, kode, barcode, satuan,
            qty: 1, subtotal: harga
        });
    }
    
    document.getElementById('search-barang').value = '';
    document.getElementById('search-results').style.display = 'none';
    renderCart();
    showToast(`${nama} ditambahkan ke keranjang`, 'success');
}

function updateQty(index, delta) {
    const item = cartItems[index];
    const newQty = item.qty + delta;
    if (newQty <= 0) {
        removeFromCart(index);
        return;
    }
    if (newQty > item.stok) {
        showToast('Stok tidak cukup!', 'warning');
        return;
    }
    item.qty = newQty;
    item.subtotal = item.qty * item.harga;
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
                    <i class="fas fa-shopping-basket mb-3" style="font-size:3rem;color:#334155"></i>
                    <p class="mb-0" style="color:#64748b">Keranjang masih kosong</p>
                    <small style="color:#475569">Cari barang atau scan barcode untuk menambah item</small>
                </td>
            </tr>`;
        document.getElementById('btn-bayar').disabled = true;
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
            <td class="text-center" style="color:#94a3b8">${formatRupiah(item.harga)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger" onclick="updateQty(${i}, -1)" style="padding:2px 8px;border-radius:6px">-</button>
                <span class="mx-2 font-bold" style="color:#f1f5f9;min-width:30px;display:inline-block;text-align:center">${item.qty}</span>
                <button class="btn btn-sm btn-outline-success" onclick="updateQty(${i}, 1)" style="padding:2px 8px;border-radius:6px">+</button>
                <small class="d-block" style="color:#64748b">Stok: ${item.stok}</small>
            </td>
            <td class="text-right font-bold" style="color:#22c55e">${formatRupiah(item.subtotal)}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${i})" style="padding:2px 6px;border-radius:6px">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    document.getElementById('btn-bayar').disabled = false;
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
    
    recalcKembali();
}

function recalcKembali() {
    const bayar = parseFloat(document.getElementById('bayar-input').value) || 0;
    const kembali = Math.max(0, bayar - grandTotalVal);
    document.getElementById('summary-kembali').textContent = formatRupiah(kembali);
    
    const btn = document.getElementById('btn-bayar');
    btn.disabled = cartItems.length === 0 || bayar < grandTotalVal;
}

async function prosesBayar() {
    if (cartItems.length === 0) return;
    
    const bayar = parseFloat(document.getElementById('bayar-input').value) || 0;
    if (bayar < grandTotalVal) {
        showToast('Jumlah bayar kurang!', 'warning');
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
        pelanggan_id: document.getElementById('pelanggan-select').value || null,
        subtotal: subtotal,
        diskon: diskon,
        pajak: pajak,
        grand_total: grandTotalVal,
        bayar: bayar,
        metode_bayar: document.getElementById('metode-bayar').value,
        items: cartItems.map(i => ({
            barang_id: i.barang_id,
            qty: i.qty,
            harga: i.harga,
            diskon: 0,
            subtotal: i.subtotal
        }))
    };
    
    document.getElementById('btn-bayar').disabled = true;
    document.getElementById('btn-bayar').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
    
    const res = await apiRequest('addPenjualan', data);
    
    if (res.status === 'success') {
        showToast('Penjualan berhasil!', 'success');
        
        // Open struk in new window
        const strukUrl = `${APP_URL}/cetak/struk.php?id=${res.data.id}&type=penjualan`;
        window.open(strukUrl, '_blank', 'width=400,height=600');
        
        // Reset
        cartItems = [];
        document.getElementById('bayar-input').value = '';
        document.getElementById('diskon-input').value = '0';
        document.getElementById('pajak-input').value = '0';
        document.getElementById('pelanggan-select').value = '';
        renderCart();
    } else {
        showToast(res.message || 'Gagal memproses', 'error');
        document.getElementById('btn-bayar').disabled = false;
        document.getElementById('btn-bayar').innerHTML = '<i class="fas fa-check-circle mr-1"></i> PROSES BAYAR';
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

$('#scanner-modal').on('hidden.bs.modal', function() {
    stopScanner();
});

// Riwayat
async function loadRiwayat() {
    const res = await apiRequest('getPenjualan', {}, 'GET');
    // Use direct fetch for GET
    const fetchRes = await fetch(`${APP_URL}/api/index.php?action=getPenjualan&page=1&limit=50`);
    const data = await fetchRes.json();
    
    const tbody = document.getElementById('riwayat-body');
    if (data.status === 'success' && data.data.length > 0) {
        tbody.innerHTML = data.data.map((p, i) => `
            <tr>
                <td>${i + 1}</td>
                <td class="font-bold">${p.no_transaksi}</td>
                <td>${formatDate(p.tanggal)}</td>
                <td>${formatRupiah(p.grand_total)}</td>
                <td>${formatRupiah(p.bayar)}</td>
                <td>${formatRupiah(p.kembali)}</td>
                <td>
                    <button class="btn btn-sm btn-outline-success" onclick="window.open('${APP_URL}/cetak/struk.php?id=${p.id}&type=penjualan','_blank','width=400,height=600')" title="Cetak Struk">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center" style="color:#64748b">Belum ada riwayat</td></tr>';
    }
    $('#riwayat-modal').modal('show');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// Init
loadPelanggan();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
