@extends('layouts.app')
@section('title', 'Manajemen Karyawan | Kesatriyan Admin')
@section('page-title', 'Manajemen Karyawan')

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
    <div
      class="card-header bg-white border-bottom-0 pt-4 pb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <h6 class="fw-bold mb-0">Daftar Karyawan</h6>
      <div class="d-flex gap-2">
        <form action="{{ route('admin.users') }}" method="GET" class="d-flex position-relative">
          <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
          <input type="text" name="search" class="form-control form-control-sm rounded-pill ps-5"
            placeholder="Cari nama/divisi..." value="{{ $search ?? '' }}">
        </form>
        <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal"
          data-bs-target="#modalUser">
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
                      <img src="{{ asset('storage/' . $u->foto) }}" class="rounded-circle object-fit-cover" width="36"
                        height="36">
                    @else
                      <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                        style="width:36px; height:36px; font-size:.9rem;">
                        {{ strtoupper(substr($u->nama, 0, 2)) }}
                      </div>
                    @endif
                    <div>
                      <div class="fw-semibold text-dark">{{ $u->nama }}</div>
                      <div class="text-muted" style="font-size:.75rem;">Terdaftar:
                        {{ Carbon\Carbon::parse($u->created_at)->format('d M Y') }}
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span
                    class="badge bg-{{ $u->role === 'admin' ? 'danger' : 'primary' }} bg-opacity-10 text-{{ $u->role === 'admin' ? 'danger' : 'primary' }} border border-{{ $u->role === 'admin' ? 'danger' : 'primary' }} border-opacity-25">
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
                  <button class="btn btn-sm btn-light border text-primary" title="Edit Data"
                    onclick='editUser(@json($u))'><i class="bi bi-pencil"></i></button>
                  @if($u->id !== auth()->id())
                    <button class="btn btn-sm btn-light border text-danger" title="Hapus"
                      onclick="deleteUser({{ $u->id }}, '{{ $u->nama }}')"><i class="bi bi-trash"></i></button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Tidak ada data karyawan ditemukan.</td>
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

  {{-- Modal Tambah Karyawan --}}
  <div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <form action="{{ route('admin.users.store') }}" method="POST">
          @csrf
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold">Tambah Karyawan Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body py-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Username *</label>
                <input type="text" name="username" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Password *</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Role *</label>
                <select name="role" class="form-select" required>
                  <option value="user">User / Karyawan</option>
                  <option value="admin">Administrator</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Divisi</label>
                <input type="text" name="devisi" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">NIM / NIK</label>
                <input type="text" name="nim" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">No HP</label>
                <input type="text" name="no_hp" class="form-control">
              </div>
              <div class="col-md-6">
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

  {{-- Modal Edit Karyawan --}}
  <div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow">
        <form id="formEditUser" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header border-bottom-0 pb-0">
            <h5 class="modal-title fw-bold">Edit Karyawan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body py-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Nama Lengkap *</label>
                <input type="text" name="nama" id="edit_nama" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Username *</label>
                <input type="text" name="username" id="edit_username" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Password</label>
                <input type="password" name="password" class="form-control" minlength="6"
                  placeholder="Biarkan kosong jika tidak diubah">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Role *</label>
                <select name="role" id="edit_role" class="form-select" required>
                  <option value="user">User / Karyawan</option>
                  <option value="admin">Administrator</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">Divisi</label>
                <input type="text" name="devisi" id="edit_devisi" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">NIM / NIK</label>
                <input type="text" name="nim" id="edit_nim" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-semibold text-muted">No HP</label>
                <input type="text" name="no_hp" id="edit_no_hp" class="form-control">
              </div>
              <div class="col-md-6">
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
            {{-- Tambahkan class btn-primary dan logic disable-on-click --}}
            <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill"
              onclick="if(this.form.checkValidity()){ this.disabled=true; this.form.submit(); }">
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Form Delete (Hidden) --}}
  <form id="formDelete" method="POST" class="d-none">
    @csrf
    @method('DELETE')
  </form>

@endsection

@section('scripts')
  <script>
    function editUser(user) {
      document.getElementById('formEditUser').action = `/admin/users/${user.id}`;
      document.getElementById('edit_nama').value = user.nama;
      document.getElementById('edit_username').value = user.username;
      document.getElementById('edit_role').value = user.role;
      document.getElementById('edit_devisi').value = user.devisi || '';
      document.getElementById('edit_nim').value = user.nim || '';
      document.getElementById('edit_no_hp').value = user.no_hp || '';
      document.getElementById('edit_aktif').value = user.aktif;

      new bootstrap.Modal(document.getElementById('modalEditUser')).show();
    }

    function deleteUser(id, nama) {
      if (confirm(`Yakin ingin menghapus karyawan "${nama}"? Semua data absensi terkait juga akan terhapus.`)) {
        const form = document.getElementById('formDelete');
        form.action = `/admin/users/${id}`;
        form.submit();
      }
    }
  </script>
@endsection