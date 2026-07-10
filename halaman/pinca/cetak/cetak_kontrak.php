<?php
/**
 * CETAK KONTRAK KERJA - Browser Print
 * Pengganti file lama yang menggunakan dompdf.
 * Sekarang menggunakan browser-native print (window.print).
 */
require_once "../../../fungsi/koneksi.php";
require_once "../../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */
session_start();

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Pemimpin Cabang' && $_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    exit('Akses ditolak');
}

// Params
if ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') {
    $id_user = isset($_GET['id_user']) ? (int) $_GET['id_user'] : (int) $_SESSION['id_user'];
    $id_cabang = isset($_GET['id_cabang']) ? (int) $_GET['id_cabang'] : (int) $_SESSION['id_cabang'];
} else {
    $id_user = (int) $_SESSION['id_user'];
    $id_cabang = (int) $_SESSION['id_cabang'];
}

$tahun = isset($_GET['filter_tahun']) ? (int) $_GET['filter_tahun'] : (int) date('Y');
$bulan = isset($_GET['filter_bulan']) ? (int) $_GET['filter_bulan'] : 0;

$nama_bulan_arr = [
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

$bulan_nama = ($bulan > 0) ? strtoupper($nama_bulan_arr[$bulan]) : "TAHUNAN";
$label_tahunan = ($bulan > 0) ? $nama_bulan_arr[$bulan] : "Tahunan";

// Ambil data user
$u = get_pinca_profile($koneksi, $id_user, $tahun);
$q_cab = "SELECT nama_cabang FROM cabang WHERE id_cabang = " . (int)$u['id_cabang'];
$res_cab = mysqli_query($koneksi, $q_cab);
$cab = mysqli_fetch_assoc($res_cab);
$u['nama_cabang'] = $cab['nama_cabang'] ?? '';

// Grade & Lokasi
$grade = (!empty($u['level_kip'])) ? $u['level_kip'] : "G-10";
$lokasi_kerja = isset($u['nama_cabang']) ? ucwords(strtolower($u['nama_cabang'])) : '-';
$jabatan_display = $_SESSION['jabatan'] ?? '...';

// 1. Ambil Sasaran Strategis untuk tahun ini
$sasaran_mapping = [];
$q_sasaran = "SELECT id_perspektif, nama_sasaran FROM sasaran_strategis WHERE tahun = $tahun";
$res_sasaran = mysqli_query($koneksi, $q_sasaran);
while ($s = mysqli_fetch_assoc($res_sasaran)) {
    $sasaran_mapping[$s['id_perspektif']] = $s['nama_sasaran'];
}

// 2. Data Indikator & Target
// Ambil Target Tahunan (bulan = 12 / Desember)
$q_ind = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, p.id_perspektif, 
          t_ann.nilai_target as target_tahunan, ss.nama_sasaran
          FROM indikator i
          JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
          JOIN perspektif p ON s.id_perspektif = p.id_perspektif
          LEFT JOIN sasaran_strategis ss ON i.id_sasaran = ss.id_sasaran
          LEFT JOIN target t_ann ON i.id_indikator = t_ann.id_indikator AND t_ann.id_cabang = $id_cabang AND t_ann.tahun = $tahun AND t_ann.bulan = 12
          WHERE i.tahun = $tahun AND i.id_cabang = $id_cabang
          ORDER BY p.id_perspektif ASC, i.id_indikator ASC";
$res_ind = mysqli_query($koneksi, $q_ind);

// Ambil SEMUA target bulanan untuk cabang & tahun ini untuk kalkulasi sisa
$q_all_mon = "SELECT id_indikator, bulan, nilai_target FROM target WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan IS NOT NULL";
$res_all_mon = mysqli_query($koneksi, $q_all_mon);
$all_monthly_targets = [];
while ($t = mysqli_fetch_assoc($res_all_mon)) {
    $all_monthly_targets[$t['id_indikator']][$t['bulan']] = (float) $t['nilai_target'];
}

$data_kontrak = [];
$total_bobot_kpi = 0;

while ($row = mysqli_fetch_assoc($res_ind)) {
    $id_ind = $row['id_indikator'];
    $target_ann = !is_null($row['target_tahunan']) ? (float) $row['target_tahunan'] : null;
    $manual_targets = isset($all_monthly_targets[$id_ind]) ? $all_monthly_targets[$id_ind] : [];

    if ($bulan > 0) {
        if (isset($manual_targets[$bulan])) {
            $row['target'] = $manual_targets[$bulan];
        } else {
            if (!is_null($target_ann)) {
                $sum_manual = array_sum($manual_targets);
                $count_manual = count($manual_targets);
                $remaining_months = 12 - $count_manual;

                if ($remaining_months > 0) {
                    $row['target'] = ($target_ann - $sum_manual) / $remaining_months;
                    if ($row['target'] < 0)
                        $row['target'] = 0;
                } else {
                    $row['target'] = 0;
                }
            } else {
                $row['target'] = null;
            }
        }
    } else {
        $row['target'] = $target_ann;
    }

    // Kelompokkan: Perspektif -> Sasaran -> Sub-Perspektif -> Indikator
    $p_name = $row['nama_perspektif'];
    $s_name = !empty($row['nama_sasaran']) ? $row['nama_sasaran'] : (!empty($row['sasaran_strategis']) ? $row['sasaran_strategis'] : 'Tanpa Sasaran');
    $sub_name = $row['nama_subperspektif'];

    $data_kontrak[$p_name]['sasaran'][$s_name][$sub_name][] = $row;
    $total_bobot_kpi += $row['bobot'];
}

// Load template
$html_kk_web = "";
require "../templates/tpl_kontrak_kerja.php";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kontrak Kerja - <?= ucwords(strtolower($u['nama_cabang'] ?? '')) ?> - Tahun <?= $tahun ?></title>
    <link rel="stylesheet" href="../../../aset/css/gaya.css">
    <style>
        body {
            background: white;
            margin: 0;
            padding: 0;
        }

        .print-container {
            box-shadow: none;
            margin: 0 auto;
        }

        .sheet-area {
            margin: 0 !important;
            padding: 0 !important;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 0.4cm;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                zoom: 0.87;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .paper-kontrak {
                padding: 3px !important;
                line-height: 1.15 !important;
            }

            .footer-signatures-k {
                margin-top: 15px !important;
            }

            .sign-area-k {
                height: 25px !important;
            }

            .contract-header-k {
                margin-bottom: 8px !important;
            }

            .print-container {
                page-break-after: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="print-container">
        <?= $html_kk_web ?>
    </div>

    <script>
        // Set nama file PDF: browser pakai document.title saat "Save as PDF"
        document.title = <?= json_encode('Kontrak Kerja - ' . ucwords(strtolower($u['nama_cabang'] ?? '')) . ' - Tahun ' . $tahun) ?>;
        // Print dikendalikan oleh parent (kontrak_kerja.php) via iframe
        // window.onafterprint: tutup tab/window jika dibuka langsung (bukan lewat iframe)
        window.onafterprint = function () {
            if (window.opener || window.parent !== window) {
                // dibuka via iframe/popup - tidak perlu close
            } else {
                window.close();
            }
        };
    </script>    <script src="../../../aset/js/blueprint.js"></script>
</body>

</html>