<?php
$pageTitle = 'Servis / Work Order';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/auth.php';
requireRole(['owner','admin','kasir','mekanik']);
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-wrench mr-2" style="color:#f59e0b"></i>Servis / Work Order</h1>
        <small style="color:#64748b">Manajemen pekerjaan servis motor</small>
    </div>
    <button class="btn btn-warning mt-2 mt-md-0" onclick="openFormWO()">
        <i class="fas fa-plus mr-1"></i> Work Order Baru
    </button>
</div>

<!-- Filter Tabs -->
<div class="card mb-4 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
    <div class="card-body p-2">
        <div class="d-flex flex-wrap gap-2" id="filter-tabs">
            <button class="btn btn-sm font-bold active" data-status="" onclick="filterWO('', this)" style="border-radius:20px;padding:6px 16px;background:rgba(14,165,233,0.2);color:#0ea5e9;border:1px solid rgba(14,165,233,0.3)">Semua</button>
            <button class="btn btn-sm font-bold" data-status="proses" onclick="filterWO('proses', this)" style="border-radius:20px;padding:6px 16px;background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.2)">Proses</button>
            <button class="btn btn-sm font-bold" data-status="selesai" onclick="filterWO('selesai', this)" style="border-radius:20px;padding:6px 16px;background:rgba(34,197,94,0.15);color:#22c55e;border:1px solid rgba(34,197,94,0.2)">Selesai</button>
            <button class="btn btn-sm font-bold" data-status="diambil" onclick="filterWO('diambil', this)" style="border-radius:20px;padding:6px 16px;background:rgba(100,116,139,0.15);color:#94a3b8;border:1px solid rgba(100,116,139,0.2)">Diambil</button>
        </div>
    </div>
</div>

<!-- Work Orders List -->
<div class="row" id="wo-list">
    <div class="col-12 text-center py-5">
        <i class="fas fa-spinner fa-spin mb-3" style="font-size:2rem;color:#334155"></i>
        <p style="color:#64748b">Memuat data...</p>
    </div>
</div>

