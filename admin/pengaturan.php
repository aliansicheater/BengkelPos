<?php
$page_title = 'Pengaturan Aplikasi';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
checkLogin();
checkRole('admin');

// Proses simpan nama aplikasi & upload logo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Simpan pengaturan
    if ($aksi === 'simpan') {
        $nama_aplikasi = mysqli_real_escape_string($conn, $_POST['nama_aplikasi'] ?? 'BengkelPOS');

        // Upload logo
        $logo_name = null;
        $old = mysqli_query($conn, "SELECT logo FROM setting WHERE id=1");
        if ($old) { $old_data = mysqli_fetch_assoc($old); $logo_name = $old_data['logo'] ?? null; }
        if (!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
            if (in_array($ext, $allowed)) {
                $logo_name = 'logo_' . time() . '.' . $ext;
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo_name);
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Format logo tidak didukung (jpg, png, webp, svg)'];
                header('Location: pengaturan.php');
                exit;
            }
        }

        mysqli_query($conn, "UPDATE setting SET nama_aplikasi='$nama_aplikasi', logo=" . ($logo_name ? "'$logo_name'" : "logo") . " WHERE id=1");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pengaturan berhasil disimpan!'];
        header('Location: pengaturan.php');
        exit;
    }

    // Hapus logo
    if ($aksi === 'hapus_logo') {
        $old = mysqli_query($conn, "SELECT logo FROM setting WHERE id=1");
        $old_data = mysqli_fetch_assoc($old);
        if ($old_data['logo'] && file_exists(__DIR__ . '/../uploads/' . $old_data['logo'])) {
            unlink(__DIR__ . '/../uploads/' . $old_data['logo']);
        }
        mysqli_query($conn, "UPDATE setting SET logo=NULL WHERE id=1");
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Logo berhasil dihapus!'];
        header('Location: pengaturan.php');
        exit;
    }

    // Backup database
    if ($aksi === 'backup') {
        $tables = [];
        $q_tables = mysqli_query($conn, "SHOW TABLES");
        while ($row = mysqli_fetch_row($q_tables)) $tables[] = $row[0];

        $output = "-- BengkelPOS Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $q_create = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            $create = mysqli_fetch_row($q_create);
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $create[1] . ";\n\n";

            $q_data = mysqli_query($conn, "SELECT * FROM `$table`");
            while ($row = mysqli_fetch_assoc($q_data)) {
                $cols = array_map(fn($c) => "`$c`", array_keys($row));
                $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'", array_values($row));
                $output .= "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
            }
            $output .= "\n";
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="bengkelpos_backup_' . date('Y-m-d') . '.sql"');
        echo $output;
        exit;
    }

    // Restore database
    if ($aksi === 'restore') {
        if (!empty($_FILES['file_restore']['tmp_name'])) {
            $content = file_get_contents($_FILES['file_restore']['tmp_name']);
            $queries = explode(";\n", $content);
            $errors = 0;
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && !str_starts_with($query, '--')) {
                    if (!mysqli_query($conn, $query)) $errors++;
                }
            }
            if ($errors > 0) {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => "Restore selesai dengan $errors error"];
            } else {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Database berhasil direstore!'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Pilih file SQL terlebih dahulu!'];
        }
        header('Location: pengaturan.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';

// Load setting
$q = mysqli_query($conn, "SELECT * FROM setting WHERE id=1");
$set = mysqli_fetch_assoc($q);
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-gear text-indigo-600 mr-2"></i>Pengaturan Aplikasi</h1>
        <p>Atur nama aplikasi, logo, serta backup & restore database</p>
    </div>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?> animate-slide-down"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<!-- Nama & Logo -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Card: Informasi Aplikasi -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-info-circle text-indigo-500 mr-2"></i>Informasi Aplikasi</h3>
        </div>
        <div class="content-card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="aksi" value="simpan">
                <div class="form-group">
                    <label class="form-label">Nama Aplikasi</label>
                    <input type="text" name="nama_aplikasi" class="form-control" value="<?= htmlspecialchars($set['nama_aplikasi'] ?? 'BengkelPOS') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Logo Aplikasi</label>
                    <?php if ($set['logo']): ?>
                        <div class="flex items-center gap-3 mb-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <img src="/Bengkel POS/uploads/<?= htmlspecialchars($set['logo']) ?>" alt="Logo" class="h-12 w-auto rounded-lg">
                            <span class="text-sm text-gray-500"><?= htmlspecialchars($set['logo']) ?></span>
                            <button type="submit" name="aksi" value="hapus_logo" class="btn btn-sm btn-danger ml-auto"><i class="fas fa-trash"></i></button>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small class="text-gray-400 text-xs mt-1 block">Format: JPG, PNG, WebP, SVG. Kosongkan jika tidak diubah.</small>
                </div>
                <div class="flex justify-end mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Card: Backup & Restore -->
    <div class="content-card">
        <div class="content-card-header">
            <h3><i class="fas fa-database text-indigo-500 mr-2"></i>Backup & Restore Database</h3>
        </div>
        <div class="content-card-body space-y-4">
            <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                <h4 class="font-semibold text-sm mb-1 flex items-center gap-2">
                    <i class="fas fa-download text-indigo-500"></i> Backup Database
                </h4>
                <p class="text-xs text-gray-400 mb-3">Download file SQL untuk menyimpan cadangan seluruh data.</p>
                <form method="POST">
                    <input type="hidden" name="aksi" value="backup">
                    <button type="submit" class="btn btn-primary w-full justify-center">
                        <i class="fas fa-download"></i> Download Backup (.sql)
                    </button>
                </form>
            </div>

            <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                <h4 class="font-semibold text-sm mb-1 flex items-center gap-2">
                    <i class="fas fa-upload text-amber-500"></i> Restore Database
                </h4>
                <p class="text-xs text-gray-400 mb-3">Upload file SQL backup untuk mengembalikan data.</p>
                <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Restore akan mengganti semua data yang ada! Lanjutkan?')">
                    <input type="hidden" name="aksi" value="restore">
                    <div class="flex gap-2">
                        <input type="file" name="file_restore" class="form-control text-sm" accept=".sql" required>
                        <button type="submit" class="btn btn-warning whitespace-nowrap"><i class="fas fa-upload"></i> Restore</button>
                    </div>
                </form>
            </div>

            <div class="text-xs text-gray-400 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Informasi:</strong> Tabel yang di-backup: pengguna, barang, pelanggan, supplier, mekanik, jasa servis, transaksi penjualan, servis, pembelian, dan setting.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
