<?php
$page_title = 'Transaksi Penjualan';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
session_start();

// Proses simpan transaksi — BEFORE header to avoid header() already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $no_invoice = generateInvoice('PJL');
    $tgl = date('Y-m-d');
    $id_user = $_SESSION['user_id'];
    $id_pelanggan = (int)$_POST['id_pelanggan'] ?: 'NULL';
    $total = str_replace('.', '', $_POST['total']);
    $bayar = str_replace('.', '', $_POST['bayar']);
    $kembalian = $bayar - $total;
    $items = json_decode($_POST['items_json'], true);

    if (!empty($items) && $bayar >= $total) {
        mysqli_begin_transaction($conn);
        try {
            // Insert penjualan
            $q = "INSERT INTO penjualan (no_invoice, tgl, id_pelanggan, id_user, total, bayar, kembalian) 
                  VALUES ('$no_invoice', '$tgl', $id_pelanggan, $id_user, $total, $bayar, $kembalian)";
            mysqli_query($conn, $q);
            $id_penjualan = mysqli_insert_id($conn);

            // Insert detail & update stok
            foreach ($items as $item) {
                $id_barang = (int)$item['id'];
                $qty = (int)$item['qty'];
                $harga = (float)$item['harga'];
                $subtotal = $qty * $harga;

                mysqli_query($conn, "INSERT INTO detail_penjualan (id_penjualan, id_barang, qty, harga_satuan, subtotal) 
                                    VALUES ($id_penjualan, $id_barang, $qty, $harga, $subtotal)");
                mysqli_query($conn, "UPDATE barang SET stok = stok - $qty WHERE id = $id_barang");
            }

            mysqli_commit($conn);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Transaksi berhasil! Invoice: $no_invoice"];
            echo "<script>window.location.href='../cetak/struk.php?invoice=$no_invoice';</script>";
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Keranjang kosong atau pembayaran kurang!'];
    }
    header('Location: penjualan.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Data
$q_barang = mysqli_query($conn, "SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.id_kategori=k.id WHERE b.stok > 0 ORDER BY b.nama_barang ASC");
$q_pelanggan = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="page-header">
    <h1><i class="fas fa-cart-shopping text-slate-700 mr-2"></i>Transaksi Penjualan</h1>
    <p>Buat transaksi penjualan barang ke pelanggan</p>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Left: Pilih Barang -->
    <div class="lg:col-span-3">
        <div class="content-card">
            <div class="content-card-header">
                <h2><i class="fas fa-box text-slate-600 mr-2"></i>Pilih Barang</h2>
                <input type="text" id="searchBarang" placeholder="Cari barang..." class="form-control py-2 px-3 w-48">
            </div>
            <div class="content-card-body p-0">
                <div class="table-container max-h-96 overflow-y-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="barangList">
                            <?php while($b = mysqli_fetch_assoc($q_barang)): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($b['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($b['nama_kategori'] ?? '-') ?></td>
                                <td class="font-semibold text-slate-700"><?= rupiah($b['harga_jual']) ?></td>
                                <td><span class="badge <?= $b['stok'] <= 5 ? 'badge-danger' : 'badge-success' ?>"><?= $b['stok'] ?></span></td>
                                <td class="text-center">
                                    <button onclick="tambahKeKeranjang(<?= $b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>', <?= $b['harga_jual'] ?>, <?= $b['stok'] ?>)" 
                                            class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Keranjang -->
    <div class="lg:col-span-2">
        <div class="content-card">
            <div class="content-card-header">
                <h2><i class="fas fa-cart-shopping text-amber-500 mr-2"></i>Keranjang</h2>
                <button onclick="kosongkanKeranjang()" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i> Kosongkan
                </button>
            </div>
            <div class="content-card-body">
                <!-- Customer -->
                <div class="form-group">
                    <label class="form-label">Pelanggan (opsional)</label>
                    <select id="idPelanggan" class="form-control">
                        <option value="">-- Umum --</option>
                        <?php mysqli_data_seek($q_pelanggan, 0); while($p = mysqli_fetch_assoc($q_pelanggan)): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Cart Items -->
                <div id="keranjangContainer" class="cart-container">
                    <div class="text-center text-gray-400 py-8" id="emptyCart">
                        <i class="fas fa-cart-plus text-4xl block mb-2"></i>
                        <span>Belum ada barang</span>
                    </div>
                    <div id="cartItems"></div>
                </div>

                <!-- Total & Payment -->
                <form method="POST" id="formPenjualan" onsubmit="return validasiBayar()">
                    <input type="hidden" name="simpan" value="1">
                    <input type="hidden" name="id_pelanggan" id="inputPelanggan">
                    <input type="hidden" name="total" id="inputTotal">
                    <input type="hidden" name="bayar" id="inputBayar">
                    <input type="hidden" name="items_json" id="inputItems">

                    <div class="mt-4 space-y-3">
                        <div class="flex justify-between items-center py-2 border-t-2 border-slate-300">
                            <span class="font-bold text-lg text-gray-700">Total</span>
                            <span class="font-extrabold text-2xl text-slate-700" id="displayTotal">Rp 0</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nominal Bayar</label>
                            <input type="text" id="inputBayarDisplay" class="form-control input-rupiah text-lg font-bold" 
                                   placeholder="0" required onkeyup="hitungKembalian()">
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-xl">
                            <span class="font-semibold text-gray-600">Kembalian</span>
                            <span class="font-bold text-xl text-green-600" id="displayKembalian">Rp 0</span>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-full">
                            <i class="fas fa-check-circle"></i> Proses Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ========== KERANJANG ==========
let keranjang = [];

function tambahKeKeranjang(id, nama, harga, stok) {
    const existing = keranjang.find(item => item.id === id);
    if (existing) {
        if (existing.qty >= stok) {
            showToast('Stok tidak mencukupi!', 'warning');
            return;
        }
        existing.qty++;
    } else {
        keranjang.push({ id, nama, harga, qty: 1, stok });
    }
    renderKeranjang();
}

function hapusDariKeranjang(id) {
    keranjang = keranjang.filter(item => item.id !== id);
    renderKeranjang();
}

function ubahQty(id, delta) {
    const item = keranjang.find(i => i.id === id);
    if (!item) return;
    const newQty = item.qty + delta;
    if (newQty < 1) {
        hapusDariKeranjang(id);
        return;
    }
    if (newQty > item.stok) {
        showToast('Stok tidak mencukupi!', 'warning');
        return;
    }
    item.qty = newQty;
    renderKeranjang();
}

function kosongkanKeranjang() {
    if (!confirm('Kosongkan keranjang?')) return;
    keranjang = [];
    renderKeranjang();
}

function renderKeranjang() {
    const container = document.getElementById('cartItems');
    const empty = document.getElementById('emptyCart');
    let total = 0;

    if (keranjang.length === 0) {
        container.innerHTML = '';
        empty.style.display = 'block';
        document.getElementById('displayTotal').textContent = 'Rp 0';
        document.getElementById('displayKembalian').textContent = 'Rp 0';
        return;
    }

    empty.style.display = 'none';
    let html = '';
    keranjang.forEach(item => {
        const subtotal = item.qty * item.harga;
        total += subtotal;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nama}</div>
                    <div class="cart-item-price">${formatRupiah(item.harga)} x ${item.qty}</div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="ubahQty(${item.id}, -1)" class="btn btn-sm btn-outline px-2">-</button>
                    <span class="font-bold w-6 text-center">${item.qty}</span>
                    <button onclick="ubahQty(${item.id}, 1)" class="btn btn-sm btn-outline px-2">+</button>
                    <span class="cart-item-subtotal ml-2">${formatRupiah(subtotal)}</span>
                    <button onclick="hapusDariKeranjang(${item.id})" class="text-red-400 hover:text-red-600 ml-1">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    document.getElementById('displayTotal').textContent = formatRupiah(total);
    document.getElementById('inputTotal').value = total;
    hitungKembalian();
}

function hitungKembalian() {
    const total = parseInt(document.getElementById('inputTotal').value) || 0;
    const bayar = parseRupiah(document.getElementById('inputBayarDisplay').value) || 0;
    const kembalian = bayar - total;
    document.getElementById('displayKembalian').textContent = formatRupiah(Math.max(0, kembalian));
    document.getElementById('inputBayar').value = bayar;
}

function validasiBayar() {
    if (keranjang.length === 0) {
        showToast('Keranjang masih kosong!', 'warning');
        return false;
    }
    const total = parseInt(document.getElementById('inputTotal').value) || 0;
    const bayar = parseRupiah(document.getElementById('inputBayarDisplay').value) || 0;
    if (bayar < total) {
        showToast('Pembayaran kurang!', 'error');
        return false;
    }

    // Set data form
    document.getElementById('inputPelanggan').value = document.getElementById('idPelanggan').value;
    document.getElementById('inputItems').value = JSON.stringify(keranjang.map(i => ({ id: i.id, qty: i.qty, harga: i.harga })));

    showToast('Memproses transaksi...', 'info');
    return true;
}

// Live search barang
document.getElementById('searchBarang').addEventListener('keyup', function() {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('#barangList tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
