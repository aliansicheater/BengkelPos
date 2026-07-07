<?php
$pageTitle = 'Manajemen User';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner']);
?>

<div class="animate-fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
        <div>
            <h1 class="text-xl font-bold" style="color:#f1f5f9">
                <i class="fas fa-users-cog text-primary-500 mr-2"></i>Manajemen User
            </h1>
            <p class="text-sm mt-1" style="color:#64748b">Kelola pengguna sistem</p>
        </div>
        <button onclick="openModal()" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> Tambah User
        </button>
    </div>

    <!-- Tabel User -->
    <div class="card overflow-hidden animate-fade-in-up stagger-1">
        <div class="overflow-x-auto">
            <table class="table table-hover w-full">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Username</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold" style="color:#64748b">Role</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold" style="color:#64748b">Aksi</th>
                    </tr>
                </thead>
                <tbody id="user-table-body">
                    <tr><td colspan="6" class="text-center py-8" style="color:#64748b">
                        <div class="skeleton h-4 w-48 mx-auto mb-2"></div>
                        <p>Memuat data...</p>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal User -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold" id="modal-title"><i class="fas fa-user-plus mr-2 text-primary-500"></i>Tambah User</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="u-id">
                <div class="mb-3">
                    <label class="form-label text-sm">Username <span class="text-red-400">*</span></label>
                    <input type="text" class="form-control" id="u-username" placeholder="Username untuk login">
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Nama Lengkap <span class="text-red-400">*</span></label>
                    <input type="text" class="form-control" id="u-nama" placeholder="Nama lengkap">
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm" id="u-pass-label">Password <span class="text-red-400">*</span></label>
                    <input type="password" class="form-control" id="u-password" placeholder="Password">
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Role <span class="text-red-400">*</span></label>
                    <select class="form-control" id="u-role">
                        <option value="admin">Admin</option>
                        <option value="kasir">Kasir</option>
                        <option value="mekanik">Mekanik</option>
                        <option value="gudang">Gudang</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Status</label>
                    <select class="form-control" id="u-status">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="simpanUser()">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let allUsers = [];
let editingId = null;

async function loadUsers() {
    const res = await apiRequest('getUsers');
    allUsers = res.data || [];
    renderUsers();
}

function renderUsers() {
    const tbody = document.getElementById('user-table-body');
    if (allUsers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8" style="color:#64748b"><i class="fas fa-inbox text-2xl mb-2 block opacity-30"></i>Tidak ada data user</td></tr>';
        return;
    }
    const roleColors = { owner:'#f59e0b', admin:'#0ea5e9', kasir:'#22c55e', mekanik:'#a78bfa', gudang:'#f97316' };
    tbody.innerHTML = allUsers.map((u, i) => `
        <tr class="hover:bg-white/5 transition-all">
            <td class="px-4 py-3 text-sm" style="color:#94a3b8">${i+1}</td>
            <td class="px-4 py-3 text-sm font-semibold" style="color:#f1f5f9">${u.username}</td>
            <td class="px-4 py-3 text-sm" style="color:#f1f5f9">${u.nama}</td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold" style="background:rgba(${u.role === 'owner' ? '245,158,11' : u.role === 'admin' ? '14,165,233' : u.role === 'kasir' ? '34,197,94' : u.role === 'mekanik' ? '167,139,250' : '249,115,22'},0.15);color:${roleColors[u.role] || '#94a3b8'}">${u.role.charAt(0).toUpperCase() + u.role.slice(1)}</span>
            </td>
            <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-lg text-xs font-semibold ${u.status === 'aktif' ? 'badge-success' : 'badge-danger'}">
                    ${u.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                </span>
            </td>
            <td class="px-4 py-3 text-center">
                <button onclick="editUser(${u.id})" class="px-2 py-1 rounded-lg text-xs transition-all hover:scale-105" style="background:rgba(14,165,233,0.15);color:#0ea5e9">
                    <i class="fas fa-edit"></i>
                </button>
                ${u.id != 1 ? `
                <button onclick="hapusUser(${u.id})" class="px-2 py-1 rounded-lg text-xs transition-all hover:scale-105 ml-1" style="background:rgba(239,68,68,0.15);color:#ef4444">
                    <i class="fas fa-trash"></i>
                </button>` : ''}
            </td>
        </tr>
    `).join('');
}

function openModal() {
    editingId = null;
    document.getElementById('u-id').value = '';
    document.getElementById('u-username').value = '';
    document.getElementById('u-nama').value = '';
    document.getElementById('u-password').value = '';
    document.getElementById('u-role').value = 'kasir';
    document.getElementById('u-status').value = 'aktif';
    document.getElementById('u-username').readOnly = false;
    document.getElementById('u-password').required = true;
    document.getElementById('u-pass-label').innerHTML = 'Password <span class="text-red-400">*</span>';
    document.getElementById('modal-title').innerHTML = '<i class="fas fa-user-plus mr-2 text-primary-500"></i>Tambah User';
    $('#userModal').modal('show');
}

function editUser(id) {
    const u = allUsers.find(x => x.id == id);
    if (!u) return;
    editingId = id;
    document.getElementById('u-id').value = u.id;
    document.getElementById('u-username').value = u.username;
    document.getElementById('u-username').readOnly = true;
    document.getElementById('u-nama').value = u.nama;
    document.getElementById('u-password').value = '';
    document.getElementById('u-password').required = false;
    document.getElementById('u-pass-label').innerHTML = 'Password <span class="text-xs" style="color:#64748b">(kosongkan jika tidak diubah)</span>';
    document.getElementById('u-role').value = u.role;
    document.getElementById('u-status').value = u.status;
    document.getElementById('modal-title').innerHTML = '<i class="fas fa-user-edit mr-2 text-primary-500"></i>Edit User';
    $('#userModal').modal('show');
}

async function simpanUser() {
    const nama = document.getElementById('u-nama').value.trim();
    const username = document.getElementById('u-username').value.trim();
    const password = document.getElementById('u-password').value;
    const role = document.getElementById('u-role').value;
    const status = document.getElementById('u-status').value;
    
    if (!nama || !username) return showToast('Username dan nama wajib diisi', 'warning');
    if (!editingId && !password) return showToast('Password wajib diisi', 'warning');
    
    if (editingId) {
        const payload = { id: editingId, nama, role, status };
        if (password) payload.password = password;
        await apiRequest('updateUser', payload, 'POST');
    } else {
        await apiRequest('addUser', { username, password, nama, role }, 'POST');
    }
    $('#userModal').modal('hide');
    loadUsers();
}

async function hapusUser(id) {
    if (!confirm('Yakin ingin menghapus user ini?')) return;
    await apiRequest('deleteUser', { id }, 'POST');
    loadUsers();
}

loadUsers();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
