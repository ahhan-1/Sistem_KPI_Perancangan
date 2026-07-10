<?php
// FILE MANAJEMEN EXCEL KPI
// Digunakan untuk urusan berkas luar: Import Target/Realisasi dan Download Template Excel.

ob_start();
session_start();
require_once "koneksi.php";
require_once "perhitungan_kpi.php";
require_once __DIR__ . "/../vendor/autoload.php";

/** @var mysqli $koneksi */

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Proteksi Akses
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../index.php');
    exit();
}

// Pengaturan Environment
ini_set('memory_limit', '512M');
set_time_limit(300);

// Ambil parameter aksi utama
$aksi_utama = isset($_POST['aksi_utama']) ? $_POST['aksi_utama'] : (isset($_GET['aksi_utama']) ? $_GET['aksi_utama'] : (isset($_GET['aksi']) ? $_GET['aksi'] : ''));

// --- 1. Import Realisasi dari Excel ---
if ($aksi_utama == 'import_realisasi' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $tahun = isset($_POST['tahun']) ? (int) $_POST['tahun'] : (int) date('Y');
    $bulan = isset($_POST['bulan']) ? (int) $_POST['bulan'] : (int) date('m');
    $file = $_FILES['file']['tmp_name'];

    if (!is_uploaded_file($file)) {
        header("Location: ../halaman/divisi/realisasi_cabang.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=gagal_upload");
        exit();
    }

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        // null=nullValue, true=calculateFormulas, false=formatData (biar angka tidak di-format sbg string)
        $rows = $sheet->toArray(null, true, false);
        $indicator_ids = $rows[0]; // Gunakan baris pertama (yang terlihat) sbg header
        $success_count = 0;

        // Fase 1: Validasi Data Kosong
        // Mulai dari $i=2 karena baris index 0=header, index 1=baris tersembunyi duplikat header
        for ($i = 2; $i < count($rows); $i++) {
            $rowData = $rows[$i];
            if (!isset($rowData[0]) || empty(trim($rowData[0])))
                continue;
            $id_cabang = (int) trim($rowData[0]);
            if ($id_cabang <= 0)
                continue;
            $nama_cabang = isset($rowData[1]) ? trim($rowData[1]) : 'Tidak Diketahui';

            for ($j = 2; $j < count($indicator_ids); $j++) {
                if (!isset($indicator_ids[$j]) || empty(trim($indicator_ids[$j])))
                    continue;
                $nama_ind = trim($indicator_ids[$j]);

                $id_indikator = 0;
                $q_match = "SELECT id_indikator FROM indikator WHERE id_cabang = $id_cabang AND tahun = $tahun AND nama_indikator = '" . mysqli_real_escape_string($koneksi, $nama_ind) . "'";
                $res_match = mysqli_query($koneksi, $q_match);
                if ($row_match = mysqli_fetch_assoc($res_match)) {
                    $id_indikator = (int) $row_match['id_indikator'];
                }

                if ($id_indikator > 0) {
                    // Jika data kosong, gagalkan keseluruhan proses import
                    if (!isset($rowData[$j]) || trim($rowData[$j]) === '') {
                        $baris = $i + 1; // Baris Excel (1-indexed)
                        $pesan_error = "Ditemukan data kosong pada Baris ke-$baris, Cabang: $nama_cabang, Indikator: $nama_ind.";
                        header("Location: ../halaman/divisi/realisasi_cabang.php?filter_tahun=$tahun&filter_bulan=$bulan&status=error_kosong&pesan_error=" . urlencode($pesan_error));
                        exit();
                    }
                }
            }
        }

        // Fase 2: Eksekusi Update/Insert
        // Mulai dari $i=2 karena baris index 0=header, index 1=baris tersembunyi duplikat header
        for ($i = 2; $i < count($rows); $i++) {
            $rowData = $rows[$i];
            if (!isset($rowData[0]) || empty(trim($rowData[0])))
                continue;
            $id_cabang = (int) trim($rowData[0]);
            if ($id_cabang <= 0)
                continue;

            for ($j = 2; $j < count($indicator_ids); $j++) {
                if (!isset($indicator_ids[$j]) || empty(trim($indicator_ids[$j])))
                    continue;
                $nama_ind = trim($indicator_ids[$j]);

                $id_indikator = 0;
                $q_match = "SELECT id_indikator FROM indikator WHERE id_cabang = $id_cabang AND tahun = $tahun AND nama_indikator = '" . mysqli_real_escape_string($koneksi, $nama_ind) . "'";
                $res_match = mysqli_query($koneksi, $q_match);
                if ($row_match = mysqli_fetch_assoc($res_match)) {
                    $id_indikator = (int) $row_match['id_indikator'];
                }

                if (isset($rowData[$j]) && trim($rowData[$j]) !== '') {
                    $val_realisasi = (float) trim($rowData[$j]);

                    if ($id_indikator > 0) {
                        // Simpan realisasi langsung tanpa syarat target wajib ada
                        $res_cek = mysqli_query($koneksi, "SELECT id_realisasi FROM realisasi WHERE id_cabang = $id_cabang AND id_indikator = $id_indikator AND tahun = $tahun AND bulan = $bulan");
                        if (mysqli_num_rows($res_cek) > 0) {
                            mysqli_query($koneksi, "UPDATE realisasi SET realisasi = $val_realisasi WHERE id_realisasi = " . mysqli_fetch_assoc($res_cek)['id_realisasi']);
                        } else {
                            mysqli_query($koneksi, "INSERT INTO realisasi (id_indikator, id_cabang, realisasi, tahun, bulan) VALUES ($id_indikator, $id_cabang, $val_realisasi, $tahun, $bulan)");
                        }
                    }
                }
            }
            update_final_score($koneksi, $id_cabang, $tahun, $bulan);
            $success_count++;
        }
        header("Location: ../halaman/divisi/realisasi_cabang.php?filter_tahun=$tahun&filter_bulan=$bulan&status=sukses_import&count=$success_count");
        exit();
    } catch (Exception $e) {
        die("Error Import Realisasi: " . $e->getMessage());
    }
}

