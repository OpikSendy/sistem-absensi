<?php
// export/export_dashboard_pdf.php
// FIXED: Menampilkan Nama Shift (Bukan ID) + Layout Rapi

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

if (!is_logged_in() || !is_admin()) {
    header("Location: " . url('user/403.php'));
    exit;
}

$conn = db();

// Ambil filter
$user_filter = trim($_GET['user'] ?? '');
$from = trim($_GET['d1'] ?? '');
$to   = trim($_GET['d2'] ?? '');
$status = trim($_GET['status'] ?? '');
$approval = trim($_GET['approval'] ?? '');

// QUERY UTAMA (DIPERBAIKI: JOIN ke shift_master)
$sql = "SELECT a.id AS absensi_id, a.waktu, a.status, a.approval_status, a.telat_menit, 
               u.username, u.nama,
               sm.nama_shift, sm.jam_masuk, sm.jam_pulang
        FROM absensi a
        JOIN users u ON u.id = a.user_id
        LEFT JOIN shift_master sm ON sm.id = a.shift_id
        WHERE 1=1";

$params = []; $types = '';

if ($from && $to) { $sql .= " AND DATE(a.waktu) BETWEEN ? AND ?"; $params[]=$from;$params[]=$to;$types.="ss";}
elseif ($from) { $sql .= " AND DATE(a.waktu) >= ?"; $params[]=$from;$types.="s";}
elseif ($to) { $sql .= " AND DATE(a.waktu) <= ?"; $params[]=$to;$types.="s";}

if ($status){ $sql .= " AND a.status=?"; $params[]=$status;$types.="s"; }
if ($approval){ $sql .= " AND a.approval_status=?"; $params[]=$approval;$types.="s"; }
if ($user_filter !== '') { $sql .= " AND (u.username LIKE ? OR u.nama LIKE ?)"; $p='%'.$user_filter.'%'; $params[]=$p; $params[]=$p; $types.="ss"; }