<!-- Form Work Order Modal -->
<div class="modal fade" id="wo-form-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold"><i class="fas fa-wrench mr-2" style="color:#f59e0b"></i>Work Order Baru</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left: Customer & Bike Info -->
                    <div class="col-lg-6 mb-3">
                        <h6 class="font-bold mb-3" style="color:#0ea5e9">Data Pelanggan & Motor</h6>
                        <div class="form-group">
                            <label style="color:#94a3b8;font-size:0.8rem">Pelanggan *</label>
                            <select class="form-control" id="wo-pelanggan" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                                <option value="">-- Pilih Pelanggan --</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label style="color:#94a3b8;font-size:0.8rem">Plat Nomor</label>
                                <input type="text" class="form-control" id="wo-plat" placeholder="B 1234 XX" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                            </div>
                            <div class="col-md-6 form-group">
                                <label style="color:#94a3b8;font-size:0.8rem">Tipe Motor</label>
                                <input type="text" class="form-control" id="wo-tipe" placeholder="Honda Vario 125" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="color:#94a3b8;font-size:0.8rem">KM Sekarang</label>
                            <input type="number" class="form-control" id="wo-km" placeholder="0" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                        </div>
                        <div class="form-group">
                            <label style="color:#94a3b8;font-size:0.8rem">Keluhan Pelanggan *</label>
                            <textarea class="form-control" id="wo-keluhan" rows="2" placeholder="Apa yang bermasalah..." style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)"></textarea>
                        </div>
                        <div class="form-group">
                            <label style="color:#94a3b8;font-size:0.8rem">Diagnosa Mekanik</label>
                            <textarea class="form-control" id="wo-diagnosa" rows="2" placeholder="Hasil pemeriksaan..." style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)"></textarea>
                        </div>
                        <div class="form-group">
                            <label style="color:#94a3b8;font-size:0.8rem">Mekanik *</label>
                            <select class="form-control" id="wo-mekanik" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1)">
                                <option value="">-- Pilih Mekanik --</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Right: Jasa & Sparepart -->
                    <div class="col-lg-6 mb-3">
                        <!-- Jasa Servis -->
                        <h6 class="font-bold mb-2" style="color:#22c55e">
                            <i class="fas fa-cogs mr-1"></i> Jasa Servis
                        </h6>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="search-jasa" placeholder="Cari jasa servis..." style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem">
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" onclick="searchJasa()" style="border-radius:0 0.75rem 0.75rem 0"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <div id="jasa-results" class="mb-2" style="max-height:150px;overflow-y:auto"></div>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-hover" id="tabel-jasa">
                                <thead>
                                    <tr>
                                        <th>Jasa</th>
                                        <th class="text-right">Harga</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="jasa-body"></tbody>
                                <tfoot id="jasa-foot" style="display:none">
                                    <tr>
                                        <td colspan="1" class="font-bold" style="color:#f1f5f9">Total Jasa</td>
                                        <td class="text-right font-bold" style="color:#22c55e" id="total-jasa">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Sparepart -->
                        <h6 class="font-bold mb-2" style="color:#0ea5e9">
                            <i class="fas fa-box mr-1"></i> Sparepart
                            <button class="btn btn-sm btn-outline-primary float-right" onclick="openScannerForWO()" style="border-radius:20px">
                                <i class="fas fa-camera"></i> Scan
                            </button>
                        </h6>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="search-sparepart" placeholder="Cari sparepart..." style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" onclick="searchSparepart()" style="border-radius:0 0.75rem 0.75rem 0"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <div id="sparepart-results" class="mb-2" style="max-height:150px;overflow-y:auto"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="tabel-sparepart">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th class="text-center" style="width:80px">Qty</th>
                                        <th class="text-right">Harga</th>
                                        <th style="width:40px"></th>
                                    </tr>
                                </thead>
                                <tbody id="sparepart-body"></tbody>
                                <tfoot id="sparepart-foot" style="display:none">
                                    <tr>
                                        <td colspan="2" class="font-bold" style="color:#f1f5f9">Total Sparepart</td>
                                        <td class="text-right font-bold" style="color:#0ea5e9" id="total-sparepart">Rp 0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="font-bold" style="color:#f59e0b;font-size:1.1rem">
                        Total: <span id="wo-grand-total">Rp 0</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-warning font-bold" onclick="simpanWorkOrder()">
                            <i class="fas fa-save mr-1"></i> SIMPAN WO
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scanner Modal (shared) -->
<div class="modal fade" id="scanner-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-camera mr-1" style="color:#f59e0b"></i> Scan Barcode Sparepart</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="scanner-reader" style="width:100%;min-height:300px"></div>
            </div>
        </div>
    </div>
</div>

<!-- Detail WO Modal -->
<div class="modal fade" id="wo-detail-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold" id="wo-detail-title">Detail Work Order</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body" id="wo-detail-body"></div>
        </div>
    </div>
</div>

<style>
.wo-card {
    border-radius: 1rem;
    overflow: hidden;
    transition: all 0.3s;
    animation: fadeInUp 0.4s ease;
    animation-fill-mode: both;
}
.wo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}
.wo-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}
.search-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    transition: all 0.2s;
    font-size: 0.85rem;
}
.search-dropdown-item:hover {
    background: rgba(14,165,233,0.1);
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
let jasaItems = [];
let sparepartItems = [];
let currentFilter = '';

// Load dropdowns
async function loadDropdowns() {
    const [pelRes, mekRes] = await Promise.all([
        apiRequest('getPelanggan'),
        apiRequest('getMekanik')
    ]);
    if (pelRes.status === 'success') {
        const sel = document.getElementById('wo-pelanggan');
        pelRes.data.forEach(p => {
            sel.innerHTML += '<option value="' + p.id + '" data-tipe="' + escapeHtml(p.tipe_motor||'') + '" data-plat="' + escapeHtml(p.plat_nomor||'') + '">' + escapeHtml(p.nama) + (p.plat_nomor ? ' ('+escapeHtml(p.plat_nomor)+')' : '') + '</option>';
        });
        sel.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                document.getElementById('wo-tipe').value = opt.dataset.tipe || '';
                document.getElementById('wo-plat').value = opt.dataset.plat || '';
            }
        });
    }
    if (mekRes.status === 'success') {
        const sel = document.getElementById('wo-mekanik');
        mekRes.data.forEach(m => {
            sel.innerHTML += '<option value="' + m.id + '">' + escapeHtml(m.nama) + ' (' + escapeHtml(m.jabatan) + ')</option>';
        });
    }
}

