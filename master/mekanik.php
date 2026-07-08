<?php
$page_title = 'Data Mekanik';
require_once __DIR__ . '/../config/database.php';

// Proses Simpan / Edit / Hapus — BEFORE header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id) {
        mysqli_query($conn, "DELETE FROM mekanik WHERE id=$id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Mekanik dihapus!'];
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_mekanik'] ?? '');
        $telepon = mysqli_real_escape_string($conn, $_POST['no_telepon'] ?? '');
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'aktif');

        if ($aksi === 'tambah') {
            mysqli_query($conn, "INSERT INTO mekanik (nama_mekanik, no_telepon, alamat, status) VALUES ('$nama', '$telepon', '$alamat', '$status')");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Mekanik ditambahkan!'];
        } elseif ($id) {
            mysqli_query($conn, "UPDATE mekanik SET nama_mekanik='$nama', no_telepon='$telepon', alamat='$alamat', status='$status' WHERE id=$id");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Mekanik diupdate!'];
        }
    }
    header('Location: mekanik.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$q = mysqli_query($conn, "SELECT m.*, 
    (SELECT COUNT(*) FROM detail_servis WHERE id_mekanik=m.id) as jml_servis 
    FROM mekanik m ORDER BY m.status ASC, m.nama_mekanik ASC");
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$mekanik_list = [];
$total_aktif = 0;
while($r = mysqli_fetch_assoc($q)) { 
    $mekanik_list[] = $r; 
    if ($r['status'] === 'aktif') $total_aktif++;
}
$total_mekanik = count($mekanik_list);
$total_servis = array_sum(array_column($mekanik_list, 'jml_servis'));

$warna = ['#4F46E5', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'];
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-user-gear text-indigo-600 mr-2"></i>Data Mekanik</h1>
        <p>Kelola data mekanik dan pantau produktivitas servis</p>
    </div>
    <button onclick="openModal('modalMekanik')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Mekanik
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="stat-card stat-card-indigo">
        <div class="stat-card-icon"><i class="fas fa-user-gear"></i></div>
        <div class="stat-card-value"><?= $total_mekanik ?></div>
        <div class="stat-card-label">Total Mekanik</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-card-value"><?= $total_aktif ?></div>
        <div class="stat-card-label">Aktif</div>
    </div>
    <div class="stat-card stat-card-rose">
        <div class="stat-card-icon"><i class="fas fa-xmark-circle"></i></div>
        <div class="stat-card-value"><?= $total_mekanik - $total_aktif ?></div>
        <div class="stat-card-label">Nonaktif</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-screwdriver-wrench"></i></div>
        <div class="stat-card-value"><?= $total_servis ?></div>
        <div class="stat-card-label">Total Servis</div>
    </div>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> animate-slide-down"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="content-card">
    <div class="content-card-header">
        <span class="text-sm text-gray-400">Total: <strong><?= $total_mekanik ?></strong> mekanik</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:45px">#</th>
                        <th>Nama Mekanik</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Total Servis</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($mekanik_list) > 0): $no=1; $ci=0; ?>
                        <?php foreach($mekanik_list as $r): ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $no++ ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                         style="background: <?= $warna[$ci % count($warna)] ?>">
                                        <?= strtoupper(substr($r['nama_mekanik'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm"><?= htmlspecialchars($r['nama_mekanik']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <?php $ci++; ?>
                            <td>
                                <div class="flex flex-col gap-0.5">
                                    <?php if ($r['no_telepon']): ?>
                                        <a href="tel:<?= htmlspecialchars($r['no_telepon']) ?>" class="text-xs text-indigo-500 hover:text-indigo-700">
                                            <i class="fas fa-phone-alt mr-1" style="font-size:9px"></i><?= htmlspecialchars($r['no_telepon']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-300">-</span>
                                    <?php endif; ?>
                                    <?php if ($r['alamat']): ?>
                                        <span class="text-[10px] text-gray-400 truncate max-w-[160px]" title="<?= htmlspecialchars($r['alamat']) ?>">
                                            <i class="fas fa-map-pin mr-1"></i><?= htmlspecialchars($r['alamat']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'aktif'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-16 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                                        <div class="h-full rounded-full bg-amber-500" 
                                             style="width: <?= $total_servis > 0 ? max(5, ($r['jml_servis'] / $total_servis) * 100) : 0 ?>%"></div>
                                    </div>
                                    <span class="badge badge-info text-[10px] font-semibold"><?= $r['jml_servis'] ?>x</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex gap-1 justify-center">
                                    <button onclick='editMekanik(<?= json_encode($r) ?>)' class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus mekanik <?= htmlspecialchars($r['nama_mekanik']) ?>?')">
                                        <input type="hidden" name="aksi" value="hapus">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-16">
                            <div class="inline-flex flex-col items-center">
                                <div class="w-20 h-20 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                                    <i class="fas fa-user-gear text-3xl text-amber-300"></i>
                                </div>
                                <h3 class="font-semibold text-gray-400 mb-1">Belum Ada Mekanik</h3>
                                <p class="text-sm text-gray-400 mb-4">Tambahkan mekanik untuk pencatatan upah servis</p>
                                <button onclick="openModal('modalMekanik')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Mekanik
                                </button>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="modalMekanik">
    <div class="modal-backdrop" onclick="closeModal('modalMekanik')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle text-indigo-500 mr-2"></i>Tambah Mekanik</h3>
            <button class="modal-close" onclick="closeModal('modalMekanik')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="formId" value="0">
                <div class="form-group">
                    <label class="form-label">Nama Mekanik <span class="text-red-400">*</span></label>
                    <input type="text" name="nama_mekanik" id="formNama" class="form-control" required placeholder="Nama mekanik">
                </div>
                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telepon" id="formTelepon" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="formStatus" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" id="formAlamat" class="form-control" placeholder="Alamat mekanik"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalMekanik')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editMekanik(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-amber-500 mr-2"></i>Edit Mekanik';
    document.getElementById('formAksi').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formNama').value = data.nama_mekanik;
    document.getElementById('formTelepon').value = data.no_telepon || '';
    document.getElementById('formAlamat').value = data.alamat || '';
    document.getElementById('formStatus').value = data.status;
    openModal('modalMekanik');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
