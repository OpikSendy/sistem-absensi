<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use Carbon\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        $absensi = Absensi::with(['user', 'shift'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'asc')
            ->get();

        $fileName = "Laporan_Absensi_{$bulan}_{$tahun}.xlsx";

        $writer = SimpleExcelWriter::streamDownload($fileName);
        
        foreach ($absensi as $abs) {
            $writer->addRow([
                'Tanggal' => Carbon::parse($abs->tanggal)->format('d/m/Y'),
                'Nama' => $abs->user->nama,
                'Divisi' => $abs->user->devisi,
                'Waktu' => Carbon::parse($abs->waktu)->format('H:i'),
                'Status' => ucfirst($abs->status),
                'Keterlambatan (Menit)' => $abs->telat_menit,
                'Keterangan' => $abs->keterangan ?? $abs->kendala_hari_ini ?? '-',
                'Approval' => $abs->approval_status,
                'Lokasi' => $abs->lokasi_text,
            ]);
        }

        return $writer->toBrowser();
    }

    public function exportPdf(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        $absensi = Absensi::with(['user'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'asc')
            ->get();

        $pdf = Pdf::loadView('admin.export.pdf', compact('absensi', 'bulan', 'tahun'));
        
        return $pdf->download("Laporan_Absensi_{$bulan}_{$tahun}.pdf");
    }
}