// Load WO list
async function loadWOList(status) {
    status = status || '';
    const url = APP_URL + '/api/index.php?action=getWorkOrder&page=1&limit=100' + (status ? '&status=' + status : '');
    const res = await fetch(url);
    const data = await res.json();
    const container = document.getElementById('wo-list');
    
    if (data.status === 'success' && data.data.length > 0) {
        let html = '';
        data.data.forEach(function(wo, i) {
            const statusColors = {
                'proses': { bg: 'rgba(245,158,11,0.15)', color: '#f59e0b', border: 'rgba(245,158,11,0.3)' },
                'selesai': { bg: 'rgba(34,197,94,0.15)', color: '#22c55e', border: 'rgba(34,197,94,0.3)' },
                'diambil': { bg: 'rgba(100,116,139,0.15)', color: '#94a3b8', border: 'rgba(100,116,139,0.3)' },
                'batal': { bg: 'rgba(239,68,68,0.15)', color: '#ef4444', border: 'rgba(239,68,68,0.3)' }
            };
            var s = statusColors[wo.status] || statusColors['proses'];
            
            html += '<div class="col-md-6 col-xl-4 mb-4" style="animation-delay:' + (i * 0.05) + 's">';
            html += '<div class="card wo-card" style="background:#1e293b;border:1px solid rgba(255,255,255,0.06)">';
            html += '<div class="card-body">';
            html += '<div class="d-flex justify-content-between align-items-start mb-3">';
            html += '<div><div class="font-bold" style="color:#f59e0b;font-size:0.9rem">' + wo.no_wo + '</div>';
            html += '<small style="color:#64748b">' + formatDate(wo.created_at) + '</small></div>';
            html += '<span class="wo-status-badge" style="background:' + s.bg + ';color:' + s.color + ';border:1px solid ' + s.border + '">' + wo.status.toUpperCase() + '</span>';
            html += '</div>';
            html += '<div class="mb-2"><small style="color:#64748b">Pelanggan:</small>';
            html += '<div class="font-bold" style="color:#f1f5f9;font-size:0.9rem">' + escapeHtml(wo.pelanggan_nama || 'Umum') + '</div></div>';
            html += '<div class="mb-2"><small style="color:#64748b">Motor:</small>';
            html += '<div style="color:#cbd5e1;font-size:0.85rem">' + escapeHtml(wo.tipe_motor || '-') + ' | ' + escapeHtml(wo.plat_nomor || '-') + '</div></div>';
            html += '<div class="mb-2"><small style="color:#64748b">Mekanik:</small>';
            html += '<div style="color:#cbd5e1;font-size:0.85rem">' + escapeHtml(wo.mekanik_nama || '-') + '</div></div>';
            html += '<div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,0.06)">';
            html += '<div class="font-bold" style="color:#22c55e">' + formatRupiah(wo.grand_total) + '</div>';
            html += '<div>';
            html += '<span class="wo-status-badge mr-1" style="background:' + (wo.status_bayar === 'lunas' ? 'rgba(34,197,94,0.2)' : 'rgba(239,68,68,0.2)') + ';color:' + (wo.status_bayar === 'lunas' ? '#22c55e' : '#ef4444') + ';font-size:0.7rem">' + (wo.status_bayar === 'lunas' ? 'LUNAS' : 'BELUM BAYAR') + '</span>';
            html += '<button class="btn btn-sm btn-outline-warning" onclick="detailWO(' + wo.id + ')" style="border-radius:6px" title="Detail"><i class="fas fa-eye"></i></button>';
            html += '</div></div></div></div></div>';
        });
        container.innerHTML = html;
    } else {
        container.innerHTML = '<div class="col-12 text-center py-5"><i class="fas fa-clipboard-list mb-3" style="font-size:3rem;color:#334155"></i><p style="color:#64748b">Belum ada work order</p></div>';
    }
}

function filterWO(status, btn) {
    currentFilter = status;
    var buttons = document.querySelectorAll('#filter-tabs button');
    buttons.forEach(function(b) {
        b.style.background = 'rgba(255,255,255,0.05)';
        b.style.color = '#94a3b8';
        b.style.border = '1px solid rgba(255,255,255,0.1)';
        b.classList.remove('active');
    });
    if (btn) {
        btn.classList.add('active');
        btn.style.background = 'rgba(14,165,233,0.2)';
        btn.style.color = '#0ea5e9';
        btn.style.border = '1px solid rgba(14,165,233,0.3)';
    }
    loadWOList(status);
}

