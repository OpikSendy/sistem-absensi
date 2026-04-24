{{-- resources/views/components/sidebar.blade.php --}}
{{-- Sidebar links berubah berdasarkan role user --}}
@php $user = auth()->user(); @endphp

@if($user && $user->isAdmin())
  {{-- ── ADMIN SIDEBAR ── --}}
  <div class="sidebar-section">Menu Utama</div>
  <nav class="sidebar-nav">
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
      <i class="bi bi-people"></i> Kelola User
    </a>
    <a href="{{ route('admin.shifts') }}" class="{{ request()->routeIs('admin.shifts*') ? 'active' : '' }}">
      <i class="bi bi-clock"></i> Kelola Shift
    </a>
    <a href="{{ route('admin.tugas') }}" class="{{ request()->routeIs('admin.tugas*') ? 'active' : '' }}">
      <i class="bi bi-list-task"></i> Kelola Tugas
    </a>
  </nav>

  <div class="sidebar-section">Laporan</div>
  <nav class="sidebar-nav">
    <a href="{{ route('admin.export.excel') }}">
      <i class="bi bi-file-earmark-excel"></i> Export Excel
    </a>
    <a href="{{ route('admin.export.pdf') }}">
      <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </a>
  </nav>

@else
  {{-- ── USER SIDEBAR ── --}}
  <div class="sidebar-section">Menu</div>
  <nav class="sidebar-nav">
    <a href="{{ route('user.dashboard') }}" class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
      <i class="bi bi-house"></i> Dashboard
    </a>
    <a href="{{ route('user.absensi') }}" class="{{ request()->routeIs('user.absensi') ? 'active' : '' }}">
      <i class="bi bi-calendar-check"></i> Absensi
    </a>
    <a href="{{ route('user.profile') }}" class="{{ request()->routeIs('user.profile') ? 'active' : '' }}">
      <i class="bi bi-person-circle"></i> Profil Saya
    </a>
  </nav>
@endif

{{-- ── BOTTOM: Logout ── --}}
<div class="mt-auto p-3 border-top" style="border-color: #1e293b !important;">
  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-sm w-100 text-start d-flex align-items-center gap-2"
            style="background:none; border:1px solid #334155; color:#94a3b8; border-radius:8px; padding:.5rem .75rem;">
      <i class="bi bi-box-arrow-right"></i>
      <span style="font-size:.8rem;">Keluar</span>
    </button>
  </form>
</div>
