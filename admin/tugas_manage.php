<?php
// admin/tugas_manage.php
// UI MODERN: Master & Sub Task Management

session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in() || !is_admin()) {
    redirect(url('user/403.php'));
}

$page_title = "Kelola Master Tugas";
$active_menu = 'tugas';

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok_post()) {
        $_SESSION['flash_err'] = 'Token keamanan tidak valid.';
        redirect(url('admin/tugas_manage.php'));
    }

    $action = $_POST['action'] ?? '';

    // 1. MASTER TUGAS
    if ($action === 'add_master') {
        $nama = trim($_POST['nama_tugas'] ?? '');
        $kat  = trim($_POST['kategori'] ?? '');
        $akt  = isset($_POST['aktif_master']) ? 1 : 0;
        
        if ($nama) {
            $stmt = db()->prepare("INSERT INTO tugas_master (nama_tugas, kategori, aktif) VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $nama, $kat, $akt);
            if ($stmt->execute()) $_SESSION['flash_ok'] = 'Master tugas berhasil ditambahkan.';
            else $_SESSION['flash_err'] = 'Gagal: ' . $stmt->error;
            $stmt->close();
        }
    } 
    elseif ($action === 'update_master') {
        $id   = (int)$_POST['master_id'];
        $nama = trim($_POST['nama_tugas'] ?? '');
        $kat  = trim($_POST['kategori'] ?? '');
        $akt  = isset($_POST['aktif_master']) ? 1 : 0;

        if ($id && $nama) {
            $stmt = db()->prepare("UPDATE tugas_master SET nama_tugas=?, kategori=?, aktif=? WHERE id=?");
            $stmt->bind_param('ssii', $nama, $kat, $akt, $id);
            if ($stmt->execute()) $_SESSION['flash_ok'] = 'Master tugas diperbarui.';
            else $_SESSION['flash_err'] = 'Gagal update: ' . $stmt->error;
            $stmt->close();
        }
    } 
    elseif ($action === 'delete_master') {
        $id = (int)$_POST['master_id'];
        // Hapus sub tugas terkait dulu (opsional, atau biarkan cascade di DB jika ada)
        db()->query("DELETE FROM tugas_sub_master WHERE master_id=$id");
        if (db()->query("DELETE FROM tugas_master WHERE id=$id")) {
            $_SESSION['flash_ok'] = 'Master tugas dihapus.';
        }
    }

    // 2. SUB TUGAS
    elseif ($action === 'add_sub') {
        $mid  = (int)$_POST['master_id_sub'];
        $nama = trim($_POST['nama_sub'] ?? '');
        $akt  = isset($_POST['aktif_sub']) ? 1 : 0;

        if ($mid && $nama) {
            $stmt = db()->prepare("INSERT INTO tugas_sub_master (master_id, nama_sub, aktif) VALUES (?, ?, ?)");
            $stmt->bind_param('isi', $mid, $nama, $akt);
            if ($stmt->execute()) $_SESSION['flash_ok'] = 'Sub tugas berhasil ditambahkan.';
            else $_SESSION['flash_err'] = 'Gagal: ' . $stmt->error;
            $stmt->close();
        }
    }
    elseif ($action === 'update_sub') {
        $sid  = (int)$_POST['sub_id'];
        $mid  = (int)$_POST['master_id_sub'];
        $nama = trim($_POST['nama_sub'] ?? '');
        $akt  = isset($_POST['aktif_sub']) ? 1 : 0;

        if ($sid && $mid && $nama) {
            $stmt = db()->prepare("UPDATE tugas_sub_master SET master_id=?, nama_sub=?, aktif=? WHERE id=?");
            $stmt->bind_param('isii', $mid, $nama, $akt, $sid);
            if ($stmt->execute()) $_SESSION['flash_ok'] = 'Sub tugas diperbarui.';
            else $_SESSION['flash_err'] = 'Gagal update: ' . $stmt->error;
            $stmt->close();
        }
    }
    elseif ($action === 'delete_sub') {
        $id = (int)$_POST['sub_id'];
        if (db()->query("DELETE FROM tugas_sub_master WHERE id=$id")) {
            $_SESSION['flash_ok'] = 'Sub tugas dihapus.';
        }
    }

    redirect(url('admin/tugas_manage.php'));
}

include __DIR__ . '/../includes/header.php';

