<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;
use App\Models\UserJadwal;
use App\Models\AbsensiTodo;
use App\Models\TugasMaster;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class AbsensiController extends Controller
{
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $user = Auth::user();
            $action = $request->input('action');
            $now = Carbon::now('Asia/Jakarta');
            $ymd = $now->toDateString();

            // 1. Validasi Awal
            if (!in_array($action, ['masuk', 'pulang'])) {
                return response()->json(['ok' => false, 'msg' => 'Aksi tidak valid.']);
            }

            // 2. Cek Jadwal OFF
            $jadwal = UserJadwal::where('user_id', $user->id)->where('tanggal', $ymd)->first();
            if ($jadwal && strtoupper($jadwal->status) === 'OFF') {
                return response()->json(['ok' => false, 'msg' => 'Status jadwal Anda hari ini adalah OFF.']);
            }

            // 3. Cek Duplikasi / Urutan Absen
            if ($action === 'masuk') {
                $exists = Absensi::where('user_id', $user->id)->where('tanggal', $ymd)->where('status', 'masuk')->exists();
                if ($exists)
                    return response()->json(['ok' => false, 'msg' => 'Anda sudah absen masuk hari ini.']);
            } else {
                $masuk = Absensi::where('user_id', $user->id)->where('tanggal', $ymd)->where('status', 'masuk')->first();
                if (!$masuk)
                    return response()->json(['ok' => false, 'msg' => 'Gagal: Anda belum absen masuk hari ini.']);

                $existsPulang = Absensi::where('user_id', $user->id)->where('tanggal', $ymd)->where('status', 'pulang')->exists();
                if ($existsPulang)
                    return response()->json(['ok' => false, 'msg' => 'Anda sudah absen pulang hari ini.']);
            }

            // 4. Handle Upload Foto (Base64 atau File)
            $fotoPath = null;
            try {
                if ($request->filled('foto_base64')) {
                    $image_parts = explode(";base64,", $request->input('foto_base64'));
                    if (count($image_parts) == 2) {
                        $image_base64 = base64_decode($image_parts[1]);
                        $fileName = 'uploads/capture_' . uniqid() . '.jpg';
                        Storage::disk('public')->put($fileName, $image_base64);
                        $fotoPath = $fileName;
                    }
                } elseif ($request->hasFile('foto')) {
                    $fotoPath = $request->file('foto')->store('uploads', 'public');
                }
            } catch (\Exception $e) {
                Log::error("Gagal upload foto: " . $e->getMessage());
            }

            // 5. Eksekusi Simpan Data
            try {
                $absensiData = [
                    'user_id' => $user->id,
                    'waktu' => $now,
                    'tgl' => $ymd, // Kolom legacy
                    'tanggal' => $ymd,
                    'status' => $action,
                    'approval_status' => 'Pending',
                    'foto' => $fotoPath,
                    'lat' => $request->input('lat'),
                    'lng' => $request->input('lng'),
                    'lokasi_text' => $request->input('lokasi_text'),
                    'ip_client' => $request->ip(),
                    'user_agent' => Str::limit($request->userAgent(), 250),
                ];

                if ($action === 'masuk') {
                    $shiftInfo = $this->getEffectiveShift($user->id, $ymd);
                    $telatMenit = 0;
                    if ($shiftInfo['jam_masuk']) {
                        $scheduled = Carbon::parse($ymd . ' ' . $shiftInfo['jam_masuk'], 'Asia/Jakarta');
                        $allowed = $scheduled->copy()->addMinutes($shiftInfo['toleransi_menit']);
                        if ($now->gt($allowed)) {
                            $telatMenit = (int) ceil($now->diffInMinutes($allowed));
                        }
                    }
                    $absensiData['shift_id'] = $shiftInfo['shift_id'];
                    $absensiData['telat_menit'] = $telatMenit;
                    $absensiData['is_telat'] = $telatMenit > 0 ? 1 : 0;
                    $absensiData['keterangan'] = $request->input('keterangan');
                } else {
                    // Masuk sudah dicek di atas, ambil shift_id dari absen masuk
                    $masuk = Absensi::where('user_id', $user->id)->where('tanggal', $ymd)->where('status', 'masuk')->first();
                    $absensiData['shift_id'] = $masuk->shift_id;
                    $absensiData['kendala_hari_ini'] = $request->input('kendala_hari_ini');

                    // Olah Todo JSON
                    $todoJson = [];
                    if ($request->filled('todo_note')) {
                        $todoJson[] = ['tipe' => 'catatan', 'teks' => $request->todo_note];
                    }
                    $tugasIds = $request->input('tugas_ids', []);
                    if (!empty($tugasIds)) {
                        $tugasNames = TugasMaster::whereIn('id', $tugasIds)->pluck('nama_tugas');
                        foreach ($tugasNames as $name) {
                            $todoJson[] = ['tipe' => 'tugas', 'teks' => $name];
                        }
                    }
                    $absensiData['todo'] = !empty($todoJson) ? json_encode($todoJson) : null;
                }

                $absensi = Absensi::create($absensiData);

                // 6. Simpan detail ke absensi_todo (Khusus Pulang)
                if ($action === 'pulang') {
                    // Bagian simpan tugas master
                    if (!empty($tugasIds)) {
                        foreach ($tugasIds as $tid) {
                            AbsensiTodo::create([
                                'absensi_id' => $absensi->id,
                                'sumber' => 'master', // Pastikan menggunakan tanda petik string
                                'master_id' => (int) $tid,
                                'is_done' => 1
                            ]);
                        }
                    }

                    // Bagian simpan catatan manual
                    if ($request->filled('todo_note')) {
                        AbsensiTodo::create([
                            'absensi_id' => $absensi->id,
                            'sumber' => 'manual', // Pastikan menggunakan tanda petik string
                            'manual_judul' => 'Catatan',
                            'manual_detail' => $request->todo_note,
                            'is_done' => 1
                        ]);
                    }
                }

                // 7. Notifikasi Admin (Gunakan Try-Catch agar tidak menggagalkan absen)
                try {
                    $this->notifyAdmins($action, $user, $absensi->id);
                } catch (\Exception $ne) {
                    Log::error("Notifikasi Gagal: " . $ne->getMessage());
                }

                return response()->json([
                    'ok' => true,
                    'msg' => 'Absensi ' . $action . ' berhasil dicatat.',
                    'redirect_url' => route('user.dashboard'),
                ]);

            } catch (\Exception $e) {
                Log::error("Fatal Error Absensi: " . $e->getMessage());
                return response()->json(['ok' => false, 'msg' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
            }
        });
    }

    private function notifyAdmins($action, $user, $absensiId)
    {
        $admins = \App\Models\User::where('role', 'admin')->where('aktif', 1)->pluck('id');
        $type = $action === 'masuk' ? 'absen_masuk' : 'absen_pulang';
        $title = ($action === 'masuk' ? 'Absen Masuk: ' : 'Absen Pulang: ') . $user->nama;
        $message = "{$user->nama} telah melakukan absen {$action}.";

        $notifications = [];
        foreach ($admins as $adminId) {
            $notifications[] = [
                'user_id' => $adminId,
                'absensi_id' => $absensiId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(), // Sangat penting untuk insert batch
            ];
        }
        if (!empty($notifications)) {
            Notification::insert($notifications);
        }
    }

    public function updateStatus(Request $request)
    {
        $absensi = Absensi::find($request->input('id'));
        if (!$absensi) {
            return response()->json(['ok' => false, 'msg' => 'Data tidak ditemukan.']);
        }

        $approval = $request->input('approval');

        if ($approval === 'delete') {
            $absensi->delete();
            return response()->json(['ok' => true, 'msg' => 'Data berhasil dihapus.']);
        }

        if (!in_array($approval, ['Disetujui', 'Ditolak'])) {
            return response()->json(['ok' => false, 'msg' => 'Status tidak valid.']);
        }

        $absensi->update(['approval_status' => $approval]);
        return response()->json(['ok' => true, 'msg' => "Status berhasil diubah menjadi {$approval}."]);
    }

    public function detail(Absensi $absensi)
    {
        return response()->json($absensi->load('user', 'shift', 'todos'));
    }

    private function getEffectiveShift(int $userId, string $tanggal): array
    {
        // Priority 1: user_jadwal
        $jadwal = DB::table('user_jadwal as uj')
            ->leftJoin('shift_master as sm', 'sm.id', '=', 'uj.shift_id')
            ->where('uj.user_id', $userId)->where('uj.tanggal', $tanggal)
            ->select(
                'uj.shift_id',
                DB::raw('COALESCE(uj.jam_masuk, sm.jam_masuk) as jam_masuk'),
                DB::raw('COALESCE(sm.toleransi_menit, 10) as toleransi_menit')
            )
            ->first();
        if ($jadwal)
            return (array) $jadwal;

        // Priority 2: user_shift aktif
        $shift = DB::table('user_shift as us')
            ->leftJoin('shift_master as sm', 'sm.id', '=', 'us.shift_id')
            ->where('us.user_id', $userId)->where('us.aktif', 1)
            ->select(
                'us.shift_id',
                'sm.jam_masuk',
                DB::raw('COALESCE(sm.toleransi_menit, 10) as toleransi_menit')
            )
            ->first();
        if ($shift)
            return (array) $shift;

        return ['shift_id' => null, 'jam_masuk' => null, 'toleransi_menit' => 10];
    }
}


