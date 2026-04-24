<?php
// user/absensi.php
// TAMPILAN BARU: Clean, Modern, & High Contrast

session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in()) {
    redirect(url('user/login.php'));
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$conn = db();
$userId = (int)($_SESSION['user']['id'] ?? 0);

// --- LOGIKA DATA (JANGAN DIUBAH) ---
$masukAt = null;
$pulangAt = null;
$riwayat = [];
$telatMenitHariIni = 0;
$canMasuk = true;
$canPulang = false;
$remaining_seconds = 0;

// 1. Ambil data Masuk
$sql = "SELECT waktu, telat_menit FROM absensi WHERE user_id = ? AND DATE(waktu) = CURDATE() AND status = 'masuk' ORDER BY waktu ASC LIMIT 1";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        $masukAt = $res['waktu'];
        $telatMenitHariIni = (int)($res['telat_menit'] ?? 0);
    }
    $stmt->close();
}

// 2. Ambil data Pulang
$sql = "SELECT waktu FROM absensi WHERE user_id = ? AND DATE(waktu) = CURDATE() AND status = 'pulang' ORDER BY waktu DESC LIMIT 1";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        $pulangAt = $res['waktu'];
    }
    $stmt->close();
}

// 3. Ambil Riwayat
$sql = "SELECT status, waktu, COALESCE(lokasi_text, '-') AS lokasi_text FROM absensi WHERE user_id = ? AND DATE(waktu) = CURDATE() ORDER BY waktu ASC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 4. Logika Izin Tombol (Countdown)
$canMasuk = !$masukAt;
$canPulang = false;
$remaining_seconds = 0;

if ($masukAt && !$pulangAt) {
    $wMasuk = DateTime::createFromFormat('Y-m-d H:i:s', $masukAt, new DateTimeZone('Asia/Jakarta'));
    if ($wMasuk) {
        $durasiMenitEffective = 480; // Default 8 jam

        // Cek Jadwal Khusus
        $sql = "SELECT COALESCE(sm.durasi_menit, 480) AS durasi_menit FROM user_jadwal uj LEFT JOIN shift_master sm ON sm.id = uj.shift_id WHERE uj.user_id = ? AND uj.tanggal = CURDATE() LIMIT 1";
        if ($st = $conn->prepare($sql)) {
            $st->bind_param('i', $userId);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            $st->close();
            if ($r && isset($r['durasi_menit'])) $durasiMenitEffective = (int)$r['durasi_menit'];
        }

        // Cek Shift Default
        if ($durasiMenitEffective === 480) {
            $sql2 = "SELECT COALESCE(sm.durasi_menit, 480) AS durasi_menit FROM user_shift us LEFT JOIN shift_master sm ON sm.id = us.shift_id WHERE us.user_id = ? AND us.aktif = 1 LIMIT 1";
            if ($st2 = $conn->prepare($sql2)) {
                $st2->bind_param('i', $userId);
                $st2->execute();
                $r2 = $st2->get_result()->fetch_assoc();
                $st2->close();
                if ($r2 && isset($r2['durasi_menit'])) $durasiMenitEffective = (int)$r2['durasi_menit'];
            }
        }

        if ($durasiMenitEffective <= 0) $durasiMenitEffective = 480;
        $requiredSec = $durasiMenitEffective * 60;
        $diffSec = time() - $wMasuk->getTimestamp();

        if ($diffSec >= $requiredSec) {
            $canPulang = true;
        } else {
            $remaining_seconds = max(0, $requiredSec - $diffSec);
            $canPulang = false;
        }
    }
}

