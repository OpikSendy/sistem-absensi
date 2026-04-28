@extends('layouts.app')
@section('title', 'Tugas Saya | Kesatriyan')
@section('page-title', 'Tugas Saya')

@section('content')

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="row g-4">
  <div class="col-12">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white border-bottom-0 pt-4 pb-3">
        <h6 class="fw-bold mb-0">Daftar Tugas Karyawan</h6>
        <p class="text-muted small mb-0 mt-1">Daftar tugas spesifik yang diberikan oleh Admin kepada Anda.</p>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small">
              <tr>
                <th class="ps-4">Judul Tugas</th>
                <th>Deskripsi</th>
                <th>Tenggat Waktu</th>
                <th>Status</th>
                <th class="pe-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($tugas as $t)
                <tr>
                  <td class="ps-4 fw-semibold text-dark">
                    {{ $t->judul_tugas }}
                    @if($t->master)
                      <span class="badge bg-light text-primary border ms-1" style="font-size:0.65rem">Template</span>
                    @endif
                  </td>
                  <td class="text-muted small" style="max-width: 250px;">
                    {{ $t->deskripsi ?: '-' }}
                  </td>
                  <td>
                    @if($t->tenggat_waktu)
                      @php
                         $deadline = \Carbon\Carbon::parse($t->tenggat_waktu);
                         $isOverdue = $t->status !== 'Completed' && $deadline->isPast();
                      @endphp
                      <span class="{{ $isOverdue ? 'text-danger fw-bold' : 'text-dark' }} small">
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
                    @if($t->status === 'Completed')
                      <span class="badge badge-soft-success rounded-pill px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Completed</span>
                    @elseif($t->status === 'In Progress')
                      <span class="badge badge-soft-primary rounded-pill px-2 py-1"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>
                    @else
                      <span class="badge badge-soft-warning rounded-pill px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                    @endif
                  </td>
                  <td class="pe-4 text-center">
                    <button class="btn btn-sm btn-light border text-primary" onclick='updateStatus(@json($t))'>
                      <i class="bi bi-pencil-square me-1"></i>Update
                    </button>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada tugas yang diberikan kepada Anda.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Modal Update Status --}}
<div class="modal fade" id="modalUpdateStatus" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form id="formUpdateStatus" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header border-bottom-0 pb-0">
          <h5 class="modal-title fw-bold">Update Status Tugas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-4">
          <div class="mb-3">
            <h6 class="fw-bold text-dark" id="displayJudul">Judul Tugas</h6>
            <p class="text-muted small mb-0" id="displayDeskripsi">Deskripsi</p>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Status Penyelesaian *</label>
            <select name="status" id="edit_status" class="form-select" required>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Catatan Laporan (Opsional)</label>
            <textarea name="catatan_karyawan" id="edit_catatan" class="form-control" rows="3" placeholder="Tulis catatan penyelesaian tugas..."></textarea>
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

@endsection

@section('scripts')
<script>
  function updateStatus(tugas) {
    document.getElementById('formUpdateStatus').action = `/user/tugas/${tugas.id}`;
    document.getElementById('displayJudul').textContent = tugas.judul_tugas;
    document.getElementById('displayDeskripsi').textContent = tugas.deskripsi || '-';
    
    document.getElementById('edit_status').value = tugas.status;
    document.getElementById('edit_catatan').value = tugas.catatan_karyawan || '';
    
    new bootstrap.Modal(document.getElementById('modalUpdateStatus')).show();
  }
</script>
@endsection
