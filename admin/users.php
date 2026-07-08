<?php
$page_title = 'Manajemen User';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
session_start();
checkLogin();
checkRole('admin');

// Proses — BEFORE header
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus' && $id) {
        if ($id == $_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Tidak bisa menghapus akun sendiri!'];
        } else {
            mysqli_query($conn, "DELETE FROM users WHERE id=$id");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User dihapus!'];
        }
    } elseif ($aksi === 'tambah' || $aksi === 'edit') {
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
        $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap'] ?? '');
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? 'kasir');

        if ($aksi === 'tambah') {
            $pass_plain = $_POST['password'] ?? 'password';
            $pass = password_hash($pass_plain, PASSWORD_DEFAULT);
            $pass_plain = mysqli_real_escape_string($conn, $pass_plain);
            mysqli_query($conn, "INSERT INTO users (username, password, password_plain, nama_lengkap, role) VALUES ('$username', '$pass', '$pass_plain', '$nama', '$role')");
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User ditambahkan!'];
        } elseif ($id) {
            if (!empty($_POST['password'])) {
                $pass_plain = $_POST['password'];
                $pass = password_hash($pass_plain, PASSWORD_DEFAULT);
                $pass_plain = mysqli_real_escape_string($conn, $pass_plain);
                mysqli_query($conn, "UPDATE users SET username='$username', password='$pass', password_plain='$pass_plain', nama_lengkap='$nama', role='$role' WHERE id=$id");
            } else {
                mysqli_query($conn, "UPDATE users SET username='$username', nama_lengkap='$nama', role='$role' WHERE id=$id");
            }
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User diupdate!'];
        }
    }
    header('Location: users.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$q = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, nama_lengkap ASC");
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
?>

<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1><i class="fas fa-shield-halved text-indigo-600 mr-2"></i>Manajemen User</h1>
        <p>Kelola akun pengguna aplikasi (Admin only)</p>
    </div>
    <button onclick="openModal('modalUser')" class="btn btn-primary">
        <i class="fas fa-plus"></i> Tambah User
    </button>
</div>

<?php if ($flash): ?><div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-check-circle"></i> <?= $flash['msg'] ?></div><?php endif; ?>

<div class="content-card">
    <div class="content-card-body p-0">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td class="font-mono font-medium"><?= htmlspecialchars($r['username']) ?></td>
                        <td>
                            <div class="flex items-center gap-1 group">
                                <span class="font-mono text-xs" id="pass-<?= $r['id'] ?>">
                                    <?php if ($r['password_plain']): ?>
                                        <span class="password-mask" data-target="pass-<?= $r['id'] ?>">
                                            <span class="pass-hidden">••••••••</span>
                                            <span class="pass-visible" style="display:none"><?= htmlspecialchars($r['password_plain']) ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-300">-</span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($r['password_plain']): ?>
                                    <button onclick="togglePass('pass-<?= $r['id'] ?>')" class="text-gray-400 hover:text-indigo-500 transition-colors ml-1" title="Lihat/Sembunyikan">
                                        <i class="fas fa-eye text-[10px]"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                        <td>
                            <span class="badge <?= $r['role'] == 'admin' ? 'badge-warning' : 'badge-info' ?>">
                                <i class="fas fa-<?= $r['role'] == 'admin' ? 'crown' : 'user' ?> mr-1"></i>
                                <?= ucfirst($r['role']) ?>
                            </span>
                        </td>
                        <td><?= tglIndo($r['created_at']) ?></td>
                        <td>
                            <div class="flex gap-1 justify-center">
                                <button onclick='editUser(<?= json_encode($r) ?>)' class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                <?php if ($r['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus user <?= htmlspecialchars($r['username']) ?>?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal" id="modalUser">
    <div class="modal-backdrop" onclick="closeModal('modalUser')"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle text-indigo-500 mr-2"></i>Tambah User</h3>
            <button class="modal-close" onclick="closeModal('modalUser')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="aksi" id="formAksi" value="tambah">
                <input type="hidden" name="id" id="formId" value="0">
                <div class="form-row grid-cols-1 sm:grid-cols-2">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="formUsername" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" id="formRole" class="form-control">
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="formNama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="formPassword" class="form-control" placeholder="Min. 6 karakter">
                    <small class="text-gray-400 text-xs" id="passHelp">Isi password untuk membuat/merubah password</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalUser')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-amber-500 mr-2"></i>Edit User';
    document.getElementById('formAksi').value = 'edit';
    document.getElementById('formId').value = data.id;
    document.getElementById('formUsername').value = data.username;
    document.getElementById('formNama').value = data.nama_lengkap;
    document.getElementById('formRole').value = data.role;
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').placeholder = 'Kosongkan jika tidak diubah';
    document.getElementById('passHelp').textContent = 'Kosongkan jika tidak ingin mengubah password';
    openModal('modalUser');
}



function togglePass(id) {
    const el = document.getElementById(id);
    el.querySelectorAll('.pass-hidden, .pass-visible').forEach(s => s.style.display = s.style.display === 'none' ? '' : 'none');
}

// Reset modal
document.getElementById('modalUser').addEventListener('click', function(e) {
    if (e.target === this) {
        resetModal();
    }
});
function resetModal() {
    document.getElementById('formAksi').value = 'tambah';
    document.getElementById('formId').value = '0';
    document.getElementById('formUsername').value = '';
    document.getElementById('formNama').value = '';
    document.getElementById('formRole').value = 'kasir';
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').placeholder = 'Min. 6 karakter';
    document.getElementById('passHelp').textContent = 'Isi password untuk membuat/merubah password';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
