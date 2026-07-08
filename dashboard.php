<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

// Statistik
$tgl_sekarang = date('Y-m-d');
$bulan_ini = date('Y-m');
$tahun_ini = date('Y');

// Total penjualan hari ini
$q_penjualan = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total, COUNT(*) as count FROM penjualan WHERE tgl = '$tgl_sekarang'");
$penjualan = mysqli_fetch_assoc($q_penjualan);

// Total servis hari ini
$q_servis = mysqli_query($conn, "SELECT COALESCE(SUM(grand_total),0) as total, COUNT(*) as count FROM servis WHERE tgl = '$tgl_sekarang'");
$servis = mysqli_fetch_assoc($q_servis);

// Stok menipis
$q_stok = mysqli_query($conn, "SELECT COUNT(*) as count FROM barang WHERE stok <= stok_minimal");
$stok_menipis = mysqli_fetch_assoc($q_stok);

// Total barang
$q_total_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang");
$total_barang = mysqli_fetch_assoc($q_total_barang);

// 10 transaksi terakhir
$q_riwayat = mysqli_query($conn, "SELECT 'penjualan' as tipe, no_invoice, tgl, total as total FROM penjualan UNION ALL SELECT 'servis' as tipe, no_invoice, tgl, grand_total FROM servis ORDER BY tgl DESC, no_invoice DESC LIMIT 10");

// --- CHART DATA: Penjualan 30 Hari ---
$chart_labels = []; $chart_data = [];
for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($tgl));
    $q = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total FROM penjualan WHERE tgl = '$tgl'");
    $r = mysqli_fetch_assoc($q);
    $chart_data[] = (int)$r['total'];
}

// --- CHART: Penjualan per Bulan (6 bulan) ---
$bulan_labels = []; $bulan_penjualan = []; $bulan_servis = [];
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-$i months"));
    $bulan_labels[] = date('M Y', strtotime($bln . '-01'));
    $q1 = mysqli_query($conn, "SELECT COALESCE(SUM(total),0) as total FROM penjualan WHERE DATE_FORMAT(tgl, '%Y-%m') = '$bln'");
    $r1 = mysqli_fetch_assoc($q1);
    $bulan_penjualan[] = (int)$r1['total'];
    $q2 = mysqli_query($conn, "SELECT COALESCE(SUM(grand_total),0) as total FROM servis WHERE DATE_FORMAT(tgl, '%Y-%m') = '$bln'");
    $r2 = mysqli_fetch_assoc($q2);
    $bulan_servis[] = (int)$r2['total'];
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<div class="page-header">
    <h1><i class="fas fa-chart-pie text-indigo-600 mr-2"></i>Dashboard</h1>
    <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>! Ringkasan bisnis hari ini.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card stat-card-indigo">
        <div class="stat-card-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-card-value"><?= rupiah($penjualan['total']) ?></div>
        <div class="stat-card-label">Penjualan Hari Ini (<?= $penjualan['count'] ?> transaksi)</div>
    </div>
    <div class="stat-card stat-card-amber">
        <div class="stat-card-icon"><i class="fas fa-motorcycle"></i></div>
        <div class="stat-card-value"><?= rupiah($servis['total']) ?></div>
        <div class="stat-card-label">Service Hari Ini (<?= $servis['count'] ?> servis)</div>
    </div>
    <div class="stat-card stat-card-green">
        <div class="stat-card-icon"><i class="fas fa-box"></i></div>
        <div class="stat-card-value"><?= $total_barang['total'] ?></div>
        <div class="stat-card-label">Total Barang Terdaftar</div>
    </div>
    <div class="stat-card stat-card-rose">
        <div class="stat-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-card-value"><?= $stok_menipis['count'] ?></div>
        <div class="stat-card-label">Stok Menipis (perlu restok)</div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Line Chart: 30 Hari -->
    <div class="content-card">
        <div class="content-card-header">
            <h2><i class="fas fa-chart-line text-indigo-500 mr-2"></i>Penjualan 30 Hari</h2>
            <span class="text-xs text-gray-400">Total per hari</span>
        </div>
        <div class="content-card-body">
            <canvas id="chartPenjualan" height="220"></canvas>
        </div>
    </div>
    <!-- Bar Chart: Per Bulan -->
    <div class="content-card">
        <div class="content-card-header">
            <h2><i class="fas fa-chart-bar text-amber-500 mr-2"></i>Perbandingan Bulanan</h2>
            <span class="text-xs text-gray-400">Penjualan vs Servis</span>
        </div>
        <div class="content-card-body">
            <canvas id="chartBulanan" height="220"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Doughnut Chart: Revenue Mix -->
    <div class="content-card">
        <div class="content-card-header">
            <h2><i class="fas fa-chart-pie text-green-500 mr-2"></i>Komposisi Pendapatan</h2>
            <span class="text-xs text-gray-400">Bulan <?= date('M Y') ?></span>
        </div>
        <div class="content-card-body flex justify-center">
            <div style="max-width: 260px;">
                <canvas id="chartDoughnut" height="260"></canvas>
            </div>
        </div>
    </div>
    <!-- Recent Transactions -->
    <div class="content-card">
        <div class="content-card-header">
            <h2><i class="fas fa-clock-rotate text-indigo-500 mr-2"></i>Transaksi Terakhir</h2>
            <a href="laporan/index.php" class="btn btn-sm btn-outline">Lihat Semua</a>
        </div>
        <div class="content-card-body p-0">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Tgl</th>
                            <th>Tipe</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($q_riwayat) > 0): ?>
                            <?php while($r = mysqli_fetch_assoc($q_riwayat)): ?>
                            <tr>
                                <td><span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"><?= htmlspecialchars($r['no_invoice']) ?></span></td>
                                <td><?= formatTanggal($r['tgl']) ?></td>
                                <td>
                                    <span class="badge <?= $r['tipe'] == 'penjualan' ? 'badge-success' : 'badge-info' ?>">
                                        <i class="fas fa-<?= $r['tipe'] == 'penjualan' ? 'cart-shopping' : 'motorcycle' ?> mr-1"></i>
                                        <?= ucfirst($r['tipe']) ?>
                                    </span>
                                </td>
                                <td class="font-semibold"><?= rupiah($r['total']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-gray-400">
                                    <i class="fas fa-inbox text-3xl block mb-2"></i>
                                    Belum ada transaksi hari ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Chart.js Dark Mode helper
function chartColors() {
    const isDark = document.body.classList.contains('dark-mode');
    return {
        grid: isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)',
        text: isDark ? '#94A3B8' : '#64748B',
        border: isDark ? '#334155' : '#E2E8F0'
    };
}

