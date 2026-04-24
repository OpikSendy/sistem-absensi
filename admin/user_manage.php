<?php
// admin/user_manage.php
// FIXED: Tambah Fitur Ganti Foto & UI Modern

session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in() || !is_admin()) {
    redirect(url('user/403.php'));
}

$page_title = "Kelola User";
$active_menu = 'users';

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok_post()) {
        $_SESSION['flash_err'] = 'Token keamanan tidak valid.';
        redirect(url('admin/user_manage.php'));
    }

    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    // 1. UPDATE USER
    if ($action === 'update' && $user_id > 0) {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $devisi = trim($_POST['devisi'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        $aktif = isset($_POST['aktif']) ? 1 : 0;

        // Data Profile
        $nim = trim($_POST['nim'] ?? '');
        $jurusan = trim($_POST['jurusan'] ?? '');
        $asal_sekolah = trim($_POST['asal_sekolah'] ?? '');
        $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $no_hp_orangtua = trim($_POST['no_hp_orangtua'] ?? '');

        if ($nama === '' || $username === '') {
            $_SESSION['flash_err'] = 'Nama dan Username wajib diisi.';
        } else {
            // --- LOGIKA UPLOAD FOTO (BARU) ---
            $fotoPath = null;
            if (!empty($_FILES['foto']['name'])) {
                $f = $_FILES['foto'];
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp'];
                
                if (in_array($ext, $allowed) && $f['size'] <= 5*1024*1024) {
                    // Hapus foto lama dulu
                    $qOld = db()->query("SELECT foto FROM users WHERE id=$user_id");
                    if ($qOld && $rOld = $qOld->fetch_assoc()) {
                        if (!empty($rOld['foto']) && file_exists(ROOT.'/'.$rOld['foto'])) {
                            @unlink(ROOT.'/'.$rOld['foto']);
                        }
                    }

                    // Upload baru
                    $targetDir = ROOT . '/uploads/avatars';
                    if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
                    
                    // Nama file aman
                    $safeName = date('YmdHis').'_admin_edit_'.$user_id.'.'.$ext;
                    if (move_uploaded_file($f['tmp_name'], $targetDir.'/'.$safeName)) {
                        $fotoPath = 'uploads/avatars/'.$safeName;
                    }
                }
            }

            // Query Update
            // Kita pisahkan logika jika ada foto baru atau tidak
            if ($fotoPath) {
                // Update dengan foto
                $stmt = db()->prepare("
                    UPDATE users SET
                      nama=?, username=?, devisi=?, role=?, aktif=?,
                      nim=?, jurusan=?, asal_sekolah=?, tanggal_lahir=NULLIF(?,''),
                      no_hp=NULLIF(?,''), no_hp_orangtua=NULLIF(?,''),
                      foto=?
                    WHERE id=?
                ");
                $stmt->bind_param(
                    'ssssisssssssi',
                    $nama, $username, $devisi, $role, $aktif,
                    $nim, $jurusan, $asal_sekolah, $tanggal_lahir,
                    $no_hp, $no_hp_orangtua, $fotoPath, $user_id
                );
            } else {
                // Update tanpa foto (foto lama tetap)
                $stmt = db()->prepare("
                    UPDATE users SET
                      nama=?, username=?, devisi=?, role=?, aktif=?,
                      nim=?, jurusan=?, asal_sekolah=?, tanggal_lahir=NULLIF(?,''),
                      no_hp=NULLIF(?,''), no_hp_orangtua=NULLIF(?,'')
                    WHERE id=?
                ");
                $stmt->bind_param(
                    'ssssissssssi',
                    $nama, $username, $devisi, $role, $aktif,
                    $nim, $jurusan, $asal_sekolah, $tanggal_lahir,
                    $no_hp, $no_hp_orangtua, $user_id
                );
            }

            if ($stmt->execute()) {
                $_SESSION['flash_ok'] = 'Data user berhasil diperbarui.';
            } else {
                $_SESSION['flash_err'] = 'Gagal update: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    // 2. DELETE USER
    elseif ($action === 'delete' && $user_id > 0) {
        $qf = db()->query("SELECT foto FROM users WHERE id=$user_id");
        if ($qf && $rf = $qf->fetch_assoc()) {
            if ($rf['foto'] && file_exists(ROOT . '/' . $rf['foto'])) @unlink(ROOT . '/' . $rf['foto']);
        }
        
        $stmt = db()->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $_SESSION['flash_ok'] = 'User berhasil dihapus permanen.';
        } else {
            $_SESSION['flash_err'] = 'Gagal hapus: ' . $stmt->error;
        }
        $stmt->close();
    }

    // 3. RESET PASSWORD
    elseif ($action === 'reset_password' && $user_id > 0) {
        $new_password = trim($_POST['new_password'] ?? '');
        if ($new_password === '') {
            $_SESSION['flash_err'] = 'Password baru tidak boleh kosong.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = db()->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si', $hashed, $user_id);
            if ($stmt->execute()) {
                $_SESSION['flash_ok'] = 'Password berhasil direset.';
            } else {
                $_SESSION['flash_err'] = 'Gagal reset password: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    redirect(url('admin/user_manage.php'));
}

include __DIR__ . '/../includes/header.php';

// Ambil data user
$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM users WHERE 1=1";
if($search) {
    $sql .= " AND (nama LIKE '%".db()->real_escape_string($search)."%' OR username LIKE '%".db()->real_escape_string($search)."%')";
}
$sql .= " ORDER BY role ASC, nama ASC";
$users = db()->query($sql);
?>

<div class="container-fluid px-4 py-4">
  
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark mb-1">Manajemen Karyawan</h3>
        <p class="text-muted mb-0">Kelola data akun, password, dan profil pengguna.</p>
    </div>
    <div>
        <a href="<?= url('admin/form_aksi.php') ?>" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-2"></i>Tambah User Baru
        </a>
    </div>
  </div>

  <?php if (isset($_SESSION['flash_ok'])): ?>
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['flash_ok']; unset($_SESSION['flash_ok']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
  <?php endif; ?>
  <?php if (isset($_SESSION['flash_err'])): ?>
      <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="fw-bold text-dark m-0"><i class="bi bi-people me-2 text-primary"></i>Daftar Pengguna</h6>
        
        <form method="get" class="d-flex m-0">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="q" value="<?= e($search) ?>" class="form-control border-start-0 ps-0" placeholder="Cari nama / username...">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
          <thead class="bg-light text-secondary">
            <tr>
              <th class="ps-4">User</th>
              <th>Username</th>
              <th>Divisi</th>
              <th>Role</th>
              <th>Status</th>
              <th class="text-end pe-4">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($users && $users->num_rows): ?>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <?php
                                $avSrc = "https://api.dicebear.com/8.x/initials/svg?seed=".urlencode($u['nama']);
                                if($u['foto'] && file_exists(ROOT.'/'.$u['foto'])) $avSrc = url($u['foto']);
                            ?>
                            <img src="<?= $avSrc ?>" class="rounded-circle border" width="36" height="36" style="object-fit:cover">
                            <div>
                                <div class="fw-bold text-dark"><?= e($u['nama']) ?></div>
                                <div class="small text-muted"><?= e($u['nim'] ?: '-') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="font-monospace text-muted">@<?= e($u['username']) ?></span></td>
                    <td><?= e($u['devisi'] ?: '-') ?></td>
                    <td>
                        <?php if($u['role']==='admin'): ?>
                            <span class="badge badge-soft-primary"><i class="bi bi-shield-lock me-1"></i>Admin</span>
                        <?php else: ?>
                            <span class="badge badge-soft-secondary">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($u['aktif']): ?>
                            <span class="badge badge-soft-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-soft-danger">Non-Aktif</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary jsEditUser"
                                data-id="<?= $u['id'] ?>"
                                data-username="<?= e($u['username']) ?>"
                                data-nama="<?= e($u['nama']) ?>"
                                data-devisi="<?= e($u['devisi']) ?>"
                                data-role="<?= e($u['role']) ?>"
                                data-aktif="<?= $u['aktif'] ?>"
                                data-nim="<?= e($u['nim']) ?>"
                                data-jurusan="<?= e($u['jurusan']) ?>"
                                data-asal_sekolah="<?= e($u['asal_sekolah']) ?>"
                                data-tanggal_lahir="<?= e($u['tanggal_lahir']) ?>"
                                data-no_hp="<?= e($u['no_hp']) ?>"
                                data-no_hp_orangtua="<?= e($u['no_hp_orangtua']) ?>"
                                data-foto="<?= $u['foto'] ? url($u['foto']) : '' ?>" 
                                title="Edit Data">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-warning jsResetPassword" 
                                data-id="<?= $u['id'] ?>" title="Reset Password">
                                <i class="bi bi-key"></i>
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-danger jsDeleteUser" 
                                data-id="<?= $u['id'] ?>" title="Hapus User">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                        Data user tidak ditemukan.
                    </td>
                </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post" action="<?= url('admin/user_manage.php') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="user_id" id="editUserId">
        
        <div class="modal-header border-bottom bg-light">
          <h5 class="modal-title fw-bold">Edit Data Karyawan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        
        <div class="modal-body p-4">
          <div class="row g-3">
            
            <div class="col-12 text-center mb-3">
                <img id="previewEditFoto" src="" class="rounded-circle border mb-2" width="100" height="100" style="object-fit:cover; background:#f8f9fa;">
                <br>
                <label class="btn btn-sm btn-outline-primary mt-2">
                    <i class="bi bi-camera me-1"></i> Ganti Foto
                    <input type="file" name="foto" class="d-none" onchange="previewFile(this)">
                </label>
                <div class="small text-muted mt-1">Format: JPG/PNG, Max 5MB</div>
            </div>

            <div class="col-12"><h6 class="text-primary fw-bold mb-0">Informasi Akun</h6></div>
            
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">Username</label>
                <input type="text" id="editUsername" name="username" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">Nama Lengkap</label>
                <input type="text" id="editNama" name="nama" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">Role</label>
                <select id="editRole" name="role" class="form-select">
                    <option value="user">Karyawan (User)</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">Status Akun</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" id="editAktif" name="aktif" value="1">
                    <label class="form-check-label" for="editAktif">Aktif / Bisa Login</label>
                </div>
            </div>

            <div class="col-12 border-top pt-2 mt-2"><h6 class="text-primary fw-bold mb-0">Detail Profil</h6></div>

            <div class="col-md-4">
                <label class="form-label small text-muted fw-bold">Divisi / Jabatan</label>
                <input type="text" id="editDevisi" name="devisi" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted fw-bold">NIM / NIP</label>
                <input type="text" id="editNIM" name="nim" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted fw-bold">Jurusan</label>
                <input type="text" id="editJurusan" name="jurusan" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">Asal Sekolah / Instansi</label>
                <input type="text" id="editAsalSekolah" name="asal_sekolah" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">Tanggal Lahir</label>
                <input type="date" id="editTanggalLahir" name="tanggal_lahir" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">No. HP (WA)</label>
                <input type="text" id="editNoHP" name="no_hp" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted fw-bold">No. HP Keluarga</label>
                <input type="text" id="editNoHPOrtu" name="no_hp_orangtua" class="form-control">
            </div>
          </div>
        </div>
        
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post" action="<?= url('admin/user_manage.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="user_id" id="resetPasswordUserId">
        
        <div class="modal-header border-bottom">
          <h5 class="modal-title fw-bold">Reset Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <div class="alert alert-warning border-0 small">
                <i class="bi bi-info-circle me-1"></i> Password lama akan ditimpa dengan password baru ini.
            </div>
            <label class="form-label fw-bold">Password Baru</label>
            <input type="text" name="new_password" class="form-control" placeholder="Masukkan password baru..." required>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning px-4 fw-bold text-dark">Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Fungsi Preview Foto saat upload
function previewFile(input) {
    const file = input.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewEditFoto').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. HANDLER EDIT USER
    document.querySelectorAll('.jsEditUser').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = this.dataset;
            // Isi form
            document.getElementById('editUserId').value = d.id;
            document.getElementById('editUsername').value = d.username;
            document.getElementById('editNama').value = d.nama;
            document.getElementById('editRole').value = d.role;
            document.getElementById('editAktif').checked = (d.aktif == 1);
            
            // Preview Foto
            const defaultAvatar = "https://api.dicebear.com/8.x/initials/svg?seed=" + encodeURIComponent(d.nama);
            document.getElementById('previewEditFoto').src = d.foto ? d.foto : defaultAvatar;

            // Detail
            document.getElementById('editDevisi').value = d.devisi;
            document.getElementById('editNIM').value = d.nim;
            document.getElementById('editJurusan').value = d.jurusan;
            document.getElementById('editAsalSekolah').value = d.asal_sekolah;
            document.getElementById('editTanggalLahir').value = d.tanggal_lahir;
            document.getElementById('editNoHP').value = d.no_hp;
            document.getElementById('editNoHPOrtu').value = d.no_hp_orangtua;

            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        });
    });

    // 2. HANDLER RESET PASSWORD
    document.querySelectorAll('.jsResetPassword').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('resetPasswordUserId').value = this.dataset.id;
            // Reset input field di modal
            document.querySelector('#resetPasswordModal input[name="new_password"]').value = '';
            new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
        });
    });

    // 3. HANDLER DELETE USER
    document.querySelectorAll('.jsDeleteUser').forEach(btn => {
        btn.addEventListener('click', function() {
            if(!confirm('Yakin ingin menghapus user ini secara permanen? Data absensi terkait mungkin akan kehilangan referensi.')) return;
            
            // Buat form submit dinamis
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('admin/user_manage.php') ?>';
            
            const i1 = document.createElement('input'); i1.type='hidden'; i1.name='action'; i1.value='delete';
            const i2 = document.createElement('input'); i2.type='hidden'; i2.name='user_id'; i2.value=this.dataset.id;
            const i3 = document.createElement('input'); i3.type='hidden'; i3.name='_token'; i3.value='<?= csrf_token() ?>';
            
            form.appendChild(i1); form.appendChild(i2); form.appendChild(i3);
            document.body.appendChild(form);
            form.submit();
        });
    });
});
</script>