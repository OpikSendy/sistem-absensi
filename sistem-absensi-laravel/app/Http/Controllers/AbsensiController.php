<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;
use App\Models\UserJadwal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AbsensiController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        $action = $request->input('action'); // 'masuk' | 'pulang'

        if (!in_array($action, ['masuk', 'pulang'])) {
            return response()->json(['ok' => false, 'msg' => 'Action tidak valid.']);
        }

        $now = Carbon::now('Asia/Jakarta');
        $ymd = $now->toDateString();

        // Cek jadwal OFF
        $jadwal = UserJadwal::where('user_id', $user->id)->where('tanggal', $ymd)->first();
        if ($jadwal && strtoupper($jadwal->status) === 'OFF') {
            return response()->json(['ok' => false, 'msg' => 'Status jadwal OFF, tidak bisa absen.']);
        }

        // Upload foto
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('uploads', 'public');
        } elseif ($request->filled('foto_base64')) {
            // Handle base64 camera capture
            $image_parts = explode(";base64,", $request->input('foto_base64'));
            if (count($image_parts) == 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'uploads/capture_' . uniqid() . '.jpg';
                Storage::disk('public')->put($fileName, $image_base64);
                $fotoPath = $fileName;
            }
        }

        if ($action === 'masuk') {
            // Cek duplikat
            $exists = Absensi::where('user_id', $user->id)
                             ->where('tanggal', $ymd)
                             ->where('status', 'masuk')
                             ->exists();
            if ($exists) {
                return response()->json(['ok' => false, 'msg' => 'Sudah absen masuk hari ini.']);
            }

            // Hitung keterlambatan
            $shiftInfo = $this->getEffectiveShift($user->id, $ymd);
            $telatMenit = 0;
            if ($shiftInfo['jam_masuk']) {
                $scheduled = Carbon::parse($ymd . ' ' . $shiftInfo['jam_masuk'], 'Asia/Jakarta');
                $allowed   = $scheduled->copy()->addMinutes($shiftInfo['toleransi_menit']);
                if ($now->gt($allowed)) {
                    $telatMenit = (int) ceil($now->diffInMinutes($allowed));
                }
            }

            $absensi = Absensi::create([
                'user_id'         => $user->id,
                'waktu'           => $now,
                'tgl'             => $ymd,
                'tanggal'         => $ymd,
                'status'          => 'masuk',
                'shift_id'        => $shiftInfo['shift_id'],
                'telat_menit'     => $telatMenit,
                'is_telat'        => $telatMenit > 0 ? 1 : 0,
                'approval_status' => 'Pending',
                'foto'            => $fotoPath,
                'lat'             => $request->input('lat'),
                'lng'             => $request->input('lng'),
                'lokasi_text'     => $request->input('lokasi_text'),
                'keterangan'      => $request->input('keterangan'),
                'ip_client'       => $request->ip(),
                'user_agent'      => Str::limit($request->userAgent(), 250),
            ]);

            return response()->json([
                'ok'  => true,
                'msg' => 'Absensi masuk berhasil dicatat.',
                'data' => ['absensi_id' => $absensi->id, 'telat_menit' => $telatMenit],
                'redirect_url' => route('user.dashboard'),
            ]);
        }

        // ACTION: pulang
        $masuk = Absensi::where('user_id', $user->id)
                        ->where('tanggal', $ymd)
                        ->where('status', 'masuk')
                        ->first();
        
        if (!$masuk) {
            return response()->json(['ok' => false, 'msg' => 'Belum ada absen masuk hari ini.']);
        }

        $existsPulang = Absensi::where('user_id', $user->id)
                        ->where('tanggal', $ymd)
                        ->where('status', 'pulang')
                        ->exists();

        if ($existsPulang) {
            return response()->json(['ok' => false, 'msg' => 'Sudah absen pulang hari ini.']);
        }

        $todoJsonData = [];
        $todoNote = $request->input('todo_note');
        if (!empty($todoNote)) {
            $todoJsonData[] = ['tipe' => 'catatan', 'teks' => $todoNote];
        }

        $tugasIds = $request->input('tugas_ids', []);
        if (is_array($tugasIds) && count($tugasIds) > 0) {
            $masterTugas = \App\Models\TugasMaster::whereIn('id', $tugasIds)->get();
            foreach ($masterTugas as $t) {
                $todoJsonData[] = ['tipe' => 'tugas', 'teks' => $t->nama_tugas];
            }
        }

        $absensi = Absensi::create([
            'user_id'         => $user->id,
            'waktu'           => $now,
            'tgl'             => $ymd,
            'tanggal'         => $ymd,
            'status'          => 'pulang',
            'shift_id'        => $masuk->shift_id,
            'approval_status' => 'Pending',
            'foto'            => $fotoPath,
            'lat'             => $request->input('lat'),
            'lng'             => $request->input('lng'),
            'lokasi_text'     => $request->input('lokasi_text'),
            'kendala_hari_ini'=> $request->input('kendala_hari_ini'),
            'todo'            => count($todoJsonData) > 0 ? json_encode($todoJsonData) : null,
            'ip_client'       => $request->ip(),
            'user_agent'      => Str::limit($request->userAgent(), 250),
        ]);

        // Insert to absensi_todo
        if (is_array($tugasIds) && count($tugasIds) > 0) {
            foreach ($tugasIds as $tid) {
                \App\Models\AbsensiTodo::create([
                    'absensi_id' => $absensi->id,
                    'sumber' => 'master',
                    'master_id' => $tid,
                    'is_done' => 1
                ]);
            }
        }

        if (!empty($todoNote)) {
            \App\Models\AbsensiTodo::create([
                'absensi_id' => $absensi->id,
                'sumber' => 'manual',
                'manual_judul' => 'Catatan',
                'manual_detail' => $todoNote,
                'is_done' => 1
            ]);
        }

        return response()->json([
            'ok'  => true,
            'msg' => 'Absensi pulang berhasil dicatat.',
            'redirect_url' => route('user.dashboard'),
        ]);
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
            ->select('uj.shift_id', DB::raw('COALESCE(uj.jam_masuk, sm.jam_masuk) as jam_masuk'),
                     DB::raw('COALESCE(sm.toleransi_menit, 10) as toleransi_menit'))
            ->first();
        if ($jadwal) return (array)$jadwal;

        // Priority 2: user_shift aktif
        $shift = DB::table('user_shift as us')
            ->leftJoin('shift_master as sm', 'sm.id', '=', 'us.shift_id')
            ->where('us.user_id', $userId)->where('us.aktif', 1)
            ->select('us.shift_id', 'sm.jam_masuk',
                     DB::raw('COALESCE(sm.toleransi_menit, 10) as toleransi_menit'))
            ->first();
        if ($shift) return (array)$shift;

        return ['shift_id' => null, 'jam_masuk' => null, 'toleransi_menit' => 10];
    }
}


