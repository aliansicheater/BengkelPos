<?php
$pageTitle = 'Pengaturan';
require_once __DIR__ . '/includes/header.php';
requireRole(['owner','admin']);
?>

<div class="animate-fade-in-up">
    <div class="mb-4">
        <h1 class="text-xl font-bold" style="color:#f1f5f9">
            <i class="fas fa-cog text-primary-500 mr-2"></i>Pengaturan
        </h1>
        <p class="text-sm mt-1" style="color:#64748b">Konfigurasi aplikasi bengkel</p>
    </div>

    <!-- Profil Bengkel -->
    <div class="card p-6 mb-4 animate-fade-in-up stagger-1">
        <h3 class="text-lg font-bold mb-4" style="color:#f1f5f9">
            <i class="fas fa-store text-primary-500 mr-2"></i>Profil Bengkel
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label text-sm">Nama Bengkel</label>
                <input type="text" class="form-control" id="cfg-nama" placeholder="Nama bengkel">
            </div>
            <div>
                <label class="form-label text-sm">No. Telepon</label>
                <input type="text" class="form-control" id="cfg-telp" placeholder="Nomor telepon">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label text-sm">Alamat</label>
                <input type="text" class="form-control" id="cfg-alamat" placeholder="Alamat lengkap">
            </div>
        </div>
    </div>

    <!-- Pengaturan Transaksi -->
    <div class="card p-6 mb-4 animate-fade-in-up stagger-2">
        <h3 class="text-lg font-bold mb-4" style="color:#f1f5f9">
            <i class="fas fa-receipt text-accent-500 mr-2"></i>Pengaturan Transaksi
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label text-sm">Pajak (%)</label>
                <input type="number" class="form-control" id="cfg-pajak" placeholder="11" min="0" max="100">
            </div>
            <div>
                <label class="form-label text-sm">Diskon Default (%)</label>
                <input type="number" class="form-control" id="cfg-diskon" placeholder="0" min="0" max="100">
            </div>
            <div>
                <label class="form-label text-sm">Printer Struk</label>
                <select class="form-control" id="cfg-printer">
                    <option value="Default">Default</option>
                    <option value="58mm">Thermal 58mm</option>
                    <option value="80mm">Thermal 80mm</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Simpan Button -->
    <div class="flex justify-end animate-fade-in-up stagger-3">
        <button onclick="simpanConfig()" class="btn btn-primary px-8 py-3">
            <i class="fas fa-save mr-2"></i> Simpan Pengaturan
        </button>
    </div>
</div>

<script>
async function loadConfig() {
    const res = await apiRequest('getAppConfig');
    const cfg = res.data || {};
    document.getElementById('cfg-nama').value = (cfg.nama_bengkel || '').replace(/"/g, '');
    document.getElementById('cfg-telp').value = (cfg.no_telp_bengkel || '').replace(/"/g, '');
    document.getElementById('cfg-alamat').value = (cfg.alamat_bengkel || '').replace(/"/g, '');
    document.getElementById('cfg-pajak').value = (cfg.pajak_persen || '11').replace(/"/g, '');
    document.getElementById('cfg-diskon').value = (cfg.diskon_default || '0').replace(/"/g, '');
    document.getElementById('cfg-printer').value = (cfg.printer_struk || 'Default').replace(/"/g, '');
}

async function simpanConfig() {
    await apiRequest('updateAppConfig', {
        nama_bengkel: JSON.stringify(document.getElementById('cfg-nama').value),
        no_telp_bengkel: JSON.stringify(document.getElementById('cfg-telp').value),
        alamat_bengkel: JSON.stringify(document.getElementById('cfg-alamat').value),
        pajak_persen: JSON.stringify(document.getElementById('cfg-pajak').value),
        diskon_default: JSON.stringify(document.getElementById('cfg-diskon').value),
        printer_struk: JSON.stringify(document.getElementById('cfg-printer').value)
    }, 'POST');
    showToast('Pengaturan berhasil disimpan', 'success');
}

loadConfig();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