$sql .= " ORDER BY a.waktu DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Helper Ringkasan To-Do
function get_ringkas_for_pdf($conn, $id, $status) {
    $items = [];
    if ($status === 'masuk') {
        $q = $conn->prepare("
            SELECT t.sumber, t.jumlah,
                   CASE WHEN t.sumber='manual' THEN t.manual_judul ELSE tm.nama_tugas END AS judul,
                   CASE WHEN t.sumber='manual' THEN t.manual_detail ELSE t.sub_nama END AS sub
            FROM absensi_todo t
            LEFT JOIN tugas_master tm ON tm.id=t.master_id
            WHERE t.absensi_id=? LIMIT 10
        ");
    } else {
        $q = $conn->prepare("SELECT nama_tugas, sub_tugas, detail, jumlah, sumber FROM absensi_detail WHERE absensi_id=? LIMIT 10");
    }
    $q->bind_param('i', $id);
    $q->execute();
    $rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();

    foreach ($rows as $r) {
        if (isset($r['judul'])) {
            $txt = $r['judul'];
            $sub = $r['sub'] ?? '';
        } else {
            $txt = $r['nama_tugas'] ?? '-';
            $sub = ($r['sumber'] === 'manual') ? ($r['detail'] ?? '') : ($r['sub_tugas'] ?? '');
        }
        if ($sub) $txt .= " ({$sub})";
        if ((int)($r['jumlah']??1) > 1) $txt .= " x".(int)$r['jumlah'];
        $items[] = $txt;
    }
    $s = implode('; ', $items) ?: '-';
    if (mb_strlen($s) > 2500) $s = mb_substr($s,0,2500) . '...';
    return $s;
}

// Class PDF dengan NbLines
class PDF_Log extends FPDF {
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0) $w=$this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r",'',$txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n") $nb--;
        $sep=-1; $i=0; $j=0; $l=0; $nl=1;
        while($i<$nb) {
            $c=$s[$i];
            if($c=="\n") { $i++; $sep=-1; $j=$i; $l=0; $nl++; continue; }
            if($c==' ') $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax) {
                if($sep==-1) { if($i==$j) $i++; }
                else $i=$sep+1;
                $sep=-1; $j=$i; $l=0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

$pdf = new PDF_Log('L','mm','A4');
$bottomMargin = 15;
$pdf->SetAutoPageBreak(true, $bottomMargin);
$pdf->AddPage();

// Header
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Laporan Dashboard Absensi',0,1,'C');
$pdf->Ln(2);

// Lebar Kolom
$colWidths = [
    'no' => 10,
    'user' => 45,
    'waktu' => 35,
    'status' => 20,
    'approval' => 25,
    'telat' => 20,
    'shift' => 30,  // Sedikit diperlebar untuk nama shift
    'todo' => 90    // Sisanya
];

function print_header($pdf, $cols) {
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell($cols['no'],8,'No',1,0,'C',true);
    $pdf->Cell($cols['user'],8,'User',1,0,'C',true);
    $pdf->Cell($cols['waktu'],8,'Waktu',1,0,'C',true);
    $pdf->Cell($cols['status'],8,'Status',1,0,'C',true);
    $pdf->Cell($cols['approval'],8,'Approval',1,0,'C',true);
    $pdf->Cell($cols['telat'],8,'Telat',1,0,'C',true);
    $pdf->Cell($cols['shift'],8,'Shift',1,0,'C',true);
    $pdf->Cell($cols['todo'],8,'To-Do Ringkas',1,1,'C',true);
}

print_header($pdf, $colWidths);

$pdf->SetFont('Arial','',8);
$lineHeight = 5;
$no = 1;

while ($row = $res->fetch_assoc()) {
    $ringkas = get_ringkas_for_pdf($conn, $row['absensi_id'], $row['status']);
    $userText = $row['nama'] ?: $row['username'];
    
    // Tampilkan Nama Shift, jika kosong tampilkan '-'
    $shiftText = $row['nama_shift'] ?? '-';
    
    // Hitung tinggi baris
    $lines = [];
    $lines[] = $pdf->NbLines($colWidths['user'], $userText);
    $lines[] = $pdf->NbLines($colWidths['todo'], $ringkas);
    $lines[] = $pdf->NbLines($colWidths['shift'], $shiftText);
    
    $maxLines = max($lines);
    $h = $lineHeight * $maxLines;

    // Cek Page Break
    if ($pdf->GetY() + $h > $pdf->GetPageHeight() - $bottomMargin) {
        $pdf->AddPage();
        print_header($pdf, $colWidths);
    }

    // Cetak
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // 1. No
    $pdf->Rect($x, $y, $colWidths['no'], $h);
    $pdf->MultiCell($colWidths['no'], 5, $no++, 0, 'C');
    
    // 2. User
    $pdf->SetXY($x + $colWidths['no'], $y);
    $pdf->Rect($pdf->GetX(), $y, $colWidths['user'], $h);
    $pdf->MultiCell($colWidths['user'], 5, $userText, 0, 'L');
    
    // 3. Waktu
    $pdf->SetXY($x + $colWidths['no'] + $colWidths['user'], $y);
    $pdf->Rect($pdf->GetX(), $y, $colWidths['waktu'], $h);
    $pdf->MultiCell($colWidths['waktu'], $h, $row['waktu'], 0, 'C');
    
    // 4. Status
    $pdf->SetXY($x + $colWidths['no'] + $colWidths['user'] + $colWidths['waktu'], $y);
    $pdf->Rect($pdf->GetX(), $y, $colWidths['status'], $h);
    $pdf->MultiCell($colWidths['status'], $h, ucfirst($row['status']), 0, 'C');
    
    // 5. Approval
    $pdf->SetXY($x + $colWidths['no'] + $colWidths['user'] + $colWidths['waktu'] + $colWidths['status'], $y);
    $pdf->Rect($pdf->GetX(), $y, $colWidths['approval'], $h);
    $pdf->MultiCell($colWidths['approval'], $h, $row['approval_status'], 0, 'C');
    
    // 6. Telat
    $pdf->SetXY($x + $colWidths['no'] + $colWidths['user'] + $colWidths['waktu'] + $colWidths['status'] + $colWidths['approval'], $y);
    $pdf->Rect($pdf->GetX(), $y, $colWidths['telat'], $h);
    $pdf->MultiCell($colWidths['telat'], $h, (int)$row['telat_menit'] . ' m', 0, 'C');

    // 7. Shift (FIXED: Nama Shift)
    $pdf->SetXY($x + $colWidths['no'] + $colWidths['user'] + $colWidths['waktu'] + $colWidths['status'] + $colWidths['approval'] + $colWidths['telat'], $y);
    $pdf->Rect($pdf->GetX(), $y, $colWidths['shift'], $h);
    $pdf->MultiCell($colWidths['shift'], 5, $shiftText, 0, 'C'); // Menggunakan 5 agar bisa wrap text jika nama shift panjang

    // 8. To-Do
    $pdf->SetXY($x + $colWidths['no'] + $colWidths['user'] + $colWidths['waktu'] + $colWidths['status'] + $colWidths['approval'] + $colWidths['telat'] + $colWidths['shift'], $y);
    $pdf->Rect($pdf->GetX(), $y, $colWidths['todo'], $h);
    $pdf->MultiCell($colWidths['todo'], 5, $ringkas, 0, 'L');

    $pdf->SetXY($x, $y + $h);
}

$pdf->Output('I', 'Log_Harian.pdf');