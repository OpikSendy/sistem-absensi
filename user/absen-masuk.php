<?php
// user/absen-masuk.php (versi diperbaiki: support 'lainnya' = judul + detail)
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_logged_in()) {
    redirect(url('user/login.php'));
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
if (!$userId) {
    redirect(url('user/login.php'));
}

$conn = db();
date_default_timezone_set('Asia/Jakarta');

// === PENGECEKAN 1: Apakah sudah absen masuk hari ini? ===
$stmt_check = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = CURDATE() AND status = 'masuk' LIMIT 1");
if ($stmt_check) {
    $stmt_check->bind_param('i', $userId);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check && $res_check->num_rows > 0) {
        $_SESSION['flash_info'] = 'Anda sudah melakukan absen masuk hari ini.';
        redirect(url('user/absensi.php'));
    }
    $stmt_check->close();
}

// === PENGECEKAN 2: Apakah jadwal hari ini LIBUR (OFF)? ===
$stmt_jadwal = $conn->prepare("SELECT status FROM user_jadwal WHERE user_id = ? AND tanggal = CURDATE() LIMIT 1");
if ($stmt_jadwal) {
    $stmt_jadwal->bind_param('i', $userId);
    $stmt_jadwal->execute();
    $res_jadwal = $stmt_jadwal->get_result()->fetch_assoc();
    $stmt_jadwal->close();
    if ($res_jadwal && strtoupper($res_jadwal['status']) === 'OFF') {
        $_SESSION['flash_err'] = 'Jadwal Anda hari ini adalah libur (OFF). Anda tidak dapat melakukan absensi.';
        redirect(url('user/absensi.php'));
    }
}

// Load master & sub master (hanya yang aktif)
$masters = $conn->query("SELECT id, nama_tugas FROM tugas_master WHERE aktif=1 ORDER BY nama_tugas")->fetch_all(MYSQLI_ASSOC);
$subMap = [];
$resSub = $conn->query("SELECT master_id, nama_sub FROM tugas_sub_master WHERE aktif=1 ORDER BY nama_sub");
while ($r = $resSub->fetch_assoc()) {
    $mid = (string)$r['master_id'];
    if (!isset($subMap[$mid])) $subMap[$mid] = [];
    $subMap[$mid][] = $r['nama_sub'];
}

$page_title = "Absen Masuk";
$active_menu = 'absensi';
include __DIR__ . '/../includes/header.php';
?>
<main class="container-fluid py-3">
  <div class="row g-3 justify-content-center">
    <div class="col-12 col-xl-10">
      <form id="formAbsen" method="post" action="<?= url('user/proses_absensi.php') ?>" enctype="multipart/form-data" class="vstack gap-4" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="masuk">

        <div class="card rounded-2xl shadow-soft">
          <div class="card-body">
            <h5 class="card-title mb-3">Absen Masuk</h5>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">📸 Foto Kehadiran <span class="text-danger">*</span></label>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-light" id="btnOpenCam">Ambil Foto</button>
                  <button type="button" class="btn btn-outline-danger d-none" id="btnRetake">Ulangi</button>
                </div>
                <input type="file" id="foto" name="foto" accept="image/*" capture="environment" class="d-none" required>
                <div class="preview-box mt-2" id="previewBox" style="display:none;"><img id="previewImg" alt="Preview Foto" class="img-fluid rounded"></div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">📍 Lokasi <span class="text-danger">*</span></label>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-light" id="btnGeo">Ambil Lokasi</button>
                  <input type="text" id="lokasi_text" name="lokasi_text" class="form-control" placeholder="Alamat/koordinat akan terisi otomatis" readonly required>
                </div>
                <input type="hidden" id="lat" name="lat">
                <input type="hidden" id="lng" name="lng">
                <div id="mapsLink" style="display:none;" class="mt-2"><a target="_blank" id="gmapsA" class="link-light">🔗 Lihat di Google Maps</a></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl shadow-soft">
          <div class="card-body">
            <h5 class="card-title mb-3">Rencana To-Do List Hari Ini</h5>
            <p class="text-secondary small">Isi rencana pekerjaan yang akan Anda lakukan hari ini. Ini akan menjadi acuan saat absen pulang.</p>
            <div id="todo-area" class="vstack gap-2"></div>
            <button type="button" class="btn btn-outline-light w-100 mt-2" id="btnAddTodo" data-name-prefix="todo_">+ Tambah Rencana Tugas</button>
          </div>
        </div>

        <div class="col-12 col-xl-10 text-end">
            <button type="submit" class="btn btn-accent btn-lg px-5">✅ Konfirmasi Masuk</button>
        </div>
      </form>
    </div>
  </div>
