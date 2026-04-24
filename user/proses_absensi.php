<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/bootstrap.php'; // db(), current_user(), csrf_ok_post(), ROOT, url(), is_logged_in()

// ---------------------------
// Helpers (single definitions)
// ---------------------------
if (!function_exists('jexit')) {
    function jexit(bool $ok, string $msg, array $extra = [], ?string $redirect_url = null) {
        $payload = ['ok' => $ok, 'msg' => $msg];
        if (!empty($extra)) $payload['data'] = $extra;
        if ($redirect_url) $payload['redirect_url'] = $redirect_url;
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('get_client_ip')) {
    function get_client_ip(): string {
        $keys = [
            'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'
        ];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $ips = explode(',', $_SERVER[$k]);
                $ip = trim(reset($ips));
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('getEffectiveShiftForUser')) {
    function getEffectiveShiftForUser(mysqli $conn, int $userId, string $tanggal): array {
        $result = [
            'shift_id' => null,
            'jam_masuk' => '',
            'jam_pulang' => null,
            'toleransi_menit' => 10,
            'durasi_menit' => 480,
            'status' => 'ON'
        ];

        // 1) view
        $stmtV = $conn->prepare("SELECT shift_id, jam_masuk, toleransi_menit, durasi_menit, status FROM v_user_jadwal_hari_ini WHERE user_id = ? AND tanggal = ? LIMIT 1");
        if ($stmtV) {
            $stmtV->bind_param('is', $userId, $tanggal);
            $stmtV->execute();
            $rV = $stmtV->get_result()->fetch_assoc();
            $stmtV->close();
            if ($rV) {
                $result['shift_id'] = !empty($rV['shift_id']) ? (int)$rV['shift_id'] : null;
                $result['jam_masuk'] = $rV['jam_masuk'] ?: $result['jam_masuk'];
                $result['toleransi_menit'] = (int)($rV['toleransi_menit'] ?? $result['toleransi_menit']);
                $result['durasi_menit'] = (int)($rV['durasi_menit'] ?? $result['durasi_menit']);
                $result['status'] = $rV['status'] ?? $result['status'];
                return $result;
            }
        }

        // 2) user_jadwal
        $stmt = $conn->prepare("
            SELECT uj.shift_id, uj.jam_masuk AS uj_jam_masuk, uj.jam_pulang AS uj_jam_pulang, uj.status AS uj_status,
                   sm.jam_masuk AS sm_jam_masuk, sm.jam_pulang AS sm_jam_pulang,
                   COALESCE(sm.toleransi_menit, 10) AS toleransi_menit,
                   COALESCE(sm.durasi_menit, 480) AS durasi_menit
            FROM user_jadwal uj
            LEFT JOIN shift_master sm ON sm.id = uj.shift_id
            WHERE uj.user_id = ? AND uj.tanggal = ? LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param('is', $userId, $tanggal);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($r) {
                $result['shift_id'] = !empty($r['shift_id']) ? (int)$r['shift_id'] : null;
                $result['jam_masuk'] = $r['uj_jam_masuk'] ?: $r['sm_jam_masuk'] ?: $result['jam_masuk'];
                $result['jam_pulang'] = $r['uj_jam_pulang'] ?: $r['sm_jam_pulang'] ?: $result['jam_pulang'];
                $result['toleransi_menit'] = (int)($r['toleransi_menit'] ?? $result['toleransi_menit']);
                $result['durasi_menit'] = (int)($r['durasi_menit'] ?? $result['durasi_menit']);
                $result['status'] = $r['uj_status'] ?? $result['status'];
                return $result;
            }
        }

        // 3) user_shift aktif
        $stmt2 = $conn->prepare("
            SELECT us.shift_id, sm.jam_masuk, sm.jam_pulang,
                   COALESCE(sm.toleransi_menit,10) AS toleransi_menit,
                   COALESCE(sm.durasi_menit,480) AS durasi_menit
            FROM user_shift us
            LEFT JOIN shift_master sm ON sm.id = us.shift_id
            WHERE us.user_id = ? AND us.aktif = 1 LIMIT 1
        ");
        if ($stmt2) {
            $stmt2->bind_param('i', $userId);
            $stmt2->execute();
            $r2 = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
            if ($r2) {
                $result['shift_id'] = !empty($r2['shift_id']) ? (int)$r2['shift_id'] : null;
                $result['jam_masuk'] = $r2['jam_masuk'] ?: $result['jam_masuk'];
                $result['jam_pulang'] = $r2['jam_pulang'] ?: $result['jam_pulang'];
                $result['toleransi_menit'] = (int)($r2['toleransi_menit'] ?? $result['toleransi_menit']);
                $result['durasi_menit'] = (int)($r2['durasi_menit'] ?? $result['durasi_menit']);
                return $result;
            }
        }

        return $result;
    }
}

if (!function_exists('build_in_params')) {
    function build_in_params(array $ints): array {
        $placeholders = implode(',', array_fill(0, count($ints), '?'));
        $types = str_repeat('i', count($ints));
        return [$placeholders, $types];
    }
}

// Reusable photo upload handler
if (!function_exists('handle_photo_upload')) {
    function handle_photo_upload(): ?string {
        if (empty($_FILES['foto']['tmp_name']) || !is_uploaded_file($_FILES['foto']['tmp_name'])) {
            return null;
        }
        $targetDir = defined('ROOT') ? (ROOT . '/uploads') : (__DIR__ . '/../uploads');
        if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);

        $ext = strtolower(pathinfo($_FILES['foto']['name'] ?? '', PATHINFO_EXTENSION));
        $safeExt = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) ? $ext : 'jpg';
        $uid = $_SESSION['user']['id'] ?? '0';
        $fname = 'foto_' . $uid . '_' . time() . '.' . $safeExt;

        if (@move_uploaded_file($_FILES['foto']['tmp_name'], $targetDir . '/' . $fname)) {
            return 'uploads/' . $fname;
        }
        return null;
    }
}

// Helper: insert todos to absensi_todo central (validates master_id)
function insert_absensi_todos(mysqli $conn, int $absensiId, array $todoItems) {
    if (empty($todoItems)) return;

    $seen = [];
    $stmt = $conn->prepare("INSERT INTO absensi_todo (absensi_id, sumber, master_id, sub_nama, manual_judul, manual_detail, jumlah) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Gagal menyiapkan insert todo: " . $conn->error);
    }

    foreach ($todoItems as $t) {
        $key = ($t['sumber'] ?? '') . '|' . (isset($t['master_id']) ? $t['master_id'] : '') . '|' . ($t['sub_nama'] ?? '') . '|' . ($t['manual_judul'] ?? '');
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $mid = null;
        if (isset($t['master_id']) && $t['master_id'] !== '' && $t['master_id'] !== null) {
            $mtemp = (int)$t['master_id'];
            if ($mtemp > 0) $mid = $mtemp;
        }

        if ($mid !== null) {
            $chk = $conn->prepare("SELECT 1 FROM tugas_master WHERE id = ? LIMIT 1");
            if (!$chk) {
                $stmt->close();
                throw new Exception("Gagal menyiapkan query validasi master: " . $conn->error);
            }
            $chk->bind_param('i', $mid);
            $chk->execute();
            $res = $chk->get_result();
            $chk->close();
            if (!$res || $res->num_rows === 0) {
                $stmt->close();
                jexit(false, "Tugas master tidak ditemukan (id: {$mid}). Silakan konfirmasi ke admin.");
            }
        }

        $sumber = $t['sumber'] ?? 'dropdown';
        $sub_nama = $t['sub_nama'] ?? null;
        $manual_judul = $t['manual_judul'] ?? null;
        $manual_detail = $t['manual_detail'] ?? null;
        $jumlah = max(1, (int)($t['jumlah'] ?? 1));

        $stmt->bind_param('isisssi', $absensiId, $sumber, $mid, $sub_nama, $manual_judul, $manual_detail, $jumlah);
        $stmt->execute();
    }

    $stmt->close();
}

