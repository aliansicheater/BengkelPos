<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$user = getCurrentUser();
$settings = getAppSettings();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get notifications count
$db = getDB();
$notifCount = 0;
try {
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM notif_stok WHERE is_read = 0");
    $notifCount = $stmt->fetch()['cnt'] ?? 0;
} catch(Exception $e) {}

// Menu structure
$menus = [
    ['page'=>'dashboard','label'=>'Dashboard','icon'=>'tachometer-alt','group'=>'','roles'=>['owner','admin','kasir','mekanik','gudang']],
    ['page'=>'master/barang','label'=>'Data Barang','icon'=>'boxes','group'=>'Master Data','roles'=>['owner','admin','gudang']],
    ['page'=>'master/jasa_servis','label'=>'Jasa Servis','icon'=>'tools','group'=>'Master Data','roles'=>['owner','admin']],
    ['page'=>'master/pelanggan','label'=>'Pelanggan','icon'=>'users','group'=>'Master Data','roles'=>['owner','admin','kasir']],
    ['page'=>'master/mekanik','label'=>'Mekanik','icon'=>'user-cog','group'=>'Master Data','roles'=>['owner','admin']],
    ['page'=>'master/supplier','label'=>'Supplier','icon'=>'truck','group'=>'Master Data','roles'=>['owner','admin','gudang']],
    ['page'=>'pembelian','label'=>'Pembelian','icon'=>'shopping-cart','group'=>'Transaksi','roles'=>['owner','admin','gudang']],
    ['page'=>'penjualan','label'=>'Penjualan / Kasir','icon'=>'cash-register','group'=>'Transaksi','roles'=>['owner','admin','kasir']],
    ['page'=>'servis','label'=>'Servis Motor','icon'=>'motorcycle','group'=>'Transaksi','roles'=>['owner','admin','kasir','mekanik']],
    ['page'=>'stok','label'=>'Manajemen Stok','icon'=>'warehouse','group'=>'Stok','roles'=>['owner','admin','gudang']],
    ['page'=>'stock_opname','label'=>'Stock Opname','icon'=>'clipboard-check','group'=>'Stok','roles'=>['owner','admin','gudang']],
    ['page'=>'retur','label'=>'Retur','icon'=>'undo-alt','group'=>'Stok','roles'=>['owner','admin','gudang']],
    ['page'=>'hutang','label'=>'Hutang Supplier','icon'=>'file-invoice-dollar','group'=>'Keuangan','roles'=>['owner','admin']],
    ['page'=>'piutang','label'=>'Piutang Pelanggan','icon'=>'hand-holding-usd','group'=>'Keuangan','roles'=>['owner','admin']],
    ['page'=>'riwayat_servis','label'=>'Riwayat Servis','icon'=>'history','group'=>'Lainnya','roles'=>['owner','admin','kasir','mekanik']],
    ['page'=>'laporan','label'=>'Laporan','icon'=>'chart-line','group'=>'Lainnya','roles'=>['owner','admin']],
    ['page'=>'manajemen_user','label'=>'Manajemen User','icon'=>'user-shield','group'=>'Pengaturan','roles'=>['owner']],
    ['page'=>'pengaturan','label'=>'Pengaturan','icon'=>'cog','group'=>'Pengaturan','roles'=>['owner','admin']],
];

