<?php
$pageTitle = 'Stock Opname';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin','gudang']);
?>

<div class="animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold" style="color:#f1f5f9">
                <i class="fas fa-clipboard-check text-primary-500 mr-2"></i>Stock Opname
            </h1>
            <p class="text-sm mt-1" style="color:#64748b">Pencatatan fisik stok barang</p>
        </div>
        <button onclick="openNewOpname()" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Stock Opname Baru
        </button>
    </div>

    <!-- Riwayat Opname -->
    <div class="card overflow-hidden animate-fade-in-up stagger-1">
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No Opname</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Keterangan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Oleh</th>
                    </tr>
                </thead>
                <tbody id="opname-table-body">
                    <tr><td colspan="5" class="text-center py-8" style="color:#64748b">
                        <div class="skeleton h-4 w-48 mx-auto mb-2"></div>
                        <p>Memuat data...</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal New Opname -->
<div class="modal fade" id="opnameModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold"><i class="fas fa-clipboard-check mr-2 text-primary-500"></i>Stock Opname Baru</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label text-sm">Tanggal</label>
                        <input type="date" class="form-control" id="op-tanggal" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="form-label text-sm">Keterangan</label>
                        <input type="text" class="form-control" id="op-keterangan" placeholder="Keterangan...">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Cari Barang</label>
                    <input type="text" class="form-control" id="op-search" placeholder="Scan barcode atau ketik nama..." oninput="searchBarangOpname()">
                </div>
                <div class="max-h-60 overflow-y-auto" id="op-search-results"></div>
                <hr class="my-3" style="border-color:rgba(255,255,255,0.05)">
                <p class="text-sm font-semibold mb-2" style="color:#f1f5f9">Daftar Barang Opname</p>
                <div id="op-items" class="space-y-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="simpanOpname()">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let opnameItems = [];

async function loadOpname() {
    const res = await apiRequest('getStockOpname');
    const data = res.data || [];
    const tbody = document.getElementById('opname-table-body');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8" style="color:#64748b"><i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>Belum ada stock opname</td></tr>';
        return;
    }
    tbody.innerHTML = data.map((o, i) => `
        <tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-bold" style="color:#0ea5e9">${o.no_opname}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${formatDate(o.tanggal)}</td>
            <td class="px-4 py-3 text-sm" style="color:#f1f5f9">${o.keterangan || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${o.user_nama || '-'}</td>
        </tr>
    `).join('');
}

function openNewOpname() {
    opnameItems = [];
    document.getElementById('op-items').innerHTML = '<p class="text-xs text-center" style="color:#64748b">Belum ada barang ditambahkan</p>';
    document.getElementById('op-search-results').innerHTML = '';
    document.getElementById('op-search').value = '';
    $('#opnameModal').modal('show');
}

async function searchBarangOpname() {
    const q = document.getElementById('op-search').value;
    if (q.length < 1) { document.getElementById('op-search-results').innerHTML = ''; return; }
    const res = await apiRequest('searchBarang', { search: q });
    const items = res.data || [];
    document.getElementById('op-search-results').innerHTML = items.map(b => `
        <div onclick="addItemOpname(${b.id}, '${b.nama.replace(/'/g,"\\'")}', ${b.stok})" class="p-2 rounded-lg mb-1 cursor-pointer transition-all hover:bg-white/10" style="background:rgba(255,255,255,0.03)">
            <div class="flex justify-between items-center">
                <span class="text-sm font-semibold" style="color:#f1f5f9">${b.nama}</span>
                <span class="text-xs" style="color:#94a3b8">Stok: ${b.stok}</span>
            </div>
        </div>
    `).join('');
}

function addItemOpname(id, nama, stokSistem) {
    if (opnameItems.find(i => i.barang_id === id)) return;
    opnameItems.push({ barang_id: id, nama, stok_sistem: stokSistem, stok_fisik: stokSistem });
    renderOpnameItems();
    document.getElementById('op-search-results').innerHTML = '';
    document.getElementById('op-search').value = '';
}

function renderOpnameItems() {
    const container = document.getElementById('op-items');
    if (opnameItems.length === 0) {
        container.innerHTML = '<p class="text-xs text-center" style="color:#64748b">Belum ada barang ditambahkan</p>';
        return;
    }
    container.innerHTML = opnameItems.map((item, idx) => `
        <div class="flex items-center gap-2 p-2 rounded-lg" style="background:rgba(255,255,255,0.03)">
            <div class="flex-1">
                <p class="text-sm font-semibold" style="color:#f1f5f9">${item.nama}</p>
                <p class="text-xs" style="color:#94a3b8">Stok Sistem: ${item.stok_sistem}</p>
            </div>
            <input type="number" class="form-control form-control-sm" style="width:80px" value="${item.stok_fisik}" onchange="updateFisik(${idx}, this.value)">
            <button onclick="removeItemOpname(${idx})" class="px-2 py-1 rounded text-xs" style="color:#ef4444;background:rgba(239,68,68,0.15)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `).join('');
}

function updateFisik(idx, val) { opnameItems[idx].stok_fisik = parseInt(val) || 0; }
function removeItemOpname(idx) { opnameItems.splice(idx, 1); renderOpnameItems(); }

async function simpanOpname() {
    if (opnameItems.length === 0) return showToast('Tambahkan minimal 1 barang', 'warning');
    await apiRequest('addStockOpname', {
        tanggal: document.getElementById('op-tanggal').value,
        keterangan: document.getElementById('op-keterangan').value,
        items: opnameItems.map(i => ({ barang_id: i.barang_id, stok_sistem: i.stok_sistem, stok_fisik: i.stok_fisik }))
    }, 'POST');
    $('#opnameModal').modal('hide');
    loadOpname();
}

loadOpname();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
