<?php
// ============================================================
// config/functions.php - Fungsi Utilitas
// ============================================================

function checkLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /Bengkel POS/index.php');
        exit;
    }
}

function checkRole($role) {
    if ($_SESSION['role'] !== $role) {
        header('Location: /Bengkel POS/dashboard.php');
        exit;
    }
}

function generateInvoice($prefix = 'INV') {
    return $prefix . date('ymd') . rand(1000, 9999);
}

function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tgl) {
    return date('d/m/Y', strtotime($tgl));
}

function bulanIndo($angka) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return $bulan[(int)$angka];
}

function tglIndo($tgl) {
    $time = strtotime($tgl);
    $t = date('j', $time);
    $b = bulanIndo(date('n', $time));
    $th = date('Y', $time);
    return "$t $b $th";
}

function activeMenu($page) {
    $current = basename($_SERVER['PHP_SELF']);
    if ($current === $page) return 'active';
    // Check parent folder
    $folder = basename(dirname($_SERVER['PHP_SELF']));
    if ($folder === $page) return 'active';
    return '';
}

function isMenuActive($pages) {
    $current = basename($_SERVER['PHP_SELF']);
    foreach ($pages as $p) {
        if ($current === $p) return true;
    }
    return false;
}
