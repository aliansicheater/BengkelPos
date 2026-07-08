<?php
$page_title = 'Kategori Barang';
require_once __DIR__ . '/../config/database.php';

// Proses Simpan / Edit / Hapus — BEFORE header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id) {
        mysqli_query($conn, "DELETE FROM kategori WHERE id=$id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Kategori dihapus!'];
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_kategori'] ?? '');

        if ($aksi === 'tambah') {
            mysqli_query($conn, "INSERT INTO kategori (nama_kategori) VALUES ('$nama')");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Kategori ditambahkan!'];
        } elseif ($id) {
            mysqli_query($conn, "UPDATE kategori SET nama_kategori='$nama' WHERE id=$id");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Kategori diupdate!'];
        }
    }
    header('Location: kategori.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$q = mysqli_query($conn, "SELECT k.*, (SELECT COUNT(*) FROM barang WHERE id_kategori=k.id) as jml_barang FROM kategori k ORDER BY nama_kategori ASC");
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

// Stats
$total_kategori = mysqli_num_rows($q);
$max_barang = 0;
$kategori_list = [];
while($r = mysqli_fetch_assoc($q)) { 
    $kategori_list[] = $r; 
    if ($r['jml_barang'] > $max_barang) $max_barang = $r['jml_barang'];
}
$q_barang_all = mysqli_query($conn, "SELECT COUNT(*) as c FROM barang");
$total_barang_all = mysqli_fetch_assoc($q_barang_all)['c'] ?? 0;
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-tags text-slate-700 mr-2"></i>Kategori Barang</h1>
        <p>Kelola kelompok / jenis barang untuk memudahkan pencarian</p>
    </div>
    <button onclick="openModal('modalKategori')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Kategori
    </button>
</div>

<!-- Stats Row -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    <div class="stat-card stat-card-slate">
        <div class="stat-card-icon"><i class="fas fa-tags"></i></div>
        <div class="stat-card-value"><?= $total_kategori ?></div>
        <div class="stat-card-label">Total Kategori</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-box"></i></div>
        <div class="stat-card-value"><?= $total_barang_all ?></div>
        <div class="stat-card-label">Total Barang</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-layer-group"></i></div>
        <div class="stat-card-value"><?= $total_kategori > 0 ? round($total_barang_all / $total_kategori, 1) : 0 ?></div>
        <div class="stat-card-label">Rata-rata Barang/Kategori</div>
    </div>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> animate-slide-down"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="content-card">
    <div class="content-card-header">
        <span class="text-sm text-gray-400">Total: <strong><?= $total_kategori ?></strong> kategori</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:45px">#</th>
                        <th>Nama Kategori</th>
                        <th>Jumlah Barang</th>
                        <th>Distribusi</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kategori_list) > 0): $no=1; ?>
                        <?php foreach($kategori_list as $r): ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $no++ ?></td>
                            <td class="font-medium">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-100 to-orange-50 flex items-center justify-center text-amber-500 text-xs">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <?= htmlspecialchars($r['nama_kategori']) ?>
                                </div>
                            </td>
                            <td><span class="badge badge-info"><?= $r['jml_barang'] ?> barang</span></td>
                            <td style="min-width:120px">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 rounded-full bg-gray-200 overflow-hidden">
                                        <div class="h-full rounded-full bg-slate-600" 
                                             style="width: <?= $max_barang > 0 ? max(5, ($r['jml_barang'] / $max_barang) * 100) : 0 ?>%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400"><?= $r['jml_barang'] ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="flex gap-1 justify-center">
                                    <button onclick="editKategori(<?= $r['id'] ?>, '<?= htmlspecialchars($r['nama_kategori'], ENT_QUOTES) ?>')" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus kategori <?= htmlspecialchars($r['nama_kategori']) ?>?')">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-16">
                            <div class="inline-flex flex-col items-center">
                                <div class="w-20 h-20 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                                    <i class="fas fa-tags text-3xl text-amber-300"></i>
                                </div>
                                <h3 class="font-semibold text-gray-400 mb-1">Belum Ada Kategori</h3>
                                <p class="text-sm text-gray-400 mb-4">Buat kategori untuk mengelompokkan barang</p>
                                <button onclick="openModal('modalKategori')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Kategori
                                </button>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="modalKategori">
    <div class="modal-backdrop" onclick="closeModal('modalKategori')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle text-slate-600 mr-2"></i>Tambah Kategori</h3>
            <button class="modal-close" onclick="closeModal('modalKategori')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="formId" value="0">
                <div class="form-group">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="nama_kategori" id="formNama" class="form-control" required placeholder="Contoh: Oli & Pelumas">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalKategori')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editKategori(id, nama) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-amber-500 mr-2"></i>Edit Kategori';
    document.getElementById('formAksi').value = 'edit';
    document.getElementById('formId').value = id;
    document.getElementById('formNama').value = nama;
    openModal('modalKategori');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
