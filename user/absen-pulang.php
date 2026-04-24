<?php
// user/absen-pulang.php (gabungan & dioptimalkan)
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

// Cek: Sudah absen pulang?
$stmt_check_pulang = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = CURDATE() AND status = 'pulang' LIMIT 1");
if ($stmt_check_pulang) {
    $stmt_check_pulang->bind_param('i', $userId);
    $stmt_check_pulang->execute();
    $res_check_pulang = $stmt_check_pulang->get_result();
    if ($res_check_pulang && $res_check_pulang->num_rows > 0) {
        $_SESSION['flash_info'] = 'Anda sudah melakukan absen pulang hari ini.';
        redirect(url('user/absensi.php'));
    }
    $stmt_check_pulang->close();
}

// Cek: Sudah absen masuk?
// ambil id absen masuk hari ini
$stmt_masuk = $conn->prepare("SELECT id, waktu FROM absensi WHERE user_id=? AND tanggal=CURDATE() AND status='masuk' ORDER BY waktu ASC LIMIT 1");
if ($stmt_masuk) {
    $stmt_masuk->bind_param('i', $userId);
    $stmt_masuk->execute();
    $absMasuk = $stmt_masuk->get_result()->fetch_assoc();
    $stmt_masuk->close();
} else {
    $absMasuk = null;
}

if (!$absMasuk) {
    $_SESSION['flash_err'] = 'Anda harus absen masuk terlebih dahulu sebelum absen pulang.';
    redirect(url('user/absensi.php'));
}

$masukId = (int)($absMasuk['id'] ?? 0);

