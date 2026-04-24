@extends('layouts.app')
@section('title', 'Manajemen Shift | Kesatriyan Admin')
@section('page-title', 'Manajemen Shift')

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
                    <button class="btn btn-sm btn-light border text-primary" title="Edit Shift" onclick='editShift(@json($s))'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-light border text-danger" title="Hapus" onclick="deleteShift({{ $s->id }}, '{{ $s->nama_shift }}')"><i class="bi bi-trash"></i></button>
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

{{-- Modal Tambah Shift --}}
<div class="modal fade" id="modalShift" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="{{ route('admin.shifts.store') }}" method="POST">
        @csrf
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Tambah Shift Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Nama Shift *</label>
              <input type="text" name="nama_shift" class="form-control" required placeholder="Contoh: Shift Pagi">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Jam Masuk *</label>
              <input type="time" name="jam_masuk" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Jam Pulang</label>
              <input type="time" name="jam_pulang" class="form-control">
              <div class="form-text small">Biarkan kosong jika waktu bebas.</div>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Toleransi Telat (Menit) *</label>
              <input type="number" name="toleransi_menit" class="form-control" value="0" min="0" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Durasi Kerja (Menit) *</label>
              <input type="number" name="durasi_menit" class="form-control" value="0" min="0" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Status *</label>
              <select name="aktif" class="form-select" required>
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill">Simpan Data</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Edit Shift --}}
<div class="modal fade" id="modalEditShift" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form id="formEditShift" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Edit Shift</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Nama Shift *</label>
              <input type="text" name="nama_shift" id="edit_nama_shift" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Jam Masuk *</label>
              <input type="time" name="jam_masuk" id="edit_jam_masuk" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Jam Pulang</label>
              <input type="time" name="jam_pulang" id="edit_jam_pulang" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Toleransi Telat (Menit) *</label>
              <input type="number" name="toleransi_menit" id="edit_toleransi_menit" class="form-control" min="0" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-muted">Durasi Kerja (Menit) *</label>
              <input type="number" name="durasi_menit" id="edit_durasi_menit" class="form-control" min="0" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Status *</label>
              <select name="aktif" id="edit_aktif" class="form-select" required>
                <option value="1">Aktif</option>
                <option value="0">Non-Aktif</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Form Delete (Hidden) --}}
<form id="formDeleteShift" method="POST" class="d-none">
  @csrf
  @method('DELETE')
</form>

@endsection

@section('scripts')
<script>
  function editShift(shift) {
    document.getElementById('formEditShift').action = `/admin/shifts/${shift.id}`;
    document.getElementById('edit_nama_shift').value = shift.nama_shift;
    
    // Format H:i dari format lengkap H:i:s
    const formatTime = (timeStr) => timeStr ? timeStr.substring(0, 5) : '';
    
    document.getElementById('edit_jam_masuk').value = formatTime(shift.jam_masuk);
    document.getElementById('edit_jam_pulang').value = formatTime(shift.jam_pulang);
    document.getElementById('edit_toleransi_menit').value = shift.toleransi_menit;
    document.getElementById('edit_durasi_menit').value = shift.durasi_menit;
    document.getElementById('edit_aktif').value = shift.aktif;
    
    new bootstrap.Modal(document.getElementById('modalEditShift')).show();
  }

  function deleteShift(id, nama) {
    if (confirm(`Yakin ingin menghapus shift "${nama}"? Data ini tidak bisa dihapus jika sedang digunakan pada jadwal.`)) {
      const form = document.getElementById('formDeleteShift');
      form.action = `/admin/shifts/${id}`;
      form.submit();
    }
  }
</script>
@endsection
