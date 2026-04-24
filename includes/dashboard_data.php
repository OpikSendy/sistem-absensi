<?php
// admin/form_aksi.php (Contoh yang harus Anda terapkan)
require_once __DIR__ . '/../includes/bootstrap.php';
$active_menu = 'aksi'; // Pastikan ini diatur di file 'form_aksi.php'
// ...
// includes/dashboard_data.php
// Pastikan $conn sudah tersedia (include db.php sebelumnya)

if (!isset($userId) || !isset($isAdmin)) {
    throw new Exception('Parameter $userId dan $isAdmin harus diset sebelum include file ini.');
}

$JAM_PATOKAN = 'Donut Bar'; // Default jam patokan telat
$ymFilter = "YEAR(waktu)=YEAR(CURDATE()) AND MONTH(waktu)=MONTH(CURDATE())"; // Filter untuk bulan ini

// --- START: approval counts untuk user (all-time + bulan ini) ---
// Pastikan $conn dan $userId tersedia (file ini sudah mengharuskannya)
$count_approved_all  = 0;
$count_rejected_all  = 0;
$count_pending_all   = 0;

if ($stmt = $conn->prepare("SELECT approval_status, COUNT(1) AS cnt FROM absensi WHERE user_id = ? GROUP BY approval_status")) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $status = $r['approval_status'] ?? '';
        $cnt = (int)$r['cnt'];
        if ($status === 'Disetujui') $count_approved_all = $cnt;
        elseif ($status === 'Ditolak') $count_rejected_all = $cnt;
        elseif ($status === 'Pending') $count_pending_all = $cnt;
    }
    $stmt->close();
}

// Bulanan (bulan ini) — gunakan filter YEAR/MONTH pada kolom waktu
$count_approved_month = 0;
$count_rejected_month = 0;
$count_pending_month  = 0;

$ymWhere = "YEAR(waktu)=YEAR(CURDATE()) AND MONTH(waktu)=MONTH(CURDATE())";
if ($stmt2 = $conn->prepare("SELECT approval_status, COUNT(1) AS cnt FROM absensi WHERE user_id = ? AND {$ymWhere} GROUP BY approval_status")) {
    $stmt2->bind_param('i', $userId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
        $status = $r['approval_status'] ?? '';
        $cnt = (int)$r['cnt'];
        if ($status === 'Disetujui') $count_approved_month = $cnt;
        elseif ($status === 'Ditolak') $count_rejected_month = $cnt;
        elseif ($status === 'Pending') $count_pending_month = $cnt;
    }
    $stmt2->close();
}

// expose array berguna bila ingin menggunakannya lain (opsional)
$user_approval_counts = [
    'all' => [
        'disetujui' => $count_approved_all,
        'ditolak'   => $count_rejected_all,
        'pending'   => $count_pending_all,
    ],
    'month' => [
        'disetujui' => $count_approved_month,
        'ditolak'   => $count_rejected_month,
        'pending'   => $count_pending_month,
    ]
];
// --- END: approval counts ---



// Fungsi untuk mendapatkan jadwal user hari ini (sudah ada, pertahankan)
function getUserScheduleToday($user_id, $conn) {
    $schedule = [
        'jam_masuk' => '08:00:00', // Default
        'toleransi_menit' => 10,   // Default
        'status' => 'ON',          // Default
        'durasi_menit' => 480      // Default fallback 8 jam
    ];

    // Cek user_jadwal (prioritas tertinggi)
    $stmt = $conn->prepare("SELECT uj.jam_masuk, uj.jam_pulang, uj.status, sm.toleransi_menit, sm.durasi_menit
                            FROM user_jadwal uj
                            LEFT JOIN shift_master sm ON uj.shift_id = sm.id
                            WHERE uj.user_id = ? AND uj.tanggal = CURDATE()");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $schedule['jam_masuk'] = $row['jam_masuk'] ?? $schedule['jam_masuk'];
            $schedule['toleransi_menit'] = (int)($row['toleransi_menit'] ?? $schedule['toleransi_menit']);
            $schedule['durasi_menit'] = (int)($row['durasi_menit'] ?? $schedule['durasi_menit']);
            $schedule['status'] = $row['status'] ?? $schedule['status'];
            return $schedule;
        }
        $stmt->close();
    }

    // Cek user_shift (prioritas kedua)
    $stmt = $conn->prepare("SELECT sm.jam_masuk, sm.toleransi_menit, sm.durasi_menit
                            FROM user_shift us
                            JOIN shift_master sm ON us.shift_id = sm.id
                            WHERE us.user_id = ? AND us.aktif = 1");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $schedule['jam_masuk'] = $row['jam_masuk'] ?? $schedule['jam_masuk'];
            $schedule['toleransi_menit'] = (int)($row['toleransi_menit'] ?? $schedule['toleransi_menit']);
            $schedule['durasi_menit'] = (int)($row['durasi_menit'] ?? $schedule['durasi_menit']);
            return $schedule;
        }
        $stmt->close();
    }

    return $schedule;
}



