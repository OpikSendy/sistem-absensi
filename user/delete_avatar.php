<?php
// user/delete_avatar.php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'msg' => 'Login required']); exit;
}

$user_id = (int)($_POST['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
if ($user_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'User tidak valid']); exit;
}

// permission
if (!is_admin() && $user_id !== (int)($_SESSION['user']['id'] ?? 0)) {
    echo json_encode(['ok' => false, 'msg' => 'Tidak punya izin']); exit;
}

// CSRF check
if (function_exists('csrf_ok_post') && !csrf_ok_post()) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF token tidak valid']);
    exit;
}

$conn = db();
$stmt = $conn->prepare("SELECT foto FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (empty($row['foto'])) {
    echo json_encode(['ok' => false, 'msg' => 'Foto tidak ditemukan']); exit;
}

$absPath = ROOT . '/' . ltrim($row['foto'], '/');
if (is_file($absPath)) @unlink($absPath);

$stmt2 = $conn->prepare("UPDATE users SET foto = NULL WHERE id = ?");
$stmt2->bind_param('i', $user_id);
$ok = $stmt2->execute();
$stmt2->close();

if ($ok) {
    echo json_encode(['ok' => true, 'msg' => 'Foto dihapus']);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Gagal mengubah DB']);
}
exit;
