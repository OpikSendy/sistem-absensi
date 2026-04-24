@extends('layouts.app')
@section('title', 'Profil Saya | Kesatriyan')
@section('page-title', 'Profil Saya')

@section('content')
<div class="row g-4">
  {{-- Kiri: Foto Profil --}}
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 text-center">
      <div class="card-body p-4">
        <div class="mb-3 position-relative d-inline-block">
          @php $u = auth()->user(); @endphp
          @if($u->foto)
            <img src="{{ asset('storage/' . $u->foto) }}" alt="Avatar" class="rounded-circle object-fit-cover shadow-sm" style="width: 120px; height: 120px;">
          @else
            <div class="rounded-circle shadow-sm bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px; font-size: 2.5rem; font-weight: bold;">
              {{ strtoupper(substr($u->nama ?: $u->username, 0, 2)) }}
            </div>
          @endif
          
          <button type="button" class="btn btn-sm btn-light border position-absolute bottom-0 end-0 rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAvatar" style="width:32px;height:32px;padding:0;">
            <i class="bi bi-camera"></i>
          </button>
        </div>
        
        <h5 class="fw-bold mb-1">{{ $u->nama ?: $u->username }}</h5>
        <p class="text-muted small mb-0">{{ $u->devisi ?: 'Belum ada divisi' }}</p>
        <div class="mt-2">
          <span class="badge bg-{{ $u->role === 'admin' ? 'danger' : 'primary' }} bg-opacity-10 text-{{ $u->role === 'admin' ? 'danger' : 'primary' }}">
            {{ ucfirst($u->role) }}
          </span>
        </div>
      </div>
    </div>
  </div>

  {{-- Kanan: Form Data Diri --}}
  <div class="col-12 col-md-8">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h6 class="fw-bold mb-0">Informasi Pribadi</h6>
      </div>
      <div class="card-body p-4">
        <form action="{{ route('user.profile.update') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Username</label>
              <input type="text" class="form-control bg-light" value="{{ $u->username }}" readonly disabled>
              <small class="text-muted" style="font-size:0.7rem;">Username tidak dapat diubah.</small>
            </div>
            
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Nama Lengkap</label>
              <input type="text" name="nama" class="form-control" value="{{ old('nama', $u->nama) }}" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">NIM / NIK</label>
              <input type="text" name="nim" class="form-control" value="{{ old('nim', $u->nim) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">No. HP</label>
              <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $u->no_hp) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Jurusan</label>
              <input type="text" name="jurusan" class="form-control" value="{{ old('jurusan', $u->jurusan) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Asal Sekolah / Kampus</label>
              <input type="text" name="asal_sekolah" class="form-control" value="{{ old('asal_sekolah', $u->asal_sekolah) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Tanggal Lahir</label>
              <input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir', $u->tanggal_lahir) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">No. HP Orang Tua</label>
              <input type="text" name="no_hp_orangtua" class="form-control" value="{{ old('no_hp_orangtua', $u->no_hp_orangtua) }}">
            </div>

            <div class="col-12 mt-4 text-end">
              <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill">Simpan Perubahan</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- Ganti Password --}}
    <div class="card shadow-sm border-0 mt-4">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h6 class="fw-bold mb-0">Ganti Password</h6>
      </div>
      <div class="card-body p-4">
        <form action="{{ route('user.profile.update') }}" method="POST">
          @csrf
          <input type="hidden" name="update_password" value="1">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Password Lama</label>
              <input type="password" name="old_password" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Password Baru</label>
              <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Konfirmasi Password Baru</label>
              <input type="password" name="new_password_confirmation" class="form-control" required>
            </div>
            <div class="col-12 text-end mt-3">
              <button type="submit" class="btn btn-danger px-4 fw-semibold rounded-pill">Ganti Password</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Modal Upload Avatar --}}
<div class="modal fade" id="modalAvatar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">Ubah Foto Profil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('user.avatar.upload') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="mb-3">
            <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg" required>
            <div class="form-text small">Format yang didukung: JPG, JPEG, PNG. Maks 2MB.</div>
          </div>
          <div class="d-flex justify-content-between">
            @if($u->foto)
              <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="event.preventDefault(); document.getElementById('formDeleteAvatar').submit();">Hapus Foto</button>
            @else
              <div></div>
            @endif
            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">Upload</button>
          </div>
        </form>

        @if($u->foto)
          <form id="formDeleteAvatar" action="{{ route('user.avatar.delete') }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
