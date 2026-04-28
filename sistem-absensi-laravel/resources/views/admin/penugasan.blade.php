@extends('layouts.app')
@section('title', 'Penugasan Karyawan | Kesatriyan Admin')
@section('page-title', 'Manajemen Penugasan Karyawan')

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
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Daftar Penugasan Karyawan</h6>
        <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalPenugasan">
          <i class="bi bi-plus-lg me-1"></i>Berikan Tugas
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th class="ps-4">Karyawan</th>
                <th>Judul Tugas</th>
                <th>Tenggat Waktu</th>
                <th>Status</th>
                <th>Catatan</th>
                <th class="pe-4 text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($penugasan as $p)
                <tr>
                  <td class="ps-4 fw-semibold text-dark">
                    {{ $p->user->nama ?? $p->user->username }}
                    <div class="small text-muted fw-normal">{{ $p->user->devisi ?? '-' }}</div>
                  </td>
                  <td>
                    <span class="fw-medium text-dark">{{ $p->judul_tugas }}</span>
                    @if($p->master)
                      <span class="badge bg-light text-primary border ms-1" style="font-size:0.65rem">Template</span>
                    @endif
                    @if($p->deskripsi)
                      <div class="small text-muted mt-1 text-truncate" style="max-width: 250px;">{{ $p->deskripsi }}</div>
                    @endif
                  </td>
                  <td>
                    @if($p->tenggat_waktu)
                      @php
                         $deadline = \Carbon\Carbon::parse($p->tenggat_waktu);
                         $isOverdue = $p->status !== 'Completed' && $deadline->isPast();
                      @endphp
                      <span class="{{ $isOverdue ? 'text-danger fw-bold' : 'text-dark' }}">
                        {{ $deadline->format('d M Y H:i') }}
                        @if($isOverdue)
                          <i class="bi bi-exclamation-circle ms-1" title="Melewati tenggat waktu"></i>
                        @endif
                      </span>
                    @else
                      <span class="text-muted small">-</span>
                    @endif
                  </td>
                  <td>
                    @if($p->status === 'Completed')
                      <span class="badge badge-soft-success rounded-pill px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Completed</span>
                    @elseif($p->status === 'In Progress')
                      <span class="badge badge-soft-primary rounded-pill px-2 py-1"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>
                    @else
                      <span class="badge badge-soft-warning rounded-pill px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                    @endif
                  </td>
                  <td>
                    @if($p->catatan_karyawan)
                      <button class="btn btn-sm btn-light border text-secondary" title="Lihat Catatan" onclick="showCatatan('{{ addslashes($p->catatan_karyawan) }}')">
                        <i class="bi bi-chat-text"></i>
                      </button>
                    @else
                      <span class="text-muted small">-</span>
                    @endif
                  </td>
                  <td class="pe-4 text-end">
                    <button class="btn btn-sm btn-light border text-primary" title="Edit" onclick='editPenugasan(@json($p))'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-light border text-danger" title="Hapus" onclick="deletePenugasan({{ $p->id }}, '{{ addslashes($p->judul_tugas) }}')"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada penugasan karyawan.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal Tambah Penugasan --}}
<div class="modal fade" id="modalPenugasan" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <form action="{{ route('admin.penugasan.store') }}" method="POST">
        @csrf
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Berikan Tugas Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Karyawan *</label>
              <select name="user_id" class="form-select" required>
                <option value="">-- Pilih Karyawan --</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}">{{ $u->nama ?: $u->username }} ({{ $u->devisi ?? 'Umum' }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Pilih dari Master Tugas (Opsional)</label>
              <select name="master_id" id="selectMaster" class="form-select" onchange="autoFillTugas()">
                <option value="">-- Tugas Kustom --</option>
                @foreach($tugasMasters as $tm)
                  <option value="{{ $tm->id }}" data-nama="{{ $tm->nama_tugas }}">{{ $tm->nama_tugas }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Judul Tugas *</label>
              <input type="text" name="judul_tugas" id="judulTugas" class="form-control" required placeholder="Contoh: Perbaiki Jaringan Lantai 2">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Deskripsi Detail</label>
              <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan detail tugas yang harus diselesaikan..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Tenggat Waktu (Deadline)</label>
              <input type="datetime-local" name="tenggat_waktu" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill">Simpan & Tugaskan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Edit Penugasan --}}
<div class="modal fade" id="modalEditPenugasan" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <form id="formEditPenugasan" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Edit Penugasan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Karyawan *</label>
              <select name="user_id" id="edit_user_id" class="form-select" required>
                @foreach($users as $u)
                  <option value="{{ $u->id }}">{{ $u->nama ?: $u->username }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Status *</label>
              <select name="status" id="edit_status" class="form-select" required>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
              </select>
            </div>
            <div class="col-md-6 d-none">
                <input type="hidden" name="master_id" id="edit_master_id">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Judul Tugas *</label>
              <input type="text" name="judul_tugas" id="edit_judul_tugas" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Deskripsi Detail</label>
              <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Tenggat Waktu</label>
              <input type="datetime-local" name="tenggat_waktu" id="edit_tenggat_waktu" class="form-control">
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

{{-- Modal Catatan --}}
<div class="modal fade" id="modalCatatan" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">Catatan Karyawan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-4">
        <p id="catatanText" class="text-dark mb-0"></p>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{-- Form Delete --}}
<form id="formDeletePenugasan" method="POST" class="d-none">
  @csrf
  @method('DELETE')
</form>

@endsection

@section('scripts')
<script>
  function autoFillTugas() {
    const select = document.getElementById('selectMaster');
    const input = document.getElementById('judulTugas');
    const selectedOption = select.options[select.selectedIndex];
    
    if(select.value) {
      input.value = selectedOption.getAttribute('data-nama');
    } else {
      input.value = '';
    }
  }

  function editPenugasan(p) {
    document.getElementById('formEditPenugasan').action = `/admin/penugasan/${p.id}`;
    document.getElementById('edit_user_id').value = p.user_id;
    document.getElementById('edit_status').value = p.status;
    document.getElementById('edit_master_id').value = p.master_id || '';
    document.getElementById('edit_judul_tugas').value = p.judul_tugas;
    document.getElementById('edit_deskripsi').value = p.deskripsi || '';
    
    if (p.tenggat_waktu) {
      // format datetime-local expected is YYYY-MM-DDThh:mm
      const dt = new Date(p.tenggat_waktu);
      dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
      document.getElementById('edit_tenggat_waktu').value = dt.toISOString().slice(0,16);
    } else {
      document.getElementById('edit_tenggat_waktu').value = '';
    }
    
    new bootstrap.Modal(document.getElementById('modalEditPenugasan')).show();
  }

  function deletePenugasan(id, judul) {
    if (confirm(`Yakin ingin menghapus penugasan "${judul}"?`)) {
      const form = document.getElementById('formDeletePenugasan');
      form.action = `/admin/penugasan/${id}`;
      form.submit();
    }
  }

  function showCatatan(teks) {
    document.getElementById('catatanText').textContent = teks;
    new bootstrap.Modal(document.getElementById('modalCatatan')).show();
  }
</script>
@endsection