if ($isAdmin) {
    $totalAbsensi = (int)($conn->query("SELECT COUNT(*) as jml FROM absensi WHERE $ymFilter")->fetch_assoc()['jml'] ?? 0);

    // Query untuk menghitung keterlambatan dengan mempertimbangkan shift/jadwal
    $qTel = $conn->query("
        SELECT COUNT(*) as jml
        FROM absensi a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN user_jadwal uj ON uj.user_id = a.user_id AND uj.tanggal = DATE(a.waktu)
        LEFT JOIN user_shift us ON us.user_id = a.user_id AND us.aktif = 1
        LEFT JOIN shift_master sm ON sm.id = COALESCE(uj.shift_id, us.shift_id)
        WHERE $ymFilter
        AND a.status = 'masuk'
        AND TIME(a.waktu) > ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60))
    ");
    $totalTelat = (int)($qTel->fetch_assoc()['jml'] ?? 0);

    $totalUsers = (int)($conn->query("SELECT COUNT(*) as jml FROM users WHERE aktif = 1")->fetch_assoc()['jml'] ?? 0);

    $qDn = $conn->query("
        SELECT
          SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) <= ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS ontime,
          SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) >  ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS telat
        FROM absensi a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN user_jadwal uj ON uj.user_id = a.user_id AND uj.tanggal = DATE(a.waktu)
        LEFT JOIN user_shift us ON us.user_id = a.user_id AND us.aktif = 1
        LEFT JOIN shift_master sm ON sm.id = COALESCE(uj.shift_id, us.shift_id)
        WHERE $ymFilter
    ");

    $res = $conn->query("
        SELECT u.nama,
               SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) <= ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS ontime,
               SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) >  ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS telat
        FROM users u
        LEFT JOIN absensi a ON a.user_id=u.id AND $ymFilter
        LEFT JOIN user_jadwal uj ON uj.user_id = u.id AND uj.tanggal = DATE(a.waktu)
        LEFT JOIN user_shift us ON us.user_id = u.id AND us.aktif = 1
        LEFT JOIN shift_master sm ON sm.id = COALESCE(uj.shift_id, us.shift_id)
        WHERE u.aktif=1
        GROUP BY u.id, u.nama
        ORDER BY u.nama
    ");

    $barTitle = 'Kedisiplinan Tim (bulan ini)';
} else { // User biasa
    $totalAbsensi = (int)($conn->query("
        SELECT COUNT(*) as jml
        FROM absensi
        WHERE $ymFilter AND user_id = $userId
    ")->fetch_assoc()['jml'] ?? 0);

    

    $qTel = $conn->query("
        SELECT COUNT(*) as jml
        FROM absensi a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN user_jadwal uj ON uj.user_id = a.user_id AND uj.tanggal = DATE(a.waktu)
        LEFT JOIN user_shift us ON us.user_id = a.user_id AND us.aktif = 1
        LEFT JOIN shift_master sm ON sm.id = COALESCE(uj.shift_id, us.shift_id)
        WHERE $ymFilter
        AND a.user_id = $userId
        AND a.status = 'masuk'
        AND TIME(a.waktu) > ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60))
    ");
    $totalTelat = (int)($qTel->fetch_assoc()['jml'] ?? 0);

    // Total user aktif tidak relevan untuk user biasa, bisa diubah atau dihapus
    $totalUsers = (int)($conn->query("SELECT COUNT(*) as jml FROM users WHERE aktif = 1")->fetch_assoc()['jml'] ?? 0); 

    $qDn = $conn->query("
        SELECT
          SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) <= ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS ontime,
          SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) >  ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS telat
        FROM absensi a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN user_jadwal uj ON uj.user_id = a.user_id AND uj.tanggal = DATE(a.waktu)
        LEFT JOIN user_shift us ON us.user_id = a.user_id AND us.aktif = 1
        LEFT JOIN shift_master sm ON sm.id = COALESCE(uj.shift_id, us.shift_id)
        WHERE $ymFilter AND a.user_id=$userId
    ");

    // Query untuk bar chart user biasa: per hari dalam bulan ini
    $res = $conn->query("
        SELECT DATE(a.waktu) AS tgl,
               SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) <= ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS ontime,
               SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) >  ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END) AS telat
        FROM absensi a
        LEFT JOIN users u ON u.id = a.user_id
        LEFT JOIN user_jadwal uj ON uj.user_id = a.user_id AND uj.tanggal = DATE(a.waktu)
        LEFT JOIN user_shift us ON us.user_id = a.user_id AND us.aktif = 1
        LEFT JOIN shift_master sm ON sm.id = COALESCE(uj.shift_id, us.shift_id)
        WHERE $ymFilter AND a.user_id=$userId
        GROUP BY DATE(a.waktu)
        ORDER BY tgl
    ");

    $barTitle = 'Kedisiplinan Saya (bulan ini, per tanggal)';
}

