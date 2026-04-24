<?php
require_once __DIR__ . '/../includes/bootstrap.php'; // Menggunakan bootstrap.php

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

// --- Set header CSV
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=detail_absensi_".$id.".csv");

$output = fopen("php://output", "w");

// Metadata absensi
fputcsv($output, ["ID Absensi", $absensi['id']]);
fputcsv($output, ["User", $absensi['username']]);
fputcsv($output, ["Waktu", $absensi['waktu']]);
fputcsv($output, ["Status", $absensi['status']]);
fputcsv($output, ["Approval", $absensi['approval_status']]);
fputcsv($output, ["Lat", $absensi['lat']]); // Pisahkan Lat dan Lng
fputcsv($output, ["Lng", $absensi['lng']]);
if($absensi['lokasi_text']) {
    fputcsv($output, ["Lokasi Text", $absensi['lokasi_text']]);
}
fputcsv($output, ["IP Client", $absensi['ip_client']]);
fputcsv($output, ["User Agent", $absensi['user_agent']]);
fputcsv($output, ["Kendala Hari Ini", $absensi['kendala_hari_ini']]);
fputcsv($output, []); // baris kosong

// Header tabel tugas
fputcsv($output, ["Nama Tugas", "Sumber", "Sub/Detail", "Jumlah"]);

// Isi tabel tugas
foreach($tugas as $t){
    fputcsv($output, [
        $t['nama_tugas'],
        $t['sumber'],
        ($t['sumber'] === 'manual' ? $t['detail'] : $t['sub_tugas']) ?: '-', // Pilih antara sub_tugas atau detail
        $t['jumlah']
    ]);
}

fclose($output);
exit;