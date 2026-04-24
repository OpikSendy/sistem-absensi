<?php
// user/dashboard.php (User Dashboard - Tampilan Modern)
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in()) {
    redirect(url('user/login.php'));
}

$hide_search = true;
$active_menu = 'dashboard';
$load_chart_js = true;

// Load Data Logic (JANGAN DIUBAH)
require_once __DIR__ . '/../includes/dashboard_data.php';

$page_title = "Dashboard User";
include __DIR__ . '/../includes/header.php';
?>

<main class="container-fluid px-4 py-4">
  
  <div class="row g-3 mb-4">
    <div class="col-12 col-xl-6">
      <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
        <div class="card-body d-flex flex-column p-4">
          <div class="z-1">
            <h4 class="fw-bold text-dark mb-1">Halo, <span class="text-primary"><?= e($nama) ?></span> 👋</h4>
            <p class="text-muted mb-4">Selamat datang kembali! Jangan lupa absen hari ini.</p>
            
            <div class="d-flex flex-wrap gap-2 mt-auto">
              <?php if(!$isAdmin): ?>
                <a href="<?= url('user/absensi.php') ?>" class="btn btn-primary px-4">
                  <i class="bi bi-camera me-2"></i>Absen Sekarang
                </a>
              <?php endif; ?>
              </div>
          </div>
          <i class="bi bi-fingerprint position-absolute text-light" style="font-size: 10rem; right: -20px; bottom: -40px; opacity: 0.1; transform: rotate(-20deg);"></i>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="row g-3 h-100">
        <div class="col-12 col-sm-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center text-center">
              <div class="icon-box icon-box-primary mb-2">
                <i class="bi bi-calendar-check"></i>
              </div>
              <div class="text-value mb-0"><?= number_format($totalAbsensi) ?></div>
              <div class="text-label text-muted small">Hadir (Bulan Ini)</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center text-center">
              <div class="icon-box icon-box-danger mb-2">
                <i class="bi bi-alarm"></i>
              </div>
              <div class="text-value mb-0"><?= number_format($totalTelat) ?></div>
              <div class="text-label text-muted small">Terlambat</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="card border-0 shadow-sm h-100 bg-primary text-white" style="background: linear-gradient(135deg, var(--primary), var(--primary-hover));">
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center text-center">
              <i class="bi bi-clock fs-3 mb-1 opacity-75"></i>
              <div id="liveClock" class="fw-bold fs-4">--:--</div>
              <div class="small opacity-75"><?= date('d M Y') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 pb-0 pt-3">
          <h6 class="fw-bold text-dark mb-0">Distribusi Kehadiran</h6>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 250px;">
          <div style="height: 200px; width: 100%;">
            <canvas id="donutStatus"></canvas>
          </div>
        </div>
        <div class="card-footer bg-white border-0 text-center pb-3">
           <small class="text-muted"><?= e($JAM_PATOKAN) ?></small>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 pb-0 pt-3">
          <h6 class="fw-bold text-dark mb-0"><?= e($barTitle) ?></h6>
        </div>
        <div class="card-body" style="min-height: 250px;">
          <div style="height: 220px; width: 100%;">
            <canvas id="barDisiplin"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-bottom">
          <h6 class="fw-bold text-dark m-0"><i class="bi bi-trophy me-2 text-warning"></i>Top 5 Disiplin</h6>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr><th class="ps-4 text-secondary">Nama Karyawan</th><th class="text-end pe-4 text-secondary">Ontime %</th></tr>
            </thead>
            <tbody>
              <?php if(empty($topUsers)): ?>
                <tr><td colspan="2" class="text-center text-muted py-4">Belum ada data cukup.</td></tr>
              <?php else: foreach($topUsers as $idx => $t): ?>
                <tr>
                  <td class="ps-4 fw-medium text-dark">
                    <span class="badge bg-light text-dark border me-2"><?= $idx+1 ?></span>
                    <?= e($t['nama'] ?: 'User') ?>
                  </td>
                  <td class="text-end pe-4">
                    <span class="badge badge-soft-success rounded-pill"><?= $t['pct']===null ? '0.0' : number_format($t['pct'],1) ?>%</span>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-bottom">
          <h6 class="fw-bold text-dark m-0"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Status Approval</h6>
        </div>
        <div class="card-body">
          <div class="mb-4">
            <div class="text-label text-muted mb-2">Bulan Ini</div>
            <div class="row g-2">
              <div class="col-4">
                <div class="p-2 border rounded-3 text-center bg-light">
                  <div class="small text-muted">Disetujui</div>
                  <div class="fw-bold text-success fs-5"><?= $count_approved_month ?? 0 ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded-3 text-center bg-light">
                  <div class="small text-muted">Ditolak</div>
                  <div class="fw-bold text-danger fs-5"><?= $count_rejected_month ?? 0 ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded-3 text-center bg-light">
                  <div class="small text-muted">Pending</div>
                  <div class="fw-bold text-warning fs-5"><?= $count_pending_month ?? 0 ?></div>
                </div>
              </div>
            </div>
          </div>
          
          <div>
            <div class="text-label text-muted mb-2">Minggu Ini</div>
            <div class="row g-2">
              <div class="col-4">
                <div class="p-2 border rounded-3 text-center bg-light">
                  <div class="fw-bold text-success"><?= $count_approved_week ?? 0 ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded-3 text-center bg-light">
                  <div class="fw-bold text-danger"><?= $count_rejected_week ?? 0 ?></div>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded-3 text-center bg-light">
                  <div class="fw-bold text-warning"><?= $count_pending_week ?? 0 ?></div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <?php if($isAdmin && !empty($latest)): ?>
  <div class="row mt-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="fw-bold text-dark m-0">Aktivitas Terbaru</h6>
          <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-sm btn-link text-decoration-none">Lihat Semua</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 text-secondary">Nama</th>
                <th class="text-secondary">Waktu</th>
                <th class="text-secondary">Jenis</th>
                <th class="text-secondary">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($latest as $l): ?>
              <tr>
                <td class="ps-4 fw-medium text-dark"><?= e($l['nama']) ?></td>
                <td class="text-muted small"><?= date('d M, H:i', strtotime($l['waktu'])) ?></td>
                <td>
                  <span class="badge <?= $l['status']==='masuk'?'badge-soft-success':'badge-soft-primary' ?>">
                    <?= ucfirst($l['status']) ?>
                  </span>
                </td>
                <td>
                  <?php 
                    $st = strtolower($l['approval_status']);
                    $bg = $st=='disetujui'?'success':($st=='ditolak'?'danger':'warning');
                  ?>
                  <span class="badge badge-soft-<?= $bg ?>"><?= e($l['approval_status']) ?></span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="text-center text-muted small mt-5">
    &copy; <?= date('Y') ?> Evolution IT System
  </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  // Data untuk Chart (dari PHP)
  const DONUT_DATA = <?= json_encode($donutData, JSON_UNESCAPED_UNICODE) ?>;
  const BAR_DATA = {
    labels: <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>,
    ontime: <?= json_encode($barOnt, JSON_UNESCAPED_UNICODE) ?>,
    telat: <?= json_encode($barTel, JSON_UNESCAPED_UNICODE) ?>
  };

  document.addEventListener('DOMContentLoaded', function() {
    // Init Charts menggunakan helper global di main.js
    if (typeof window.buildDonut === 'function') {
      window.buildDonut('donutStatus', DONUT_DATA.labels, DONUT_DATA.data);
    }
    if (typeof window.buildBar === 'function') {
      const datasets = [
        { label: 'On-time', data: BAR_DATA.ontime, backgroundColor: '#10b981' },
        { label: 'Telat', data: BAR_DATA.telat, backgroundColor: '#ef4444' }
      ];
      window.buildBar('barDisiplin', BAR_DATA.labels, datasets, true);
    }
  });
</script>