// ---------------------------
// Init & validations
// ---------------------------
$conn = db();

$user_data = function_exists('current_user') ? current_user() : (($_SESSION['user'] ?? null) ?: null);
if (!$user_data || empty($user_data['id'])) {
    $redirect = function_exists('url') ? url('user/login.php') : null;
    jexit(false, "Sesi tidak valid. Silakan login ulang.", [], $redirect);
}

$userId = (int)$user_data['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jexit(false, "Metode tidak diizinkan.");
}
if (function_exists('csrf_ok_post') && !csrf_ok_post()) {
    jexit(false, "Token keamanan tidak valid.");
}

$action = trim((string)( $_POST['action'] ?? $_POST['aksi'] ?? '' ));
if (!in_array($action, ['masuk', 'pulang'], true)) {
    jexit(false, "Action tidak valid.");
}

date_default_timezone_set('Asia/Jakarta');
$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$waktu_now = $now->format('Y-m-d H:i:s');
$ymd = $now->format('Y-m-d');

$tgl_field = $ymd;
$created_at = $waktu_now;
$keterangan = trim((string)($_POST['keterangan'] ?? ''));

$ip_client = substr((string)(function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')), 0, 100);
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// Jadwal OFF quick check
$stmtJ = $conn->prepare("SELECT status FROM user_jadwal WHERE user_id = ? AND tanggal = ? LIMIT 1");
if ($stmtJ) {
    $stmtJ->bind_param('is', $userId, $ymd);
    $stmtJ->execute();
    $rj = $stmtJ->get_result()->fetch_assoc();
    $stmtJ->close();
    if ($rj && strtoupper((string)($rj['status'] ?? '')) === 'OFF') {
        jexit(false, "Anda tidak dapat absen hari ini — status jadwal: OFF.");
    }
}

// Editable recent pending (10 minutes)
$tenMinAgo = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->modify('-10 minutes')->format('Y-m-d H:i:s');
$editableId = 0;
$oldFoto = null;
$actionRaw = $_POST['action'] ?? '';

$qe = $conn->prepare("
    SELECT id, foto FROM absensi
    WHERE user_id = ? AND status = ? AND tanggal = ? AND approval_status = 'Pending' AND waktu >= ?
    ORDER BY waktu DESC LIMIT 1
");
if ($qe) {
    $qe->bind_param('isss', $userId, $actionRaw, $ymd, $tenMinAgo);
    $qe->execute();
    $rse = $qe->get_result();
    if ($rse && $rse->num_rows) {
        $re = $rse->fetch_assoc();
        $editableId = (int)$re['id'];
        $oldFoto = $re['foto'];
    }
    $qe->close();
}

$shiftInfo = getEffectiveShiftForUser($conn, $userId, $ymd);
$effectiveShiftId = $shiftInfo['shift_id'];
$jam_masuk_effective = $shiftInfo['jam_masuk'];
$toleransi_effective = (int)$shiftInfo['toleransi_menit'];
$durasi_effective = (int)($shiftInfo['durasi_menit'] ?? 0);

// Handle photo upload
$fotoStored = '';
$uploadedFoto = handle_photo_upload();
if ($uploadedFoto !== null) {
    $fotoStored = $uploadedFoto;
} else {
    $client_sent_foto_field = array_key_exists('foto', $_FILES) || isset($_POST['foto']) || isset($_FILES['foto']);
    $fotoStored = $oldFoto ?? '';
}

// Build compact todo legacy
$todo = '';
if (!empty($_POST['todo_master']) && is_array($_POST['todo_master'])) {
    $todoParts = [];
    foreach ($_POST['todo_master'] as $i => $tm) {
        $sub = $_POST['todo_sub'][$i] ?? '';
        if ($tm) $todoParts[] = $tm . ($sub ? " ({$sub})" : '');
    }
    $todo = implode('; ', $todoParts);
} else {
    $todo = trim((string)($_POST['todo'] ?? ''));
}

// Normalize location
$lat = trim((string)($_POST['lat'] ?? ''));
$lng = trim((string)($_POST['lng'] ?? ''));
$lokasi_text = trim((string)($_POST['lokasi_text'] ?? ''));

$client_sent_latlng = (isset($_POST['lat']) || isset($_POST['lng'])) && ($lat !== '' || $lng !== '');
$client_sent_foto_field = array_key_exists('foto', $_FILES) || isset($_POST['foto']) || isset($_FILES['foto']);

// ---------------------------
// ACTION: masuk
// ---------------------------
if ($action === 'masuk') {
    // Update editable pending record if exists
    if ($editableId > 0) {
        $telat_menit = 0;
        $is_telat = 0;
        if (!empty($jam_masuk_effective)) {
            $scheduled_ts = strtotime($ymd . ' ' . $jam_masuk_effective);
            $allowed_ts = $scheduled_ts + ($toleransi_effective * 60);
            $now_ts = $now->getTimestamp();
            $delta_sec = $now_ts - $allowed_ts;
            if ($delta_sec > 0) {
                $telat_menit = (int)ceil($delta_sec / 60.0);
                $is_telat = $telat_menit > 0 ? 1 : 0;
            }
        }

        $up = $conn->prepare("
            UPDATE absensi SET 
              waktu = ?, tgl = ?, shift_id = ?, telat_menit = ?, foto = ?, ip_client = ?, user_agent = ?, tanggal = ?, is_telat = ?, keterangan = ?, todo = ?, lat = ?, lng = ?, lokasi_text = ?, created_at = ?
            WHERE id = ? AND user_id = ?
        ");
        if (!$up) jexit(false, "Gagal menyiapkan query update: " . $conn->error);

        $bindShift = $effectiveShiftId === null ? 0 : (int)$effectiveShiftId;

        $ok = $up->bind_param(
            'ssiiissssisssssii',
            $waktu_now,
            $tgl_field,
            $bindShift,
            $telat_menit,
            $fotoStored,
            $ip_client,
            $user_agent,
            $ymd,
            $is_telat,
            $keterangan,
            $todo,
            $lat,
            $lng,
            $lokasi_text,
            $created_at,
            $editableId,
            $userId
        );
        if ($ok === false) jexit(false, "Gagal bind param update: " . $conn->error);

        try {
            $execOk = $up->execute();
        } catch (mysqli_sql_exception $e) {
            jexit(false, "Gagal memperbarui absen (DB): " . $e->getMessage());
        }
        $up->close();
        if (!$execOk) jexit(false, "Gagal memperbarui absen: " . $conn->error);

        jexit(true, "Absensi masuk diperbarui.", ['telat_menit' => $telat_menit, 'is_telat' => $is_telat], function_exists('url') ? url('user/absensi.php') : null);
    }

    // Duplicate check
    $q1 = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ? AND status = 'masuk' LIMIT 1");
    if ($q1) {
        $q1->bind_param('is', $userId, $ymd);
        $q1->execute();
        $r1 = $q1->get_result();
        $q1->close();
        if ($r1 && $r1->num_rows) {
            jexit(false, "Anda sudah absen masuk hari ini.", [], function_exists('url') ? url('user/absensi.php') : null);
        }
    }

    // Validation
    if ($client_sent_latlng && (empty($lat) || empty($lng))) {
        jexit(false, "Lokasi wajib diisi jika dikirim.");
    }
    if ($client_sent_foto_field && empty($fotoStored)) {
        jexit(false, "Foto kehadiran wajib diisi.");
    }

    // Late calculation
    $telat_menit = 0;
    $is_telat = 0;
    if (!empty($jam_masuk_effective)) {
        $scheduled_ts = strtotime($ymd . ' ' . $jam_masuk_effective);
        $allowed_ts = $scheduled_ts + ($toleransi_effective * 60);
        $now_ts = $now->getTimestamp();
        $delta_sec = $now_ts - $allowed_ts;
        if ($delta_sec > 0) {
            $telat_menit = (int)ceil($delta_sec / 60.0);
            $is_telat = $telat_menit > 0 ? 1 : 0;
        }
    }

    // Build canonical INSERT with optional fields
    $hasLatLng = $client_sent_latlng && $lat !== '' && $lng !== '';
    $hasShift = $effectiveShiftId !== null;

    if ($hasShift) {
        if ($hasLatLng) {
            $ins = $conn->prepare("
                INSERT INTO absensi
                  (user_id, waktu, tgl, status, shift_id, telat_menit, approval_status, foto, ip_client, user_agent, tanggal, is_telat, keterangan, todo, created_at, lat, lng, lokasi_text)
                VALUES (?, ?, ?, 'masuk', ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ins) jexit(false, "Gagal menyiapkan query insert: " . $conn->error);
            $ins->bind_param(
                'issiissssissssss',
                $userId,
                $waktu_now,
                $tgl_field,
                $effectiveShiftId,
                $telat_menit,
                $fotoStored,
                $ip_client,
                $user_agent,
                $ymd,
                $is_telat,
                $keterangan,
                $todo,
                $created_at,
                $lat,
                $lng,
                $lokasi_text
            );
        } else {
            $ins = $conn->prepare("
                INSERT INTO absensi
                  (user_id, waktu, tgl, status, shift_id, telat_menit, approval_status, foto, ip_client, user_agent, tanggal, is_telat, keterangan, todo, created_at)
                VALUES (?, ?, ?, 'masuk', ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ins) jexit(false, "Gagal menyiapkan query insert: " . $conn->error);
            $ins->bind_param(
                'issiissssisss',
                $userId,
                $waktu_now,
                $tgl_field,
                $effectiveShiftId,
                $telat_menit,
                $fotoStored,
                $ip_client,
                $user_agent,
                $ymd,
                $is_telat,
                $keterangan,
                $todo,
                $created_at
            );
        }
    } else {
        if ($hasLatLng) {
            $ins = $conn->prepare("
                INSERT INTO absensi
                  (user_id, waktu, tgl, status, telat_menit, approval_status, foto, ip_client, user_agent, tanggal, is_telat, keterangan, todo, created_at, lat, lng, lokasi_text)
                VALUES (?, ?, ?, 'masuk', ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ins) jexit(false, "Gagal menyiapkan query insert: " . $conn->error);
            $ins->bind_param(
                'ississssissssss',
                $userId,
                $waktu_now,
                $tgl_field,
                $telat_menit,
                $fotoStored,
                $ip_client,
                $user_agent,
                $ymd,
                $is_telat,
                $keterangan,
                $todo,
                $created_at,
                $lat,
                $lng,
                $lokasi_text
            );
        } else {
            $ins = $conn->prepare("
                INSERT INTO absensi
                  (user_id, waktu, tgl, status, telat_menit, approval_status, foto, ip_client, user_agent, tanggal, is_telat, keterangan, todo, created_at)
                VALUES (?, ?, ?, 'masuk', ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$ins) jexit(false, "Gagal menyiapkan query insert: " . $conn->error);
            $ins->bind_param(
                'ississssisss',
                $userId,
                $waktu_now,
                $tgl_field,
                $telat_menit,
                $fotoStored,
                $ip_client,
                $user_agent,
                $ymd,
                $is_telat,
                $keterangan,
                $todo,
                $created_at
            );
        }
    }

    try {
        $ok = $ins->execute();
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            jexit(false, "Anda sudah absen masuk hari ini (duplicate).");
        }
        jexit(false, "Gagal menyimpan absen (DB error): " . $e->getMessage());
    }
    $ins->close();
    if (!$ok) jexit(false, "Gagal menyimpan absen masuk: " . $conn->error);

    $absensiId = $conn->insert_id;

    // Normalize todo items from many possible input shapes
    $todoItems = [];

    if (!empty($_POST['todo_master']) && is_array($_POST['todo_master'])) {
        foreach ($_POST['todo_master'] as $i => $masterIdRaw) {
            $masterId = (int)$masterIdRaw;
            if ($masterId === 0) continue;
            $sub = trim((string)($_POST['todo_sub'][$i] ?? ''));
            $jumlah = max(1, (int)($_POST['todo_jumlah'][$i] ?? 1));
            $todoItems[] = [
                'sumber' => 'dropdown',
                'master_id' => $masterId,
                'sub_nama' => $sub,
                'manual_judul' => '',
                'manual_detail' => '',
                'jumlah' => $jumlah
            ];
        }
    }

    $todo_sumber = $_POST['todo_sumber'] ?? [];
    if (!empty($todo_sumber) && is_array($todo_sumber)) {
        foreach ($todo_sumber as $i => $sumber) {
            $jumlah = max(1, (int)($_POST['todo_jumlah'][$i] ?? 1));
            if ($sumber === 'manual') {
                $manual_judul = trim((string)($_POST['todo_manual_judul'][$i] ?? ''));
                $manual_detail = trim((string)($_POST['todo_manual_detail'][$i] ?? ''));
                if ($manual_judul === '') continue;
                $todoItems[] = [
                    'sumber' => 'manual',
                    'master_id' => null,
                    'sub_nama' => null,
                    'manual_judul' => $manual_judul,
                    'manual_detail' => $manual_detail,
                    'jumlah' => $jumlah
                ];
            } else {
                $mid = (int)($_POST['todo_master_id'][$i] ?? 0);
                if ($mid === 0) continue;
                $sub = trim((string)($_POST['todo_sub_nama'][$i] ?? ''));
                $todoItems[] = [
                    'sumber' => 'dropdown',
                    'master_id' => $mid,
                    'sub_nama' => $sub,
                    'manual_judul' => '',
                    'manual_detail' => '',
                    'jumlah' => $jumlah
                ];
            }
        }
    }

    if (!empty($_POST['todo_dropdown']) && is_array($_POST['todo_dropdown'])) {
        foreach ($_POST['todo_dropdown'] as $j => $jsonStr) {
            $obj = @json_decode($jsonStr, true);
            if (!is_array($obj)) continue;
            $masterId = (int) ($obj['master_id'] ?? $obj['id'] ?? 0);
            $subNama  = trim((string) ($obj['sub_nama'] ?? $obj['sub'] ?? ''));
            $jumlah   = max(1, (int) ($obj['jumlah'] ?? $obj['qty'] ?? 1));
            if ($masterId === 0) continue;
            $todoItems[] = [
                'sumber'        => 'dropdown',
                'master_id'     => $masterId,
                'sub_nama'      => $subNama,
                'manual_judul'  => '',
                'manual_detail' => '',
                'jumlah'        => $jumlah,
            ];
        }
    }

    if (!empty($_POST['todo_manual']) && is_array($_POST['todo_manual'])) {
        foreach ($_POST['todo_manual'] as $j => $jsonStr) {
            $obj = @json_decode($jsonStr, true);
            if (!is_array($obj)) continue;
            $judul  = trim((string) ($obj['judul'] ?? $obj['title'] ?? ''));
            if ($judul === '') continue;
            $detail = trim((string) ($obj['detail'] ?? ''));
            $jumlah = max(1, (int) ($obj['jumlah'] ?? $obj['qty'] ?? 1));
            $todoItems[] = [
                'sumber'        => 'manual',
                'master_id'     => null,
                'sub_nama'      => null,
                'manual_judul'  => $judul,
                'manual_detail' => $detail,
                'jumlah'        => $jumlah,
            ];
        }
    }

    // dedupe & insert
    insert_absensi_todos($conn, $absensiId, $todoItems);

    jexit(true, "Absensi masuk tersimpan.", ['absensi_id' => $absensiId], function_exists('url') ? url('user/absensi.php') : null);
}

// ---------------------------
// ACTION: pulang
// ---------------------------
if ($action === 'pulang') {
    // Prevent duplicate pulang
    $q2 = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ? AND status = 'pulang' LIMIT 1");
    if ($q2) {
        $q2->bind_param('is', $userId, $ymd);
        $q2->execute();
        $r2 = $q2->get_result();
        $q2->close();
        if ($r2 && $r2->num_rows) {
            jexit(false, "Anda sudah absen pulang hari ini.", [], function_exists('url') ? url('user/absensi.php') : null);
        }
    }

    // Get earliest "masuk" today
    $q3 = $conn->prepare("SELECT id, waktu, shift_id FROM absensi WHERE user_id = ? AND tanggal = ? AND status = 'masuk' ORDER BY waktu ASC LIMIT 1");
    if ($q3) {
        $q3->bind_param('is', $userId, $ymd);
        $q3->execute();
        $r3 = $q3->get_result();
        $q3->close();
        if (!$r3 || !$r3->num_rows) {
            jexit(false, "Belum ada absen masuk hari ini.");
        }
        $first = $r3->fetch_assoc();
        $masukId = (int)$first['id'];
        $masukWaktu = $first['waktu'];
        $masukShiftId = $first['shift_id'] ?? null;
    } else {
        jexit(false, "Gagal menyiapkan query absen masuk: " . $conn->error);
    }

    // Minimal duration check
    $canPulang = true;
    if (!empty($durasi_effective)) {
        $masuk_ts = strtotime($masukWaktu);
        $minDurationSec = ($durasi_effective) * 60;
        $now_ts = $now->getTimestamp();
        if (($now_ts - $masuk_ts) < $minDurationSec) {
            $canPulang = false;
        }
    }
    if (!$canPulang) {
        jexit(false, "Belum mencapai durasi kerja minimal untuk pulang.");
    }

    // For pulang: photo/location validation
    $uploadedFotoPulang = handle_photo_upload();
    if ($uploadedFotoPulang !== null) {
        $fotoStored = $uploadedFotoPulang;
    }
    if (isset($_FILES['foto']) && empty($fotoStored)) jexit(false, "Foto untuk absen pulang wajib diisi.");
    if ($lat === '' || $lng === '') jexit(false, "Lokasi wajib diisi.");

    // Parse done todos (checkboxes + hasil_todo JSON)
    $done = $_POST['todo_done'] ?? [];
    if (!is_array($done)) $done = [];

    if (!empty($_POST['hasil_todo']) && is_array($_POST['hasil_todo'])) {
        foreach ($_POST['hasil_todo'] as $hjson) {
            $h = json_decode($hjson, true);
            if (is_array($h) && !empty($h['id'])) {
                if (!empty($h['is_done']) || (isset($h['is_done']) && intval($h['is_done']) === 1)) {
                    $done[] = (int)$h['id'];
                }
            }
        }
    }

    $done = array_map('intval', $done);
    $done = array_values(array_filter(array_unique($done), function($v){ return $v>0; }));

    // p_todo processing (like masuk)
    $todoItems = [];
    if (!empty($_POST['p_todo_sumber']) && is_array($_POST['p_todo_sumber'])) {
        foreach ($_POST['p_todo_sumber'] as $i => $s) {
            $jumlah = max(1, (int)($_POST['p_todo_jumlah'][$i] ?? 1));
            if ($s === 'manual') {
                $judul = trim((string)($_POST['p_todo_manual_judul'][$i] ?? ''));
                $detail = trim((string)($_POST['p_todo_manual_detail'][$i] ?? ''));
                if ($judul === '') continue;
                $todoItems[] = ['sumber'=>'manual','master_id'=>null,'sub_nama'=>null,'manual_judul'=>$judul,'manual_detail'=>$detail,'jumlah'=>$jumlah];
            } else {
                $mid = (int)($_POST['p_todo_master_id'][$i] ?? 0);
                if ($mid === 0) continue;
                $sub = trim((string)($_POST['p_todo_sub_nama'][$i] ?? ''));
                $todoItems[] = ['sumber'=>'dropdown','master_id'=>$mid,'sub_nama'=>$sub,'manual_judul'=>'','manual_detail'=>'','jumlah'=>$jumlah];
            }
        }
    }

    insert_absensi_todos($conn, $masukId, $todoItems);

    // Transaction for pulang
    $conn->begin_transaction();
    try {
        $kendala = trim((string)($_POST['kendala_hari_ini'] ?? ''));

        $shiftToStore = $masukShiftId ?? $effectiveShiftId;

        // canonical insert for pulang
        if ($shiftToStore === null) {
            $stmt_pulang = $conn->prepare("
                INSERT INTO absensi (
                    user_id, waktu, tgl, status, approval_status,
                    ip_client, user_agent, tanggal, foto,
                    lat, lng, lokasi_text, kendala_hari_ini, created_at
                ) VALUES (?, ?, ?, 'pulang', 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt_pulang) throw new Exception("Gagal menyiapkan query pulang: " . $conn->error);
            $stmt_pulang->bind_param(
                'isssssssssss',
                $userId,
                $waktu_now,
                $tgl_field,
                $ip_client,
                $user_agent,
                $ymd,
                $fotoStored,
                $lat,
                $lng,
                $lokasi_text,
                $kendala,
                $created_at
            );
        } else {
            $stmt_pulang = $conn->prepare("
                INSERT INTO absensi (
                    user_id, waktu, tgl, status, shift_id, approval_status,
                    ip_client, user_agent, tanggal, foto,
                    lat, lng, lokasi_text, kendala_hari_ini, created_at
                ) VALUES (?, ?, ?, 'pulang', ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt_pulang) throw new Exception("Gagal menyiapkan query pulang: " . $conn->error);
            $stmt_pulang->bind_param(
                'ississsssssss',
                $userId,
                $waktu_now,
                $tgl_field,
                $shiftToStore,
                $ip_client,
                $user_agent,
                $ymd,
                $fotoStored,
                $lat,
                $lng,
                $lokasi_text,
                $kendala,
                $created_at
            );
        }

        $stmt_pulang->execute();
        $pulangAbsensiId = $conn->insert_id;
        $stmt_pulang->close();

        // parse extra_data[] -> absensi_detail
        if (!empty($_POST['extra_data']) && is_array($_POST['extra_data'])) {
            $stmt_extra = $conn->prepare("INSERT INTO absensi_detail (absensi_id, nama_tugas, sub_tugas, detail, jumlah, sumber) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_extra) {
                foreach ($_POST['extra_data'] as $edj) {
                    $ed = json_decode($edj, true);
                    if (!is_array($ed)) continue;
                    $nama = trim((string)($ed['nama_tugas'] ?? ($ed['nama_tugas'] ?? 'Tugas')));
                    $sub = isset($ed['sub_tugas']) ? trim((string)$ed['sub_tugas']) : null;
                    $detail = isset($ed['detail']) ? trim((string)$ed['detail']) : null;
                    $jumlah = max(1, (int)($ed['jumlah'] ?? 1));
                    $sumber = trim((string)($ed['sumber'] ?? 'manual'));
                    $stmt_extra->bind_param('isssis', $pulangAbsensiId, $nama, $sub, $detail, $jumlah, $sumber);
                    $stmt_extra->execute();
                }
                $stmt_extra->close();
            }
        }

        // Update done todos (done_ids) -> gunakan $done yang sudah dinormalisasi
        $done_ids = $done;
        if (!empty($done_ids) && is_array($done_ids)) {
            $done_ids = array_map('intval', $done_ids);
            $done_ids = array_filter($done_ids, function($v){ return $v > 0; });
            if (!empty($done_ids)) {
                list($placeholders, $types) = build_in_params($done_ids);
                $stmt_update_todo = $conn->prepare("UPDATE absensi_todo SET is_done = 1 WHERE id IN ($placeholders) AND absensi_id = ?");
                if (!$stmt_update_todo) throw new Exception("Gagal menyiapkan update to-do: " . $conn->error);
                $types_all = $types . 'i';
                $params = array_merge($done_ids, [$masukId]);
                $stmt_update_todo->bind_param($types_all, ...$params);
                $stmt_update_todo->execute();
                $stmt_update_todo->close();

                // Insert into absensi_detail for checked todos
                $stmt_get_done = $conn->prepare("SELECT t.id, tm.nama_tugas, t.sub_nama, t.manual_judul, t.manual_detail, t.jumlah, t.sumber FROM absensi_todo t LEFT JOIN tugas_master tm ON t.master_id = tm.id WHERE t.id = ?");
                if (!$stmt_get_done) throw new Exception("Gagal menyiapkan select todo: " . $conn->error);
                $stmt_detail = $conn->prepare("INSERT INTO absensi_detail (absensi_id, nama_tugas, sub_tugas, detail, jumlah, sumber) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt_detail) throw new Exception("Gagal menyiapkan insert detail: " . $conn->error);

                foreach ($done_ids as $did_val) {
                    $did = (int)$did_val;
                    $stmt_get_done->bind_param('i', $did);
                    $stmt_get_done->execute();
                    $todo_data = $stmt_get_done->get_result()->fetch_assoc();
                    if ($todo_data) {
                        $nama_tugas = $todo_data['sumber'] === 'manual' ? ($todo_data['manual_judul'] ?? 'Tugas') : ($todo_data['nama_tugas'] ?? 'Tugas');
                        $sub_tugas = $todo_data['sumber'] === 'dropdown' ? ($todo_data['sub_nama'] ?? null) : null;
                        $detail = $todo_data['sumber'] === 'manual' ? ($todo_data['manual_detail'] ?? null) : null;
                        $jumlah = (int)($todo_data['jumlah'] ?? 1);
                        $sumber = $todo_data['sumber'] ?? 'dropdown';
                        $stmt_detail->bind_param('isssis', $pulangAbsensiId, $nama_tugas, $sub_tugas, $detail, $jumlah, $sumber);
                        $stmt_detail->execute();
                    }
                }
                $stmt_get_done->close();
                $stmt_detail->close();
            }
        }

        // extra masters -> absensi_detail
        $extra_masters = $_POST['extra_master'] ?? [];
        if (!empty($extra_masters) && is_array($extra_masters)) {
            $stmt_detail2 = $conn->prepare("INSERT INTO absensi_detail (absensi_id, nama_tugas, sub_tugas, detail, jumlah, sumber) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt_detail2) throw new Exception("Gagal menyiapkan insert detail extra: " . $conn->error);

            foreach ($extra_masters as $i => $master_id) {
                if (empty($master_id)) continue;
                $mid = (int)$master_id;
                $stmt_get_master = $conn->prepare("SELECT nama_tugas FROM tugas_master WHERE id = ? LIMIT 1");
                if (!$stmt_get_master) throw new Exception("Gagal menyiapkan query master: " . $conn->error);
                $stmt_get_master->bind_param('i', $mid);
                $stmt_get_master->execute();
                $master_row = $stmt_get_master->get_result()->fetch_assoc();
                $master_nama = $master_row['nama_tugas'] ?? 'Tugas';
                $stmt_get_master->close();

                $sub_tugas_extra = trim((string)($_POST['extra_sub'][$i] ?? ''));
                $manual_judul_extra = trim((string)($_POST['extra_manual_judul'][$i] ?? ''));
                $jumlah_extra = (int)($_POST['extra_jumlah'][$i] ?? 1);
                if ($jumlah_extra <= 0) $jumlah_extra = 1;
                $sumber_extra = 'dropdown';

                $stmt_detail2->bind_param('isssis', $pulangAbsensiId, $master_nama, $sub_tugas_extra, $manual_judul_extra, $jumlah_extra, $sumber_extra);
                $stmt_detail2->execute();
            }
            $stmt_detail2->close();
        }

        $conn->commit();
        jexit(true, "Absen pulang berhasil direkam!", [], function_exists('url') ? url('user/absensi.php') : null);
    } catch (Exception $e) {
        $conn->rollback();
        if (!empty($uploadedFotoPulang) && file_exists((defined('ROOT') ? ROOT . '/' : __DIR__ . '/../') . $uploadedFotoPulang)) {
            @unlink((defined('ROOT') ? ROOT . '/' : __DIR__ . '/../') . $uploadedFotoPulang);
        }
        jexit(false, "Terjadi kesalahan database saat absen pulang: " . $e->getMessage());
    }
}

jexit(false, "Action tidak diketahui.");
