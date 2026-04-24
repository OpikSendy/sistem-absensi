<?php
require_once __DIR__ . '/../includes/bootstrap.php'; 

if (is_logged_in()) {
    redirect(url(is_admin() ? 'admin/dashboard.php' : 'user/dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        $stmt = db()->prepare("SELECT id, username, password, role, nama, aktif, devisi FROM users WHERE username=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $u = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if ($u && (int)$u['aktif'] === 1) {
                $ok = false;
                if (strlen($u['password']) >= 60 && str_starts_with($u['password'], '$2y$')) {
                    $ok = password_verify($password, $u['password']);
                } else {
                    $ok = hash_equals($u['password'], $password);
                }

                if ($ok) {
                    $_SESSION['user'] = [
                        'id' => (int)$u['id'],
                        'username' => $u['username'],
                        'nama' => $u['nama'],
                        'role' => $u['role'],
                        'devisi' => $u['devisi']
                    ];
                    redirect(url($u['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
                } else {
                    $error = 'Kata sandi tidak valid.';
                }
            } else {
                $error = 'Akun tidak ditemukan atau non-aktif.';
            }
        } else {
            $error = 'Kesalahan sistem.';
        }
    } else {
        $error = 'Silakan lengkapi form.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | Kesatriyan System</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  
  <style>
    body {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background-color: var(--bg-body);
    }
    .login-card {
        width: 100%;
        max-width: 400px;
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        background: #fff;
    }
    .brand-icon {
        width: 56px; height: 56px;
        background: var(--primary);
        color: #fff;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1.25rem auto;
    }
    .form-control {
        padding: 0.8rem 1rem;
        font-size: 0.95rem;
    }
    .input-group-text {
        background: #f8fafc;
        border-color: var(--border-color);
        color: var(--text-muted);
    }
  </style>
</head>
<body>

  <div class="container py-3">
    <div class="card login-card mx-auto p-4 p-md-5">
      <div class="card-body p-0">
        
        <div class="text-center mb-4">
            <div class="brand-icon shadow-sm">
                <i class="bi bi-shield-check"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">Kesatriyan</h4>
            <p class="text-muted small">Sistem Manejemen</p>
        </div>

        <?php if($error): ?>
          <div class="alert alert-danger d-flex align-items-center small p-2 mb-4 border-0 bg-danger-subtle text-danger rounded-3" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2 fs-6"></i>
            <div><?= e($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="post" action="" novalidate>
          
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="Masukan ID / Username" required autofocus autocomplete="username">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-bold text-muted">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Masukan kata sandi" required autocomplete="current-password">
            </div>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-lg fs-6 fw-bold py-3">
                Masuk Aplikasi
            </button>
          </div>

        </form>
      </div>
      
      <div class="text-center mt-4 pt-3 border-top">
        <small class="text-muted" style="font-size: 0.75rem;">
            &copy; <?= date('Y') ?> Kesatriyan System.
        </small>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>