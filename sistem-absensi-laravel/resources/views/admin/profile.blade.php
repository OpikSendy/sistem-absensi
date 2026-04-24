@extends('layouts.app')
@section('title', 'Profil Admin | Kesatriyan Admin')
@section('page-title', 'Profil Admin')

@section('content')

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi kesalahan saat memproses data.
    <ul class="mb-0 mt-2">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="row g-4">
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm border-0 h-100 text-center">
      <div class="card-body p-4">
        <div class="mb-4 position-relative d-inline-block">
          @if($user->foto)
            <img src="{{ asset('storage/' . $user->foto) }}" class="rounded-circle object-fit-cover shadow-sm border" width="120" height="120" alt="Avatar">
          @else
            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width:120px; height:120px; font-size:2.5rem;">
              {{ strtoupper(substr($user->nama, 0, 2)) }}
            </div>
          @endif
        </div>
        <h5 class="fw-bold mb-1">{{ $user->nama }}</h5>
        <p class="text-muted small mb-3">{{ $user->username }} | <span class="badge bg-danger">Administrator</span></p>
        <p class="small text-muted mb-0"><i class="bi bi-calendar3 me-1"></i>Bergabung sejak {{ \Carbon\Carbon::parse($user->created_at)->format('d F Y') }}</p>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h6 class="fw-bold mb-0">Informasi Pribadi & Akun</h6>
      </div>
      <div class="card-body p-4">
        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Nama Lengkap *</label>
              <input type="text" name="nama" class="form-control" value="{{ old('nama', $user->nama) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Username *</label>
              <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Email</label>
              <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">No. HP</label>
              <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $user->no_hp) }}">
            </div>
            
            <div class="col-12 mt-4">
              <hr class="text-muted opacity-25">
              <h6 class="fw-bold mb-3">Ganti Password <span class="text-muted fw-normal small">(Opsional)</span></h6>
            </div>
            
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Password Baru</label>
              <input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tidak diubah">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Konfirmasi Password Baru</label>
              <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password baru">
            </div>
            
            <div class="col-12 mt-4">
              <hr class="text-muted opacity-25">
              <h6 class="fw-bold mb-3">Foto Profil <span class="text-muted fw-normal small">(Opsional)</span></h6>
            </div>

            <div class="col-12">
              <input type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/jpg">
              <div class="form-text small">Maksimal 2MB. Format: JPG, PNG, JPEG.</div>
            </div>

            <div class="col-12 text-end mt-4">
              <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill">Simpan Perubahan</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
