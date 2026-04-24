<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ShiftMaster;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function users()
    {
        return view('admin.users');
    }

    public function editUser(User $user)
    {
        return view('admin.users', compact('user'));
    }

    public function storeUser(Request $request)
    {
        // Phase 5
        return back()->with('success', 'User ditambahkan.');
    }

    public function updateUser(Request $request, User $user)
    {
        // Phase 5
        return back()->with('success', 'User diperbarui.');
    }

    public function destroyUser(User $user)
    {
        // Phase 5
        return back()->with('success', 'User dihapus.');
    }

    public function shifts()
    {
        return view('admin.shifts');
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