$durasi = durasiHHMM($masukAt, $pulangAt);
$page_title = "Absensi Harian";
$active_menu = 'absensi';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Absensi Harian</h3>
            <p class="text-muted mb-0">Kelola jam masuk dan jam pulang Anda hari ini.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="badge bg-white text-dark border shadow-sm py-2 px-3">
                <i class="bi bi-calendar-check me-2 text-primary"></i> <?= date('l, d F Y') ?>
            </span>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_info'])): ?>
        <div class="alert alert-info border-0 shadow-sm mb-4"><i class="bi bi-info-circle me-2"></i><?= e($_SESSION['flash_info']); unset($_SESSION['flash_info']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_err'])): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= e($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark m-0">Status Kehadiran</h6>
                    
                    <div class="stepper">
                        <div class="step <?= $masukAt ? 'done' : 'active' ?>">
                            <span class="dot"></span> <span class="d-none d-sm-inline">Masuk</span>
                        </div>
                        <div class="step <?= ($masukAt && !$pulangAt) ? 'active' : ($pulangAt ? 'done' : '') ?>">
                            <span class="dot"></span> <span class="d-none d-sm-inline">Bekerja</span>
                        </div>
                        <div class="step <?= $pulangAt ? 'done' : '' ?>">
                            <span class="dot"></span> <span class="d-none d-sm-inline">Pulang</span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row text-center py-4">
                        <div class="col-4 border-end">
                            <div class="text-uppercase text-muted small fw-bold mb-1">Jam Masuk</div>
                            <div class="fs-2 fw-bold text-dark mb-0">
                                <?= $masukAt ? date('H:i', strtotime($masukAt)) : '--:--' ?>
                            </div>
                            <?php if ($telatMenitHariIni > 0): ?>
                                <span class="badge badge-soft-danger mt-2">Telat <?= $telatMenitHariIni ?>m</span>
                            <?php elseif ($masukAt): ?>
                                <span class="badge badge-soft-success mt-2">Tepat Waktu</span>
                            <?php else: ?>
                                <span class="badge badge-soft-secondary mt-2">Belum Absen</span>
                            <?php endif; ?>
                        </div>

                        <div class="col-4 border-end">
                            <div class="text-uppercase text-muted small fw-bold mb-1">Durasi Kerja</div>
                            <div class="fs-2 fw-bold text-primary mb-0"><?= $durasi ?></div>
                            <small class="text-muted">Jam : Menit</small>
                        </div>

                        <div class="col-4">
                            <div class="text-uppercase text-muted small fw-bold mb-1">Jam Pulang</div>
                            <div class="fs-2 fw-bold text-dark mb-0">
                                <?= $pulangAt ? date('H:i', strtotime($pulangAt)) : '--:--' ?>
                            </div>
                            <?php if ($pulangAt): ?>
                                <span class="badge badge-soft-success mt-2">Selesai</span>
                            <?php else: ?>
                                <span class="badge badge-soft-secondary mt-2">Menunggu</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-0">

                    <div class="row g-0">
                        <div class="col-12 col-md-6 border-end p-4">
                            <h6 class="fw-bold text-dark mb-2">Mulai Bekerja</h6>
                            <p class="text-muted small mb-3">Klik tombol di bawah ini untuk mencatat jam kehadiran Anda hari ini.</p>
                            
                            <?php if ($masukAt): ?>
                                <button class="btn btn-success w-100 py-2 disabled" disabled>
                                    <i class="bi bi-check-circle-fill me-2"></i> Sudah Absen Masuk
                                </button>
                            <?php else: ?>
                                <a href="<?= url('user/absen-masuk.php') ?>" class="btn btn-primary w-100 py-2" data-absen="in">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Absen Masuk
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="col-12 col-md-6 p-4">
                            <h6 class="fw-bold text-dark mb-2">Selesai Bekerja</h6>
                            <p class="text-muted small mb-3">
                                Pastikan durasi kerja terpenuhi sebelum melakukan checkout.
                            </p>

                            <?php if ($pulangAt): ?>
                                <button class="btn btn-secondary w-100 py-2 disabled" disabled>
                                    <i class="bi bi-check-circle-fill me-2"></i> Sudah Absen Pulang
                                </button>
                            <?php elseif (!$masukAt): ?>
                                <button class="btn btn-light border w-100 py-2 text-muted disabled" disabled>
                                    <i class="bi bi-lock-fill me-2"></i> Masuk Terlebih Dahulu
                                </button>
                            <?php else: ?>
                                <a href="<?= url('user/absen-pulang.php') ?>" class="btn btn-danger w-100 py-2 <?= $canPulang ? '' : 'disabled' ?>" data-absen="out" id="btnPulang">
                                    <i class="bi bi-box-arrow-left me-2"></i> Absen Pulang
                                </a>
                                <?php if ($remaining_seconds > 0): ?>
                                    <div class="text-center mt-2 small text-warning fw-bold">
                                        <i class="bi bi-hourglass-split"></i> Tunggu: <span id="countdown">--:--:--</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold text-dark m-0">Riwayat Hari Ini</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small">
                                <tr>
                                    <th class="ps-3">Jam</th>
                                    <th>Status</th>
                                    <th class="pe-3">Lokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($riwayat)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted small">
                                            <i class="bi bi-clock-history fs-1 mb-2 d-block opacity-25"></i>
                                            Belum ada aktivitas hari ini.
                                        </td>
                                    </tr>
                                <?php else: foreach ($riwayat as $r): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark">
                                            <?= date('H:i', strtotime($r['waktu'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= $r['status'] == 'masuk' ? 'badge-soft-success' : 'badge-soft-danger' ?> text-uppercase">
                                                <?= e($r['status']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted pe-3 text-truncate" style="max-width: 100px;">
                                            <i class="bi bi-geo-alt me-1"></i><?= e($r['lokasi_text']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 text-center py-3">
                    <small class="text-muted">Semangat bekerja, jaga kesehatan!</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Tandai niat absen (untuk UX saat klik tombol disabled)
document.querySelectorAll('[data-absen]').forEach(a=>{
  if(!a.classList.contains('disabled')){
      a.addEventListener('click',()=> localStorage.setItem('abs-pending', a.dataset.absen));
  }
});

// Countdown Logic
const remainingSeconds = <?= (int)$remaining_seconds ?>;
if (remainingSeconds > 0) {
    let s = remainingSeconds;
    const btnPulang = document.getElementById('btnPulang');
    const countdownEl = document.getElementById('countdown');
    
    if (btnPulang && countdownEl) {
        const timer = setInterval(() => {
            s--;
            if (s <= 0) {
                clearInterval(timer);
                // Aktifkan tombol
                btnPulang.classList.remove('disabled');
                btnPulang.classList.remove('btn-light', 'text-muted');
                btnPulang.classList.add('btn-danger');
                countdownEl.parentElement.style.display = 'none'; // Sembunyikan timer
                // Reload halaman otomatis agar status fresh
                window.location.reload();
            } else {
                // Format HH:MM:SS
                const h = String(Math.floor(s / 3600)).padStart(2,'0');
                const m = String(Math.floor((s % 3600) / 60)).padStart(2,'0');
                const ss = String(s % 60).padStart(2,'0');
                countdownEl.textContent = `${h}:${m}:${ss}`;
            }
        }, 1000);
    }
}
</script>