function openFormWO() {
    jasaItems = [];
    sparepartItems = [];
    document.getElementById('wo-pelanggan').value = '';
    document.getElementById('wo-plat').value = '';
    document.getElementById('wo-tipe').value = '';
    document.getElementById('wo-km').value = '';
    document.getElementById('wo-keluhan').value = '';
    document.getElementById('wo-diagnosa').value = '';
    document.getElementById('wo-mekanik').value = '';
    document.getElementById('jasa-body').innerHTML = '';
    document.getElementById('sparepart-body').innerHTML = '';
    document.getElementById('jasa-foot').style.display = 'none';
    document.getElementById('sparepart-foot').style.display = 'none';
    document.getElementById('jasa-results').innerHTML = '';
    document.getElementById('sparepart-results').innerHTML = '';
    updateWOTotals();
    $('#wo-form-modal').modal('show');
}

// Jasa search
var jasaTimeout;
document.getElementById('search-jasa').addEventListener('input', function() {
    clearTimeout(jasaTimeout);
    var val = this.value.trim();
    if (val.length < 1) { document.getElementById('jasa-results').innerHTML = ''; return; }
    jasaTimeout = setTimeout(function() { doSearchJasa(val); }, 300);
});

function searchJasa() {
    var val = document.getElementById('search-jasa').value.trim();
    if (val) doSearchJasa(val);
}

