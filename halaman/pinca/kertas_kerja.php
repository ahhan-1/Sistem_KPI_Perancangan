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

// --- 1. Filter Utama ---
$tahun_aktif = (isset($_GET['filter_tahun']) && $_GET['filter_tahun'] != 0) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_pilih = (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] != 0) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan_long = get_nama_bulan();
$label_periode = ($bulan_pilih == 1)
    ? $nama_bulan_long[$bulan_pilih] . " " . $tahun_aktif
    : "Januari - " . $nama_bulan_long[$bulan_pilih] . " " . $tahun_aktif;

// --- 2. Ambil Profil User Detail ---
$q_user = "SELECT u.*, c.nama_cabang 
           FROM users u 
           JOIN cabang c ON u.id_cabang = c.id_cabang
           WHERE u.id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

// --- Guard: Cek Profil Lengkap ---

// --- 3. Ambil Data KPI (Validasi Kelengkapan) ---
$q_data = "SELECT i.id_indikator,
           (SELECT COUNT(*) FROM realisasi 
            WHERE id_indikator = i.id_indikator 
            AND id_cabang = $id_cabang 
            AND tahun = $tahun_aktif 
            AND bulan = $bulan_pilih) as cek_realisasi_bulan_ini
           FROM indikator i
           WHERE i.tahun = $tahun_aktif AND i.id_cabang = $id_cabang";

$res_data = mysqli_query($koneksi, $q_data);
$grouped_data = [];
$is_lengkap = true;
$total_kpi = 0;
while ($row = mysqli_fetch_assoc($res_data)) {
    $total_kpi++;
    if ($row['cek_realisasi_bulan_ini'] == 0) {
        $is_lengkap = false;
    }
}
if ($total_kpi == 0) $is_lengkap = false;

$title = "Kertas Kerja Penilaian";
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

        .kertas-kerja {
            background: white;
            padding: 40px;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            color: #000;
        }

        .no-print-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        @media print {

            .sidebar,
            .no-print,
            .no-print-section {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
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
                    <h1>Monitoring Kertas Kerja KPI</h1>
                    <p>Capaian realisasi KPI Cabang <?php echo $u['nama_cabang'] ?? '-'; ?> per periode terpilih.</p>
                </div>

                <div class="no-print-section no-print">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <?php if ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi'): ?>
                            <a href="../<?= ($_SESSION['jabatan'] == 'Direksi') ? 'direksi' : 'divisi'; ?>/monitoring_cabang.php" class="tombol" style="background: #4a5568; color: white; padding: 8px 15px;">
                                <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
                            </a>
                        <?php endif; ?>

                        <div class="filter-periode-container">
                            <i class="fas fa-calendar-check"></i>
                            <input type="month" id="periodePicker" class="input-month"
                                value="<?php echo $tahun_aktif . '-' . str_pad($bulan_pilih, 2, '0', STR_PAD_LEFT); ?>"
                                onchange="updateNav()">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <?php if ($is_lengkap && $total_kpi > 0): ?>
                            <button onclick="cetakPDF()" type="button" class="tombol" style="background: #edf2f7; color: #4a5568;"><i class="fas fa-print"></i> Cetak</button>
                            <?php if ($_SESSION['jabatan'] == 'Pemimpin Cabang'): ?>
                                <button type="submit" form="formKK" class="tombol tombol-utama"><i class="fas fa-save"></i> Simpan Hasil Penilaian</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="kartu" style="background: #e6fffa; border: 1px solid #b2f5ea; color: #234e52; margin-bottom: 20px; padding: 15px; width: 100%;">
                    <i class="fas fa-circle-check"></i> <strong>Berhasil!</strong> Data penilaian periode <?php echo $label_periode; ?> telah disimpan ke database.
                </div>
            <?php endif; ?>

            <?php if (!$is_lengkap || $total_kpi == 0): ?>
                <div class="kartu" style="text-align: center; padding: 80px 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 15px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <i class="fas fa-file-circle-exclamation" style="font-size: 4em; color: #a0aec0; margin-bottom: 20px;"></i>
                    <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Kertas Kerja Belum Tersedia</h3>

                    <?php if ($_SESSION['jabatan'] == 'Divisi'): ?>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto; margin-bottom: 25px;">
                            Data realisasi KPI untuk periode <strong><?php echo $label_periode; ?></strong> belum di-input ke sistem.
                        </p>
                        <a href="../divisi/realisasi_cabang.php" target="_parent" class="tombol tombol-utama" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 25px; background: #c05621;">
                            <i class="fas fa-edit"></i> Input Realisasi Sekarang
                        </a>
                    <?php else: ?>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto;">
                            Data Kertas Kerja untuk periode <?php echo $label_periode; ?> belum tersedia atau belum di-input oleh Divisi BKU. Silakan hubungi Divisi terkait.
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="kertas-kerja">
                    <form id="formKK" action="../../fungsi/kpi_manajemen_penilaian.php" method="POST">
                        <input type="hidden" name="aksi_utama" value="simpan_kertas_kerja">
                        <input type="hidden" name="tahun" value="<?php echo $tahun_aktif; ?>">
                        <input type="hidden" name="bulan" value="<?php echo $bulan_pilih; ?>">
                        <?php
                        // Tell template to include hidden inputs
                        $tahun = $tahun_aktif;
                        $bulan = $bulan_pilih;
                        $is_web = true;

                        include "templates/tpl_kertas_kerja.php";
                        /** @var string $html_kk */
                        echo $html_kk;
                        ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
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

            let url = `cetak/cetak_browser.php?mod=kk&filter_tahun=${thn}&filter_bulan=${bln}`;
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

            let url = `kertas_kerja.php?filter_tahun=${thn}&filter_bulan=${bln}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            window.location.href = url;
        }
    </script>

</body>

</html>