</main>

<template id="todo-row-tpl">
  <div class="row g-2 align-items-start mb-2 todo-row">
    <div class="col-12 col-md-3 master-col">
      <select name="todo_master[]" class="form-select master-select">
        <option value="">— Pilih Tugas —</option>
        <?php foreach ($masters as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= e($m['nama_tugas']) ?></option>
        <?php endforeach; ?>
        <option value="lainnya">Lainnya…</option>
      </select>
    </div>
    <div class="col-12 col-md-4 sub-col"></div>
    <div class="col-12 col-md-3 manual-input-col d-none">
      <input type="text" name="todo_manual_judul[]" class="form-control manual-judul" placeholder="Judul tugas manual.">
      <!-- TAMBAHAN: textarea detail agar sinkron dengan absen-pulang/admin -->
      <textarea name="todo_manual_detail[]" class="form-control mt-1 manual-detail" rows="2" placeholder="Detail/uraian (opsional)"></textarea>
    </div>
    <div class="col-6 col-md-1">
      <input type="number" name="todo_jumlah[]" class="form-control qty-input" min="1" value="1">
    </div>
    <div class="col-6 col-md-1 text-end">
      <button type="button" class="btn btn-outline-danger w-100 btn-del">−</button>
    </div>
    <input type="hidden" name="todo_sumber[]" class="sumber-input" value="dropdown">
  </div>
</template>

<!-- Modal (success) -->
<div class="modal fade" id="modalSuccess" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Status</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalMsg"></div>
      <div class="modal-footer">
        <a id="btnModalRedirect" class="btn btn-accent" style="display:none">Ke Halaman</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
window.SUB_OPTIONS = <?= json_encode($subMap, JSON_UNESCAPED_UNICODE) ?>;

(function(){
  const form = document.getElementById('formAbsen');
  const tpl = document.getElementById('todo-row-tpl');
  const todoArea = document.getElementById('todo-area');
  const btnAddTodo = document.getElementById('btnAddTodo');

  const fileInput = document.getElementById('foto');
  const btnGeo = document.getElementById('btnGeo');
  const lokasiText = document.getElementById('lokasi_text');
  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const mapsLink = document.getElementById('mapsLink');
  const gmapsA = document.getElementById('gmapsA');

  // Element Modal
  const modalEl = document.getElementById('modalSuccess');
  const modalSuccess = new bootstrap.Modal(modalEl);
  const modalTitle = modalEl.querySelector('.modal-title'); // Ambil judul modal
  const modalMsg = document.getElementById('modalMsg');
  const btnModalRedirect = document.getElementById('btnModalRedirect');

  let __submitting = false;

  function safeOn(el, ev, fn){ if(!el) return; el.addEventListener(ev, fn); }

  // Geolocation
  safeOn(btnGeo, 'click', function(){
    if (!navigator.geolocation) return showError('Geolocation tidak didukung.');
    const orig = btnGeo.innerHTML;
    btnGeo.disabled = true; btnGeo.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengambil...';
    navigator.geolocation.getCurrentPosition(function(pos){
      const lat = pos.coords.latitude; const lng = pos.coords.longitude;
      if(latInput) latInput.value = lat; if(lngInput) lngInput.value = lng;
      if(lokasiText) lokasiText.value = lat.toFixed(6) + ', ' + lng.toFixed(6);
      if(gmapsA) gmapsA.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(lat + ',' + lng);
      if(mapsLink) mapsLink.style.display = 'block';
      btnGeo.disabled = false; btnGeo.innerHTML = 'Lokasi Terkunci ✅';
      btnGeo.classList.replace('btn-outline-light', 'btn-success');
    }, function(err){
      showError('Gagal ambil lokasi. Pastikan GPS aktif.');
      btnGeo.disabled = false; btnGeo.innerHTML = orig;
    }, { enableHighAccuracy:true, timeout:15000});
  });

  // Helper Show Error di Modal (Bukan Alert)
  function showError(msg) {
      modalTitle.textContent = '❌ Gagal';
      modalTitle.classList.add('text-danger');
      modalMsg.innerHTML = msg.replace(/\n/g, '<br>');
      btnModalRedirect.style.display = 'none'; // Sembunyikan tombol redirect
      modalSuccess.show();
  }

  // Helper Show Success
  function showSuccess(msg, url) {
      modalTitle.textContent = '✅ Berhasil';
      modalTitle.classList.remove('text-danger');
      modalMsg.innerHTML = msg;
      btnModalRedirect.href = url || '<?= url('user/absensi.php') ?>';
      btnModalRedirect.style.display = 'inline-block';
      modalSuccess.show();
  }

  // ... (Fungsi renderSubSelect, applyRowMode, createTodoRow, collectTodos SAMA SEPERTI SEBELUMNYA - Silakan copy) ...
  // Agar kode tidak terlalu panjang di sini, pastikan fungsi Todo List tetap ada.
  // Gunakan fungsi createTodoRow dan collectTodos dari kode sebelumnya.
  function renderSubSelect(masterId, nameAttr){
    const sel = document.createElement('select'); sel.className = 'form-select sub-select'; sel.name = nameAttr || 'todo_sub[]';
    sel.innerHTML = '<option value="">— Pilih Sub —</option>';
    const opts = (window.SUB_OPTIONS && window.SUB_OPTIONS[String(masterId)]) || [];
    opts.forEach(s => { const o = document.createElement('option'); o.value = s; o.textContent = s; sel.appendChild(o); });
    return sel;
  }
  function applyRowMode(rowEl, prefix){
    prefix = prefix || 'todo_';
    const masterSel = rowEl.querySelector('.master-select');
    const manualCol = rowEl.querySelector('.manual-input-col');
    const subCol = rowEl.querySelector('.sub-col');
    const sumberInp = rowEl.querySelector('.sumber-input');
    const manualJudul = rowEl.querySelector(`input[name$="manual_judul[]"]`);
    if(manualJudul) manualJudul.required = false; 
    const val = masterSel ? masterSel.value : '';
    subCol.innerHTML = '';
    if (val === 'lainnya') {
      if(manualCol) manualCol.classList.remove('d-none');
      if(sumberInp) sumberInp.value = 'manual';
      if(manualJudul) manualJudul.required = true; 
    } else if (val && window.SUB_OPTIONS && (window.SUB_OPTIONS[String(val)] || []).length) {
      if(manualCol) manualCol.classList.add('d-none');
      subCol.appendChild(renderSubSelect(val, `${prefix}sub[]`));
      if(sumberInp) sumberInp.value = 'dropdown';
    } else {
      if(manualCol) manualCol.classList.add('d-none');
      if(sumberInp) sumberInp.value = 'dropdown';
    }
  }
  function createTodoRow(tplEl, namePrefix){
    namePrefix = namePrefix || 'todo_';
    const node = tplEl.content.firstElementChild.cloneNode(true);
    node.querySelectorAll('[name^="todo_"]').forEach(el=>{
      const old = el.getAttribute('name');
      el.setAttribute('name', old.replace(/^todo_/, namePrefix));
    });
    const masterSel = node.querySelector('.master-select');
    const delBtn = node.querySelector('.btn-del');
    if(masterSel) safeOn(masterSel, 'change', ()=> applyRowMode(node, namePrefix));
    if(delBtn) safeOn(delBtn, 'click', ()=> node.remove());
    applyRowMode(node, namePrefix);
    return node;
  }
  if (btnAddTodo && tpl && todoArea) {
    safeOn(btnAddTodo, 'click', ()=> todoArea.appendChild(createTodoRow(tpl, 'todo_')));
    if(todoArea.children.length === 0) todoArea.appendChild(createTodoRow(tpl, 'todo_'));
  }
  function collectTodos(){
    const rows = document.querySelectorAll('#todo-area .todo-row');
    const todoDropdown = []; const todoManual = [];
    rows.forEach(row=>{
      const sumber = row.querySelector('.sumber-input')?.value || 'dropdown';
      const qty = parseInt((row.querySelector('.qty-input')?.value)||0,10) || 0;
      if (sumber === 'manual') {
        const judul = (row.querySelector('input[name$="manual_judul[]"]')?.value||'').trim();
        const detail = (row.querySelector('textarea[name$="manual_detail[]"]')?.value||'').trim();
        if (judul) todoManual.push(JSON.stringify({judul, detail, jumlah: qty}));
      } else {
        const masterVal = row.querySelector('.master-select')?.value || '';
        if (masterVal && masterVal !== 'lainnya') {
          const subVal = row.querySelector('.sub-select')?.value || '';
          todoDropdown.push(JSON.stringify({master_id: masterVal, sub_nama: subVal, jumlah: qty}));
        } else if (masterVal === 'lainnya') {
          const judul = (row.querySelector('input[name$="manual_judul[]"]')?.value||'').trim();
          const detail = (row.querySelector('textarea[name$="manual_detail[]"]')?.value||'').trim();
          if (judul) todoManual.push(JSON.stringify({judul, detail, jumlah: qty}));
        }
      }
    });
    return { todoDropdown, todoManual };
  }

  // --- SUBMIT HANDLER ---
  if (form) {
    form.addEventListener('submit', async function(e){
      e.preventDefault(); 
      
      // Validasi
      if(fileInput && (!fileInput.files || !fileInput.files.length)){
        return showError('⚠️ Foto Wajib Diisi!\nSilakan klik tombol "Ambil Foto".');
      }
      if(lokasiText && !lokasiText.value.trim()){
        return showError('⚠️ Lokasi Wajib Diisi!\nSilakan klik tombol "Ambil Lokasi".');
      }

      if(__submitting) return;
      
      // Kirim Data
      const fd = new FormData(form);
      const { todoDropdown, todoManual } = collectTodos();
      
      // Hapus data form todo asli agar tidak duplikat
      document.querySelectorAll('#todo-area select, #todo-area input, #todo-area textarea').forEach(el => fd.delete(el.name));
      
      todoDropdown.forEach(x => fd.append('todo_dropdown[]', x));
      todoManual.forEach(x => fd.append('todo_manual[]', x));

      const submitBtn = form.querySelector('button[type="submit"]');
      const origLabel = submitBtn.innerHTML;
      submitBtn.disabled = true; 
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengirim...';
      __submitting = true;

      try {
        const res = await fetch(form.getAttribute('action'), {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        // Coba parsing JSON
        let json;
        const txt = await res.text();
        try { 
            json = JSON.parse(txt); 
        } catch(e) {
            // Jika bukan JSON (misal error PHP fatal), tampilkan di modal error
            console.error("Server Error:", txt);
            throw new Error("Terjadi kesalahan server. Cek console untuk detail.");
        }

        if (json.ok) {
          showSuccess(json.msg, json.redirect_url);
        } else {
          // DISINI KUNCINYA: Tampilkan pesan error JSON di Modal
          showError(json.msg || 'Gagal menyimpan data.');
        }

      } catch (err) {
        showError('❌ Error: ' + err.message);
      } finally {
        submitBtn.disabled = false; 
        submitBtn.innerHTML = origLabel;
        __submitting = false;
      }
    });
  }

})();
</script>