// --- 2. Import Target dari Excel ---
elseif ($aksi_utama == 'import_target' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $tahun = isset($_POST['tahun']) ? (int) $_POST['tahun'] : (int) date('Y');
    $file = $_FILES['file']['tmp_name'];

    if (!is_uploaded_file($file)) {
        header("Location: ../halaman/divisi/target_cabang.php?filter_tahun=$tahun&pesan=gagal_upload");
        exit();
    }

    try {
        $spreadsheet = IOFactory::load($file);
        $success_count = 0;

        $sheetCount = $spreadsheet->getSheetCount();
        $monthsToProcess = min($sheetCount, 12); // Process max 12 sheets

        // Fase 1: Validasi Data Kosong
        for ($sheetIndex = 0; $sheetIndex < $monthsToProcess; $sheetIndex++) {
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $nama_bulan_sheet = $sheet->getTitle();
            $rows = $sheet->toArray(null, true, false);
            if (count($rows) < 2)
                continue; // Skip if empty sheet

            $indicator_ids = $rows[0]; // Gunakan baris pertama (yang terlihat) sbg header

            for ($i = 1; $i < count($rows); $i++) {
                $rowData = $rows[$i];
                if (!isset($rowData[0]) || empty(trim($rowData[0])))
                    continue;
                $id_cabang = (int) trim($rowData[0]);
                if ($id_cabang <= 0)
                    continue;
                $nama_cabang = isset($rowData[1]) ? trim($rowData[1]) : 'Tidak Diketahui';

                for ($j = 2; $j < count($indicator_ids); $j++) {
                    if (!isset($indicator_ids[$j]) || empty(trim($indicator_ids[$j])))
                        continue;
                    $nama_ind = trim($indicator_ids[$j]);

                    $id_indikator = 0;
                    $q_match = "SELECT id_indikator FROM indikator WHERE id_cabang = $id_cabang AND tahun = $tahun AND nama_indikator = '" . mysqli_real_escape_string($koneksi, $nama_ind) . "'";
                    $res_match = mysqli_query($koneksi, $q_match);
                    if ($row_match = mysqli_fetch_assoc($res_match)) {
                        $id_indikator = (int) $row_match['id_indikator'];
                    }

                    if ($id_indikator > 0) {
                        // Jika data kosong, gagalkan keseluruhan proses import
                        if (!isset($rowData[$j]) || trim($rowData[$j]) === '') {
                            $baris = $i + 1;
                            $pesan_error = "Ditemukan data kosong pada Bulan: $nama_bulan_sheet, Baris ke-$baris, Cabang: $nama_cabang, Indikator: $nama_ind.";
                            header("Location: ../halaman/divisi/target_cabang.php?filter_tahun=$tahun&status=error_kosong&pesan_error=" . urlencode($pesan_error));
                            exit();
                        }
                    }
                }
            }
        }

        // Fase 2: Eksekusi Update/Insert
        for ($sheetIndex = 0; $sheetIndex < $monthsToProcess; $sheetIndex++) {
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $bulan = $sheetIndex + 1;

            $rows = $sheet->toArray(null, true, false);
            if (count($rows) < 2)
                continue; // Skip if empty sheet

            $indicator_ids = $rows[0]; // Gunakan baris pertama (yang terlihat) sbg header

            for ($i = 1; $i < count($rows); $i++) {
                $rowData = $rows[$i];
                if (!isset($rowData[0]) || empty(trim($rowData[0])))
                    continue;
                $id_cabang = (int) trim($rowData[0]);
                if ($id_cabang <= 0)
                    continue;

                for ($j = 2; $j < count($indicator_ids); $j++) {
                    if (!isset($indicator_ids[$j]) || empty(trim($indicator_ids[$j])))
                        continue;
                    $nama_ind = trim($indicator_ids[$j]);

                    $id_indikator = 0;
                    $q_match = "SELECT id_indikator FROM indikator WHERE id_cabang = $id_cabang AND tahun = $tahun AND nama_indikator = '" . mysqli_real_escape_string($koneksi, $nama_ind) . "'";
                    $res_match = mysqli_query($koneksi, $q_match);
                    if ($row_match = mysqli_fetch_assoc($res_match)) {
                        $id_indikator = (int) $row_match['id_indikator'];
                    }

                    if (isset($rowData[$j]) && trim($rowData[$j]) !== '') {
                        $val_target = (float) trim($rowData[$j]);

                        if ($id_indikator > 0) {
                            $res_cek = mysqli_query($koneksi, "SELECT id_target FROM target WHERE id_cabang = $id_cabang AND id_indikator = $id_indikator AND tahun = $tahun AND bulan = $bulan");
                            if (mysqli_num_rows($res_cek) > 0) {
                                mysqli_query($koneksi, "UPDATE target SET nilai_target = $val_target WHERE id_target = " . mysqli_fetch_assoc($res_cek)['id_target']);
                            } else {
                                mysqli_query($koneksi, "INSERT INTO target (id_indikator, id_cabang, tahun, bulan, nilai_target) VALUES ($id_indikator, $id_cabang, $tahun, $bulan, $val_target)");
                            }
                            $success_count++;
                        }
                    }
                }
            }
        }
        header("Location: ../halaman/divisi/target_cabang.php?filter_tahun=$tahun&status=sukses_import&count=$success_count");
        exit();
    } catch (Exception $e) {
        die("Error Import Target: " . $e->getMessage());
    }
}

