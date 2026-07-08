<?php
// cetak/struk.php - Cetak Struk Penjualan & Servis
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$invoice = mysqli_real_escape_string($conn, $_GET['invoice'] ?? '');
$tipe = $_GET['tipe'] ?? 'penjualan';

if (!$invoice) die('No invoice');

// Cari data
if ($tipe === 'servis') {
    $q = mysqli_query($conn, "SELECT s.*, u.nama_lengkap, pl.nama as nama_pelanggan 
        FROM servis s 
        LEFT JOIN users u ON s.id_user=u.id 
        LEFT JOIN pelanggan pl ON s.id_pelanggan=pl.id
        WHERE s.no_invoice='$invoice'");
    $data = mysqli_fetch_assoc($q);
    if (!$data) die('Data tidak ditemukan');

    $q_detail = mysqli_query($conn, "SELECT ds.*, js.nama_jasa, b.nama_barang, m.nama_mekanik
        FROM detail_servis ds
        LEFT JOIN jasa_servis js ON ds.id_jasa=js.id
        LEFT JOIN barang b ON ds.id_barang=b.id
        LEFT JOIN mekanik m ON ds.id_mekanik=m.id
        WHERE ds.id_servis={$data['id']}");
} else {
    $q = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, pl.nama as nama_pelanggan 
        FROM penjualan p 
        LEFT JOIN users u ON p.id_user=u.id 
        LEFT JOIN pelanggan pl ON p.id_pelanggan=pl.id
        WHERE p.no_invoice='$invoice'");
    $data = mysqli_fetch_assoc($q);
    if (!$data) die('Data tidak ditemukan');

    $q_detail = mysqli_query($conn, "SELECT dp.*, b.nama_barang 
        FROM detail_penjualan dp
        LEFT JOIN barang b ON dp.id_barang=b.id
        WHERE dp.id_penjualan={$data['id']}");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?= htmlspecialchars($invoice) ?></title>
    <link rel="stylesheet" href="/BengkelPOS/assets/css/inter.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
            background: #F1F5F9;
        }
        .struk {
            max-width: 380px;
            width: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            padding: 2rem 1.5rem;
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #E2E8F0;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .header h1 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1E293B;
        }
        .header h1 span { color: #1E293B; }
        .header p {
            font-size: 0.75rem;
            color: #64748B;
            margin-top: 0.25rem;
        }
        .info {
            font-size: 0.813rem;
            color: #475569;
            margin-bottom: 1rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }
        .info-label { color: #94A3B8; }
        .items {
            border-top: 1px solid #E2E8F0;
            border-bottom: 1px solid #E2E8F0;
            padding: 0.75rem 0;
            margin-bottom: 0.75rem;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0;
            font-size: 0.813rem;
        }
        .item-left { flex: 1; }
        .item-name { font-weight: 600; color: #1E293B; }
        .item-meta { font-size: 0.688rem; color: #94A3B8; }
        .item-price { font-weight: 700; color: #1E293B; white-space: nowrap; }
        .total-section {
            text-align: right;
            margin-bottom: 1rem;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.813rem;
            color: #475569;
            padding: 0.125rem 0;
        }
        .grand-total {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1E293B;
            border-top: 2px solid #1E293B;
            padding-top: 0.5rem;
            margin-top: 0.25rem;
            display: flex;
            justify-content: space-between;
        }
        .footer {
            text-align: center;
            font-size: 0.75rem;
            color: #94A3B8;
            border-top: 2px dashed #E2E8F0;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .btn-print {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background: #1E293B;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            margin-top: 1rem;
            font-family: 'Inter', sans-serif;
        }
        .btn-print:hover { background: #4338CA; }
        @media print {
            body { background: white; padding: 0; }
            .struk { box-shadow: none; border-radius: 0; padding: 1rem; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="struk">
        <div class="header">
            <h1>Bengkel<span>POS</span></h1>
            <p>Sistem Manajemen Bengkel Profesional</p>
        </div>

        <div class="info">
            <div class="info-row">
                <span class="info-label">No. Invoice</span>
                <span class="font-semibold"><?= htmlspecialchars($data['no_invoice']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal</span>
                <span><?= tglIndo($data['tgl']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Kasir</span>
                <span><?= htmlspecialchars($data['nama_lengkap']) ?></span>
            </div>
            <?php if ($data['nama_pelanggan']): ?>
            <div class="info-row">
                <span class="info-label">Pelanggan</span>
                <span><?= htmlspecialchars($data['nama_pelanggan']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($tipe === 'servis'): ?>
            <div class="info-row">
                <span class="info-label">Motor</span>
                <span><?= htmlspecialchars($data['no_plat']) ?> - <?= htmlspecialchars($data['jenis_motor']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="items">
            <?php while($d = mysqli_fetch_assoc($q_detail)): ?>
            <div class="item">
                <div class="item-left">
                    <div class="item-name">
                        <?php if ($tipe === 'servis' && $d['tipe'] === 'jasa'): ?>
                            <?= htmlspecialchars($d['nama_jasa']) ?>
                            <?php if ($d['nama_mekanik']): ?>
                                <br><span class="item-meta">Mekanik: <?= htmlspecialchars($d['nama_mekanik']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= htmlspecialchars($d['nama_barang'] ?? '-') ?>
                        <?php endif; ?>
                    </div>
                    <div class="item-meta"><?= $d['qty'] ?> x <?= rupiah($d['harga_satuan']) ?></div>
                </div>
                <div class="item-price"><?= rupiah($d['subtotal']) ?></div>
            </div>
            <?php endwhile; ?>
        </div>

        <?php if ($tipe === 'servis'): ?>
        <div class="total-section">
            <div class="total-row">
                <span>Total Jasa</span>
                <span><?= rupiah($data['total_jasa']) ?></span>
            </div>
            <div class="total-row">
                <span>Total Barang</span>
                <span><?= rupiah($data['total_barang']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grand-total">
            <span>Grand Total</span>
            <span><?= rupiah($tipe === 'servis' ? $data['grand_total'] : $data['total']) ?></span>
        </div>

        <div class="total-row" style="margin-top: 0.5rem;">
            <span>Bayar</span>
            <span><?= rupiah($data['bayar']) ?></span>
        </div>
        <div class="total-row" style="font-weight: 700; color: #059669; font-size: 0.938rem;">
            <span>Kembalian</span>
            <span><?= rupiah($data['kembalian']) ?></span>
        </div>

        <div class="footer">
            <p>Terima kasih telah menggunakan layanan kami</p>
            <p style="margin-top: 0.25rem;">Semoga puas dengan pelayanan BengkelPOS</p>
        </div>

        <button class="btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Struk
        </button>
    </div>
    <link rel="stylesheet" href="/BengkelPOS/assets/css/all.min.css">
</body>
</html>
