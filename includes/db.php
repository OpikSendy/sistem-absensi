<?php
// includes/db.php
// Pastikan user/config.php sudah di-include sebelumnya (karena ada konstanta DB)

if (file_exists(__DIR__ . '/../user/config.php')) {
    require_once __DIR__ . '/../user/config.php';
}

if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_NAME')) {
    http_response_code(500);
    echo "DB config belum benar. Periksa user/config.php";
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (\mysqli_sql_exception $e) {
    http_response_code(500);
    echo "Gagal konek ke database: " . htmlspecialchars($e->getMessage());
    exit;
}

// compatibility: some files used $conn
$conn = $mysqli;

// Pastikan fungsi db() tersedia secara global
if (!function_exists('db')) {
    function db(): mysqli { // Mengubah return type menjadi mysqli
        global $mysqli; // Menggunakan global $mysqli
        return $mysqli;
    }
}