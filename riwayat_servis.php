<?php
$pageTitle = 'Riwayat Servis';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin','kasir','mekanik']);
?>

<div class="animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold" style="color:#f1f5f9">
                <i class="fas fa-history text-primary-500 mr-2"></i>Riwayat Servis
            </h1>
            <p class="text-sm mt-1" style="color:#64748b">Riwayat servis motor pelanggan</p>
        </div>
        <div class="flex gap-2">
            <input type="text" class="form-control" id="search-wo" placeholder="Cari no WO / pelanggan..." style="width:250px" oninput="searchRiwayat()">
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden animate-fade-in-up stagger-1">
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No WO</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Pelanggan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Plat</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Tipe Motor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Mekanik</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold" style="color:#64748b">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Aksi</th>
                    </tr>
                </thead>
                <tbody id="riwayat-table-body">
                    <tr><td colspan="9" class="text-center py-8" style="color:#64748b">
                        <div class="skeleton h-4 w-48 mx-auto mb-2"></div>
                        <p>Memuat data...</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold"><i class="fas fa-wrench mr-2 text-primary-500"></i>Detail Work Order</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="detail-content">
                <div class="skeleton h-4 w-full mb-2"></div>
            </div>
        </div>
    </div>
</div>

<script>
let allRiwayat = [];

async function loadRiwayat() {
    const res = await apiRequest('getRiwayatServis');
    allRiwayat = res.data || [];
    renderRiwayat(allRiwayat);
}

function renderRiwayat(data) {
    const tbody = document.getElementById('riwayat-table-body');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8" style="color:#64748b"><i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>Tidak ada riwayat servis</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map((wo, i) => `
        <tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-bold" style="color:#0ea5e9">${wo.no_wo}</td>
            <td class="px-4 py-3 text-sm font-semibold" style="color:#f1f5f9">${wo.pelanggan_nama || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${wo.plat_nomor || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${wo.tipe_motor || '-'}</td>
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${wo.mekanik_nama || '-'}</td>
            <td class="px-4 py-3 text-sm text-right font-semibold" style="color:#f1f5f9">${formatRupiah(wo.grand_total)}</td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${wo.status === 'diambil' ? 'badge-success' : wo.status === 'selesai' ? 'badge-warning' : 'badge-info'}">
                    ${wo.status === 'diambil' ? 'Diambil' : wo.status === 'selesai' ? 'Selesai' : 'Proses'}
                </span>
            </td>
            <td class="px-4 py-3 text-center">
                <button onclick="showDetail(${wo.id})" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all hover:scale-105" style="background:rgba(14,165,233,0.15);color:#0ea5e9">
                    <i class="fas fa-eye mr-1"></i>Detail
                </button>
            </td>
        </tr>
    `).join('');
}

function searchRiwayat() {
    const q = document.getElementById('search-wo').value.toLowerCase();
    const filtered = allRiwayat.filter(wo => 
        (wo.no_wo || '').toLowerCase().includes(q) ||
        (wo.pelanggan_nama || '').toLowerCase().includes(q) ||
        (wo.plat_nomor || '').toLowerCase().includes(q)
    );
    renderRiwayat(filtered);
}

async function showDetail(id) {
    const res = await apiRequest('getWorkOrderDetail', { id });
    const wo = res.data;
    if (!wo) return;
    
    let html = `
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-xs" style="color:#64748b">No Work Order</p>
                <p class="text-sm font-bold" style="color:#0ea5e9">${wo.no_wo}</p>
            </div>
            <div>
                <p class="text-xs" style="color:#64748b">Status</p>
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${wo.status === 'diambil' ? 'badge-success' : wo.status === 'selesai' ? 'badge-warning' : 'badge-info'}">
                    ${wo.status === 'diambil' ? 'Diambil' : wo.status === 'selesai' ? 'Selesai' : 'Proses'}
                </span>
            </div>
            <div>
                <p class="text-xs" style="color:#64748b">Pelanggan</p>
                <p class="text-sm font-semibold" style="color:#f1f5f9">${wo.pelanggan_nama || '-'}</p>
            </div>
            <div>
                <p class="text-xs" style="color:#64748b">Mekanik</p>
                <p class="text-sm font-semibold" style="color:#f1f5f9">${wo.mekanik_nama || '-'}</p>
            </div>
            <div>
                <p class="text-xs" style="color:#64748b">Plat Nomor</p>
                <p class="text-sm" style="color:#f1f5f9">${wo.plat_nomor || '-'}</p>
            </div>
            <div>
                <p class="text-xs" style="color:#64748b">Tipe Motor</p>
                <p class="text-sm" style="color:#f1f5f9">${wo.tipe_motor || '-'}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-xs" style="color:#64748b">Keluhan</p>
                <p class="text-sm" style="color:#f1f5f9">${wo.keluhan || '-'}</p>
            </div>
        </div>
    `;
    
    if (wo.jasa_list && wo.jasa_list.length > 0) {
        html += `<p class="text-sm font-bold mb-2" style="color:#f1f5f9"><i class="fas fa-tools mr-1 text-primary-500"></i>Jasa Servis</p>
        <table class="table w-full mb-4"><thead><tr>
            <th class="text-left text-xs" style="color:#64748b">Jasa</th>
            <th class="text-right text-xs" style="color:#64748b">Harga</th>
        </tr></thead><tbody>`;
        wo.jasa_list.forEach(j => {
            html += `<tr><td class="text-sm" style="color:#f1f5f9">${j.jasa_nama || '-'}</td><td class="text-sm text-right" style="color:#f1f5f9">${formatRupiah(j.subtotal)}</td></tr>`;
        });
        html += `</tbody></table>`;
    }
    
    if (wo.sparepart_list && wo.sparepart_list.length > 0) {
        html += `<p class="text-sm font-bold mb-2" style="color:#f1f5f9"><i class="fas fa-cogs mr-1 text-accent-500"></i>Sparepart</p>
        <table class="table w-full mb-4"><thead><tr>
            <th class="text-left text-xs" style="color:#64748b">Barang</th>
            <th class="text-center text-xs" style="color:#64748b">Qty</th>
            <th class="text-right text-xs" style="color:#64748b">Harga</th>
            <th class="text-right text-xs" style="color:#64748b">Subtotal</th>
        </tr></thead><tbody>`;
        wo.sparepart_list.forEach(s => {
            html += `<tr><td class="text-sm" style="color:#f1f5f9">${s.barang_nama || '-'}</td><td class="text-sm text-center" style="color:#94a3b8">${s.qty}</td><td class="text-sm text-right" style="color:#94a3b8">${formatRupiah(s.harga)}</td><td class="text-sm text-right" style="color:#f1f5f9">${formatRupiah(s.subtotal)}</td></tr>`;
        });
        html += `</tbody></table>`;
    }
    
    html += `
        <div class="flex justify-between items-center pt-3" style="border-top:1px solid rgba(255,255,255,0.05)">
            <span class="text-sm font-bold" style="color:#f1f5f9">Grand Total</span>
            <span class="text-lg font-bold" style="color:#0ea5e9">${formatRupiah(wo.grand_total)}</span>
        </div>
    `;
    
    document.getElementById('detail-content').innerHTML = html;
    $('#detailModal').modal('show');
}

loadRiwayat();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
