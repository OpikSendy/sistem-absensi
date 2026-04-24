<?php
// user/config.php
// Hanya konfigurasi — jangan letakkan helper atau session_start() di sini.

// DB (sesuaikan dengan XAMPP)
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan jika ada password
define('DB_NAME', 'db_absensi_ks');

// === BASE_URL (hard-coded ke folder project)
// NOTE: pastikan folder project di webroot adalah /sistem-absensi
// Jika kamu pindah folder, ubah string ini sesuai folder baru.
// Ini akan di-override oleh auto-detect di helpers.php jika tidak didefinisikan.
if (!defined('BASE_URL')) define('BASE_URL', '/sistem-absensi/');

// Default timezone untuk aplikasi
// Ini akan dipanggil di includes/bootstrap.php
// date_default_timezone_set('Asia/Jakarta'); // Pindahkan ke bootstrap.php