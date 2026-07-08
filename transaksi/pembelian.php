<?php
$page_title = 'Pembelian / Restok';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $no_po = generateInvoice('PO');
    $tgl = date('Y-m-d');
    $id_user = $_SESSION['user_id'];
    $id_supplier = (int)$_POST['id_supplier'] ?: 'NULL';
    $total = str_replace('.', '', $_POST['total']);
    $items = json_decode($_POST['items_json'], true);

    if (!empty($items)) {
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "INSERT INTO pembelian (no_po, tgl, id_user, id_supplier, total) 
                                 VALUES ('$no_po', '$tgl', $id_user, $id_supplier, $total)");
            $id_pembelian = mysqli_insert_id($conn);

            foreach ($items as $item) {
                $id_barang = (int)$item['id'];
                $qty = (int)$item['qty'];
                $harga = (float)$item['harga'];
                $subtotal = $qty * $harga;

                mysqli_query($conn, "INSERT INTO detail_pembelian (id_pembelian, id_barang, qty, harga_satuan, subtotal) 
                                     VALUES ($id_pembelian, $id_barang, $qty, $harga, $subtotal)");
                mysqli_query($conn, "UPDATE barang SET stok = stok + $qty WHERE id = $id_barang");
            }

            mysqli_commit($conn);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Pembelian berhasil! No. PO: $no_po"];
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Item kosong!'];
    }
    header('Location: pembelian.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$q_barang = mysqli_query($conn, "SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.id_kategori=k.id ORDER BY b.nama_barang ASC");
$q_supplier = mysqli_query($conn, "SELECT * FROM supplier ORDER BY nama_supplier ASC");
$q_riwayat = mysqli_query($conn, "SELECT p.*, s.nama_supplier FROM pembelian p LEFT JOIN supplier s ON p.id_supplier=s.id ORDER BY p.created_at DESC LIMIT 10");

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="page-header">
    <h1><i class="fas fa-truck-loading text-indigo-600 mr-2"></i>Pembelian / Restok Barang</h1>
    <p>Catat pembelian barang dari supplier dan update stok</p>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Left -->
    <div class="lg:col-span-3">
        <div class="content-card">
            <div class="content-card-header">
                <h2><i class="fas fa-box text-indigo-500 mr-2"></i>Pilih Barang</h2>
                <input type="text" id="searchBarang" placeholder="Cari barang..." class="form-control py-2 px-3 w-48">
            </div>
            <div class="content-card-body p-0">
                <div class="table-container max-h-96 overflow-y-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Stok Saat Ini</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="barangList">
                            <?php while($b = mysqli_fetch_assoc($q_barang)): ?>
                            <tr>
                                <td><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($b['kode_barang']) ?></span></td>
                                <td class="font-medium"><?= htmlspecialchars($b['nama_barang']) ?></td>
                                <td><?= htmlspecialchars($b['nama_kategori'] ?? '-') ?></td>
                                <td><span class="badge <?= $b['stok'] <= 5 ? 'badge-danger' : 'badge-success' ?>"><?= $b['stok'] ?></span></td>
                                <td class="text-center">
                                    <button onclick="tambahKeKeranjang(<?= $b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>', <?= $b['harga_beli'] ?>)" 
                                            class="btn btn-sm btn-primary"><i class="fas fa-plus"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right -->
    <div class="lg:col-span-2">
        <div class="content-card">
            <div class="content-card-header">
                <h2><i class="fas fa-cart-plus text-amber-500 mr-2"></i>Keranjang Pembelian</h2>
                <button onclick="kosongkanKeranjang()" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Kosongkan</button>
            </div>
            <div class="content-card-body">
                <div class="form-group">
                    <label class="form-label">Supplier (opsional)</label>
                    <select id="idSupplier" class="form-control">
                        <option value="">-- Pilih Supplier --</option>
                        <?php while($s = mysqli_fetch_assoc($q_supplier)): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_supplier']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div id="keranjangContainer" class="cart-container">
                    <div class="text-center text-gray-400 py-8" id="emptyCart">
                        <i class="fas fa-cart-plus text-4xl block mb-2"></i>
                        <span>Belum ada barang</span>
                    </div>
                    <div id="cartItems"></div>
                </div>

                <form method="POST" id="formPembelian" onsubmit="return validasi()">
                    <input type="hidden" name="simpan" value="1">
                    <input type="hidden" name="id_supplier" id="inputSupplier">
                    <input type="hidden" name="total" id="inputTotal">
                    <input type="hidden" name="items_json" id="inputItems">

                    <div class="mt-4 space-y-3">
                        <div class="flex justify-between items-center py-2 border-t-2 border-indigo-200">
                            <span class="font-bold text-lg">Total</span>
                            <span class="font-extrabold text-2xl text-indigo-600" id="displayTotal">Rp 0</span>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-full">
                            <i class="fas fa-check-circle"></i> Proses Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="content-card mt-4">
            <div class="content-card-header">
                <h2><i class="fas fa-history text-gray-500 mr-2"></i>Riwayat Pembelian</h2>
            </div>
            <div class="content-card-body p-0">
                <div class="table-container max-h-60">
                    <table class="text-sm">
                        <thead>
                            <tr>
                                <th>No. PO</th>
                                <th>Supplier</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($r = mysqli_fetch_assoc($q_riwayat)): ?>
                            <tr>
                                <td class="font-mono text-xs"><?= htmlspecialchars($r['no_po']) ?></td>
                                <td><?= htmlspecialchars($r['nama_supplier'] ?? '-') ?></td>
                                <td><?= rupiah($r['total']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let keranjang = [];

function tambahKeKeranjang(id, nama, harga) {
    const existing = keranjang.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        keranjang.push({ id, nama, harga, qty: 1 });
    }
    renderKeranjang();
}

function hapusDariKeranjang(idx) {
    keranjang.splice(idx, 1);
    renderKeranjang();
}

function ubahQty(idx, delta) {
    if (keranjang[idx].qty + delta < 1) { hapusDariKeranjang(idx); return; }
    keranjang[idx].qty += delta;
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
        container.innerHTML = ''; empty.style.display = 'block';
        document.getElementById('displayTotal').textContent = 'Rp 0';
        return;
    }
    empty.style.display = 'none';
    let html = '';
    keranjang.forEach((item, idx) => {
        const subtotal = item.qty * item.harga;
        total += subtotal;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nama}</div>
                    <div class="cart-item-price">${formatRupiah(item.harga)} x ${item.qty}</div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="ubahQty(${idx}, -1)" class="btn btn-sm btn-outline px-2">-</button>
                    <span class="font-bold w-6 text-center">${item.qty}</span>
                    <button onclick="ubahQty(${idx}, 1)" class="btn btn-sm btn-outline px-2">+</button>
                    <span class="cart-item-subtotal ml-2">${formatRupiah(subtotal)}</span>
                    <button onclick="hapusDariKeranjang(${idx})" class="text-red-400 hover:text-red-600 ml-1"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    document.getElementById('displayTotal').textContent = formatRupiah(total);
    document.getElementById('inputTotal').value = total;
}

function validasi() {
    if (keranjang.length === 0) { showToast('Keranjang kosong!', 'warning'); return false; }
    document.getElementById('inputSupplier').value = document.getElementById('idSupplier').value;
    document.getElementById('inputItems').value = JSON.stringify(keranjang);
    return true;
}

document.getElementById('searchBarang').addEventListener('keyup', function() {
    const kw = this.value.toLowerCase();
    document.querySelectorAll('#barangList tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(kw) ? '' : 'none');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
