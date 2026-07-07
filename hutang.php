<?php
$pageTitle = 'Hutang Supplier';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin']);
?>

<div class="animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold" style="color:#f1f5f9">
                <i class="fas fa-file-invoice-dollar text-primary-500 mr-2"></i>Hutang Supplier
            </h1>
            <p class="text-sm mt-1" style="color:#64748b">Kelola pembayaran hutang ke supplier</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4" id="summary-cards">
        <div class="card p-4 stagger-1 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(239,68,68,0.15)">
                    <i class="fas fa-exclamation-triangle" style="color:#ef4444"></i>
                </div>
                <div>
                    <p class="text-xs" style="color:#64748b">Sisa Hutang Belum Bayar</p>
                    <p class="text-lg font-bold" style="color:#ef4444" id="total-hutang">Rp 0</p>
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
                    <p class="text-xs" style="color:#64748b">Jumlah Hutang Aktif</p>
                    <p class="text-lg font-bold" style="color:#22c55e" id="jml-hutang">0</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
        <button onclick="filterHutang('')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-semua" style="background:rgba(14,165,233,0.15);color:#0ea5e9">Semua</button>
        <button onclick="filterHutang('belum')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-belum" style="background:rgba(255,255,255,0.05);color:#94a3b8">Belum Bayar</button>
        <button onclick="filterHutang('cicilan')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-cicilan" style="background:rgba(255,255,255,0.05);color:#94a3b8">Cicilan</button>
        <button onclick="filterHutang('lunas')" class="px-4 py-2 rounded-xl text-sm font-semibold transition-all whitespace-nowrap" id="tab-lunas" style="background:rgba(255,255,255,0.05);color:#94a3b8">Lunas</button>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden animate-fade-in-up stagger-2">
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Supplier</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No Faktur</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Jumlah</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Terbayar</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Sisa</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Aksi</th>
                    </tr>
                </thead>
                <tbody id="hutang-table-body">
                    <tr><td colspan="9" class="text-center py-8" style="color:#64748b">
                        <div class="skeleton h-4 w-48 mx-auto mb-2"></div>
                        <p>Memuat data...</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bayar Hutang Modal -->
<div class="modal fade" id="bayarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold"><i class="fas fa-money-bill-wave mr-2 text-accent-500"></i>Bayar Hutang</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="bayar-id">
                <div class="mb-3">
                    <label class="form-label text-sm">Supplier</label>
                    <input type="text" class="form-control" id="bayar-supplier" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Sisa Hutang</label>
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
let allHutang = [];
let currentFilter = '';

async function loadHutang() {
    const res = await apiRequest('getHutang');
    allHutang = res.data || [];
    renderHutang();
    updateSummary();
}

function renderHutang() {
    const tbody = document.getElementById('hutang-table-body');
    let filtered = currentFilter ? allHutang.filter(h => h.status === currentFilter) : allHutang;
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8" style="color:#64748b"><i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>Tidak ada data hutang</td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.map((h, i) => `
        <tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-semibold" style="color:#f1f5f9">${h.supplier_nama || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${h.no_faktur || '-'}</td>
            <td class="px-4 py-3 text-sm text-right font-semibold" style="color:#f1f5f9">${formatRupiah(h.jumlah)}</td>
            <td class="px-4 py-3 text-sm text-right" style="color:#22c55e">${formatRupiah(h.terbayar)}</td>
            <td class="px-4 py-3 text-sm text-right font-bold" style="color:${h.sisa_hutang > 0 ? '#ef4444' : '#22c55e'}">${formatRupiah(h.sisa_hutang)}</td>
            <td class="px-4 py-3 text-sm" style="color:${h.jatuh_tempo && new Date(h.jatuh_tempo) < new Date() ? '#ef4444' : '#94a3b8'}">${h.jatuh_tempo ? formatDate(h.jatuh_tempo) : '-'}</td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${h.status === 'lunas' ? 'badge-success' : h.status === 'cicilan' ? 'badge-warning' : 'badge-danger'}">
                    ${h.status === 'lunas' ? 'Lunas' : h.status === 'cicilan' ? 'Cicilan' : 'Belum'}
                </span>
            </td>
            <td class="px-4 py-3 text-center">
                ${h.status !== 'lunas' ? `
                    <button onclick="openBayar(${h.id}, '${h.supplier_nama}', ${h.sisa_hutang})" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:scale-105" style="background:rgba(34,197,94,0.15);color:#22c55e">
                        <i class="fas fa-money-bill-wave mr-1"></i>Bayar
                    </button>
                ` : '<span class="text-xs" style="color:#64748b">Selesai</span>'}
            </td>
        </tr>
    `).join('');
}

function updateSummary() {
    const belumBayar = allHutang.filter(h => h.status !== 'lunas');
    const totalSisa = belumBayar.reduce((s, h) => s + parseFloat(h.sisa_hutang || 0), 0);
    const totalBayar = allHutang.reduce((s, h) => s + parseFloat(h.terbayar || 0), 0);
    document.getElementById('total-hutang').textContent = formatRupiah(totalSisa);
    document.getElementById('total-terbayar').textContent = formatRupiah(totalBayar);
    document.getElementById('jml-hutang').textContent = belumBayar.length;
}

function filterHutang(status) {
    currentFilter = status;
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.style.background = 'rgba(255,255,255,0.05)';
        el.style.color = '#94a3b8';
    });
    const activeTab = document.getElementById('tab-' + (status || 'semua'));
    activeTab.style.background = 'rgba(14,165,233,0.15)';
    activeTab.style.color = '#0ea5e9';
    renderHutang();
}

function openBayar(id, supplier, sisa) {
    document.getElementById('bayar-id').value = id;
    document.getElementById('bayar-supplier').value = supplier;
    document.getElementById('bayar-sisa').value = formatRupiah(sisa);
    document.getElementById('bayar-jumlah').max = sisa;
    document.getElementById('bayar-jumlah').value = '';
    $('#bayarModal').modal('show');
}

async function prosesBayar() {
    const id = document.getElementById('bayar-id').value;
    const jumlah = parseFloat(document.getElementById('bayar-jumlah').value);
    if (!jumlah || jumlah <= 0) return showToast('Masukkan jumlah bayar', 'warning');
    
    await apiRequest('bayarHutang', { id: parseInt(id), jumlah }, 'POST');
    $('#bayarModal').modal('hide');
    loadHutang();
}

loadHutang();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
