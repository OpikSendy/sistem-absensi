<?php
// admin/shift_manage.php
// UI FINAL: Compact Grid + Master Shift + Print (TANPA Bulk Assign)

session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in() || !is_admin()) {
    redirect(url('user/403.php'));
}

$page_title = "Manajemen Jadwal";
$active_menu = 'shifts';
$conn = db();

define('MAX_RANGE_DAYS', 35); 

// --- HELPERS ---
function is_valid_date($d) {
    return $d && DateTime::createFromFormat('Y-m-d', $d) !== false;
}
function normalize_time($t) {
    if (!$t) return null;
    $dt = DateTime::createFromFormat('H:i', substr($t,0,5));
    return $dt ? $dt->format('H:i:s') : null;
}

// --- POST HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok_post()) {
        $_SESSION['flash_err'] = 'Token keamanan tidak valid.';
        redirect(url('admin/shift_manage.php'));
    }

    // 1. SIMPAN JADWAL (GRID CLICK)
    if (isset($_POST['action']) && $_POST['action'] === 'save_schedule') {
        if (isset($_POST['schedule']) && is_array($_POST['schedule'])) {
            $stmt_on = $conn->prepare("INSERT INTO user_jadwal (user_id, tanggal, shift_id, status) VALUES (?, ?, ?, 'ON') ON DUPLICATE KEY UPDATE shift_id=VALUES(shift_id), status='ON'");
            $stmt_off = $conn->prepare("INSERT INTO user_jadwal (user_id, tanggal, shift_id, status) VALUES (?, ?, NULL, 'OFF') ON DUPLICATE KEY UPDATE shift_id=NULL, status='OFF'");
            
            foreach ($_POST['schedule'] as $uidStr => $dates) {
                $uid = (int)$uidStr;
                foreach ($dates as $date => $shiftId) {
                    if (!is_valid_date($date)) continue;
                    $sid = (int)$shiftId;
                    if ($sid > 0) {
                        $stmt_on->bind_param('isi', $uid, $date, $sid);
                        $stmt_on->execute();
                    } elseif ($sid === 0) { // 0 = OFF
                        $stmt_off->bind_param('is', $uid, $date);
                        $stmt_off->execute();
                    }
                    // -1 = Ignore/Empty
                }
            }
            $stmt_on->close();
            $stmt_off->close();
            $_SESSION['flash_ok'] = "Jadwal berhasil disimpan.";
        }
        $s = $_POST['start_date'] ?? '';
        $e = $_POST['end_date'] ?? '';
        redirect(url('admin/shift_manage.php') . "?start=$s&end=$e");
    }

    // 2. MASTER SHIFT CRUD
    if (isset($_POST['shift_action'])) {
        $sa = $_POST['shift_action'];
        
        // DELETE
        if ($sa === 'delete') {
            $id = (int)$_POST['shift_id'];
            if($conn->query("DELETE FROM shift_master WHERE id=$id")) {
                $_SESSION['flash_ok'] = 'Shift berhasil dihapus.';
            } else {
                $_SESSION['flash_err'] = 'Gagal hapus: '.$conn->error;
            }
        }
        // SAVE (ADD/EDIT)
        elseif ($sa === 'save_master') {
            $id = (int)($_POST['shift_id'] ?? 0);
            $nama = trim($_POST['nama_shift'] ?? '');
            $masuk = normalize_time($_POST['jam_masuk'] ?? '');
            $pulang = normalize_time($_POST['jam_pulang'] ?? '');
            $toleransi = (int)($_POST['toleransi_menit'] ?? 15);
            $durasi = (int)($_POST['durasi_menit'] ?? 480);
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if ($nama && $masuk) {
                if ($id === 0) {
                    $stmt = $conn->prepare("INSERT INTO shift_master (nama_shift, jam_masuk, jam_pulang, toleransi_menit, durasi_menit, aktif) VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param('sssiii', $nama, $masuk, $pulang, $toleransi, $durasi, $aktif);
                } else {
                    $stmt = $conn->prepare("UPDATE shift_master SET nama_shift=?, jam_masuk=?, jam_pulang=?, toleransi_menit=?, durasi_menit=?, aktif=? WHERE id=?");
                    $stmt->bind_param('sssiiii', $nama, $masuk, $pulang, $toleransi, $durasi, $aktif, $id);
                }
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_ok'] = 'Master shift disimpan.';
            } else {
                $_SESSION['flash_err'] = 'Nama Shift dan Jam Masuk wajib diisi.';
            }
        }
        redirect(url('admin/shift_manage.php'));
    }
}

