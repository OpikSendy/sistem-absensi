<?php
// admin/shift_print.php  (FIXED - print & responsive improvements)
// Ganti file lama dengan yang ini.
// Bergantung pada includes/bootstrap.php (db(), e(), is_logged_in(), is_admin(), csrf_field(), url()).

session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
if (!is_logged_in() || !is_admin()) redirect(url('user/403.php'));

$conn = db();

$raw_start = $_GET['start'] ?? null;
$raw_end   = $_GET['end'] ?? null;

function is_valid_date($d) {
    if (!$d) return false;
    $t = DateTime::createFromFormat('Y-m-d', $d);
    return $t && $t->format('Y-m-d') === $d;
}

// build date range (default minggu ini)
if (!is_valid_date($raw_start) || !is_valid_date($raw_end)) {
    $today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $w = (int)$today->format('N'); $mon = clone $today; $mon->modify('-'.($w-1).' days');
    $dates = [];
    for($i=0;$i<7;$i++){ $d = clone $mon; $d->modify("+$i days"); $dates[] = $d->format('Y-m-d'); }
    $start = $dates[0]; $end = end($dates);
} else {
    $sd = new DateTime($raw_start); $ed = new DateTime($raw_end);
    if ($ed < $sd) { $tmp = $sd; $sd = $ed; $ed = $tmp; }
    $dates = [];
    for ($dt = clone $sd; $dt <= $ed; $dt->modify('+1 day')) $dates[] = $dt->format('Y-m-d');
    $start = $sd->format('Y-m-d'); $end = $ed->format('Y-m-d');
}

$week_label = (new DateTime($start))->format('d M Y').' — '.(new DateTime($end))->format('d M Y');

// fetch users, shifts, jadwals
$users = $conn->query("SELECT id,nama,username,COALESCE(devisi,'-') AS devisi FROM users WHERE aktif=1 AND role!='admin' ORDER BY devisi, nama")->fetch_all(MYSQLI_ASSOC);
$shifts = $conn->query("SELECT id,nama_shift,jam_masuk,jam_pulang FROM shift_master ORDER BY jam_masuk")->fetch_all(MYSQLI_ASSOC);
$shift_map = [];
foreach($shifts as $s) $shift_map[(int)$s['id']] = $s;

$dates_list = "'" . implode("','", $dates) . "'";
$jadwals = [];
if ($res = $conn->query("SELECT user_id,tanggal,shift_id,status FROM user_jadwal WHERE tanggal IN ($dates_list)")) {
    while ($r = $res->fetch_assoc()) $jadwals[$r['user_id']][$r['tanggal']] = $r;
}

// --- OUTPUT HTML (print-friendly + improved CSS) ---
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cetak Jadwal <?= e($week_label) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  /* --- Screen styles --- */
  body{font-family:system-ui, -apple-system, "Segoe UI", Roboto, Arial; padding:12px; color:#111}
  .print-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;gap:12px;flex-wrap:wrap}
  .print-head h3{margin:0}
  .table-wrap{overflow:auto}
  /* make table flexible on screen but do not force min width */
  .tbl-print { width:100%; font-size:0.9rem; border-collapse:collapse; }
  .tbl-print th, .tbl-print td { padding:.35rem .45rem; vertical-align:middle; border:1px solid #dee2e6; }
  .tbl-print thead th { background:#f8f9fa; font-weight:600; text-align:center; }
  .cell-shift { white-space:nowrap; font-size:0.85rem; }

  /* --- Print specific overrides --- */
  @page { size: A4 landscape; margin: 10mm; }
  @media print {
    body{padding:6mm; color:#000; background:#fff}
    .no-print{display:none !important}
    /* allow table to expand across the landscape page, and make overflow visible */
    .table-wrap{overflow:visible !important}
    .tbl-print { min-width:0 !important; width:100% !important; table-layout:fixed !important; font-size:10pt !important; }
    .tbl-print th, .tbl-print td { word-break:break-word; white-space:normal !important; }
    .cell-shift { white-space:normal !important; }
    table { page-break-inside:auto; }
    tr { page-break-inside:avoid; page-break-after:auto; }
  }

  /* small devices */
  @media (max-width:767px) {
    .print-head {flex-direction:column; align-items:flex-start}
    .tbl-print th, .tbl-print td { font-size:0.82rem; }
  }

  /* first column nicer width on screen/print */
  .tbl-print td:first-child, .tbl-print th:first-child { min-width:180px; max-width:260px; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="print-head">
    <h3>Cetak Jadwal <small class="text-muted"><?= e($week_label) ?></small></h3>

    <form class="row g-2 no-print" method="get" style="align-items:center">
      <div class="col-auto"><input class="form-control form-control-sm" type="date" name="start" value="<?= e($start) ?>"></div>
      <div class="col-auto"><input class="form-control form-control-sm" type="date" name="end" value="<?= e($end) ?>"></div>
      <div class="col-auto"><button class="btn btn-primary btn-sm">Tampilkan</button></div>
      <div class="col-auto"><button type="button" onclick="window.print()" class="btn btn-success btn-sm">Print</button></div>
    </form>
  </div>

  <div class="table-wrap">
    <table class="tbl-print">
      <thead>
        <tr>
          <th>User / Devisi</th>
          <?php foreach($dates as $d): ?>
            <th class="text-center"><?= e(date('D d M', strtotime($d))) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $u): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($u['nama']) ?></div>
              <div class="small text-secondary"><?= e($u['devisi']) ?> · <?= e($u['username']) ?></div>
            </td>
            <?php foreach($dates as $d):
              $cur = $jadwals[$u['id']][$d] ?? null;
              if (!$cur) {
                echo '<td class="text-center text-muted small">—</td>';
                continue;
              }
              if ($cur['status'] === 'OFF') {
                echo '<td class="text-center text-danger small">OFF</td>';
              } else {
                $sid = (int)$cur['shift_id'];
                $s = $shift_map[$sid] ?? null;
                if ($s) {
                  // jam_pulang mungkin null
                  $jp = $s['jam_pulang'] ? ' - ' . e($s['jam_pulang']) : '';
                  echo '<td class="text-center cell-shift"><strong>'.e($s['nama_shift']).'</strong><div class="small text-muted">'.e($s['jam_masuk']) . $jp .'</div></td>';
                } else {
                  echo '<td class="text-center small">-</td>';
                }
              }
            endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-3 small text-muted">
    Tanggal: <?= e($start) ?> — <?= e($end) ?>.
    Jika browser tidak mengikuti orientasi landscape, pilih pengaturan Print → Orientation → Landscape, atau gunakan "Save as PDF" dengan size A4 landscape.
  </div>
</div>
</body>
</html>