$roleMenus = array_filter($menus, fn($m) => in_array($user['role'], $m['roles']) || $user['role'] === 'owner');
?>
<!DOCTYPE html>
<html lang="id" class="hold-transition sidebar-mini layout-fixed <?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light' ? '' : 'dark-mode' ?>" data-theme="<?= $_COOKIE['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f172a">
    <title><?= $pageTitle ?? ucfirst(str_replace('_',' ',$currentPage)) ?> — <?= $settings['nama_bengkel'] ?></title>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                colors: {
                    primary: {50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1'},
                    accent: {400:'#4ade80',500:'#22c55e',600:'#16a34a'},
                }
            }
        }
    }
    </script>
    
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        
        /* ===== DARK MODE (Default) ===== */
        body, .main-sidebar, .content-wrapper, .main-header {
            background: #0f172a !important;
            color: #e2e8f0 !important;
        }
        .main-sidebar { background: #1e293b !important; border-right: 1px solid rgba(255,255,255,0.05) !important; }
        .main-header { background: rgba(15,23,42,0.95) !important; backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.05) !important; }
        .content-wrapper { border: none !important; }
        
        .nav-sidebar .nav-link {
            color: #94a3b8 !important;
            border-radius: 0.75rem;
            margin: 2px 8px;
            transition: all 0.2s;
        }
        .nav-sidebar .nav-link:hover {
            color: #e2e8f0 !important;
            background: rgba(255,255,255,0.05) !important;
        }
        .nav-sidebar .nav-link.active {
            color: #0ea5e9 !important;
            background: rgba(14,165,233,0.1) !important;
        }
        .nav-sidebar .nav-link.active .nav-icon { color: #0ea5e9 !important; }
        .nav-sidebar .nav-header { color: #475569 !important; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 1rem 1.25rem 0.25rem; }
        
        .sidebar-dark-primary .main-sidebar .user-panel,
        .sidebar-dark-primary .main-sidebar .sidebar a:hover,
        .sidebar-dark-primary .main-sidebar .sidebar .user-panel,
        .sidebar-dark-primary .main-sidebar .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active { background: rgba(14,165,233,0.1) !important; }
        
        /* Cards */
        .card { background: #1e293b !important; border: 1px solid rgba(255,255,255,0.05); border-radius: 1rem; transition: all 0.3s; }
        .card:hover { border-color: rgba(14,165,233,0.15); }
        .card-header { background: transparent !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; }
        .card-title { color: #f1f5f9 !important; }
        
        /* Tables */
        .table { color: #e2e8f0 !important; }
        .table thead th { background: transparent !important; color: #64748b !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }
        .table td { border-top: 1px solid rgba(255,255,255,0.03) !important; vertical-align: middle; }
        .table-hover tbody tr:hover { background: rgba(255,255,255,0.02) !important; }
        
        /* Forms */
        .form-control, .form-select {
            background: rgba(255,255,255,0.05) !important;
            border-color: rgba(255,255,255,0.1) !important;
            color: #f1f5f9 !important;
            border-radius: 0.75rem;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.08) !important;
            border-color: #0ea5e9 !important;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.15) !important;
            color: #f1f5f9 !important;
        }
        .form-control::placeholder { color: #475569 !important; }
        select.form-select option { background: #1e293b; color: #f1f5f9; }
        .form-label { color: #94a3b8 !important; }
        
        /* Buttons */
        .btn { border-radius: 0.75rem; font-weight: 600; transition: all 0.2s; }
        .btn:hover { transform: translateY(-1px); }
        .btn:active { transform: translateY(0) scale(0.98); }
        .btn-primary { background: linear-gradient(135deg, #0ea5e9, #0284c7); border: none; }
        .btn-primary:hover { box-shadow: 0 8px 20px -4px rgba(14,165,233,0.4); }
        .btn-success { background: linear-gradient(135deg, #22c55e, #16a34a); border: none; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); border: none; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); border: none; }
        .btn-secondary { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; }
        .btn-secondary:hover { background: rgba(255,255,255,0.1); color: #f1f5f9; }
        
        /* Pagination */
        .pagination .page-link { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.05); color: #94a3b8; }
        .pagination .page-item.active .page-link { background: #0ea5e9; border-color: #0ea5e9; }
        
        /* Badge */
        .badge-success { background: rgba(34,197,94,0.15); color: #22c55e; }
        .badge-warning { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .badge-danger { background: rgba(239,68,68,0.15); color: #ef4444; }
        .badge-info { background: rgba(14,165,233,0.15); color: #0ea5e9; }
        
        /* Modals */
        .modal-content { background: #1e293b !important; border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; }
        .modal-header { border-bottom: 1px solid rgba(255,255,255,0.05) !important; }
        .modal-footer { border-top: 1px solid rgba(255,255,255,0.05) !important; }
        .modal-title { color: #f1f5f9 !important; }
        .btn-close { filter: invert(1); }
        
        /* Small Box */
        .small-box { border-radius: 1rem; transition: all 0.3s; }
        .small-box:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -10px rgba(0,0,0,0.4); }
        .small-box .inner h3 { color: #f1f5f9; }
        .small-box .inner p { color: #94a3b8; }
        
        /* Animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        @keyframes toast-in { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toast-out { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100%); } }
        .animate-fade-in-up { animation: fadeInUp 0.5s cubic-bezier(0.16,1,0.3,1) forwards; }
        .skeleton { background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 0.5rem; }
        .toast { animation: toast-in 0.4s cubic-bezier(0.16,1,0.3,1); min-width: 280px; }
        .toast.removing { animation: toast-out 0.3s ease forwards; }
        .stagger-1 { animation-delay: 0.05s; }
        .stagger-2 { animation-delay: 0.1s; }
        .stagger-3 { animation-delay: 0.15s; }
        .stagger-4 { animation-delay: 0.2s; }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .main-sidebar, .main-header, .main-footer { display: none !important; }
            .content-wrapper { margin: 0 !important; padding: 0 !important; }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        
        /* ===== LIGHT MODE ===== */
        body:not(.dark-mode), .body:not(.dark-mode) { background: #f1f5f9 !important; color: #0f172a !important; }
        body:not(.dark-mode) .main-sidebar { background: #ffffff !important; border-right-color: #e2e8f0 !important; }
        body:not(.dark-mode) .main-header { background: rgba(255,255,255,0.95) !important; border-bottom-color: #e2e8f0 !important; }
        body:not(.dark-mode) .content-wrapper { background: #f1f5f9 !important; }
        body:not(.dark-mode) .nav-sidebar .nav-link { color: #475569 !important; }
        body:not(.dark-mode) .nav-sidebar .nav-link:hover { background: #f1f5f9 !important; color: #0f172a !important; }
        body:not(.dark-mode) .nav-sidebar .nav-link.active { background: rgba(14,165,233,0.08) !important; color: #0ea5e9 !important; }
        body:not(.dark-mode) .nav-sidebar .nav-header { color: #94a3b8 !important; }
        body:not(.dark-mode) .card { background: #ffffff !important; border-color: #e2e8f0; }
        body:not(.dark-mode) .card-header { border-bottom-color: #e2e8f0 !important; }
        body:not(.dark-mode) .card-title { color: #0f172a !important; }
        body:not(.dark-mode) .table thead th { color: #64748b !important; border-bottom-color: #e2e8f0 !important; }
        body:not(.dark-mode) .table td { border-top-color: #f1f5f9 !important; }
        body:not(.dark-mode) .table-hover tbody tr:hover { background: #f8fafc !important; }
        body:not(.dark-mode) .form-control, body:not(.dark-mode) .form-select { background: #f8fafc !important; border-color: #e2e8f0 !important; color: #0f172a !important; }
        body:not(.dark-mode) .small-box .inner h3 { color: #0f172a; }
        body:not(.dark-mode) .small-box .inner p { color: #64748b; }
        body:not(.dark-mode) .pagination .page-link { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }
        body:not(.dark-mode) .modal-content { background: #ffffff !important; }
        body:not(.dark-mode) select.form-select option { background: #ffffff; color: #0f172a; }
    </style>
    
    <?= $extraStyles ?? '' ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light' ? '' : 'dark-mode' ?>">
<div class="wrapper">

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark no-print">
    <!-- Left: sidebar toggle -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars text-lg"></i>
            </a>
        </li>
    </ul>
    
    <!-- Center: Page title (mobile) -->
    <div class="navbar-nav mx-auto d-lg-none">
        <span class="navbar-text font-bold text-sm"><?= $pageTitle ?? ucfirst(str_replace('_',' ',$currentPage)) ?></span>
    </div>
    
    <!-- Right -->
    <ul class="navbar-nav ml-auto">
        <!-- Theme Toggle -->
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="toggleTheme()" title="Ganti Tema">
                <i class="fas fa-moon" id="theme-icon-dark" style="<?= ($_COOKIE['theme'] ?? 'dark') === 'dark' ? '' : 'display:none' ?>"></i>
                <i class="fas fa-sun" id="theme-icon-light" style="<?= ($_COOKIE['theme'] ?? 'dark') === 'light' ? '' : 'display:none' ?>"></i>
            </a>
        </li>
        <!-- Notifications -->
        <li class="nav-item dropdown">
            <a class="nav-link" href="#">
                <i class="fas fa-bell"></i>
                <?php if ($notifCount > 0): ?>
                <span class="badge badge-danger navbar-badge" style="font-size:10px"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <!-- User Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <div class="d-flex align-items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-xs font-bold">
                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline text-sm"><?= $user['nama'] ?></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right" style="background:#1e293b; border:1px solid rgba(255,255,255,0.1)">
                <div class="dropdown-header">
                    <p class="text-sm font-bold text-white mb-0"><?= $user['nama'] ?></p>
                    <p class="text-xs text-gray-400 capitalize"><?= $user['role'] ?></p>
                </div>
                <div class="dropdown-divider" style="border-color:rgba(255,255,255,0.05)"></div>
                <a href="<?= BASE_URL ?>/pengaturan.php" class="dropdown-item" style="color:#94a3b8">
                    <i class="fas fa-cog mr-2"></i> Pengaturan
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="dropdown-item" style="color:#f87171">
                    <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-primary no-print">
    <a href="<?= BASE_URL ?>/dashboard.php" class="brand-link d-flex align-items-center px-3 py-3">
        <div class="brand-image img-circle elevation-1" style="width:36px;height:36px;background:linear-gradient(135deg,#0ea5e9,#0284c7);display:flex;align-items:center;justify-content:center;font-size:16px;color:white">
            <i class="fas fa-wrench"></i>
        </div>
        <span class="brand-text font-bold ml-2" style="color:#f1f5f9;font-size:0.95rem"><?= $settings['nama_bengkel'] ?></span>
    </a>

    <div class="sidebar">
        <!-- User Panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex" style="border-bottom:1px solid rgba(255,255,255,0.05)">
            <div class="image">
                <div class="w-35 h-35 rounded-circle bg-gradient-to-br from-accent-500 to-accent-600 flex items-center justify-center text-white text-sm font-bold" style="width:35px;height:35px">
                    <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                </div>
            </div>
            <div class="info">
                <a href="#" class="d-block text-sm font-semibold" style="color:#f1f5f9"><?= $user['nama'] ?></a>
                <span class="text-xs capitalize" style="color:#64748b"><?= $user['role'] ?></span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <?php
                $lastGroup = '';
                foreach ($roleMenus as $menu):
                    if ($menu['group'] !== '' && $menu['group'] !== $lastGroup):
                        if ($lastGroup !== '') echo '</ul></li>';
                        echo '<li class="nav-header">' . strtoupper($menu['group']) . '</li>';
                        $lastGroup = $menu['group'];
                    endif;
                ?>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/<?= $menu['page'] ?>.php"
                       class="nav-link <?= $currentPage === $menu['page'] || basename($currentPage, '.php') === basename($menu['page'], '.php') || $currentPage === basename($menu['page'], '') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-<?= $menu['icon'] ?> text-sm"></i>
                        <p><?= $menu['label'] ?></p>
                    </a>
                </li>
                <?php endforeach; ?>
                
                <!-- Logout -->
                <li class="nav-header" style="margin-top:1rem">AKUN</li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/logout.php" class="nav-link" style="color:#f87171">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Keluar</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Content Wrapper -->
<div class="content-wrapper" style="min-height: 100vh">
    <div class="content p-3 p-md-4">
        <div class="container-fluid">

<!-- Toast Container -->
<div id="toast-container" class="fixed-top" style="right:16px;top:16px;z-index:9999;pointer-events:none">
</div>
