<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;

$now = Carbon::now('Asia/Jakarta');
$ymd = $now->toDateString();
$jam_masuk = '09:00:00';
$toleransi = 10;

$scheduled = Carbon::parse($ymd . ' ' . $jam_masuk, 'Asia/Jakarta');
$allowed = $scheduled->copy()->addMinutes($toleransi);

echo "Method 1: " . $now->diffInMinutes($allowed) . "\n";
echo "Method 2: " . $allowed->diffInMinutes($now) . "\n";
echo "Method 3: " . abs($now->diffInMinutes($allowed)) . "\n";
