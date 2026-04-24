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

        return view('admin.dashboard', compact(
            'totalKaryawan', 
            'hadirHariIni', 
            'terlambatHariIni', 
            'pendingApproval',
            'recentAbsensi'
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
        // Phase 5
        return back()->with('success', 'Shift ditambahkan.');
    }

    public function updateShift(Request $request, ShiftMaster $shift)
    {
        // Phase 5
        return back()->with('success', 'Shift diperbarui.');
    }

    public function destroyShift(ShiftMaster $shift)
    {
        // Phase 5
        return back()->with('success', 'Shift dihapus.');
    }

    public function tugas()
    {
        return view('admin.tugas');
    }
}

