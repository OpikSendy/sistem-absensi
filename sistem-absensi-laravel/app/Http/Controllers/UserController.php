<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;
use App\Models\User;
use App\Models\TugasKaryawan;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function tugas()
    {
        $user = Auth::user();
        $tugas = TugasKaryawan::with('master')
            ->where('user_id', $user->id)
            ->orderByRaw("FIELD(status, 'Pending', 'In Progress', 'Completed')")
            ->orderBy('tenggat_waktu', 'asc')
            ->get();

        return view('user.tugas', compact('tugas'));
    }

    public function updateTugasStatus(Request $request, $id)
    {
        $tugas = TugasKaryawan::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed',
            'catatan_karyawan' => 'nullable|string'
        ]);

        $tugas->update([
            'status' => $request->status,
            'catatan_karyawan' => $request->catatan_karyawan
        ]);

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    /**
     * Menampilkan Daftar Karyawan (Untuk Admin)
     * Ini fungsi yang memperbaiki error 'hasPages'
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $users = User::when($search, function ($query) use ($search) {
            $query->where('nama', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('devisi', 'like', "%{$search}%");
        })
            ->orderBy('id', 'desc')
            ->paginate(10); // WAJIB paginate() agar hasPages() di Blade jalan

        return view('admin.users', compact('users', 'search'));
    }

    public function dashboard()
    {
        $user = Auth::user();
        $today = now()->format('Y-m-d');
        $month = now()->format('m');
        $year = now()->format('Y');

        $totalKehadiran = Absensi::where('user_id', $user->id)
            ->where('status', 'masuk')
            ->count();

        $absenMasukHariIni = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->where('status', 'masuk')
            ->first();

        $absenPulangHariIni = Absensi::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->where('status', 'pulang')
            ->first();

        $terlambatBulanIni = Absensi::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->where('status', 'masuk')
            ->where('is_telat', 1)
            ->count();

        $activeShift = \App\Models\UserShift::with('shift')
            ->where('user_id', $user->id)
            ->where('aktif', 1)
            ->first();

        return view('user.dashboard', compact(
            'totalKehadiran',
            'absenMasukHariIni',
            'absenPulangHariIni',
            'terlambatBulanIni',
            'activeShift'
        ));
    }

    public function absensi()
    {
        $tugas = \App\Models\TugasMaster::where('aktif', 1)->orderBy('nama_tugas')->get();
        return view('user.absensi', compact('tugas'));
    }

    public function profile()
    {
        return view('user.profile');
    }

    /**
     * Update Profile Terpadu
     * Mencegah double notifikasi dan error kolom 'email'
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $data = $request->only(['nama', 'username', 'no_hp', 'devisi', 'nim', 'jurusan', 'asal_sekolah', 'tanggal_lahir']);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('foto')) {
            // Hapus foto lama
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            $data['foto'] = $request->file('foto')->store('uploads/profiles', 'public');
        }

        $user->update($data);

        // Hanya kirim SATU session success
        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    // Fungsi uploadAvatar & deleteAvatar bisa dikosongkan/dihapus jika sudah digabung ke updateProfile
    public function uploadAvatar(Request $request)
    {
        return $this->updateProfile($request);
    }

    // ─── Analytics ─────────────────────────────────────────────────────────────

    public function getMyDisciplineData()
    {
        $userId = Auth::id();
        $month = now()->month;
        $year = now()->year;

        $absensi = Absensi::where('user_id', $userId)
            ->where('status', 'masuk')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->orderBy('tanggal', 'asc')
            ->get();

        $labels = [];
        $onTimeData = [];
        $lateData = [];

        foreach ($absensi as $record) {
            $labels[] = \Carbon\Carbon::parse($record->tanggal)->format('d M');
            if ($record->is_telat) {
                $onTimeData[] = 0;
                $lateData[] = 1;
            } else {
                $onTimeData[] = 1;
                $lateData[] = 0;
            }
        }

        return response()->json([
            'labels' => $labels,
            'onTime' => $onTimeData,
            'late' => $lateData
        ]);
    }

    public function getMyDistributionData()
    {
        $userId = Auth::id();
        $month = now()->month;
        $year = now()->year;

        $query = Absensi::where('user_id', $userId)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year);

        $onTime = (clone $query)->where('status', 'masuk')->where('is_telat', 0)->count();
        $late = (clone $query)->where('status', 'masuk')->where('is_telat', 1)->count();
        $izin = (clone $query)->whereIn('status', ['izin', 'sakit'])->count();

        return response()->json([
            'labels' => ['Tepat Waktu', 'Terlambat', 'Izin/Sakit'],
            'data' => [$onTime, $late, $izin]
        ]);
    }
}