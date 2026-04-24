<?php
// includes/bootstrap.php
// Central bootstrap: load config, helpers, db. TIDAK melakukan redirect otomatis.

// Set default timezone (penting untuk konsistensi waktu)
date_default_timezone_set('Asia/Jakarta');

// Define ROOT constant
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

// load config first (defines DB constants & BASE_URL)
if (file_exists(ROOT . '/user/config.php')) {
    require_once ROOT . '/user/config.php';
} else {
    http_response_code(500);
    echo "user/config.php tidak ditemukan. Pastikan file konfigurasi database ada.";
    exit;
}

// load centralized helpers (url(), asset(), is_logged_in(), etc.)
// This file also handles session_start()
if (file_exists(ROOT . '/user/helpers.php')) {
    require_once ROOT . '/user/helpers.php';
} else {
    http_response_code(500);
    echo "user/helpers.php tidak ditemukan.";
    exit;
}

// load db connection
if (file_exists(ROOT . '/includes/db.php')) {
    require_once ROOT . '/includes/db.php';
} else {
    http_response_code(500);
    echo "includes/db.php tidak ditemukan.";
    exit;
}

// Set some globals for convenience in views/controllers
$user_data = current_user();
$userId  = (int)($user_data['id'] ?? 0);
$nama    = $user_data['nama'] ?? $user_data['username'] ?? 'User';
$role    = $user_data['role'] ?? 'user';
$isAdmin = ($user_data['role'] ?? '') === 'admin';


// ======= paste di includes/bootstrap.php (di bagian helper global) =======
if (!function_exists('get_client_ip')) {
    /**
     * Mengambil IP client dengan prioritas header proxy (jika ada).
     * Mengembalikan string (selalu ada), maksimal 100 karakter sesuai field DB.
     */
    function get_client_ip(): string {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR', // bisa berisi daftar IP, pakai yang pertama
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $k) {
            if (empty($_SERVER[$k])) continue;
            $ip = $_SERVER[$k];

            // Jika header X_FORWARDED_FOR ada, gunakan ip pertama di daftar
            if ($k === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $ip);
                $ip = trim($parts[0]);
            } else {
                $ip = trim($ip);
            }

            // Validasi minimal; terima juga private IP (localhost)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                // potong sesuai kolom DB
                return substr($ip, 0, 100);
            }
        }

        // fallback aman
        return '0.0.0.0';
    }
}
// =======================================================================

// Note: Pages should call their own auth checks (e.g. if (!is_logged_in()) redirect(url('user/login.php')); )