<?php
$page_title = 'Histori Transaksi';
require_once __DIR__ . '/../includes/header.php';

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$tipe = $_GET['tipe'] ?? '';
$cari = mysqli_real_escape_string($conn, $_GET['cari'] ?? '');

$where = "WHERE tgl BETWEEN '$dari' AND '$sampai'";
$params = "dari=$dari&sampai=$sampai";

if ($tipe === 'penjualan') {
    $where_tipe = "";
} elseif ($tipe === 'servis') {
    $where_tipe = "";
} else {
    $where_tipe = "";
}

// Penjualan
$q_pjl = mysqli_query($conn, "SELECT p.*, 'Penjualan' as tipe_transaksi, u.nama_lengkap, pl.nama as nama_pelanggan
    FROM penjualan p 
    LEFT JOIN users u ON p.id_user=u.id 
    LEFT JOIN pelanggan pl ON p.id_pelanggan=pl.id
    $where
    ORDER BY p.tgl DESC, p.id DESC");

// Servis
$q_srv = mysqli_query($conn, "SELECT s.*, 'Servis' as tipe_transaksi, u.nama_lengkap, pl.nama as nama_pelanggan
    FROM servis s
    LEFT JOIN users u ON s.id_user=u.id 
    LEFT JOIN pelanggan pl ON s.id_pelanggan=pl.id
    $where
    ORDER BY s.tgl DESC, s.id DESC");

// Merge dan sort by date
$all_trans = [];
while($r = mysqli_fetch_assoc($q_pjl)) $all_trans[] = $r;
while($r = mysqli_fetch_assoc($q_srv)) $all_trans[] = $r;

usort($all_trans, function($a, $b) {
    return strcmp($b['tgl'].' '.$b['id'], $a['tgl'].' '.$a['id']);
});

// Filter by tipe + search
$filtered = [];
foreach ($all_trans as $t) {
    if ($tipe && $t['tipe_transaksi'] !== $tipe) continue;
    if ($cari && stripos($t['no_invoice'], $cari) === false && stripos($t['nama_pelanggan'] ?? '', $cari) === false) continue;
    $filtered[] = $t;
}

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="page-header">
    <h1><i class="fas fa-history text-slate-700 mr-2"></i>Histori Transaksi</h1>
    <p>Semua riwayat penjualan dan service motor</p>
</div>

<!-- Filter -->
<div class="content-card mb-6">
    <div class="content-card-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="form-group mb-0 min-w-[140px]">
                <label class="form-label">Dari</label>
                <input type="date" name="dari" class="form-control" value="<?= $dari ?>">
            </div>
            <div class="form-group mb-0 min-w-[140px]">
                <label class="form-label">Sampai</label>
                <input type="date" name="sampai" class="form-control" value="<?= $sampai ?>">
            </div>
            <div class="form-group mb-0 min-w-[130px]">
                <label class="form-label">Tipe</label>
                <select name="tipe" class="form-control">
                    <option value="">Semua</option>
                    <option value="Penjualan" <?= $tipe == 'Penjualan' ? 'selected' : '' ?>>Penjualan</option>
                    <option value="Servis" <?= $tipe == 'Servis' ? 'selected' : '' ?>>Servis</option>
                </select>
            </div>
            <div class="form-group mb-0 min-w-[180px]">
                <label class="form-label">Cari Invoice / Pelanggan</label>
                <input type="text" name="cari" class="form-control" value="<?= htmlspecialchars($cari) ?>" placeholder="Ketik keyword...">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            <a href="histori.php" class="btn btn-outline"><i class="fas fa-undo"></i> Reset</a>
        </form>
    </div>
</div>

<!-- Quick Filter Buttons -->
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="?dari=<?= date('Y-m-d') ?>&sampai=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $dari == date('Y-m-d') ? 'btn-primary' : 'btn-outline' ?>">
        <i class="fas fa-calendar-day"></i> Hari Ini
    </a>
    <a href="?dari=<?= date('Y-m-d', strtotime('-7 days')) ?>&sampai=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $dari == date('Y-m-d', strtotime('-7 days')) ? 'btn-primary' : 'btn-outline' ?>">
        <i class="fas fa-calendar-week"></i> 7 Hari
    </a>
    <a href="?dari=<?= date('Y-m-01') ?>&sampai=<?= date('Y-m-t') ?>" class="btn btn-sm <?= $dari == date('Y-m-01') ? 'btn-primary' : 'btn-outline' ?>">
        <i class="fas fa-calendar-alt"></i> Bulan Ini
    </a>
    <a href="?dari=<?= date('Y-m-01', strtotime('-3 months')) ?>&sampai=<?= date('Y-m-d') ?>" class="btn btn-sm <?= $dari == date('Y-m-01', strtotime('-3 months')) ? 'btn-primary' : 'btn-outline' ?>">
        <i class="fas fa-calendar"></i> 3 Bulan
    </a>
</div>

<div class="content-card">
    <div class="content-card-header">
        <span class="text-sm text-gray-500">Ditemukan: <?= count($filtered) ?> transaksi</span>
        <span class="text-xs text-gray-400">Klik baris untuk detail</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Pelanggan</th>
                        <th>Kasir</th>
                        <th>Total</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($filtered) > 0): ?>
                        <?php foreach($filtered as $t): ?>
                        <tr class="cursor-pointer hover:bg-slate-100/50" onclick="showDetail('<?= $t['tipe_transaksi'] ?>', <?= $t['id'] ?>)">
                            <td><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?= htmlspecialchars($t['no_invoice']) ?></span></td>
                            <td><?= tglIndo($t['tgl']) ?></td>
                            <td>
                                <span class="badge <?= $t['tipe_transaksi'] == 'Penjualan' ? 'badge-success' : 'badge-info' ?>">
                                    <i class="fas fa-<?= $t['tipe_transaksi'] == 'Penjualan' ? 'cart-shopping' : 'motorcycle' ?> mr-1"></i>
                                    <?= $t['tipe_transaksi'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($t['nama_pelanggan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                            <td class="font-semibold text-slate-700"><?= rupiah($t['tipe_transaksi'] == 'Penjualan' ? $t['total'] : $t['grand_total']) ?></td>
                            <td class="text-center">
                                <a href="../cetak/struk.php?invoice=<?= $t['no_invoice'] ?>&tipe=<?= strtolower($t['tipe_transaksi']) ?>" target="_blank" 
                                   class="btn btn-sm btn-outline" onclick="event.stopPropagation()">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-12 text-gray-400">
                            <i class="fas fa-inbox text-4xl block mb-3"></i>Tidak ada transaksi ditemukan
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Transaksi -->
<div class="modal" id="modalDetail">
    <div class="modal-backdrop" onclick="closeModal('modalDetail')"></div>
    <div class="modal-content" style="max-width:650px;">
        <div class="modal-header">
            <h3><i class="fas fa-receipt text-slate-600 mr-2"></i>Detail Transaksi</h3>
            <button class="modal-close" onclick="closeModal('modalDetail')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="detailContent" class="space-y-4">
                <div class="flex justify-center py-8 text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('modalDetail')">Tutup</button>
            <a href="#" id="detailCetakLink" target="_blank" class="btn btn-primary">
                <i class="fas fa-print"></i> Cetak Ulang
            </a>
        </div>
    </div>
</div>

<script>
function showDetail(tipe, id) {
    openModal('modalDetail');
    document.getElementById('detailContent').innerHTML = '<div class="flex justify-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';

    fetch('../ajax/detail_transaksi.php?tipe=' + tipe + '&id=' + id)
        .then(r => r.text())
        .then(html => {
            document.getElementById('detailContent').innerHTML = html;
            // Extract invoice for print link
            const match = html.match(/no_invoice['":]\s*([^'"]+)/);
            if (match) {
                document.getElementById('detailCetakLink').href = '../cetak/struk.php?invoice=' + match[1] + '&tipe=' + tipe.toLowerCase();
            }
        })
        .catch(() => {
            document.getElementById('detailContent').innerHTML = '<div class="text-center py-8 text-red-400"><i class="fas fa-exclamation-circle text-3xl block mb-2"></i>Gagal memuat detail</div>';
        });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
