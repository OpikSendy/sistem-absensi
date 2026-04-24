<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Login | Kesatriyan System</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary: #2563eb;
      --primary-dark: #1d4ed8;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    /* Background decoration */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: radial-gradient(circle at 20% 50%, rgba(37,99,235,.15) 0%, transparent 50%),
                  radial-gradient(circle at 80% 20%, rgba(99,102,241,.1) 0%, transparent 40%);
      pointer-events: none;
    }

    .login-wrapper {
      width: 100%;
      max-width: 420px;
      position: relative;
      z-index: 1;
    }

    .login-card {
      background: rgba(255,255,255,0.98);
      border-radius: 20px;
      box-shadow: 0 25px 50px rgba(0,0,0,.35);
      border: 1px solid rgba(255,255,255,.2);
      overflow: hidden;
    }

    .login-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      padding: 2rem 2rem 1.5rem;
      text-align: center;
      position: relative;
    }
    .login-header::after {
      content: '';
      position: absolute;
      bottom: -1px; left: 0; right: 0;
      height: 20px;
      background: rgba(255,255,255,0.98);
      border-radius: 20px 20px 0 0;
    }

    .brand-icon {
      width: 64px; height: 64px;
      background: rgba(255,255,255,.15);
      border: 2px solid rgba(255,255,255,.3);
      border-radius: 16px;
      display: inline-flex;
      align-items: center; justify-content: center;
      font-size: 1.75rem;
      color: #fff;
      margin-bottom: 1rem;
      backdrop-filter: blur(10px);
    }
    .login-header h4 { color: #fff; font-weight: 700; margin: 0; font-size: 1.25rem; }
    .login-header p  { color: rgba(255,255,255,.75); font-size: .8rem; margin: .25rem 0 0; }

    .login-body { padding: 1.75rem 2rem 2rem; }

    .form-label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .4rem; }
    .input-group-text {
      background: #f8fafc;
      border-color: #e2e8f0;
      color: #94a3b8;
      border-right: none;
    }
    .form-control {
      border-color: #e2e8f0;
      border-left: none;
      padding: .65rem .875rem;
      font-size: .875rem;
    }
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    .form-control:focus + * { border-color: var(--primary); }
    .input-group:focus-within .input-group-text {
      border-color: var(--primary);
    }

    .is-invalid ~ .input-group-text,
    .input-group .form-control.is-invalid { border-color: #ef4444 !important; }

    .btn-login {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      border: none;
      padding: .8rem;
      font-weight: 600;
      font-size: .9rem;
      border-radius: 10px;
      transition: all .2s;
      box-shadow: 0 4px 12px rgba(37,99,235,.35);
    }
    .btn-login:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(37,99,235,.45);
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
    }
    .btn-login:active { transform: translateY(0); }

    .login-footer {
      text-align: center;
      padding: 1rem 2rem;
      border-top: 1px solid #f1f5f9;
      background: #fafafa;
    }
    .login-footer small { color: #94a3b8; font-size: .72rem; }

    .alert-login {
      border-radius: 10px;
      border: none;
      font-size: .825rem;
      padding: .65rem .875rem;
    }
  </style>
</head>
<body>

<div class="login-wrapper">
  <div class="login-card">

    {{-- Header --}}
    <div class="login-header">
      <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
      <h4>Kesatriyan</h4>
      <p>Sistem Manajemen Absensi</p>
    </div>

    {{-- Body --}}
    <div class="login-body">

      {{-- Success flash (misal setelah logout) --}}
      @if(session('success'))
        <div class="alert alert-success alert-login d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-check-circle-fill flex-shrink-0"></i>
          <div>{{ session('success') }}</div>
        </div>
      @endif

      {{-- Error summary --}}
      @if($errors->any())
        <div class="alert alert-danger alert-login d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
          <div>{{ $errors->first() }}</div>
        </div>
      @endif

      <form method="POST" action="{{ route('login.post') }}" novalidate id="loginForm">
        @csrf

        {{-- Username --}}
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input
              type="text"
              id="username"
              name="username"
              class="form-control @error('username') is-invalid @enderror"
              value="{{ old('username') }}"
              placeholder="Masukkan username"
              required
              autofocus
              autocomplete="username"
            >
          </div>
        </div>

        {{-- Password --}}
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control @error('password') is-invalid @enderror"
              placeholder="Masukkan kata sandi"
              required
              autocomplete="current-password"
            >
            <button type="button" class="input-group-text border-start-0" id="btnTogglePw"
                    style="cursor:pointer;" title="Tampilkan/sembunyikan password">
              <i class="bi bi-eye" id="iconPw"></i>
            </button>
          </div>
        </div>

        {{-- Submit --}}
        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-login text-white" id="btnSubmit">
            <span id="btnText"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk Aplikasi</span>
            <span id="btnLoading" class="d-none">
              <span class="spinner-border spinner-border-sm me-1"></span>Memproses...
            </span>
          </button>
        </div>

      </form>
    </div>

    {{-- Footer --}}
    <div class="login-footer">
      <small>&copy; {{ date('Y') }} Kesatriyan System &mdash; Evolution IT</small>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle password visibility
  document.getElementById('btnTogglePw').addEventListener('click', function() {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('iconPw');
    if (pw.type === 'password') {
      pw.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      pw.type = 'password';
      icon.className = 'bi bi-eye';
    }
  });

  // Loading state on submit
  document.getElementById('loginForm').addEventListener('submit', function() {
    document.getElementById('btnText').classList.add('d-none');
    document.getElementById('btnLoading').classList.remove('d-none');
    document.getElementById('btnSubmit').disabled = true;
  });
</script>
</body>
</html>
