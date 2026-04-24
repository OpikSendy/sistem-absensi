<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .header { margin-bottom: 30px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
    </style>
</head>
<body>

    <div class="header text-center">
        <div class="title">Laporan Kehadiran Karyawan</div>
        <div>Periode: {{ date('F', mktime(0, 0, 0, $bulan, 1)) }} {{ $tahun }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>Waktu</th>
                <th>Status</th>
                <th>Telat (Mnt)</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($absensi as $index => $abs)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($abs->tanggal)->format('d/m/Y') }}</td>
                <td>{{ $abs->user->nama }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($abs->waktu)->format('H:i') }}</td>
                <td class="text-center">{{ ucfirst($abs->status) }}</td>
                <td class="text-center">{{ $abs->telat_menit }}</td>
                <td>{{ $abs->keterangan ?? $abs->kendala_hari_ini ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada data absensi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
