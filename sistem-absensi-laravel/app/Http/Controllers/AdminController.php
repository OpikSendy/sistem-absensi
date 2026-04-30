<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ShiftMaster;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $today = now()->format('Y-m-d');
        
        $totalKaryawan = User::where('role', 'user')->where('aktif', 1)->count();
        $hadirHariIni = \App\Models\Absensi::where('tanggal', $today)->where('status', 'masuk')->count();
        $terlambatHariIni = \App\Models\Absensi::where('tanggal', $today)->where('status', 'masuk')->where('is_telat', 1)->count();
        $pendingApproval = \App\Models\Absensi::where('approval_status', 'Pending')->count();

        // Ambil data absensi terbaru
        $recentAbsensi = \App\Models\Absensi::with(['user', 'shift'])
            ->orderBy('waktu', 'desc')
            ->paginate(15);

        // Ambil data semua user untuk dropdown filter chart
        $allUsers = User::where('role', 'user')->where('aktif', 1)->orderBy('nama')->get();

        return view('admin.dashboard', compact(
            'totalKaryawan', 
            'hadirHariIni', 
            'terlambatHariIni', 
            'pendingApproval',
            'recentAbsensi',
            'allUsers'
        ));
    }

    public function users(Request $request)
    {
        $search = $request->input('search');
        $users = User::when($search, function ($q) use ($search) {
                return $q->where('nama', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%")
                         ->orWhere('devisi', 'like', "%{$search}%");
            })
            ->orderBy('role')
            ->orderBy('nama')
            ->paginate(15);
            
        return view('admin.users', compact('users', 'search'));
    }

    public function editUser(User $user)
    {
        return view('admin.users', compact('user'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,user',
            'devisi' => 'nullable|string|max:100',
            'nim' => 'nullable|string|max:50',
            'jurusan' => 'nullable|string|max:100',
            'asal_sekolah' => 'nullable|string|max:150',
            'no_hp' => 'nullable|string|max:20',
            'aktif' => 'required|boolean',
        ]);

        $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);

        User::create($validated);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'role' => 'required|in:admin,user',
            'devisi' => 'nullable|string|max:100',
            'nim' => 'nullable|string|max:50',
            'jurusan' => 'nullable|string|max:100',
            'asal_sekolah' => 'nullable|string|max:150',
            'no_hp' => 'nullable|string|max:20',
            'aktif' => 'required|boolean',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6']);
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->update($validated);

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }
        
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    public function shifts()
    {
        $shifts = \App\Models\ShiftMaster::orderBy('jam_masuk')->get();
        return view('admin.shifts', compact('shifts'));
    }

    public function storeShift(Request $request)
    {
        $validated = $request->validate([
            'nama_shift' => 'required|string|max:100',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'toleransi_menit' => 'required|integer|min:0',
            'durasi_menit' => 'required|integer|min:0',
            'aktif' => 'required|boolean',
        ]);

        $shift = ShiftMaster::create($validated);
        
        $this->notifyUsers('shift_baru', 'Shift Baru Ditambahkan', "Shift '{$shift->nama_shift}' telah ditambahkan oleh Admin.");

        return back()->with('success', 'Shift kerja berhasil ditambahkan.');
    }

    public function updateShift(Request $request, ShiftMaster $shift)
    {
        $validated = $request->validate([
            'nama_shift' => 'required|string|max:100',
            'jam_masuk' => 'required|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'toleransi_menit' => 'required|integer|min:0',
            'durasi_menit' => 'required|integer|min:0',
            'aktif' => 'required|boolean',
        ]);

        $shift->update($validated);

        $this->notifyUsers('shift_update', 'Perubahan Shift', "Shift '{$shift->nama_shift}' telah diperbarui oleh Admin.");

        return back()->with('success', 'Data shift kerja berhasil diperbarui.');
    }

    public function destroyShift(ShiftMaster $shift)
    {
        if ($shift->userShifts()->exists() || \App\Models\UserJadwal::where('shift_id', $shift->id)->exists() || $shift->absensi()->exists()) {
            return back()->withErrors(['Hapus Gagal' => 'Shift ini tidak dapat dihapus karena masih digunakan dalam jadwal karyawan atau rekam absensi.']);
        }

        $shift->delete();
        return back()->with('success', 'Shift kerja berhasil dihapus.');
    }

    public function userShifts(Request $request)
    {
        $search = $request->input('search');
        $users = User::where('role', 'user')
            ->when($search, function ($q) use ($search) {
                return $q->where(function($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                          ->orWhere('username', 'like', "%{$search}%")
                          ->orWhere('devisi', 'like', "%{$search}%");
                });
            })
            ->with(['shifts' => function($q) {
                $q->where('aktif', 1);
            }, 'shifts.shift'])
            ->orderBy('nama')
            ->paginate(15);
            
        $shifts = \App\Models\ShiftMaster::where('aktif', 1)->orderBy('jam_masuk')->get();
            
        return view('admin.user_shifts', compact('users', 'shifts', 'search'));
    }

    public function updateUserShift(Request $request, User $user)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shift_master,id',
        ]);

        // Nonaktifkan shift lama jika ada
        \App\Models\UserShift::where('user_id', $user->id)->update(['aktif' => 0]);

        // Buat atau aktifkan shift baru
        \App\Models\UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $validated['shift_id'],
            'aktif' => 1
        ]);

        $shiftMaster = \App\Models\ShiftMaster::find($validated['shift_id']);
        $this->notifyUsersSingle($user->id, 'shift_update', 'Penempatan Shift Baru', "Anda telah ditempatkan pada Shift '{$shiftMaster->nama_shift}' oleh Admin.");

        return back()->with('success', "Shift kerja untuk {$user->nama} berhasil diperbarui.");
    }

    private function notifyUsersSingle($userId, $type, $title, $message)
    {
        \App\Models\Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => 0,
            'created_at' => now()
        ]);
    }

    public function tugas()
    {
        $tugas = \App\Models\TugasMaster::orderBy('nama_tugas')->get();
        return view('admin.tugas', compact('tugas'));
    }

    public function storeTugas(Request $request)
    {
        $validated = $request->validate([
            'nama_tugas' => 'required|string|max:100',
            'kategori' => 'nullable|string|max:100',
            'aktif' => 'required|boolean',
        ]);

        $tugas = \App\Models\TugasMaster::create($validated);

        $this->notifyUsers('tugas_baru', 'Tugas Baru Ditambahkan', "Master tugas '{$tugas->nama_tugas}' telah ditambahkan oleh Admin.");

        return back()->with('success', 'Master Tugas berhasil ditambahkan.');
    }

    public function updateTugas(Request $request, \App\Models\TugasMaster $tugas)
    {
        $validated = $request->validate([
            'nama_tugas' => 'required|string|max:100',
            'kategori' => 'nullable|string|max:100',
            'aktif' => 'required|boolean',
        ]);

        $tugas->update($validated);

        $this->notifyUsers('tugas_update', 'Perubahan Tugas', "Master tugas '{$tugas->nama_tugas}' telah diperbarui oleh Admin.");

        return back()->with('success', 'Data Master Tugas berhasil diperbarui.');
    }

    private function notifyUsers($type, $title, $message)
    {
        $users = \App\Models\User::where('role', 'user')->where('aktif', 1)->pluck('id');
        $notifications = [];
        $now = now();
        foreach ($users as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'is_read' => 0,
                'created_at' => $now
            ];
        }
        if (count($notifications) > 0) {
            \App\Models\Notification::insert($notifications);
        }
    }

    public function destroyTugas(\App\Models\TugasMaster $tugas)
    {
        if ($tugas->todos()->exists()) {
            return back()->withErrors(['Hapus Gagal' => 'Tugas ini tidak dapat dihapus karena sudah digunakan oleh Karyawan dalam ToDo List.']);
        }

        $tugas->delete();
        return back()->with('success', 'Master Tugas berhasil dihapus.');
    }

    public function profile()
    {
        $user = auth()->user();
        return view('admin.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'nullable|email|max:100',
            'no_hp' => 'nullable|string|max:20',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:6|confirmed',
            ]);
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        if ($request->hasFile('foto')) {
            $request->validate([
                'foto' => 'image|mimes:jpeg,png,jpg|max:2048'
            ]);
            
            if ($user->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->foto);
            }
            
            $path = $request->file('foto')->store('uploads/profiles', 'public');
            $validated['foto'] = $path;
        }

        $user->update($validated);

        return back()->with('success', 'Profil admin berhasil diperbarui.');
    }

    // ─── Analytics ─────────────────────────────────────────────────────────────

    public function getRankingData()
    {
        $startOfWeek = now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = now()->endOfWeek()->format('Y-m-d');

        // Top 5 users based on most "Tepat Waktu" attendance this week
        $ranking = \App\Models\Absensi::selectRaw('user_id, count(*) as total_tepat_waktu')
            ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
            ->where('status', 'masuk')
            ->where('is_telat', 0)
            ->groupBy('user_id')
            ->orderByDesc('total_tepat_waktu')
            ->limit(5)
            ->with('user:id,nama,foto')
            ->get();

        $labels = [];
        $data = [];
        foreach ($ranking as $rank) {
            $labels[] = $rank->user->nama ?? 'Unknown';
            $data[] = $rank->total_tepat_waktu;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ]);
    }

    public function getUserDisciplineData(Request $request)
    {
        $userId = $request->input('user_id');
        $month = now()->month;
        $year = now()->year;

        $query = \App\Models\Absensi::where('status', 'masuk')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $absensi = $query->orderBy('tanggal', 'asc')->get();

        $grouped = $absensi->groupBy('tanggal');
        
        $labels = [];
        $onTimeData = [];
        $lateData = [];

        foreach ($grouped as $tanggal => $records) {
            $labels[] = \Carbon\Carbon::parse($tanggal)->format('d M');
            $onTimeCount = $records->where('is_telat', 0)->count();
            $lateCount = $records->where('is_telat', 1)->count();
            
            $onTimeData[] = $onTimeCount;
            $lateData[] = $lateCount;
        }

        return response()->json([
            'labels' => $labels,
            'onTime' => $onTimeData,
            'late' => $lateData
        ]);
    }

    public function getUserDistributionData(Request $request)
    {
        $userId = $request->input('user_id');
        $month = now()->month;
        $year = now()->year;

        $query = \App\Models\Absensi::where('status', 'masuk')
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $onTime = (clone $query)->where('is_telat', 0)->count();
        $late = (clone $query)->where('is_telat', 1)->count();
        
        $queryIzin = \App\Models\Absensi::whereIn('status', ['izin', 'sakit'])
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year);
        if ($userId) {
            $queryIzin->where('user_id', $userId);
        }
        $izin = $queryIzin->count();

        return response()->json([
            'labels' => ['Tepat Waktu', 'Terlambat', 'Izin/Sakit'],
            'data' => [$onTime, $late, $izin]
        ]);
    }
}

