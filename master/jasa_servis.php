<?php
$page_title = 'Jasa Servis';
require_once __DIR__ . '/../config/database.php';

// Proses Simpan / Edit / Hapus — BEFORE header to avoid header() already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id) {
        mysqli_query($conn, "DELETE FROM jasa_servis WHERE id=$id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Jasa servis dihapus!'];
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_jasa'] ?? '');
        $upah = str_replace('.', '', $_POST['upah_mekanik'] ?? '0');
        $harga = str_replace('.', '', $_POST['harga_jual'] ?? '0');
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'] ?? '');

        if ($aksi === 'tambah') {
            mysqli_query($conn, "INSERT INTO jasa_servis (nama_jasa, upah_mekanik, harga_jual, keterangan) VALUES ('$nama', $upah, $harga, '$keterangan')");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Jasa servis ditambahkan!'];
        } elseif ($id) {
            mysqli_query($conn, "UPDATE jasa_servis SET nama_jasa='$nama', upah_mekanik=$upah, harga_jual=$harga, keterangan='$keterangan' WHERE id=$id");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Jasa servis diupdate!'];
        }
    }
    header('Location: jasa_servis.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$q = mysqli_query($conn, "SELECT js.*, 
    (SELECT COUNT(*) FROM detail_servis WHERE id_jasa=js.id) as jml_digunakan 
    FROM jasa_servis js ORDER BY js.nama_jasa ASC");
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$jasa_list = []; $total_laba = 0;
while($r = mysqli_fetch_assoc($q)) { 
    $jasa_list[] = $r; 
    $total_laba += ($r['harga_jual'] - $r['upah_mekanik']);
}
$total_jasa = count($jasa_list);
$total_omzet = array_sum(array_column($jasa_list, 'harga_jual'));
$total_upah = array_sum(array_column($jasa_list, 'upah_mekanik'));

$icons = ['wrench', 'oil-can', 'car-battery', 'fan', 'filter', 'gear', 'droplet', 'car-side'];
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-screwdriver-wrench text-slate-700 mr-2"></i>Jasa Servis</h1>
        <p>Daftar jasa servis dengan upah mekanik dan harga jual</p>
    </div>
    <button onclick="openModal('modalJasa')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Jasa
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="stat-card stat-card-slate">
        <div class="stat-card-icon"><i class="fas fa-screwdriver-wrench"></i></div>
        <div class="stat-card-value"><?= $total_jasa ?></div>
        <div class="stat-card-label">Total Jasa</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-hand-holding-dollar"></i></div>
        <div class="stat-card-value"><?= rupiah($total_upah) ?></div>
        <div class="stat-card-label">Total Upah Mekanik</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-sack-dollar"></i></div>
        <div class="stat-card-value"><?= rupiah($total_omzet) ?></div>
        <div class="stat-card-label">Total Harga Jual</div>
    </div>
    <div class="stat-card stat-card-rose">
        <div class="stat-card-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-card-value"><?= rupiah($total_laba) ?></div>
        <div class="stat-card-label">Total Laba Kotor</div>
    </div>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> animate-slide-down"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="content-card">
    <div class="content-card-header">
        <span class="text-sm text-gray-400">Total: <strong><?= $total_jasa ?></strong> jenis jasa servis</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:45px">#</th>
                        <th>Nama Jasa</th>
                        <th>Upah Mekanik</th>
                        <th>Harga Jual</th>
                        <th>Laba</th>
                        <th>Digunakan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($jasa_list) > 0): $no=1; ?>
                        <?php foreach($jasa_list as $r): 
                            $laba = $r['harga_jual'] - $r['upah_mekanik'];
                            $margin_persen = $r['harga_jual'] > 0 ? round(($laba / $r['harga_jual']) * 100) : 0;
                            $i = ($no-1) % count($icons);
                        ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $no++ ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-cyan-100 to-teal-50 flex items-center justify-center text-teal-500 text-xs flex-shrink-0">
                                        <i class="fas fa-<?= $icons[$i] ?>"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm"><?= htmlspecialchars($r['nama_jasa']) ?></div>
                                        <?php if ($r['keterangan']): ?>
                                            <div class="text-[10px] text-gray-400 max-w-[200px] truncate"><?= htmlspecialchars($r['keterangan']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-gray-600 text-xs"><?= rupiah($r['upah_mekanik']) ?></td>
                            <td class="font-semibold text-slate-700 text-sm"><?= rupiah($r['harga_jual']) ?></td>
                            <td>
                                <span class="badge <?= $laba > 0 ? 'badge-success' : 'badge-danger' ?> text-[10px]">
                                    <?= rupiah($laba) ?> (<?= $margin_persen ?>%)
                                </span>
                            </td>
                            <td><span class="badge badge-info text-[10px]"><?= $r['jml_digunakan'] ?>x</span></td>
                            <td>
                                <div class="flex gap-1 justify-center">
                                    <button onclick='editJasa(<?= json_encode($r) ?>)' class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus jasa servis <?= htmlspecialchars($r['nama_jasa']) ?>?')">
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
                                <div class="w-20 h-20 rounded-2xl bg-teal-50 flex items-center justify-center mb-4">
                                    <i class="fas fa-screwdriver-wrench text-3xl text-teal-300"></i>
                                </div>
                                <h3 class="font-semibold text-gray-400 mb-1">Belum Ada Jasa Servis</h3>
                                <p class="text-sm text-gray-400 mb-4">Tambah jasa untuk pencatatan servis motor</p>
                                <button onclick="openModal('modalJasa')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Jasa Servis
                                </button>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="modalJasa">
    <div class="modal-backdrop" onclick="closeModal('modalJasa')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle text-slate-600 mr-2"></i>Tambah Jasa Servis</h3>
            <button class="modal-close" onclick="closeModal('modalJasa')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="formId" value="0">
                <div class="form-group">
                    <label class="form-label">Nama Jasa <span class="text-red-400">*</span></label>
                    <input type="text" name="nama_jasa" id="formNama" class="form-control" required placeholder="Contoh: Ganti Oli Mesin">
                </div>
                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">Upah Mekanik (Rp) <i class="fas fa-info-circle text-gray-300 ml-1" title="Biaya jasa mekanik"></i></label>
                        <input type="text" name="upah_mekanik" id="formUpah" class="form-control input-rupiah" required placeholder="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp) <span class="text-red-400">*</span></label>
                        <input type="text" name="harga_jual" id="formHarga" class="form-control input-rupiah" required placeholder="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" id="formKeterangan" class="form-control" placeholder="Deskripsi jasa servis"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalJasa')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editJasa(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-amber-500 mr-2"></i>Edit Jasa Servis';
    document.getElementById('formAksi').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formNama').value = data.nama_jasa;
    document.getElementById('formUpah').value = parseInt(data.upah_mekanik).toLocaleString('id-ID');
    document.getElementById('formHarga').value = parseInt(data.harga_jual).toLocaleString('id-ID');
    document.getElementById('formKeterangan').value = data.keterangan || '';
    openModal('modalJasa');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
