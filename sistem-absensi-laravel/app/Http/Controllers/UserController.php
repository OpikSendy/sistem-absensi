<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;

class UserController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        $month = now()->format('m');
        $year = now()->format('Y');

        // Total kehadiran (Absen Masuk yang di-approve / semua masuk)
        $totalKehadiran = Absensi::where('user_id', $user->id)
            ->where('status', 'masuk')
            ->count();

        // Absen Masuk Hari Ini
        $absenMasukHariIni = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->where('status', 'masuk')
            ->first();

        // Absen Pulang Hari Ini
        $absenPulangHariIni = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->where('status', 'pulang')
            ->first();

        // Terlambat Bulan Ini
        $terlambatBulanIni = Absensi::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status', 'masuk')
            ->where('is_telat', 1)
            ->count();

        return view('user.dashboard', compact(
            'totalKehadiran',
            'absenMasukHariIni',
            'absenPulangHariIni',
            'terlambatBulanIni'
        ));
    }

    public function absensi()
    {
        return view('user.absensi');
    }

    public function profile()
    {
        return view('user.profile');
    }

    public function updateProfile(Request $request)
    {
        // Phase 4
        return back()->with('success', 'Profil diperbarui.');
    }

    public function uploadAvatar(Request $request)
    {
        // Phase 4
        return back()->with('success', 'Avatar diperbarui.');
    }

    public function deleteAvatar(Request $request)
    {
        // Phase 4
        return back()->with('success', 'Avatar dihapus.');
    }
}

