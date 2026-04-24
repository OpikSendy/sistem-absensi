<?php
// export/export_dashboard_csv.php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in() || !is_admin()) {
    header('Location: ' . url('user/403.php'));
    exit;
}

$conn = db();
$user_filter = trim($_GET['user'] ?? '');
$from = trim($_GET['d1'] ?? '');
$to   = trim($_GET['d2'] ?? '');
$status = trim($_GET['status'] ?? '');
$approval = trim($_GET['approval'] ?? '');

// same SQL as excel
$sql = "SELECT a.id AS absensi_id, a.waktu, a.status, a.approval_status, a.telat_menit, a.shift_id, u.username, u.nama
        FROM absensi a
        JOIN users u ON u.id = a.user_id
        WHERE 1=1";
$params = []; $types = '';

if ($from && $to) { $sql .= " AND DATE(a.waktu) BETWEEN ? AND ?"; $params[]=$from;$params[]=$to;$types.="ss";}
elseif ($from) { $sql .= " AND DATE(a.waktu) >= ?"; $params[]=$from;$types.="s";}
elseif ($to) { $sql .= " AND DATE(a.waktu) <= ?"; $params[]=$to;$types.="s";}
if ($status){ $sql .= " AND a.status=?"; $params[]=$status;$types.="s"; }
if ($approval){ $sql .= " AND a.approval_status=?"; $params[]=$approval;$types.="s"; }
if ($user_filter !== '') { $sql .= " AND u.username LIKE ?"; $params[] = '%' . $user_filter . '%'; $types .= 's'; }

$sql .= " ORDER BY a.waktu DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// helper sama seperti di excel
function get_ringkas_for_export_csv($conn, $absensi_id, $status) {
    // boleh reuse code, tapi disederhanakan di sini
    $items = [];
    if ($status === 'masuk') {
        $q = $conn->prepare("
            SELECT t.sumber, t.jumlah,
                   CASE WHEN t.sumber = 'manual' THEN t.manual_judul ELSE tm.nama_tugas END AS judul,
                   CASE WHEN t.sumber = 'manual' THEN t.manual_detail ELSE t.sub_nama END AS sub
            FROM absensi_todo t
            LEFT JOIN tugas_master tm ON tm.id = t.master_id
            WHERE t.absensi_id = ?
            LIMIT 10
        ");
        $q->bind_param('i', $absensi_id);
        $q->execute();
        $rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
        $q->close();
        foreach ($rows as $r) {
            $txt = $r['judul'] ?: '-';
            if (!empty($r['sub'])) $txt .= " ({$r['sub']})";
            if ((int)($r['jumlah'] ?? 0) > 1) $txt .= " x" . (int)$r['jumlah'];
            $items[] = $txt;
        }
    } else {
        $q = $conn->prepare("SELECT nama_tugas, sub_tugas, detail, jumlah, sumber FROM absensi_detail WHERE absensi_id = ? LIMIT 10");
        $q->bind_param('i', $absensi_id);
        $q->execute();
        $rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
        $q->close();
        foreach ($rows as $r) {
            $txt = $r['nama_tugas'] ?: '-';
            $sub = ($r['sumber'] === 'manual') ? $r['detail'] : $r['sub_tugas'];
            if (!empty($sub)) $txt .= " ({$sub})";
            if ((int)($r['jumlah'] ?? 0) > 1) $txt .= " x" . (int)$r['jumlah'];
            $items[] = $txt;
        }
    }
    $s = implode('; ', $items);
    if (strlen($s) > 1000) $s = substr($s,0,1000).'...';
    return $s ?: '-';
}

// output CSV
$filename = 'dashboard_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
echo "\xEF\xBB\xBF"; // BOM for Excel

$fh = fopen('php://output', 'w');
fputcsv($fh, ['User','Waktu','Status','Approval','Telat (menit)','Shift ID','To-Do Ringkas']);

while($row = $res->fetch_assoc()){
    $ringkas = get_ringkas_for_export_csv($conn, $row['absensi_id'], $row['status']);
    fputcsv($fh, [
        $row['username'] . ($row['nama'] ? " / {$row['nama']}" : ''),
        $row['waktu'],
        ucfirst($row['status']),
        $row['approval_status'],
        (int)($row['telat_menit'] ?? 0),
        $row['shift_id'] ?? '',
        $ringkas
    ]);
}
fclose($fh);
exit;