// DATA FETCH
$masters = db()->query("SELECT * FROM tugas_master ORDER BY nama_tugas ASC");
$subs    = db()->query("
    SELECT s.*, m.nama_tugas as master_nama 
    FROM tugas_sub_master s 
    JOIN tugas_master m ON m.id = s.master_id 
    ORDER BY m.nama_tugas ASC, s.nama_sub ASC
");
?>

<div class="container-fluid px-4 py-4">
  
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Manajemen Tugas</h3>
        <p class="text-muted mb-0">Atur daftar pekerjaan yang muncul di formulir absensi.</p>
    </div>
  </div>

  <?php if (isset($_SESSION['flash_ok'])): ?>
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4"><i class="bi bi-check-circle me-2"></i><?= $_SESSION['flash_ok']; unset($_SESSION['flash_ok']); ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['flash_err'])): ?>
      <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle me-2"></i><?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <div class="row g-4">
    
    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark m-0">1. Master Tugas (Kategori)</h6>
                <button class="btn btn-primary btn-sm shadow-sm" onclick="openAddMaster()">
                    <i class="bi bi-plus-lg"></i> Tambah
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-3">Nama Tugas</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($masters && $masters->num_rows > 0): ?>
                            <?php while($m = $masters->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark"><?= e($m['nama_tugas']) ?></div>
                                    <?php if($m['kategori']): ?>
                                        <div class="small text-muted text-uppercase" style="font-size:0.7rem"><?= e($m['kategori']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($m['aktif']): ?>
                                        <span class="badge badge-soft-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-secondary">Non-Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-secondary" onclick='editMaster(<?= json_encode($m) ?>)'><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="delMaster(<?= $m['id'] ?>)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada master tugas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-dark m-0">2. Sub Tugas (Detail)</h6>
                <button class="btn btn-outline-primary btn-sm" onclick="openAddSub()">
                    <i class="bi bi-plus-lg"></i> Tambah Sub
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-3">Induk (Master)</th>
                            <th>Nama Sub Tugas</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($subs && $subs->num_rows > 0): ?>
                            <?php while($s = $subs->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 text-primary fw-medium"><?= e($s['master_nama']) ?></td>
                                <td><?= e($s['nama_sub']) ?></td>
                                <td>
                                    <?php if($s['aktif']): ?>
                                        <span class="badge badge-soft-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-secondary">Non-Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-secondary" onclick='editSub(<?= json_encode($s) ?>)'><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="delSub(<?= $s['id'] ?>)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada sub tugas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

  </div>
</div>

<div class="modal fade" id="modalMaster" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="masterAction" value="add_master">
        <input type="hidden" name="master_id" id="masterId">
        
        <div class="modal-header border-bottom bg-light">
          <h5 class="modal-title fw-bold" id="modalMasterTitle">Tambah Master Tugas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Nama Tugas</label>
                <input type="text" name="nama_tugas" id="masterNama" class="form-control" placeholder="Contoh: Maintenance, Development" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Kategori (Opsional)</label>
                <input type="text" name="kategori" id="masterKat" class="form-control" placeholder="Contoh: IT, HRD">
            </div>
            <div class="form-check form-switch bg-light p-2 rounded ps-5">
                <input class="form-check-input" type="checkbox" name="aktif_master" id="masterAktif" value="1" checked>
                <label class="form-check-label" for="masterAktif">Status Aktif</label>
            </div>
        </div>
        <div class="modal-footer border-top bg-light">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalSub" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" id="subAction" value="add_sub">
        <input type="hidden" name="sub_id" id="subId">
        
        <div class="modal-header border-bottom bg-light">
          <h5 class="modal-title fw-bold" id="modalSubTitle">Tambah Sub Tugas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Induk (Master Tugas)</label>
                <select name="master_id_sub" id="subMasterId" class="form-select" required>
                    <option value="">-- Pilih Master --</option>
                    <?php 
                    $masters->data_seek(0); // Reset pointer
                    while($m = $masters->fetch_assoc()): 
                    ?>
                        <option value="<?= $m['id'] ?>"><?= e($m['nama_tugas']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted">Nama Sub Tugas</label>
                <input type="text" name="nama_sub" id="subNama" class="form-control" placeholder="Contoh: Bug Fixing, Meeting Harian" required>
            </div>
            <div class="form-check form-switch bg-light p-2 rounded ps-5">
                <input class="form-check-input" type="checkbox" name="aktif_sub" id="subAktif" value="1" checked>
                <label class="form-check-label" for="subAktif">Status Aktif</label>
            </div>
        </div>
        <div class="modal-footer border-top bg-light">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="formDel" method="post" style="display:none">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="delAction">
    <input type="hidden" name="master_id" id="delMasterId">
    <input type="hidden" name="sub_id" id="delSubId">
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// MASTER HELPERS
function openAddMaster() {
    document.getElementById('masterAction').value = 'add_master';
    document.getElementById('masterId').value = '';
    document.getElementById('masterNama').value = '';
    document.getElementById('masterKat').value = '';
    document.getElementById('masterAktif').checked = true;
    document.getElementById('modalMasterTitle').textContent = 'Tambah Master Tugas';
    new bootstrap.Modal(document.getElementById('modalMaster')).show();
}
function editMaster(d) {
    document.getElementById('masterAction').value = 'update_master';
    document.getElementById('masterId').value = d.id;
    document.getElementById('masterNama').value = d.nama_tugas;
    document.getElementById('masterKat').value = d.kategori;
    document.getElementById('masterAktif').checked = (d.aktif == 1);
    document.getElementById('modalMasterTitle').textContent = 'Edit Master Tugas';
    new bootstrap.Modal(document.getElementById('modalMaster')).show();
}
function delMaster(id) {
    if(!confirm('Hapus master tugas ini? Semua sub-tugas terkait juga akan terhapus.')) return;
    document.getElementById('delAction').value = 'delete_master';
    document.getElementById('delMasterId').value = id;
    document.getElementById('formDel').submit();
}

// SUB HELPERS
function openAddSub() {
    document.getElementById('subAction').value = 'add_sub';
    document.getElementById('subId').value = '';
    document.getElementById('subMasterId').value = '';
    document.getElementById('subNama').value = '';
    document.getElementById('subAktif').checked = true;
    document.getElementById('modalSubTitle').textContent = 'Tambah Sub Tugas';
    new bootstrap.Modal(document.getElementById('modalSub')).show();
}
function editSub(d) {
    document.getElementById('subAction').value = 'update_sub';
    document.getElementById('subId').value = d.id;
    document.getElementById('subMasterId').value = d.master_id;
    document.getElementById('subNama').value = d.nama_sub;
    document.getElementById('subAktif').checked = (d.aktif == 1);
    document.getElementById('modalSubTitle').textContent = 'Edit Sub Tugas';
    new bootstrap.Modal(document.getElementById('modalSub')).show();
}
function delSub(id) {
    if(!confirm('Hapus sub tugas ini?')) return;
    document.getElementById('delAction').value = 'delete_sub';
    document.getElementById('delSubId').value = id;
    document.getElementById('formDel').submit();
}
</script>