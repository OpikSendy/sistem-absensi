@extends('layouts.app')

@section('title', 'Dashboard | Kesatriyan')
@section('page-title', 'Dashboard Saya')

@section('content')
  <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h4 class="fw-bold text-dark mb-1">Halo, {{ auth()->user()->nama ?: auth()->user()->username }}! 👋</h4>
      <p class="text-muted mb-0 small">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>
    <div>
      @if(isset($activeShift) && $activeShift->shift)
        <div
          class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded-pill shadow-sm border border-primary border-opacity-10">
          <i class="bi bi-clock-history text-primary"></i>
          <div class="small">
            <span class="text-muted me-1">Shift Aktif:</span>
            <strong class="text-primary">{{ $activeShift->shift->nama_shift }}</strong>
            <span class="text-muted ms-1"
              style="font-size: 0.75rem;">({{ \Carbon\Carbon::parse($activeShift->shift->jam_masuk)->format('H:i') }})</span>
          </div>
        </div>
      @else
        <div
          class="d-flex align-items-center gap-2 bg-white px-3 py-2 rounded-pill shadow-sm border border-secondary border-opacity-10">
          <i class="bi bi-info-circle text-secondary"></i>
          <div class="small">
            <span class="text-muted me-1">Shift Aktif:</span>
            <strong class="text-secondary">Default (Bebas)</strong>
          </div>
        </div>
      @endif
    </div>
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
            <div class="fw-bold fs-5">
              {{ $absenMasukHariIni ? Carbon\Carbon::parse($absenMasukHariIni->waktu)->format('H:i') : '—' }}
            </div>
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
            <div class="fw-bold fs-5">
              {{ $absenPulangHariIni ? Carbon\Carbon::parse($absenPulangHariIni->waktu)->format('H:i') : '—' }}
            </div>
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
          <a href="{{ route('user.absensi') }}"
            class="btn btn-light text-primary fw-bold px-4 py-2 w-100 rounded-pill shadow-sm">
            Pergi ke Form Absensi
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- Analitik Kehadiran Saya --}}
  <div class="row g-3">
    <div class="col-12 col-xl-4">
      <div class="card border-0 h-100">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
          <h6 class="fw-bold mb-0">Top 5 Karyawan Tepat Waktu</h6>
          <p class="text-muted small mb-0">Minggu Ini</p>
        </div>
        <div class="card-body">
          <canvas id="rankingChart" style="max-height: 250px;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-8">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
          <h6 class="fw-bold mb-0">Tren Kedisiplinan Saya</h6>
          <p class="text-muted small mb-0">Bulan Ini</p>
        </div>
        <div class="card-body">
          <canvas id="myDisciplineChart" style="max-height: 250px;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-4">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-center">
          <h6 class="fw-bold mb-0">Distribusi Kehadiran</h6>
          <p class="text-muted small mb-0">Bulan Ini</p>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <canvas id="myDistributionChart" style="max-height: 220px;"></canvas>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('scripts')
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // 1. Fetch Ranking Chart (Top 5)
      fetch('{{ route("user.analytics.ranking") }}')
        .then(res => res.json())
        .then(data => {
          const ctx = document.getElementById('rankingChart').getContext('2d');
          new Chart(ctx, {
            type: 'bar',
            data: {
              labels: data.labels,
              datasets: [{
                label: 'Tepat Waktu',
                data: data.data,
                backgroundColor: '#3b82f6',
                borderRadius: 5
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { display: false } },
              scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
              }
            }
          });
        })
        .catch(err => console.error('Error loading ranking chart:', err));

      // 2. Fetch Discipline Chart (Trend)
      fetch('{{ route("user.analytics.my_discipline") }}')
        .then(res => res.json())
        .then(data => {
          const ctx = document.getElementById('myDisciplineChart').getContext('2d');
          new Chart(ctx, {
            type: 'line',
            data: {
              labels: data.labels,
              datasets: [
                {
                  label: 'Tepat Waktu',
                  data: data.onTime,
                  borderColor: '#10b981',
                  backgroundColor: 'rgba(16, 185, 129, 0.1)',
                  fill: true,
                  tension: 0.4
                },
                {
                  label: 'Terlambat',
                  data: data.late,
                  borderColor: '#ef4444',
                  backgroundColor: 'rgba(239, 68, 68, 0.1)',
                  fill: true,
                  tension: 0.4
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
          });
        });

      // 3. Fetch Distribution Chart (Pie)
      fetch('{{ route("user.analytics.my_distribution") }}')
        .then(res => res.json())
        .then(data => {
          const ctx = document.getElementById('myDistributionChart').getContext('2d');
          new Chart(ctx, {
            type: 'pie',
            data: {
              labels: data.labels,
              datasets: [{
                data: data.data,
                backgroundColor: ['#10b981', '#ef4444', '#6b7280']
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { position: 'bottom' } }
            }
          });
        });
    });
  </script>
@endsection