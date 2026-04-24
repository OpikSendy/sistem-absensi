<?php
// Jalankan sekali lewat browser: /sistem-absensi/migration/hash_password.php
// Meng-hash kolom users.password kalau masih plaintext.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../user/config.php'; // Untuk konstanta DB

$conn = db(); // Menggunakan fungsi db()

$res = $conn->query("SELECT id, username, password FROM users");
$updated = 0; $skipped = 0;

while ($row = $res->fetch_assoc()) {
    $pwd = $row['password'];
    // Skip jika sudah hash bcrypt
    if (strlen($pwd) >= 60 && str_starts_with($pwd, '$2y$')) { $skipped++; continue; }
    $hash = password_hash($pwd, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param('si', $hash, $row['id']);
    $stmt->execute();
    $updated++;
}

echo "Selesai. Diupdate: {$updated}, dilewati (sudah hash): {$skipped}";
