<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Direksi') {
    header('Location: ../../index.php');
    exit();
}

$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan_filter = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');

$nama_bulan_long = get_nama_bulan();

// --- 1. Query Perangkingan ---
$q_rank = "SELECT na.*, u.nama, c.nama_cabang 
           FROM nilai_akhir na
           JOIN users u ON na.id_user = u.id_user
           JOIN cabang c ON u.id_cabang = c.id_cabang
           WHERE na.tahun = $tahun_filter AND na.bulan = $bulan_filter
           AND na.nilai_akhir_kinerja > 0
           ORDER BY na.nilai_akhir_kinerja DESC";
$res_rank = mysqli_query($koneksi, $q_rank);
$rank_data = [];
while ($row = mysqli_fetch_assoc($res_rank)) {
    $rank_data[] = $row;
}
$total_data = count($rank_data);

$title = "Dashboard Direksi";
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
        .rank-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .rank-table th {
            background: var(--biru-utama);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.9em;
            text-transform: uppercase;
        }

        .rank-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.95em;
        }

        /* Warna Top 5 */
        .top-rank {
            background-color: #ebf8ff !important;
            color: #2b6cb0 !important;
            font-weight: 600;
        }

        .top-rank .rank-badge {
            background: #3182ce;
            color: white;
        }

        /* Warna Bottom 5 */
        .bottom-rank {
            background-color: #fff5f5 !important;
            color: #c53030 !important;
            font-weight: 600;
        }

        .bottom-rank .rank-badge {
            background: #e53e3e;
            color: white;
        }

        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            border-radius: 50%;
            background: #edf2f7;
            color: #4a5568;
            font-size: 0.85em;
            font-weight: bold;
        }

        .indeks-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .indeks-A {
            background: #c6efce;
            color: #006100;
        }

        .indeks-B {
            background: #e2efda;
            color: #375623;
        }

        .indeks-C {
            background: #ffeb9c;
            color: #9c6500;
        }

        .indeks-D {
            background: #ffc7ce;
            color: #9c0006;
        }

        .indeks-E {
            background: #ffc7ce;
            color: #9c0006;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h1>Perangkingan Nilai Akhir KPI</h1>
                    <p>Pemantauan performa seluruh Cabang Bank Sumsel Babel secara real-time.</p>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                    <div class="filter-periode-container" style="box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                        <i class="fas fa-calendar-check"></i>
                        <input type="month" id="periodePicker" class="input-month"
                            value="<?php echo $tahun_filter . '-' . str_pad($bulan_filter, 2, '0', STR_PAD_LEFT); ?>"
                            onchange="updateRank()">
                    </div>
                    <div class="text-right" style="background: white; padding: 8px 15px; border-radius: 12px; border: 1px solid #e2e8f0; min-width: 150px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div style="font-size: 0.7em; color: #718096; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Total Cabang Dinilai</div>
                        <div style="font-size: 1.8em; font-weight: 800; color: var(--biru-utama); line-height: 1;"><?php echo $total_data; ?></div>
                    </div>
                </div>
            </div>


            <div class="kartu" style="padding: 0; background: transparent; border: none; box-shadow: none;">
                <?php if ($total_data > 0):
                    $top_5 = array_slice($rank_data, 0, min(5, $total_data));
                    $bottom_5_start = max(5, $total_data - 5);
                    $bottom_5 = ($total_data > 10) ? array_slice($rank_data, $bottom_5_start) : (($total_data > 5) ? array_slice($rank_data, -5) : []);
                    $middle_start = 5;
                    $middle_end = ($total_data > 10) ? $total_data - 5 : $total_data;
                    $middle_ranks = ($total_data > 5) ? array_slice($rank_data, $middle_start, $middle_end - $middle_start) : [];

                    // --- 2. Hitung Distribusi Indeks ---
                    $indeks_count = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
                    foreach ($rank_data as $r) {
                        $idx_val = strtoupper(trim($r['indeks_nilai_akhir']));
                        if (isset($indeks_count[$idx_val])) {
                            $indeks_count[$idx_val]++;
                        }
                    }
                ?>

                    <!-- Row 1: Top 5 (Left) + Bottom 5 (Right) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <!-- Top 5 -->
                        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 15px;">
                            <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1em; color: #2b6cb0; border-bottom: 2px solid #ebf8ff; padding-bottom: 10px;">
                                <i class="fas fa-trophy" style="color: #ecc94b; margin-right: 8px;"></i> Top 5 Cabang
                            </h3>
                            <table class="rank-table" style="margin-top: 0; box-shadow: none;">
                                <thead>
                                    <tr>
                                        <th width="60" class="text-center">Rank</th>
                                        <th>Kantor Cabang</th>
                                        <th width="120" class="text-center">Nilai</th>
                                        <th width="80" class="text-center">Indeks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_5 as $idx => $r): ?>
                                        <tr class="top-rank">
                                            <td class="text-center"><span class="rank-badge"><?php echo ($idx + 1); ?></span></td>
                                            <td>
                                                <div style="font-weight: bold; font-size: 0.9em;"><?php echo ucwords(strtolower($r['nama_cabang'])); ?></div>
                                            </td>
                                            <td class="text-center"><span style="font-size: 1.05em; font-weight: bold;"><?php echo number_format($r['nilai_akhir_kinerja'], 2, ',', '.'); ?></span></td>
                                            <td class="text-center"><span class="indeks-badge indeks-<?php echo $r['indeks_nilai_akhir']; ?>"><?php echo $r['indeks_nilai_akhir']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bottom 5 -->
                        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 15px;">
                            <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1em; color: #c53030; border-bottom: 2px solid #fff5f5; padding-bottom: 10px;">
                                <i class="fas fa-arrow-trend-down" style="color: #e53e3e; margin-right: 8px;"></i> Bottom 5 Cabang
                            </h3>
                            <?php if (count($bottom_5) > 0): ?>
                                <table class="rank-table" style="margin-top: 0; box-shadow: none;">
                                    <thead>
                                        <tr>
                                            <th width="60" class="text-center">Rank</th>
                                            <th>Kantor Cabang</th>
                                            <th width="120" class="text-center">Nilai</th>
                                            <th width="80" class="text-center">Indeks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bottom_5 as $idx => $r):
                                            $rank_bottom = $bottom_5_start + $idx + 1;
                                        ?>
                                            <tr class="bottom-rank">
                                                <td class="text-center"><span class="rank-badge"><?php echo $rank_bottom; ?></span></td>
                                                <td>
                                                    <div style="font-weight: bold; font-size: 0.9em;"><?php echo ucwords(strtolower($r['nama_cabang'])); ?></div>
                                                </td>
                                                <td class="text-center"><span style="font-size: 1.05em; font-weight: bold;"><?php echo number_format($r['nilai_akhir_kinerja'], 2, ',', '.'); ?></span></td>
                                                <td class="text-center"><span class="indeks-badge indeks-<?php echo $r['indeks_nilai_akhir']; ?>"><?php echo $r['indeks_nilai_akhir']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p style="text-align: center; color: #888; font-size: 0.9em; margin-top: 30px;">Data tidak cukup untuk Bottom 5.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Row 2: Peringkat 6+ (Left) + Rangkuman (Right) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                        <!-- Peringkat 6 - N (Middle Ranks) -->
                        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 15px; max-height: 500px; overflow-y: auto;">
                            <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1em; color: #4a5568; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">
                                <i class="fas fa-list-ol" style="color: var(--biru-utama); margin-right: 8px;"></i> Peringkat <?php echo count($middle_ranks) > 0 ? '6 - ' . ($middle_start + count($middle_ranks)) : '6+'; ?>
                            </h3>
                            <?php if (count($middle_ranks) > 0): ?>
                                <table class="rank-table" style="margin-top: 0; box-shadow: none;">
                                    <thead>
                                        <tr>
                                            <th width="60" class="text-center">Rank</th>
                                            <th>Kantor Cabang</th>
                                            <th width="120" class="text-center">Nilai</th>
                                            <th width="80" class="text-center">Indeks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($middle_ranks as $idx => $r):
                                            $rank_mid = $middle_start + $idx + 1;
                                        ?>
                                            <tr>
                                                <td class="text-center"><span class="rank-badge"><?php echo $rank_mid; ?></span></td>
                                                <td>
                                                    <div style="font-weight: bold; font-size: 0.9em;"><?php echo ucwords(strtolower($r['nama_cabang'])); ?></div>
                                                </td>
                                                <td class="text-center"><span style="font-size: 1.05em; font-weight: bold;"><?php echo number_format($r['nilai_akhir_kinerja'], 2, ',', '.'); ?></span></td>
                                                <td class="text-center"><span class="indeks-badge indeks-<?php echo $r['indeks_nilai_akhir']; ?>"><?php echo $r['indeks_nilai_akhir']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p style="text-align: center; color: #888; font-size: 0.9em; margin-top: 30px;">Belum ada data peringkat menengah.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Rangkuman / Recap -->
                        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); padding: 20px; border: 1px solid #edf2f7;">
                            <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.1em; color: #1a365d; border-bottom: 1px solid #edf2f7; padding-bottom: 12px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-chart-pie" style="color: var(--biru-utama);"></i> Distribusi Indikator Kinerja
                            </h3>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php
                                $indeks_config = [
                                    'A' => ['label' => 'Memuaskan',              'color' => '#2b6cb0', 'bg_subtle' => '#ebf8ff', 'icon' => 'fa-star'],
                                    'B' => ['label' => 'Baik',                   'color' => '#3182ce', 'bg_subtle' => '#ebf8ff', 'icon' => 'fa-thumbs-up'],
                                    'C' => ['label' => 'Cukup',                  'color' => '#4299e1', 'bg_subtle' => '#f7fafc', 'icon' => 'fa-minus-circle'],
                                    'D' => ['label' => 'Perlu Perhatian Khusus', 'color' => '#63b3ed', 'bg_subtle' => '#f7fafc', 'icon' => 'fa-arrow-down'],
                                    'E' => ['label' => 'Kurang',                 'color' => '#a0aec0', 'bg_subtle' => '#f7fafc', 'icon' => 'fa-triangle-exclamation'],
                                ];
                                foreach ($indeks_config as $huruf => $cfg):
                                    $jumlah = $indeks_count[$huruf];
                                    $persen = $total_data > 0 ? round(($jumlah / $total_data) * 100) : 0;
                                ?>
                                    <div style="display: flex; align-items: center; gap: 15px; padding: 12px 16px; border-radius: 10px; background: white; border: 1px solid #e2e8f0; border-left: 4px solid <?php echo $cfg['color']; ?>; transition: all 0.3s ease;" onmouseover="this.style.borderColor='<?php echo $cfg['color']; ?>'; this.style.transform='translateX(5px)'" onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateX(0)'">
                                        <div style="width: 35px; height: 35px; border-radius: 8px; background: <?php echo $cfg['bg_subtle']; ?>; color: <?php echo $cfg['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 0.9em; flex-shrink: 0; font-weight: 800; border: 1px solid rgba(0,0,0,0.03);">
                                            <?php echo $huruf; ?>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                                <span style="font-weight: 700; color: #2d3748; font-size: 0.9em;"><?php echo $cfg['label']; ?></span>
                                                <span style="font-weight: 800; font-size: 1.1em; color: var(--biru-utama);"><?php echo $jumlah; ?> <span style="font-size: 0.65em; color: #a0aec0; font-weight: 600; text-transform: uppercase;">Unit</span></span>
                                            </div>
                                            <div style="background: #f1f5f9; border-radius: 10px; height: 6px; overflow: hidden;">
                                                <div style="width: <?php echo $persen; ?>%; height: 100%; background: linear-gradient(to right, <?php echo $cfg['color']; ?>, var(--biru-utama)); border-radius: 10px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #e2e8f0; text-align: center;">
                                <p style="font-size: 0.75em; color: #a0aec0; font-style: italic;">Data distribusi performa berdasarkan target periode berjalan.</p>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="kartu text-center" style="padding: 50px;">
                        <i class="fas fa-chart-line" style="font-size: 3em; color: #cbd5e0; margin-bottom: 20px;"></i>
                        <h3 style="color: #4a5568;">Belum Ada Data Penilaian</h3>
                        <p style="color: #718096;">Data perangkingan belum tersedia untuk periode <?php echo $nama_bulan_long[$bulan_filter] . " " . $tahun_filter; ?>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function updateRank() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const parts = val.split('-');
            const thn = parts[0];
            const bln = parseInt(parts[1]);
            window.location.href = `beranda.php?tahun=${thn}&bulan=${bln}`;
        }
    </script>

</body>

</html>