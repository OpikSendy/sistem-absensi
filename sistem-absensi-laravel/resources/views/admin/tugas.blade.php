@extends('layouts.app')
@section('title', 'Manajemen Tugas | Kesatriyan Admin')
@section('page-title', 'Manajemen Master Tugas')

@section('styles')
  <style>
    .sub-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 2px 10px;
      font-size: .72rem;
      font-weight: 600;
    }

    .master-row {
      cursor: pointer;
      transition: background .15s;
    }

    .master-row.selected td {
      background: #eff6ff !important;
    }

    .master-row:hover td {
      background: #f8fafc;
    }
  </style>
@endsection

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
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">

    {{-- ─── Panel 1: Master Tugas ─────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="fw-bold mb-0">1. Master Tugas (Kategori)</h6>
            <p class="text-muted small mb-0">Klik baris untuk melihat sub tugasnya.</p>
          </div>
          <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal"
            data-bs-target="#modalTugas">
            <i class="bi bi-plus-lg me-1"></i>Tambah
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light text-muted small">
                <tr>
                  <th class="ps-4">Nama Tugas</th>
                  <th>Kategori</th>
                  <th>Sub</th>
                  <th>Status</th>
                  <th class="pe-4 text-end">Aksi</th>
                </tr>
              </thead>
              <tbody id="masterTbody">
                @forelse($tugas as $t)
                  <tr class="master-row" data-master-id="{{ $t->id }}" data-master-nama="{{ $t->nama_tugas }}"
                    onclick="selectMaster(this)">
                    <td class="ps-4 fw-semibold text-dark">{{ $t->nama_tugas }}</td>
                    <td class="text-muted small">{{ $t->kategori ?: '-' }}</td>
                    <td><span class="sub-badge"><i class="bi bi-list-ul"></i>{{ $t->subTugas->count() }}</span></td>
                    <td>
                      @if($t->aktif)
                        <span class="badge bg-success">Aktif</span>
                      @else
                        <span class="badge bg-secondary">Non-Aktif</span>
                      @endif
                    </td>
                    <td class="pe-4 text-end" onclick="event.stopPropagation()">
                      <button class="btn btn-sm btn-light border text-primary" title="Edit" data-tugas='@json($t)'
                        onclick="editTugas(this)">
                        <i class="bi bi-pencil"></i>
                      </button> <button class="btn btn-sm btn-light border text-danger" title="Hapus"
                        onclick="deleteTugas({{ $t->id }}, '{{ addslashes($t->nama_tugas) }}')"><i
                          class="bi bi-trash"></i></button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada master tugas.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- ─── Panel 2: Sub Tugas ─────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-bottom pt-4 pb-3 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="fw-bold mb-0">2. Sub Tugas <span id="subPanelMasterName" class="text-primary fw-bold"></span></h6>
            <p class="text-muted small mb-0" id="subPanelHint">← Klik salah satu Master Tugas untuk melihat sub tugasnya.
            </p>
          </div>
          <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" id="btnTambahSub" disabled
            onclick="openAddSub()">
            <i class="bi bi-plus-lg me-1"></i>Tambah Sub
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light text-muted small">
                <tr>
                  <th class="ps-4">Nama Sub Tugas</th>
                  <th>Status</th>
                  <th class="pe-4 text-end">Aksi</th>
                </tr>
              </thead>
              <tbody id="subTbody">
                <tr id="subEmptyRow">
                  <td colspan="3" class="text-center text-muted py-5">
                    <i class="bi bi-arrow-left-circle fs-3 d-block mb-2 opacity-25"></i>
                    Pilih master tugas terlebih dahulu.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- ─── Modals ─────────────────────────────────────────────────────────────────── --}}

  {{-- Modal Tambah Master Tugas --}}
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
                <input type="text" name="nama_tugas" class="form-control" required
                  placeholder="Contoh: Maintenance Server">
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold text-muted">Kategori</label>
                <input type="text" name="kategori" class="form-control" list="kategoriList"
                  placeholder="Pilih atau ketik kategori baru...">
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

  {{-- Modal Edit Master Tugas --}}
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
                <input type="text" name="kategori" id="edit_kategori" class="form-control" list="kategoriList">
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

  {{-- Modal Tambah/Edit Sub Tugas --}}
  <div class="modal fade" id="modalSub" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <form id="formSub" method="POST">
          @csrf
          <input type="hidden" name="_method" id="subMethod" value="POST">
          <input type="hidden" name="master_id" id="subMasterId">
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold" id="subModalTitle">Tambah Sub Tugas</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body py-4">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-semibold text-muted">Induk (Master Tugas)</label>
                <input type="text" id="subMasterNamaDisplay" class="form-control bg-light" readonly>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold text-muted">Nama Sub Tugas *</label>
                <input type="text" name="nama_sub" id="subNamaSub" class="form-control" required
                  placeholder="Contoh: Setup environment, Testing, Deploy" onclick>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold text-muted">Status *</label>
                <select name="aktif" id="subAktif" class="form-select" required>
                  <option value="1">Aktif</option>
                  <option value="0">Non-Aktif</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Hidden Delete Forms --}}
  <form id="formDeleteTugas" method="POST" class="d-none">@csrf @method('DELETE')</form>
  <form id="formDeleteSub" method="POST" class="d-none">@csrf @method('DELETE')</form>