// --- CHART 1: Penjualan 30 Hari ---
const ctx1 = document.getElementById('chartPenjualan').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Penjualan (Rp)',
            data: <?= json_encode($chart_data) ?>,
            borderColor: '#4F46E5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: '#4F46E5',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: chartColors().text, font: { size: 10 } }
            },
            y: {
                grid: { color: chartColors().grid },
                ticks: {
                    color: chartColors().text,
                    font: { size: 10 },
                    callback: function(v) { return 'Rp' + (v / 1000).toFixed(0) + 'k'; }
                }
            }
        }
    }
});

// --- CHART 2: Bulanan ---
const ctx2 = document.getElementById('chartBulanan').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?= json_encode($bulan_labels) ?>,
        datasets: [
            {
                label: 'Penjualan',
                data: <?= json_encode($bulan_penjualan) ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderRadius: 6,
                borderSkipped: false
            },
            {
                label: 'Servis',
                data: <?= json_encode($bulan_servis) ?>,
                backgroundColor: 'rgba(245, 158, 11, 0.8)',
                borderRadius: 6,
                borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: chartColors().text, font: { size: 11 }, boxWidth: 12, padding: 10 }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: chartColors().text, font: { size: 9 } }
            },
            y: {
                grid: { color: chartColors().grid },
                ticks: {
                    color: chartColors().text,
                    font: { size: 10 },
                    callback: function(v) { return 'Rp' + (v / 1000).toFixed(0) + 'k'; }
                }
            }
        }
    }
});

// --- CHART 3: Doughnut ---
const totalPjl = <?= array_sum($bulan_penjualan) ?: 0 ?>;
const totalSrv = <?= array_sum($bulan_servis) ?: 0 ?>;

const ctx3 = document.getElementById('chartDoughnut').getContext('2d');
new Chart(ctx3, {
    type: 'doughnut',
    data: {
        labels: ['Penjualan Barang', 'Service Motor'],
        datasets: [{
            data: [totalPjl || 1, totalSrv || 1],
            backgroundColor: ['#4F46E5', '#F59E0B'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: chartColors().text, font: { size: 11 }, padding: 12 }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
