<?php
$pageTitle = 'Laporan';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin']);
?>

<div class="animate-fade-in-up">
    <div class="mb-4">
        <h1 class="text-xl font-bold" style="color:#f1f5f9">
            <i class="fas fa-chart-bar text-primary-500 mr-2"></i>Laporan
        </h1>
        <p class="text-sm mt-1" style="color:#64748b">Lihat laporan bisnis bengkel</p>
    </div>

    <!-- Date Filter -->
    <div class="card p-4 mb-4 animate-fade-in-up stagger-1">
        <div class="flex flex-col sm:flex-row gap-3 items-end flex-wrap">
            <div class="flex-1">
                <label class="form-label text-sm">Dari Tanggal</label>
                <input type="date" class="form-control" id="lap-start" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="flex-1">
                <label class="form-label text-sm">Sampai Tanggal</label>
                <input type="date" class="form-control" id="lap-end" value="<?= date('Y-m-d') ?>">
            </div>
            <button onclick="loadAllLaporan()" class="btn btn-primary">
                <i class="fas fa-sync mr-1"></i> Muat Laporan
            </button>
            <div class="relative">
                <button onclick="togglePdfDropdown()" class="btn" style="background:rgba(239,68,68,0.15);color:#ef4444;border:1px solid rgba(239,68,68,0.3)">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </button>
                <div id="pdf-dropdown" class="hidden absolute right-0 mt-1 rounded-xl shadow-lg z-50 min-w-[160px]" style="background:#1e293b;border:1px solid rgba(148,163,184,0.2)">
                    <a href="#" onclick="exportPDF('keuangan');return false" class="block px-4 py-2 text-sm hover:bg-white/10 transition-all" style="color:#94a3b8;border-radius:0.5rem 0.5rem 0 0">Keuangan</a>
                    <a href="#" onclick="exportPDF('penjualan');return false" class="block px-4 py-2 text-sm hover:bg-white/10 transition-all" style="color:#94a3b8">Penjualan</a>
                    <a href="#" onclick="exportPDF('pembelian');return false" class="block px-4 py-2 text-sm hover:bg-white/10 transition-all" style="color:#94a3b8">Pembelian</a>
                    <a href="#" onclick="exportPDF('servis');return false" class="block px-4 py-2 text-sm hover:bg-white/10 transition-all" style="color:#94a3b8;border-radius:0 0 0.5rem 0.5rem">Servis</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
        <button onclick="switchTab('keuangan')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-keuangan" style="background:rgba(14,165,233,0.15);color:#0ea5e9">Keuangan</button>
        <button onclick="switchTab('penjualan')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-penjualan" style="background:rgba(255,255,255,0.05);color:#94a3b8">Penjualan</button>
        <button onclick="switchTab('pembelian')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-pembelian" style="background:rgba(255,255,255,0.05);color:#94a3b8">Pembelian</button>
        <button onclick="switchTab('servis')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-servis" style="background:rgba(255,255,255,0.05);color:#94a3b8">Servis</button>
    </div>

    <!-- Keuangan Tab -->
    <div id="content-keuangan" class="laporan-content">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="card p-4 animate-fade-in-up">
                <p class="text-xs" style="color:#64748b">Total Penjualan</p>
                <p class="text-lg font-bold" style="color:#22c55e" id="lap-penjualan">Rp 0</p>
            </div>
            <div class="card p-4 animate-fade-in-up stagger-1">
                <p class="text-xs" style="color:#64748b">Total Servis</p>
                <p class="text-lg font-bold" style="color:#0ea5e9" id="lap-servis-total">Rp 0</p>
            </div>
            <div class="card p-4 animate-fade-in-up stagger-2">
                <p class="text-xs" style="color:#64748b">Total Pembelian</p>
                <p class="text-lg font-bold" style="color:#ef4444" id="lap-pembelian">Rp 0</p>
            </div>
            <div class="card p-4 animate-fade-in-up stagger-3">
                <p class="text-xs" style="color:#64748b">Laba Kotor</p>
                <p class="text-lg font-bold" style="color:#f59e0b" id="lap-laba">Rp 0</p>
            </div>
        </div>
    </div>

    <!-- Penjualan Tab -->
    <div id="content-penjualan" class="laporan-content" style="display:none">
        <div class="card overflow-hidden animate-fade-in-up">
            <div class="overflow-x-auto">
                <table class="table table-hover w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No Transaksi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Tanggal</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Grand Total</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Kasir</th>
                        </tr>
                    </thead>
                    <tbody id="lap-penjualan-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pembelian Tab -->
    <div id="content-pembelian" class="laporan-content" style="display:none">
        <div class="card overflow-hidden animate-fade-in-up">
            <div class="overflow-x-auto">
                <table class="table table-hover w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No Faktur</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Supplier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Tanggal</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Grand Total</th>
                        </tr>
                    </thead>
                    <tbody id="lap-pembelian-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Servis Tab -->
    <div id="content-servis" class="laporan-content" style="display:none">
        <div class="card overflow-hidden animate-fade-in-up">
            <div class="overflow-x-auto">
                <table class="table table-hover w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No WO</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Pelanggan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Mekanik</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Total</th>
                        </tr>
                    </thead>
                    <tbody id="lap-servis-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentTab = 'keuangan';

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.style.background = 'rgba(255,255,255,0.05)';
        el.style.color = '#94a3b8';
    });
    document.getElementById('tab-' + tab).style.background = 'rgba(14,165,233,0.15)';
    document.getElementById('tab-' + tab).style.color = '#0ea5e9';
    document.querySelectorAll('.laporan-content').forEach(el => el.style.display = 'none');
    document.getElementById('content-' + tab).style.display = 'block';
}

