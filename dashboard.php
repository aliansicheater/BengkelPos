<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Stat Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,rgba(14,165,233,0.15),rgba(14,165,233,0.05))">
            <div class="inner">
                <h3 id="omzet-hari" class="animate-fade-in-up">Rp 0</h3>
                <p>Omzet Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave" style="color:rgba(14,165,233,0.4)"></i>
            </div>
            <a href="<?= BASE_URL ?>/laporan.php" class="small-box-footer" style="color:#0ea5e9">
                Lihat laporan <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,rgba(34,197,94,0.15),rgba(34,197,94,0.05))">
            <div class="inner">
                <h3 id="servis-hari" class="animate-fade-in-up" style="animation-delay:0.1s">0</h3>
                <p>Servis Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-wrench" style="color:rgba(34,197,94,0.4)"></i>
            </div>
            <a href="<?= BASE_URL ?>/servis.php" class="small-box-footer" style="color:#22c55e">
                Lihat servis <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,rgba(245,158,11,0.15),rgba(245,158,11,0.05))">
            <div class="inner">
                <h3 id="stok-menipis" class="animate-fade-in-up" style="animation-delay:0.2s;color:#f59e0b">0</h3>
                <p>Stok Menipis</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle" style="color:rgba(245,158,11,0.4)"></i>
            </div>
            <a href="<?= BASE_URL ?>/barang.php" class="small-box-footer" style="color:#f59e0b">
                Lihat barang <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box" style="background:linear-gradient(135deg,rgba(239,68,68,0.15),rgba(239,68,68,0.05))">
            <div class="inner">
                <h3 id="servis-proses" class="animate-fade-in-up" style="animation-delay:0.3s;color:#ef4444">0</h3>
                <p>Servis Dalam Proses</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock" style="color:rgba(239,68,68,0.4)"></i>
            </div>
            <a href="<?= BASE_URL ?>/servis.php" class="small-box-footer" style="color:#ef4444">
                Lihat proses <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Second Row -->
<div class="row mt-3">
    <div class="col-lg-4 col-sm-6">
        <div class="info-box" style="background:#1e293b;border:1px solid rgba(255,255,255,0.05);border-radius:1rem">
            <span class="info-box-icon" style="background:rgba(14,165,233,0.1);border-radius:0.75rem"><i class="fas fa-chart-bar" style="color:#0ea5e9"></i></span>
            <div class="info-box-content">
                <span class="info-box-text" style="color:#64748b">Omzet Bulan Ini</span>
                <span class="info-box-number" id="omzet-bulan" style="color:#f1f5f9">Rp 0</span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="info-box" style="background:#1e293b;border:1px solid rgba(255,255,255,0.05);border-radius:1rem">
            <span class="info-box-icon" style="background:rgba(239,68,68,0.1);border-radius:0.75rem"><i class="fas fa-hand-holding-usd" style="color:#ef4444"></i></span>
            <div class="info-box-content">
                <span class="info-box-text" style="color:#64748b">Piutang Pelanggan</span>
                <span class="info-box-number" id="piutang" style="color:#f1f5f9">Rp 0</span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="info-box" style="background:#1e293b;border:1px solid rgba(255,255,255,0.05);border-radius:1rem">
            <span class="info-box-icon" style="background:rgba(245,158,11,0.1);border-radius:0.75rem"><i class="fas fa-file-invoice-dollar" style="color:#f59e0b"></i></span>
            <div class="info-box-content">
                <span class="info-box-text" style="color:#64748b">Hutang Supplier</span>
                <span class="info-box-number" id="hutang" style="color:#f1f5f9">Rp 0</span>
            </div>
        </div>
    </div>
</div>

<!-- Chart + Quick Actions -->
<div class="row mt-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2" style="color:#0ea5e9"></i>Grafik Penjualan 7 Hari</h3>
            </div>
            <div class="card-body">
                <canvas id="salesChart" style="height:280px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt mr-2" style="color:#f59e0b"></i>Aksi Cepat</h3>
            </div>
            <div class="card-body p-2">
                <a href="<?= BASE_URL ?>/penjualan.php" class="btn btn-block btn-primary mb-2 text-left">
                    <i class="fas fa-cash-register mr-2"></i> Buka Kasir
                </a>
                <a href="<?= BASE_URL ?>/servis.php" class="btn btn-block btn-success mb-2 text-left">
                    <i class="fas fa-motorcycle mr-2"></i> Buat Work Order
                </a>
                <a href="<?= BASE_URL ?>/pembelian.php" class="btn btn-block btn-warning mb-2 text-left">
                    <i class="fas fa-shopping-cart mr-2"></i> Stok Masuk
                </a>
                <a href="<?= BASE_URL ?>/barang.php" class="btn btn-block btn-secondary mb-2 text-left">
                    <i class="fas fa-boxes mr-2"></i> Data Barang
                </a>
            </div>
        </div>
    </div>
</div>

<?php $extraScripts = '
async function loadDashboard() {
    const res = await apiRequest("getDashboard", {}, "GET");
    if (res.status === "success") {
        const d = res.data;
        animateNumber(document.getElementById("omzet-hari"), d.omzet_hari);
        animateNumber(document.getElementById("omzet-bulan"), d.omzet_bulan);
        document.getElementById("servis-hari").textContent = d.servis_hari;
        document.getElementById("stok-menipis").textContent = d.stok_menipis;
        document.getElementById("servis-proses").textContent = d.servis_proses;
        animateNumber(document.getElementById("piutang"), d.piutang);
        animateNumber(document.getElementById("hutang"), d.hutang);
        
        const labels = d.grafik_penjualan.map(r => {
            const dt = new Date(r.tanggal);
            return dt.toLocaleDateString("id-ID", {day:"numeric", month:"short"});
        });
        const values = d.grafik_penjualan.map(r => r.total);
        
        const isDark = document.body.classList.contains("dark-mode");
        const gridColor = isDark ? "rgba(255,255,255,0.03)" : "rgba(0,0,0,0.05)";
        const tickColor = isDark ? "#64748b" : "#94a3b8";
        
        new Chart(document.getElementById("salesChart"), {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Penjualan",
                    data: values,
                    backgroundColor: "rgba(14,165,233,0.3)",
                    borderColor: "#0ea5e9",
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: tickColor, font: { size: 11 } } },
                    y: { grid: { color: gridColor }, ticks: { color: tickColor, font: { size: 11 }, callback: v => formatRupiah(v) } }
                }
            }
        });
    }
}
loadDashboard();
'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
