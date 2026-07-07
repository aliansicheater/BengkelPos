<?php
$pageTitle = 'Retur';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin','kasir','gudang']);
?>

<div class="animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold" style="color:#f1f5f9">
                <i class="fas fa-undo text-primary-500 mr-2"></i>Retur Barang
            </h1>
            <p class="text-sm mt-1" style="color:#64748b">Kelola retur pembelian dan penjualan</p>
        </div>
        <button onclick="openNewRetur()" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Retur Baru
        </button>
    </div>

    <!-- Tabel Retur -->
    <div class="card overflow-hidden animate-fade-in-up stagger-1">
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No Retur</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Tipe</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Keterangan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Oleh</th>
                    </tr>
                </thead>
                <tbody id="retur-table-body">
                    <tr><td colspan="6" class="text-center py-8" style="color:#64748b">
                        <div class="skeleton h-4 w-48 mx-auto mb-2"></div>
                        <p>Memuat data...</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Retur -->
<div class="modal fade" id="returModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold"><i class="fas fa-undo mr-2 text-primary-500"></i>Retur Baru</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="form-label text-sm">Tipe Retur <span class="text-red-400">*</span></label>
                        <select class="form-control" id="rt-type">
                            <option value="penjualan">Penjualan</option>
                            <option value="pembelian">Pembelian</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-sm">Ref ID (No Transaksi)</label>
                        <input type="number" class="form-control" id="rt-ref" placeholder="ID transaksi">
                    </div>
                    <div>
                        <label class="form-label text-sm">Tanggal</label>
                        <input type="date" class="form-control" id="rt-tanggal" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Keterangan</label>
                    <input type="text" class="form-control" id="rt-keterangan" placeholder="Alasan retur...">
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Cari Barang</label>
                    <input type="text" class="form-control" id="rt-search" placeholder="Scan barcode atau ketik nama..." oninput="searchBarangRetur()">
                </div>
                <div class="max-h-40 overflow-y-auto" id="rt-search-results"></div>
                <hr class="my-3" style="border-color:rgba(255,255,255,0.05)">
                <p class="text-sm font-semibold mb-2" style="color:#f1f5f9">Daftar Barang Retur</p>
                <div id="rt-items" class="space-y-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="simpanRetur()">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let returItems = [];

async function loadRetur() {
    const res = await apiRequest('getRetur');
    const data = res.data || [];
    const tbody = document.getElementById('retur-table-body');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8" style="color:#64748b"><i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>Belum ada retur</td></tr>';
        return;
    }
    tbody.innerHTML = data.map((r, i) => `
        <tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-bold" style="color:#0ea5e9">${r.no_retur}</td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${r.type === 'penjualan' ? 'badge-info' : 'badge-success'}">${r.type === 'penjualan' ? 'Penjualan' : 'Pembelian'}</span>
            </td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${formatDate(r.tanggal)}</td>
            <td class="px-4 py-3 text-sm" style="color:#f1f5f9">${r.keterangan || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${r.user_nama || '-'}</td>
        </tr>
    `).join('');
}

function openNewRetur() {
    returItems = [];
    document.getElementById('rt-items').innerHTML = '<p class="text-xs text-center" style="color:#64748b">Belum ada barang ditambahkan</p>';
    document.getElementById('rt-search-results').innerHTML = '';
    document.getElementById('rt-search').value = '';
    document.getElementById('rt-ref').value = '';
    document.getElementById('rt-keterangan').value = '';
    $('#returModal').modal('show');
}

async function searchBarangRetur() {
    const q = document.getElementById('rt-search').value;
    if (q.length < 1) { document.getElementById('rt-search-results').innerHTML = ''; return; }
    const res = await apiRequest('searchBarang', { search: q });
    const items = res.data || [];
    document.getElementById('rt-search-results').innerHTML = items.map(b => `
        <div onclick="addItemRetur(${b.id}, '${b.nama.replace(/'/g,"\\'")}', ${b.harga_jual})" class="p-2 rounded-lg mb-1 cursor-pointer transition-all hover:bg-white/10" style="background:rgba(255,255,255,0.03)">
            <div class="flex justify-between items-center">
                <span class="text-sm font-semibold" style="color:#f1f5f9">${b.nama}</span>
                <span class="text-xs" style="color:#94a3b8">${formatRupiah(b.harga_jual)}</span>
            </div>
        </div>
    `).join('');
}

function addItemRetur(id, nama, harga) {
    if (returItems.find(i => i.barang_id === id)) return;
    returItems.push({ barang_id: id, nama, harga, qty: 1 });
    renderReturItems();
    document.getElementById('rt-search-results').innerHTML = '';
    document.getElementById('rt-search').value = '';
}

function renderReturItems() {
    const container = document.getElementById('rt-items');
    if (returItems.length === 0) {
        container.innerHTML = '<p class="text-xs text-center" style="color:#64748b">Belum ada barang ditambahkan</p>';
        return;
    }
    container.innerHTML = returItems.map((item, idx) => `
        <div class="flex items-center gap-2 p-2 rounded-lg" style="background:rgba(255,255,255,0.03)">
            <div class="flex-1">
                <p class="text-sm font-semibold" style="color:#f1f5f9">${item.nama}</p>
                <p class="text-xs" style="color:#94a3b8">${formatRupiah(item.harga)}</p>
            </div>
            <input type="number" class="form-control form-control-sm" style="width:70px" value="${item.qty}" min="1" onchange="updateQtyRetur(${idx}, this.value)">
            <button onclick="removeItemRetur(${idx})" class="px-2 py-1 rounded text-xs" style="color:#ef4444;background:rgba(239,68,68,0.15)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `).join('');
}

function updateQtyRetur(idx, val) { returItems[idx].qty = parseInt(val) || 1; }
function removeItemRetur(idx) { returItems.splice(idx, 1); renderReturItems(); }

async function simpanRetur() {
    if (returItems.length === 0) return showToast('Tambahkan minimal 1 barang', 'warning');
    await apiRequest('addRetur', {
        type: document.getElementById('rt-type').value,
        ref_id: parseInt(document.getElementById('rt-ref').value) || 0,
        tanggal: document.getElementById('rt-tanggal').value,
        keterangan: document.getElementById('rt-keterangan').value,
        items: returItems.map(i => ({ barang_id: i.barang_id, qty: i.qty, harga: i.harga }))
    }, 'POST');
    $('#returModal').modal('hide');
    loadRetur();
}

loadRetur();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
