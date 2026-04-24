<?php
// export/export_dashboard_excel.php
// FIXED: Menangani Tampilan IP Address (Localhost & Empty)

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../libs/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;

if (!is_logged_in() || !is_admin()) {
    redirect(url('user/403.php'));
}

$conn = db();

// 1. AMBIL FILTER DARI URL
$u    = trim($_GET['user'] ?? '');
$st   = trim($_GET['status'] ?? '');
$app  = trim($_GET['approval'] ?? '');
$d1   = trim($_GET['d1'] ?? '');
$d2   = trim($_GET['d2'] ?? '');

$mapApproval = ['pending'=>'Pending', 'disetujui'=>'Disetujui', 'ditolak'=>'Ditolak'];
if ($app !== '') $app = $mapApproval[strtolower($app)] ?? $app;

// 2. BUILD QUERY
$where = ["1=1"];
$params = [];
$types  = '';

if ($u !== '') { $where[] = "u.username LIKE ?"; $params[] = '%'.$u.'%'; $types .= 's'; }
if ($st === 'masuk' || $st === 'pulang') { $where[] = "a.status = ?"; $params[] = $st; $types .= 's'; }
if (in_array($app, ['Pending', 'Disetujui', 'Ditolak'], true)) { $where[] = "a.approval_status = ?"; $params[] = $app; $types .= 's'; }
if ($d1 !== '') { $where[] = "a.waktu >= ?"; $params[] = $d1." 00:00:00"; $types .= 's'; }
if ($d2 !== '') { $where[] = "a.waktu <= ?"; $params[] = $d2." 23:59:59"; $types .= 's'; }

// Pastikan kolom ip_client terpilih
$sql = "
    SELECT a.id AS absensi_id, a.waktu, a.status, a.approval_status, a.telat_menit, 
           u.username, u.nama, a.lat, a.lng, a.lokasi_text, a.ip_client, a.foto
    FROM absensi a
    JOIN users u ON u.id = a.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.waktu DESC
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Helper untuk ambil ringkasan tugas
function get_ringkas_tugas($conn, $id, $status) {
    $items = [];
    if ($status === 'masuk') {
        $q = $conn->prepare("
            SELECT CASE WHEN t.sumber='manual' THEN t.manual_judul ELSE tm.nama_tugas END AS judul
            FROM absensi_todo t LEFT JOIN tugas_master tm ON tm.id=t.master_id
            WHERE t.absensi_id=? LIMIT 10
        ");
    } else {
        $q = $conn->prepare("SELECT nama_tugas FROM absensi_detail WHERE absensi_id=? LIMIT 10");
    }
    $q->bind_param('i', $id);
    $q->execute();
    $rr = $q->get_result();
    while($row = $rr->fetch_assoc()) $items[] = $row['judul'] ?? $row['nama_tugas'];
    return implode(', ', $items);
}

// 3. SUSUN DATA EXCEL
$data = [];
// Header
$data[] = [
    'No', 
    'Nama Karyawan', 
    'Username', 
    'Waktu Absen', 
    'Status', 
    'Approval', 
    'Telat (Menit)', 
    'Lokasi', 
    'Koordinat', 
    'IP Address', 
    'Ringkasan Tugas'
];

$no = 1;
while ($row = $res->fetch_assoc()) {
    $ringkas = get_ringkas_tugas($conn, $row['absensi_id'], $row['status']);
    
    // Format link GMaps jika ada koordinat
    $koordinat = ($row['lat'] && $row['lng']) ? $row['lat'] . ',' . $row['lng'] : '-';
    
    // FIX TAMPILAN IP: Handle Localhost & Empty
    $ip = $row['ip_client'] ?? '';
    if ($ip === '::1' || $ip === '127.0.0.1') {
        $ip = 'Localhost';
    } elseif (empty($ip) || $ip === '0.0.0.0') {
        $ip = '-';
    }
    
    $data[] = [
        $no++,
        $row['nama'],
        $row['username'],
        $row['waktu'],
        ucfirst($row['status']),
        $row['approval_status'],
        (int)$row['telat_menit'],
        $row['lokasi_text'] ?: '-',
        $koordinat,
        $ip, // Tampilkan IP yang sudah diformat
        $ringkas ?: '-'
    ];
}

// 4. GENERATE FILE
$filename = "Log_Absensi_" . date('Y-m-d_His') . ".xlsx";
SimpleXLSXGen::fromArray($data)->downloadAs($filename);
exit;