@extends('layouts.app')
@section('title', 'Absensi | Kesatriyan')
@section('page-title', 'Form Absensi')

@section('styles')
<style>
  .camera-box {
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    aspect-ratio: 4/3;
  }
  #webcam {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  #preview {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: none;
    position: absolute;
    top: 0; left: 0;
  }
  .camera-overlay {
    position: absolute;
    bottom: 15px;
    left: 0; right: 0;
    text-align: center;
    z-index: 10;
  }
  .btn-capture {
    width: 60px; height: 60px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    border: 4px solid #fff;
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-capture:hover { background: rgba(255,255,255,0.8); transform: scale(1.05); }
  .btn-retake { display: none; }
</style>
@endsection

@section('content')

@php
  $ymd = now()->format('Y-m-d');
  $user = auth()->user();
  $masuk = App\Models\Absensi::where('user_id', $user->id)->where('tanggal', $ymd)->where('status', 'masuk')->first();
  $pulang = App\Models\Absensi::where('user_id', $user->id)->where('tanggal', $ymd)->where('status', 'pulang')->first();

  $action = 'masuk';
  if ($masuk && !$pulang) $action = 'pulang';
  if ($masuk && $pulang) $action = 'done';
@endphp

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-6">

    @if($action === 'done')
      <div class="card border-0 shadow-sm text-center p-5">
        <i class="bi bi-check-circle-fill text-success mb-3" style="font-size: 4rem;"></i>
        <h4 class="fw-bold">Absensi Selesai!</h4>
        <p class="text-muted">Anda telah menyelesaikan absensi masuk dan pulang untuk hari ini.</p>
        <a href="{{ route('user.dashboard') }}" class="btn btn-primary mt-2 rounded-pill px-4">Kembali ke Dashboard</a>
      </div>
    @else
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 text-center border-bottom">
          <h5 class="fw-bold mb-1">
            {{ $action === 'masuk' ? 'Absen Masuk' : 'Absen Pulang' }}
          </h5>
          <p class="text-muted small mb-0">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        
        <div class="card-body p-4">
          <form id="formAbsensi" onsubmit="submitAbsensi(event)">
            @csrf
            <input type="hidden" name="action" value="{{ $action }}">
            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">
            <input type="hidden" name="lokasi_text" id="lokasi_text" value="Mencari lokasi...">
            <input type="hidden" name="foto_base64" id="foto_base64">

            {{-- Camera Section --}}
            <div class="mb-4 text-center">
              <label class="form-label fw-semibold">Ambil Foto Selfie</label>
              <div class="camera-box shadow-sm">
                <video id="webcam" autoplay playsinline></video>
                <img id="preview" alt="Preview Foto">
                <div class="camera-overlay" id="camControls">
                  <button type="button" class="btn-capture shadow" id="btnCapture"></button>
                </div>
              </div>
              <div class="mt-3 btn-retake" id="btnRetakeContainer">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnRetake">
                  <i class="bi bi-camera me-1"></i>Ambil Ulang
                </button>
              </div>
            </div>

            {{-- Lokasi Status --}}
            <div class="mb-4 text-center">
              <div class="d-inline-flex align-items-center gap-2 px-3 py-2 bg-light rounded-pill border small">
                <i class="bi bi-geo-alt-fill text-danger"></i>
                <span id="locStatus" class="text-muted">Mencari lokasi perangkat...</span>
              </div>
            </div>

            @if($action === 'masuk')
              <div class="mb-4">
                <label for="keterangan" class="form-label fw-semibold">Keterangan (Opsional)</label>
                <textarea name="keterangan" id="keterangan" rows="2" class="form-control" placeholder="Tulis keterangan jika perlu..."></textarea>
              </div>
            @endif

            @if($action === 'pulang')
              <div class="mb-4">
                <label for="kendala" class="form-label fw-semibold">Kendala Hari Ini (Opsional)</label>
                <textarea name="kendala_hari_ini" id="kendala" rows="2" class="form-control" placeholder="Tulis kendala pekerjaan hari ini..."></textarea>
              </div>
              
              <div class="mb-4">
                <label class="form-label fw-semibold">Pekerjaan / Todo List (Opsional)</label>
                <textarea name="todo_json" class="form-control" rows="3" placeholder="Deskripsikan pekerjaan yang diselesaikan..."></textarea>
                <small class="text-muted">Fitur Todo yang lebih lengkap akan ada di Phase 5.</small>
              </div>
            @endif

            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold shadow-sm" id="btnSubmitForm" disabled>
              <span id="btnText"><i class="bi {{ $action === 'masuk' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' }} me-2"></i>Kirim Absensi</span>
              <span id="btnSpinner" class="d-none"><span class="spinner-border spinner-border-sm me-2"></span>Memproses...</span>
            </button>
          </form>
        </div>
      </div>
    @endif

  </div>
</div>
@endsection

@section('scripts')
@if($action !== 'done')
<script>
  // Camera Variables
  const video = document.getElementById('webcam');
  const preview = document.getElementById('preview');
  const btnCapture = document.getElementById('btnCapture');
  const btnRetakeContainer = document.getElementById('btnRetakeContainer');
  const btnRetake = document.getElementById('btnRetake');
  const camControls = document.getElementById('camControls');
  const fotoInput = document.getElementById('foto_base64');
  const btnSubmit = document.getElementById('btnSubmitForm');
  let stream = null;

  // Location Variables
  const locStatus = document.getElementById('locStatus');
  const latInput = document.getElementById('lat');
  const lngInput = document.getElementById('lng');
  let locReady = false;

  // Initialize Camera
  async function initCamera() {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
      video.srcObject = stream;
    } catch (err) {
      alert("Tidak dapat mengakses kamera: " + err.message);
    }
  }

  initCamera();

  // Capture Image
  btnCapture.addEventListener('click', () => {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    
    const base64Data = canvas.toDataURL('image/jpeg', 0.8);
    fotoInput.value = base64Data;
    preview.src = base64Data;
    
    video.style.display = 'none';
    preview.style.display = 'block';
    camControls.style.display = 'none';
    btnRetakeContainer.style.display = 'block';
    
    checkReady();
  });

  // Retake Image
  btnRetake.addEventListener('click', () => {
    fotoInput.value = '';
    video.style.display = 'block';
    preview.style.display = 'none';
    camControls.style.display = 'block';
    btnRetakeContainer.style.display = 'none';
    
    checkReady();
  });

  // Get Location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        latInput.value = position.coords.latitude;
        lngInput.value = position.coords.longitude;
        locStatus.textContent = "Lokasi terdeteksi ✓";
        locStatus.classList.remove('text-muted');
        locStatus.classList.add('text-success', 'fw-bold');
        locReady = true;
        checkReady();
      },
      (error) => {
        locStatus.textContent = "Gagal mengambil lokasi!";
        locStatus.classList.remove('text-muted');
        locStatus.classList.add('text-danger');
        alert("Mohon izinkan akses lokasi untuk melakukan absensi.");
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  } else {
    locStatus.textContent = "Geolocation tidak didukung browser.";
  }

  // Enable submit if both photo and location are ready
  function checkReady() {
    if (fotoInput.value && locReady) {
      btnSubmit.disabled = false;
    } else {
      btnSubmit.disabled = true;
    }
  }

  // Submit via AJAX
  async function submitAbsensi(e) {
    e.preventDefault();
    
    document.getElementById('btnText').classList.add('d-none');
    document.getElementById('btnSpinner').classList.remove('d-none');
    btnSubmit.disabled = true;

    const form = document.getElementById('formAbsensi');
    const formData = new FormData(form);

    try {
      const response = await fetch("{{ route('user.absensi.store') }}", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        body: formData
      });
      const data = await response.json();

      if (data.ok) {
        // Hentikan stream kamera
        if (stream) stream.getTracks().forEach(track => track.stop());
        alert(data.msg);
        window.location.href = data.redirect_url;
      } else {
        alert("Gagal: " + data.msg);
        resetBtn();
      }
    } catch (error) {
      alert("Terjadi kesalahan server.");
      resetBtn();
    }
  }

  function resetBtn() {
    document.getElementById('btnText').classList.remove('d-none');
    document.getElementById('btnSpinner').classList.add('d-none');
    btnSubmit.disabled = false;
  }
</script>
@endif
@endsection
