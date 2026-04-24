<?php
// includes/sidebar.php
require_once __DIR__ . '/bootstrap.php';


$pendingCount = 0;
if ($isAdmin) {
    $stmt = db()->prepare("SELECT COUNT(1) AS cnt FROM absensi WHERE approval_status = 'Pending'");
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $pendingCount = (int)($res['cnt'] ?? 0);
    $stmt->close();
}
?>
<!-- Sidebar Desktop -->
<aside class="sidebar d-none d-lg-flex flex-column bg-dark text-light" style="width: 78px; min-width: 78px; height: 100vh; position: sticky; top: 0; z-index: 1030;">
  <a href="<?= url(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php') ?>" class="rail-item <?= ($active_menu === 'dashboard') ? 'active' : '' ?>" title="Dashboard">
    <span class="rail-dot"><i class="bi bi-house"></i></span>
    <span class="rail-label">Dashboard</span>
  </a>

  <?php if ($isAdmin): ?>
    <a href="<?= url('admin/form_aksi.php') ?>" class="rail-item <?= ($active_menu === 'aksi') ? 'active' : '' ?>" title="Kelola Aksi">
      <span class="rail-dot"><i class="bi bi-gear"></i></span>
      <span class="rail-label">Kelola User
        <?php if ($pendingCount > 0): ?>
          <span class="badge bg-warning ms-auto"><?= $pendingCount ?></span>
        <?php endif; ?>
      </span>
    </a>
    <a href="<?= url('admin/user_manage.php') ?>" class="rail-item <?= ($active_menu === 'users') ? 'active' : '' ?>" title="Kelola User">
      <span class="rail-dot"><i class="bi bi-people"></i></span>
      <span class="rail-label">Kelola User</span>
    </a>
    <a href="<?= url('admin/shift_manage.php') ?>" class="rail-item <?= ($active_menu === 'shifts') ? 'active' : '' ?>" title="Kelola Shift">
      <span class="rail-dot"><i class="bi bi-clock-history"></i></span>
      <span class="rail-label">Kelola Shift</span>
    </a>
    <a href="<?= url('admin/tugas_manage.php') ?>" class="rail-item <?= ($active_menu === 'tugas') ? 'active' : '' ?>" title="Kelola Tugas">
      <span class="rail-dot"><i class="bi bi-list-task"></i></span>
      <span class="rail-label">Kelola Tugas</span>
    </a>
  <?php else: ?>
    <a href="<?= url('user/absensi.php') ?>" class="rail-item <?= ($active_menu === 'absensi') ? 'active' : '' ?>" title="Absen">
      <span class="rail-dot"><i class="bi bi-fingerprint"></i></span>
      <span class="rail-label">Absen</span>
    </a>
    <a href="<?= url('#') ?>" class="rail-item <?= ($active_menu === 'laporan') ? 'active' : '' ?>" title="Laporan">
      <span class="rail-dot"><i class="bi bi-clipboard-data"></i></span>
      <span class="rail-label">Laporan</span>
    </a>
  <?php endif; ?>

  <div class="spacer flex-grow-1"></div>

  <button class="rail-item btn p-0 text-start" data-bs-toggle="offcanvas" data-bs-target="#ocSettings" title="Pengaturan">
    <span class="rail-dot"><i class="bi bi-gear"></i></span>
    <span class="rail-label">Pengaturan</span>
  </button>

  <a href="<?= url('user/logout.php') ?>" class="rail-item mt-2" title="Logout">
    <span class="rail-dot"><i class="bi bi-box-arrow-right"></i></span>
    <span class="rail-label">Logout</span>
  </a>
</aside>

<!-- Sidebar Mobile Offcanvas -->
<div class="offcanvas offcanvas-start offcanvas-sidebar bg-dark text-light" tabindex="-1" id="ocSidebar" aria-labelledby="ocSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="ocSidebarLabel">Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column p-0">
    <a href="<?= url(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php') ?>" class="rail-item <?= ($active_menu === 'dashboard') ? 'active' : '' ?>">
      <span class="rail-dot"><i class="bi bi-house"></i></span>
      <span class="rail-label">Dashboard</span>
    </a>
    <?php if ($isAdmin): ?>
      <a href="<?= url('admin/form_aksi.php') ?>" class="rail-item <?= ($active_menu === 'aksi') ? 'active' : '' ?>">
        <span class="rail-dot"><i class="bi bi-gear"></i></span>
        <span class="rail-label">Kelola Aksi
          <?php if ($pendingCount > 0): ?>
            <span class="badge bg-warning ms-auto"><?= $pendingCount ?></span>
          <?php endif; ?>
        </span>
      </a>
      <a href="<?= url('admin/user_manage.php') ?>" class="rail-item <?= ($active_menu === 'users') ? 'active' : '' ?>">
        <span class="rail-dot"><i class="bi bi-people"></i></span>
        <span class="rail-label">Kelola User</span>
      </a>
      <a href="<?= url('admin/shift_manage.php') ?>" class="rail-item <?= ($active_menu === 'shifts') ? 'active' : '' ?>">
        <span class="rail-dot"><i class="bi bi-clock-history"></i></span>
        <span class="rail-label">Kelola Shift</span>
      </a>
      <a href="<?= url('admin/tugas_manage.php') ?>" class="rail-item <?= ($active_menu === 'tugas') ? 'active' : '' ?>">
        <span class="rail-dot"><i class="bi bi-list-task"></i></span>
        <span class="rail-label">Kelola Tugas</span>
      </a>
    <?php else: ?>
      <a href="<?= url('user/absensi.php') ?>" class="rail-item <?= ($active_menu === 'absensi') ? 'active' : '' ?>">
        <span class="rail-dot"><i class="bi bi-fingerprint"></i></span>
        <span class="rail-label">Absen</span>
      </a>
      <a href="<?= url('#') ?>" class="rail-item <?= ($active_menu === 'laporan') ? 'active' : '' ?>">
        <span class="rail-dot"><i class="bi bi-clipboard-data"></i></span>
        <span class="rail-label">Laporan</span>
      </a>
    <?php endif; ?>
    <div class="spacer flex-grow-1"></div>
    <button class="rail-item btn p-0 text-start" data-bs-toggle="offcanvas" data-bs-target="#ocSettings">
      <span class="rail-dot"><i class="bi bi-gear"></i></span>
      <span class="rail-label">Pengaturan</span>
    </button>
    <a href="<?= url('user/logout.php') ?>" class="rail-item mt-2">
      <span class="rail-dot"><i class="bi bi-box-arrow-right"></i></span>
      <span class="rail-label">Logout</span>
    </a>
  </div>
</div>