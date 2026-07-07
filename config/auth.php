<?php
/**
 * AUTHENTICATION — Bengkel Pro V1
 */

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function isOwner() { return hasRole('owner'); }
function isAdmin() { return hasRole('admin') || isOwner(); }
function isKasir() { return hasRole('kasir') || isAdmin(); }
function isMekanik() { return hasRole('mekanik'); }
function isGudang() { return hasRole('gudang') || isAdmin(); }

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'nama'     => $_SESSION['user_nama'] ?? '',
        'role'     => $_SESSION['user_role'] ?? '',
    ];
}

function doLogin($username, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u AND password = MD5(:p) AND status = 'aktif' LIMIT 1");
    $stmt->execute(['u' => $username, 'p' => $password]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['user_nama']  = $user['nama'];
        $_SESSION['user_role']  = $user['role'];
        logActivity('login', 'User logged in');
        return true;
    }
    return false;
}

function doLogout() {
    logActivity('logout', 'User logged out');
    session_unset();
    session_destroy();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (is_array($roles)) {
        $user = getCurrentUser();
        if (!in_array($user['role'], $roles) && !isOwner()) {
            http_response_code(403);
            die('Akses ditolak.');
        }
    }
}

function getAppConfig($key) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT config_value FROM app_config WHERE config_key = :key LIMIT 1");
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();
        if ($row) {
            return json_decode($row['config_value'], true);
        }
    } catch (Exception $e) {}
    return null;
}

function logActivity($action, $description = '') {
    try {
        $db = getDB();
        $user = getCurrentUser();
        if (!$user) return;
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, username, role, action, description, ip_address) VALUES (:uid, :uname, :role, :act, :desc, :ip)");
        $stmt->execute([
            'uid' => $user['id'], 'uname' => $user['username'], 'role' => $user['role'],
            'act' => $action, 'desc' => $description, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (Exception $e) {}
}

function getAppSettings() {
    static $settings = null;
    if ($settings === null) {
        $settings = [
            'nama_bengkel'  => getAppConfig('nama_bengkel') ?? 'Bengkel Motor Pro',
            'alamat_bengkel' => getAppConfig('alamat_bengkel') ?? '',
            'no_telp'       => getAppConfig('no_telp_bengkel') ?? '',
            'pajak'         => getAppConfig('pajak_persen') ?? 11,
            'diskon_default' => getAppConfig('diskon_default') ?? 0,
        ];
    }
    return $settings;
}
