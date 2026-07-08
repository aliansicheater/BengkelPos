<?php
$page_title = 'Data Barang';
require_once __DIR__ . '/../config/database.php';

// Proses Simpan / Edit / Hapus — BEFORE header to avoid header() already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id) {
        mysqli_query($conn, "DELETE FROM barang WHERE id=$id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Barang berhasil dihapus!'];
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $kode_barang = mysqli_real_escape_string($conn, $_POST['kode_barang'] ?? '');
        $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang'] ?? '');
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        $harga_beli = str_replace('.', '', $_POST['harga_beli'] ?? '0');
        $harga_jual = str_replace('.', '', $_POST['harga_jual'] ?? '0');
        $stok = (int)($_POST['stok'] ?? 0);
        $stok_minimal = (int)($_POST['stok_minimal'] ?? 5);

        if ($aksi === 'tambah') {
            $q = "INSERT INTO barang (kode_barang, nama_barang, id_kategori, harga_beli, harga_jual, stok, stok_minimal) 
                  VALUES ('$kode_barang', '$nama_barang', $id_kategori, $harga_beli, $harga_jual, $stok, $stok_minimal)";
            mysqli_query($conn, $q);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Barang berhasil ditambahkan!'];
        } elseif ($id) {
            $q = "UPDATE barang SET 
                  kode_barang='$kode_barang', nama_barang='$nama_barang', id_kategori=$id_kategori,
                  harga_beli=$harga_beli, harga_jual=$harga_jual, stok=$stok, stok_minimal=$stok_minimal
                  WHERE id=$id";
            mysqli_query($conn, $q);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Barang berhasil diupdate!'];
        }
    }
    header('Location: barang.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Ambil data
$q_barang = mysqli_query($conn, "SELECT b.*, k.nama_kategori FROM barang b 
                                 LEFT JOIN kategori k ON b.id_kategori = k.id 
                                 ORDER BY b.nama_barang ASC");
$q_kategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Stats
$total_item = mysqli_num_rows($q_barang);
$total_stok = 0; $total_nilai = 0; $stok_menipis = 0;
$barang_list = [];
while($b = mysqli_fetch_assoc($q_barang)) { 
    $barang_list[] = $b; 
    $total_stok += $b['stok'];
    $total_nilai += $b['harga_beli'] * $b['stok'];
    if ($b['stok'] <= $b['stok_minimal']) $stok_menipis++;
}
$q_kategori->data_seek(0);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-boxes-stacked text-slate-700 mr-2"></i>Data Barang</h1>
        <p>Kelola stok, harga, dan kategori sparepart & aksesoris</p>
    </div>
    <button onclick="openModal('modalBarang')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Barang
    </button>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="stat-card stat-card-slate">
        <div class="stat-card-icon"><i class="fas fa-box"></i></div>
        <div class="stat-card-value"><?= $total_item ?></div>
        <div class="stat-card-label">Total Item</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-warehouse"></i></div>
        <div class="stat-card-value"><?= $total_stok ?></div>
        <div class="stat-card-label">Total Stok</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-coins"></i></div>
        <div class="stat-card-value"><?= rupiah($total_nilai) ?></div>
        <div class="stat-card-label">Nilai Modal</div>
    </div>
    <div class="stat-card stat-card-rose">
        <div class="stat-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-card-value"><?= $stok_menipis ?></div>
        <div class="stat-card-label">Stok Menipis</div>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> animate-slide-down">
    <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= $flash['msg'] ?>
</div>
<?php endif; ?>

<div class="content-card">
    <div class="content-card-header">
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <i class="fas fa-search text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="Cari nama/kode barang..." class="form-control py-2 px-3 w-full sm:w-64">
        </div>
        <span class="text-sm text-gray-400">Total: <strong><?= $total_item ?></strong> barang</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th style="width:45px">#</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Margin</th>
                        <th>Stok</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($barang_list) > 0): ?>
                        <?php $no = 1; foreach($barang_list as $b): 
                            $margin = $b['harga_jual'] - $b['harga_beli'];
                            $margin_persen = $b['harga_beli'] > 0 ? round(($margin / $b['harga_beli']) * 100) : 0;
                            $stok_class = $b['stok'] <= 0 ? 'danger' : ($b['stok'] <= $b['stok_minimal'] ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $no++ ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-200 to-slate-100 flex items-center justify-center text-slate-600 text-xs font-bold flex-shrink-0">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm"><?= htmlspecialchars($b['nama_barang']) ?></div>
                                        <span class="text-[10px] font-mono text-gray-400"><?= htmlspecialchars($b['kode_barang']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-info text-[10px]"><?= htmlspecialchars($b['nama_kategori'] ?? '-') ?></span></td>
                            <td class="text-gray-500 text-xs"><?= rupiah($b['harga_beli']) ?></td>
                            <td class="font-semibold text-slate-700 text-sm"><?= rupiah($b['harga_jual']) ?></td>
                            <td>
                                <span class="badge <?= $margin > 0 ? 'badge-success' : 'badge-danger' ?> text-[10px]">
                                    <?= rupiah($margin) ?> (<?= $margin_persen ?>%)
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-16 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                                        <div class="h-full rounded-full transition-all <?= $stok_class == 'danger' ? 'bg-red-500' : ($stok_class == 'warning' ? 'bg-amber-500' : 'bg-emerald-500') ?>" 
                                             style="width: <?= min(100, ($b['stok'] / max($b['stok_minimal'], 1)) * 50) ?>%"></div>
                                    </div>
                                    <span class="badge badge-<?= $stok_class ?> text-[10px] font-semibold"><?= $b['stok'] ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="flex gap-1 justify-center">
                                    <button onclick='editBarang(<?= json_encode($b) ?>)' class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus barang <?= htmlspecialchars($b['nama_barang']) ?>?')">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-16">
                                <div class="inline-flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                                        <i class="fas fa-box-open text-3xl text-slate-400"></i>
                                    </div>
                                    <h3 class="font-semibold text-gray-400 mb-1">Belum Ada Barang</h3>
                                    <p class="text-sm text-gray-400 mb-4">Klik "Tambah Barang" untuk mulai menambahkan sparepart</p>
                                    <button onclick="openModal('modalBarang')" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Tambah Barang Pertama
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Barang -->
<div class="modal" id="modalBarang">
    <div class="modal-backdrop" onclick="closeModal('modalBarang')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalBarangTitle"><i class="fas fa-plus-circle text-slate-600 mr-2"></i>Tambah Barang</h3>
            <button class="modal-close" onclick="closeModal('modalBarang')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="formId" value="0">

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">Kode Barang <span class="text-red-400">*</span></label>
                        <input type="text" name="kode_barang" id="formKode" class="form-control" required
                               placeholder="Contoh: BRG001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori <span class="text-red-400">*</span></label>
                        <select name="id_kategori" id="formKategori" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <?php while($k = mysqli_fetch_assoc($q_kategori)): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Barang <span class="text-red-400">*</span></label>
                    <input type="text" name="nama_barang" id="formNama" class="form-control" required
                           placeholder="Nama barang / sparepart">
                </div>
                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">Harga Beli (Rp) <i class="fas fa-info-circle text-gray-300 ml-1" title="Modal awal"></i></label>
                        <input type="text" name="harga_beli" id="formHargaBeli" class="form-control input-rupiah" required placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp) <span class="text-red-400">*</span></label>
                        <input type="text" name="harga_jual" id="formHargaJual" class="form-control input-rupiah" required placeholder="0">
                    </div>
                </div>
                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" name="stok" id="formStok" class="form-control" required value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stok Minimal <i class="fas fa-info-circle text-gray-300 ml-1" title="Peringatan saat stok di bawah nilai ini"></i></label>
                        <input type="number" name="stok_minimal" id="formStokMin" class="form-control" required value="5" min="1">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalBarang')">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="btnSimpanText">Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editBarang(data) {
    document.getElementById('modalBarangTitle').innerHTML = '<i class="fas fa-edit text-amber-500 mr-2"></i>Edit Barang';
    document.getElementById('formAksi').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formKode').value = data.kode_barang;
    document.getElementById('formNama').value = data.nama_barang;
    document.getElementById('formKategori').value = data.id_kategori;
    document.getElementById('formHargaBeli').value = parseInt(data.harga_beli).toLocaleString('id-ID');
    document.getElementById('formHargaJual').value = parseInt(data.harga_jual).toLocaleString('id-ID');
    document.getElementById('formStok').value = data.stok;
    document.getElementById('formStokMin').value = data.stok_minimal;
    document.getElementById('btnSimpanText').textContent = 'Update';
    openModal('modalBarang');
}

// Reset modal when closed
document.getElementById('modalBarang').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('modalBarang');
        setTimeout(resetModalBarang, 300);
    }
});

function resetModalBarang() {
    document.getElementById('modalBarangTitle').innerHTML = '<i class="fas fa-plus-circle text-slate-600 mr-2"></i>Tambah Barang';
    document.getElementById('formAksi').value = 'tambah';
    document.getElementById('formId').value = '0';
    document.getElementById('formKode').value = '';
    document.getElementById('formNama').value = '';
    document.getElementById('formKategori').value = '';
    document.getElementById('formHargaBeli').value = '';
    document.getElementById('formHargaJual').value = '';
    document.getElementById('formStok').value = '0';
    document.getElementById('formStokMin').value = '5';
    document.getElementById('btnSimpanText').textContent = 'Simpan';
}

// Live search
document.getElementById('searchInput').addEventListener('keyup', function() {
    const keyword = this.value.toLowerCase();
    document.querySelectorAll('#dataTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
