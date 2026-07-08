<?php
$page_title = 'Laporan Stok';
require_once __DIR__ . '/../includes/header.php';

$filter = $_GET['filter'] ?? 'semua';

if ($filter === 'menipis') {
    $q = mysqli_query($conn, "SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.id_kategori=k.id WHERE b.stok <= b.stok_minimal ORDER BY (b.stok_minimal - b.stok) DESC");
} else {
    $q = mysqli_query($conn, "SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.id_kategori=k.id ORDER BY b.nama_barang ASC");
}

$q_total = mysqli_query($conn, "SELECT COUNT(*) as total, COALESCE(SUM(stok * harga_beli),0) as total_modal, COALESCE(SUM(stok * harga_jual),0) as total_jual FROM barang");
$total = mysqli_fetch_assoc($q_total);

$q_stok_menipis = mysqli_query($conn, "SELECT COUNT(*) as count FROM barang WHERE stok <= stok_minimal");
$stok_menipis = mysqli_fetch_assoc($q_stok_menipis);
?>

<div class="page-header">
    <h1><i class="fas fa-warehouse text-slate-700 mr-2"></i>Laporan Stok Barang</h1>
    <p>Informasi stok dan nilai barang</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-card stat-card-slate">
        <div class="stat-card-icon"><i class="fas fa-box"></i></div>
        <div class="stat-card-value"><?= $total['total'] ?></div>
        <div class="stat-card-label">Total Item Barang</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-card-value"><?= rupiah($total['total_modal']) ?></div>
        <div class="stat-card-label">Nilai Modal Stok</div>
    </div>
    <div class="stat-card stat-card-rose">
        <div class="stat-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-card-value"><?= $stok_menipis['count'] ?></div>
        <div class="stat-card-label">Stok Menipis</div>
    </div>
</div>

<div class="content-card">
    <div class="content-card-header">
        <div class="flex gap-2">
            <a href="?filter=semua" class="btn btn-sm <?= $filter == 'semua' ? 'btn-primary' : 'btn-outline' ?>">Semua</a>
            <a href="?filter=menipis" class="btn btn-sm <?= $filter == 'menipis' ? 'btn-warning' : 'btn-outline' ?>">Stok Menipis</a>
        </div>
        <button onclick="window.print()" class="btn btn-sm btn-outline"><i class="fas fa-print"></i></button>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Minimal</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Nilai Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($b = mysqli_fetch_assoc($q)): 
                        $nilai_stok = $b['stok'] * $b['harga_beli'];
                        $status_stok = $b['stok'] <= $b['stok_minimal'] ? 'bg-red-50' : '';
                    ?>
                    <tr class="<?= $status_stok ?>">
                        <td><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($b['kode_barang']) ?></span></td>
                        <td class="font-medium"><?= htmlspecialchars($b['nama_barang']) ?></td>
                        <td><?= htmlspecialchars($b['nama_kategori'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= $b['stok'] <= $b['stok_minimal'] ? 'badge-danger' : 'badge-success' ?>">
                                <?= $b['stok'] ?>
                            </span>
                        </td>
                        <td><?= $b['stok_minimal'] ?></td>
                        <td><?= rupiah($b['harga_beli']) ?></td>
                        <td class="font-semibold text-slate-700"><?= rupiah($b['harga_jual']) ?></td>
                        <td><?= rupiah($nilai_stok) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