async function doSearchJasa(query) {
    var res = await fetch(APP_URL + '/api/index.php?action=getJasaServis&search=' + encodeURIComponent(query));
    var data = await res.json();
    var container = document.getElementById('jasa-results');
    if (data.status === 'success' && data.data.length > 0) {
        container.innerHTML = data.data.map(function(j) {
            return '<div class="search-dropdown-item" onclick="addJasa(' + j.id + ', \'' + escapeHtml(j.nama).replace(/'/g, "\\'") + '\', ' + j.harga + ')" style="background:#1e293b;border-radius:0.5rem;margin-bottom:4px"><div class="d-flex justify-content-between"><span style="color:#f1f5f9">' + escapeHtml(j.nama) + '</span><span style="color:#22c55e;font-weight:bold">' + formatRupiah(j.harga) + '</span></div></div>';
        }).join('');
    } else {
        container.innerHTML = '<div class="text-center p-2" style="color:#64748b;font-size:0.8rem;background:#1e293b;border-radius:0.5rem">Tidak ditemukan</div>';
    }
}

function addJasa(id, nama, harga) {
    for (var i = 0; i < jasaItems.length; i++) {
        if (jasaItems[i].jasa_id === id) { showToast('Jasa sudah ditambahkan', 'warning'); return; }
    }
    jasaItems.push({ jasa_id: id, nama: nama, harga: harga, subtotal: harga });
    document.getElementById('search-jasa').value = '';
    document.getElementById('jasa-results').innerHTML = '';
    renderJasa();
}

function removeJasa(index) {
    jasaItems.splice(index, 1);
    renderJasa();
}

function renderJasa() {
    var tbody = document.getElementById('jasa-body');
    var foot = document.getElementById('jasa-foot');
    if (jasaItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center" style="color:#475569;font-size:0.8rem">Belum ada jasa</td></tr>';
        foot.style.display = 'none';
    } else {
        tbody.innerHTML = jasaItems.map(function(j, i) {
            return '<tr><td style="color:#f1f5f9;font-size:0.85rem">' + escapeHtml(j.nama) + '</td><td class="text-right" style="color:#22c55e">' + formatRupiah(j.harga) + '</td><td><button class="btn btn-sm" onclick="removeJasa(' + i + ')" style="color:#ef4444;padding:2px 6px"><i class="fas fa-times"></i></button></td></tr>';
        }).join('');
        foot.style.display = '';
    }
    updateWOTotals();
}

// Sparepart search
var spTimeout;
document.getElementById('search-sparepart').addEventListener('input', function() {
    clearTimeout(spTimeout);
    var val = this.value.trim();
    if (val.length < 1) { document.getElementById('sparepart-results').innerHTML = ''; return; }
    spTimeout = setTimeout(function() { doSearchSparepart(val); }, 300);
});

function searchSparepart() {
    var val = document.getElementById('search-sparepart').value.trim();
    if (val) doSearchSparepart(val);
}

async function doSearchSparepart(query) {
    var res = await fetch(APP_URL + '/api/index.php?action=searchBarang&search=' + encodeURIComponent(query));
    var data = await res.json();
    var container = document.getElementById('sparepart-results');
    if (data.status === 'success' && data.data.length > 0) {
        container.innerHTML = data.data.map(function(b) {
            return '<div class="search-dropdown-item" onclick="addSparepart(' + b.id + ', \'' + escapeHtml(b.nama).replace(/'/g, "\\'") + '\', ' + b.harga_jual + ', ' + b.stok + ', \'' + escapeHtml(b.satuan) + '\')" style="background:#1e293b;border-radius:0.5rem;margin-bottom:4px"><div class="d-flex justify-content-between"><div><span style="color:#f1f5f9">' + escapeHtml(b.nama) + '</span> <small style="color:#64748b">| Stok: ' + b.stok + '</small></div><span style="color:#0ea5e9;font-weight:bold">' + formatRupiah(b.harga_jual) + '</span></div></div>';
        }).join('');
    } else {
        container.innerHTML = '<div class="text-center p-2" style="color:#64748b;font-size:0.8rem;background:#1e293b;border-radius:0.5rem">Tidak ditemukan</div>';
    }
}

function addSparepart(id, nama, harga, stok, satuan) {
    var existing = null;
    for (var i = 0; i < sparepartItems.length; i++) {
        if (sparepartItems[i].barang_id === id) { existing = sparepartItems[i]; break; }
    }
    if (existing) {
        if (existing.qty >= stok) { showToast('Stok tidak cukup', 'warning'); return; }
        existing.qty++;
        existing.subtotal = existing.qty * existing.harga;
    } else {
        if (stok <= 0) { showToast('Stok habis', 'error'); return; }
        sparepartItems.push({ barang_id: id, nama: nama, harga: harga, stok: stok, satuan: satuan, qty: 1, subtotal: harga });
    }
    document.getElementById('search-sparepart').value = '';
    document.getElementById('sparepart-results').innerHTML = '';
    renderSparepart();
    showToast(nama + ' ditambahkan', 'success');
}

function updateSPQty(index, delta) {
    var item = sparepartItems[index];
    var newQty = item.qty + delta;
    if (newQty <= 0) { removeSparepart(index); return; }
    if (newQty > item.stok) { showToast('Stok tidak cukup', 'warning'); return; }
    item.qty = newQty;
    item.subtotal = item.qty * item.harga;
    renderSparepart();
}

function removeSparepart(index) {
    sparepartItems.splice(index, 1);
    renderSparepart();
}

function renderSparepart() {
    var tbody = document.getElementById('sparepart-body');
    var foot = document.getElementById('sparepart-foot');
    if (sparepartItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center" style="color:#475569;font-size:0.8rem">Belum ada sparepart</td></tr>';
        foot.style.display = 'none';
    } else {
        tbody.innerHTML = sparepartItems.map(function(s, i) {
            return '<tr><td style="color:#f1f5f9;font-size:0.85rem">' + escapeHtml(s.nama) + '</td><td class="text-center"><button class="btn btn-sm" onclick="updateSPQty(' + i + ',-1)" style="color:#ef4444;padding:2px 6px">-</button> <span class="mx-1" style="color:#f1f5f9">' + s.qty + '</span> <button class="btn btn-sm" onclick="updateSPQty(' + i + ',1)" style="color:#22c55e;padding:2px 6px">+</button></td><td class="text-right" style="color:#0ea5e9">' + formatRupiah(s.subtotal) + '</td><td><button class="btn btn-sm" onclick="removeSparepart(' + i + ')" style="color:#ef4444;padding:2px 6px"><i class="fas fa-times"></i></button></td></tr>';
        }).join('');
        foot.style.display = '';
    }
    updateWOTotals();
}

function updateWOTotals() {
    var totalJasa = 0;
    jasaItems.forEach(function(j) { totalJasa += j.subtotal; });
    var totalSP = 0;
    sparepartItems.forEach(function(s) { totalSP += s.subtotal; });
    document.getElementById('total-jasa').textContent = formatRupiah(totalJasa);
    document.getElementById('total-sparepart').textContent = formatRupiah(totalSP);
    document.getElementById('wo-grand-total').textContent = formatRupiah(totalJasa + totalSP);
}

async function simpanWorkOrder() {
    var pelangganId = document.getElementById('wo-pelanggan').value;
    var mekanikId = document.getElementById('wo-mekanik').value;
    var keluhan = document.getElementById('wo-keluhan').value.trim();
    
    if (!pelangganId) { showToast('Pilih pelanggan!', 'warning'); return; }
    if (!mekanikId) { showToast('Pilih mekanik!', 'warning'); return; }
    if (!keluhan) { showToast('Isi keluhan pelanggan!', 'warning'); return; }
    if (jasaItems.length === 0 && sparepartItems.length === 0) {
        showToast('Tambahkan minimal satu jasa atau sparepart!', 'warning');
        return;
    }
    
    var totalJasa = 0;
    jasaItems.forEach(function(j) { totalJasa += j.subtotal; });
    var totalSP = 0;
    sparepartItems.forEach(function(s) { totalSP += s.subtotal; });
    
    var data = {
        pelanggan_id: pelangganId,
        mekanik_id: mekanikId,
        plat_nomor: document.getElementById('wo-plat').value,
        tipe_motor: document.getElementById('wo-tipe').value,
        km_sekarang: document.getElementById('wo-km').value || 0,
        keluhan: keluhan,
        diagnosa: document.getElementById('wo-diagnosa').value,
        total_jasa: totalJasa,
        total_sparepart: totalSP,
        jasa: jasaItems.map(function(j) { return { jasa_id: j.jasa_id, harga: j.harga, subtotal: j.subtotal }; }),
        sparepart: sparepartItems.map(function(s) { return { barang_id: s.barang_id, qty: s.qty, harga: s.harga, subtotal: s.subtotal }; })
    };
    
    var res = await apiRequest('addWorkOrder', data);
    if (res.status === 'success') {
        showToast('Work Order berhasil dibuat!', 'success');
        $('#wo-form-modal').modal('hide');
        loadWOList(currentFilter);
    } else {
        showToast(res.message || 'Gagal menyimpan', 'error');
    }
}

async function detailWO(id) {
    var res = await fetch(APP_URL + '/api/index.php?action=getWorkOrderDetail&id=' + id);
    var data = await res.json();
    if (data.status !== 'success') { showToast('Gagal memuat detail', 'error'); return; }
    
    var wo = data.data;
    document.getElementById('wo-detail-title').textContent = 'Detail ' + wo.no_wo;
    
    var statusColors = { 'proses': '#f59e0b', 'selesai': '#22c55e', 'diambil': '#94a3b8', 'batal': '#ef4444' };
    
    var html = '<div class="row mb-3"><div class="col-md-6"><div class="p-3" style="background:#0f172a;border-radius:0.75rem">';
    html += '<div class="small mb-1" style="color:#64748b">Pelanggan</div>';
    html += '<div class="font-bold" style="color:#f1f5f9">' + escapeHtml(wo.pelanggan_nama || 'Umum') + '</div>';
    html += '<div class="small" style="color:#94a3b8">' + escapeHtml(wo.plat_nomor || '-') + ' | ' + escapeHtml(wo.tipe_motor || '-') + '</div>';
    html += '<div class="small mt-1" style="color:#94a3b8">KM: ' + (wo.km_sekarang || '-') + '</div>';
    html += '</div></div>';
    
    html += '<div class="col-md-6"><div class="p-3" style="background:#0f172a;border-radius:0.75rem">';
    html += '<div class="small mb-1" style="color:#64748b">Status</div>';
    html += '<span class="wo-status-badge" style="background:' + (statusColors[wo.status] || '#f59e0b') + '22;color:' + (statusColors[wo.status] || '#f59e0b') + '">' + wo.status.toUpperCase() + '</span> ';
    html += '<span class="wo-status-badge" style="background:' + (wo.status_bayar === 'lunas' ? '#22c55e' : '#ef4444') + '22;color:' + (wo.status_bayar === 'lunas' ? '#22c55e' : '#ef4444') + '">' + (wo.status_bayar === 'lunas' ? 'LUNAS' : 'BELUM BAYAR') + '</span>';
    html += '<div class="small mt-1" style="color:#94a3b8">Mekanik: ' + escapeHtml(wo.mekanik_nama || '-') + '</div>';
    html += '</div></div></div>';
    
    if (wo.keluhan) html += '<div class="mb-3"><div class="small" style="color:#64748b">Keluhan</div><div style="color:#cbd5e1">' + escapeHtml(wo.keluhan) + '</div></div>';
    if (wo.diagnosa) html += '<div class="mb-3"><div class="small" style="color:#64748b">Diagnosa</div><div style="color:#cbd5e1">' + escapeHtml(wo.diagnosa) + '</div></div>';
    
    if (wo.jasa_list && wo.jasa_list.length > 0) {
        html += '<h6 class="font-bold mt-3 mb-2" style="color:#22c55e">Jasa Servis</h6><table class="table table-sm"><thead><tr><th>Jasa</th><th class="text-right">Harga</th></tr></thead><tbody>';
        wo.jasa_list.forEach(function(j) {
            html += '<tr><td style="color:#f1f5f9">' + escapeHtml(j.jasa_nama) + '</td><td class="text-right" style="color:#22c55e">' + formatRupiah(j.subtotal) + '</td></tr>';
        });
        html += '</tbody></table>';
    }
    
    if (wo.sparepart_list && wo.sparepart_list.length > 0) {
        html += '<h6 class="font-bold mt-3 mb-2" style="color:#0ea5e9">Sparepart</h6><table class="table table-sm"><thead><tr><th>Barang</th><th class="text-center">Qty</th><th class="text-right">Harga</th></tr></thead><tbody>';
        wo.sparepart_list.forEach(function(s) {
            html += '<tr><td style="color:#f1f5f9">' + escapeHtml(s.barang_nama) + '</td><td class="text-center" style="color:#94a3b8">' + s.qty + '</td><td class="text-right" style="color:#0ea5e9">' + formatRupiah(s.subtotal) + '</td></tr>';
        });
        html += '</tbody></table>';
    }
    
    html += '<div class="d-flex justify-content-between align-items-center mt-3 p-3" style="background:#0f172a;border-radius:0.75rem"><div>';
    html += '<div class="small" style="color:#64748b">Total</div>';
    html += '<div class="font-bold" style="color:#f59e0b;font-size:1.2rem">' + formatRupiah(wo.grand_total) + '</div></div><div>';
    
    if (wo.status === 'proses') {
        html += '<button class="btn btn-success btn-sm mr-1" onclick="updateWOStatus(' + wo.id + ', \'selesai\')">Tandai Selesai</button>';
    } else if (wo.status === 'selesai' && wo.status_bayar !== 'lunas') {
        html += '<button class="btn btn-warning btn-sm mr-1" onclick="updateWOBayar(' + wo.id + ', \'lunas\')">Tandai Lunas</button>';
        html += '<button class="btn btn-secondary btn-sm mr-1" onclick="updateWOStatus(' + wo.id + ', \'diambil\')">Tandai Diambil</button>';
    } else if (wo.status === 'selesai') {
        html += '<button class="btn btn-secondary btn-sm mr-1" onclick="updateWOStatus(' + wo.id + ', \'diambil\')">Tandai Diambil</button>';
    }
    
    html += '</div></div>';
    
    document.getElementById('wo-detail-body').innerHTML = html;
    $('#wo-detail-modal').modal('show');
}

async function updateWOStatus(id, status) {
    var res = await apiRequest('updateWorkOrderStatus', { id: id, status: status });
    if (res.status === 'success') {
        showToast('Status diupdate', 'success');
        $('#wo-detail-modal').modal('hide');
        loadWOList(currentFilter);
    } else {
        showToast(res.message || 'Gagal', 'error');
    }
}

async function updateWOBayar(id, status) {
    var res = await apiRequest('updateWorkOrderBayar', { id: id, status: status });
    if (res.status === 'success') {
        showToast('Pembayaran diupdate', 'success');
        $('#wo-detail-modal').modal('hide');
        loadWOList(currentFilter);
    } else {
        showToast(res.message || 'Gagal', 'error');
    }
}

// Scanner for WO sparepart
function openScannerForWO() {
    $('#scanner-modal').modal('show');
    setTimeout(function() {
        startScanner('scanner-reader', function(decodedText) {
            document.getElementById('search-sparepart').value = decodedText;
            doSearchSparepart(decodedText);
            stopScanner();
            $('#scanner-modal').modal('hide');
        });
    }, 500);
}
$('#scanner-modal').on('hidden.bs.modal', function() { stopScanner(); });

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// Init
loadDropdowns();
loadWOList();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
