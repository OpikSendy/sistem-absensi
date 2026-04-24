<?php
// includes/navbar.php
// PERBAIKAN: Menggunakan text-dark, menghapus navbar-dark, dan memperbaiki kontras tombol

$user_data = current_user();
$isAdmin = is_admin();
$nama = $user_data['nama'] ?? $user_data['username'] ?? 'User';
$current_user_id = (int)($user_data['id'] ?? 0);

// Avatar logic
$avatarUrl = "https://api.dicebear.com/8.x/initials/svg?seed=" . urlencode(substr($nama,0,2)?:'U');
if ($current_user_id > 0) {
    try {
        $conn = db();
        $stmt = $conn->prepare("SELECT foto FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $current_user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!empty($row['foto'])) {
                $fotoRaw = $row['foto'];
                $avatarUrl = function_exists('url') ? url($fotoRaw) : ((strpos($fotoRaw, 'http') === 0) ? $fotoRaw : '/' . ltrim($fotoRaw, '/'));
            }
        }
    } catch (Throwable $e) {}
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top" style="z-index: 1020;">
  <div class="container-fluid px-4">
    
    <button class="btn btn-outline-secondary d-lg-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#ocSidebar">
      <i class="bi bi-list fs-5"></i>
    </button>

    <div class="d-flex align-items-center">
        <h5 class="mb-0 text-dark fw-bold tracking-tight"><?= e($page_title ?? 'AbsensiPro') ?></h5>
    </div>

    <div class="ms-auto d-flex align-items-center gap-3">
      <?php if ($isAdmin): ?>
        <a href="<?= url('admin/form_aksi.php') ?>" class="btn btn-primary btn-sm d-none d-md-inline-flex align-items-center gap-2 shadow-sm">
          <i class="bi bi-plus-lg"></i> <span class="d-none d-lg-inline">User Baru</span>
        </a>
      <?php else: ?>
        <a href="<?= url('user/absensi.php') ?>" class="btn btn-primary btn-sm d-none d-md-inline-flex align-items-center gap-2 shadow-sm">
          <i class="bi bi-geo-alt-fill"></i> <span class="d-none d-lg-inline">Absen Sekarang</span>
        </a>
      <?php endif; ?>

      <div class="dropdown">
        <button class="btn btn-link text-decoration-none d-flex align-items-center gap-2 p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="<?= e($avatarUrl) ?>" alt="avatar" class="rounded-circle border shadow-sm" width="38" height="38" style="object-fit:cover" />
          <div class="d-none d-md-block text-start">
            <div class="fw-bold text-dark fs-7 lh-1"><?= e(substr($nama, 0, 15)) ?></div>
            <div class="text-muted fs-8 lh-1 mt-1"><?= $isAdmin ? 'Administrator' : 'Karyawan' ?></div>
          </div>
          <i class="bi bi-chevron-down text-muted ms-1 fs-7"></i>
        </button>
        
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 animate slideIn">
          <li><h6 class="dropdown-header">Akun Saya</h6></li>
          <li><a class="dropdown-item py-2" href="<?= url('user/profile.php?id=' . (int)$current_user_id) ?>"><i class="bi bi-person me-2 text-primary"></i> Profil Saya</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item py-2 text-danger" href="<?= url('user/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i> Keluar</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>