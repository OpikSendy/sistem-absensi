<?php
// admin/form_aksi.php (digabung dan dioptimalkan tanpa mengubah fungsionalitas)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/bootstrap.php';

/*
 * Akses:
 * - Jika belum login -> arahkan ke halaman login
 * - Jika sudah login tetapi bukan admin -> arahkan ke halaman 403
 */
if (!is_logged_in()) {
    redirect(url('user/login.php'));
}
if (!is_admin()) {
    redirect(url('user/403.php'));
}

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$id   = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

/* Helper kecil internal: sanitasi role */
$allowed_roles = ['user', 'admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF untuk semua POST; respons JSON bila AJAX, flash+redirect bila normal
    if (!csrf_ok_post()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Token keamanan tidak valid.']);
            exit;
        } else {
            $_SESSION['flash_err'] = 'Token keamanan tidak valid. Mohon coba lagi.';
            redirect(url('admin/form_aksi.php'));
        }
    }

    // --- Tambah User Baru ---
    if ($aksi === 'add_user') {
        $username         = trim($_POST['username'] ?? '');
        $password         = trim($_POST['password'] ?? '');
        $nama             = trim($_POST['nama'] ?? '');
        $devisi           = trim($_POST['devisi'] ?? '');
        $role_new         = trim($_POST['role_new'] ?? 'user');

        // field tambahan
        $nim              = trim($_POST['nim'] ?? '');
        $jurusan          = trim($_POST['jurusan'] ?? '');
        $asal_sekolah     = trim($_POST['asal_sekolah'] ?? '');
        $tanggal_lahir    = trim($_POST['tanggal_lahir'] ?? '');
        $no_hp            = trim($_POST['no_hp'] ?? '');
        $no_hp_orangtua   = trim($_POST['no_hp_orangtua'] ?? '');
        $aktif            = 1;

        // validasi wajib
        if ($username === '' || $password === '' || $nama === '') {
            $_SESSION['flash_err'] = 'Username, Nama, dan Password harus diisi.';
            redirect(url('admin/form_aksi.php'));
        }

        // validasi role
        if (!in_array($role_new, $allowed_roles, true)) {
            $role_new = 'user';
        }

        // validasi tanggal sederhana: kosong atau format YYYY-MM-DD
        if ($tanggal_lahir !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);
            if (!($d && $d->format('Y-m-d') === $tanggal_lahir)) {
                $_SESSION['flash_err'] = 'Format Tanggal Lahir harus YYYY-MM-DD.';
                redirect(url('admin/form_aksi.php'));
            }
        }

        // hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // cek duplicate username
        $stmt_check = db()->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt_check) {
            $_SESSION['flash_err'] = 'Gagal menyiapkan pengecekan username: ' . db()->error;
            redirect(url('admin/form_aksi.php'));
        }
        $stmt_check->bind_param('s', $username);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $_SESSION['flash_err'] = 'Username sudah digunakan.';
            $stmt_check->close();
            redirect(url('admin/form_aksi.php'));
        }
        $stmt_check->close();

        // insert (NULLIF untuk tanggal_lahir agar '' disimpan sebagai NULL)
        $stmt_insert = db()->prepare(
            "INSERT INTO users (username, password, nama, devisi, role, aktif, nim, jurusan, asal_sekolah, tanggal_lahir, no_hp, no_hp_orangtua)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?)"
        );
        if (!$stmt_insert) {
            $_SESSION['flash_err'] = 'Gagal menyiapkan query: ' . db()->error;
            redirect(url('admin/form_aksi.php'));
        }

        // bind types:
        // username(s), password(s), nama(s), devisi(s), role(s), aktif(i),
        // nim(s), jurusan(s), asal_sekolah(s), tanggal_lahir(s), no_hp(s), no_hp_orangtua(s)
        $bindTypes = 'sssssissssss';
        $stmt_insert->bind_param(
            $bindTypes,
            $username,
            $hashed_password,
            $nama,
            $devisi,
            $role_new,
            $aktif,
            $nim,
            $jurusan,
            $asal_sekolah,
            $tanggal_lahir,
            $no_hp,
            $no_hp_orangtua
        );

        if ($stmt_insert->execute()) {
            $_SESSION['flash_ok'] = 'User baru berhasil ditambahkan.';
        } else {
            $_SESSION['flash_err'] = 'Gagal menambahkan user baru: ' . $stmt_insert->error;
        }
        $stmt_insert->close();
        redirect(url('admin/form_aksi.php'));
    }

    // aksi POST lain dapat ditambahkan di sini tanpa memecah file
}

// VIEW (aksi kosong)
$page_title    = "Kelola Aksi";
$active_menu   = 'aksi';
$load_chart_js = false;

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-column flex-grow-1 min-vh-100">
  <main class="container-fluid py-3 flex-grow-1">
    <?php if (isset($_SESSION['flash_ok'])): ?>
      <div class="alert alert-success py-2"><?= $_SESSION['flash_ok']; unset($_SESSION['flash_ok']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_err'])): ?>
      <div class="alert alert-danger py-2"><?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
    <?php endif; ?>

    <div class="row g-3 justify-content-center">
      <div class="col-lg-6">
        <div class="card p-3">
          <h6 class="mb-3">Tambah User Baru</h6>
          <form method="post" action="<?= url('admin/form_aksi.php') ?>" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="add_user">

            <div class="col-12">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label">Nama Lengkap</label>
              <input type="text" name="nama" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>

            <div class="col-12">
              <label class="form-label">Devisi (opsional)</label>
              <input type="text" name="devisi" class="form-control">
            </div>

            <div class="col-12">
              <label class="form-label">Role</label>
              <select name="role_new" class="form-select" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <!-- optional extended fields -->
            <div class="col-12">
              <label class="form-label">NIM (opsional)</label>
              <input type="text" name="nim" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Jurusan (opsional)</label>
              <input type="text" name="jurusan" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Asal Sekolah (opsional)</label>
              <input type="text" name="asal_sekolah" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Tanggal Lahir (YYYY-MM-DD)</label>
              <input type="date" name="tanggal_lahir" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">No HP (opsional)</label>
              <input type="text" name="no_hp" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">No HP Orang Tua (opsional)</label>
              <input type="text" name="no_hp_orangtua" class="form-control">
            </div>

            <div class="col-12 text-end">
              <button class="btn btn-primary">Tambah User</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
</div>

<script>
// No inline JS untuk approve/deny; handlers ditempatkan di file terpisah bila diperlukan
</script>
