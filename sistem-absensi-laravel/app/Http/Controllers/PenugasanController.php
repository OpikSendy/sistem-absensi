<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PenugasanController extends Controller
{
    public function index()
    {
        $penugasan = \App\Models\TugasKaryawan::with(['user', 'master'])
            ->orderBy('created_at', 'desc')
            ->get();
        $users = \App\Models\User::where('role', 'user')->where('aktif', 1)->get();
        $tugasMasters = \App\Models\TugasMaster::where('aktif', 1)->get();

        return view('admin.penugasan', compact('penugasan', 'users', 'tugasMasters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'judul_tugas' => 'required|string|max:200',
            'master_id' => 'nullable|exists:tugas_master,id',
            'deskripsi' => 'nullable|string',
            'tenggat_waktu' => 'nullable|date',
        ]);

        \App\Models\TugasKaryawan::create([
            'user_id' => $request->user_id,
            'master_id' => $request->master_id,
            'judul_tugas' => $request->judul_tugas,
            'deskripsi' => $request->deskripsi,
            'tenggat_waktu' => $request->tenggat_waktu,
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Tugas berhasil diberikan kepada karyawan.');
    }

    public function update(Request $request, $id)
    {
        $tugas = \App\Models\TugasKaryawan::findOrFail($id);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'judul_tugas' => 'required|string|max:200',
            'master_id' => 'nullable|exists:tugas_master,id',
            'deskripsi' => 'nullable|string',
            'tenggat_waktu' => 'nullable|date',
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        $tugas->update([
            'user_id' => $request->user_id,
            'master_id' => $request->master_id,
            'judul_tugas' => $request->judul_tugas,
            'deskripsi' => $request->deskripsi,
            'tenggat_waktu' => $request->tenggat_waktu,
            'status' => $request->status,
        ]);

        return back()->with('success', 'Tugas karyawan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $tugas = \App\Models\TugasKaryawan::findOrFail($id);
        $tugas->delete();

        return back()->with('success', 'Tugas karyawan berhasil dihapus.');
    }
}
