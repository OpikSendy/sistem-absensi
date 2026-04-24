<?php
require_once __DIR__ . '/../includes/bootstrap.php'; // Menggunakan bootstrap.php
require_once __DIR__.'/../libs/SimpleXLSXGen.php'; // Path ke SimpleXLSXGen
use Shuchkin\SimpleXLSXGen;

if (!is_logged_in() || !is_admin()){
  header("Location: " . url('user/403.php')); exit; // Gunakan helper url()
}

$conn = db(); // Gunakan fungsi db()

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID absensi tidak valid.");

// --- Ambil absensi
$stmt = $conn->prepare("
  SELECT a.*, u.username
  FROM absensi a
  JOIN users u ON u.id=a.user_id
  WHERE a.id=? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$absensi = $stmt->get_result()->fetch_assoc();
if(!$absensi) die("Data absensi tidak ditemukan.");

// --- Ambil tugas harian (absensi_detail)
$stmt2 = $conn->prepare("SELECT * FROM absensi_detail WHERE absensi_id=? ORDER BY id");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$tugas = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Siapkan data Excel
$data = [];
$data[] = ["Detail Absensi"];
$data[] = ["ID Absensi", $absensi['id']];
$data[] = ["User", $absensi['username']];
$data[] = ["Waktu", $absensi['waktu']];
$data[] = ["Status", $absensi['status']];
$data[] = ["Approval", $absensi['approval_status']];
$data[] = ["Lat", $absensi['lat']]; // Pisahkan Lat dan Lng
$data[] = ["Lng", $absensi['lng']];
if($absensi['lokasi_text']){
  $data[] = ["Lokasi Text", $absensi['lokasi_text']];
}
$data[] = ["IP Client", $absensi['ip_client']];
$data[] = ["User Agent", $absensi['user_agent']];
$data[] = ["Kendala Hari Ini", $absensi['kendala_hari_ini']];
$data[] = []; // kosong

// Header tugas
$data[] = ["No","Nama Tugas","Sumber","Sub/Detail","Jumlah"];

// Isi tugas
if($tugas){
  $no=1;
  foreach($tugas as $t){
    $data[] = [
      $no++,
      $t['nama_tugas'],
      $t['sumber'],
      ($t['sumber'] === 'manual' ? $t['detail'] : $t['sub_tugas']) ?: '-', // Pilih antara sub_tugas atau detail
      $t['jumlah']
    ];
  }
} else {
  $data[] = ["-","Tidak ada tugas","","",""];
}

// --- Export
$xlsx = SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs('detail_absensi_'.$id.'.xlsx');
exit;