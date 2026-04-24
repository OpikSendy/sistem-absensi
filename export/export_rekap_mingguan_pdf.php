<?php
// export/export_rekap_mingguan_pdf.php
// FIXED 100%: Class Extension dengan Getter Public untuk PageBreakTrigger

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

if (!is_logged_in() || !is_admin()) redirect(url('user/403.php'));
$conn = db();

$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
$mode  = $_GET['mode']  ?? 'minggu';

// --- 1. LOGIKA FILTER TANGGAL ---
if (!empty($start) && !empty($end)) {
    $date_filter = "a.waktu >= '$start 00:00:00' AND a.waktu <= '$end 23:59:59'";
    $display_period = date('d M Y', strtotime($start)) . " - " . date('d M Y', strtotime($end));
} else {
    if ($mode === 'bulan') {
        $date_filter = "MONTH(a.waktu) = MONTH(CURDATE()) AND YEAR(a.waktu) = YEAR(CURDATE())";
        $display_period = date('F Y');
    } else {
        $date_filter = "WEEK(a.waktu,1) = WEEK(CURDATE(),1) AND YEAR(a.waktu) = YEAR(CURDATE())";
        $display_period = "Minggu Ini";
    }
}

// --- 2. QUERY BAGIAN 1: MATRIX ANGKA ---
$master_names = [];
$rm = $conn->query("SELECT nama_tugas FROM tugas_master WHERE aktif = 1 ORDER BY nama_tugas ASC");
if ($rm) while ($m = $rm->fetch_assoc()) $master_names[] = $m['nama_tugas'];

$totals = [];
$t_sql = "SELECT u.id, COUNT(DISTINCT a.id) as cnt FROM users u LEFT JOIN absensi a ON u.id = a.user_id AND a.status='pulang' AND {$date_filter} WHERE u.aktif=1 GROUP BY u.id";
$tr = $conn->query($t_sql);
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

$rekap_data = [];
foreach ($rekap_map as $info) {
    $row = ['username' => $info['username'], 'total_absensi' => $info['total_absensi']];
    foreach ($master_names as $mn) $row[$mn] = (int)($info['tasks'][$mn] ?? 0);
    $row['Lainnya'] = (int)($info['tasks']['Lainnya'] ?? 0);
    $rekap_data[] = $row;
}

// --- 3. QUERY BAGIAN 2: RINCIAN TUGAS MANUAL ---
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
$manual_data = $manual_res ? $manual_res->fetch_all(MYSQLI_ASSOC) : [];


// --- 4. EXTENDED FPDF CLASS (SOLUSI ERROR) ---
class PDF_Rekap extends FPDF {
    
    // Fungsi untuk mendapatkan PageBreakTrigger (karena properti aslinya protected)
    public function getBreakTrigger() {
        return $this->PageBreakTrigger;
    }

