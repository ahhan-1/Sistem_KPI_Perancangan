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
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_aktif = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan = get_nama_bulan();

// --- Guard: Cek Profil Lengkap ---

// --- 1. Ambil Semua Indikator ---
$q_ind = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, t.nilai_target as target, tk.nilai_target as target_koreksi 
          FROM indikator i
          JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
          JOIN perspektif p ON s.id_perspektif = p.id_perspektif
          LEFT JOIN target t ON i.id_indikator = t.id_indikator AND t.id_cabang = $id_cabang AND t.tahun = $tahun_aktif AND t.bulan = 12
          LEFT JOIN target tk ON i.id_indikator = tk.id_indikator AND tk.id_cabang = $id_cabang AND tk.tahun = $tahun_aktif AND tk.bulan = $bulan_aktif
          WHERE i.tahun = $tahun_aktif AND i.id_cabang = $id_cabang
          ORDER BY p.id_perspektif ASC, s.id_subperspektif ASC";
$res_ind = mysqli_query($koneksi, $q_ind);
$indikator_list = [];
while ($row = mysqli_fetch_assoc($res_ind)) {
    $indikator_list[$row['nama_subperspektif']][] = $row;
}

$title = "Target Cabang";
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
                    <h1>Data Target Cabang</h1>
                    <p>Monitoring target tahunan yang telah ditetapkan untuk cabang Anda.</p>
                </div>
                <div class="filter-periode-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="month" id="periodePicker" class="input-month"
                        value="<?php echo $tahun_aktif . '-' . str_pad($bulan_aktif, 2, '0', STR_PAD_LEFT); ?>"
                        onchange="updateFilter()">
                </div>
            </div>

            <?php if (count($indikator_list) > 0): ?>
                <?php foreach ($indikator_list as $subperspektif => $indikators): ?>
                    <div class="kartu" style="padding: 0; overflow: hidden; margin-bottom: 25px;">
                        <div class="group-header">
                            <i class="fas fa-layer-group"></i> Sub-Perspektif: <?php echo $subperspektif; ?>
                        </div>
                        <table class="tabel-kpi">
                            <thead>
                                <tr>
                                    <th width="35%">Indikator</th>
                                    <th width="15%" style="text-align: center;">Satuan</th>
                                    <th width="10%" style="text-align: center;">Bobot</th>
                                    <th width="20%" style="text-align: center;">Target <?php echo $nama_bulan[$bulan_aktif]; ?></th>
                                    <th width="20%" style="text-align: center;">Target Tahunan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($indikators as $ind): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $ind['nama_indikator']; ?></strong><br>
                                            <small style="color: #999;">Perspektif: <?php echo $ind['nama_perspektif']; ?></small>
                                        </td>
                                        <td style="text-align: center; color: #666;"><?php echo $ind['satuan']; ?></td>
                                        <td style="text-align: center; font-weight: bold;"><?php echo $ind['bobot']; ?>%</td>
                                        <td style="text-align: center; color: var(--biru-utama); font-weight: 600;">
                                            <?php
                                            $s = trim(strtolower($ind['satuan']));
                                            $suffix = ($s == 'persen' || $s == '%') ? '%' : '';

                                            // Pakai fungsi pusat agar konsisten (Single Month)
                                            require_once "../../fungsi/perhitungan_kpi.php";
                                            $target_smart = calculate_target_period($koneksi, (int)$ind['id_indikator'], $id_cabang, $tahun_aktif, $bulan_aktif);

                                            echo number_format($target_smart, 2, ',', '.') . $suffix;
                                            ?>
                                        </td>
                                        <td style="text-align: center; font-weight: 700;">
                                            <?php
                                            echo $ind['target'] ? number_format($ind['target'], 2, ',', '.') . $suffix : '-';
                                            ?>
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
                    <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Data Target Belum Tersedia</h3>
                    <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto;">Divisi BKU belum mendistribusikan data target cabang untuk tahun <?php echo $tahun_aktif; ?>. Silakan hubungi Divisi terkait.</p>
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
            window.location.href = `target_saya.php?filter_tahun=${thn}&filter_bulan=${bln}`;
        }
    </script>

</body>

</html>