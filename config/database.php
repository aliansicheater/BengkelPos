<?php
/**
 * DATABASE CONNECTION — Bengkel Pro V1
 * PDO MySQL
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'db_bengkelpro');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base URL
define('BASE_URL', '/Aplikasi Bengkel Pro V1');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['status' => 'error', 'message' => 'DB connection failed']));
        }
    }
    return $pdo;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function generateId() {
    return bin2hex(random_bytes(16));
}

function sanitize($input) {
    if (is_array($input)) return array_map('sanitize', $input);
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatRupiah($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function generateCode($prefix, $table, $column, $length = 5) {
    $db = getDB();
    $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING($column, " . (strlen($prefix)+1) . ") AS UNSIGNED)) AS last_num FROM $table WHERE $column LIKE :prefix");
    $stmt->execute(['prefix' => $prefix . '%']);
    $row = $stmt->fetch();
    $next = ($row['last_num'] ?? 0) + 1;
    return $prefix . str_pad($next, $length, '0', STR_PAD_LEFT);
}
