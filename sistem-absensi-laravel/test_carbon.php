<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserShift;

$now = Carbon::now('Asia/Jakarta');
echo "Now: " . $now->toDateTimeString() . "\n";

$ymd = $now->toDateString();
$jam_masuk = '09:00:00';
$toleransi = 10;

$scheduled = Carbon::parse($ymd . ' ' . $jam_masuk, 'Asia/Jakarta');
$allowed = $scheduled->copy()->addMinutes($toleransi);

echo "Scheduled: " . $scheduled->toDateTimeString() . "\n";
echo "Allowed: " . $allowed->toDateTimeString() . "\n";

if ($now->gt($allowed)) {
    echo "Is late! \n";
    $telatMenit = (int) ceil($now->diffInMinutes($allowed));
    echo "Telat Menit: " . $telatMenit . "\n";
} else {
    echo "Not late.\n";
}

// Cek UserShift
$userShifts = UserShift::with('shift')->where('aktif', 1)->get();
echo "Active UserShifts count: " . $userShifts->count() . "\n";
foreach ($userShifts as $us) {
    echo "User ID: {$us->user_id}, Shift ID: {$us->shift_id}, Jam Masuk: " . ($us->shift ? $us->shift->jam_masuk : 'NULL') . "\n";
}
