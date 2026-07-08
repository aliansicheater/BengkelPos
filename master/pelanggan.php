<?php
$page_title = 'Data Pelanggan';
require_once __DIR__ . '/../config/database.php';

// Proses Simpan / Edit / Hapus — BEFORE header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id) {
        mysqli_query($conn, "DELETE FROM pelanggan WHERE id=$id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pelanggan dihapus!'];
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
        $telepon = mysqli_real_escape_string($conn, $_POST['no_telepon'] ?? '');
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');

        if ($aksi === 'tambah') {
            mysqli_query($conn, "INSERT INTO pelanggan (nama, no_telepon, alamat) VALUES ('$nama', '$telepon', '$alamat')");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pelanggan ditambahkan!'];
        } elseif ($id) {
            mysqli_query($conn, "UPDATE pelanggan SET nama='$nama', no_telepon='$telepon', alamat='$alamat' WHERE id=$id");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pelanggan diupdate!'];
        }
    }
    header('Location: pelanggan.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$q = mysqli_query($conn, "SELECT p.*, 
    (SELECT COUNT(*) FROM penjualan WHERE id_pelanggan=p.id) as jml_transaksi,
    (SELECT COUNT(*) FROM servis WHERE id_pelanggan=p.id) as jml_servis
    FROM pelanggan p ORDER BY p.nama ASC");
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$total_pelanggan = mysqli_num_rows($q);
$pelanggan_list = [];
while($r = mysqli_fetch_assoc($q)) $pelanggan_list[] = $r;
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-users text-slate-700 mr-2"></i>Data Pelanggan</h1>
        <p>Kelola data pelanggan dan lihat riwayat transaksi</p>
    </div>
    <button onclick="openModal('modalPelanggan')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Pelanggan
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    <div class="stat-card stat-card-slate">
        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
        <div class="stat-card-value"><?= $total_pelanggan ?></div>
        <div class="stat-card-label">Total Pelanggan</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-card-value"><?= array_sum(array_column($pelanggan_list, 'jml_transaksi')) ?></div>
        <div class="stat-card-label">Total Pembelian</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-motorcycle"></i></div>
        <div class="stat-card-value"><?= array_sum(array_column($pelanggan_list, 'jml_servis')) ?></div>
        <div class="stat-card-label">Total Servis</div>
    </div>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> animate-slide-down"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="content-card">
    <div class="content-card-header">
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <i class="fas fa-search text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="Cari pelanggan..." class="form-control py-2 px-3 w-full sm:w-64">
        </div>
        <span class="text-sm text-gray-400">Total: <strong><?= $total_pelanggan ?></strong> pelanggan</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th style="width:45px">#</th>
                        <th>Nama Pelanggan</th>
                        <th>Kontak</th>
                        <th>Transaksi</th>
                        <th>Servis</th>
                        <th>Total</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pelanggan_list) > 0): $no=1; ?>
                        <?php foreach($pelanggan_list as $r): 
                            $total_trans = $r['jml_transaksi'] + $r['jml_servis'];
                        ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $no++ ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                        <?= strtoupper(substr($r['nama'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm"><?= htmlspecialchars($r['nama']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col gap-0.5">
                                    <?php if ($r['no_telepon']): ?>
                                        <a href="tel:<?= htmlspecialchars($r['no_telepon']) ?>" class="text-xs text-slate-600 hover:text-slate-800 transition-colors">
                                            <i class="fas fa-phone-alt mr-1" style="font-size:9px"></i><?= htmlspecialchars($r['no_telepon']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-300">-</span>
                                    <?php endif; ?>
                                    <?php if ($r['alamat']): ?>
                                        <span class="text-[10px] text-gray-400 truncate max-w-[180px]" title="<?= htmlspecialchars($r['alamat']) ?>">
                                            <i class="fas fa-map-pin mr-1"></i><?= htmlspecialchars($r['alamat']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-success text-[10px]"><?= $r['jml_transaksi'] ?>x</span>
                            </td>
                            <td>
                                <span class="badge badge-info text-[10px]"><?= $r['jml_servis'] ?>x</span>
                            </td>
                            <td class="font-semibold text-xs <?= $total_trans > 0 ? 'text-slate-700' : 'text-gray-300' ?>">
                                <?= $total_trans ?> transaksi
                            </td>
                            <td>
                                <div class="flex gap-1 justify-center">
                                    <button onclick="riwayatPelanggan(<?= $r['id'] ?>)" class="btn btn-sm btn-info" title="Lihat Riwayat"><i class="fas fa-history"></i></button>
                                    <button onclick='editPelanggan(<?= json_encode($r) ?>)' class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus pelanggan <?= htmlspecialchars($r['nama']) ?>?')">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-16">
                            <div class="inline-flex flex-col items-center">
                                <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                                    <i class="fas fa-users-slash text-3xl text-slate-400"></i>
                                </div>
                                <h3 class="font-semibold text-gray-400 mb-1">Belum Ada Pelanggan</h3>
                                <p class="text-sm text-gray-400 mb-4">Tambah pelanggan untuk mencatat transaksi</p>
                                <button onclick="openModal('modalPelanggan')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Pelanggan
                                </button>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="modalRiwayat">
    <div class="modal-backdrop" onclick="closeModal('modalRiwayat')"></div>
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h3><i class="fas fa-history text-slate-600 mr-2"></i>Riwayat Transaksi</h3>
            <button class="modal-close" onclick="closeModal('modalRiwayat')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="riwayatContent">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-2xl text-slate-600"></i>
                <p class="text-sm text-gray-400 mt-2">Memuat riwayat...</p>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modalPelanggan">
    <div class="modal-backdrop" onclick="closeModal('modalPelanggan')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle text-slate-600 mr-2"></i>Tambah Pelanggan</h3>
            <button class="modal-close" onclick="closeModal('modalPelanggan')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="formId" value="0">
                <div class="form-group">
                    <label class="form-label">Nama Pelanggan <span class="text-red-400">*</span></label>
                    <input type="text" name="nama" id="formNama" class="form-control" required placeholder="Nama pelanggan">
                </div>
                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telepon" id="formTelepon" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" id="formAlamat" class="form-control" placeholder="Alamat lengkap"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalPelanggan')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function riwayatPelanggan(id) {
    document.getElementById('riwayatContent').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-slate-600"></i><p class="text-sm text-gray-400 mt-2">Memuat riwayat...</p></div>';
    openModal('modalRiwayat');
    fetch('/BengkelPOS/ajax/riwayat_pelanggan.php?id=' + id)
        .then(r => r.text())
        .then(html => {
            document.getElementById('riwayatContent').innerHTML = html;
        })
        .catch(() => document.getElementById('riwayatContent').innerHTML = '<div class="text-center py-8 text-red-400">Gagal memuat riwayat</div>');
}

function switchRiwayatTab(tabId) {
    document.querySelectorAll('#riwayatContent .riwayat-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('#riwayatContent .tab-riwayat').forEach(el => el.classList.remove('active'));
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';
    const btn = document.querySelector(`#riwayatContent .tab-riwayat[data-tab="${tabId}"]`);
    if (btn) btn.classList.add('active');
}

function editPelanggan(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-amber-500 mr-2"></i>Edit Pelanggan';
    document.getElementById('formAksi').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formNama').value = data.nama;
    document.getElementById('formTelepon').value = data.no_telepon || '';
    document.getElementById('formAlamat').value = data.alamat || '';
    openModal('modalPelanggan');
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
