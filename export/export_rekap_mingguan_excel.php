<?php
// export/export_rekap_mingguan_excel.php
// EXCEL MULTI-SHEET: Matrix + Detail Manual

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../libs/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;

if (!is_logged_in() || !is_admin()) redirect(url('user/403.php'));
$conn = db();

$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
$mode  = $_GET['mode']  ?? 'minggu';

// 1. FILTER TANGGAL
if (!empty($start) && !empty($end)) {
    $date_filter = "a.waktu >= '$start 00:00:00' AND a.waktu <= '$end 23:59:59'";
    $display_period = $start . ' s.d. ' . $end;
} else {
    if ($mode === 'bulan') {
        $date_filter = "MONTH(a.waktu) = MONTH(CURDATE()) AND YEAR(a.waktu) = YEAR(CURDATE())";
        $display_period = date('F Y');
    } else {
        $date_filter = "WEEK(a.waktu,1) = WEEK(CURDATE(),1) AND YEAR(a.waktu) = YEAR(CURDATE())";
        $display_period = "Minggu Ini";
    }
}

// 2. QUERY MATRIX (Sama seperti PDF/Dashboard)
$master_names = [];
$rm = $conn->query("SELECT nama_tugas FROM tugas_master WHERE aktif = 1 ORDER BY nama_tugas ASC");
if ($rm) while ($m = $rm->fetch_assoc()) $master_names[] = $m['nama_tugas'];

$totals = [];
$tr = $conn->query("SELECT u.id, COUNT(DISTINCT a.id) as cnt FROM users u LEFT JOIN absensi a ON u.id = a.user_id AND a.status='pulang' AND {$date_filter} WHERE u.aktif=1 GROUP BY u.id");
if($tr) while($r = $tr->fetch_assoc()) $totals[(int)$r['id']] = (int)$r['cnt'];

$task_sql = "SELECT u.id AS user_id, u.username, COALESCE(tm.nama_tugas, '') AS master_tugas, COALESCE(SUM(ad.jumlah),0) AS total_jumlah
  FROM users u LEFT JOIN absensi a ON u.id = a.user_id AND a.status='pulang' AND {$date_filter}
  LEFT JOIN absensi_detail ad ON a.id = ad.absensi_id
  LEFT JOIN tugas_master tm ON LOWER(TRIM(tm.nama_tugas)) = LOWER(TRIM(ad.nama_tugas))
  WHERE u.aktif = 1 GROUP BY u.id, tm.nama_tugas";
$task_rs = $conn->query($task_sql);

$rekap_map = [];
$ur = $conn->query("SELECT id, username FROM users WHERE aktif = 1 ORDER BY username");
while ($u = $ur->fetch_assoc()) {
    $uid = (int)$u['id'];
    $rekap_map[$uid] = ['username' => $u['username'], 'total_absensi' => $totals[$uid] ?? 0, 'tasks' => []];
}
if ($task_rs) {
    while ($r = $task_rs->fetch_assoc()) {
        $uid = (int)$r['user_id'];
        $key = trim($r['master_tugas'] ?? '') ?: 'Lainnya';
        if (isset($rekap_map[$uid])) $rekap_map[$uid]['tasks'][$key] = (int)$r['total_jumlah'];
    }
}

// Data Sheet 1 (Matrix)
$dataSheet1 = [];
$header1 = array_merge(['User', 'Total Hadir'], $master_names, ['Lainnya']);
$dataSheet1[] = ["Laporan Rekap Absensi ($display_period)"];
$dataSheet1[] = $header1;

foreach ($rekap_map as $info) {
    $line = [$info['username'], $info['total_absensi']];
    foreach ($master_names as $mn) {
        $line[] = (int)($info['tasks'][$mn] ?? 0);
    }
    $line[] = (int)($info['tasks']['Lainnya'] ?? 0);
    $dataSheet1[] = $line;
}

// 3. QUERY RINCIAN MANUAL (Sheet 2)
$manual_sql = "
    SELECT u.nama, a.tanggal, ad.nama_tugas, ad.detail, ad.jumlah
    FROM absensi_detail ad
    JOIN absensi a ON a.id = ad.absensi_id
    JOIN users u ON u.id = a.user_id
    LEFT JOIN tugas_master tm ON LOWER(TRIM(tm.nama_tugas)) = LOWER(TRIM(ad.nama_tugas))
    WHERE ({$date_filter}) 
    AND (ad.sumber = 'manual' OR tm.id IS NULL)
    ORDER BY a.tanggal DESC, u.nama ASC
";
$manual_res = $conn->query($manual_sql);

// Data Sheet 2 (Detail)
$dataSheet2 = [];
$dataSheet2[] = ["Rincian Tugas Manual / Lainnya"];
$dataSheet2[] = ["Tanggal", "Karyawan", "Judul Tugas", "Detail Keterangan", "Jumlah"];

if ($manual_res) {
    while ($m = $manual_res->fetch_assoc()) {
        $dataSheet2[] = [
            $m['tanggal'],
            $m['nama'],
            $m['nama_tugas'],
            $m['detail'] ?: '-',
            $m['jumlah']
        ];
    }
} else {
    $dataSheet2[] = ["-", "-", "Tidak ada data manual", "-", "-"];
}

// --- GENERATE EXCEL (MULTI SHEET) ---
$xlsx = new SimpleXLSXGen();
$xlsx->addSheet($dataSheet1, 'Rekap Angka');
$xlsx->addSheet($dataSheet2, 'Rincian Manual');
$xlsx->downloadAs("Rekap_Absensi_Lengkap.xlsx");
exit;