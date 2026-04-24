<?php
require_once __DIR__ . '/../includes/bootstrap.php'; // Menggunakan bootstrap.php
require_once __DIR__.'/../libs/fpdf/fpdf.php'; // Path ke FPDF

if (!is_logged_in() || !is_admin()){
  header("Location: " . url('user/403.php')); exit; // Gunakan helper url()
}

$conn = db(); // Gunakan fungsi db()

$id=(int)($_GET['id']??0);
if($id<=0) die("Invalid ID");

// ambil absensi
$stmt=$conn->prepare("SELECT a.*, u.username FROM absensi a JOIN users u ON u.id=a.user_id WHERE a.id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$abs=$stmt->get_result()->fetch_assoc();
if(!$abs) die("Not found");

// ambil tugas (absensi_detail)
$stmt2=$conn->prepare("SELECT * FROM absensi_detail WHERE absensi_id=?");
$stmt2->bind_param("i",$id);
$stmt2->execute();
$tugas=$stmt2->get_result();

// PDF
$pdf=new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,"Detail Absensi #".$id,0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',11);
$pdf->Cell(40,8,"User: ".$abs['username'],0,1);
$pdf->Cell(40,8,"Waktu: ".$abs['waktu'],0,1);
$pdf->Cell(40,8,"Status: ".$abs['status'],0,1);
$pdf->Cell(40,8,"Approval: ".$abs['approval_status'],0,1);
$pdf->Cell(40,8,"Lat: ".$abs['lat'],0,1); // Pisahkan Lat dan Lng
$pdf->Cell(40,8,"Lng: ".$abs['lng'],0,1);
if($abs['lokasi_text']) $pdf->Cell(40,8,"Lokasi Text: ".$abs['lokasi_text'],0,1);
$pdf->Cell(40,8,"IP Client: ".$abs['ip_client'],0,1);
$pdf->Cell(40,8,"User Agent: ".$abs['user_agent'],0,1);
$pdf->Cell(40,8,"Kendala Hari Ini: ".$abs['kendala_hari_ini'],0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',9); // Ukuran font lebih kecil
$pdf->Cell(10,8,"No",1);
$pdf->Cell(60,8,"Nama Tugas",1);
$pdf->Cell(30,8,"Sumber",1);
$pdf->Cell(60,8,"Sub/Detail",1); // Ubah label
$pdf->Cell(20,8,"Jumlah",1);
$pdf->Ln();

$pdf->SetFont('Arial','',8); // Ukuran font lebih kecil untuk data
$no=1;
while($t=$tugas->fetch_assoc()){
  $pdf->Cell(10,8,$no++,1);
  $pdf->Cell(60,8,$t['nama_tugas'],1);
  $pdf->Cell(30,8,$t['sumber'],1);
  $pdf->Cell(60,8,($t['sumber'] === 'manual' ? $t['detail'] : $t['sub_tugas']) ?: '-',1); // Pilih antara sub_tugas atau detail
  $pdf->Cell(20,8,$t['jumlah'],1);
  $pdf->Ln();
}

$pdf->Output("D","detail_absensi_$id.pdf");
exit;