// Load todos (prioritaskan sub_lookup jika ada)
// --- ambil todos untuk absen masuk (untuk ditampilkan saat pulang) ---
$todos = [];
if ($masukId > 0) {
    $stmt_t = $conn->prepare("
        SELECT 
            t.id,
            t.absensi_id,
            t.sumber,
            t.master_id,
            COALESCE(tm.nama_tugas, '') AS master_nama,
            t.sub_nama,
            t.manual_judul,
            t.manual_detail,
            t.jumlah,
            t.is_done
        FROM absensi_todo t
        LEFT JOIN tugas_master tm ON tm.id = t.master_id
        WHERE t.absensi_id = ?
        ORDER BY t.id ASC
    ");
    if ($stmt_t) {
        $stmt_t->bind_param('i', $masukId);
        $stmt_t->execute();
        $todos = $stmt_t->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_t->close();
    }
}




// Load masters & subMap
$masters = $conn->query("SELECT id, nama_tugas FROM tugas_master WHERE aktif=1 ORDER BY nama_tugas")->fetch_all(MYSQLI_ASSOC);
$subMap = [];
$resSub = $conn->query("SELECT master_id, nama_sub FROM tugas_sub_master WHERE aktif=1 ORDER BY nama_sub");
while ($r = $resSub->fetch_assoc()) {
    $mid = (string)$r['master_id'];
    if (!isset($subMap[$mid])) $subMap[$mid] = [];
    $subMap[$mid][] = $r['nama_sub'];
}

$page_title = "Absen Pulang";
$active_menu = 'absensi';
include __DIR__ . '/../includes/header.php';
?>
<main class="container-fluid py-3">
  <form id="formAbsen" method="post" action="<?= url('user/proses_absensi.php') ?>" enctype="multipart/form-data" class="vstack gap-3" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="pulang">

    <div class="row g-3 justify-content-center">
      <div class="col-12 col-xl-6">
        <div class="card rounded-2xl shadow-soft">
          <div class="card-body vstack gap-4">
            <h5 class="card-title">Absen Pulang</h5>

            <div>
              <label class="form-label">📸 Foto Pulang <span class="text-danger">*</span></label>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-light" id="btnOpenCam">Ambil Foto</button>
                <button type="button" class="btn btn-outline-danger d-none" id="btnRetake">Ulangi</button>
              </div>
              <input type="file" id="foto" name="foto" accept="image/*" capture="environment" class="d-none" required>
              <div class="preview-box mt-2" id="previewBox" style="display:none;"><img id="previewImg" alt="Preview Foto" class="img-fluid rounded"></div>
            </div>

            <div>
              <label class="form-label">📍 Lokasi <span class="text-danger">*</span></label>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-light" id="btnGeo">Ambil Lokasi</button>
                <input type="text" id="lokasi_text" name="lokasi_text" class="form-control" placeholder="Alamat/koordinat akan terisi otomatis" readonly required>
              </div>
              <input type="hidden" id="lat" name="lat">
              <input type="hidden" id="lng" name="lng">
              <div id="mapsLink" style="display:none;" class="mt-2"><a target="_blank" id="gmapsA" class="link-light">🔗 Lihat di Google Maps</a></div>
            </div>

            <div>
              <label class="form-label">📝 Kendala Hari Ini (Opsional)</label>
              <textarea name="kendala_hari_ini" class="form-control" rows="2" placeholder="Tuliskan kendala atau catatan penting jika ada"></textarea>
            </div>

          </div>
        </div>
      </div>

      <div class="col-12 col-xl-6">
        <div class="card rounded-2xl shadow-soft mb-3">
          <div class="card-body">
            <h5 class="card-title">Realisasi Tugas Harian</h5>
            <p class="text-secondary small">Centang tugas yang sudah Anda selesaikan dari rencana pagi.</p>
            <!-- Ganti loop lama dengan potongan ini di user/absen-pulang.php -->
<?php if (empty($todos)): ?>
  <p class="text-secondary">Tidak ada rencana tugas yang tercatat saat absen masuk.</p>
<?php else: ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($todos as $t): 
      $tid = (int)($t['id'] ?? 0);
      $isDone = (bool)($t['is_done'] ?? false);
      $judul = $t['sumber'] === 'manual' ? ($t['manual_judul'] ?? '') : ($t['master_nama'] ?? '');
      $sub = $t['sumber'] === 'manual' ? ($t['manual_detail'] ?? '') : ($t['sub_nama'] ?? '');
    ?>
      <li class="list-group-item bg-dark text-light d-flex align-items-start gap-3">
        <div class="form-check" style="min-width:28px;">
          <input class="form-check-input mt-1" type="checkbox" 
                 name="done_ids[]" id="todo-done-<?= $tid ?>" value="<?= $tid ?>"
                 <?= $isDone ? 'checked' : '' ?>>
        </div>

        <div class="flex-fill">
          <label for="todo-done-<?= $tid ?>" class="mb-0" style="cursor:pointer;">
            <strong class="me-2"><?= e($judul) ?></strong>
            <?php if ((int)($t['jumlah'] ?? 0) > 1): ?> <small class="text-secondary">(x<?= (int)($t['jumlah']) ?>)</small><?php endif; ?>
          </label>
          <?php if (!empty($sub)): ?>
            <div class="small text-secondary mt-1"><?= e($sub) ?></div>
          <?php endif; ?>
        </div>

        <?php if ($isDone): ?>
          <div class="text-success ms-2" title="Sudah ditandai selesai">
            <i class="bi bi-check-circle-fill"></i>
          </div>
        <?php endif; ?>

      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>


          </div>
        </div>

        <div class="card rounded-2xl shadow-soft">
          <div class="card-body">
            <h5 class="card-title">Pekerjaan Tambahan</h5>
            <p class="text-secondary small">Jika ada pekerjaan di luar rencana, tambahkan di sini.</p>
            <div id="todo-area" class="vstack gap-2"></div>
            <button type="button" class="btn btn-outline-light w-100 mt-2" id="btnAddTodo" data-name-prefix="extra_">+ Tambah Pekerjaan</button>
          </div>
        </div>
      </div>

      <div class="col-12 text-center mt-3">
        <button type="submit" class="btn btn-accent btn-lg px-5" id="submitBtn">✅ Konfirmasi Pulang</button>
      </div>
    </div>
  </form>
</main>

<!-- template -->
<template id="todo-row-tpl">
  <div class="row g-2 align-items-start mb-2 todo-row">
    <div class="col-12 col-md-3 master-col">
      <select name="extra_master[]" class="form-select master-select">
        <option value="">— Pilih Tugas —</option>
        <?php foreach ($masters as $m): ?>
          <option value="<?= (int)$m['id'] ?>"><?= e($m['nama_tugas']) ?></option>
        <?php endforeach; ?>
        <option value="lainnya">Lainnya…</option>
      </select>
    </div>
    <div class="col-12 col-md-4 sub-col"></div>
    <div class="col-12 col-md-3 manual-input-col d-none">
      <input type="text" name="extra_manual_judul[]" class="form-control" placeholder="Judul tugas manual...">
      <textarea name="extra_manual_detail[]" class="form-control mt-1" rows="2" placeholder="Detail/uraian (opsional)"></textarea>
    </div>
    <div class="col-6 col-md-1">
      <input type="number" name="extra_jumlah[]" class="form-control qty-input" min="1" value="1">
    </div>
    <div class="col-6 col-md-1 text-end">
      <button type="button" class="btn btn-outline-danger w-100 btn-del">−</button>
    </div>
    <input type="hidden" name="extra_sumber[]" class="sumber-input" value="dropdown">
  </div>
</template>

<!-- modal sukses -->
<div class="modal fade" id="modalSuccess" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">Berhasil ✅</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalMsg">Absen tersimpan.</div>
      <div class="modal-footer">
        <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button> -->
        <a class="btn btn-accent" id="btnModalRedirect" href="<?= url('user/absensi.php') ?>">Ke Dashboard</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
/* Data dari PHP untuk JS */
window.SUB_OPTIONS = <?= json_encode($subMap, JSON_UNESCAPED_UNICODE) ?>;
// Pastikan array ini tidak null
const ORIGINAL_TODOS = <?= !empty($todos) ? json_encode($todos, JSON_UNESCAPED_UNICODE) : '[]' ?>;

document.addEventListener('DOMContentLoaded', function(){
  
  /* --- 1. DEFINISI VARIABEL --- */
  const form = document.getElementById('formAbsen');
  const tpl = document.getElementById('todo-row-tpl');
  const todoArea = document.getElementById('todo-area');
  const btnAdd = document.getElementById('btnAddTodo');
  
  const fotoInput = document.getElementById('foto');
  const lokasiText = document.getElementById('lokasi_text');
  const btnGeo = document.getElementById('btnGeo');
  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  const mapsLink = document.getElementById('mapsLink');
  const gmapsA = document.getElementById('gmapsA');
  
  const submitBtn = document.getElementById('submitBtn');
  const modalSuccess = new bootstrap.Modal(document.getElementById('modalSuccess'));
  const modalMsg = document.getElementById('modalMsg');
  const btnModalRedirect = document.getElementById('btnModalRedirect');
  
  // Status flag
  let isSubmitting = false;

  function safeOn(el, ev, fn){ if(el) el.addEventListener(ev, fn); }

  /* --- 2. GEOLOCATION --- */
  safeOn(btnGeo, 'click', function(){
    if (!navigator.geolocation) return alert('Geolocation tidak didukung.');
    
    const origText = btnGeo.innerHTML;
    btnGeo.disabled = true; 
    btnGeo.innerHTML = '<span class="spinner-border spinner-border-sm"></span>...';
    
    navigator.geolocation.getCurrentPosition(function(pos){
      const lat = pos.coords.latitude; 
      const lng = pos.coords.longitude;
      
      latInput.value = lat; 
      lngInput.value = lng;
      lokasiText.value = lat.toFixed(6) + ', ' + lng.toFixed(6);
      
      gmapsA.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(lat + ',' + lng);
      mapsLink.style.display = 'block';
      
      btnGeo.disabled = false; 
      btnGeo.innerHTML = 'Lokasi Oke ✅';
      btnGeo.classList.remove('btn-outline-light');
      btnGeo.classList.add('btn-success');
      
    }, function(err){
      alert('Gagal ambil lokasi: ' + err.message);
      btnGeo.disabled = false; 
      btnGeo.innerHTML = origText;
    }, { enableHighAccuracy:true, timeout:15000 });
  });

  /* --- 3. LOGIKA TAMBAH TUGAS (EXTRA) --- */
  if(btnAdd) {
    safeOn(btnAdd, 'click', function() {
       // Panggil fungsi global dari main.js (jika ada)
       if(typeof window.createTodoRow === 'function') {
         const newRow = window.createTodoRow(tpl, 'extra_');
         todoArea.appendChild(newRow);
       } else {
         alert('Script main.js belum dimuat sempurna. Coba refresh.');
       }
    });
  }

  /* --- 4. SUBMIT HANDLER (DENGAN TRY-CATCH PENUH) --- */
  if (form) {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();

      // --- VALIDASI AWAL ---
      if (fotoInput && fotoInput.required && (!fotoInput.files || !fotoInput.files.length)) {
        alert('⚠️ Foto Pulang Wajib Diisi!');
        fotoInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      if (lokasiText && lokasiText.required && !lokasiText.value.trim()) {
        alert('⚠️ Lokasi Wajib Diambil!');
        btnGeo.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      if (isSubmitting) return;
      if(!confirm('Konfirmasi: Yakin ingin kirim absen pulang?')) return;

      // Kunci Tombol
      isSubmitting = true;
      const originalBtnHtml = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

      // --- MULAI PROSES DATA (DALAM TRY CATCH) ---
      try {
          const fd = new FormData(form);

          // 1. KOLEKSI DATA EXTRA (TUGAS TAMBAHAN)
          const extraData = [];
          document.querySelectorAll('#todo-area .todo-row').forEach(row => {
            const masterSel = row.querySelector('.master-select');
            const subSel = row.querySelector('.sub-select');
            
            // Ambil input manual/detail dengan selector aman
            const manualJudulInp = row.querySelector('input[name*="manual_judul"]');
            const manualDetailInp = row.querySelector('textarea[name*="manual_detail"]');
            const qtyInp = row.querySelector('.qty-input');

            const manualJudul = manualJudulInp ? manualJudulInp.value.trim() : '';
            const manualDetail = manualDetailInp ? manualDetailInp.value.trim() : '';
            const qty = qtyInp ? (parseInt(qtyInp.value, 10) || 1) : 1;
            
            const masterVal = masterSel ? masterSel.value : '';

            // Logic mapping
            if (masterVal === 'lainnya') {
              if (manualJudul) {
                extraData.push(JSON.stringify({ nama_tugas: manualJudul, detail: manualDetail, jumlah: qty, sumber: 'manual' }));
              }
            } else if (masterVal) {
              const namaMaster = masterSel.options[masterSel.selectedIndex]?.text || masterVal;
              const subNama = subSel ? subSel.value : '';
              extraData.push(JSON.stringify({ nama_tugas: namaMaster, sub_tugas: subNama, jumlah: qty, sumber: 'dropdown' }));
            } else if (manualJudul) {
              // Fallback manual
              extraData.push(JSON.stringify({ nama_tugas: manualJudul, detail: manualDetail, jumlah: qty, sumber: 'manual' }));
            }
          });

          // 2. KOLEKSI DATA CHECKBOX (DONE IDS)
          const hasilTodo = [];
          document.querySelectorAll('input[name="done_ids[]"]').forEach(cb => {
            const id = parseInt(cb.value, 10);
            const checked = cb.checked ? 1 : 0;
            
            // Cari data asli di JSON ORIGINAL_TODOS
            const orig = ORIGINAL_TODOS.find(t => String(t.id) === String(id));
            if (orig) {
              hasilTodo.push(JSON.stringify({
                id: id,
                nama_tugas: orig.sumber === 'manual' ? orig.manual_judul : (orig.master_nama || orig.nama_tugas),
                sub_tugas: orig.sumber === 'manual' ? orig.manual_detail : (orig.sub_nama || orig.sub_lookup || orig.manual_judul || ''),
                jumlah: orig.jumlah,
                sumber: orig.sumber,
                is_done: checked
              }));
            }
          });

          // 3. BERSIHKAN & SUSUN FORM DATA
          // Hapus input duplikat dari DOM di dalam FormData
          // (Kita hapus dari object fd, bukan dari HTML)
          for (var pair of fd.entries()) {
              if(pair[0].includes('extra_') || pair[0].includes('done_ids')) {
                  fd.delete(pair[0]);
              }
          }
          // Hapus manual spesifik agar tidak dobel
          document.querySelectorAll('#todo-area input, #todo-area select, #todo-area textarea').forEach(el => {
              if(el.name) fd.delete(el.name);
          });
          
          // Masukkan JSON yang sudah rapi
          extraData.forEach(d => fd.append('extra_data[]', d));
          hasilTodo.forEach(d => fd.append('hasil_todo[]', d));

          // 4. KIRIM AJAX
          const res = await fetch(form.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          const txt = await res.text();
          let json = null;
          
          try { 
            json = JSON.parse(txt); 
          } catch (e) {
            console.error("Non-JSON response:", txt);
            throw new Error("Respon server tidak valid (Bukan JSON). Cek Console.");
          }

          if (json && json.ok) {
            // SUKSES
            modalMsg.innerHTML = json.modal?.html || (json.msg || 'Absen pulang berhasil.');
            btnModalRedirect.href = json.redirect_url || '<?= url('user/absensi.php') ?>';
            btnModalRedirect.style.display = 'inline-block';
            modalSuccess.show();
          } else {
            // GAGAL LOGIC SERVER
            throw new Error(json.msg || 'Terjadi kesalahan tidak diketahui.');
          }

      } catch (err) {
          console.error('Submit Error:', err);
          alert('❌ Gagal: ' + err.message);
      } finally {
          // 5. RESET TOMBOL (WAJIB JALAN APAPUN YANG TERJADI)
          isSubmitting = false;
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnHtml;
      }
    });
  }
});
</script>