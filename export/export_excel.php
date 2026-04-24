<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Nama');
$sheet->setCellValue('B1', 'Email');
$sheet->setCellValue('A2', 'Evolution');
$sheet->setCellValue('B2', 'evolution@example.com');

// Simpan ke folder public/exports
$writer = new Xlsx($spreadsheet);
$writer->save(__DIR__ . '/public/exports/data.xlsx');

echo "File berhasil dibuat di public/exports/data.xlsx";