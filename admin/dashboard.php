<?php
// admin/dashboard.php
// FIXED: Variable Conflict ($res -> $resLog)

require_once __DIR__ . '/../includes/bootstrap.php';
$active_menu = 'dashboard';

// 1. PROTEKSI AKSES
if (!is_logged_in()) redirect(url('user/login.php'));
if (!is_admin()) redirect(url('user/dashboard.php'));

$conn = db();

// 2. AMBIL FILTER
$u    = trim($_GET['user'] ?? '');
$st   = trim($_GET['status'] ?? '');
$app  = trim($_GET['approval'] ?? '');
$d1   = trim($_GET['d1'] ?? '');
$d2   = trim($_GET['d2'] ?? '');

$mapApproval = ['pending'=>'Pending', 'disetujui'=>'Disetujui', 'ditolak'=>'Ditolak'];
if ($app !== '') $app = $mapApproval[strtolower($app)] ?? $app;

// 3. QUERY UTAMA: LOG ABSENSI
$where = ["1=1"];
$params = [];
$types  = '';

if ($u !== '') { 
    $where[] = "(u.username LIKE ? OR u.nama LIKE ?)"; 
    $search_param = '%'.$u.'%';
    $params[] = $search_param; 
    $params[] = $search_param; 
    $types .= 'ss'; 
}
if ($st === 'masuk' || $st === 'pulang') { 
    $where[] = "a.status = ?"; 
    $params[] = $st; 
    $types .= 's'; 
}
if (in_array($app, ['Pending', 'Disetujui', 'Ditolak'], true)) { 
    $where[] = "a.approval_status = ?"; 
    $params[] = $app; 
    $types .= 's'; 
}
if ($d1 !== '') { 
    $where[] = "a.waktu >= ?"; 
    $params[] = $d1." 00:00:00"; 
    $types .= 's'; 
}
if ($d2 !== '') { 
    $where[] = "a.waktu <= ?"; 
    $params[] = $d2." 23:59:59"; 
    $types .= 's'; 
}

$sql = "
    SELECT a.id, a.waktu, a.status, a.approval_status, a.foto, u.username, u.nama, a.tanggal, a.user_id,
           COALESCE(a.telat_menit, 0) AS telat_menit,
           sm.jam_masuk AS jam_masuk_patokan
    FROM absensi a
    JOIN users u ON u.id = a.user_id
    LEFT JOIN shift_master sm ON sm.id = a.shift_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.waktu DESC
    LIMIT 1000
";

// PERBAIKAN: Ganti nama variabel $res menjadi $resLog agar tidak bentrok dengan sidebar
$resLog = null;
$query_error = "";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if ($params) $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $resLog = $stmt->get_result(); // Simpan ke $resLog
    } else {
        $query_error = "Execute failed: " . $stmt->error;
    }
} else {
    $query_error = "Prepare failed: " . $conn->error;
}

// 4. REKAP TUGAS
$mode = isset($_GET['mode']) && $_GET['mode'] === 'bulan' ? 'bulan' : 'minggu';
$rekap_label = "";
$date_filter_rekap = "";

if (!empty($d1) && !empty($d2)) {
    $date_filter_rekap = "a.waktu >= '$d1 00:00:00' AND a.waktu <= '$d2 23:59:59'";
    $rekap_label = "Periode: " . date('d M', strtotime($d1)) . " - " . date('d M Y', strtotime($d2));
    $mode = 'custom';
} else {
    if ($mode === 'bulan') {
        $date_filter_rekap = "MONTH(a.waktu) = MONTH(CURDATE()) AND YEAR(a.waktu) = YEAR(CURDATE())";
        $rekap_label = "Bulan Ini (" . date('F Y') . ")";
    } else {
        $date_filter_rekap = "WEEK(a.waktu,1) = WEEK(CURDATE(),1) AND YEAR(a.waktu) = YEAR(CURDATE())";
        $rekap_label = "Minggu Ini";
    }
}

$master_names = [];
$rmasters = $conn->query("SELECT id, nama_tugas FROM tugas_master WHERE aktif = 1 ORDER BY nama_tugas ASC");
if ($rmasters) while ($m = $rmasters->fetch_assoc()) $master_names[] = $m['nama_tugas'];

$totals = [];
$total_sql = "SELECT u.id, COUNT(DISTINCT a.id) as cnt FROM users u LEFT JOIN absensi a ON u.id = a.user_id AND a.status='pulang' AND {$date_filter_rekap} WHERE u.aktif=1 GROUP BY u.id";
$total_rs = $conn->query($total_sql);
if($total_rs) while($r = $total_rs->fetch_assoc()) $totals[(int)$r['id']] = (int)$r['cnt'];

