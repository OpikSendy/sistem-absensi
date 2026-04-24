<?php
// user/profile.php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in()) {
    redirect(url('user/login.php'));
}

// Ambil target user id (admin boleh lihat user lain)
$profile_user_id = (int)($_GET['id'] ?? 0);
if ($profile_user_id === 0) {
    $profile_user_id = (int)($_SESSION['user']['id'] ?? 0);
    if ($profile_user_id === 0) redirect(url('user/login.php'));
}

$conn = db();

// Ambil data user (tambahkan kolom foto)
$stmt_user = $conn->prepare("
    SELECT id, username, nama, devisi, role,
           nim, jurusan, asal_sekolah, tanggal_lahir, no_hp, no_hp_orangtua, foto
    FROM users WHERE id = ? LIMIT 1
");
if ($stmt_user === false) {
    redirect(url('user/403.php'));
}
$stmt_user->bind_param('i', $profile_user_id);
$stmt_user->execute();
$profile_user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

if (!$profile_user_data) redirect(url('user/403.php'));

// cek permission: hanya admin boleh lihat user lain
if (!is_admin() && $profile_user_id !== (int)($_SESSION['user']['id'] ?? 0)) {
    redirect(url('user/403.php'));
}

// Page meta
$page_title = "Profil " . ($profile_user_data['nama'] ?? 'User');
$active_menu = 'profile';

// Because you asked to remove the donut/chart section, we don't load Chart.js
$load_chart_js = false;

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <!-- Frame utama profil: border + shadow, responsive -->
      <div class="card border-primary shadow-sm">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row align-items-center gap-3">
            <div class="text-center" style="min-width:140px;">
              <?php
                $fotoSrc = '';
                if (!empty($profile_user_data['foto'])) {
                    // jika disimpan relatif 'uploads/...' -> buat url
                    $fotoSrc = $profile_user_data['foto'];
                }
                if (empty($fotoSrc)) {
                    $fotoSrc = "https://api.dicebear.com/8.x/initials/svg?seed=" . urlencode(substr($profile_user_data['nama'] ?? $profile_user_data['username'] ?? 'U',0,2));
                } else {
                    // buat url jika helper url() ada
                    $fotoSrc = function_exists('url') ? url($fotoSrc) : '/' . ltrim($fotoSrc, '/');
                }
              ?>
              <img id="profileAvatar" src="<?= e($fotoSrc) ?>" alt="avatar" class="rounded-circle mb-2" width="140" height="140" style="object-fit:cover;cursor:pointer;border:4px solid #f1f1f1;" title="Klik untuk ubah foto" />
              <div class="small text-muted">Role: <strong><?= e(ucfirst($profile_user_data['role'] ?: '-')) ?></strong></div>

              <?php if (is_admin() || $profile_user_id === (int)($_SESSION['user']['id'] ?? 0)): ?>
                <div class="mt-2 d-flex gap-2 justify-content-center">
                  <button class="btn btn-sm btn-outline-primary" id="btnEditAvatar">Ubah</button>
                  <button class="btn btn-sm btn-outline-danger" id="btnDeleteAvatar" <?= empty($profile_user_data['foto']) ? 'disabled' : '' ?>>Hapus</button>
                </div>
              <?php endif; ?>
            </div>

            <div class="flex-fill">
              <h4 class="mb-1"><?= e($profile_user_data['nama'] ?? '-') ?></h4>
              <p class="mb-1 text-secondary">@<?= e($profile_user_data['username'] ?? '-') ?></p>

              <div class="row">
                <div class="col-6">
                  <div class="small text-muted">TIM</div>
                  <div><?= e($profile_user_data['devisi'] ?? '-') ?></div>
                </div>
                <div class="col-6">
                  <div class="small text-muted">Role</div>
                  <div><?= e($profile_user_data['role'] ?? '-') ?></div>
                </div>
              </div>

              <hr>

              <div class="row">
                <div class="col-6"><small class="text-muted">NIM / NISN</small><div><?= e($profile_user_data['nim'] ?? '-') ?></div></div>
                <div class="col-6"><small class="text-muted">Jurusan</small><div><?= e($profile_user_data['jurusan'] ?? '-') ?></div></div>
                <div class="col-6 mt-2"><small class="text-muted">Asal Sekolah</small><div><?= e($profile_user_data['asal_sekolah'] ?? '-') ?></div></div>
                <div class="col-6 mt-2"><small class="text-muted">Tanggal Lahir</small><div><?= e($profile_user_data['tanggal_lahir'] ?? '-') ?></div></div>
                <div class="col-6 mt-2"><small class="text-muted">No HP</small><div><?= e($profile_user_data['no_hp'] ?? '-') ?></div></div>
                <div class="col-6 mt-2"><small class="text-muted">No HP Orang Tua</small><div><?= e($profile_user_data['no_hp_orangtua'] ?? '-') ?></div></div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Upload Avatar -->
  <div class="modal fade" id="modalAvatar" tabindex="-1" aria-hidden="true">\
    <!-- Modal Zoom Gambar -->
<div class="modal fade" id="modalZoomAvatar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0 shadow-none">
      <div class="position-relative">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);z-index:10;"></button>
        <img id="zoomedAvatarImg" src="" alt="zoom" class="img-fluid rounded mx-auto d-block" style="max-height:90vh;object-fit:contain;">
      </div>
    </div>
  </div>
