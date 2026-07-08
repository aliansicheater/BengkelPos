<?php
// ajax/detail_transaksi.php - Return HTML detail of a transaction
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: text/html; charset=utf-8');

$tipe = $_GET['tipe'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$id) { echo '<div class="text-center py-8 text-red-400">ID tidak valid</div>'; exit; }

if ($tipe === 'Penjualan') {
    $q = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, pl.nama as nama_pelanggan 
        FROM penjualan p 
        LEFT JOIN users u ON p.id_user=u.id 
        LEFT JOIN pelanggan pl ON p.id_pelanggan=pl.id
        WHERE p.id=$id");
    $data = mysqli_fetch_assoc($q);
    if (!$data) { echo '<div class="text-center py-8 text-red-400">Data tidak ditemukan</div>'; exit; }
    
    $q_detail = mysqli_query($conn, "SELECT dp.*, b.nama_barang FROM detail_penjualan dp LEFT JOIN barang b ON dp.id_barang=b.id WHERE dp.id_penjualan=$id");
    ?>
    <div class="grid grid-cols-2 gap-3 text-sm mb-4">
        <div><span class="text-gray-400">Invoice:</span> <span class="font-semibold" id="detail_invoice"><?= htmlspecialchars($data['no_invoice']) ?></span></div>
        <div><span class="text-gray-400">Tanggal:</span> <?= tglIndo($data['tgl']) ?></div>
        <div><span class="text-gray-400">Pelanggan:</span> <?= htmlspecialchars($data['nama_pelanggan'] ?? '-') ?></div>
        <div><span class="text-gray-400">Kasir:</span> <?= htmlspecialchars($data['nama_lengkap']) ?></div>
    </div>
    <table class="w-full text-sm">
        <thead><tr><th>Barang</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php $total=0; while($d = mysqli_fetch_assoc($q_detail)): $total+=$d['subtotal']; ?>
            <tr>
                <td><?= htmlspecialchars($d['nama_barang']) ?></td>
                <td><?= $d['qty'] ?></td>
                <td><?= rupiah($d['harga_satuan']) ?></td>
                <td class="font-semibold"><?= rupiah($d['subtotal']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="flex justify-between items-center mt-4 pt-3 border-t font-bold text-lg">
        <span>Total</span>
        <span class="text-indigo-600"><?= rupiah($data['total']) ?></span>
    </div>
    <div class="flex justify-between text-sm mt-1"><span class="text-gray-400">Bayar</span><span><?= rupiah($data['bayar']) ?></span></div>
    <div class="flex justify-between text-sm"><span class="text-gray-400">Kembalian</span><span class="text-green-600 font-semibold"><?= rupiah($data['kembalian']) ?></span></div>
    <?php
} elseif ($tipe === 'Servis') {
    $q = mysqli_query($conn, "SELECT s.*, u.nama_lengkap, pl.nama as nama_pelanggan 
        FROM servis s 
        LEFT JOIN users u ON s.id_user=u.id 
        LEFT JOIN pelanggan pl ON s.id_pelanggan=pl.id
        WHERE s.id=$id");
    $data = mysqli_fetch_assoc($q);
    if (!$data) { echo '<div class="text-center py-8 text-red-400">Data tidak ditemukan</div>'; exit; }
    
    $q_detail = mysqli_query($conn, "SELECT ds.*, js.nama_jasa, b.nama_barang, m.nama_mekanik 
        FROM detail_servis ds
        LEFT JOIN jasa_servis js ON ds.id_jasa=js.id
        LEFT JOIN barang b ON ds.id_barang=b.id
        LEFT JOIN mekanik m ON ds.id_mekanik=m.id
        WHERE ds.id_servis=$id");
    ?>
    <div class="grid grid-cols-2 gap-3 text-sm mb-4">
        <div><span class="text-gray-400">Invoice:</span> <span class="font-semibold" id="detail_invoice"><?= htmlspecialchars($data['no_invoice']) ?></span></div>
        <div><span class="text-gray-400">Tanggal:</span> <?= tglIndo($data['tgl']) ?></div>
        <div><span class="text-gray-400">Pelanggan:</span> <?= htmlspecialchars($data['nama_pelanggan'] ?? '-') ?></div>
        <div><span class="text-gray-400">Motor:</span> <?= htmlspecialchars($data['no_plat']) ?> - <?= htmlspecialchars($data['jenis_motor']) ?></div>
        <div><span class="text-gray-400">Keluhan:</span> <?= htmlspecialchars($data['keluhan'] ?? '-') ?></div>
        <div><span class="text-gray-400">Kasir:</span> <?= htmlspecialchars($data['nama_lengkap']) ?></div>
    </div>
    <table class="w-full text-sm">
        <thead><tr><th>Item</th><th>Mekanik</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php while($d = mysqli_fetch_assoc($q_detail)): ?>
            <tr>
                <td><?= htmlspecialchars($d['nama_jasa'] ?? $d['nama_barang'] ?? '-') ?></td>
                <td class="text-xs text-gray-400"><?= htmlspecialchars($d['nama_mekanik'] ?? '-') ?></td>
                <td><?= $d['qty'] ?></td>
                <td><?= rupiah($d['harga_satuan']) ?></td>
                <td class="font-semibold"><?= rupiah($d['subtotal']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="flex justify-between text-sm mt-3"><span class="text-gray-400">Total Jasa</span><span><?= rupiah($data['total_jasa']) ?></span></div>
    <div class="flex justify-between text-sm"><span class="text-gray-400">Total Barang</span><span><?= rupiah($data['total_barang']) ?></span></div>
    <div class="flex justify-between items-center mt-2 pt-3 border-t font-bold text-lg">
        <span>Grand Total</span>
        <span class="text-indigo-600"><?= rupiah($data['grand_total']) ?></span>
    </div>
    <div class="flex justify-between text-sm mt-1"><span class="text-gray-400">Bayar</span><span><?= rupiah($data['bayar']) ?></span></div>
    <div class="flex justify-between text-sm"><span class="text-gray-400">Kembalian</span><span class="text-green-600 font-semibold"><?= rupiah($data['kembalian']) ?></span></div>
    <?php
} else {
    echo '<div class="text-center py-8 text-red-400">Tipe tidak valid</div>';
}
