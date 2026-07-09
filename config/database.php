<?php
// ============================================================
// config/database.php - Koneksi Database MySQLi
// ============================================================

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'db_bengkelpos';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:2rem;color:#dc2626;'>
        <h2>Koneksi Database Gagal</h2>
        <p>" . $conn->connect_error . "</p>
    </div>");
}

// ============================================================
// BASE_URL - Auto-detect apakah di root atau subfolder
// ============================================================
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$base_url = ($script_dir === '/' || $script_dir === '\\') ? '' : $script_dir;
define('BASE_URL', $base_url);
define('BASE_URL_SLASH', $base_url ? $base_url . '/' : '/');