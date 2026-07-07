<?php
$pageTitle = 'Piutang Pelanggan';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin']);
?>

<div class="animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold" style="color:#f1f5f9">
                <i class="fas fa-hand-holding-usd text-primary-500 mr-2"></i>Piutang Pelanggan
            </h1>
            <p class="text-sm mt-1" style="color:#64748b">Kelola pembayaran piutang dari pelanggan</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div class="card p-4 stagger-1 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(239,68,68,0.15)">
                    <i class="fas fa-exclamation-triangle" style="color:#ef4444"></i>
                </div>
                <div>
                    <p class="text-xs" style="color:#64748b">Sisa Piutang Belum Bayar</p>
                    <p class="text-lg font-bold" style="color:#ef4444" id="total-piutang">Rp 0</p>
                </div>
            </div>
        </div>
        <div class="card p-4 stagger-2 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(245,158,11,0.15)">
                    <i class="fas fa-clock" style="color:#f59e0b"></i>
                </div>
                <div>
                    <p class="text-xs" style="color:#64748b">Total Terbayar</p>
                    <p class="text-lg font-bold" style="color:#f59e0b" id="total-terbayar">Rp 0</p>
                </div>
            </div>
        </div>
        <div class="card p-4 stagger-3 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(34,197,94,0.15)">
                    <i class="fas fa-check-circle" style="color:#22c55e"></i>
                </div>
                <div>
                    <p class="text-xs" style="color:#64748b">Jumlah Piutang Aktif</p>
                    <p class="text-lg font-bold" style="color:#22c55e" id="jml-piutang">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
        <button onclick="filterPiutang('')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-semua" style="background:rgba(14,165,233,0.15);color:#0ea5e9">Semua</button>
        <button onclick="filterPiutang('belum')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-belum" style="background:rgba(255,255,255,0.05);color:#94a3b8">Belum Bayar</button>
        <button onclick="filterPiutang('cicilan')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-cicilan" style="background:rgba(255,255,255,0.05);color:#94a3b8">Cicilan</button>
        <button onclick="filterPiutang('lunas')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-lunas" style="background:rgba(255,255,255,0.05);color:#94a3b8">Lunas</button>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden animate-fade-in-up stagger-2">
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Referensi</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Jumlah</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Terbayar</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Sisa</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Aksi</th>
                    </tr>
                </thead>
                <tbody id="piutang-table-body">
                    <tr><td colspan="9" class="text-center py-8" style="color:#64748b">
                        <div class="skeleton h-4 w-48 mx-auto mb-2"></div>
                        <p>Memuat data...</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bayar Piutang Modal -->
<div class="modal fade" id="bayarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold"><i class="fas fa-money-bill-wave mr-2 text-accent-500"></i>Bayar Piutang</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="bayar-id">
                <div class="mb-3">
                    <label class="form-label text-sm">Pelanggan</label>
                    <input type="text" class="form-control" id="bayar-pelanggan" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Sisa Piutang</label>
                    <input type="text" class="form-control" id="bayar-sisa" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Jumlah Bayar <span class="text-red-400">*</span></label>
                    <input type="number" class="form-control" id="bayar-jumlah" placeholder="Masukkan jumlah bayar" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="prosesBayar()">
                    <i class="fas fa-check mr-1"></i> Bayar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let allPiutang = [];
let currentFilter = '';

async function loadPiutang() {
    const res = await apiRequest('getPiutang');
    allPiutang = res.data || [];
    renderPiutang();
    updateSummary();
}

function renderPiutang() {
    const tbody = document.getElementById('piutang-table-body');
    let filtered = currentFilter ? allPiutang.filter(p => p.status === currentFilter) : allPiutang;
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8" style="color:#64748b"><i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>Tidak ada data piutang</td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.map((p, i) => `
        <tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-semibold" style="color:#f1f5f9">${p.pelanggan_nama || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">
                <span class="px-2 py-0.5 rounded text-xs font-semibold ${p.ref_type === 'wo' ? 'badge-info' : 'badge-success'}">${p.ref_type === 'wo' ? 'Servis' : 'Penjualan'}</span>
            </td>
            <td class="px-4 py-3 text-sm text-right font-semibold" style="color:#f1f5f9">${formatRupiah(p.jumlah)}</td>
            <td class="px-4 py-3 text-sm text-right" style="color:#22c55e">${formatRupiah(p.terbayar)}</td>
            <td class="px-4 py-3 text-sm text-right font-bold" style="color:${p.sisa_piutang > 0 ? '#ef4444' : '#22c55e'}">${formatRupiah(p.sisa_piutang)}</td>
            <td class="px-4 py-3 text-sm" style="color:${p.jatuh_tempo && new Date(p.jatuh_tempo) < new Date() ? '#ef4444' : '#94a3b8'}">${p.jatuh_tempo ? formatDate(p.jatuh_tempo) : '-'}</td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${p.status === 'lunas' ? 'badge-success' : p.status === 'cicilan' ? 'badge-warning' : 'badge-danger'}">
                    ${p.status === 'lunas' ? 'Lunas' : p.status === 'cicilan' ? 'Cicilan' : 'Belum'}
                </span>
            </td>
            <td class="px-4 py-3 text-center">
                ${p.status !== 'lunas' ? `
                    <button onclick="openBayar(${p.id}, '${p.pelanggan_nama}', ${p.sisa_piutang})" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:scale-105" style="background:rgba(34,197,94,0.15);color:#22c55e">
                        <i class="fas fa-money-bill-wave mr-1"></i>Bayar
                    </button>
                ` : '<span class="text-xs" style="color:#64748b">Selesai</span>'}
            </td>
        </tr>
    `).join('');
}

function updateSummary() {
    const belumBayar = allPiutang.filter(p => p.status !== 'lunas');
    const totalSisa = belumBayar.reduce((s, p) => s + parseFloat(p.sisa_piutang || 0), 0);
    const totalBayar = allPiutang.reduce((s, p) => s + parseFloat(p.terbayar || 0), 0);
    document.getElementById('total-piutang').textContent = formatRupiah(totalSisa);
    document.getElementById('total-terbayar').textContent = formatRupiah(totalBayar);
    document.getElementById('jml-piutang').textContent = belumBayar.length;
}

function filterPiutang(status) {
    currentFilter = status;
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.style.background = 'rgba(255,255,255,0.05)';
        el.style.color = '#94a3b8';
    });
    const activeTab = document.getElementById('tab-' + (status || 'semua'));
    activeTab.style.background = 'rgba(14,165,233,0.15)';
    activeTab.style.color = '#0ea5e9';
    renderPiutang();
}

function openBayar(id, pelanggan, sisa) {
    document.getElementById('bayar-id').value = id;
    document.getElementById('bayar-pelanggan').value = pelanggan;
    document.getElementById('bayar-sisa').value = formatRupiah(sisa);
    document.getElementById('bayar-jumlah').max = sisa;
    document.getElementById('bayar-jumlah').value = '';
    $('#bayarModal').modal('show');
}

async function prosesBayar() {
    const id = document.getElementById('bayar-id').value;
    const jumlah = parseFloat(document.getElementById('bayar-jumlah').value);
    if (!jumlah || jumlah <= 0) return showToast('Masukkan jumlah bayar', 'warning');
    
    await apiRequest('bayarPiutang', { id: parseInt(id), jumlah }, 'POST');
    $('#bayarModal').modal('hide');
    loadPiutang();
}

loadPiutang();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
