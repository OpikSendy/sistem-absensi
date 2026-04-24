@extends('layouts.app')
@section('title', 'Manajemen Tugas | Kesatriyan Admin')
@section('page-title', 'Manajemen Master Tugas')

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
        <h6 class="fw-bold mb-0">Daftar Master Tugas</h6>
        <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTugas">
          <i class="bi bi-plus-lg me-1"></i>Tambah Tugas
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th class="ps-4">Nama Tugas</th>
                <th>Kategori</th>
                <th>Status</th>
                <th class="pe-4 text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tugas as $t)
                <tr>
                  <td class="ps-4 fw-semibold text-dark">{{ $t->nama_tugas }}</td>
                  <td class="text-muted small">{{ $t->kategori ?: '-' }}</td>
                  <td>
                    @if($t->aktif)
                      <span class="badge bg-success">Aktif</span>
                    @else
                      <span class="badge bg-secondary">Non-Aktif</span>
                    @endif
                  </td>
                  <td class="pe-4 text-end">
                    <button class="btn btn-sm btn-light border text-primary" title="Edit Tugas" onclick='editTugas(@json($t))'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-light border text-danger" title="Hapus" onclick="deleteTugas({{ $t->id }}, '{{ addslashes($t->nama_tugas) }}')"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data master tugas.</td></tr>
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
        <h5 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Informasi Master Tugas</h5>
        <p class="small opacity-75 mb-3">Tugas Master digunakan untuk standarisasi jenis tugas harian karyawan:</p>
        <ol class="small opacity-75 ps-3 mb-0" style="line-height:1.8;">
          <li>Karyawan akan memilih tugas dari daftar master ini saat mereka melaporkan aktivitas harian (ToDo list).</li>
          <li>Data tidak dapat dihapus jika sudah ada karyawan yang pernah melaporkan tugas tersebut. Sebaiknya gunakan fitur <strong>Non-Aktif</strong>.</li>
        </ol>
      </div>
    </div>
  </div>
</div>

{{-- Modal Tambah Tugas --}}
<div class="modal fade" id="modalTugas" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form action="{{ route('admin.tugas.store') }}" method="POST">
        @csrf
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Tambah Master Tugas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Nama Tugas *</label>
              <input type="text" name="nama_tugas" class="form-control" required placeholder="Contoh: Maintenance Server">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Kategori</label>
              <input type="text" name="kategori" class="form-control" list="kategoriList" placeholder="Pilih dari daftar atau ketik kategori baru...">
              <datalist id="kategoriList">
                <option value="IT & Support">
                <option value="Marketing & Sales">
                <option value="Finance & Accounting">
                <option value="HRD & General Affair">
                <option value="Operasional">
                <option value="Administrasi">
                <option value="Desain & Kreatif">
              </datalist>
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

{{-- Modal Edit Tugas --}}
<div class="modal fade" id="modalEditTugas" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form id="formEditTugas" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Edit Master Tugas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Nama Tugas *</label>
              <input type="text" name="nama_tugas" id="edit_nama_tugas" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Kategori</label>
              <input type="text" name="kategori" id="edit_kategori" class="form-control" list="kategoriList" placeholder="Pilih dari daftar atau ketik kategori baru...">
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
<form id="formDeleteTugas" method="POST" class="d-none">
  @csrf
  @method('DELETE')
</form>

@endsection

@section('scripts')
<script>
  function editTugas(tugas) {
    document.getElementById('formEditTugas').action = `/admin/tugas/${tugas.id}`;
    document.getElementById('edit_nama_tugas').value = tugas.nama_tugas;
    document.getElementById('edit_kategori').value = tugas.kategori || '';
    document.getElementById('edit_aktif').value = tugas.aktif;
    
    new bootstrap.Modal(document.getElementById('modalEditTugas')).show();
  }

  function deleteTugas(id, nama) {
    if (confirm(`Yakin ingin menghapus master tugas "${nama}"? Data tidak bisa dihapus jika sedang dipakai di ToDo list karyawan.`)) {
      const form = document.getElementById('formDeleteTugas');
      form.action = `/admin/tugas/${id}`;
      form.submit();
    }
  }
</script>
@endsection
