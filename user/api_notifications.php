<?php
// user/api_notifications.php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$conn = db();
$userId = (int)($_SESSION['user']['id'] ?? 0);
if (!$userId) {
    echo json_encode(['ok' => false, 'msg' => 'User not found']);
    exit;
}

// Ambil notifikasi belum dibaca
$stmt = $conn->prepare("SELECT id, absensi_id, type, title, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY id ASC");
if (!$stmt) {
    echo json_encode(['ok' => false, 'msg' => 'DB error']);
    exit;
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tandai sebagai dibaca (bulk)
if (!empty($res)) {
    $stmt2 = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    if ($stmt2) {
        $stmt2->bind_param('i', $userId);
        $stmt2->execute();
        $stmt2->close();
    }
}

echo json_encode(['ok' => true, 'notifications' => $res]);
exit;
