<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Pemimpin Cabang' && $_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$id_user = (int)(isset($_GET['id_user']) ? $_GET['id_user'] : ($_SESSION['id_user'] ?? 0));
$id_cabang = (int)(isset($_GET['id_cabang']) ? $_GET['id_cabang'] : ($_SESSION['id_cabang'] ?? 0));

$tahun = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan_long = get_nama_bulan();

// --- 1. Ambil Profil User ---
$q_user = "SELECT u.*, c.nama_cabang 
           FROM users u 
           JOIN cabang c ON u.id_cabang = c.id_cabang
           WHERE u.id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

// --- Guard: Cek Profil Lengkap ---

// --- 2. Ambil Data Nilai Akhir ---
$q_na = "SELECT * FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun AND bulan = $bulan";
$res_na = mysqli_query($koneksi, $q_na);
$na = mysqli_fetch_assoc($res_na);

$id_nilai = $na['id_nilai'] ?? 0;
$nilai_kinerja = (float)($na['nilai_akhir_kpi'] ?? 0);
$nilai_perilaku = (float)($na['nilai_kompetensi'] ?? 0);
$nilai_tambahan = (float)($na['nilai_tambahan_total'] ?? 0);
$nilai_pengurang = (float)($na['nilai_pengurang_total'] ?? 0);
$nilai_akhir_total = (float)($na['nilai_akhir_kinerja'] ?? 0);
$indeks_akhir = $na['indeks_nilai_akhir'] ?? 'F';

// --- Syarat Tampil: KPI (A) dan Kompetensi (D) harus > 0 ---
$is_kpi_siap = ($nilai_kinerja > 0);
$is_kompetensi_siap = ($nilai_perilaku > 0);
$tampilkan_akhir = ($is_kpi_siap && $is_kompetensi_siap);

// --- 3. Ambil Data Resume ---
$resume_data = [
    'pertimbangan' => [],
    'rekomendasi' => '',
    'pelatihan' => []
];

if ($id_nilai) {
    $q_res = "SELECT pertimbangan_khusus, rekomendasi_penilai FROM nilai_akhir WHERE id_nilai = $id_nilai";
    $res_res = mysqli_query($koneksi, $q_res);
    if ($row_res = mysqli_fetch_assoc($res_res)) {
        $resume_data['pertimbangan'] = explode(',', $row_res['pertimbangan_khusus'] ?? '');
        $resume_data['rekomendasi'] = $row_res['rekomendasi_penilai'] ?? '';
    }

    $q_pel = "SELECT nama_bidang, nama_pelatihan FROM riwayat_pelatihan WHERE id_nilai = $id_nilai";
    $res_pel = mysqli_query($koneksi, $q_pel);
    while ($rp = mysqli_fetch_assoc($res_pel)) {
        $resume_data['pelatihan'][$rp['nama_bidang']] = $rp['nama_pelatihan'];
    }
}

$weighted_kinerja = $nilai_kinerja * 0.85;
$weighted_perilaku = $nilai_perilaku * 0.15;
$nilai_akhir = $nilai_akhir_total;
$indeks = $indeks_akhir;
$indeks_ket = calculate_indeks_label($nilai_akhir);

