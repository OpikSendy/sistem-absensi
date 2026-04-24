<?php
// user/helpers.php
// Optimized helpers: robust BASE_URL detection, safe csrf_field, misc helpers.
// Session start jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * BASE_URL
 * - Izinkan override dari user/config.php (jika ingin set manual)
 * - Jika current script berada di /user atau /admin, naikkkan 1 level supaya BASE_URL menunjuk root app
 */
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = rtrim(dirname($scriptName), '/\\');

    // Jika script sedang berada di subfolder umum aplikasi (user/admin/includes/api), naikkkan 1 level
    if (preg_match('#/(user|admin|includes|api)(/|$)#', $dir)) {
        $dir = dirname($dir);
    }

    if ($dir === '' || $dir === '.' || $dir === '/') {
        $base = '/';
    } else {
        $base = $dir . '/';
    }

    // Normalisasi: selalu awali dan akhiri dengan slash (kecuali root '/')
    if ($base !== '/') {
        $base = '/' . trim($base, '/') . '/';
    }

    define('BASE_URL', $base);
}

/* ---------- URL & asset helpers ---------- */
if (!function_exists('url')) {
    function url(string $p = ''): string {
        // kalau path sudah absolute (http://, https://, atau protocol-relative //) -> return apa adanya
        if (preg_match('#^(https?:)?//#i', $p)) return $p;

        // jika kosong → return base (mis. url() => '/project/')
        $base = rtrim(BASE_URL, '/');
        if ($p === '') return $base . '/';

        $path = ltrim($p, '/');
        return $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $p = ''): string {
        return url('assets/' . ltrim($p, '/'));
    }
}

/* ---------- HTML escape helper ---------- */
if (!function_exists('e')) {
    function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/* ---------- redirect helper ---------- */
if (!function_exists('redirect')) {
    function redirect(string $to){
        header('Location: ' . $to);
        exit;
    }
}

/* ---------- auth helpers ---------- */
if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return !empty($_SESSION['user']);
    }
}
if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return (is_logged_in() && ($_SESSION['user']['role'] ?? '') === 'admin');
    }
}
if (!function_exists('current_user')) {
    function current_user(): ?array {
        return $_SESSION['user'] ?? null;
    }
}

/* ---------- CSRF helpers ---------- */
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['_csrf'];
    }
}
if (!function_exists('csrf_field')) {
    // Mengembalikan input token valid (tanpa backslash yang tidak perlu)
    function csrf_field(): string {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
if (!function_exists('csrf_ok_post')) {
    function csrf_ok_post(): bool {
        return !empty($_POST['_token']) && hash_equals($_SESSION['_csrf'] ?? '', (string)($_POST['_token'] ?? ''));
    }
}

/* ---------- get_client_ip (robust) ---------- */
if (!function_exists('get_client_ip')) {
    function get_client_ip(): string {
        $keys = [
            'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'
        ];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = $_SERVER[$k];
                if ($k === 'HTTP_X_FORWARDED_FOR') {
                    $parts = explode(',', $ip);
                    $ip = trim($parts[0]);
                } else {
                    $ip = trim($ip);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) return substr($ip, 0, 100);
            }
        }
        return '0.0.0.0';
    }
}

/* ---------- time/format helpers ---------- */
if (!function_exists('fmtTime')) {
    function fmtTime($dt){ return $dt ? date('H:i', strtotime($dt)) : '—'; }
}
if (!function_exists('durasiHHMM')) {
    function durasiHHMM($start, $end){
        if(!$start || !$end) return '—';
        $diff = strtotime($end) - strtotime($start);
        if ($diff < 0) return '—';
        $h = floor($diff/3600); $m = floor(($diff%3600)/60);
        return sprintf('%02d:%02d', $h, $m);
    }
}

/* ---------- normalize approval ---------- */
if (!function_exists('normalize_approval')) {
    function normalize_approval(string $s): string {
        $s = trim(strtolower($s));
        if (in_array($s, ['disetujui','approve','approved','ok'])) return 'Disetujui';
        if (in_array($s, ['ditolak','reject','rejected'])) return 'Ditolak';
        return 'Pending';
    }
}