@endsection

@section('scripts')
  <script>
    // Embed sub tugas data as JSON for client-side filtering
    const allSubTugas = @json($subTugasJson);

    let activeMasterId = null;
    let activeMasterNama = '';

    // ─── Select master row ────────────────────────────────────────────────────────
    function selectMaster(row) {
      document.querySelectorAll('.master-row').forEach(r => r.classList.remove('selected'));
      row.classList.add('selected');
      activeMasterId = row.dataset.masterId;
      activeMasterNama = row.dataset.masterNama;

      document.getElementById('subPanelMasterName').textContent = '— ' + activeMasterNama;
      document.getElementById('subPanelHint').textContent = 'Daftar sub tugas dari master ini.';
      document.getElementById('btnTambahSub').disabled = false;

      renderSubTable();
    }

    function renderSubTable() {
      const filtered = allSubTugas.filter(s => String(s.master_id) === String(activeMasterId));
      const tbody = document.getElementById('subTbody');

      if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>Belum ada sub tugas. Klik "Tambah Sub".
              </td></tr>`;
        return;
      }

      tbody.innerHTML = filtered.map(s => `
              <tr>
                <td class="ps-4 fw-semibold text-dark">${escHtml(s.nama_sub)}</td>
                <td>${s.aktif ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Non-Aktif</span>'}</td>
                <td class="pe-4 text-end">
                  <button class="btn btn-sm btn-light border text-primary" onclick='editSub(${JSON.stringify(s)})'>
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-light border text-danger" onclick="deleteSub(${s.id}, '${escHtml(s.nama_sub)}')">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            `).join('');
    }

    function escHtml(str) {
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ─── Open Add Sub Modal ───────────────────────────────────────────────────────
    function openAddSub() {
      document.getElementById('subModalTitle').textContent = 'Tambah Sub Tugas';
      document.getElementById('formSub').action = '{{ route("admin.sub_tugas.store") }}';
      document.getElementById('subMethod').value = 'POST';
      document.getElementById('subMasterId').value = activeMasterId;
      document.getElementById('subMasterNamaDisplay').value = activeMasterNama;
      document.getElementById('subNamaSub').value = '';
      document.getElementById('subAktif').value = '1';
      new bootstrap.Modal(document.getElementById('modalSub')).show();
    }

    // ─── Edit Sub ─────────────────────────────────────────────────────────────────
    function editSub(sub) {
      document.getElementById('subModalTitle').textContent = 'Edit Sub Tugas';
      document.getElementById('formSub').action = `/admin/sub-tugas/${sub.id}`;
      document.getElementById('subMethod').value = 'PUT';
      document.getElementById('subMasterId').value = sub.master_id;
      document.getElementById('subMasterNamaDisplay').value = activeMasterNama;
      document.getElementById('subNamaSub').value = sub.nama_sub;
      document.getElementById('subAktif').value = sub.aktif;
      new bootstrap.Modal(document.getElementById('modalSub')).show();
    }

    // ─── Delete Sub ───────────────────────────────────────────────────────────────
    function deleteSub(id, nama) {
      if (confirm(`Hapus sub tugas "${nama}"?`)) {
        const form = document.getElementById('formDeleteSub');
        form.action = `/admin/sub-tugas/${id}`;
        form.submit();
      }
    }

    // ─── Master CRUD helpers ──────────────────────────────────────────────────────
    function editTugas(button) {
      // Parse the JSON string from the data attribute
      const tugas = JSON.parse(button.getAttribute('data-tugas'));

      document.getElementById('formEditTugas').action = `/admin/tugas/${tugas.id}`;
      document.getElementById('edit_nama_tugas').value = tugas.nama_tugas;
      document.getElementById('edit_kategori').value = tugas.kategori || '';
      document.getElementById('edit_aktif').value = tugas.aktif;

      new bootstrap.Modal(document.getElementById('modalEditTugas')).show();
    }

    function deleteTugas(id, nama) {
      if (confirm(`Yakin ingin menghapus master tugas "${nama}"? Data tidak bisa dihapus jika sedang dipakai.`)) {
        const form = document.getElementById('formDeleteTugas');
        form.action = `/admin/tugas/${id}`;
        form.submit();
      }
    }
  </script>
@endsection