$task_sql = "SELECT u.id AS user_id, u.username, COALESCE(tm.nama_tugas, '') AS master_tugas, COALESCE(SUM(ad.jumlah),0) AS total_jumlah
  FROM users u LEFT JOIN absensi a ON u.id = a.user_id AND a.status = 'pulang' AND {$date_filter_rekap}
  LEFT JOIN absensi_detail ad ON a.id = ad.absensi_id
  LEFT JOIN tugas_master tm ON LOWER(TRIM(tm.nama_tugas)) = LOWER(TRIM(ad.nama_tugas))
  WHERE u.aktif = 1 GROUP BY u.id, tm.nama_tugas";
$task_rs = $conn->query($task_sql);

$rekap_map = [];
$users_rs = $conn->query("SELECT id, username FROM users WHERE aktif=1 ORDER BY username");
while($u_row = $users_rs->fetch_assoc()){
    $uid = (int)$u_row['id'];
    $rekap_map[$uid] = ['username' => $u_row['username'], 'total_absensi' => $totals[$uid] ?? 0, 'tasks' => []];
}
while($r = $task_rs->fetch_assoc()){
    $uid = (int)$r['user_id'];
    if(isset($rekap_map[$uid])){
        $key = trim($r['master_tugas'] ?? '') ?: 'Lainnya';
        $rekap_map[$uid]['tasks'][$key] = (int)$r['total_jumlah'];
    }
}
$rekap_data = [];
foreach($rekap_map as $info){
    $row = ['username' => $info['username'], 'total_absensi' => $info['total_absensi']];
    foreach($master_names as $mn) $row[$mn] = (int)($info['tasks'][$mn] ?? 0);
    $row['Lainnya'] = (int)($info['tasks']['Lainnya'] ?? 0);
    $rekap_data[] = $row;
}

// 5. CHART DATA
$ymFilter_chart = (!empty($d1) && !empty($d2)) ? "a.waktu >= '$d1 00:00:00' AND a.waktu <= '$d2 23:59:59'" : "YEAR(a.waktu)=YEAR(CURDATE()) AND MONTH(a.waktu)=MONTH(CURDATE())";

$qDn = $conn->query("SELECT SUM(CASE WHEN a.status='masuk' AND COALESCE(a.telat_menit, 0) <= 0 THEN 1 ELSE 0 END) AS ontime, SUM(CASE WHEN a.status='masuk' AND COALESCE(a.telat_menit, 0) > 0 THEN 1 ELSE 0 END) AS telat FROM absensi a WHERE {$ymFilter_chart} AND a.status='masuk'");
$dn = $qDn->fetch_assoc() ?: ['ontime'=>0, 'telat'=>0];
$donutData = ['labels' => ['Tepat Waktu', 'Terlambat'], 'data' => [(int)$dn['ontime'], (int)$dn['telat']]];

$res_bar = $conn->query("SELECT u.nama, SUM(CASE WHEN a.status='masuk' AND COALESCE(a.telat_menit, 0) <= 0 THEN 1 ELSE 0 END) AS ontime, SUM(CASE WHEN a.status='masuk' AND COALESCE(a.telat_menit, 0) > 0 THEN 1 ELSE 0 END) AS telat FROM users u LEFT JOIN absensi a ON a.user_id=u.id AND {$ymFilter_chart} AND a.status='masuk' WHERE u.aktif=1 GROUP BY u.id ORDER BY u.nama");
$barLabels = []; $barOntime = []; $barTelat = [];
while($r = $res_bar->fetch_assoc()){ $barLabels[] = $r['nama']; $barOntime[] = (int)$r['ontime']; $barTelat[] = (int)$r['telat']; }

