<?php
// admin/detail_tugas.php
// FIXED: Tampilan IP & User Agent Responsif (Full Detail, No Offside)

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in() || !is_admin()) {
    redirect(url('user/403.php'));
}

$conn = db();
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID tidak valid.");

// Ambil data absensi utama
$stmt = $conn->prepare("SELECT a.*, u.username, u.nama FROM absensi a JOIN users u ON u.id = a.user_id WHERE a.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$absensi = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$absensi) die("Data absensi tidak ditemukan.");

$absensi_masuk_id = null;
$absensi_pulang_id = null;
$absensi_masuk_data = null;

if (!empty($absensi['user_id']) && !empty($absensi['tanggal'])) {
    $uid = (int)$absensi['user_id'];
    $tgl = $absensi['tanggal'];

    // Cari pasangan Absensi
    $stmt_pair = $conn->prepare("SELECT t.*, a.id AS absensi_id, a.user_id, a.tanggal, a.status, a.waktu, a.telat_menit AS absensi_telat_menit
    FROM absensi_todo t
    JOIN absensi a ON a.id = t.absensi_id
    WHERE a.user_id = ? AND DATE(a.tanggal) = ?
    ORDER BY t.id ASC");
    if ($stmt_pair) {
        $stmt_pair->bind_param('is', $uid, $tgl);
        $stmt_pair->execute();
        $res_pair = $stmt_pair->get_result();
        while ($row = $res_pair->fetch_assoc()) {
            $absensiIdFromRow = isset($row['absensi_id']) ? (int)$row['absensi_id'] : null;
            if (isset($row['status']) && $row['status'] === 'masuk' && !$absensi_masuk_id) {
                $absensi_masuk_id = $absensiIdFromRow;
                $absensi_masuk_data = $row;
            }
            if (isset($row['status']) && $row['status'] === 'pulang') {
                $absensi_pulang_id = $absensiIdFromRow;
            }
        }
        $stmt_pair->close();
    }

    // Fallback search
    if (!$absensi_masuk_id || !$absensi_pulang_id) {
        $s = $conn->prepare("SELECT id, telat_menit FROM absensi WHERE user_id = ? AND tanggal = ? AND status = 'masuk' ORDER BY waktu ASC LIMIT 1");
        if ($s) {
            $s->bind_param('is', $uid, $tgl);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            if ($r && !$absensi_masuk_id) $absensi_masuk_id = (int)$r['id'];
            $s->close();
        }
        $s2 = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ? AND status = 'pulang' ORDER BY waktu DESC LIMIT 1");
        if ($s2) {
            $s2->bind_param('is', $uid, $tgl);
            $s2->execute();
            $r2 = $s2->get_result()->fetch_assoc();
            if ($r2 && !$absensi_pulang_id) $absensi_pulang_id = (int)$r2['id'];
            $s2->close();
        }
    }
}

// To-Do RENCANA
$todos_rencana = [];
if ($absensi_masuk_id) {
    $stmt = $conn->prepare("
        SELECT at.sumber, at.jumlah, at.is_done,
            CASE WHEN at.sumber='manual' THEN at.manual_judul ELSE tm.nama_tugas END AS judul_tugas,
            CASE WHEN at.sumber='manual' THEN at.manual_detail ELSE at.sub_nama END AS sub_tugas_detail
        FROM absensi_todo at
        LEFT JOIN tugas_master tm ON tm.id = at.master_id
        WHERE at.absensi_id = ? ORDER BY at.id ASC
    ");
    if ($stmt) {
        $stmt->bind_param('i', $absensi_masuk_id);
        $stmt->execute();
        $todos_rencana = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// To-Do REALISASI
$todos_realisasi = [];
if ($absensi_pulang_id) {
    $stmt_detail = $conn->prepare("
        SELECT nama_tugas, sub_tugas, jumlah, sumber, detail
        FROM absensi_detail
        WHERE absensi_id = ? ORDER BY id ASC
    ");
    if ($stmt_detail) {
        $stmt_detail->bind_param('i', $absensi_pulang_id);
        $stmt_detail->execute();
        $todos_realisasi = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_detail->close();
    }
}

$page_title = "Detail Absensi";
$active_menu = 'dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Detail Absensi</h3>
            <p class="text-muted mb-0">ID #<?= $id ?> • <?= e(date('d M Y', strtotime($absensi['tanggal'] ?? date('Y-m-d')))) ?></p>
        </div>
        <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-12 col-lg-6 border-end-lg">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php
                            $fotoSrc = !empty($absensi['foto']) ? url($absensi['foto']) : "https://api.dicebear.com/8.x/initials/svg?seed=" . urlencode($absensi['nama']);
                        ?>
                        <img src="<?= e($fotoSrc) ?>" class="rounded-circle border shadow-sm" width="72" height="72" style="object-fit:cover;">
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><?= e($absensi['nama']) ?></h5>
                            <div class="text-muted small">@<?= e($absensi['username']) ?></div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-muted fw-bold text-uppercase">Status</div>
                            <span class="badge bg-<?= ($absensi['status'] === 'masuk') ? 'success-subtle text-success' : 'primary-subtle text-primary' ?> px-3 py-2 rounded-pill border border-<?= ($absensi['status'] === 'masuk') ? 'success' : 'primary' ?>-subtle">
                                <?= ucfirst(e($absensi['status'])) ?>
                            </span>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted fw-bold text-uppercase">Approval</div>
                            <?php
                                $stt = strtolower($absensi['approval_status'] ?? '');
                                $badgeClass = match($stt) { 'disetujui'=>'success', 'ditolak'=>'danger', default=>'warning' };
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> px-3 py-2 rounded-pill border border-<?= $badgeClass ?>-subtle">
                                <?= e($absensi['approval_status']) ?>
                            </span>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted fw-bold text-uppercase">Waktu Absen</div>
                            <div class="fw-medium text-dark fs-5"><?= e($absensi['waktu']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted fw-bold text-uppercase">Lokasi</div>
                            <div class="text-dark small text-break mb-1"><?= e($absensi['lokasi_text'] ?: '-') ?></div>
                            <?php if (!empty($absensi['lat']) && !empty($absensi['lng'])): ?>
                                <a href="https://maps.google.com/?q=<?= e($absensi['lat']) ?>,<?= e($absensi['lng']) ?>" target="_blank" class="btn btn-light btn-sm border text-decoration-none small">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> Buka Peta
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 text-center d-flex flex-column justify-content-center">
                    <div class="small text-muted fw-bold mb-2 text-start w-100">BUKTI FOTO</div>
                    <?php if (!empty($absensi['foto'])): ?>
                        <a href="<?= url($absensi['foto']) ?>" target="_blank" class="d-block w-100 h-100">
                            <img src="<?= url($absensi['foto']) ?>" class="img-fluid rounded border" style="max-height: 300px; width:100%; object-fit:contain; background:#f8f9fa;">
                        </a>
                    <?php else: ?>
                        <div class="p-5 bg-light rounded text-muted border border-dashed h-100 d-flex align-items-center justify-content-center">
                            <div>
                                <i class="bi bi-camera-video-off fs-1 d-block mb-2 opacity-25"></i>
                                Tidak ada foto.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-top bg-light-subtle rounded-3 p-3">
                <div class="row g-3">
                    <div class="col-12 col-md-5">
                        <div class="d-flex gap-2">
                            <div class="mt-1 text-primary"><i class="bi bi-hdd-network"></i></div>
                            <div style="min-width: 0;"> <div class="small fw-bold text-muted text-uppercase" style="font-size:0.7rem">IP Address</div>
                                <div class="font-monospace text-dark text-break" style="font-size:0.85rem">
                                    <?= e($absensi['ip_client'] ?: '-') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-7">
                        <div class="d-flex gap-2">
                            <div class="mt-1 text-primary"><i class="bi bi-phone"></i></div>
                            <div style="min-width: 0;"> <div class="small fw-bold text-muted text-uppercase" style="font-size:0.7rem">Perangkat (User Agent)</div>
                                <div class="font-monospace text-dark text-break" style="font-size:0.8rem; line-height:1.4;">
                                    <?= e($absensi['user_agent'] ?: '-') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($absensi['kendala_hari_ini'])): ?>
                        <div class="col-12">
                            <div class="alert alert-danger border-0 bg-danger-subtle text-danger mb-0 mt-2 d-flex gap-2 align-items-start">
                                <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                                <div>
                                    <strong>Kendala Terlapor:</strong><br>
                                    <?= nl2br(e($absensi['kendala_hari_ini'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-clipboard-check me-2 text-primary"></i>Rencana (Pagi)</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($todos_rencana)): ?>
                        <li class="list-group-item text-center text-muted py-4">Tidak ada rencana tugas.</li>
                    <?php else: foreach ($todos_rencana as $t): ?>
                        <li class="list-group-item d-flex align-items-start gap-3 py-3">
                            <i class="bi bi-<?= (bool)($t['is_done'] ?? false) ? 'check-circle-fill text-success' : 'circle text-secondary' ?> fs-5 mt-1"></i>
                            <div>
                                <div class="fw-bold text-dark text-break"><?= e($t['judul_tugas'] ?? '-') ?></div>
                                <?php if (!empty($t['sub_tugas_detail'])): ?>
                                    <div class="small text-muted text-break"><?= e($t['sub_tugas_detail']) ?></div>
                                <?php endif; ?>
                                <?php if ((int)($t['jumlah'] ?? 0) > 1): ?>
                                    <span class="badge bg-light text-dark border mt-1">x<?= (int)$t['jumlah'] ?></span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-check2-all me-2 text-success"></i>Realisasi (Pulang)</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($todos_realisasi)): ?>
                        <li class="list-group-item text-center text-muted py-4">Belum ada laporan pulang.</li>
                    <?php else: foreach ($todos_realisasi as $t): ?>
                        <li class="list-group-item d-flex align-items-start gap-3 py-3">
                            <i class="bi bi-check2-square text-success fs-5 mt-1"></i>
                            <div>
                                <div class="fw-bold text-dark text-break"><?= e($t['nama_tugas'] ?? '-') ?></div>
                                <?php 
                                    $sub = (isset($t['sumber']) && $t['sumber'] === 'manual') ? ($t['detail'] ?? '') : ($t['sub_tugas'] ?? '');
                                    if ($sub): 
                                ?>
                                    <div class="small text-muted text-break"><?= e($sub) ?></div>
                                <?php endif; ?>
                                <?php if ((int)($t['jumlah'] ?? 0) > 1): ?>
                                    <span class="badge bg-light text-dark border mt-1">x<?= (int)$t['jumlah'] ?></span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>
    </div>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>