    // Fungsi hitung jumlah baris text (MultiCell)
    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw']; // Akses properti protected diizinkan di dalam class
        if($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb > 0 && $s[$nb-1] == "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if($c == ' ') $sep = $i;
            $l += $cw[$c];
            if($l > $wmax) {
                if($sep == -1) { if($i == $j) $i++; }
                else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

// --- 5. PDF GENERATION ---
$pdf = new PDF_Rekap('L', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 15);

// BAGIAN 1: MATRIX ANGKA
$chunks = array_chunk($master_names, 7);
if (empty($chunks)) $chunks = [[]];

foreach ($chunks as $pageIndex => $chunk) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,"Rekap Absensi & Tugas ({$display_period})",0,1,'C');
    $pdf->SetFont('Arial','I',10);
    $pdf->Cell(0,6,"Bagian 1: Ringkasan Jumlah",0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(240,240,240);
    
    $wUser = 45; $wTotal = 25; $wLainnya = 25;
    $availableW = 277 - $wUser - $wTotal - (($pageIndex==count($chunks)-1)?$wLainnya:0);
    $wTask = count($chunk) > 0 ? floor($availableW / count($chunk)) : 0;

    $pdf->Cell($wUser, 10, 'Karyawan', 1, 0, 'L', true);
    $pdf->Cell($wTotal, 10, "Total Hadir", 1, 0, 'C', true);
    foreach ($chunk as $mn) {
        $head = strlen($mn)>13 ? substr($mn,0,11).'..' : $mn;
        $pdf->Cell($wTask, 10, $head, 1, 0, 'C', true);
    }
    if ($pageIndex == count($chunks)-1) $pdf->Cell($wLainnya, 10, "Lainnya", 1, 0, 'C', true);
    $pdf->Ln();

    $pdf->SetFont('Arial','',8);
    foreach ($rekap_data as $row) {
        $pdf->Cell($wUser, 8, $row['username'], 1);
        $pdf->Cell($wTotal, 8, $row['total_absensi'], 1, 0, 'C');
        foreach ($chunk as $mn) {
            $val = $row[$mn] ?? 0;
            $pdf->SetTextColor($val==0?180:0);
            $pdf->Cell($wTask, 8, $val, 1, 0, 'C');
            $pdf->SetTextColor(0);
        }
        if ($pageIndex == count($chunks)-1) $pdf->Cell($wLainnya, 8, $row['Lainnya'], 1, 0, 'C');
        $pdf->Ln();
    }
}

// BAGIAN 2: LAMPIRAN TUGAS MANUAL
if (!empty($manual_data)) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,"Lampiran: Rincian Tugas Manual / Lainnya",0,1,'L');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,6,"Detail aktivitas yang tidak masuk kategori Master Tugas.",0,1,'L');
    $pdf->Ln(5);

    // Header Lampiran
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(230,230,250); 
    
    $wDate = 30;
    $wName = 45;
    $wTitle = 60;
    $wDesc = 142;

    $pdf->Cell($wDate, 8, 'Tanggal', 1, 0, 'C', true);
    $pdf->Cell($wName, 8, 'Karyawan', 1, 0, 'L', true);
    $pdf->Cell($wTitle, 8, 'Judul Tugas', 1, 0, 'L', true);
    $pdf->Cell($wDesc, 8, 'Keterangan Detail', 1, 1, 'L', true);

    $pdf->SetFont('Arial','',8);
    
    foreach ($manual_data as $m) {
        $cellWidths = [$wDate, $wName, $wTitle, $wDesc];
        $cellData = [
            date('d M Y', strtotime($m['tanggal'])),
            $m['nama'],
            $m['nama_tugas'] . ($m['jumlah']>1 ? " (x{$m['jumlah']})" : ""),
            $m['detail'] ?: '-'
        ];

        // 1. HITUNG BARIS (Menggunakan method class)
        $nb = 0;
        for($i=0; $i<4; $i++) {
            $nb = max($nb, $pdf->NbLines($cellWidths[$i], $cellData[$i]));
        }
        $h = 5 * $nb;

        // 2. CEK PAGE BREAK (Menggunakan method getter class yang aman)
        // Gunakan $pdf->getBreakTrigger() sebagai pengganti $pdf->PageBreakTrigger
        if($pdf->GetY() + $h > $pdf->getBreakTrigger()) {
            $pdf->AddPage();
            // Reprint Header
            $pdf->SetFont('Arial','B',9);
            $pdf->SetFillColor(230,230,250);
            $pdf->Cell($wDate, 8, 'Tanggal', 1, 0, 'C', true);
            $pdf->Cell($wName, 8, 'Karyawan', 1, 0, 'L', true);
            $pdf->Cell($wTitle, 8, 'Judul Tugas', 1, 0, 'L', true);
            $pdf->Cell($wDesc, 8, 'Keterangan Detail', 1, 1, 'L', true);
            $pdf->SetFont('Arial','',8);
        }

        // 3. CETAK BARIS
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        
        $pdf->Rect($x, $y, $wDate, $h);
        $pdf->MultiCell($wDate, 5, $cellData[0], 0, 'C');
        $pdf->SetXY($x+$wDate, $y);
        
        $pdf->Rect($x+$wDate, $y, $wName, $h);
        $pdf->MultiCell($wName, 5, $cellData[1], 0, 'L');
        $pdf->SetXY($x+$wDate+$wName, $y);
        
        $pdf->Rect($x+$wDate+$wName, $y, $wTitle, $h);
        $pdf->MultiCell($wTitle, 5, $cellData[2], 0, 'L');
        $pdf->SetXY($x+$wDate+$wName+$wTitle, $y);
        
        $pdf->Rect($x+$wDate+$wName+$wTitle, $y, $wDesc, $h);
        $pdf->MultiCell($wDesc, 5, $cellData[3], 0, 'L');
        
        $pdf->SetXY($x, $y+$h);
    }
}

$pdf->Output('I', 'Laporan_Lengkap_Absensi.pdf');