$title = "Penilaian Akhir (Resume)";
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
        .input-kontrol {
            padding: 8px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
        }

        .resume-sheet {
            background: white;
            padding: 40px;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            color: #000;
        }

        @media print {

            .no-print,
            .sidebar {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .resume-sheet {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
            }
        }

        .resume-title {
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 20px;
            text-transform: capitalize;
        }

        .excel-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .excel-table th,
        .excel-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            vertical-align: middle;
        }

        .excel-header {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .bg-blue-light {
            background-color: #d9e1f2;
        }

        .bg-green-light {
            background-color: #c6efce;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .value-box {
            font-weight: bold;
            text-align: center;
        }

        .index-box {
            background-color: #c6efce;
            font-weight: bold;
            text-align: center;
            font-size: 1.1em;
        }

        .legend-table {
            font-size: 0.9em;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .legend-table td {
            padding: 2px 10px;
            border: none;
        }

        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
            margin-top: 25px;
            display: block;
        }

        .catatan-section {
            margin-bottom: 30px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            border: 1px solid #000;
            display: inline-block;
            vertical-align: middle;
            background: #fff;
        }

        .signature-section {
            margin-top: 50px;
            width: 100%;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            text-align: center;
            margin-top: 20px;
        }

        .signature-box {
            padding: 0 10px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 80px;
        }

        .signature-role {
            font-size: 0.9em;
        }

        .input-plain {
            border: none;
            width: 100%;
            font-family: inherit;
            font-size: inherit;
            outline: none;
            background: transparent;
        }
    </style>
</head>

<body>

    <?php $is_iframe = isset($_GET['iframe']) && (int)$_GET['iframe'] == 1; ?>
    <div class="dashboard-container">
        <?php if (!$is_iframe) include "../komponen/sidebar.php"; ?>

        <div class="main-content" <?php if ($is_iframe) echo 'style="margin-left: 0 !important; width: 100% !important; padding: 10px !important;"'; ?>>
            <?php if (!$is_iframe): ?>
                <div class="halaman-header no-print">
                    <h1>Resume Penilaian Akhir</h1>
                    <p>Ringkasan perolehan skor KPI dan Kompetensi Cabang <?php echo $u['nama_cabang'] ?? '-'; ?>.</p>
                </div>

                <!-- FILTER NAVIGASI (SELALU NONGOL) -->
                <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <?php if ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi'): ?>
                            <a href="../<?= ($_SESSION['jabatan'] == 'Direksi') ? 'direksi' : 'divisi'; ?>/monitoring_cabang.php" class="tombol" style="background: #4a5568; color: white; padding: 8px 15px;">
                                <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
                            </a>
                        <?php endif; ?>

                        <div class="filter-periode-container">
                            <i class="fas fa-calendar-check"></i>
                            <input type="month" id="periodePicker" class="input-month"
                                value="<?php echo $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT); ?>"
                                onchange="updateNav()">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <?php if ($nilai_akhir > 0): ?>
                            <button onclick="cetakLaporanLengkap()" class="tombol" style="background: #2b5797; color: white;">
                                <i class="fas fa-file-archive"></i> Cetak Laporan Lengkap
                            </button>
                            <button onclick="cetakPDF()" class="tombol" style="background: #edf2f7; color: #4a5568;">
                                <i class="fas fa-print"></i> Cetak Resume
                            </button>
                            <?php if ($_SESSION['jabatan'] == 'Pemimpin Cabang'): ?>
                                <button type="submit" form="formResume" class="tombol tombol-utama"><i class="fas fa-save"></i> Simpan Resume</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="kartu" style="background: #e6fffa; border: 1px solid #b2f5ea; color: #234e52; margin: 0 0 20px 0; padding: 15px; width: 100%;">
                    <i class="fas fa-circle-check"></i> <strong>Berhasil!</strong> Ringkasan penilaian periode <?php echo $nama_bulan_long[$bulan] . " " . $tahun; ?> telah disimpan.
                </div>
            <?php endif; ?>

            <?php if (!$tampilkan_akhir): ?>
                <div class="kartu" style="text-align: center; padding: 80px 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 15px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <i class="fas fa-file-shield" style="font-size: 4em; color: #cbd5e0; margin-bottom: 20px;"></i>
                    <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Laporan Akhir Belum Siap</h3>

                    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e0; text-align: left;">
                        <p style="color: #718096; font-size: 1em; margin-bottom: 15px;">Resume penilaian untuk periode <strong><?php echo $nama_bulan_long[$bulan] . ' ' . $tahun; ?></strong> baru akan diterbitkan jika syarat berikut terpenuhi:</p>
                        <ul style="list-style: none; padding: 0; margin: 0; color: #4a5568; font-size: 0.95em;">
                            <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <i class="fas <?php echo $is_kpi_siap ? 'fa-check-circle' : 'fa-times-circle'; ?>" style="color: <?php echo $is_kpi_siap ? '#38a169' : '#e53e3e'; ?>;"></i>
                                Skor Capaian KPI (Seksi A) - <?php echo $is_kpi_siap ? '<strong>Sudah Siap</strong>' : 'Belum Ada'; ?>
                            </li>
                            <li style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas <?php echo $is_kompetensi_siap ? 'fa-check-circle' : 'fa-times-circle'; ?>" style="color: <?php echo $is_kompetensi_siap ? '#38a169' : '#e53e3e'; ?>;"></i>
                                Penilaian Kompetensi (Seksi D) - <?php echo $is_kompetensi_siap ? '<strong>Sudah Siap</strong>' : 'Belum Diisi Pinca'; ?>
                            </li>
                        </ul>
                    </div>

                    <?php if ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi'): ?>
                        <p style="color: #a0aec0; font-size: 0.9em; margin-top: 20px;">
                            <i class="fas fa-info-circle"></i> Pantau progres pengisian pada menu monitoring secara berkala.
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="resume-sheet">
                    <form id="formResume" action="../../fungsi/kpi_manajemen_penilaian.php" method="POST">
                        <input type="hidden" name="aksi_utama" value="proses_akhir">
                        <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
                        <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">
                        <input type="hidden" name="id_nilai" value="<?php echo $id_nilai; ?>">

                        <?php
                        // Mapping variabel agar sesuai dengan yang diminta template
                        $is_web = true;
                        $pertimbangan = $resume_data['pertimbangan'];
                        $rekomendasi = $resume_data['rekomendasi'];
                        $pelatihan = $resume_data['pelatihan'];
                        $tgl_pbg = (ucwords(strtolower($u['nama_cabang'] ?? '-'))) . ", " . $nama_bulan_long[$bulan] . " " . $tahun;

                        include __DIR__ . "/templates/tpl_resume.php";
                        echo $html_r;
                        ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <script src="../../aset/js/app.js"></script>
        <script>
            function cetakPDF() {
                const val = document.getElementById('periodePicker').value;
                if (!val) return;
                const parts = val.split('-');
                const thn = parts[0];
                const bln = parseInt(parts[1]);
                const urlParams = new URLSearchParams(window.location.search);
                const idUser = urlParams.get('id_user');
                const idCabang = urlParams.get('id_cabang');

                let url = `cetak/cetak_browser.php?mod=r&filter_tahun=${thn}&filter_bulan=${bln}`;
                if (idUser) url += `&id_user=${idUser}`;
                if (idCabang) url += `&id_cabang=${idCabang}`;

                // Gunakan Iframe Tersembunyi untuk Direct Print
                printViaIframe(url);
            }

            function cetakLaporanLengkap() {
                const val = document.getElementById('periodePicker').value;
                if (!val) return;
                const parts = val.split('-');
                const thn = parts[0];
                const bln = parseInt(parts[1]);
                const urlParams = new URLSearchParams(window.location.search);
                const idUser = urlParams.get('id_user');
                const idCabang = urlParams.get('id_cabang');

                let url = `cetak/cetak_browser.php?mod=all&filter_tahun=${thn}&filter_bulan=${bln}`;
                if (idUser) url += `&id_user=${idUser}`;
                if (idCabang) url += `&id_cabang=${idCabang}`;

                printViaIframe(url);
            }

            function updateNav() {
                const val = document.getElementById('periodePicker').value;
                if (!val) return;
                const parts = val.split('-');
                const thn = parts[0];
                const bln = parseInt(parts[1]);
                const urlParams = new URLSearchParams(window.location.search);
                const idUser = urlParams.get('id_user');
                const idCabang = urlParams.get('id_cabang');

                let url = `penilaian_akhir.php?filter_tahun=${thn}&filter_bulan=${bln}`;
                if (idUser) url += `&id_user=${idUser}`;
                if (idCabang) url += `&id_cabang=${idCabang}`;

                window.location.href = url;
            }
        </script>

</body>

</html>