</div>

    <div class="modal-dialog modal-dialog-centered">
      <form id="formAvatar" class="modal-content" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Ubah Foto Profil</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3 text-center">
            <img id="previewAvatar" src="<?= e($fotoSrc) ?>" alt="preview" class="rounded-circle" width="120" height="120" style="object-fit:cover;border:1px solid #ddd;">
          </div>
          <div class="mb-3">
            <label class="form-label">Pilih file (jpg/png/webp, max 5MB)</label>
            <input type="file" name="avatar" id="inputAvatar" accept="image/*" class="form-control" required>
          </div>
          <div class="form-text text-muted">Nama file akan disimpan otomatis dengan format tanggal_username_jurusan.</div>
        </div>
        <div class="modal-footer">
          <input type="hidden" name="user_id" value="<?= (int)$profile_user_id ?>">
          <input type="hidden" name="_token" value="<?= csrf_token() ?>">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Upload & Simpan</button>
        </div>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const modalEl = document.getElementById('modalAvatar');
  const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;
  const btnEdit = document.getElementById('btnEditAvatar');
  const btnDelete = document.getElementById('btnDeleteAvatar');
  const avatarImg = document.getElementById('profileAvatar');
  const preview = document.getElementById('previewAvatar');
  const inputFile = document.getElementById('inputAvatar');
  const form = document.getElementById('formAvatar');

  function setPreviewFromFile(f){
    const reader = new FileReader();
    reader.onload = function(e){ preview.src = e.target.result; };
    reader.readAsDataURL(f);
  }

  if (avatarImg) {
    avatarImg.addEventListener('click', function(){ if (btnEdit) btnEdit.click(); });
  }

  if (btnEdit) btnEdit.addEventListener('click', function(){
    if (bsModal) bsModal.show();
  });

  if (inputFile) {
    inputFile.addEventListener('change', function(e){
      const f = e.target.files[0];
      if (f) setPreviewFromFile(f);
    });
  }

  if (form) {
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      const fd = new FormData(form);
      // include CSRF token field already present
      try {
        const res = await fetch('<?= url('user/upload_avatar.php') ?>', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.ok) {
          // update avatar on page
          document.getElementById('profileAvatar').src = json.url + '?t=' + Date.now();
          if (preview) preview.src = json.url + '?t=' + Date.now();
          if (bsModal) bsModal.hide();
          if (btnDelete) btnDelete.disabled = false;
          alert(json.msg || 'Berhasil');
        } else {
          alert(json.msg || 'Gagal upload');
        }
      } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan jaringan.');
      }
    });
  }

  if (btnDelete) {
    btnDelete.addEventListener('click', async function(){
      if (!confirm('Hapus foto profil ini?')) return;
      const fd = new FormData();
      fd.append('user_id', '<?= (int)$profile_user_id ?>');
      fd.append('_token', '<?= csrf_token() ?>');
      try {
        const res = await fetch('<?= url('user/delete_avatar.php') ?>', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.ok) {
          // fallback to initials avatar
          const fallback = "https://api.dicebear.com/8.x/initials/svg?seed=" + encodeURIComponent("<?= e(substr($profile_user_data['nama'] ?? $profile_user_data['username'] ?? 'U',0,2)) ?>");
          document.getElementById('profileAvatar').src = fallback;
          if (preview) preview.src = fallback;
          btnDelete.disabled = true;
          alert(json.msg || 'Terhapus');
        } else {
          alert(json.msg || 'Gagal menghapus');
        }
      } catch (err) {
        console.error(err);
        alert('Kesalahan jaringan.');
      }
    });
  }

});


  // --- Zoom Avatar Preview ---
  const zoomModalEl = document.getElementById('modalZoomAvatar');
  const bsZoomModal = zoomModalEl ? new bootstrap.Modal(zoomModalEl) : null;
  const zoomImg = document.getElementById('zoomedAvatarImg');
  const previewImg = document.getElementById('previewAvatar');

  if (previewImg) {
    previewImg.style.cursor = 'zoom-in';
    previewImg.addEventListener('click', function() {
      if (bsZoomModal && previewImg.src) {
        zoomImg.src = previewImg.src;
        bsZoomModal.show();
      }
    });
  }



</script>
