<?php
// admin/api_todo_detail.php
// FIXED: Light Theme Output

require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date = $_GET['date'] ?? '';

if ($user_id <= 0 || !$date || !preg_match('/^\d{4}-\\d{2}-\\d{2}$/', $date)) {
    echo json_encode(['ok' => false, 'msg' => 'Parameter error.']);
    exit;
}

$conn = db();

// Ambil ID Masuk & Pulang
$aid_masuk = 0; $aid_pulang = 0;
$q = $conn->prepare("SELECT id, status FROM absensi WHERE user_id=? AND tanggal=? AND status IN ('masuk','pulang')");
$q->bind_param('is', $user_id, $date);
$q->execute();
$res = $q->get_result();
while($r = $res->fetch_assoc()){
    if($r['status']=='masuk') $aid_masuk = $r['id'];
    if($r['status']=='pulang') $aid_pulang = $r['id'];
}
$q->close();

// 1. HTML Rencana (Masuk) - LIGHT THEME
$masuk_html = '';
if ($aid_masuk) {
    $stmt = $conn->prepare("
        SELECT t.sumber, tm.nama_tugas, t.sub_nama, t.manual_judul, t.manual_detail, t.jumlah, t.is_done
        FROM absensi_todo t LEFT JOIN tugas_master tm ON tm.id = t.master_id WHERE t.absensi_id = ?
    ");
    $stmt->bind_param('i', $aid_masuk);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $masuk_html .= '<ul class="list-group list-group-flush">';
        while ($row = $res->fetch_assoc()) {
            $judul = $row['sumber'] === 'manual' ? $row['manual_judul'] : $row['nama_tugas'];
            $sub = $row['sumber'] === 'manual' ? $row['manual_detail'] : $row['sub_nama'];
            $jum = (int)$row['jumlah'];
            $isDone = (bool)$row['is_done'];
            
            $icon = $isDone ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-secondary"></i>';

            $masuk_html .= '<li class="list-group-item d-flex align-items-start gap-2 py-2">';
            $masuk_html .= '<div class="mt-1">'.$icon.'</div>';
            $masuk_html .= '<div><div class="fw-bold text-dark">'.e($judul).'</div>';
            if($sub) $masuk_html .= '<div class="small text-muted">'.e($sub).'</div>';
            if($jum > 1) $masuk_html .= '<span class="badge bg-light text-dark border mt-1">x'.$jum.'</span>';
            $masuk_html .= '</div></li>';
        }
        $masuk_html .= '</ul>';
    } else {
        $masuk_html = '<div class="text-muted p-2 small fst-italic">Tidak ada to-do.</div>';
    }
    $stmt->close();
} else {
    $masuk_html = '<div class="text-muted p-2 small">Belum absen masuk.</div>';
}

// 2. HTML Realisasi (Pulang) - LIGHT THEME
$pulang_html = '';
if ($aid_pulang) {
    $stmt = $conn->prepare("SELECT nama_tugas, sub_tugas, detail, jumlah, sumber FROM absensi_detail WHERE absensi_id = ?");
    $stmt->bind_param('i', $aid_pulang);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $pulang_html .= '<ul class="list-group list-group-flush">';
        while ($row = $res->fetch_assoc()) {
            $judul = $row['nama_tugas'];
            $sub = $row['sumber'] === 'manual' ? $row['detail'] : $row['sub_tugas'];
            $jum = (int)$row['jumlah'];

            $pulang_html .= '<li class="list-group-item d-flex align-items-start gap-2 py-2">';
            $pulang_html .= '<i class="bi bi-check2-square text-primary mt-1"></i>';
            $pulang_html .= '<div><div class="fw-bold text-dark">'.e($judul).'</div>';
            if($sub) $pulang_html .= '<div class="small text-muted">'.e($sub).'</div>';
            if($jum > 1) $pulang_html .= '<span class="badge bg-light text-dark border mt-1">x'.$jum.'</span>';
            $pulang_html .= '</div></li>';
        }
        $pulang_html .= '</ul>';
    } else {
        $pulang_html = '<div class="text-muted p-2 small fst-italic">Tidak ada laporan detail.</div>';
    }
    $stmt->close();
} else {
    $pulang_html = '<div class="text-muted p-2 small">Belum absen pulang.</div>';
}

echo json_encode(['ok' => true, 'masuk_html' => $masuk_html, 'pulang_html' => $pulang_html]);
exit;