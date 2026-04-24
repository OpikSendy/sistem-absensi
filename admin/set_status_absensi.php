<?php
// admin/set_status_absensi.php
require_once __DIR__ . '/../includes/bootstrap.php';
if (!is_logged_in() || !is_admin()) {
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'msg'=>'Forbidden']); exit;
    }
    redirect(url('user/403.php'));
}

$conn = db();

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$straw = $_POST['approval'] ?? $_GET['s'] ?? ($_POST['approval_status'] ?? null) ?? '';
$action_type = $_POST['action'] ?? $_GET['action'] ?? '';

// validasi id
if (!$id) {
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
        http_response_code(422);
        echo json_encode(['ok'=>false,'msg'=>'ID tidak valid']); exit;
    }
    $_SESSION['flash_err'] = 'ID tidak valid';
    redirect(url('admin/dashboard.php'));
}

// normalize approval (helper ada di includes/helpers.php)
$st = normalize_approval($straw);

// ambil data absensi dulu (untuk dapatkan user_id & waktu & foto)
$absRow = null;
$stmt_fetch = $conn->prepare("SELECT user_id, waktu, status, foto FROM absensi WHERE id = ? LIMIT 1");
if ($stmt_fetch) {
    $stmt_fetch->bind_param('i', $id);
    $stmt_fetch->execute();
    $absRow = $stmt_fetch->get_result()->fetch_assoc() ?: null;
    $stmt_fetch->close();
}
$targetUserId = $absRow['user_id'] ?? null;

// --- Helper internal: normalisasi dan cek keamanan path foto ---
function normalize_and_safe_foto(string $foto = null) : ?string {
    if (empty($foto) || $foto === '0') return null;
    // hapus whitespace
    $foto = trim($foto);
    // jika berawalan slash, hapus leading slash
    $foto_rel = ltrim($foto, '/');

    // jika path tidak mengandung uploads/ maka coba tambahkan 'uploads/' prefix
    if (stripos($foto_rel, 'uploads/') !== 0) {
        // jika string mengandung directory traversal -> tolak
        if (strpos($foto_rel, '..') !== false) return null;
        // fallback: treat as plain filename inside uploads
        $foto_rel = 'uploads/' . $foto_rel;
    }
    // normalize slashes
    $foto_rel = preg_replace('#/+#','/',$foto_rel);
    return $foto_rel;
}

// Handle delete action
if ($action_type === 'delete' || strtolower($straw) === 'delete') {
    // dapatkan foto (lagi) dan normalisasi
    $foto_to_delete_raw = $absRow['foto'] ?? null;
    $foto_rel = normalize_and_safe_foto($foto_to_delete_raw);

    // Mulai transaksi supaya operasi DB konsisten (opsional)
    $useTransaction = (bool)method_exists($conn, 'begin_transaction');
    if ($useTransaction) $conn->begin_transaction();

    $stmt = $conn->prepare("DELETE FROM absensi WHERE id=?");
    if (!$stmt) {
        if ($useTransaction) $conn->rollback();
        $err = $conn->error;
        if (is_ajax()) { echo json_encode(['ok'=>false,'msg'=>'Gagal menyiapkan query delete: '.$err]); exit; }
        $_SESSION['flash_err'] = 'Gagal menghapus absensi: '.$err;
        redirect($_SERVER['HTTP_REFERER'] ?? url('admin/dashboard.php'));
    }
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if ($ok) {
        if ($useTransaction) $conn->commit();
        // hanya unlink file jika path aman dan file ada di folder uploads/
        if ($foto_rel) {
            $absPath = ROOT . '/' . $foto_rel;
            // extra-safety: pastikan absPath berada di dalam ROOT/uploads
            $uploadsDir = realpath(ROOT . '/uploads');
            $fileReal = @realpath($absPath);
            if ($fileReal && $uploadsDir && strpos($fileReal, $uploadsDir) === 0 && is_file($fileReal)) {
                @unlink($fileReal);
            }
        }
    } else {
        if ($useTransaction) $conn->rollback();
    }

    // Response: AJAX or regular
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        echo json_encode(['ok'=>$ok, 'msg'=> $ok ? 'Absensi berhasil dihapus.' : 'Gagal menghapus absensi: '.$err ]);
        exit;
    }
    if ($ok) $_SESSION['flash_ok'] = 'Absensi berhasil dihapus.';
    else $_SESSION['flash_err'] = 'Gagal menghapus absensi: '.$err;
    $back = $_SERVER['HTTP_REFERER'] ?? url('admin/dashboard.php');
    redirect($back);
}

// Jika bukan delete -> Update approval_status seperti biasa
$stmt = $conn->prepare("UPDATE absensi SET approval_status=? WHERE id=?");
$stmt->bind_param('si', $st, $id);
$ok = $stmt->execute();
$err = $stmt->error;
$stmt->close();

// Jika update sukses dan statusnya 'Ditolak', buat notifikasi untuk user
if ($ok && $targetUserId && $st === 'Ditolak') {
    $timeStr = $absRow['waktu'] ?? null;
    $displayTime = $timeStr ? date('Y-m-d H:i', strtotime($timeStr)) : 'waktu tidak diketahui';
    $title = 'Absen Ditolak';
    $message = "Absen Anda pada {$displayTime} telah ditolak oleh admin.";
    $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, absensi_id, type, title, message) VALUES (?, ?, ?, ?, ?)");
    if ($stmt_notif) {
        $type = 'absensi_approval';
        $stmt_notif->bind_param('iisss', $targetUserId, $id, $type, $title, $message);
        $stmt_notif->execute();
        $stmt_notif->close();
    }
}

// Response JSON (AJAX) or redirect back
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
    echo json_encode(['ok'=>$ok,'msg'=>$ok ? 'Status approval berhasil diupdate.' : 'Gagal mengupdate approval: '.$err]);
    exit;
}
if ($ok) $_SESSION['flash_ok'] = 'Status approval berhasil diupdate.';
else $_SESSION['flash_err'] = 'Gagal mengupdate approval: '.$err;
redirect($_SERVER['HTTP_REFERER'] ?? url('admin/dashboard.php'));

// kecil: helper is_ajax (bisa ditempatkan di includes/helpers.php jika ingin permanen)
function is_ajax() {
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
}