$dn = $qDn->fetch_assoc() ?: ['ontime'=>0,'telat'=>0];
$donutData = [
    'labels' => ['On‑time','Telat'],
    'data'   => [ (int)$dn['ontime'], (int)$dn['telat'] ]
];

$labels = $barOnt = $barTel = [];
while($r = $res->fetch_assoc()){
    if ($isAdmin) {
        $labels[] = $r['nama'] ?: 'User '.$r['id'];
        $barOnt[] = (int)$r['ontime'];
        $barTel[] = (int)$r['telat'];
    } else {
        $labels[] = $r['tgl'];
        $barOnt[] = (int)$r['ontime'];
        $barTel[] = (int)$r['telat'];
    }
}

// Top 5 disiplin (untuk semua user, kecuali admin)
$topUsers = [];
$resTop = $conn->query("
  SELECT u.nama,
         ROUND(
           (SUM(CASE WHEN a.status='masuk' AND TIME(a.waktu) <= ADDTIME(COALESCE(uj.jam_masuk, sm.jam_masuk, '$JAM_PATOKAN'), SEC_TO_TIME(COALESCE(sm.toleransi_menit, 0) * 60)) THEN 1 ELSE 0 END)
           / NULLIF(SUM(CASE WHEN a.status='masuk' THEN 1 ELSE 0 END),0))*100,1
         ) AS pct
  FROM users u
  LEFT JOIN absensi a ON a.user_id=u.id AND $ymFilter
  LEFT JOIN user_jadwal uj ON uj.user_id = u.id AND uj.tanggal = DATE(a.waktu)
  LEFT JOIN user_shift us ON us.user_id = u.id AND us.aktif = 1
  LEFT JOIN shift_master sm ON sm.id = COALESCE(uj.shift_id, us.shift_id)
  WHERE u.aktif=1 AND u.role != 'admin' -- Exclude admin
  GROUP BY u.id, u.nama
  HAVING SUM(CASE WHEN a.status='masuk' THEN 1 ELSE 0 END) > 0 -- Only include users with at least one 'masuk' record
  ORDER BY pct DESC
  LIMIT 5
");
while($r=$resTop->fetch_assoc()){ $topUsers[] = $r; }

// Aktivitas terbaru (admin saja)
$latest = [];
if ($isAdmin) {
    $resLt = $conn->query("
        SELECT a.id, a.user_id, u.nama, a.waktu, a.status, a.approval_status
        FROM absensi a
        JOIN users u ON u.id=a.user_id
        ORDER BY a.waktu DESC
        LIMIT 8
    ");
    while($r=$resLt->fetch_assoc()){ $latest[] = $r; }
}

// --- START: approval counts untuk minggu ini ---
$count_approved_week = 0;
$count_rejected_week = 0;
$count_pending_week  = 0;

$weekWhere = "YEAR(waktu)=YEAR(CURDATE()) AND WEEK(waktu)=WEEK(CURDATE())";
if ($stmt3 = $conn->prepare("SELECT approval_status, COUNT(1) AS cnt FROM absensi WHERE user_id = ? AND {$weekWhere} GROUP BY approval_status")) {
    $stmt3->bind_param('i', $userId);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    while ($r = $res3->fetch_assoc()) {
        $status = $r['approval_status'] ?? '';
        $cnt = (int)$r['cnt'];
        if ($status === 'Disetujui') $count_approved_week = $cnt;
        elseif ($status === 'Ditolak') $count_rejected_week = $cnt;
        elseif ($status === 'Pending') $count_pending_week = $cnt;
    }
    $stmt3->close();
}
// --- END: approval counts untuk minggu ini ---
