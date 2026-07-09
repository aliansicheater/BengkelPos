<?php
$page_title = 'Laporan Penjualan';
require_once __DIR__ . '/../includes/header.php';

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$id_user = $_GET['id_user'] ?? '';

// Ambil daftar kasir untuk filter
$q_kasir = mysqli_query($conn, "SELECT DISTINCT u.id, u.nama_lengkap FROM penjualan p JOIN users u ON p.id_user=u.id ORDER BY u.nama_lengkap");

// Build base query conditions
$conditions = "p.tgl BETWEEN '$dari' AND '$sampai'";
if ($id_user !== '') {
    $conditions .= " AND p.id_user = '" . intval($id_user) . "'";
}

$q = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, pl.nama as nama_pelanggan
    FROM penjualan p 
    LEFT JOIN users u ON p.id_user=u.id 
    LEFT JOIN pelanggan pl ON p.id_pelanggan=pl.id
    WHERE $conditions
    ORDER BY p.tgl DESC");

$q_total = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total, COUNT(*) as count FROM penjualan p WHERE $conditions");
$total = mysqli_fetch_assoc($q_total);
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-file-invoice text-slate-700 mr-2"></i>Laporan Penjualan</h1>
        <p>Rekap transaksi penjualan barang</p>
    </div>
    <div class="flex gap-2">
        <button onclick="exportPDF()" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Export PDF</button>
        <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Cetak</button>
    </div>
</div>

<!-- Filter -->
<div class="content-card mb-6">
    <div class="content-card-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="form-group mb-0">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control" value="<?= $dari ?>">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control" value="<?= $sampai ?>">
            </div>
            <?php if (mysqli_num_rows($q_kasir) > 0): ?>
            <div class="form-group mb-0">
                <label class="form-label">Kasir</label>
                <select name="id_user" class="form-control">
                    <option value="">Semua Kasir</option>
                    <?php mysqli_data_seek($q_kasir, 0); while($k = mysqli_fetch_assoc($q_kasir)): ?>
                    <option value="<?= $k['id'] ?>" <?= $id_user == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_lengkap']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
        </form>
    </div>
</div>

<!-- Quick Filters -->
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="?dari=<?= date('Y-m-d') ?>&sampai=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $dari == date('Y-m-d') ? 'btn-primary' : 'btn-outline' ?>">Hari Ini</a>
    <a href="?dari=<?= date('Y-m-d', strtotime('-7 days')) ?>&sampai=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $dari == date('Y-m-d', strtotime('-7 days')) ? 'btn-primary' : 'btn-outline' ?>">7 Hari</a>
    <a href="?dari=<?= date('Y-m-01') ?>&sampai=<?= date('Y-m-t') ?>" class="btn btn-sm <?= $dari == date('Y-m-01') ? 'btn-primary' : 'btn-outline' ?>">Bulan Ini</a>
    <a href="?dari=<?= date('Y-01-01') ?>&sampai=<?= date('Y-12-31') ?>" class="btn btn-sm <?= $dari == date('Y-01-01') ? 'btn-primary' : 'btn-outline' ?>">Tahun Ini</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-card stat-card-slate">
        <div class="stat-card-icon"><i class="fas fa-money-bill"></i></div>
        <div class="stat-card-value"><?= rupiah($total['total']) ?></div>
        <div class="stat-card-label">Total Penjualan</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-receipt"></i></div>
        <div class="stat-card-value"><?= $total['count'] ?></div>
        <div class="stat-card-label">Jumlah Transaksi</div>
    </div>
</div>

<div class="content-card">
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($q) > 0): ?>
                        <?php while($r = mysqli_fetch_assoc($q)): ?>
                        <tr>
                            <td><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($r['no_invoice']) ?></span></td>
                            <td><?= tglIndo($r['tgl']) ?></td>
                            <td><?= htmlspecialchars($r['nama_pelanggan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                            <td class="font-semibold"><?= rupiah($r['total']) ?></td>
                            <td><?= rupiah($r['bayar']) ?></td>
                            <td class="text-center">
                                <a href="cetak/struk.php?invoice=<?= $r['no_invoice'] ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-12 text-gray-400"><i class="fas fa-inbox text-4xl block mb-3"></i>Tidak ada data penjualan</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/BengkelPOS/assets/js/jspdf.umd.min.js"></script>
<script src="/BengkelPOS/assets/js/jspdf.plugin.autotable.min.js"></script>
<script>
function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');
    
    doc.setFontSize(18);
    doc.setTextColor(79, 70, 229);
    doc.text('BengkelPOS', 14, 20);
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text('Laporan Penjualan', 14, 28);
    doc.text('Periode: <?= tglIndo($dari) ?> - <?= tglIndo($sampai) ?>', 14, 34);
    
    // Get table data
    const rows = [];
    document.querySelectorAll('table tbody tr').forEach(tr => {
        const cols = tr.querySelectorAll('td');
        if (cols.length > 1) {
            rows.push([
                cols[0]?.textContent?.trim() || '',
                cols[1]?.textContent?.trim() || '',
                cols[2]?.textContent?.trim() || '',
                cols[3]?.textContent?.trim() || '',
                cols[4]?.textContent?.trim() || '',
                cols[5]?.textContent?.trim() || ''
            ]);
        }
    });
    
    doc.autoTable({
        head: [['Invoice', 'Tanggal', 'Pelanggan', 'Kasir', 'Total', 'Bayar']],
        body: rows,
        startY: 40,
        styles: { fontSize: 8 },
        headStyles: { fillColor: [79, 70, 229] },
        theme: 'grid'
    });
    
    doc.save('laporan_penjualan_<?= date('Ymd') ?>.pdf');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