// --- DATA FETCH ---
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('monday this week'));
$end_date   = $_GET['end'] ?? date('Y-m-d', strtotime('sunday this week'));

$d1 = new DateTime($start_date);
$d2 = new DateTime($end_date);
if ($d1 > $d2) { $t=$d1; $d1=$d2; $d2=$t; }
if ($d1->diff($d2)->days > MAX_RANGE_DAYS) {
    $d2 = clone $d1; $d2->modify('+'.MAX_RANGE_DAYS.' days');
}
$period = new DatePeriod($d1, new DateInterval('P1D'), $d2->modify('+1 day'));
$dates = [];
foreach ($period as $dt) $dates[] = $dt->format('Y-m-d');

// Shifts
$shifts_all = $conn->query("SELECT * FROM shift_master ORDER BY jam_masuk ASC")->fetch_all(MYSQLI_ASSOC);
$shifts_active = array_filter($shifts_all, function($s){ return $s['aktif'] == 1; });

// Warna Pastel Modern
$colors = ['#dbeafe', '#d1fae5', '#fef3c7', '#f3e8ff', '#fce7f3', '#e0e7ff', '#ccfbf1']; 
$text_colors = ['#1e40af', '#065f46', '#92400e', '#6b21a8', '#9d174d', '#3730a3', '#115e59'];

$shiftMap = [];
$idx = 0;
foreach($shifts_active as $s) {
    $cIdx = $idx % count($colors);
    // Logic Kode: Ambil 2 Huruf Depan (Upper)
    $code = strtoupper(substr(str_replace(' ', '', $s['nama_shift']), 0, 2));
    
    $shiftMap[$s['id']] = [
        'code' => $code,
        'name' => $s['nama_shift'],
        'time' => substr($s['jam_masuk'],0,5),
        'bg' => $colors[$cIdx],
        'text' => $text_colors[$cIdx]
    ];
    $idx++;
}

