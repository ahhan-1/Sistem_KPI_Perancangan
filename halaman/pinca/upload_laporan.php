<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Pemimpin Cabang') {
    header('Location: ../../index.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$id_cabang = $_SESSION['id_cabang'];
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_aktif = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');
$nama_bulan_long = [
    1 => "Januari",
    2 => "Februari",
    3 => "Maret",
    4 => "April",
    5 => "Mei",
    6 => "Juni",
    7 => "Juli",
    8 => "Agustus",
    9 => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];

// --- 1. Ambil Laporan Periode Ini ---
$q_app = "SELECT * FROM laporan_persetujuan WHERE id_cabang = $id_cabang AND tahun = $tahun_aktif AND bulan = $bulan_aktif ORDER BY jenis_laporan ASC";
$res_app = mysqli_query($koneksi, $q_app);
$app_list = [];
while ($row = mysqli_fetch_assoc($res_app)) {
    $app_list[$row['jenis_laporan']] = $row;
}

// --- 2. Cek Status Penilaian Akhir (Skor KPI & Kompetensi > 0) ---
$q_nilai = "SELECT * FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun_aktif AND bulan = $bulan_aktif";
$res_nilai = mysqli_query($koneksi, $q_nilai);
$na = mysqli_fetch_assoc($res_nilai);

$nilai_kpi = (float)($na['nilai_akhir_kpi'] ?? 0);
$nilai_kompetensi = (float)($na['nilai_kompetensi'] ?? 0);
$nilai_akhir_kinerja = (float)($na['nilai_akhir_kinerja'] ?? 0);

// Penilaian akhir siap jika KPI > 0 DAN Kompetensi > 0 DAN nilai akhir kinerja > 0
$is_nilai_ada = ($nilai_kpi > 0 && $nilai_kompetensi > 0 && $nilai_akhir_kinerja > 0);

// --- Guard: Cek Profil Lengkap ---

$title = "Upload Laporan Final";

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - KPI BSB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <style>
        .status-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            text-align: left;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Upload Laporan Final</h1>
                    <p>Setor laporan lengkap atau kontrak kerja yang telah ditandatangani.</p>
                </div>
                <div class="filter-periode-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="month" id="periodePicker" class="input-month"
                        value="<?php echo $tahun_aktif . '-' . str_pad($bulan_aktif, 2, '0', STR_PAD_LEFT); ?>"
                        onchange="updateFilter()">
                </div>
            </div>

            <div>
                <!-- --- 4. Form Upload --- -->
                <div class="kartu" style="padding: 40px; margin-bottom: 30px;">
                    <div style="margin-bottom: 30px; border-bottom: 2px solid #edf2f7; padding-bottom: 15px;">
                        <h2 style="color: #2b5797; margin: 0; font-size: 1.5em;">
                            <i class="fas fa-file-upload"></i> Form Upload Dokumen Baru
                        </h2>
                        <p style="color: #718096; margin-top: 5px; font-size: 0.95em;">Silakan pilih jenis dokumen dan lampirkan file PDF yang telah ditandatangani.</p>
                    </div>

                    <?php if (!$is_nilai_ada): ?>
                        <div style="background: #fff5f5; color: #c53030; padding: 25px; border-radius: 12px; border: 1px solid #feb2b2; display: flex; align-items: center; gap: 20px;">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                            <div>
                                <strong style="font-size: 1.1em;">Data Penilaian Akhir Belum Diterbitkan!</strong>
                                <p style="margin-top: 5px; font-size: 0.9em;">Halaman upload laporan ini hanya dapat diakses setelah <strong>Penilaian Akhir</strong> untuk periode <strong><?php echo $nama_bulan_long[$bulan_aktif] . ' ' . $tahun_aktif; ?></strong> diterbitkan oleh Pinca (KPI Seksi A dan Kompetensi Seksi D harus sudah diisi dan disimpan).</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="max-width: 600px;">
                            <?php include_once "komponen_upload.php"; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- --- 5. Daftar Status Laporan --- -->
                <?php if ($is_nilai_ada): ?>
                    <div class="kartu" style="padding: 40px;">
                        <div style="margin-bottom: 30px; border-bottom: 2px solid #edf2f7; padding-bottom: 15px;">
                            <h2 style="color: #2b5797; margin: 0; font-size: 1.5em;">
                                <i class="fas fa-tasks"></i> Status Approval Dokumen
                            </h2>
                            <p style="color: #718096; margin-top: 5px; font-size: 0.95em;">Pantau status persetujuan dari Divisi BKU secara real-time.</p>
                        </div>

                        <div style="background: white; border-radius: 15px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                            <table class="tabel-kpi" style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #2b5797; color: white;">
                                    <tr>
                                        <th style="padding: 15px 20px; text-align: left;">Jenis Dokumen</th>
                                        <th style="padding: 15px 20px; text-align: center;">Tanggal Upload</th>
                                        <th style="padding: 15px 20px; text-align: center;">Status Approval</th>
                                        <th style="padding: 15px 20px; text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $docs = ['laporan' => 'Laporan Lengkap (Bulanan)', 'kontrak' => 'Kontrak Kerja (Tahunan)'];
                                    foreach ($docs as $key => $label):
                                        $data = $app_list[$key] ?? null;
                                    ?>
                                        <tr style="border-bottom: 1px solid #edf2f7;">
                                            <td style="padding: 20px; font-weight: 700; color: #2d3748; font-size: 1.05em;"><?php echo $label; ?></td>
                                            <td style="padding: 20px; text-align: center; color: #718096; font-size: 0.9em;">
                                                <?php echo ($data) ? date('d M Y, H:i', strtotime($data['tgl_unggah'])) : '-'; ?>
                                            </td>
                                            <td style="padding: 20px; text-align: center;">
                                                <?php if ($data): ?>
                                                    <?php
                                                    $st = $data['status'];
                                                    $bg = '#fef3c7';
                                                    $fg = '#92400e';
                                                    $lbl = 'MENUNGGU REVIEW';
                                                    if ($st == 'disetujui') {
                                                        $bg = '#d1fae5';
                                                        $fg = '#065f46';
                                                        $lbl = 'DISETUJUI';
                                                    } elseif ($st == 'ditolak') {
                                                        $bg = '#fee2e2';
                                                        $fg = '#991b1b';
                                                        $lbl = 'DITOLAK';
                                                    }
                                                    ?>
                                                    <span style="background: <?php echo $bg; ?>; color: <?php echo $fg; ?>; padding: 6px 15px; border-radius: 20px; font-size: 0.75em; font-weight: 800; border: 1px solid <?php echo $fg; ?>40;">
                                                        <?php echo $lbl; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #cbd5e0; font-style: italic;">Belum Ada Data</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 20px; text-align: center;">
                                                <?php if ($data): ?>
                                                    <a href="../../berkas/laporan/<?php echo rawurlencode($data['berkas_pdf']); ?>" target="_blank" class="tombol" style="background: #edf2f7; color: #2b5797; padding: 8px 15px; font-size: 0.85em; border-radius: 6px;">
                                                        <i class="fas fa-file-pdf"></i> Lihat File
                                                    </a>
                                                <?php else: ?>
                                                    <span style="color: #eee;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if ($data && $data['status'] == 'ditolak'): ?>
                                            <tr style="background: #fffafa;">
                                                <td colspan="4" style="padding: 15px 20px;">
                                                    <div style="background: #fee2e2; border-left: 5px solid #ef4444; padding: 15px; border-radius: 8px; color: #991b1b; font-size: 0.9em;">
                                                        <strong><i class="fas fa-comment-dots"></i> Alasan Penolakan:</strong><br>
                                                        <?php echo $data['catatan_bku']; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function updateFilter() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const [thn, bln] = val.split('-');
            window.location.href = 'upload_laporan.php?filter_tahun=' + thn + '&filter_bulan=' + parseInt(bln);
        }
    </script>
</body>

</html>