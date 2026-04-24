@extends('layouts.app')

@section('title', 'Dashboard Admin | Kesatriyan')
@section('page-title', 'Dashboard Admin')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
  <div>
    <h4 class="fw-bold text-dark mb-1">Selamat datang, {{ auth()->user()->nama ?: auth()->user()->username }}!</h4>
    <p class="text-muted mb-0 small">Overview kinerja dan manajemen absensi karyawan.</p>
  </div>
</div>

{{-- Placeholder stats cards --}}
<div class="row g-3 mb-4">
  @foreach([
    ['icon'=>'bi-people-fill','label'=>'Total Karyawan','val'=>'—','color'=>'primary'],
    ['icon'=>'bi-calendar-check','label'=>'Hadir Hari Ini','val'=>'—','color'=>'success'],
    ['icon'=>'bi-clock-history','label'=>'Terlambat','val'=>'—','color'=>'warning'],
    ['icon'=>'bi-hourglass-split','label'=>'Pending Approval','val'=>'—','color'=>'danger'],
  ] as $stat)
  <div class="col-6 col-lg-3">
    <div class="card shadow-sm p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 bg-{{ $stat['color'] }} bg-opacity-10">
          <i class="bi {{ $stat['icon'] }} text-{{ $stat['color'] }} fs-5"></i>
        </div>
        <div>
          <div class="fw-bold fs-5">{{ $stat['val'] }}</div>
          <div class="text-muted small">{{ $stat['label'] }}</div>
        </div>
      </div>
    </div>
  </div>
  @endforeach
</div>

<div class="card shadow-sm p-4 text-center text-muted">
  <i class="bi bi-tools fs-2 mb-2"></i>
  <div class="fw-semibold">Admin Dashboard — Phase 5</div>
  <small>Konten lengkap akan diimplementasikan di Phase 5.</small>
</div>
@endsection
