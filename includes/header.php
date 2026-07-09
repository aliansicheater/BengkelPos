<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

checkLogin();

$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));

// Role check helper
$is_admin = ($_SESSION['role'] === 'admin');
$is_kasir = ($_SESSION['role'] === 'kasir');

// Load settings
$q_setting = mysqli_query($conn, "SELECT * FROM setting WHERE id=1");
$setting = mysqli_fetch_assoc($q_setting);
$app_name = $setting['nama_aplikasi'] ?? 'BengkelPOS';
$app_logo = $setting['logo'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> - <?= $page_title ?? 'Dashboard' ?></title>
    <script src="/assets/js/tailwind.min.js"></script>
    <link rel="stylesheet" href="/assets/css/inter.css">
    <link rel="stylesheet" href="/assets/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<!-- Doodle Background -->
<div class="doodle-bg">
    <div class="doodle-svg"></div>
</div>
<div class="doodle-overlay"></div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <?php if ($app_logo): ?>
                <img src="/uploads/<?= htmlspecialchars($app_logo) ?>" alt="Logo" class="w-8 h-8 object-contain">
            <?php else: ?>
                <i class="fas fa-wrench"></i>
            <?php endif; ?>
        </div>
        <div class="sidebar-logo-text">
            <?php
            // Split name into base and highlight part (last uppercase segment)
            $parts = preg_split('/(?=[A-Z][a-z]*$)/', $app_name, 2);
            if (count($parts) > 1 && trim($parts[1])): ?>
                <?= htmlspecialchars($parts[0]) ?><span><?= htmlspecialchars($parts[1]) ?></span>
            <?php else: ?>
                <?= htmlspecialchars($app_name) ?>
            <?php endif; ?>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="sidebar-menu-label">Menu Utama</div>

        <a href="/dashboard.php" class="sidebar-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <!-- Master Data -->
        <div class="sidebar-menu-label" style="margin-top:0.5rem;">Master Data</div>

        <a href="/master/barang.php" class="sidebar-item <?= ($current_page == 'barang.php') ? 'active' : '' ?>">
            <i class="fas fa-box"></i>
            <span>Barang</span>
        </a>
        <a href="/master/kategori.php" class="sidebar-item <?= ($current_page == 'kategori.php') ? 'active' : '' ?>">
            <i class="fas fa-tags"></i>
            <span>Kategori</span>
        </a>
        <a href="/master/pelanggan.php" class="sidebar-item <?= ($current_page == 'pelanggan.php') ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Pelanggan</span>
        </a>
        <a href="/master/mekanik.php" class="sidebar-item <?= ($current_page == 'mekanik.php') ? 'active' : '' ?>">
            <i class="fas fa-user-gear"></i>
            <span>Mekanik</span>
        </a>
        <a href="/master/jasa_servis.php" class="sidebar-item <?= ($current_page == 'jasa_servis.php') ? 'active' : '' ?>">
            <i class="fas fa-screwdriver-wrench"></i>
            <span>Jasa Servis</span>
        </a>
        <a href="/master/supplier.php" class="sidebar-item <?= ($current_page == 'supplier.php') ? 'active' : '' ?>">
            <i class="fas fa-truck"></i>
            <span>Supplier</span>
        </a>

        <!-- Transaksi -->
        <div class="sidebar-menu-label" style="margin-top:0.5rem;">Transaksi</div>

        <a href="/transaksi/penjualan.php" class="sidebar-item <?= ($current_page == 'penjualan.php') ? 'active' : '' ?>">
            <i class="fas fa-cart-shopping"></i>
            <span>Penjualan</span>
        </a>
        <a href="/transaksi/servis.php" class="sidebar-item <?= ($current_page == 'servis.php') ? 'active' : '' ?>">
            <i class="fas fa-motorcycle"></i>
            <span>Service Motor</span>
        </a>
        <a href="/transaksi/pembelian.php" class="sidebar-item <?= ($current_page == 'pembelian.php') ? 'active' : '' ?>">
            <i class="fas fa-truck-loading"></i>
            <span>Pembelian / Restok</span>
        </a>
        <a href="/transaksi/histori.php" class="sidebar-item <?= ($current_page == 'histori.php') ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            <span>Histori Transaksi</span>
        </a>

        <!-- Laporan -->
        <div class="sidebar-menu-label" style="margin-top:0.5rem;">Laporan</div>

        <a href="/laporan/index.php" class="sidebar-item <?= ($current_folder == 'laporan' && $current_page == 'index.php') ? 'active' : '' ?>">
            <i class="fas fa-file-invoice"></i>
            <span>Penjualan</span>
        </a>
        <a href="/laporan/servis.php" class="sidebar-item <?= ($current_page == 'servis.php' && $current_folder == 'laporan') ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Servis</span>
        </a>
        <a href="/laporan/stok.php" class="sidebar-item <?= ($current_page == 'stok.php') ? 'active' : '' ?>">
            <i class="fas fa-warehouse"></i>
            <span>Stok Barang</span>
        </a>

        <?php if ($is_admin): ?>
        <!-- Pengaturan -->
        <div class="sidebar-menu-label" style="margin-top:0.5rem;">Pengaturan</div>
        <a href="/admin/users.php" class="sidebar-item <?= ($current_page == 'users.php') ? 'active' : '' ?>">
            <i class="fas fa-shield-halved"></i>
            <span>Manajemen User</span>
        </a>
        <a href="/admin/pengaturan.php" class="sidebar-item <?= ($current_page == 'pengaturan.php') ? 'active' : '' ?>">
            <i class="fas fa-gear"></i>
            <span>Pengaturan</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>

<!-- Main Content -->
<div class="main-content" id="mainContent">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()" id="hamburgerBtn">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="topbar-title"><?= $page_title ?? 'Dashboard' ?></h1>
        </div>
        <div class="topbar-right">
            <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle Dark Mode">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>
            <div class="topbar-user" onclick="window.location.href='/logout.php'">
                <div class="topbar-user-avatar">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
                </div>
                <div class="topbar-user-info">
                    <div class="topbar-user-name"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></div>
                    <div class="topbar-user-role">
                        <span class="badge badge-info">
                            <i class="fas fa-<?= $is_admin ? 'crown' : 'user' ?> mr-1"></i>
                            <?= ucfirst($_SESSION['role']) ?>
                        </span>
                    </div>
                </div>
                <i class="fas fa-right-from-bracket text-gray-400 text-xs ml-2"></i>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-content">
