<?php
session_start();
require_once "../../../fungsi/koneksi.php";
require_once "../../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Pemimpin Cabang' && $_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    exit('Akses ditolak');
}

// Params
if ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') {
    $id_user = (int)$_GET['id_user'];
    $id_cabang = (int)$_GET['id_cabang'];
} else {
    $id_user = $_SESSION['id_user'];
    $id_cabang = $_SESSION['id_cabang'];
}

$tahun = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');
$multiplier = $bulan / 12;
$nama_bulan_long = get_nama_bulan();

// Persiapan Variabel Umum untuk Template
$label_periode = ($bulan == 1)
    ? $nama_bulan_long[$bulan] . " " . $tahun
    : "Januari - " . $nama_bulan_long[$bulan] . " " . $tahun;
$u = get_pinca_profile($koneksi, $id_user, $tahun);
$q_cab = "SELECT nama_cabang FROM cabang WHERE id_cabang = " . (int)$u['id_cabang'];
$res_cab = mysqli_query($koneksi, $q_cab);
$cab = mysqli_fetch_assoc($res_cab);
$u['nama_cabang'] = $cab['nama_cabang'] ?? '';

$cabang_display = $u['nama_cabang'] ?? '-';
if (stripos($cabang_display, 'Kapten A. Rivai') !== false) {
    $cabang_display = 'Palembang';
}
$cabang_display = ucwords(strtolower($cabang_display));
$tgl_pbg = $cabang_display . ", " . $nama_bulan_long[$bulan] . " " . $tahun;

// Ambil data nilai akhir lengkap untuk template
$q_na = "SELECT * FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun AND bulan = $bulan";
$na = mysqli_fetch_assoc(mysqli_query($koneksi, $q_na));
$id_nilai = $na['id_nilai'] ?? 0;

// Inisialisasi Variabel untuk Template Resume (Agar tidak undefined)
$nilai_kinerja    = (float)($na['nilai_akhir_kpi'] ?? 0);
$nilai_perilaku   = (float)($na['nilai_kompetensi'] ?? 0);
$nilai_tambahan   = (float)($na['nilai_tambahan_total'] ?? 0);
$nilai_pengurang  = (float)($na['nilai_pengurang_total'] ?? 0);
$nilai_akhir_total = (float)($na['nilai_akhir_kinerja'] ?? 0);
$indeks_akhir     = $na['indeks_nilai_akhir'] ?? '-';

$weighted_kinerja  = $nilai_kinerja * 0.85;
$weighted_perilaku = $nilai_perilaku * 0.15;

// Data Resume
$pertimbangan = explode(',', $na['pertimbangan_khusus'] ?? '');
$rekomendasi  = $na['rekomendasi_penilai'] ?? '';

$pelatihan = [];
if ($id_nilai) {
    $q_pel = "SELECT nama_bidang, nama_pelatihan FROM riwayat_pelatihan WHERE id_nilai = $id_nilai";
    $res_pel = mysqli_query($koneksi, $q_pel);
    while ($rp = mysqli_fetch_assoc($res_pel)) {
        $pelatihan[$rp['nama_bidang']] = $rp['nama_pelatihan'];
    }
}

// Determinas Modul yang dicetak
$mod = $_GET['mod'] ?? 'all';
$html_kk = $html_f = $html_k = $html_r = "";

// Buat label jenis dokumen untuk title (sebelum include agar tetap dalam 1 blok PHP)
$mod_labels = [
    'kk' => 'Kertas Kerja',
    'f'  => 'Formulir Penilaian',
    'k'  => 'Penilaian Kompetensi',
    'r'  => 'Resume Akhir',
    'all' => 'Laporan Lengkap KPI'
];
$mod_label_title = $mod_labels[$mod] ?? 'Laporan KPI';
$cabang_title = isset($u['nama_cabang']) ? ucwords(strtolower($u['nama_cabang'])) : '';
if (stripos($cabang_title, 'Kapten A. Rivai') !== false) {
    $cabang_title = 'Palembang';
}
$title_pdf = $mod_label_title . ' - ' . $cabang_title . ' - ' . $nama_bulan_long[$bulan] . ' ' . $tahun;

// LOAD TEMPLATES
if ($mod == 'all' || $mod == 'kk') include "../templates/tpl_kertas_kerja.php";
if ($mod == 'all' || $mod == 'f')  include "../templates/tpl_formulir.php";
if ($mod == 'all' || $mod == 'k')  include "../templates/tpl_kompetensi.php";
if ($mod == 'all' || $mod == 'r')  include "../templates/tpl_resume.php";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title_pdf) ?></title>
    <link rel="stylesheet" href="../../../aset/css/gaya.css?v=<?= time() ?>">
    <style>
        /* Sembunyikan tampilan layar agar terasa langsung ke dialog print */
        body {
            background: white;
            margin: 0;
            padding: 0;
        }

        .print-container {
            box-shadow: none;
            margin: 0 auto;
            padding-top: 30px;
            padding-bottom: 20px;
        }
    </style>
</head>

<body>

    <!-- 1. MODUL KERTAS KERJA -->
    <?php if ($mod == 'all' || $mod == 'kk'): ?>
        <div class="print-container">
            <?= $html_kk ?>
        </div>
    <?php endif; ?>

    <!-- 2. MODUL FORMULIR PENILAIAN -->
    <?php if ($mod == 'all' || $mod == 'f'): ?>
        <div class="print-container">
            <?= $html_f ?>
        </div>
    <?php endif; ?>

    <!-- 3. MODUL KOMPETENSI -->
    <?php if ($mod == 'all' || $mod == 'k'): ?>
        <div class="print-container">
            <?= $html_k ?>
        </div>
    <?php endif; ?>

    <!-- 4. MODUL RESUME / AKHIR -->
    <?php if ($mod == 'all' || $mod == 'r'): ?>
        <div class="print-container">
            <?= $html_r ?>
        </div>
    <?php endif; ?>

    <script src="../../../aset/js/blueprint.js?v=<?= time() ?>"></script>
</body>
<script>
    // Pastikan document.title ter-set dengan benar sebelum print
    // Browser menggunakan document.title sebagai nama file default saat "Save as PDF"
    document.title = <?= json_encode($title_pdf) ?>;
</script>
</html>