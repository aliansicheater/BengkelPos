<?php
$page_title = 'Laporan Servis';
require_once __DIR__ . '/../includes/header.php';

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-t');
$id_mekanik = (int)($_GET['id_mekanik'] ?? 0);

$where_mekanik = $id_mekanik ? "AND ds.id_mekanik = $id_mekanik" : '';

$q = mysqli_query($conn, "SELECT s.*, u.nama_lengkap, pl.nama as nama_pelanggan,
    (SELECT COUNT(*) FROM detail_servis WHERE id_servis=s.id AND tipe='jasa') as jml_jasa,
    (SELECT COUNT(*) FROM detail_servis WHERE id_servis=s.id AND tipe='barang') as jml_barang
    FROM servis s 
    LEFT JOIN users u ON s.id_user=u.id 
    LEFT JOIN pelanggan pl ON s.id_pelanggan=pl.id
    WHERE s.tgl BETWEEN '$dari' AND '$sampai'
    ORDER BY s.tgl DESC");

$q_total = mysqli_query($conn, "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as count FROM servis WHERE tgl BETWEEN '$dari' AND '$sampai'");
$total = mysqli_fetch_assoc($q_total);

$q_mekanik = mysqli_query($conn, "SELECT * FROM mekanik ORDER BY nama_mekanik ASC");
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar text-indigo-600 mr-2"></i>Laporan Servis</h1>
    <p>Rekap transaksi service motor</p>
</div>

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
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Tampilkan</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-money-bill"></i></div>
        <div class="stat-card-value"><?= rupiah($total['total']) ?></div>
        <div class="stat-card-label">Total Pendapatan Servis</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-motorcycle"></i></div>
        <div class="stat-card-value"><?= $total['count'] ?></div>
        <div class="stat-card-label">Jumlah Servis</div>
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
                        <th>Motor</th>
                        <th>Jasa</th>
                        <th>Barang</th>
                        <th>Grand Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($r['no_invoice']) ?></span></td>
                        <td><?= tglIndo($r['tgl']) ?></td>
                        <td><?= htmlspecialchars($r['nama_pelanggan'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['no_plat']) ?> - <?= htmlspecialchars($r['jenis_motor']) ?></td>
                        <td><?= rupiah($r['total_jasa']) ?></td>
                        <td><?= rupiah($r['total_barang']) ?></td>
                        <td class="font-semibold"><?= rupiah($r['grand_total']) ?></td>
                        <td>
                            <span class="badge <?= $r['status'] == 'selesai' ? 'badge-success' : 'badge-warning' ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
