<?php
// Snippet: form absen masuk/pulang + detail tugas singkat
// Gunakan: include __DIR__ . '/snippets/absen_farm_snippets.php';

require_once __DIR__ . '/../includes/db.php'; // Pastikan koneksi DB
require_once __DIR__ . '/../user/config.php';
require_once __DIR__ . '/../user/helpers.php'; // Untuk csrf_field() dan url()

// Ambil status dari POST jika ada, atau default ke 'masuk'
$form_status = $_POST['status'] ?? 'masuk';
?>
<form action="<?= url('user/proses_absensi.php'); ?>" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?> <!-- Tambahkan CSRF token -->
  <div class="card">
    <div class="card-header"><strong>Form Absensi</strong></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6"> <!-- Gunakan col-md-6 untuk responsivitas -->
          <label>Status</label>
          <select name="status" class="form-control" required>
            <option value="masuk" <?= $form_status === 'masuk' ? 'selected' : '' ?>>Masuk</option>
            <option value="pulang" <?= $form_status === 'pulang' ? 'selected' : '' ?>>Pulang</option>
          </select>
        </div>
        <div class="col-md-6"> <!-- Gunakan col-md-6 untuk responsivitas -->
          <label>Shift (opsional)</label>
          <select name="shift_id" class="form-control">
            <option value="">- Auto dari jadwal -</option>
            <?php
            $sh = db()->query("SELECT id, nama_shift FROM shift_master WHERE aktif=1 ORDER BY id");
            while ($r = $sh->fetch_assoc()):
            ?>
              <option value="<?= (int)$r['id']; ?>"><?= htmlspecialchars($r['nama_shift']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>

      <label class="mt-3">Keterangan</label>
      <textarea name="keterangan" class="form-control" rows="2" placeholder="opsional"></textarea>

      <div class="row mt-3">
        <div class="col-md-6">
          <label>Foto (opsional)</label>
          <input type="file" name="foto" accept="image/*" class="form-control">
        </div>
        <div class="col-md-6">
          <label>Lokasi (opsional)</label>
          <input type="text" name="lokasi_text" class="form-control" placeholder="mis. Kantor A">
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-md-4">
          <label>Tugas (dropdown)</label>
          <select name="todo_master[]" class="form-control"> <!-- Ubah name menjadi array -->
            <option value="">- pilih -</option>
            <?php
            $tm = db()->query("SELECT id, nama_tugas FROM tugas_master WHERE aktif=1 ORDER BY nama_tugas");
            while ($r = $tm->fetch_assoc()):
            ?>
              <option value="<?= (int)$r['id']; ?>"><?= htmlspecialchars($r['nama_tugas']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Sub Tugas (opsional)</label>
          <input type="text" name="todo_sub[]" class="form-control" placeholder="isi manual atau kosongkan"> <!-- Ubah name menjadi array -->
        </div>
        <div class="col-md-4">
          <label>Jumlah</label>
          <input type="number" name="todo_jumlah[]" class="form-control" min="0" value="0"> <!-- Ubah name menjadi array -->
        </div>
        <input type="hidden" name="todo_sumber[]" value="dropdown"> <!-- Tambahkan sumber -->
      </div>
      <div class="row mt-2">
        <div class="col-12">
          <button type="button" class="btn btn-sm btn-secondary" id="add_todo_row">Tambah Tugas Lain</button>
        </div>
      </div>
      <div id="todo_container">
        <!-- Dynamic To-Do rows will be added here -->
      </div>


      <label class="mt-3">Catatan Manual (opsional)</label>
      <input type="text" name="manual_judul" class="form-control" placeholder="judul singkat (opsional)">
      <textarea name="manual_detail" class="form-control mt-2" rows="2" placeholder="detail pekerjaan (opsional)"></textarea>

      <input type="hidden" name="lat" id="lat">
      <input type="hidden" name="lng" id="lng">
    </div>
    <div class="card-footer text-end"> <!-- Ubah text-right menjadi text-end -->
      <button class="btn btn-primary">Simpan</button>
    </div>
  </div>
</form>

<script>
// Coba ambil geolokasi (opsional)
if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(function(pos){
    document.getElementById('lat').value = pos.coords.latitude.toFixed(7);
    document.getElementById('lng').value = pos.coords.longitude.toFixed(7);
  });
}

// Fungsi untuk menambah baris tugas dinamis
document.getElementById('add_todo_row').addEventListener('click', function() {
  const todoContainer = document.getElementById('todo_container');
  const newRow = document.createElement('div');
  newRow.classList.add('row', 'mt-3');
  newRow.innerHTML = `
    <div class="col-md-4">
      <label>Tugas (dropdown)</label>
      <select name="todo_master[]" class="form-control">
        <option value="">- pilih -</option>
        <?php
        $tm->data_seek(0); // Reset pointer
        while ($r = $tm->fetch_assoc()):
        ?>
          <option value="<?= (int)$r['id']; ?>"><?= htmlspecialchars($r['nama_tugas']); ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label>Sub Tugas (opsional)</label>
      <input type="text" name="todo_sub[]" class="form-control" placeholder="isi manual atau kosongkan">
    </div>
    <div class="col-md-4">
      <label>Jumlah</label>
      <input type="number" name="todo_jumlah[]" class="form-control" min="0" value="0">
    </div>
    <input type="hidden" name="todo_sumber[]" value="dropdown">
    <div class="col-12 mt-2 text-end">
      <button type="button" class="btn btn-sm btn-danger remove_todo_row">Hapus</button>
    </div>
  `;
  todoContainer.appendChild(newRow);

  // Add event listener for remove button
  newRow.querySelector('.remove_todo_row').addEventListener('click', function() {
    newRow.remove();
  });
});
</script>
