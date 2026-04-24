@extends('layouts.app')
@section('title', 'Manajemen Shift | Kesatriyan Admin')
@section('page-title', 'Manajemen Shift')

@section('content')
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Daftar Shift Kerja</h6>
        <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalShift">
          <i class="bi bi-plus-lg me-1"></i>Tambah Shift
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th class="ps-4">Nama Shift</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Toleransi</th>
                <th>Status</th>
                <th class="pe-4 text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($shifts as $s)
                <tr>
                  <td class="ps-4 fw-semibold text-dark">{{ $s->nama_shift }}</td>
                  <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{{ Carbon\Carbon::parse($s->jam_masuk)->format('H:i') }}</span></td>
                  <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $s->jam_pulang ? Carbon\Carbon::parse($s->jam_pulang)->format('H:i') : '-' }}</span></td>
                  <td class="text-muted small">{{ $s->toleransi_menit }} Menit</td>
                  <td>
                    @if($s->aktif)
                      <span class="badge bg-success">Aktif</span>
                    @else
                      <span class="badge bg-secondary">Non-Aktif</span>
                    @endif
                  </td>
                  <td class="pe-4 text-end">
                    <button class="btn btn-sm btn-light border text-primary" title="Edit Shift"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-light border text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data shift master.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm border-0 h-100 bg-primary text-white">
      <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Shift</h5>
        <p class="small opacity-75 mb-3">Sistem absensi menggunakan logika shift yang fleksibel:</p>
        <ol class="small opacity-75 ps-3 mb-0" style="line-height:1.8;">
          <li>Prioritas pertama: <strong>Jadwal Harian User</strong> (jika diatur spesifik pada hari tersebut).</li>
          <li>Prioritas kedua: <strong>Shift Default User</strong> (shift yang selalu berlaku untuk user tersebut).</li>
          <li>Toleransi keterlambatan dihitung dari <code class="bg-white text-primary px-1 rounded">jam_masuk + toleransi_menit</code>.</li>
        </ol>
      </div>
    </div>
  </div>
</div>

{{-- Modal Placeholder untuk CRUD Shift --}}
<div class="modal fade" id="modalShift" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">Tambah Shift Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-5">
        <i class="bi bi-clock-history text-muted mb-3" style="font-size: 3rem;"></i>
        <p class="text-muted mb-0">Fitur penambahan Shift secara lengkap belum diimplementasikan di Phase ini.</p>
      </div>
    </div>
  </div>
</div>
@endsection
