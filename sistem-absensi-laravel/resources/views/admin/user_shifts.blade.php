@extends('layouts.app')
@section('title', 'Penempatan Shift Karyawan | Kesatriyan Admin')
@section('page-title', 'Penempatan Shift Karyawan')

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

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <h6 class="fw-bold mb-0">Tabel Penempatan Shift Karyawan</h6>
      <div class="d-flex gap-2">
        <form action="{{ route('admin.user_shifts') }}" method="GET" class="d-flex position-relative">
          <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
          <input type="text" name="search" class="form-control form-control-sm rounded-pill ps-5"
            placeholder="Cari nama karyawan..." value="{{ $search ?? '' }}">
        </form>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted small">
            <tr>
              <th class="ps-4">Karyawan</th>
              <th>Divisi</th>
              <th>Shift Saat Ini</th>
              <th class="pe-4 text-end" style="width: 250px;">Aksi Penempatan</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $u)
              @php
                  // Ambil shift aktif dari relasi
                  $activeShift = $u->shifts->first(); 
              @endphp
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
                      <div class="text-muted" style="font-size:.75rem;">{{ $u->username }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-muted small">{{ $u->devisi ?: '-' }}</td>
                <td>
                  @if($activeShift && $activeShift->shift)
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-2 px-3">
                        <i class="bi bi-clock me-1"></i> {{ $activeShift->shift->nama_shift }}
                        ({{ \Carbon\Carbon::parse($activeShift->shift->jam_masuk)->format('H:i') }})
                    </span>
                  @else
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 py-2 px-3">
                        <i class="bi bi-exclamation-circle me-1"></i> Belum Ada Shift
                    </span>
                  @endif
                </td>
                <td class="pe-4 text-end">
                    <form action="{{ route('admin.user_shifts.update', $u->id) }}" method="POST" class="d-flex gap-2 justify-content-end align-items-center">
                        @csrf
                        <select name="shift_id" class="form-select form-select-sm" style="max-width: 180px;" required onchange="this.form.submit()">
                            <option value="">-- Pilih Shift --</option>
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ ($activeShift && $activeShift->shift_id == $s->id) ? 'selected' : '' }}>
                                    {{ $s->nama_shift }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">Tidak ada data karyawan ditemukan.</td>
              </tr>
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

  <div class="alert alert-info shadow-sm border-0 mt-4">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-info-circle-fill fs-4 text-info"></i>
        <div>
            <h6 class="fw-bold mb-1">Informasi Penempatan Shift</h6>
            <p class="small mb-0">Setiap karyawan harus memiliki shift kerja (misal: Pagi, Siang, dll) yang berlaku setiap harinya. Jika karyawan belum memiliki shift kerja yang aktif, absensinya akan menggunakan perhitungan waktu bebas/fleksibel (Default). Penempatan shift ini berlaku permanen hingga admin mengubahnya kembali.</p>
        </div>
    </div>
  </div>

@endsection
