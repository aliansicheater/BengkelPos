<?php
// ajax/riwayat_pelanggan.php - Return HTML riwayat transaksi pelanggan
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: text/html; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<div class="text-center py-8 text-red-400">ID tidak valid</div>'; exit; }

// Ambil data pelanggan
$q_p = mysqli_query($conn, "SELECT * FROM pelanggan WHERE id=$id");
$pelanggan = mysqli_fetch_assoc($q_p);
if (!$pelanggan) { echo '<div class="text-center py-8 text-red-400">Pelanggan tidak ditemukan</div>'; exit; }

// Penjualan
$q_jual = mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM penjualan p LEFT JOIN users u ON p.id_user=u.id WHERE p.id_pelanggan=$id ORDER BY p.tgl DESC LIMIT 20");
// Servis
$q_servis = mysqli_query($conn, "SELECT s.*, u.nama_lengkap FROM servis s LEFT JOIN users u ON s.id_user=u.id WHERE s.id_pelanggan=$id ORDER BY s.tgl DESC LIMIT 20");
?>

<!-- Header Info -->
<div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-200 dark:border-gray-700">
    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
        <?= strtoupper(substr($pelanggan['nama'], 0, 1)) ?>
    </div>
    <div>
        <h4 class="font-bold text-base"><?= htmlspecialchars($pelanggan['nama']) ?></h4>
        <div class="flex gap-3 text-xs text-gray-400 mt-0.5">
            <?php if ($pelanggan['no_telepon']): ?>
                <span><i class="fas fa-phone-alt mr-1"></i><?= htmlspecialchars($pelanggan['no_telepon']) ?></span>
            <?php endif; ?>
            <?php if ($pelanggan['alamat']): ?>
                <span><i class="fas fa-map-pin mr-1"></i><?= htmlspecialchars($pelanggan['alamat']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (mysqli_num_rows($q_jual) > 0 || mysqli_num_rows($q_servis) > 0): ?>

    <!-- Tab buttons -->
    <div class="flex gap-2 mb-4" id="riwayatTabs">
        <button onclick="switchRiwayatTab('tab-jual')" class="tab-riwayat active" data-tab="tab-jual">
            <i class="fas fa-shopping-cart mr-1"></i> Penjualan (<?= mysqli_num_rows($q_jual) ?>)
        </button>
        <button onclick="switchRiwayatTab('tab-servis')" class="tab-riwayat" data-tab="tab-servis">
            <i class="fas fa-motorcycle mr-1"></i> Servis (<?= mysqli_num_rows($q_servis) ?>)
        </button>
    </div>

    <!-- Tab Content: Penjualan -->
    <div class="riwayat-tab-content" id="tab-jual" style="display:block">
        <?php if (mysqli_num_rows($q_jual) > 0): ?>
            <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                <?php while($r = mysqli_fetch_assoc($q_jual)): ?>
                <div class="riwayat-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-mono bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 px-2 py-0.5 rounded font-semibold"><?= htmlspecialchars($r['no_invoice']) ?></span>
                        </div>
                        <span class="text-[10px] text-gray-400"><?= tglIndo($r['tgl']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400">Kasir: <?= htmlspecialchars($r['nama_lengkap']) ?></span>
                        <span class="font-bold text-sm text-indigo-600 dark:text-indigo-400"><?= rupiah($r['total']) ?></span>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] text-gray-400"><i class="fas fa-credit-card mr-0.5"></i>Bayar <?= rupiah($r['bayar']) ?></span>
                        <span class="text-[10px] text-green-500"><i class="fas fa-arrow-left mr-0.5"></i>Kembali <?= rupiah($r['kembalian']) ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-400 text-sm">
                <i class="fas fa-receipt text-2xl mb-2 block text-gray-300"></i>
                Belum ada transaksi penjualan
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab Content: Servis -->
    <div class="riwayat-tab-content" id="tab-servis" style="display:none">
        <?php if (mysqli_num_rows($q_servis) > 0): ?>
            <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                <?php while($r = mysqli_fetch_assoc($q_servis)): ?>
                <div class="riwayat-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-mono bg-teal-100 dark:bg-teal-900/40 text-teal-600 dark:text-teal-300 px-2 py-0.5 rounded font-semibold"><?= htmlspecialchars($r['no_invoice']) ?></span>
                            <span class="text-[10px] text-gray-400"><?= htmlspecialchars($r['no_plat']) ?></span>
                        </div>
                        <span class="text-[10px] text-gray-400"><?= tglIndo($r['tgl']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex gap-2 text-xs text-gray-400">
                            <span><?= htmlspecialchars($r['jenis_motor']) ?></span>
                            <span>•</span>
                            <span>Keluhan: <?= htmlspecialchars($r['keluhan'] ?? '-') ?></span>
                        </div>
                        <span class="font-bold text-sm text-teal-600 dark:text-teal-400"><?= rupiah($r['grand_total']) ?></span>
                    </div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] text-gray-400"><i class="fas fa-credit-card mr-0.5"></i>Bayar <?= rupiah($r['bayar']) ?></span>
                        <span class="text-[10px] text-green-500"><i class="fas fa-arrow-left mr-0.5"></i>Kembali <?= rupiah($r['kembalian']) ?></span>
                        <span class="text-[10px] badge badge-success"><?= ucfirst($r['status']) ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-400 text-sm">
                <i class="fas fa-tools text-2xl mb-2 block text-gray-300"></i>
                Belum ada riwayat servis
            </div>
        <?php endif; ?>
    </div>

    <style>
    .tab-riwayat {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #6b7280;
        transition: all 0.2s;
    }
    .tab-riwayat:hover { background: #eef2ff; color: #4f46e5; }
    .tab-riwayat.active {
        background: #4f46e5;
        color: white;
        border-color: #4f46e5;
    }
    .riwayat-card {
        padding: 10px 12px;
        border-radius: 10px;
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        transition: all 0.15s;
    }
    .riwayat-card:hover {
        background: #f3f4f6;
        border-color: #e5e7eb;
    }
    body.dark-mode .tab-riwayat {
        background: #1f2937;
        border-color: #374151;
        color: #9ca3af;
    }
    body.dark-mode .tab-riwayat:hover { background: #1e1b4b; color: #818cf8; }
    body.dark-mode .tab-riwayat.active { background: #4f46e5; color: white; border-color: #4f46e5; }
    body.dark-mode .riwayat-card {
        background: #1f2937;
        border-color: #374151;
    }
    body.dark-mode .riwayat-card:hover {
        background: #111827;
        border-color: #4f46e5;
    }
    </style>

<?php else: ?>
    <div class="text-center py-12">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 dark:bg-gray-800 flex items-center justify-center mb-4">
            <i class="fas fa-clock-rotate text-2xl text-indigo-300"></i>
        </div>
        <h4 class="font-semibold text-gray-400 mb-1">Belum Ada Riwayat</h4>
        <p class="text-xs text-gray-400">Pelanggan ini belum melakukan transaksi apapun</p>
    </div>
<?php endif; ?>
