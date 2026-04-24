@extends('layouts.app')
@section('title', 'Manajemen Karyawan | Kesatriyan Admin')
@section('page-title', 'Manajemen Karyawan')

@section('content')
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white border-bottom-0 pt-4 pb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <h6 class="fw-bold mb-0">Daftar Karyawan</h6>
    <div class="d-flex gap-2">
      <form action="{{ route('admin.users') }}" method="GET" class="d-flex position-relative">
        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
        <input type="text" name="search" class="form-control form-control-sm rounded-pill ps-5" placeholder="Cari nama/divisi..." value="{{ $search ?? '' }}">
      </form>
      <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalUser">
        <i class="bi bi-plus-lg me-1"></i>Tambah
      </button>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-muted small">
          <tr>
            <th class="ps-4">Karyawan</th>
            <th>Role</th>
            <th>Username</th>
            <th>Divisi</th>
            <th>No. HP</th>
            <th>Status</th>
            <th class="pe-4 text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  @if($u->foto)
                    <img src="{{ asset('storage/' . $u->foto) }}" class="rounded-circle object-fit-cover" width="36" height="36">
                  @else
                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:36px; height:36px; font-size:.9rem;">
                      {{ strtoupper(substr($u->nama, 0, 2)) }}
                    </div>
                  @endif
                  <div>
                    <div class="fw-semibold text-dark">{{ $u->nama }}</div>
                    <div class="text-muted" style="font-size:.75rem;">Terdaftar: {{ Carbon\Carbon::parse($u->created_at)->format('d M Y') }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge bg-{{ $u->role === 'admin' ? 'danger' : 'primary' }} bg-opacity-10 text-{{ $u->role === 'admin' ? 'danger' : 'primary' }} border border-{{ $u->role === 'admin' ? 'danger' : 'primary' }} border-opacity-25">
                  {{ ucfirst($u->role) }}
                </span>
              </td>
              <td class="text-muted small">{{ $u->username }}</td>
              <td class="text-muted small">{{ $u->devisi ?: '-' }}</td>
              <td class="text-muted small">{{ $u->no_hp ?: '-' }}</td>
              <td>
                @if($u->aktif)
                  <span class="badge bg-success">Aktif</span>
                @else
                  <span class="badge bg-secondary">Non-Aktif</span>
                @endif
              </td>
              <td class="pe-4 text-end">
                <button class="btn btn-sm btn-light border text-primary" title="Edit Data"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-light border text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data karyawan ditemukan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($users->hasPages())
    <div class="card-footer bg-white border-0 pt-3 pb-3">
      {{ $users->appends(['search' => $search])->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>

{{-- Modal Placeholder untuk Phase Selanjutnya --}}
<div class="modal fade" id="modalUser" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">Tambah Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-5">
        <i class="bi bi-tools text-muted mb-3" style="font-size: 3rem;"></i>
        <p class="text-muted mb-0">Fitur CRUD Karyawan secara lengkap belum diimplementasikan di Phase ini.</p>
      </div>
    </div>
  </div>
</div>
@endsection
