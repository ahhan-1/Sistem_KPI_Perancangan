<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Pemimpin Cabang') {
    header('Location: ../../index.php');
    exit();
}

$id_cabang = $_SESSION['id_cabang'];
$id_user = $_SESSION['id_user'];

// --- 1. Filter Utama ---
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_aktif = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan = [
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
$label_periode = "Bulan " . $nama_bulan[$bulan_aktif];

// --- Guard: Cek Profil Lengkap ---

// --- 2. Ambil Target & Realisasi ---
$tahun_skrg = (int)date('Y');
$q_data = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, t.nilai_target as target, tk.nilai_target as target_koreksi,
           (SELECT SUM(realisasi) FROM realisasi 
            WHERE id_indikator = i.id_indikator 
            AND id_cabang = $id_cabang 
            AND tahun = $tahun_aktif 
            AND bulan = $bulan_aktif) as total_realisasi
           FROM indikator i
           JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
           JOIN perspektif p ON s.id_perspektif = p.id_perspektif
           LEFT JOIN target t ON i.id_indikator = t.id_indikator 
                AND t.id_cabang = $id_cabang 
                AND t.tahun = $tahun_aktif 
                AND t.bulan = 12
           LEFT JOIN target tk ON i.id_indikator = tk.id_indikator 
                AND tk.id_cabang = $id_cabang 
                AND tk.tahun = $tahun_aktif 
                AND tk.bulan = $bulan_aktif
           WHERE i.tahun = $tahun_aktif AND i.id_cabang = $id_cabang
           ORDER BY p.id_perspektif ASC, s.id_subperspektif ASC, i.id_indikator ASC";

$res_data = mysqli_query($koneksi, $q_data);
$data_list = [];

// --- 3. Cek Status Kelengkapan Realisasi ---
$q_count_ind = "SELECT COUNT(*) as total FROM indikator WHERE tahun = $tahun_aktif AND id_cabang = $id_cabang";
$total_ind = mysqli_fetch_assoc(mysqli_query($koneksi, $q_count_ind))['total'];

$q_count_real = "SELECT COUNT(*) as total FROM realisasi WHERE id_cabang = $id_cabang AND tahun = $tahun_aktif AND bulan = $bulan_aktif";
$total_real = mysqli_fetch_assoc(mysqli_query($koneksi, $q_count_real))['total'];

$bulan_ini_terisi = ($total_ind > 0 && $total_real >= $total_ind);

if ($bulan_ini_terisi) {
    while ($row = mysqli_fetch_assoc($res_data)) {
        $data_list[$row['nama_subperspektif']][] = $row;
    }
}

$title = "Capaian Realisasi";
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
        .group-header {
            background: #f8f9fa;
            padding: 12px 20px;
            font-weight: bold;
            color: var(--biru-utama);
            border-bottom: 2px solid var(--biru-muda);
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Capaian Realisasi</h1>
                    <p>Monitoring performa cabang secara bulanan.</p>
                </div>
                <div class="filter-periode-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="month" id="periodePicker" class="input-month"
                        value="<?php echo $tahun_aktif . '-' . str_pad($bulan_aktif, 2, '0', STR_PAD_LEFT); ?>"
                        onchange="updateFilter()">
                </div>
            </div>

            <?php if (count($data_list) > 0): ?>
                <?php foreach ($data_list as $subperspektif => $indikators): ?>
                    <div class="kartu" style="padding: 0; overflow: hidden; margin-bottom: 25px;">
                        <div class="group-header">
                            <i class="fas fa-layer-group"></i> Sub-Perspektif: <?php echo $subperspektif; ?>
                        </div>
                        <table class="tabel-kpi">
                            <thead>
                                <tr>
                                    <th width="35%">Indikator Kinerja</th>
                                    <th width="15%" style="text-align: center;">Satuan</th>
                                    <th width="15%" style="text-align: center;">Target Bulanan</th>
                                    <th width="15%" style="text-align: center;">Total Realisasi</th>
                                    <th width="20%" style="text-align: center;">Pencapaian (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($indikators as $ind):
                                    if (isset($ind['target_koreksi']) && $ind['target_koreksi'] !== null && $ind['target_koreksi'] !== '') {
                                        // Pakai koreksi target bulanan jika ada
                                        $target_final = (float)$ind['target_koreksi'];
                                    } else {
                                        // Gunakan fungsi pusat agar konsisten dengan kalkulasi backend
                                        $target_final = calculate_target_period($koneksi, (int)$ind['id_indikator'], $id_cabang, $tahun_aktif, $bulan_aktif);
                                    }
                                    $real_final = $ind['total_realisasi'] ?: 0;

                                    if ($target_final > 0) {
                                        $persen = ($real_final / $target_final) * 100;
                                    } else {
                                        $persen = ($real_final == 0) ? 100 : 0;
                                    }

                                    $kelas_status = 'badge-merah';
                                    $icon = 'fa-circle-xmark';
                                    if ($persen >= 100) {
                                        $kelas_status = 'badge-hijau';
                                        $icon = 'fa-circle-check';
                                    } elseif ($persen >= 90) {
                                        $kelas_status = 'badge-kuning';
                                        $icon = 'fa-circle-exclamation';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $ind['nama_indikator']; ?></strong><br>
                                            <small style="color: #999;">Perspektif: <?php echo $ind['nama_perspektif']; ?></small>
                                        </td>
                                        <td style="text-align: center; color: #666;"><?php echo $ind['satuan']; ?></td>
                                        <td style="text-align: center; color: #4a5568; font-weight: 600;">
                                            <?php
                                            $s = trim(strtolower($ind['satuan']));
                                            $suffix = ($s == 'persen' || $s == '%') ? '%' : '';
                                            echo number_format($target_final, 2, ',', '.') . $suffix;
                                            ?>
                                        </td>
                                        <td style="text-align: center; font-weight: 700; color: var(--biru-utama);">
                                            <?php echo number_format($real_final, 2, ',', '.') . $suffix; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="badge-pill <?php echo $kelas_status; ?>">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                                <?php echo number_format($persen, 2, ',', '.'); ?>%
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="kartu" style="text-align: center; padding: 80px 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 15px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <i class="fas fa-folder-open" style="font-size: 4em; color: #a0aec0; margin-bottom: 20px;"></i>
                    <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Capaian Realisasi Belum Tersedia</h3>
                    <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto;">Data target atau realisasi untuk bulan <?php echo $nama_bulan[$bulan_aktif] . ' ' . $tahun_aktif; ?> belum diisi secara lengkap oleh Divisi BKU. Silakan hubungi Divisi terkait.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFilter() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const parts = val.split('-');
            const thn = parts[0];
            const bln = parseInt(parts[1]);
            window.location.href = `realisasi_saya.php?filter_tahun=${thn}&filter_bulan=${bln}`;
        }
    </script>

</body>

</html>