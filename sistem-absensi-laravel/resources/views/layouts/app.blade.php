<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Kesatriyan System')</title>

  {{-- Bootstrap 5 + Bootstrap Icons --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  {{-- Google Fonts --}}
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --primary:       #2563eb;
      --primary-dark:  #1d4ed8;
      --sidebar-bg:    #0f172a;
      --sidebar-text:  #94a3b8;
      --sidebar-hover: #1e293b;
      --sidebar-w:     250px;
      --topbar-h:      60px;
      --bg-body:       #f1f5f9;
      --border-color:  #e2e8f0;
      --text-muted:    #64748b;
    }

    * { box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      font-size: 0.875rem;
      background: var(--bg-body);
      margin: 0;
    }

    /* ── Sidebar ── */
    #sidebar {
      position: fixed;
      top: 0; left: 0;
      width: var(--sidebar-w);
      height: 100vh;
      background: var(--sidebar-bg);
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: transform 0.3s ease;
      overflow-y: auto;
    }
    #sidebar.collapsed { transform: translateX(calc(-1 * var(--sidebar-w))); }
    .sidebar-brand {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #1e293b;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .sidebar-brand .brand-icon {
      width: 36px; height: 36px;
      background: var(--primary);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1rem; flex-shrink: 0;
    }
    .sidebar-brand .brand-name {
      font-size: 0.9rem;
      font-weight: 700;
      color: #f1f5f9;
      letter-spacing: 0.02em;
    }
    .sidebar-brand .brand-sub {
      font-size: 0.7rem;
      color: var(--sidebar-text);
    }
    .sidebar-section {
      padding: 1rem 1rem 0.25rem;
      font-size: 0.65rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #475569;
    }
    .sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.625rem 1.25rem;
      color: var(--sidebar-text);
      text-decoration: none;
      font-size: 0.825rem;
      font-weight: 500;
      border-left: 3px solid transparent;
      transition: all 0.2s;
    }
    .sidebar-nav a:hover,
    .sidebar-nav a.active {
      background: var(--sidebar-hover);
      color: #f1f5f9;
      border-left-color: var(--primary);
    }
    .sidebar-nav a .bi { font-size: 1rem; }

    /* ── Topbar ── */
    #topbar {
      position: fixed;
      top: 0;
      left: var(--sidebar-w);
      right: 0;
      height: var(--topbar-h);
      background: #fff;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.5rem;
      z-index: 999;
      transition: left 0.3s ease;
    }
    #topbar.expanded { left: 0; }
    .topbar-left { display: flex; align-items: center; gap: 1rem; }
    .topbar-right { display: flex; align-items: center; gap: 0.75rem; }
    #btnToggleSidebar {
      background: none; border: none;
      font-size: 1.25rem; color: var(--text-muted);
      cursor: pointer; padding: 0.25rem;
    }
    .topbar-title { font-size: 0.9rem; font-weight: 600; color: #1e293b; }

    /* ── Main content ── */
    #main-content {
      margin-left: var(--sidebar-w);
      padding-top: var(--topbar-h);
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }
    #main-content.expanded { margin-left: 0; }
    .content-area { padding: 1.5rem; }

    /* ── Cards & badges ── */
    .card { border: none; border-radius: 12px; }
    .card.shadow-sm { box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04) !important; }

    .badge-soft-success { background: #dcfce7; color: #15803d; }
    .badge-soft-danger  { background: #fee2e2; color: #b91c1c; }
    .badge-soft-warning { background: #fef9c3; color: #a16207; }
    .badge-soft-primary { background: #dbeafe; color: #1d4ed8; }
    .badge-soft-secondary { background: #f1f5f9; color: #475569; }

    /* ── Avatar ── */
    .avatar {
      width: 36px; height: 36px;
      border-radius: 50%;
      object-fit: cover;
      background: #e2e8f0;
    }
    .avatar-initials {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: var(--primary);
      color: #fff;
      font-size: 0.8rem;
      font-weight: 700;
      display: flex; align-items: center; justify-content: center;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      #sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
      #sidebar.show { transform: translateX(0); }
      #topbar { left: 0 !important; }
      #main-content { margin-left: 0 !important; }
    }
  </style>

  @yield('styles')
</head>
<body>

{{-- ── SIDEBAR ──────────────────────────────────────────────────────────────── --}}
<nav id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
    <div>
      <div class="brand-name">Kesatriyan</div>
      <div class="brand-sub">Sistem Absensi</div>
    </div>
  </div>

  @include('components.sidebar')
</nav>

{{-- ── TOPBAR ───────────────────────────────────────────────────────────────── --}}
<header id="topbar">
  <div class="topbar-left">
    <button id="btnToggleSidebar" title="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>
    <span class="topbar-title d-none d-md-block">@yield('page-title', 'Dashboard')</span>
  </div>
  <div class="topbar-right">
    {{-- Notifikasi --}}
    <div class="dropdown">
      <button class="btn btn-sm btn-light position-relative border" id="btnNotif" data-bs-toggle="dropdown" aria-expanded="false" onclick="markNotificationsRead()">
        <i class="bi bi-bell"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notifBadge">
          0
        </span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="width: 320px; max-height: 400px; overflow-y: auto;" id="notifDropdown">
        <li class="p-3 text-center text-muted small" id="notifEmpty">Belum ada notifikasi.</li>
      </ul>
    </div>

    {{-- User dropdown --}}
    <div class="dropdown">
      <button class="btn btn-sm btn-light border d-flex align-items-center gap-2 px-2"
              data-bs-toggle="dropdown" id="userDropdown">
        @php $u = auth()->user(); @endphp
        @if($u->foto)
          <img src="{{ asset('storage/' . $u->foto) }}" class="avatar" alt="avatar">
        @else
          <div class="avatar-initials">{{ strtoupper(substr($u->nama ?: $u->username, 0, 2)) }}</div>
        @endif
        <span class="d-none d-md-block fw-semibold" style="font-size:.8rem;">
          {{ $u->nama ?: $u->username }}
        </span>
        <i class="bi bi-chevron-down small text-muted"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:200px;">
        <li>
          <div class="px-3 py-2 border-bottom">
            <div class="fw-bold text-dark small">{{ $u->nama ?: $u->username }}</div>
            <div class="text-muted" style="font-size:.75rem;">{{ ucfirst($u->role) }} · {{ $u->devisi ?? '-' }}</div>
          </div>
        </li>
        @if($u->isAdmin())
          <li><a class="dropdown-item small" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Admin Panel</a></li>
        @else
          <li><a class="dropdown-item small" href="{{ route('user.profile') }}"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
        @endif
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item small text-danger">
              <i class="bi bi-box-arrow-right me-2"></i>Keluar
            </button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</header>

{{-- ── MAIN CONTENT ─────────────────────────────────────────────────────────── --}}
<main id="main-content">
  <div class="content-area">

    {{-- Flash messages --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @yield('content')
  </div>
</main>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1055;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle sidebar
  const sidebar = document.getElementById('sidebar');
  const topbar  = document.getElementById('topbar');
  const main    = document.getElementById('main-content');
  const btn     = document.getElementById('btnToggleSidebar');

  btn.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
      sidebar.classList.toggle('show');
    } else {
      sidebar.classList.toggle('collapsed');
      topbar.classList.toggle('expanded');
      main.classList.toggle('expanded');
    }
  });

  // Auto-close sidebar on mobile when clicking outside
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
      if (!sidebar.contains(e.target) && e.target !== btn) {
        sidebar.classList.remove('show');
      }
    }
  });

  // Mark active sidebar link
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    if (link.href === window.location.href) {
      link.classList.add('active');
    }
  });

  // Notifications Logic
  const notifBadge = document.getElementById('notifBadge');
  const notifDropdown = document.getElementById('notifDropdown');
  let lastNotifIds = [];

  function fetchNotifications() {
    fetch("{{ route('notifications.unread') }}")
      .then(res => res.json())
      .then(data => {
        if (data.count > 0) {
          notifBadge.textContent = data.count;
          notifBadge.classList.remove('d-none');
          
          let html = '';
          let newNotifs = [];
          
          data.data.forEach(n => {
            if (!lastNotifIds.includes(n.id) && lastNotifIds.length > 0) {
              newNotifs.push(n);
            }
            
            html += `
              <li class="border-bottom">
                <a class="dropdown-item py-2 px-3 text-wrap" href="#">
                  <div class="fw-bold small mb-1">${n.title}</div>
                  <div class="text-muted" style="font-size: 0.75rem;">${n.message}</div>
                  <div class="text-muted mt-1" style="font-size: 0.65rem;">${new Date(n.created_at).toLocaleString('id-ID')}</div>
                </a>
              </li>
            `;
          });
          
          notifDropdown.innerHTML = html;
          
          // Save current IDs
          lastNotifIds = data.data.map(n => n.id);
          
          // Show toasts for new notifications
          newNotifs.forEach(n => showToast(n.title, n.message));
        } else {
          notifBadge.classList.add('d-none');
          notifDropdown.innerHTML = '<li class="p-3 text-center text-muted small" id="notifEmpty">Belum ada notifikasi.</li>';
          lastNotifIds = [];
        }
      })
      .catch(err => console.error("Error fetching notifications", err));
  }

  function markNotificationsRead() {
    if (notifBadge.classList.contains('d-none')) return;
    
    fetch("{{ route('notifications.read') }}", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        "Accept": "application/json"
      }
    }).then(res => res.json()).then(data => {
      if (data.ok) {
        notifBadge.classList.add('d-none');
        notifBadge.textContent = '0';
      }
    });
  }

  function showToast(title, message) {
    const toastHtml = `
      <div class="toast show border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header border-bottom-0 bg-primary text-white">
          <i class="bi bi-bell-fill me-2"></i>
          <strong class="me-auto">${title}</strong>
          <small>Baru saja</small>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-white text-dark" style="font-size: 0.85rem;">
          ${message}
        </div>
      </div>
    `;
    const container = document.getElementById('toastContainer');
    container.insertAdjacentHTML('beforeend', toastHtml);
    
    const toasts = container.querySelectorAll('.toast');
    const newToast = toasts[toasts.length - 1];
    
    setTimeout(() => {
      newToast.classList.remove('show');
      setTimeout(() => newToast.remove(), 300);
    }, 5000);
  }

  // Initial fetch
  fetchNotifications();
  // Poll every 15 seconds
  setInterval(fetchNotifications, 15000);
</script>

@yield('scripts')
</body>
</html>
