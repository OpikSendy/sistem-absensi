<?php
// user/upload_avatar.php
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'msg' => 'Login required']);
    exit;
}

$user_id = (int)($_POST['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
if ($user_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'User tidak valid']);
    exit;
}

// permission: hanya owner atau admin boleh upload foto profil
if (!is_admin() && $user_id !== (int)($_SESSION['user']['id'] ?? 0)) {
    echo json_encode(['ok' => false, 'msg' => 'Tidak punya izin']);
    exit;
}

// CSRF check jika kamu punya helper csrf_ok_post()
if (function_exists('csrf_ok_post') && !csrf_ok_post()) {
    echo json_encode(['ok' => false, 'msg' => 'CSRF token tidak valid']);
    exit;
}

if (empty($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    echo json_encode(['ok' => false, 'msg' => 'File tidak ditemukan']);
    exit;
}

// Validasi file
$maxSize = 5 * 1024 * 1024; // 5MB
if ($_FILES['avatar']['size'] > $maxSize) {
    echo json_encode(['ok' => false, 'msg' => 'File terlalu besar (maks 5MB)']);
    exit;
}

$info = @getimagesize($_FILES['avatar']['tmp_name']);
if ($info === false) {
    echo json_encode(['ok' => false, 'msg' => 'Bukan file gambar yang valid']);
    exit;
}

$allowedExt = ['jpg','jpeg','png','webp'];
$origName = $_FILES['avatar']['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    $ext = 'jpg'; // fallback
}

// ambil username + jurusan untuk nama file (safe)
$conn = db();
$stmt = $conn->prepare("SELECT username, jurusan, foto FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$username_for_file = $userRow['username'] ?? 'user' . $user_id;
$jurusan_for_file = $userRow['jurusan'] ?? '';

function safe_slug($s) {
    $s = preg_replace('/[^A-Za-z0-9\-_]/', '_', trim($s));
    $s = preg_replace('/_+/', '_', $s);
    return substr($s, 0, 60);
}

$fname = date('YmdHis') . '_' . safe_slug($username_for_file) . '_' . safe_slug($jurusan_for_file) . '.' . $ext;
$targetDir = ROOT . '/uploads/avatars';
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0755, true);
}
$targetPath = $targetDir . '/' . $fname;

// pindahkan file
if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
    echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan file']);
    exit;
}

// delete old file (jika ada dan berada di folder uploads)
$oldFoto = $userRow['foto'] ?? null;
if (!empty($oldFoto)) {
    // jika path relatif seperti 'uploads/avatars/...' -> hapus
    $possible = ROOT . '/' . ltrim($oldFoto, '/');
    if (is_file($possible)) {
        @unlink($possible);
    }
}

// Simpan path relatif ke DB (misal 'uploads/avatars/2025...jpg')
$relPath = 'uploads/avatars/' . $fname;
$stmt2 = $conn->prepare("UPDATE users SET foto = ? WHERE id = ?");
$stmt2->bind_param('si', $relPath, $user_id);
$ok = $stmt2->execute();
$stmt2->close();

if (!$ok) {
    // rollback file
    @unlink($targetPath);
    echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan path ke DB']);
    exit;
}

// return success + url
$url = (function_exists('url') ? url($relPath) : '/' . $relPath);
echo json_encode(['ok' => true, 'msg' => 'Upload berhasil', 'url' => $url, 'path' => $relPath]);
exit;
