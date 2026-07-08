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
