<?php
$page_title = 'Service Motor';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
session_start();

// Proses simpan transaksi servis — BEFORE header to avoid header() already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $no_invoice = generateInvoice('SRV');
    $tgl = date('Y-m-d');
    $id_user = $_SESSION['user_id'];
    $id_pelanggan = (int)$_POST['id_pelanggan'] ?: 'NULL';
    $no_plat = mysqli_real_escape_string($conn, $_POST['no_plat']);
    $jenis_motor = mysqli_real_escape_string($conn, $_POST['jenis_motor']);
    $keluhan = mysqli_real_escape_string($conn, $_POST['keluhan']);
    $total_jasa = str_replace('.', '', $_POST['total_jasa']);
    $total_barang = str_replace('.', '', $_POST['total_barang']);
    $grand_total = str_replace('.', '', $_POST['grand_total']);
    $bayar = str_replace('.', '', $_POST['bayar']);
    $kembalian = $bayar - $grand_total;
    $items = json_decode($_POST['items_json'], true);

    if (!empty($items) && $bayar >= $grand_total) {
        mysqli_begin_transaction($conn);
        try {
            $q = "INSERT INTO servis (no_invoice, tgl, id_pelanggan, id_user, no_plat, jenis_motor, keluhan, total_jasa, total_barang, grand_total, bayar, kembalian, status) 
                  VALUES ('$no_invoice', '$tgl', $id_pelanggan, $id_user, '$no_plat', '$jenis_motor', '$keluhan', $total_jasa, $total_barang, $grand_total, $bayar, $kembalian, 'selesai')";
            mysqli_query($conn, $q);
            $id_servis = mysqli_insert_id($conn);

            foreach ($items as $item) {
                $tipe = $item['tipe'];
                $id_ref = (int)$item['id'];
                $id_mekanik = (int)($item['id_mekanik'] ?? 0) ?: 'NULL';
                $qty = (int)$item['qty'];
                $harga = (float)$item['harga'];
                $subtotal = $qty * $harga;

                $col_id = ($tipe === 'jasa') ? 'id_jasa' : 'id_barang';
                mysqli_query($conn, "INSERT INTO detail_servis (id_servis, $col_id, id_mekanik, qty, harga_satuan, subtotal, tipe) 
                                    VALUES ($id_servis, $id_ref, $id_mekanik, $qty, $harga, $subtotal, '$tipe')");
                
                if ($tipe === 'barang') {
                    mysqli_query($conn, "UPDATE barang SET stok = stok - $qty WHERE id = $id_ref");
                }
            }

            mysqli_commit($conn);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Service selesai! Invoice: $no_invoice"];
            echo "<script>window.location.href='../cetak/struk.php?invoice=$no_invoice&tipe=servis';</script>";
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal: ' . $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Item kosong atau pembayaran kurang!'];
    }
    header('Location: servis.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Data
$q_jasa = mysqli_query($conn, "SELECT * FROM jasa_servis ORDER BY nama_jasa ASC");
$q_barang = mysqli_query($conn, "SELECT b.*, k.nama_kategori FROM barang b LEFT JOIN kategori k ON b.id_kategori=k.id WHERE b.stok > 0 ORDER BY b.nama_barang ASC");
$q_mekanik = mysqli_query($conn, "SELECT * FROM mekanik WHERE status='aktif' ORDER BY nama_mekanik ASC");
$q_pelanggan = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama ASC");

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="page-header">
    <h1><i class="fas fa-motorcycle text-slate-700 mr-2"></i>Service Motor</h1>
    <p>Catat service motor dengan jasa dan mekanik</p>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- Left: Form + Pilih Item -->
    <div class="lg:col-span-3">
        <!-- Data Motor -->
        <div class="content-card mb-4">
            <div class="content-card-header">
                <h2><i class="fas fa-motorcycle text-slate-600 mr-2"></i>Data Motor</h2>
            </div>
            <div class="content-card-body">
                <div class="form-row grid-cols-1 sm:grid-cols-3">
                    <div class="form-group">
                        <label class="form-label">Pelanggan (opsional)</label>
                        <select id="idPelanggan" class="form-control">
                            <option value="">-- Umum --</option>
                            <?php while($p = mysqli_fetch_assoc($q_pelanggan)): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Plat</label>
                        <input type="text" id="noPlat" class="form-control" placeholder="B 1234 ABC">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Motor</label>
                        <input type="text" id="jenisMotor" class="form-control" placeholder="Vario 125">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keluhan</label>
                    <textarea id="keluhan" class="form-control" placeholder="Deskripsi keluhan motor"></textarea>
                </div>
            </div>
        </div>

        <!-- Pilih Jasa Servis -->
        <div class="content-card mb-4">
            <div class="content-card-header">
                <h2><i class="fas fa-screwdriver-wrench text-slate-600 mr-2"></i>Pilih Jasa Servis</h2>
                <input type="text" id="searchJasa" placeholder="Cari jasa..." class="form-control py-2 px-3 w-48">
            </div>
            <div class="content-card-body p-0">
                <div class="table-container max-h-60 overflow-y-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Jasa</th>
                                <th>Upah Mekanik</th>
                                <th>Harga Jual</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="jasaList">
                            <?php while($j = mysqli_fetch_assoc($q_jasa)): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($j['nama_jasa']) ?></td>
                                <td><?= rupiah($j['upah_mekanik']) ?></td>
                                <td class="font-semibold text-slate-700"><?= rupiah($j['harga_jual']) ?></td>
                                <td class="text-center">
                                    <button onclick="tambahItem('jasa', <?= $j['id'] ?>, '<?= htmlspecialchars($j['nama_jasa'], ENT_QUOTES) ?>', <?= $j['harga_jual'] ?>, <?= $j['upah_mekanik'] ?>)" 
                                            class="btn btn-sm btn-primary"><i class="fas fa-plus"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pilih Barang -->
        <div class="content-card">
            <div class="content-card-header">
                <h2><i class="fas fa-box text-slate-600 mr-2"></i>Pilih Barang (Sparepart)</h2>
                <input type="text" id="searchBarang" placeholder="Cari barang..." class="form-control py-2 px-3 w-48">
            </div>
            <div class="content-card-body p-0">
                <div class="table-container max-h-60 overflow-y-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="barangList">
                            <?php while($b = mysqli_fetch_assoc($q_barang)): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($b['nama_barang']) ?></td>
                                <td class="font-semibold text-slate-700"><?= rupiah($b['harga_jual']) ?></td>
                                <td><span class="badge <?= $b['stok'] <= 5 ? 'badge-danger' : 'badge-success' ?>"><?= $b['stok'] ?></span></td>
                                <td class="text-center">
                                    <button onclick="tambahItem('barang', <?= $b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>', <?= $b['harga_jual'] ?>, 0, <?= $b['stok'] ?>)" 
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

    <!-- Right: Keranjang -->
    <div class="lg:col-span-2">
        <div class="content-card">
            <div class="content-card-header">
                <h2><i class="fas fa-clipboard-list text-amber-500 mr-2"></i>Item Servis</h2>
                <button onclick="kosongkanKeranjang()" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Kosongkan</button>
            </div>
            <div class="content-card-body">
                <div id="keranjangContainer" class="cart-container">
                    <div class="text-center text-gray-400 py-8" id="emptyCart">
                        <i class="fas fa-plus-circle text-4xl block mb-2"></i>
                        <span>Belum ada item</span>
                    </div>
                    <div id="cartItems"></div>
                </div>

                <form method="POST" id="formServis" onsubmit="return validasiBayar()">
                    <input type="hidden" name="simpan" value="1">
                    <input type="hidden" name="id_pelanggan" id="inputPelanggan">
                    <input type="hidden" name="no_plat" id="inputPlat">
                    <input type="hidden" name="jenis_motor" id="inputJenisMotor">
                    <input type="hidden" name="keluhan" id="inputKeluhan">
                    <input type="hidden" name="total_jasa" id="inputTotalJasa">
                    <input type="hidden" name="total_barang" id="inputTotalBarang">
                    <input type="hidden" name="grand_total" id="inputGrandTotal">
                    <input type="hidden" name="bayar" id="inputBayar">
                    <input type="hidden" name="items_json" id="inputItems">

                    <div class="mt-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span>Total Jasa</span>
                            <span class="font-semibold text-slate-700" id="displayTotalJasa">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Total Barang</span>
                            <span class="font-semibold text-slate-700" id="displayTotalBarang">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-t-2 border-slate-300">
                            <span class="font-bold text-lg">Grand Total</span>
                            <span class="font-extrabold text-2xl text-slate-700" id="displayGrandTotal">Rp 0</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nominal Bayar</label>
                            <input type="text" id="inputBayarDisplay" class="form-control input-rupiah text-lg font-bold" placeholder="0" required onkeyup="hitungKembalian()">
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-xl">
                            <span class="font-semibold">Kembalian</span>
                            <span class="font-bold text-xl text-green-600" id="displayKembalian">Rp 0</span>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-full">
                            <i class="fas fa-check-circle"></i> Selesaikan Servis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ========== KERANJANG SERVIS ==========
let keranjang = [];

function tambahItem(tipe, id, nama, harga, upah = 0, stok = 999) {
    const isJasa = tipe === 'jasa';
    
    if (!isJasa) {
        const existing = keranjang.find(item => item.tipe === 'barang' && item.id === id);
        if (existing) {
            if (existing.qty >= stok) { showToast('Stok tidak mencukupi!', 'warning'); return; }
            existing.qty++;
            renderKeranjang();
            return;
        }
    }

    // Check if same jasa already exists
    if (isJasa && keranjang.find(item => item.tipe === 'jasa' && item.id === id)) {
        showToast('Jasa sudah ditambahkan!', 'warning');
        return;
    }

    keranjang.push({ tipe, id, nama, harga, upah, qty: 1, stok });
    
    if (isJasa) {
        // Prompt for mekanik
        showMekanikPicker(id, nama);
    }
    renderKeranjang();
}

function showMekanikPicker(idJasa, namaJasa) {
    // We'll use a simple prompt approach - user can set mekanik from the cart
    showToast('Jasa ditambahkan! Pilih mekanik di daftar item.', 'info');
}

function setMekanik(idJasa, idMekanik) {
    const item = keranjang.find(i => i.tipe === 'jasa' && i.id === idJasa);
    if (item) item.id_mekanik = idMekanik;
    renderKeranjang();
}

function hapusItem(index) {
    keranjang.splice(index, 1);
    renderKeranjang();
}

function ubahQty(index, delta) {
    const item = keranjang[index];
    if (!item) return;
    const newQty = item.qty + delta;
    if (newQty < 1) { hapusItem(index); return; }
    if (item.tipe === 'barang' && newQty > item.stok) { showToast('Stok tidak cukup!', 'warning'); return; }
    item.qty = newQty;
    renderKeranjang();
}

function kosongkanKeranjang() {
    if (!confirm('Kosongkan semua item?')) return;
    keranjang = [];
    renderKeranjang();
}

function renderKeranjang() {
    const container = document.getElementById('cartItems');
    const empty = document.getElementById('emptyCart');
    let totalJasa = 0, totalBarang = 0;

    if (keranjang.length === 0) {
        container.innerHTML = '';
        empty.style.display = 'block';
        document.getElementById('displayTotalJasa').textContent = 'Rp 0';
        document.getElementById('displayTotalBarang').textContent = 'Rp 0';
        document.getElementById('displayGrandTotal').textContent = 'Rp 0';
        document.getElementById('displayKembalian').textContent = 'Rp 0';
        return;
    }

    empty.style.display = 'none';
    let html = '';
    keranjang.forEach((item, idx) => {
        const subtotal = item.qty * item.harga;
        if (item.tipe === 'jasa') totalJasa += subtotal;
        else totalBarang += subtotal;

        const badgeClass = item.tipe === 'jasa' ? 'badge-info' : 'badge-success';
        const icon = item.tipe === 'jasa' ? 'fa-screwdriver-wrench' : 'fa-box';

        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name flex items-center gap-2">
                        <span class="badge ${badgeClass}"><i class="fas ${icon}"></i></span>
                        ${item.nama}
                    </div>
                    <div class="cart-item-price">${formatRupiah(item.harga)} x ${item.qty}</div>
                    ${item.tipe === 'jasa' ? `
                        <div class="text-xs text-gray-400 mt-1">
                            Upah mekanik: ${formatRupiah(item.upah)} | 
                            Mekanik: <select onchange="setMekanik(${item.id}, this.value)" class="text-xs border rounded px-1 py-0.5">
                                <option value="">-- Pilih --</option>
                                <?php mysqli_data_seek($q_mekanik, 0); while($m = mysqli_fetch_assoc($q_mekanik)): ?>
                                <option value="<?= $m['id'] ?>" ${item.id_mekanik == <?= $m['id'] ?> ? 'selected' : ''}>${'<?= htmlspecialchars($m['nama_mekanik']) ?>'}</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    ` : ''}
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="ubahQty(${idx}, -1)" class="btn btn-sm btn-outline px-2">-</button>
                    <span class="font-bold w-6 text-center">${item.qty}</span>
                    <button onclick="ubahQty(${idx}, 1)" class="btn btn-sm btn-outline px-2">+</button>
                    <span class="cart-item-subtotal ml-2">${formatRupiah(subtotal)}</span>
                    <button onclick="hapusItem(${idx})" class="text-red-400 hover:text-red-600 ml-1">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    const grandTotal = totalJasa + totalBarang;
    document.getElementById('displayTotalJasa').textContent = formatRupiah(totalJasa);
    document.getElementById('displayTotalBarang').textContent = formatRupiah(totalBarang);
    document.getElementById('displayGrandTotal').textContent = formatRupiah(grandTotal);
    document.getElementById('inputTotalJasa').value = totalJasa;
    document.getElementById('inputTotalBarang').value = totalBarang;
    document.getElementById('inputGrandTotal').value = grandTotal;
    hitungKembalian();
}

function hitungKembalian() {
    const total = parseInt(document.getElementById('inputGrandTotal').value) || 0;
    const bayar = parseRupiah(document.getElementById('inputBayarDisplay').value) || 0;
    document.getElementById('displayKembalian').textContent = formatRupiah(Math.max(0, bayar - total));
    document.getElementById('inputBayar').value = bayar;
}

function validasiBayar() {
    if (keranjang.length === 0) {
        showToast('Belum ada item servis!', 'warning');
        return false;
    }

    // Check mekanik
    for (const item of keranjang) {
        if (item.tipe === 'jasa' && (!item.id_mekanik || item.id_mekanik == 0)) {
            showToast('Pilih mekanik untuk jasa ' + item.nama, 'warning');
            return false;
        }
    }

    const total = parseInt(document.getElementById('inputGrandTotal').value) || 0;
    const bayar = parseRupiah(document.getElementById('inputBayarDisplay').value) || 0;
    if (bayar < total) {
        showToast('Pembayaran kurang!', 'error');
        return false;
    }

    document.getElementById('inputPelanggan').value = document.getElementById('idPelanggan').value;
    document.getElementById('inputPlat').value = document.getElementById('noPlat').value;
    document.getElementById('inputJenisMotor').value = document.getElementById('jenisMotor').value;
    document.getElementById('inputKeluhan').value = document.getElementById('keluhan').value;
    document.getElementById('inputItems').value = JSON.stringify(keranjang);
    showToast('Memproses...', 'info');
    return true;
}

// Live search
document.getElementById('searchJasa')?.addEventListener('keyup', function() {
    const kw = this.value.toLowerCase();
    document.querySelectorAll('#jasaList tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(kw) ? '' : 'none');
});
document.getElementById('searchBarang')?.addEventListener('keyup', function() {
    const kw = this.value.toLowerCase();
    document.querySelectorAll('#barangList tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(kw) ? '' : 'none');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
