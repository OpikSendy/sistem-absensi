<?php
require_once __DIR__ . '/bootstrap.php';

$page_title = $page_title ?? 'Dashboard';

// Determine App Branding based on Role
$appBrand = 'Kesatriyan';
if (isset($isAdmin) && $isAdmin) {
    $appBrand = 'Kesatriyan Admin';
} elseif (is_logged_in()) {
    $appBrand = 'Kesatriyan Staff';
}

// Notification Counter
$pendingCount = 0;
if (isset($isAdmin) && $isAdmin) {
    $stmt = db()->prepare("SELECT COUNT(1) AS cnt FROM absensi WHERE approval_status = 'Pending'");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $pendingCount = (int)($res['cnt'] ?? 0);
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> | <?= e($appBrand) ?></title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  
  <?php if (!empty($load_chart_js)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <?php endif; ?>
</head>
<body>

<div class="app-layout">

  <aside class="sidebar-panel d-none d-lg-flex">
    <div class="p-4 border-bottom">
      <h6 class="fw-bold text-primary m-0 text-uppercase" style="letter-spacing: 0.5px;">
        <i class="bi bi-shield-check me-2"></i><?= e($appBrand) ?>
      </h6>
    </div>
    
    <nav class="flex-grow-1 py-3">
      <a href="<?= url(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php') ?>" class="rail-item <?= ($active_menu=='dashboard')?'active':'' ?>">
        <span class="rail-dot"><i class="bi bi-grid"></i></span> Dashboard
        <?php if($isAdmin && $pendingCount>0): ?>
          <span class="badge bg-warning text-dark ms-auto rounded-pill"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>

      <?php if ($isAdmin): ?>
        <div class="px-4 mt-3 mb-2 text-uppercase text-muted" style="font-size:0.75rem;font-weight:700">Administrasi</div>
        
        <a href="<?= url('admin/user_manage.php') ?>" class="rail-item <?= ($active_menu=='users')?'active':'' ?>">
          <span class="rail-dot"><i class="bi bi-people"></i></span> Data Karyawan
        </a>
        <a href="<?= url('admin/shift_manage.php') ?>" class="rail-item <?= ($active_menu=='shifts')?'active':'' ?>">
          <span class="rail-dot"><i class="bi bi-clock"></i></span> Shift Kerja
        </a>
        <a href="<?= url('admin/tugas_manage.php') ?>" class="rail-item <?= ($active_menu=='tugas')?'active':'' ?>">
          <span class="rail-dot"><i class="bi bi-list-check"></i></span> Master Tugas
        </a>
      <?php else: ?>
        <div class="px-4 mt-3 mb-2 text-uppercase text-muted" style="font-size:0.75rem;font-weight:700">Menu Utama</div>
        <a href="<?= url('user/absensi.php') ?>" class="rail-item <?= ($active_menu=='absensi')?'active':'' ?>">
          <span class="rail-dot"><i class="bi bi-camera"></i></span> Absensi
        </a>
        <a href="<?= url('user/403.php') ?>" class="rail-item <?= ($active_menu=='laporan')?'active':'' ?>">
          <span class="rail-dot"><i class="bi bi-file-text"></i></span> Laporan Saya
        </a>
      <?php endif; ?>
    </nav>

    <div class="p-3 border-top mt-auto">
      <a href="<?= url('user/logout.php') ?>" class="rail-item text-danger">
        <span class="rail-dot"><i class="bi bi-box-arrow-left"></i></span> Keluar
      </a>
    </div>
  </aside>

  <div class="offcanvas offcanvas-start" tabindex="-1" id="ocSidebar">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title fw-bold text-primary">
        <i class="bi bi-shield-check me-2"></i><?= e($appBrand) ?>
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    
    <div class="offcanvas-body p-0">
      <div class="py-3">
         <a href="<?= url(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php') ?>" class="rail-item <?= ($active_menu=='dashboard')?'active':'' ?>">
            <span class="rail-dot"><i class="bi bi-grid"></i></span> Dashboard
            <?php if($isAdmin && $pendingCount>0): ?>
              <span class="badge bg-warning text-dark ms-auto rounded-pill"><?= $pendingCount ?></span>
            <?php endif; ?>
         </a>

         <?php if ($isAdmin): ?>
            <div class="px-4 mt-3 mb-2 text-uppercase text-muted" style="font-size:0.75rem;font-weight:700">Administrasi</div>
            <a href="<?= url('admin/user_manage.php') ?>" class="rail-item <?= ($active_menu=='users')?'active':'' ?>">
              <span class="rail-dot"><i class="bi bi-people"></i></span> Data Karyawan
            </a>
            <a href="<?= url('admin/shift_manage.php') ?>" class="rail-item <?= ($active_menu=='shifts')?'active':'' ?>">
              <span class="rail-dot"><i class="bi bi-clock"></i></span> Shift Kerja
            </a>
            <a href="<?= url('admin/tugas_manage.php') ?>" class="rail-item <?= ($active_menu=='tugas')?'active':'' ?>">
              <span class="rail-dot"><i class="bi bi-list-check"></i></span> Master Tugas
            </a>
         <?php else: ?>
            <div class="px-4 mt-3 mb-2 text-uppercase text-muted" style="font-size:0.75rem;font-weight:700">Menu Utama</div>
            <a href="<?= url('user/absensi.php') ?>" class="rail-item <?= ($active_menu=='absensi')?'active':'' ?>">
              <span class="rail-dot"><i class="bi bi-camera"></i></span> Absensi
            </a>
            <a href="<?= url('user/403.php') ?>" class="rail-item <?= ($active_menu=='laporan')?'active':'' ?>">
              <span class="rail-dot"><i class="bi bi-file-text"></i></span> Laporan Saya
            </a>
         <?php endif; ?>

         <div class="border-top mt-4 pt-2">
            <a href="<?= url('user/logout.php') ?>" class="rail-item text-danger">
                <span class="rail-dot"><i class="bi bi-box-arrow-left"></i></span> Keluar
            </a>
         </div>
      </div>
    </div>
  </div>

  <div class="main-wrapper">
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <div class="content-scrollable">