// --- 3. Download Template Realisasi ---
elseif ($aksi_utama == 'template_realisasi') {
    $tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
    $bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('m');

    $inds = [];
    $res_ind = mysqli_query($koneksi, "SELECT DISTINCT nama_indikator FROM indikator WHERE tahun = $tahun AND id_cabang = 1 ORDER BY id_indikator ASC");
    while ($row = mysqli_fetch_assoc($res_ind))
        $inds[] = $row;

    $cabs = [];
    $res_cab = mysqli_query($koneksi, "SELECT id_cabang, nama_cabang FROM cabang ORDER BY id_cabang ASC");
    while ($row = mysqli_fetch_assoc($res_cab))
        $cabs[] = $row;

    $realisasi = [];
    $res_real = mysqli_query($koneksi, "SELECT id_cabang, id_indikator, realisasi FROM realisasi WHERE tahun = $tahun AND bulan = $bulan");
    while ($row = mysqli_fetch_assoc($res_real))
        $realisasi[$row['id_cabang']][$row['id_indikator']] = $row['realisasi'];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'ID Cabang (System)')->setCellValue('B1', 'Nama Kantor Cabang');
    $sheet->getStyle('A1:B2')->getFont()->setBold(true);

    $colLetter = 'C';
    foreach ($inds as $ind) {
        $sheet->setCellValue($colLetter . '1', $ind['nama_indikator']);
        $sheet->setCellValue($colLetter . '2', $ind['nama_indikator']);
        $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        $sheet->getStyle($colLetter . '1')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $colLetter++;
    }
    $sheet->getRowDimension(2)->setVisible(false);

    $rowNum = 3;
    foreach ($cabs as $cab) {
        $sheet->setCellValue('A' . $rowNum, $cab['id_cabang'])->setCellValue('B' . $rowNum, $cab['nama_cabang']);
        $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');
        $colLetter = 'C';
        foreach ($inds as $ind) {
            $q_match = "SELECT id_indikator FROM indikator WHERE id_cabang = " . $cab['id_cabang'] . " AND tahun = $tahun AND nama_indikator = '" . mysqli_real_escape_string($koneksi, $ind['nama_indikator']) . "'";
            $res_match = mysqli_query($koneksi, $q_match);
            $match_ind = mysqli_fetch_assoc($res_match);
            $sheet->setCellValue($colLetter . $rowNum, '');
            $sheet->getStyle($colLetter . $rowNum)->getNumberFormat()->setFormatCode('0');
            $colLetter++;
        }
        $rowNum++;
    }

    // Add thin borders to the entire table to make it a normal, clean table
    $highestCol = $sheet->getHighestColumn();
    $highestRow = $sheet->getHighestRow();
    $sheet->getStyle('A1:' . $highestCol . $highestRow)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FFCCCCCC'],
            ],
        ],
    ]);

    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_realisasi_' . $tahun . '_' . $bulan . '.xlsx"');
    if (ob_get_length())
        ob_end_clean();
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// --- 4. Download Template Target ---
elseif ($aksi_utama == 'template_target') {
    $tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');

    $inds = [];
    $res_ind = mysqli_query($koneksi, "SELECT DISTINCT nama_indikator FROM indikator WHERE tahun = $tahun AND id_cabang = 1 ORDER BY id_indikator ASC");
    while ($row = mysqli_fetch_assoc($res_ind))
        $inds[] = $row;

    $cabs = [];
    $res_cab = mysqli_query($koneksi, "SELECT id_cabang, nama_cabang FROM cabang ORDER BY id_cabang ASC");
    while ($row = mysqli_fetch_assoc($res_cab))
        $cabs[] = $row;

    $targets = [];
    $res_tar = mysqli_query($koneksi, "SELECT id_cabang, id_indikator, bulan, nilai_target FROM target WHERE tahun = $tahun AND bulan IS NOT NULL");
    while ($row = mysqli_fetch_assoc($res_tar)) {
        $targets[$row['bulan']][$row['id_cabang']][$row['id_indikator']] = $row['nilai_target'];
    }

    $spreadsheet = new Spreadsheet();
    $nama_bulan_list = get_nama_bulan();

    for ($m = 1; $m <= 12; $m++) {
        if ($m == 1) {
            $sheet = $spreadsheet->getActiveSheet();
        } else {
            $sheet = $spreadsheet->createSheet();
        }
        $sheet->setTitle($nama_bulan_list[$m]);

        $sheet->setCellValue('A1', 'ID Cabang (System)')->setCellValue('B1', 'Nama Kantor Cabang');
        $sheet->getStyle('A1:B2')->getFont()->setBold(true);

        $colLetter = 'C';
        foreach ($inds as $ind) {
            $sheet->setCellValue($colLetter . '1', $ind['nama_indikator']);
            $sheet->setCellValue($colLetter . '2', $ind['nama_indikator']);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
            $sheet->getStyle($colLetter . '1')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $colLetter++;
        }
        $sheet->getRowDimension(2)->setVisible(false);

        $rowNum = 3;
        foreach ($cabs as $cab) {
            $sheet->setCellValue('A' . $rowNum, $cab['id_cabang'])->setCellValue('B' . $rowNum, $cab['nama_cabang']);
            $sheet->getStyle('A' . $rowNum . ':B' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');
            $colLetter = 'C';
            foreach ($inds as $ind) {
                $q_match = "SELECT id_indikator FROM indikator WHERE id_cabang = " . $cab['id_cabang'] . " AND tahun = $tahun AND nama_indikator = '" . mysqli_real_escape_string($koneksi, $ind['nama_indikator']) . "'";
                $res_match = mysqli_query($koneksi, $q_match);
                $match_ind = mysqli_fetch_assoc($res_match);
                $sheet->setCellValue($colLetter . $rowNum, '');
                $sheet->getStyle($colLetter . $rowNum)->getNumberFormat()->setFormatCode('0.00');
                $colLetter++;
            }
            $rowNum++;
        }

        $highestCol = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 3) {
            $sheet->getStyle('A1:' . $highestCol . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFCCCCCC'],
                    ],
                ],
            ]);
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    // Set active sheet back to January
    $spreadsheet->setActiveSheetIndex(0);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_target_bulanan_' . $tahun . '.xlsx"');
    if (ob_get_length())
        ob_end_clean();
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// --- 5. Download Template Profil Pinca ---
elseif ($aksi_utama == 'template_profil') {
    $tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

    $cabs = [];
    $res_cab = mysqli_query($koneksi, "SELECT id_cabang, kode_cabang, nama_cabang FROM cabang ORDER BY id_cabang ASC");
    while ($row = mysqli_fetch_assoc($res_cab)) $cabs[] = $row;

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Profil Pinca $tahun");

    // Header
    $headers = [
        'A' => 'ID Cabang (Sistem)',
        'B' => 'Kode Cabang',
        'C' => 'Nama Cabang',
        'D' => 'Nama Pemimpin Cabang',
        'E' => 'NIP',
        'F' => 'Nomor HP',
        'G' => 'Pangkat (AVP/MGR/AMGR/SMGR/KABAG/WAKADIV/PIPINCAPEM)',
        'H' => 'Direktorat (Bisnis/Operasional)',
        'I' => 'Level KIP (Utama/Madya/Muda)',
        'J' => 'Tgl Menjabat (YYYY-MM-DD)',
        'K' => 'Tgl Masuk (YYYY-MM-DD)',
        'L' => 'Tgl Pengangkatan (YYYY-MM-DD)',
        'M' => 'Atasan Langsung',
        'N' => 'Atasan Kedua (Dir. Terkait)',
        'O' => 'Direktur Utama'
    ];

    foreach ($headers as $col => $title) {
        $sheet->setCellValue($col . '1', $title);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
        $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $rowNum = 2;
    foreach ($cabs as $cab) {
        $sheet->setCellValue('A' . $rowNum, $cab['id_cabang']);
        $sheet->setCellValue('B' . $rowNum, $cab['kode_cabang']);
        $sheet->setCellValue('C' . $rowNum, $cab['nama_cabang']);

        $sheet->getStyle('A' . $rowNum . ':C' . $rowNum)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F0F0');

        // Cek jika sudah ada data pinca
        $q_pinca = "SELECT * FROM users WHERE id_cabang = " . $cab['id_cabang'] . " AND jabatan = 'Pemimpin Cabang' AND tahun = $tahun LIMIT 1";
        $res_pinca = mysqli_query($koneksi, $q_pinca);
        $pinca = ($res_pinca && mysqli_num_rows($res_pinca) > 0) ? mysqli_fetch_assoc($res_pinca) : null;

        if ($pinca) {
            $sheet->setCellValue('D' . $rowNum, $pinca['nama']);
            $sheet->setCellValueExplicit('E' . $rowNum, $pinca['NIP'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $rowNum, $pinca['no_hp'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $rowNum, $pinca['pangkat']);
            $sheet->setCellValue('H' . $rowNum, $pinca['direktorat']);
            $sheet->setCellValue('I' . $rowNum, $pinca['level_kip']);
            $sheet->setCellValue('J' . $rowNum, $pinca['tanggal_menjabat']);
            $sheet->setCellValue('K' . $rowNum, $pinca['tanggal_masuk_kerja']);
            $sheet->setCellValue('L' . $rowNum, $pinca['tanggal_pengangkatan_terakhir']);
            $sheet->setCellValue('M' . $rowNum, $pinca['atasan_langsung']);
            $sheet->setCellValue('N' . $rowNum, $pinca['atasan_dari_atasan_langsung']);
            $sheet->setCellValue('O' . $rowNum, $pinca['direktur_utama']);
        }

        $rowNum++;
    }

    $highestRow = $sheet->getHighestRow();
    $sheet->getStyle('A1:O' . $highestRow)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FFCCCCCC'],
            ],
        ],
    ]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_profil_pinca_' . $tahun . '.xlsx"');
    if (ob_get_length()) ob_end_clean();
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

// --- 6. Import Profil Pinca ---
elseif ($aksi_utama == 'import_profil' && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $tahun = isset($_POST['tahun']) ? (int)$_POST['tahun'] : (int)date('Y');
    $file = $_FILES['file']['tmp_name'];

    if (!is_uploaded_file($file)) {
        header("Location: ../halaman/divisi/daftar_pinca.php?filter_tahun=$tahun&status=gagal_upload");
        exit();
    }

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        $success_count = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $rowData = $rows[$i];
            if (!isset($rowData[0]) || empty(trim($rowData[0]))) continue;

            $id_cabang = (int)trim($rowData[0]);
            if ($id_cabang <= 0) continue;

            $nama = mysqli_real_escape_string($koneksi, $rowData[3] ?? '');
            if (empty($nama)) continue; // Wajib ada nama pinca untuk insert/update

            $nip = mysqli_real_escape_string($koneksi, $rowData[4] ?? '');
            $no_hp = mysqli_real_escape_string($koneksi, $rowData[5] ?? '');
            $pangkat = mysqli_real_escape_string($koneksi, $rowData[6] ?? '');
            $direktorat = mysqli_real_escape_string($koneksi, $rowData[7] ?? '');
            $level_kip = mysqli_real_escape_string($koneksi, $rowData[8] ?? '');
            $tgl_menjabat = mysqli_real_escape_string($koneksi, $rowData[9] ?? '');
            $tgl_masuk = mysqli_real_escape_string($koneksi, $rowData[10] ?? '');
            $tgl_pengangkatan = mysqli_real_escape_string($koneksi, $rowData[11] ?? '');
            $atasan_langsung = mysqli_real_escape_string($koneksi, $rowData[12] ?? '');
            $atasan_kedua = mysqli_real_escape_string($koneksi, $rowData[13] ?? '');
            $dir_utama = mysqli_real_escape_string($koneksi, $rowData[14] ?? '');

            // Format ulang tanggal Excel yang mungkin dibaca sebagai format aneh
            $format_date = function ($d) {
                if (empty($d)) return '';
                if (is_numeric($d)) {
                    $unix_date = (\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($d));
                    return date("Y-m-d", $unix_date);
                }
                return $d;
            };

            $tgl_menjabat = $format_date($tgl_menjabat);
            $tgl_masuk = $format_date($tgl_masuk);
            $tgl_pengangkatan = $format_date($tgl_pengangkatan);

            // Cek apakah user sudah ada
            $q_cek = "SELECT id_user FROM users WHERE id_cabang = $id_cabang AND jabatan = 'Pemimpin Cabang' AND tahun = $tahun";
            $res_cek = mysqli_query($koneksi, $q_cek);

            if (mysqli_num_rows($res_cek) > 0) {
                $row_user = mysqli_fetch_assoc($res_cek);
                $id_user = $row_user['id_user'];
                $q_upd = "UPDATE users SET 
                            nama = '$nama', NIP = '$nip', no_hp = '$no_hp', pangkat = '$pangkat', 
                            direktorat = '$direktorat', level_kip = '$level_kip', 
                            tanggal_menjabat = '$tgl_menjabat', tanggal_masuk_kerja = '$tgl_masuk', 
                            tanggal_pengangkatan_terakhir = '$tgl_pengangkatan', atasan_langsung = '$atasan_langsung', 
                            atasan_dari_atasan_langsung = '$atasan_kedua', direktur_utama = '$dir_utama' 
                          WHERE id_user = $id_user";
                mysqli_query($koneksi, $q_upd);
                $success_count++;
            } else {
                // Cek apakah cabang ini punya user di tahun-tahun sebelumnya
                $q_prev = "SELECT username, password FROM users WHERE id_cabang = $id_cabang AND jabatan = 'Pemimpin Cabang' ORDER BY tahun DESC LIMIT 1";
                $res_prev = mysqli_query($koneksi, $q_prev);

                if (mysqli_num_rows($res_prev) > 0) {
                    $row_prev = mysqli_fetch_assoc($res_prev);
                    $username_baru = $row_prev['username'];
                    $pass_default = $row_prev['password'];
                } else {
                    // Buat username baru jika belum pernah ada sama sekali
                    $q_cab_nama = "SELECT nama_cabang FROM cabang WHERE id_cabang = $id_cabang";
                    $res_cab_nama = mysqli_query($koneksi, $q_cab_nama);
                    $nama_cab_db = "CabangBaru";
                    if (mysqli_num_rows($res_cab_nama) > 0) {
                        $nama_cab_db = mysqli_fetch_assoc($res_cab_nama)['nama_cabang'];
                    }
                    $username_baru = "Pinca_" . str_replace(' ', '', $nama_cab_db);

                    // Pastikan unik di tahun ini (meski langka bentrok)
                    $q_dup = "SELECT id_user FROM users WHERE username = '$username_baru' AND tahun = $tahun";
                    if (mysqli_num_rows(mysqli_query($koneksi, $q_dup)) > 0) {
                        $username_baru .= "_" . time();
                    }

                    $pass_default = password_hash('12345', PASSWORD_DEFAULT);
                }

                $q_ins = "INSERT INTO users (id_cabang, username, password, jabatan, nama, NIP, no_hp, pangkat, direktorat, level_kip, tanggal_menjabat, tanggal_masuk_kerja, tanggal_pengangkatan_terakhir, atasan_langsung, atasan_dari_atasan_langsung, direktur_utama, tahun) 
                          VALUES ($id_cabang, '$username_baru', '$pass_default', 'Pemimpin Cabang', '$nama', '$nip', '$no_hp', '$pangkat', '$direktorat', '$level_kip', '$tgl_menjabat', '$tgl_masuk', '$tgl_pengangkatan', '$atasan_langsung', '$atasan_kedua', '$dir_utama', $tahun)";
                mysqli_query($koneksi, $q_ins);
                $success_count++;
            }
        }

        header("Location: ../halaman/divisi/daftar_pinca.php?filter_tahun=$tahun&status=sukses_import&count=$success_count");
        exit();
    } catch (Exception $e) {
        die("Error Import Profil: " . $e->getMessage());
    }
}

// Default Redirect
else {
    header("Location: ../index.php");
    exit();
}