function togglePdfDropdown() {
    document.getElementById('pdf-dropdown').classList.toggle('hidden');
}

function exportPDF(type) {
    const start = document.getElementById('lap-start').value;
    const end = document.getElementById('lap-end').value;
    window.open(APP_URL + '/cetak/laporan_pdf.php?type=' + type + '&start=' + start + '&end=' + end, '_blank');
    document.getElementById('pdf-dropdown').classList.add('hidden');
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('pdf-dropdown');
    const btn = e.target.closest('.relative');
    if (!btn || !btn.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

async function loadAllLaporan() {
    const start = document.getElementById('lap-start').value;
    const end = document.getElementById('lap-end').value;
    
    const fin = await apiRequest('getLaporanKeuangan', { start, end });
    if (fin.data) {
        document.getElementById('lap-penjualan').textContent = formatRupiah(fin.data.penjualan);
        document.getElementById('lap-servis-total').textContent = formatRupiah(fin.data.servis);
        document.getElementById('lap-pembelian').textContent = formatRupiah(fin.data.pembelian);
        document.getElementById('lap-laba').textContent = formatRupiah(fin.data.laba_kotor);
    }
    
    const jual = await apiRequest('getLaporanPenjualan', { start, end });
    const jualData = jual.data || [];
    document.getElementById('lap-penjualan-body').innerHTML = jualData.length === 0 ? '<tr><td colspan="5" class="text-center py-6" style="color:#64748b">Tidak ada data</td></tr>' :
        jualData.map((p, i) => `<tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-bold" style="color:#0ea5e9">${p.no_transaksi}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${formatDate(p.tanggal)}</td>
            <td class="px-4 py-3 text-sm text-right font-semibold" style="color:#22c55e">${formatRupiah(p.grand_total)}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${p.user_nama || '-'}</td>
        </tr>`).join('');
    
    const beli = await apiRequest('getLaporanPembelian', { start, end });
    const beliData = beli.data || [];
    document.getElementById('lap-pembelian-body').innerHTML = beliData.length === 0 ? '<tr><td colspan="5" class="text-center py-6" style="color:#64748b">Tidak ada data</td></tr>' :
        beliData.map((pb, i) => `<tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-bold" style="color:#0ea5e9">${pb.no_faktur}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${pb.supplier_nama || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${formatDate(pb.tanggal)}</td>
            <td class="px-4 py-3 text-sm text-right font-semibold" style="color:#ef4444">${formatRupiah(pb.grand_total)}</td>
        </tr>`).join('');
    
    const srv = await apiRequest('getLaporanServis', { start, end });
    const srvData = srv.data || [];
    document.getElementById('lap-servis-body').innerHTML = srvData.length === 0 ? '<tr><td colspan="6" class="text-center py-6" style="color:#64748b">Tidak ada data</td></tr>' :
        srvData.map((wo, i) => `<tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-bold" style="color:#0ea5e9">${wo.no_wo}</td>
            <td class="px-4 py-3 text-sm" style="color:#f1f5f9">${wo.pelanggan_nama || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${wo.mekanik_nama || '-'}</td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${wo.status === 'diambil' ? 'badge-success' : wo.status === 'selesai' ? 'badge-warning' : 'badge-info'}">
                    ${wo.status === 'diambil' ? 'Diambil' : wo.status === 'selesai' ? 'Selesai' : 'Proses'}
                </span>
            </td>
            <td class="px-4 py-3 text-sm text-right font-semibold" style="color:#f1f5f9">${formatRupiah(wo.grand_total)}</td>
        </tr>`).join('');
}

loadAllLaporan();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