$users = $conn->query("SELECT id, nama, devisi FROM users WHERE aktif=1 AND role!='admin' ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

$s_str = $dates[0];
$e_str = end($dates);
$schedules = [];
$q_sch = $conn->query("SELECT user_id, tanggal, shift_id, status FROM user_jadwal WHERE tanggal BETWEEN '$s_str' AND '$e_str'");
while ($r = $q_sch->fetch_assoc()) {
    $schedules[$r['user_id']][$r['tanggal']] = $r;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    /* --- COMPACT GRID STYLES --- */
    .table-schedule { border-collapse: separate; border-spacing: 0; width: 100%; }
    
    .col-name {
        position: sticky; left: 0; z-index: 20;
        background-color: #fff;
        width: 150px; min-width: 150px; max-width: 150px;
        border-right: 2px solid #e2e8f0 !important;
        padding: 0 10px !important;
        vertical-align: middle;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    
    .col-date {
        text-align: center; padding: 4px 0;
        font-size: 0.7rem; border: 1px solid #e2e8f0;
        background: #f8fafc; color: #64748b;
        min-width: 40px;
    }

    .cell-day {
        padding: 0 !important; height: 36px;
        border: 1px solid #f1f5f9;
        cursor: pointer; vertical-align: middle;
    }
    .cell-day:hover { border: 1px solid #94a3b8; z-index: 5; }
    
    .shift-box {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.75rem; font-weight: 800; letter-spacing: -0.5px;
    }
    
    /* OFF STYLE (HITAM) */
    .shift-off { background: #1e293b; color: #fff; font-size: 0.7rem; }
    .shift-empty { background: #fff; }

    /* TOOLBAR BUTTONS */
    .tool-btn {
        display: inline-flex; align-items: center; justify-content: flex-start;
        padding: 4px 8px; margin-right: 8px; height: 46px; width: auto; min-width: 110px;
        border: 1px solid #e2e8f0; border-radius: 8px;
        background: #fff; cursor: pointer; transition: 0.2s; flex-shrink: 0;
    }
    .tool-btn:hover { background: #f8fafc; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .tool-btn.active { 
        background: #eff6ff; border-color: var(--primary); 
        box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
    }
    .tool-code-box {
        width: 32px; height: 32px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 0.8rem; margin-right: 8px;
        flex-shrink: 0;
    }
    .tool-info { display: flex; flex-direction: column; line-height: 1.1; overflow: hidden; }
    .tool-name { font-size: 0.75rem; font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tool-time { font-size: 0.65rem; color: #94a3b8; }
    .tool-btn-off .tool-code-box { background: #1e293b; color: #fff; font-size: 0.65rem; }

    /* MOBILE OPTIMIZATION */
    @media (max-width: 767.98px) {
        .header-wrap { flex-direction: column; align-items: flex-start; gap: 10px; }
        .action-buttons { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; }
        .action-buttons .btn { width: 100%; justify-content: center; }
        .tool-btn { min-width: 80px; width: auto; padding: 4px 6px; height: 40px; }
        .tool-code-box { width: 26px; height: 26px; font-size: 0.7rem; margin-right: 6px; }
        .tool-time { display: none; } 
        .date-filter-wrap { flex-direction: row; width: 100%; justify-content: space-between; }
        .date-filter-wrap input { width: 45% !important; }
    }
</style>

<div class="container-fluid px-4 py-4">
  
  <div class="d-flex justify-content-between align-items-center mb-3 header-wrap">
    <div>
        <h4 class="fw-bold text-dark mb-1">Manajemen Jadwal</h4>
        <p class="text-muted mb-0 small">Klik shift di bawah, lalu klik tabel untuk mengisi.</p>
    </div>
    <div class="d-flex gap-2 action-buttons">
        <button class="btn btn-light border btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMasterShift">
            <i class="bi bi-gear me-1"></i> Master Shift
        </button>
        <a href="<?= url('admin/shift_print.php') ?>?start=<?= e($start_date) ?>&end=<?= e($end_date) ?>" target="_blank" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-printer me-1"></i> Cetak
        </a>
        <button class="btn btn-success btn-sm shadow-sm fw-bold" onclick="document.getElementById('formSchedule').submit()">
            <i class="bi bi-save me-1"></i> Simpan
        </button>
    </div>
  </div>

  <?php if (isset($_SESSION['flash_ok'])): ?>
      <div class="alert alert-success border-0 shadow-sm py-2 mb-3 small"><i class="bi bi-check-circle me-2"></i><?= $_SESSION['flash_ok']; unset($_SESSION['flash_ok']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['flash_err'])): ?>
      <div class="alert alert-danger border-0 shadow-sm py-2 mb-3 small"><i class="bi bi-exclamation-triangle me-2"></i><?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
      
      <div class="card-header bg-white py-2 border-bottom">
          <div class="row g-2 align-items-center">
              
              <div class="col-12 col-md-8">
                  <div class="d-flex gap-2 overflow-auto pb-1 pt-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                      <div class="tool-btn tool-btn-off active" onclick="selectTool(0, this)" id="tool-0">
                          <div class="tool-code-box">OFF</div>
                          <div class="tool-info"><div class="tool-name">Libur</div><div class="tool-time">Reset</div></div>
                      </div>
                      <?php foreach($shifts_active as $s): $map = $shiftMap[$s['id']]; ?>
                        <div class="tool-btn" onclick="selectTool(<?= $s['id'] ?>, this)">
                            <div class="tool-code-box" style="background: <?= $map['bg'] ?>; color: <?= $map['text'] ?>"><?= $map['code'] ?></div>
                            <div class="tool-info"><div class="tool-name"><?= e($s['nama_shift']) ?></div><div class="tool-time"><?= substr($s['jam_masuk'],0,5) ?></div></div>
                        </div>
                      <?php endforeach; ?>
                      <div class="tool-btn" onclick="selectTool(-1, this)">
                          <div class="tool-code-box bg-white border text-danger"><i class="bi bi-x-lg"></i></div>
                          <div class="tool-info"><div class="tool-name text-danger">Kosong</div><div class="tool-time">Hapus</div></div>
                      </div>
                  </div>
              </div>

              <div class="col-12 col-md-4">
                  <form method="get" class="d-flex align-items-center justify-content-md-end gap-2 m-0 date-filter-wrap">
                      <input type="date" name="start" value="<?= $start_date ?>" class="form-control form-control-sm text-center" style="max-width: 140px;" onchange="this.form.submit()">
                      <span class="text-muted small">s/d</span>
                      <input type="date" name="end" value="<?= $end_date ?>" class="form-control form-control-sm text-center" style="max-width: 140px;" onchange="this.form.submit()">
                  </form>
              </div>
          </div>
      </div>

      <div class="card-body p-0 overflow-auto bg-white" style="max-height: 75vh;">
          <form method="post" id="formSchedule">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_schedule">
            <input type="hidden" name="start_date" value="<?= $start_date ?>">
            <input type="hidden" name="end_date" value="<?= $end_date ?>">
            
            <table class="table-schedule" id="gridTable">
                <thead class="sticky-top" style="top: 0; z-index: 30;">
                    <tr>
                        <th class="col-name shadow-sm">Karyawan</th>
                        <?php foreach($dates as $d): 
                            $dayName = date('D', strtotime($d)); 
                            $isWeekend = ($dayName == 'Sat' || $dayName == 'Sun');
                            $colorClass = $isWeekend ? 'text-danger' : '';
                        ?>
                            <th class="col-date <?= $colorClass ?>">
                                <div class="fw-bold"><?= date('d', strtotime($d)) ?></div>
                                <div style="font-size:0.6rem; opacity:0.8;"><?= $dayName ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td class="col-name" title="<?= e($u['nama']) ?>">
                            <div class="fw-bold text-dark text-truncate"><?= e($u['nama']) ?></div>
                            <div class="text-muted x-small text-truncate" style="font-size: 0.65rem;"><?= e($u['devisi']) ?></div>
                        </td>
                        <?php foreach($dates as $d): 
                            $sch = $schedules[$u['id']][$d] ?? null;
                            $sid = 0; 
                            if ($sch) {
                                if ($sch['status'] === 'OFF') $sid = 0;
                                elseif ($sch['status'] === 'ON') $sid = (int)$sch['shift_id'];
                            } else {
                                $sid = -1;
                            }
                        ?>
                            <td class="cell-day">
                                <input type="hidden" id="inp_<?= $u['id'] ?>_<?= $d ?>" name="schedule[<?= $u['id'] ?>][<?= $d ?>]" value="<?= $sid ?>">
                                <div class="shift-box" 
                                     id="cell_<?= $u['id'] ?>_<?= $d ?>"
                                     onmousedown="applyTool(<?= $u['id'] ?>, '<?= $d ?>')" 
                                     oncontextmenu="rightClick(event, <?= $u['id'] ?>, '<?= $d ?>')">
                                    <?php 
                                    if ($sid > 0 && isset($shiftMap[$sid])) {
                                        $map = $shiftMap[$sid];
                                        echo "<div style='width:100%; height:100%; background-color:{$map['bg']}; color:{$map['text']}; display:flex; align-items:center; justify-content:center;'>{$map['code']}</div>";
                                    } elseif ($sid === 0) {
                                        echo "<div class='shift-box shift-off'>OFF</div>";
                                    } else {
                                        echo "<div class='shift-box shift-empty'></div>";
                                    }
                                    ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
          </form>
      </div>
  </div>
</div>

<div class="modal fade" id="modalMasterShift" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom py-3">
        <h6 class="modal-title fw-bold">Kelola Data Master Shift</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      
      <div class="modal-body p-0">
        <div class="p-4 bg-light border-bottom">
            <form method="post" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="shift_action" value="save_master">
                <input type="hidden" name="shift_id" id="formShiftId" value="0">
                
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-bold text-muted">Nama Shift</label>
                    <input type="text" name="nama_shift" id="formShiftName" class="form-control" placeholder="Contoh: Shift Pagi" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted">Jam Masuk</label>
                    <input type="time" name="jam_masuk" id="formShiftIn" class="form-control" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted">Jam Pulang</label>
                    <input type="time" name="jam_pulang" id="formShiftOut" class="form-control" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted">Durasi (Menit)</label>
                    <input type="number" name="durasi_menit" id="formShiftDur" class="form-control" value="480">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted">Toleransi (Menit)</label>
                    <input type="number" name="toleransi_menit" id="formShiftTol" class="form-control" value="15">
                </div>
                <div class="col-6 col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2 p-0 d-flex align-items-center gap-2">
                        <input class="form-check-input ms-0" type="checkbox" name="aktif" id="formShiftActive" value="1" checked>
                        <label class="form-check-label pt-1" for="formShiftActive">Status Aktif</label>
                    </div>
                </div>
                <div class="col-6 col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="btnSaveShift"><i class="bi bi-save me-1"></i> Simpan</button>
                </div>
            </form>
        </div>
        
        <div class="table-responsive" style="max-height: 300px;">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="bg-white sticky-top shadow-sm">
                    <tr><th class="ps-4">Shift</th><th>Jam</th><th>Durasi</th><th>Status</th><th class="text-end pe-4">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach($shifts_all as $s): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= e($s['nama_shift']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= substr($s['jam_masuk'],0,5) ?> - <?= substr($s['jam_pulang'],0,5) ?></span></td>
                        <td class="small text-muted"><?= (int)$s['durasi_menit'] ?> m</td>
                        <td><?= $s['aktif'] ? '<span class="text-success">✔</span>' : '<span class="text-muted">✘</span>' ?></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-link p-0 btn-sm text-primary me-2" onclick='editMaster(<?= json_encode($s) ?>)' title="Edit"><i class="bi bi-pencil-square fs-6"></i></button>
                            <form method="post" class="d-inline" onsubmit="return confirm('Hapus shift ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="shift_action" value="delete">
                                <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                <button class="btn btn-link text-danger p-0 btn-sm" title="Hapus"><i class="bi bi-trash fs-6"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// --- LOGIKA CLICK MODE ---
const SHIFT_MAP = <?= json_encode($shiftMap) ?>;
let currentToolId = 0; // Default OFF

function selectTool(id, el) {
    currentToolId = id;
    document.querySelectorAll('.tool-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
}

function updateVisual(uid, date, sid) {
    const cell = document.getElementById(`cell_${uid}_${date}`);
    const input = document.getElementById(`inp_${uid}_${date}`);
    if (!cell || !input) return;
    input.value = sid;
    
    if (sid > 0 && SHIFT_MAP[sid]) {
        const m = SHIFT_MAP[sid];
        cell.innerHTML = `<div style="width:100%; height:100%; background-color:${m.bg}; color:${m.text}; display:flex; align-items:center; justify-content:center;">${m.code}</div>`;
    } else if (sid === 0) {
        cell.innerHTML = `<div class="shift-box shift-off">OFF</div>`;
    } else {
        cell.innerHTML = `<div class="shift-box shift-empty"></div>`;
    }
}

function applyTool(uid, date) {
    updateVisual(uid, date, currentToolId);
}
function rightClick(e, uid, date) {
    e.preventDefault(); 
    updateVisual(uid, date, -1);
}

// --- MASTER SHIFT JS ---
function editMaster(data) {
    document.getElementById('formShiftId').value = data.id;
    document.getElementById('formShiftName').value = data.nama_shift;
    document.getElementById('formShiftIn').value = data.jam_masuk;
    document.getElementById('formShiftOut').value = data.jam_pulang;
    document.getElementById('formShiftDur').value = data.durasi_menit || 480;
    const tolInput = document.getElementById('formShiftTol');
    if(tolInput) tolInput.value = data.toleransi_menit || 15;
    document.getElementById('formShiftActive').checked = (data.aktif == 1);
    document.getElementById('btnSaveShift').innerHTML = '<i class="bi bi-check-lg me-1"></i> Update';
    document.querySelector('#modalMasterShift .modal-body').scrollTop = 0;
}

document.getElementById('gridTable').addEventListener('mousedown', e => e.preventDefault());
</script>