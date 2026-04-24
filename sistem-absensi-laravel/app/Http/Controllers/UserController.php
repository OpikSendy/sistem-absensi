<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;
use App\Models\User; // Tambahkan ini
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
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

        return view('user.dashboard', compact(
            'totalKehadiran',
            'absenMasukHariIni',
            'absenPulangHariIni',
            'terlambatBulanIni'
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
}