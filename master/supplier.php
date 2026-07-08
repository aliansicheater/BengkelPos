<?php
$page_title = 'Data Supplier';
require_once __DIR__ . '/../config/database.php';

// Proses Simpan / Edit / Hapus — BEFORE header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id) {
        mysqli_query($conn, "DELETE FROM supplier WHERE id=$id");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Supplier dihapus!'];
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_supplier'] ?? '');
        $telepon = mysqli_real_escape_string($conn, $_POST['no_telepon'] ?? '');
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');

        if ($aksi === 'tambah') {
            mysqli_query($conn, "INSERT INTO supplier (nama_supplier, no_telepon, alamat) VALUES ('$nama', '$telepon', '$alamat')");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Supplier ditambahkan!'];
        } elseif ($id) {
            mysqli_query($conn, "UPDATE supplier SET nama_supplier='$nama', no_telepon='$telepon', alamat='$alamat' WHERE id=$id");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Supplier diupdate!'];
        }
    }
    header('Location: supplier.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$q = mysqli_query($conn, "SELECT s.*, 
    (SELECT COUNT(*) FROM pembelian WHERE id_supplier=s.id) as jml_pembelian,
    (SELECT COALESCE(SUM(total),0) FROM pembelian WHERE id_supplier=s.id) as total_pembelian
    FROM supplier s ORDER BY s.nama_supplier ASC");
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

$supplier_list = [];
while($r = mysqli_fetch_assoc($q)) $supplier_list[] = $r;
$total_supplier = count($supplier_list);
$total_transaksi = array_sum(array_column($supplier_list, 'jml_pembelian'));
$total_nilai = array_sum(array_column($supplier_list, 'total_pembelian'));
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-truck text-slate-700 mr-2"></i>Data Supplier</h1>
        <p>Kelola data supplier / pemasok sparepart & aksesoris</p>
    </div>
    <button onclick="openModal('modalSupplier')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah Supplier
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
    <div class="stat-card stat-card-slate">
        <div class="stat-card-icon"><i class="fas fa-truck"></i></div>
        <div class="stat-card-value"><?= $total_supplier ?></div>
        <div class="stat-card-label">Total Supplier</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-card-value"><?= $total_transaksi ?></div>
        <div class="stat-card-label">Total Pembelian</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-card-value"><?= rupiah($total_nilai) ?></div>
        <div class="stat-card-label">Total Nilai Pembelian</div>
    </div>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> animate-slide-down"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="content-card">
    <div class="content-card-header">
        <span class="text-sm text-gray-400">Total: <strong><?= $total_supplier ?></strong> supplier</span>
    </div>
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width:45px">#</th>
                        <th>Nama Supplier</th>
                        <th>Kontak</th>
                        <th>Total Pembelian</th>
                        <th>Nilai Pembelian</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($supplier_list) > 0): $no=1; ?>
                        <?php foreach($supplier_list as $r): ?>
                        <tr>
                            <td class="text-gray-400 text-xs"><?= $no++ ?></td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-100 to-green-50 flex items-center justify-center text-emerald-500 text-xs flex-shrink-0">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm"><?= htmlspecialchars($r['nama_supplier']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-col gap-0.5">
                                    <?php if ($r['no_telepon']): ?>
                                        <a href="tel:<?= htmlspecialchars($r['no_telepon']) ?>" class="text-xs text-slate-600 hover:text-slate-800">
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
                            <td><span class="badge badge-info text-[10px]"><?= $r['jml_pembelian'] ?>x</span></td>
                            <td class="text-slate-700 font-semibold text-xs"><?= rupiah($r['total_pembelian']) ?></td>
                            <td>
                                <div class="flex gap-1 justify-center">
                                    <button onclick='editSupplier(<?= json_encode($r) ?>)' class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus supplier <?= htmlspecialchars($r['nama_supplier']) ?>?')">
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
                                <div class="w-20 h-20 rounded-2xl bg-emerald-50 flex items-center justify-center mb-4">
                                    <i class="fas fa-truck text-3xl text-emerald-300"></i>
                                </div>
                                <h3 class="font-semibold text-gray-400 mb-1">Belum Ada Supplier</h3>
                                <p class="text-sm text-gray-400 mb-4">Tambahkan supplier untuk mencatat pembelian barang</p>
                                <button onclick="openModal('modalSupplier')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Supplier
                                </button>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="modalSupplier">
    <div class="modal-backdrop" onclick="closeModal('modalSupplier')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle text-slate-600 mr-2"></i>Tambah Supplier</h3>
            <button class="modal-close" onclick="closeModal('modalSupplier')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="formId" value="0">
                <div class="form-group">
                    <label class="form-label">Nama Supplier <span class="text-red-400">*</span></label>
                    <input type="text" name="nama_supplier" id="formNama" class="form-control" required placeholder="Nama supplier">
                </div>
                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telepon" id="formTelepon" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" id="formAlamat" class="form-control" placeholder="Alamat supplier"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalSupplier')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSupplier(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-amber-500 mr-2"></i>Edit Supplier';
    document.getElementById('formAksi').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formNama').value = data.nama_supplier;
    document.getElementById('formTelepon').value = data.no_telepon || '';
    document.getElementById('formAlamat').value = data.alamat || '';
    openModal('modalSupplier');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
