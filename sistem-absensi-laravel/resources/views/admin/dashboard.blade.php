@extends('layouts.app')

@section('title', 'Dashboard Admin | Kesatriyan')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h4 class="fw-bold text-dark mb-1">Selamat datang, {{ auth()->user()->nama ?: auth()->user()->username }}!</h4>
    <p class="text-muted mb-0 small">Overview kinerja dan manajemen absensi karyawan.</p>
  </div>
</div>

{{-- Placeholder stats cards --}}
<div class="row g-3 mb-4">
  {{-- Total Karyawan --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
          <i class="bi bi-people-fill fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $totalKaryawan }}</div>
          <div class="text-muted small">Total Karyawan Aktif</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Hadir Hari Ini --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
          <i class="bi bi-calendar-check fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $hadirHariIni }}</div>
          <div class="text-muted small">Hadir Hari Ini</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Terlambat --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning">
          <i class="bi bi-clock-history fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $terlambatHariIni }}</div>
          <div class="text-muted small">Terlambat Hari Ini</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Pending Approval --}}
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-danger bg-opacity-10 text-danger">
          <i class="bi bi-hourglass-split fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $pendingApproval }}</div>
          <div class="text-muted small">Pending Approval</div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Tabel Data Kehadiran --}}
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-bottom-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0">Log Kehadiran Terbaru</h6>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.export.excel') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i> Export Excel</a>
      <a href="{{ route('admin.export.pdf') }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF</a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-muted small">
          <tr>
            <th class="ps-4">Karyawan</th>
            <th>Waktu</th>
            <th>Status</th>
            <th>Telat</th>
            <th>Keterangan</th>
            <th>Approval</th>
            <th class="pe-4 text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentAbsensi as $abs)
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  @if($abs->user->foto)
                    <img src="{{ asset('storage/' . $abs->user->foto) }}" class="rounded-circle object-fit-cover" width="32" height="32">
                  @else
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px; height:32px; font-size:.8rem;">
                      {{ strtoupper(substr($abs->user->nama, 0, 2)) }}
                    </div>
                  @endif
                  <div>
                    <div class="fw-semibold text-dark" style="font-size:.85rem;">{{ $abs->user->nama }}</div>
                    <div class="text-muted" style="font-size:.7rem;">{{ $abs->user->devisi ?? '-' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="fw-semibold" style="font-size:.85rem;">{{ Carbon\Carbon::parse($abs->waktu)->format('d M Y') }}</div>
                <div class="text-muted" style="font-size:.75rem;">{{ Carbon\Carbon::parse($abs->waktu)->format('H:i') }}</div>
              </td>
              <td>
                @if($abs->status === 'masuk')
                  <span class="badge bg-primary text-white">Masuk</span>
                @else
                  <span class="badge bg-info text-dark">Pulang</span>
                @endif
              </td>
              <td>
                @if($abs->is_telat)
                  <span class="text-danger fw-semibold small">{{ $abs->telat_menit }} mnt</span>
                @else
                  <span class="text-success fw-semibold small">-</span>
                @endif
              </td>
              <td>
                <span class="d-inline-block text-truncate" style="max-width: 150px; font-size:.8rem;" title="{{ $abs->keterangan ?? $abs->kendala_hari_ini ?? '-' }}">
                  {{ $abs->keterangan ?? $abs->kendala_hari_ini ?? '-' }}
                </span>
              </td>
              <td>
                @if($abs->approval_status === 'Disetujui')
                  <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="bi bi-check-circle me-1"></i>Disetujui</span>
                @elseif($abs->approval_status === 'Ditolak')
                  <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-x-circle me-1"></i>Ditolak</span>
                @else
                  <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="bi bi-clock me-1"></i>Pending</span>
                @endif
              </td>
              <td class="pe-4 text-end">
                <div class="dropdown">
                  <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow border-0 small">
                    <li><button class="dropdown-item" onclick="viewDetail({{ $abs->id }})"><i class="bi bi-eye me-2 text-primary"></i>Lihat Detail / Foto</button></li>
                    @if($abs->approval_status === 'Pending')
                      <li><hr class="dropdown-divider"></li>
                      <li><button class="dropdown-item" onclick="updateStatus({{ $abs->id }}, 'Disetujui')"><i class="bi bi-check2-circle me-2 text-success"></i>Setujui</button></li>
                      <li><button class="dropdown-item" onclick="updateStatus({{ $abs->id }}, 'Ditolak')"><i class="bi bi-x-circle me-2 text-danger"></i>Tolak</button></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-danger" onclick="updateStatus({{ $abs->id }}, 'delete')"><i class="bi bi-trash me-2"></i>Hapus Data</button></li>
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada log absensi terbaru.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($recentAbsensi->hasPages())
    <div class="card-footer bg-white border-0 pt-0">
      {{ $recentAbsensi->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>

{{-- Modal Detail --}}
<div class="modal fade" id="modalDetail" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">Detail Absensi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <img id="detailFoto" src="" class="img-fluid rounded-3 shadow-sm d-none" style="max-height: 300px; object-fit: contain;">
          <div id="noFoto" class="text-muted small py-4 d-none bg-light rounded-3 border">
            <i class="bi bi-image text-secondary fs-1 d-block mb-2"></i>Tidak ada foto terlampir.
          </div>
        </div>
        <div class="row g-2 small">
          <div class="col-4 text-muted fw-semibold">Lokasi</div>
          <div class="col-8" id="detailLokasi">-</div>
          <div class="col-4 text-muted fw-semibold">Koordinat</div>
          <div class="col-8">
            <a href="#" target="_blank" id="detailMapLink" class="text-decoration-none">-</a>
          </div>
          <div class="col-4 text-muted fw-semibold">Catatan</div>
          <div class="col-8" id="detailCatatan">-</div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
  function viewDetail(id) {
    fetch(`/admin/absensi/${id}`)
      .then(res => res.json())
      .then(data => {
        const detailLokasi = document.getElementById('detailLokasi');
        const detailMapLink = document.getElementById('detailMapLink');
        const detailCatatan = document.getElementById('detailCatatan');
        const detailFoto = document.getElementById('detailFoto');
        const noFoto = document.getElementById('noFoto');

        detailLokasi.textContent = data.lokasi_text || '-';
        if (data.lat && data.lng) {
          detailMapLink.href = `https://maps.google.com/?q=${data.lat},${data.lng}`;
          detailMapLink.textContent = `${data.lat}, ${data.lng}`;
        } else {
          detailMapLink.removeAttribute('href');
          detailMapLink.textContent = '-';
        }
        detailCatatan.textContent = data.keterangan || data.kendala_hari_ini || '-';

        if (data.foto) {
          detailFoto.src = `/storage/${data.foto}`;
          detailFoto.classList.remove('d-none');
          noFoto.classList.add('d-none');
        } else {
          detailFoto.classList.add('d-none');
          noFoto.classList.remove('d-none');
        }

        new bootstrap.Modal(document.getElementById('modalDetail')).show();
      });
  }

  function updateStatus(id, status) {
    if (status === 'delete' && !confirm('Yakin ingin menghapus data absensi ini?')) return;
    
    fetch('{{ route("admin.absensi.status") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ id: id, approval: status })
    })
    .then(res => res.json())
    .then(data => {
      if(data.ok) {
        window.location.reload();
      } else {
        alert(data.msg);
      }
    });
  }
</script>
@endsection
