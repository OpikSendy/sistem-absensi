@extends('layouts.app')

@section('title', 'Dashboard | Kesatriyan')
@section('page-title', 'Dashboard Saya')

@section('content')
<div class="mb-4">
  <h4 class="fw-bold text-dark mb-1">Halo, {{ auth()->user()->nama ?: auth()->user()->username }}! 👋</h4>
  <p class="text-muted mb-0 small">{{ now()->translatedFormat('l, d F Y') }}</p>
</div>

<div class="row g-3 mb-4">
  {{-- Total Kehadiran --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
          <i class="bi bi-calendar-check fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $totalKehadiran }}</div>
          <div class="text-muted small">Total Kehadiran</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Absen Masuk Hari Ini --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
          <i class="bi bi-door-open fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $absenMasukHariIni ? Carbon\Carbon::parse($absenMasukHariIni->waktu)->format('H:i') : '—' }}</div>
          <div class="text-muted small">Absen Masuk</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Absen Pulang Hari Ini --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info">
          <i class="bi bi-box-arrow-right fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $absenPulangHariIni ? Carbon\Carbon::parse($absenPulangHariIni->waktu)->format('H:i') : '—' }}</div>
          <div class="text-muted small">Absen Pulang</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Terlambat Bulan Ini --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning">
          <i class="bi bi-exclamation-triangle fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $terlambatBulanIni }} x</div>
          <div class="text-muted small">Terlambat Bulan Ini</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-12 col-md-8">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-0 pt-4 pb-0">
        <h6 class="fw-bold mb-0">Aktifitas Kehadiran Terakhir</h6>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Tipe</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @php
                $recentAbsensi = App\Models\Absensi::where('user_id', auth()->id())
                  ->orderBy('waktu', 'desc')
                  ->take(5)
                  ->get();
              @endphp
              
              @forelse($recentAbsensi as $abs)
                <tr>
                  <td>{{ Carbon\Carbon::parse($abs->tanggal)->translatedFormat('d M Y') }}</td>
                  <td>{{ Carbon\Carbon::parse($abs->waktu)->format('H:i') }}</td>
                  <td>
                    @if($abs->status === 'masuk')
                      <span class="badge bg-primary text-white">Masuk</span>
                    @else
                      <span class="badge bg-info text-dark">Pulang</span>
                    @endif
                  </td>
                  <td>
                    @if($abs->approval_status === 'Disetujui')
                      <span class="text-success small fw-semibold"><i class="bi bi-check-circle me-1"></i>Disetujui</span>
                    @elseif($abs->approval_status === 'Ditolak')
                      <span class="text-danger small fw-semibold"><i class="bi bi-x-circle me-1"></i>Ditolak</span>
                    @else
                      <span class="text-warning small fw-semibold"><i class="bi bi-clock me-1"></i>Pending</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Belum ada data kehadiran.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 h-100 bg-primary text-white">
      <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
        <div class="mb-3">
          <i class="bi bi-calendar-check-fill opacity-50" style="font-size: 4rem;"></i>
        </div>
        <h5 class="fw-bold mb-1">Sudah Absen Hari Ini?</h5>
        <p class="small opacity-75 mb-4">Jangan lupa untuk mencatat kehadiran Anda tepat waktu.</p>
        <a href="{{ route('user.absensi') }}" class="btn btn-light text-primary fw-bold px-4 py-2 w-100 rounded-pill shadow-sm">
          Pergi ke Form Absensi
        </a>
      </div>
    </div>
  </div>
</div>

@endsection