$page_title = "Kesatriyan Admin";
$load_chart_js = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
  
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark mb-1">Kesatriyan Admin</h3>
        <p class="text-muted mb-0">Overview kinerja dan manajemen absensi.</p>
    </div>
    <div>
        <button class="btn btn-success shadow-sm d-flex align-items-center gap-2 px-3" data-bs-toggle="modal" data-bs-target="#modalExport">
            <i class="bi bi-download"></i> Export Data
        </button>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3"><h6 class="fw-bold text-dark m-0">Konsistensi</h6></div>
        <div class="card-body d-flex align-items-center justify-content-center"><div style="height:200px;width:100%"><canvas id="donutStatus"></canvas></div></div>
      </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-bottom py-3"><h6 class="fw-bold text-dark m-0">Statistik Kedisiplinan</h6></div>
        <div class="card-body"><div style="height:200px;width:100%"><canvas id="barDisiplin"></canvas></div></div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold text-dark m-0"><i class="bi bi-table me-2 text-primary"></i>Rekap Tugas</h6>
            <span class="badge bg-light text-secondary border"><?= e($rekap_label) ?></span>
        </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive" style="max-height:300px;">
        <table class="table table-hover table-striped mb-0 small">
          <thead class="bg-light sticky-top">
            <tr>
              <th class="ps-3">Karyawan</th>
              <th class="text-center">Total Hadir</th>
              <?php foreach($master_names as $mn): ?><th class="text-center"><?= e($mn) ?></th><?php endforeach; ?>
              <th class="text-center">Lainnya</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($rekap_data)): ?><tr><td colspan="<?= count($master_names)+3 ?>" class="text-center py-3">Tidak ada data.</td></tr><?php else: foreach($rekap_data as $r): ?>
                <tr>
                    <td class="ps-3 fw-bold"><?= e($r['username']) ?></td>
                    <td class="text-center fw-bold text-primary"><?= $r['total_absensi'] ?></td>
                    <?php foreach($master_names as $mn): ?><td class="text-center text-muted"><?= $r[$mn] ?: '-' ?></td><?php endforeach; ?>
                    <td class="text-center text-muted"><?= $r['Lainnya'] ?: '-' ?></td>
                </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark m-0"><i class="bi bi-clock-history me-2 text-primary"></i>Log Absensi Harian</h6>
        <button class="btn btn-sm btn-light border d-md-none" data-bs-toggle="collapse" data-bs-target="#filterLog"><i class="bi bi-filter"></i></button>
    </div>
    
    <div class="card-body p-0">
        <?php if ($query_error): ?>
            <div class="alert alert-danger m-3">
                <strong>Error Database:</strong> <?= e($query_error) ?>
            </div>
        <?php endif; ?>

        <div class="collapse d-md-block border-bottom bg-light p-3" id="filterLog">
            <form class="row g-2" method="get">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="user" value="<?= e($u) ?>" class="form-control border-start-0 ps-0" placeholder="Cari nama / username...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select"><option value="">Semua Status</option><option value="masuk" <?= $st==='masuk'?'selected':'' ?>>Masuk</option><option value="pulang" <?= $st==='pulang'?'selected':'' ?>>Pulang</option></select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="approval" class="form-select"><option value="">Semua Approval</option><option value="Pending" <?= $app==='Pending'?'selected':'' ?>>Pending</option><option value="Disetujui" <?= $app==='Disetujui'?'selected':'' ?>>Disetujui</option><option value="Ditolak" <?= $app==='Ditolak'?'selected':'' ?>>Ditolak</option></select>
                </div>
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <input type="date" name="d1" value="<?= e($d1) ?>" class="form-control text-center">
                        <span class="input-group-text bg-white border-0 text-muted small">s/d</span>
                        <input type="date" name="d2" value="<?= e($d2) ?>" class="form-control text-center">
                    </div>
                </div>
                <div class="col-12 col-md-1"><button class="btn btn-primary w-100 fw-bold">Filter</button></div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="bg-light text-secondary">
                    <tr><th class="ps-3 py-3">Karyawan</th><th>Waktu</th><th>Status</th><th>Approval</th><th class="text-end pe-3">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php 
                    // PERBAIKAN: Gunakan $resLog (bukan $res yang mungkin tertimpa)
                    if ($resLog && $resLog instanceof mysqli_result && $resLog->num_rows > 0): 
                        while($row = $resLog->fetch_assoc()): 
                            $isMasuk=$row['status']==='masuk'; 
                            $telat=(int)$row['telat_menit']; 
                            $appSt=strtolower($row['approval_status']); 
                            $badgeApp=match($appSt){'disetujui'=>'success','ditolak'=>'danger',default=>'warning'}; 
                    ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark"><?= e($row['nama']) ?></div><div class="small text-muted">@<?= e($row['username']) ?></div>
                            </td>
                            <td><div class="fw-medium"><?= date('H:i', strtotime($row['waktu'])) ?></div><div class="small text-muted"><?= date('d M', strtotime($row['waktu'])) ?></div></td>
                            <td><span class="badge badge-soft-<?= $isMasuk?'success':'primary' ?> text-uppercase"><?= $row['status'] ?></span></td>
                            <td><span class="badge badge-soft-<?= $badgeApp ?>"><?= e($row['approval_status']) ?></span><?php if($isMasuk && $telat > 0): ?><span class="badge badge-soft-danger ms-1">Telat <?= $telat ?>m</span><?php endif; ?></td>
                            <td class="text-end pe-3">
                                <div class="btn-group">
                                    <a href="<?= url('admin/detail_tugas.php?id='.$row['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Detail"><i class="bi bi-eye"></i></a>
                                    <?php if($appSt === 'pending'): ?>
                                        <button class="btn btn-sm btn-success jsApprove" data-id="<?= (int)$row['id'] ?>"><i class="bi bi-check"></i></button>
                                        <button class="btn btn-sm btn-danger jsReject" data-id="<?= (int)$row['id'] ?>"><i class="bi bi-x"></i></button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger jsDelete" data-id="<?= (int)$row['id'] ?>"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalExport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-bold">Export Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <h6 class="text-uppercase text-muted small fw-bold mb-3 text-primary">1. Log Absensi Harian</h6>
        <p class="small text-muted mb-3">Data detail absensi (jam, foto, lokasi) sesuai filter tanggal di atas.</p>
        <div class="d-flex gap-2 mb-4">
            <button onclick="doExport('log','excel')" class="btn btn-outline-success flex-fill"><i class="bi bi-file-earmark-excel"></i> Excel Log</button>
            <button onclick="doExport('log','pdf')" class="btn btn-outline-danger flex-fill"><i class="bi bi-file-earmark-pdf"></i> PDF Log</button>
        </div>
        <hr class="my-4">
        <h6 class="text-uppercase text-muted small fw-bold mb-3 text-primary">2. Rekap Tugas (Matrix)</h6>
        <p class="small text-muted mb-3">Ringkasan jumlah tugas per karyawan (Periode: <?= e($rekap_label) ?>).</p>
        <div class="d-flex gap-2">
            <button onclick="doExport('rekap','excel')" class="btn btn-outline-success flex-fill"><i class="bi bi-table"></i> Excel Rekap</button>
            <button onclick="doExport('rekap','pdf')" class="btn btn-outline-danger flex-fill"><i class="bi bi-table"></i> PDF Rekap</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  function doExport(type, format) {
    const params = new URLSearchParams(window.location.search);
    const u = params.get('user') || '';
    const st = params.get('status') || '';
    const app = params.get('approval') || '';
    const d1 = params.get('d1') || '';
    const d2 = params.get('d2') || '';
    const mode = params.get('mode') || 'minggu';
    
    let url = "";
    if (type === 'log') {
        url = format === 'excel' ? "<?= url('export/export_dashboard_excel.php') ?>" : "<?= url('export/export_dashboard_pdf.php') ?>";
        window.location.href = `${url}?user=${u}&status=${st}&approval=${app}&d1=${d1}&d2=${d2}`;
    } else {
        url = format === 'excel' ? "<?= url('export/export_rekap_mingguan_excel.php') ?>" : "<?= url('export/export_rekap_mingguan_pdf.php') ?>";
        window.location.href = `${url}?start=${d1}&end=${d2}&mode=${mode}`;
    }
  }

  const DONUT_DATA = <?= json_encode($donutData, JSON_UNESCAPED_UNICODE) ?>;
  const BAR_DATA = { labels: <?= json_encode($barLabels, JSON_UNESCAPED_UNICODE) ?>, ontime: <?= json_encode($barOntime) ?>, telat: <?= json_encode($barTelat) ?> };

  document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.buildDonut === 'function') window.buildDonut('donutStatus', DONUT_DATA.labels, DONUT_DATA.data);
    if (typeof window.buildBar === 'function') window.buildBar('barDisiplin', BAR_DATA.labels, [{label:'On-Time',data:BAR_DATA.ontime,backgroundColor:'#10b981'},{label:'Telat',data:BAR_DATA.telat,backgroundColor:'#ef4444'}], true);

    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if(!btn) return;
        if(btn.classList.contains('jsApprove')) doAction(btn.dataset.id, 'Disetujui');
        if(btn.classList.contains('jsReject')) doAction(btn.dataset.id, 'Ditolak');
        if(btn.classList.contains('jsDelete')) doAction(btn.dataset.id, 'delete');
    });

    async function doAction(id, status) {
        const act = status==='delete'?'hapus':status;
        if(!confirm(`Yakin ${act}?`)) return;
        const fd = new FormData(); fd.append('id', id); fd.append('approval', status); fd.append('_token', '<?= csrf_token() ?>');
        try {
            const res = await fetch('<?= url('admin/set_status_absensi.php') ?>', {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}});
            const json = await res.json();
            if(json.ok) { alert(json.msg); location.reload(); } else alert('Gagal: '+json.msg);
        } catch(e){ alert('Error koneksi'); }
    }
  });
</script>