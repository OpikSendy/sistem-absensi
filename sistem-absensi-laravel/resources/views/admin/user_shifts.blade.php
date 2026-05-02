@extends('layouts.app')
@section('title', 'Manajemen Jadwal | Kesatriyan Admin')
@section('page-title', 'Manajemen Jadwal')

@section('styles')
<style>
  .roster-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .roster-table {
    min-width: 800px;
    border-collapse: separate;
    border-spacing: 0;
  }
  .roster-table th, .roster-table td {
    white-space: nowrap;
    vertical-align: middle;
  }
  .roster-table thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 10;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6c757d;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    padding: 10px 8px;
    text-align: center;
  }
  .roster-table thead th.col-name {
    position: sticky;
    left: 0;
    z-index: 20;
    min-width: 180px;
    text-align: left;
    padding-left: 16px;
  }
  .roster-table td.col-name {
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 5;
    min-width: 180px;
    border-right: 1px solid #dee2e6;
    padding: 8px 12px;
  }
  .roster-table tbody tr:hover td.col-name { background: #f8f9fa; }
  .roster-table td.col-name:after {
    content: '';
    position: absolute;
    right: 0; top: 0; bottom: 0;
    width: 1px;
    background: #dee2e6;
  }
  .roster-cell {
    text-align: center;
    padding: 4px 6px;
    min-width: 90px;
    cursor: pointer;
    transition: background .15s;
  }
  .roster-cell:hover { background: #e9ecef; }

  /* Shift pill inside cell */
  .shift-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid transparent;
    transition: transform .1s;
    user-select: none;
  }
  .shift-pill:active { transform: scale(.95); }
  .shift-btn-off   { background: #f1f3f5; color: #6c757d; border-color: #dee2e6; }
  .shift-btn-libur { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

  /* Palette buttons in header */
  .palette-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    border: 2px solid transparent;
    transition: box-shadow .15s, transform .1s;
    user-select: none;
  }
  .palette-btn:active { transform: scale(.95); }
  .palette-btn.active { box-shadow: 0 0 0 3px rgba(0,0,0,.15); }

  /* Weekend column highlight */
  .col-weekend th, .col-weekend td { background: #fffbf2 !important; }
  .roster-table tbody tr:hover .col-weekend td { background: #fff3cd !important; }

  .today-col th, .today-col td { background: #eff6ff !important; border-bottom: 2px solid #3b82f6; }

  /* Save button glow */
  #btnSaveRoster {
    transition: box-shadow .2s;
  }
  #btnSaveRoster:not(:disabled):hover {
    box-shadow: 0 0 0 4px rgba(13,110,253,.25);
  }

  /* Changed cell highlight */
  .cell-changed {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
  }

  /* Crosshair cursor when hovering cells */
  .roster-cell {
    cursor: crosshair;
  }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
  <div>
    <h5 class="fw-bold mb-1">Manajemen Jadwal</h5>
    <p class="text-muted small mb-0">Klik shift di bawah, lalu klik sel karyawan untuk mengisi jadwal.</p>
  </div>
  <div class="d-flex gap-2 align-items-center flex-wrap">
    {{-- Date Range --}}
    <form id="rangeForm" action="{{ route('admin.user_shifts') }}" method="GET" class="d-flex align-items-center gap-2">
      <input type="date" name="start_date" id="startDate" class="form-control form-control-sm" value="{{ $startDate }}" style="max-width:140px;">
      <span class="text-muted small">s/d</span>
      <input type="date" name="end_date" id="endDate" class="form-control form-control-sm" value="{{ $endDate }}" style="max-width:140px;">
      <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
        <i class="bi bi-calendar3 me-1"></i>Tampilkan
      </button>
    </form>
    <button id="btnSaveRoster" class="btn btn-sm btn-success rounded-pill px-4 fw-bold" disabled>
      <span id="saveText"><i class="bi bi-check-lg me-1"></i>Simpan</span>
      <span id="saveSpinner" class="d-none"><span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...</span>
    </button>
  </div>
</div>

{{-- Shift Palette --}}
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-3 px-4 d-flex flex-wrap gap-2 align-items-center">
    <span class="text-muted small fw-semibold me-2">Pilih Shift:</span>
    {{-- OFF --}}
    <button class="palette-btn active" data-shift-id="" data-shift-status="OFF"
      style="background:#f1f3f5;color:#6c757d;border-color:#dee2e6;" id="palette-off">
      <i class="bi bi-dash-circle"></i> OFF
    </button>
    {{-- Libur --}}
    <button class="palette-btn" data-shift-id="" data-shift-status="TM"
      style="background:#fee2e2;color:#dc2626;border-color:#fca5a5;" id="palette-libur">
      <i class="bi bi-calendar-x"></i> Libur
    </button>
    {{-- Dynamic shifts --}}
    @php
      $colors = [
        ['bg'=>'#dbeafe','text'=>'#1d4ed8','border'=>'#93c5fd'],
        ['bg'=>'#dcfce7','text'=>'#15803d','border'=>'#86efac'],
        ['bg'=>'#fef9c3','text'=>'#a16207','border'=>'#fde047'],
        ['bg'=>'#f3e8ff','text'=>'#7e22ce','border'=>'#d8b4fe'],
        ['bg'=>'#ffe4e6','text'=>'#be123c','border'=>'#fda4af'],
        ['bg'=>'#ccfbf1','text'=>'#0f766e','border'=>'#5eead4'],
      ];
    @endphp
    @foreach($shifts as $i => $s)
      @php $c = $colors[$i % count($colors)]; @endphp
      <button class="palette-btn" data-shift-id="{{ $s->id }}" data-shift-status="hadir"
        data-shift-label="{{ $s->nama_shift }}" data-shift-time="{{ \Carbon\Carbon::parse($s->jam_masuk)->format('H:i') }}"
        data-bg="{{ $c['bg'] }}" data-text="{{ $c['text'] }}" data-border="{{ $c['border'] }}"
        style="background:{{ $c['bg'] }};color:{{ $c['text'] }};border-color:{{ $c['border'] }};"
        id="palette-shift-{{ $s->id }}">
        <i class="bi bi-clock"></i> {{ $s->nama_shift }} <span style="font-weight:400;opacity:.7;">{{ \Carbon\Carbon::parse($s->jam_masuk)->format('H:i') }}</span>
      </button>
    @endforeach
  </div>
</div>

{{-- Roster Grid --}}
<div class="card border-0 shadow-sm">
  <div class="card-body p-0 roster-wrap">
    <table class="roster-table w-100" id="rosterTable">
      <thead>
        <tr>
          <th class="col-name">Karyawan</th>
          @foreach($dates as $d)
            <th class="{{ $d->isWeekend() ? 'col-weekend' : '' }} {{ $d->isToday() ? 'today-col' : '' }}">
              <div>{{ $d->translatedFormat('D') }}</div>
              <div style="font-size:1rem;font-weight:800;color:#1e293b;">{{ $d->format('d') }}</div>
            </th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse($users as $u)
          <tr>
            <td class="col-name">
              <div class="d-flex align-items-center gap-2">
                @if($u->foto)
                  <img src="{{ asset('storage/' . $u->foto) }}" class="rounded-circle object-fit-cover" width="30" height="30">
                @else
                  <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width:30px;height:30px;font-size:.75rem;">
                    {{ strtoupper(substr($u->nama, 0, 2)) }}
                  </div>
                @endif
                <div>
                  <div class="fw-semibold text-dark" style="font-size:.82rem;">{{ $u->nama }}</div>
                  <div class="text-muted" style="font-size:.68rem;">{{ $u->devisi ?: '-' }}</div>
                </div>
              </div>
            </td>
            @foreach($dates as $d)
              @php
                $ymd  = $d->format('Y-m-d');
                $jadwal = $jadwalList[$u->id][$ymd] ?? null;
                $isWeekend = $d->isWeekend();
                $isToday   = $d->isToday();
              @endphp
              <td class="roster-cell {{ $isWeekend ? 'col-weekend' : '' }} {{ $isToday ? 'today-col' : '' }}"
                data-user-id="{{ $u->id }}"
                data-tanggal="{{ $ymd }}"
                data-shift-id="{{ $jadwal?->shift_id ?? '' }}"
                data-status="{{ $jadwal?->status ?? ($isWeekend ? 'TM' : 'OFF') }}"
                onclick="handleCellClick(this)">
                @if($jadwal && $jadwal->status === 'ON' && $jadwal->shift)
                  @php
                    $idx = $shifts->search(fn($s) => $s->id === $jadwal->shift_id);
                    $c2  = $idx !== false ? $colors[$idx % count($colors)] : $colors[0];
                  @endphp
                  <span class="shift-pill"
                    style="background:{{ $c2['bg'] }};color:{{ $c2['text'] }};border-color:{{ $c2['border'] }};">
                    {{ $jadwal->shift->nama_shift }}
                  </span>
                @elseif($jadwal && $jadwal->status === 'TM')
                  <span class="shift-pill shift-btn-libur"><i class="bi bi-calendar-x"></i></span>
                @else
                  <span class="shift-pill shift-btn-off"><i class="bi bi-dash"></i></span>
                @endif
              </td>
            @endforeach
          </tr>
        @empty
          <tr>
            <td colspan="{{ count($dates) + 1 }}" class="text-center text-muted py-5">
              <i class="bi bi-calendar2-x fs-2 d-block mb-2"></i>Belum ada karyawan aktif.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Legend --}}
<div class="mt-3 d-flex flex-wrap gap-3 align-items-center">
  <small class="text-muted fw-semibold">Keterangan:</small>
  <span class="shift-pill shift-btn-off"><i class="bi bi-dash"></i> Belum diatur</span>
  <span class="shift-pill shift-btn-libur"><i class="bi bi-calendar-x"></i> Libur</span>
  @foreach($shifts as $i => $s)
    @php $c = $colors[$i % count($colors)]; @endphp
    <span class="shift-pill" style="background:{{ $c['bg'] }};color:{{ $c['text'] }};border-color:{{ $c['border'] }};">
      {{ $s->nama_shift }}
    </span>
  @endforeach
</div>

@endsection

@section('scripts')
<script>
  // ─── State ────────────────────────────────────────────────────────────────────
  let selectedPalette = {
    shiftId: '',
    status: 'OFF',       // Must match DB enum: ON | OFF | TM
    label: 'OFF',
    bg: '#f1f3f5',
    text: '#6c757d',
    border: '#dee2e6',
    time: ''
  };

  // Changed cells: keyed by "userId_tanggal" => entry object
  const changedCells = {};

  // Block-select state
  let isDragging = false;

  // ─── Palette click ────────────────────────────────────────────────────────────
  document.querySelectorAll('.palette-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.palette-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedPalette = {
        shiftId: btn.dataset.shiftId || '',
        status:  btn.dataset.shiftStatus,
        label:   btn.dataset.shiftLabel || btn.textContent.trim(),
        bg:      btn.dataset.bg     || '#f1f3f5',
        text:    btn.dataset.text   || '#6c757d',
        border:  btn.dataset.border || '#dee2e6',
        time:    btn.dataset.shiftTime || ''
      };
    });
  });

  // ─── Apply palette to a single cell ──────────────────────────────────────────
  function applyPaletteToCell(cell) {
    const userId  = cell.dataset.userId;
    const tanggal = cell.dataset.tanggal;
    if (!userId || !tanggal) return;
    const key = userId + '_' + tanggal;

    let pillHtml = '';
    if (selectedPalette.status === 'OFF') {
      pillHtml = `<span class="shift-pill shift-btn-off"><i class="bi bi-dash"></i></span>`;
    } else if (selectedPalette.status === 'TM') {
      pillHtml = `<span class="shift-pill shift-btn-libur"><i class="bi bi-calendar-x"></i></span>`;
    } else {
      pillHtml = `<span class="shift-pill" style="background:${selectedPalette.bg};color:${selectedPalette.text};border-color:${selectedPalette.border};">
        ${selectedPalette.label}
      </span>`;
    }
    cell.innerHTML = pillHtml;
    cell.classList.add('cell-changed');

    changedCells[key] = {
      user_id:  userId,
      tanggal:  tanggal,
      shift_id: selectedPalette.shiftId || null,
      status:   selectedPalette.status  // Already ON | OFF | TM
    };

    document.getElementById('btnSaveRoster').disabled = false;
  }

  // ─── Cell click (single) ──────────────────────────────────────────────────────
  function handleCellClick(cell) {
    applyPaletteToCell(cell);
  }

  // ─── Block / Drag select ──────────────────────────────────────────────────────
  const rosterTable = document.getElementById('rosterTable');

  rosterTable.addEventListener('mousedown', e => {
    const cell = e.target.closest('.roster-cell');
    if (!cell) return;
    isDragging = true;
    applyPaletteToCell(cell);
    e.preventDefault(); // prevent text selection while dragging
  });

  rosterTable.addEventListener('mouseover', e => {
    if (!isDragging) return;
    const cell = e.target.closest('.roster-cell');
    if (cell) applyPaletteToCell(cell);
  });

  document.addEventListener('mouseup', () => { isDragging = false; });

  // ─── Save Roster ──────────────────────────────────────────────────────────────
  document.getElementById('btnSaveRoster').addEventListener('click', async () => {
    const entries = Object.values(changedCells);
    if (entries.length === 0) return;

    document.getElementById('saveText').classList.add('d-none');
    document.getElementById('saveSpinner').classList.remove('d-none');
    document.getElementById('btnSaveRoster').disabled = true;

    try {
      const response = await fetch('{{ route("admin.roster.save") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ entries })
      });

      const data = await response.json();
      if (data.ok) {
        const btn = document.getElementById('btnSaveRoster');
        btn.classList.replace('btn-success', 'btn-outline-success');
        document.getElementById('saveText').innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Tersimpan!';
        document.getElementById('saveText').classList.remove('d-none');
        document.getElementById('saveSpinner').classList.add('d-none');
        // Remove highlight from changed cells
        document.querySelectorAll('.cell-changed').forEach(c => c.classList.remove('cell-changed'));
        setTimeout(() => {
          btn.classList.replace('btn-outline-success', 'btn-success');
          document.getElementById('saveText').innerHTML = '<i class="bi bi-check-lg me-1"></i>Simpan';
          btn.disabled = true;
          Object.keys(changedCells).forEach(k => delete changedCells[k]);
        }, 2000);
      } else {
        alert('Gagal menyimpan: ' + (data.msg || 'Error tidak diketahui'));
        resetSaveBtn();
      }
    } catch (err) {
      console.error(err);
      alert('Koneksi error saat menyimpan jadwal.');
      resetSaveBtn();
    }
  });

  function resetSaveBtn() {
    document.getElementById('saveText').classList.remove('d-none');
    document.getElementById('saveSpinner').classList.add('d-none');
    document.getElementById('btnSaveRoster').disabled = false;
  }
</script>
@endsection
