<?php
$pageTitle = 'Manajemen Stok';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin','gudang']);
?>

<div class="animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold" style="color:#f1f5f9">
                <i class="fas fa-warehouse text-primary-500 mr-2"></i>Manajemen Stok
            </h1>
            <p class="text-sm mt-1" style="color:#64748b">Monitor dan kelola stok barang</p>
        </div>
        <div class="flex gap-2">
            <input type="text" class="form-control" id="search-stok" placeholder="Cari barang..." style="width:220px" oninput="searchStok()">
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="card p-4 stagger-1 animate-fade-in-up">
            <p class="text-xs" style="color:#64748b">Total Barang</p>
            <p class="text-xl font-bold" style="color:#f1f5f9" id="total-barang">0</p>
        </div>
        <div class="card p-4 stagger-2 animate-fade-in-up">
            <p class="text-xs" style="color:#64748b">Total Stok</p>
            <p class="text-xl font-bold" style="color:#0ea5e9" id="total-stok">0</p>
        </div>
        <div class="card p-4 stagger-3 animate-fade-in-up">
            <p class="text-xs" style="color:#64748b">Stok Menipis</p>
            <p class="text-xl font-bold" style="color:#f59e0b" id="stok-menipis">0</p>
        </div>
        <div class="card p-4 stagger-4 animate-fade-in-up">
            <p class="text-xs" style="color:#64748b">Stok Habis</p>
            <p class="text-xl font-bold" style="color:#ef4444" id="stok-habis">0</p>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden animate-fade-in-up stagger-2">
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Nama Barang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Kategori</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Stok</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Min. Stok</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Lokasi</th>
                    </tr>
                </thead>
                <tbody id="stok-table-body">
                    <tr><td colspan="7" class="text-center py-8" style="color:#64748b">
                        <div class="skeleton h-4 w-48 mx-auto mb-2"></div>
                        <p>Memuat data...</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let allBarang = [];

async function loadStok() {
    const res = await apiRequest('getBarang', { limit: 200 });
    allBarang = res.data || [];
    renderStok(allBarang);
    updateSummary();
}

function renderStok(data) {
    const tbody = document.getElementById('stok-table-body');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8" style="color:#64748b"><i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>Tidak ada data barang</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(b => {
        const isHabis = b.stok <= 0;
        const isMenipis = b.stok > 0 && b.stok <= b.stok_minimum;
        return `
        <tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm font-mono" style="color:#94a3b8">${b.kode_barang}</td>
            <td class="px-4 py-3 text-sm font-semibold" style="color:#f1f5f9">${b.nama}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${b.kategori_nama || '-'}</td>
            <td class="px-4 py-3 text-sm text-center font-bold" style="color:${isHabis ? '#ef4444' : isMenipis ? '#f59e0b' : '#22c55e'}">${b.stok}</td>
            <td class="px-4 py-3 text-sm text-center" style="color:#94a3b8">${b.stok_minimum}</td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${isHabis ? 'badge-danger' : isMenipis ? 'badge-warning' : 'badge-success'}">
                    ${isHabis ? 'Habis' : isMenipis ? 'Menipis' : 'Aman'}
                </span>
            </td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${b.lokasi_rak || '-'}</td>
        </tr>`;
    }).join('');
}

function updateSummary() {
    document.getElementById('total-barang').textContent = allBarang.length;
    document.getElementById('total-stok').textContent = allBarang.reduce((s, b) => s + (b.stok || 0), 0);
    document.getElementById('stok-menipis').textContent = allBarang.filter(b => b.stok > 0 && b.stok <= b.stok_minimum).length;
    document.getElementById('stok-habis').textContent = allBarang.filter(b => b.stok <= 0).length;
}

function searchStok() {
    const q = document.getElementById('search-stok').value.toLowerCase();
    const filtered = allBarang.filter(b => 
        (b.nama || '').toLowerCase().includes(q) ||
        (b.kode_barang || '').toLowerCase().includes(q) ||
        (b.barcode || '').toLowerCase().includes(q)
    );
    renderStok(filtered);
}

loadStok();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
