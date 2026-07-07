<?php
/**
 * Cetak Struk Thermal — Bengkel Pro V1
 * ?id=X&type=penjualan|wo
 * Thermal 58mm (384 dots) / 80mm (576 dots)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$db = getDB();
$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'penjualan';

// Get bengkel info
$cfg = $db->query("SELECT * FROM app_config LIMIT 1")->fetch();
$nama_bengkel = $cfg['nama_bengkel'] ?? 'BENGKEL PRO';
$alamat = $cfg['alamat'] ?? '';
$no_telp = $cfg['no_telp'] ?? '';

if ($type === 'penjualan') {
    $trx = $db->prepare("SELECT p.*, u.nama AS kasir_nama FROM penjualan p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $trx->execute([$id]);
    $header = $trx->fetch();
    $details = $db->prepare("SELECT pd.*, b.nama AS barang_nama, b.kode_barang FROM penjualan_detail pd LEFT JOIN barang b ON pd.barang_id = b.id WHERE pd.penjualan_id = ?");
    $details->execute([$id]);
    $items = $details->fetchAll();
    $no_trx = $header['no_transaksi'] ?? '-';
    $kasir = $header['kasir_nama'] ?? '-';
} elseif ($type === 'wo') {
    $trx = $db->prepare("SELECT wo.*, plg.nama AS pelanggan_nama, mk.nama AS mekanik_nama FROM work_order wo LEFT JOIN pelanggan plg ON wo.pelanggan_id = plg.id LEFT JOIN mekanik mk ON wo.mekanik_id = mk.id WHERE wo.id = ?");
    $trx->execute([$id]);
    $header = $trx->fetch();
    // Get jasa
    $jasa_stmt = $db->prepare("SELECT wj.*, js.nama AS jasa_nama FROM work_order_jasa wj LEFT JOIN jasa_servis js ON wj.jasa_id = js.id WHERE wj.wo_id = ?");
    $jasa_stmt->execute([$id]);
    $jasa_items = $jasa_stmt->fetchAll();
    // Get sparepart
    $sp_stmt = $db->prepare("SELECT ws.*, b.nama AS barang_nama FROM work_order_sparepart ws LEFT JOIN barang b ON ws.barang_id = b.id WHERE ws.wo_id = ?");
    $sp_stmt->execute([$id]);
    $sparepart_items = $sp_stmt->fetchAll();
    $no_trx = $header['no_wo'] ?? '-';
    $kasir = $header['mekanik_nama'] ?? '-';
} else {
    die('Type tidak valid');
}

if (!$header) die('Data tidak ditemukan');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk <?= $no_trx ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 58mm; /* 58mm thermal — change to 80mm if needed */
            background: #fff;
            color: #000;
            padding: 2mm;
            line-height: 1.3;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .small { font-size: 10px; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
        .line-double { border-top: 3px double #000; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        
        @media print {
            body { width: 58mm; padding: 0; }
            @page { size: 58mm auto; margin: 0; }
        }

        .no-print {
            padding: 10px;
            text-align: center;
            background: #f1f5f9;
            border-bottom: 2px solid #0ea5e9;
            margin-bottom: 10px;
        }
        .no-print button {
            padding: 10px 24px;
            background: #0ea5e9;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            margin: 4px;
        }
        .no-print button:hover { background: #0284c7; }
    </style>
</head>
<body>
    <div class="no-print">
        <p style="font-size:14px;font-weight:bold;margin-bottom:8px">Preview Struk Thermal</p>
        <button onclick="window.print()"><i class="fas fa-print"></i> Cetak Struk</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <!-- HEADER -->
    <div class="center bold" style="font-size:14px"><?= strtoupper($nama_bengkel) ?></div>
    <div class="center small"><?= $alamat ?></div>
    <?php if ($no_telp): ?>
    <div class="center small">Telp: <?= $no_telp ?></div>
    <?php endif; ?>
    
    <div class="line-double"></div>
    
    <div class="center bold small">STRUK <?= strtoupper($type === 'penjualan' ? 'PENJUALAN' : 'SERVIS') ?></div>
    
    <div class="line"></div>
    
    <!-- INFO TRX -->
    <table>
        <tr><td class="small">No</td><td class="text-right bold"><?= $no_trx ?></td></tr>
        <tr><td class="small">Tanggal</td><td class="text-right"><?= date('d/m/Y H:i', strtotime($header['created_at'] ?? $header['tanggal'] ?? 'now')) ?></td></tr>
        <tr><td class="small">Kasir/Mekanik</td><td class="text-right"><?= $kasir ?></td></tr>
        <?php if ($type === 'penjualan' && !empty($header['metode_bayar'])): ?>
        <tr><td class="small">Bayar</td><td class="text-right"><?= strtoupper($header['metode_bayar']) ?></td></tr>
        <?php endif; ?>
        <?php if ($type === 'wo'): ?>
        <tr><td class="small">Pelanggan</td><td class="text-right"><?= $header['pelanggan_nama'] ?? '-' ?></td></tr>
        <tr><td class="small">Plat</td><td class="text-right"><?= $header['plat_nomor'] ?? '-' ?></td></tr>
        <tr><td class="small">Motor</td><td class="text-right"><?= $header['tipe_motor'] ?? '-' ?></td></tr>
        <?php endif; ?>
    </table>
    
    <div class="line"></div>
    
    <!-- ITEMS -->
    <?php if ($type === 'penjualan'): ?>
    <table>
        <tr><th class="text-left small">Barang</th><th class="text-right small">Qty</th><th class="text-right small">Harga</th><th class="text-right small">Subtotal</th></tr>
        <?php foreach ($items as $item): ?>
        <tr>
            <td class="small"><?= $item['barang_nama'] ?></td>
            <td class="text-right small"><?= $item['qty'] ?></td>
            <td class="text-right small"><?= number_format($item['harga'], 0, ',', '.') ?></td>
            <td class="text-right small"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php elseif ($type === 'wo'): ?>
    <?php if (!empty($jasa_items)): ?>
    <div class="bold small">JASA SERVIS:</div>
    <table>
        <?php foreach ($jasa_items as $j): ?>
        <tr>
            <td class="small"><?= $j['jasa_nama'] ?></td>
            <td class="text-right small"><?= number_format($j['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <?php if (!empty($sparepart_items)): ?>
    <div class="bold small">SPAREPART:</div>
    <table>
        <?php foreach ($sparepart_items as $s): ?>
        <tr>
            <td class="small"><?= $s['barang_nama'] ?> (x<?= $s['qty'] ?>)</td>
            <td class="text-right small"><?= number_format($s['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <?php endif; ?>
    
    <div class="line-double"></div>
    
    <!-- TOTALS -->
    <?php if ($type === 'penjualan'): ?>
    <table>
        <tr><td class="small">Subtotal</td><td class="text-right small"><?= number_format($header['subtotal'] ?? 0, 0, ',', '.') ?></td></tr>
        <?php if (($header['diskon'] ?? 0) > 0): ?>
        <tr><td class="small">Diskon</td><td class="text-right small">-<?= number_format($header['diskon'], 0, ',', '.') ?></td></tr>
        <?php endif; ?>
        <?php if (($header['pajak'] ?? 0) > 0): ?>
        <tr><td class="small">Pajak</td><td class="text-right small"><?= number_format($header['pajak'], 0, ',', '.') ?></td></tr>
        <?php endif; ?>
        <tr><td class="bold small">TOTAL</td><td class="text-right bold small"><?= number_format($header['grand_total'] ?? 0, 0, ',', '.') ?></td></tr>
        <tr><td class="small">Bayar</td><td class="text-right small"><?= number_format($header['bayar'] ?? 0, 0, ',', '.') ?></td></tr>
        <?php if (($header['kembali'] ?? 0) > 0): ?>
        <tr><td class="small">Kembali</td><td class="text-right small"><?= number_format($header['kembali'], 0, ',', '.') ?></td></tr>
        <?php endif; ?>
    </table>
    <?php elseif ($type === 'wo'): ?>
    <table>
        <tr><td class="small">Jasa Servis</td><td class="text-right small"><?= number_format($header['total_jasa'] ?? 0, 0, ',', '.') ?></td></tr>
        <tr><td class="small">Sparepart</td><td class="text-right small"><?= number_format($header['total_sparepart'] ?? 0, 0, ',', '.') ?></td></tr>
        <tr><td class="bold small">TOTAL</td><td class="text-right bold small"><?= number_format($header['grand_total'] ?? 0, 0, ',', '.') ?></td></tr>
        <tr><td class="small">Status Bayar</td><td class="text-right small"><?= strtoupper($header['status_bayar'] ?? 'belum') ?></td></tr>
    </table>
    <?php endif; ?>
    
    <div class="line-double"></div>
    
    <div class="center small">Terima kasih atas kunjungan Anda</div>
    <div class="center small">Barang yang sudah dibeli tidak</div>
    <div class="center small">dapat dikembalikan</div>
    
    <div class="center small" style="margin-top:8px">*** <?= $nama_bengkel ?> ***</div>

    <!-- Auto print on load -->
    <script>
    window.onload = function() {
        // Auto-print after 500ms
        setTimeout(function() { window.print(); }, 500);
    };
    </script>
</body>
</html>
