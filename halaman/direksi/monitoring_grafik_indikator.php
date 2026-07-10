<?php
session_start();
require "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$id_user = isset($_GET['id_user']) ? (int) $_GET['id_user'] : 0;
$id_cabang = isset($_GET['id_cabang']) ? (int) $_GET['id_cabang'] : 0;
$tahun_aktif = isset($_GET['filter_tahun']) ? (int) $_GET['filter_tahun'] : (int) date('Y');
$id_indikator_aktif = isset($_GET['id_indikator']) ? (int) $_GET['id_indikator'] : 0;

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

// --- 1. Ambil Profil ---
$q_cabang = "SELECT * FROM cabang WHERE id_cabang = $id_cabang";
$res_cabang = mysqli_query($koneksi, $q_cabang);
$cabang = mysqli_fetch_assoc($res_cabang);

$q_user = "SELECT * FROM users WHERE id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

$nama_cabang = $cabang['nama_cabang'] ?? '-';
$pinca_name = (!empty($u['nama'])) ? $u['nama'] : ($u['username'] ?? '-');

// --- 2. Ambil Daftar Indikator ---
$q_ind = "SELECT i.id_indikator, i.nama_indikator 
          FROM indikator i 
          WHERE i.id_cabang = $id_cabang AND i.tahun = $tahun_aktif
          ORDER BY i.id_indikator ASC";
$res_ind = mysqli_query($koneksi, $q_ind);
$indikators = [];
while ($row = mysqli_fetch_assoc($res_ind)) {
    $indikators[] = $row;
}

if ($id_indikator_aktif == 0 && count($indikators) > 0) {
    $id_indikator_aktif = $indikators[0]['id_indikator'];
}

$chart_labels = array_values($nama_bulan_long);
$chart_target = [];
$chart_realisasi = [];

$nama_indikator_aktif = "";

if ($id_indikator_aktif > 0) {
    // --- 3. Ambil Detail Indikator Aktif & Data Grafik ---
    foreach ($indikators as $ind) {
        if ($ind['id_indikator'] == $id_indikator_aktif) {
            $nama_indikator_aktif = $ind['nama_indikator'];
            break;
        }
    }

    $q_t = "SELECT nilai_target as target FROM target WHERE id_indikator = $id_indikator_aktif AND id_cabang = $id_cabang AND tahun = $tahun_aktif AND bulan = 12";
    $res_t = mysqli_query($koneksi, $q_t);
    $t_row = mysqli_fetch_assoc($res_t);
    $target_tahunan = $t_row ? (float) $t_row['target'] : 0;

    $koreksi = [];
    $q_k = "SELECT bulan, nilai_target as target_koreksi FROM target WHERE id_indikator = $id_indikator_aktif AND id_cabang = $id_cabang AND tahun = $tahun_aktif AND bulan IS NOT NULL";
    $res_k = mysqli_query($koneksi, $q_k);
    while ($k_row = mysqli_fetch_assoc($res_k)) {
        $koreksi[$k_row['bulan']] = (float) $k_row['target_koreksi'];
    }

    $realisasi_bulanan = [];
    $q_r = "SELECT bulan, SUM(realisasi) as total_r FROM realisasi WHERE id_indikator = $id_indikator_aktif AND id_cabang = $id_cabang AND tahun = $tahun_aktif GROUP BY bulan";
    $res_r = mysqli_query($koneksi, $q_r);
    while ($r_row = mysqli_fetch_assoc($res_r)) {
        $realisasi_bulanan[$r_row['bulan']] = (float) $r_row['total_r'];
    }

    for ($m = 1; $m <= 12; $m++) {
        // Target Bulanan
        if (isset($koreksi[$m])) {
            $target_m = $koreksi[$m];
        } else {
            $target_m = $target_tahunan / 12;
        }
        $chart_target[] = round($target_m, 2);

        // Realisasi Bulanan
        $r_m = isset($realisasi_bulanan[$m]) ? $realisasi_bulanan[$m] : 0;
        $chart_realisasi[] = round($r_m, 2);
    }
}

$title = "Grafik Realisasi Indikator";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - KPI BSB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .btn-kembali {
            background: #4a5568;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-kembali:hover {
            background: #2d3748;
        }

        .profil-header-simple {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #edf2f7;
            color: #4a5568;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h1>Grafik Realisasi vs Target (Per Indikator)</h1>
                    <p>Pemantauan tren pencapaian bulanan secara grafikal untuk tiap indikator yang ditentukan.</p>
                </div>
                <a href="monitoring_cabang.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="btn-kembali">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Cabang
                </a>
            </div>

            <div class="profil-header-simple">
                <h2 style="margin:0 0 5px 0; color: #2d3748; font-size: 1.4em;">Cabang <?php echo $nama_cabang; ?></h2>
                <p style="margin:0; font-size: 0.95em;">Pemimpin Cabang: <strong><?php echo $pinca_name; ?></strong> |
                    Tahun Penilaian: <strong><?php echo $tahun_aktif; ?></strong></p>
            </div>

            <div class="kartu" style="margin-bottom: 20px;">
                <form method="GET" action="">
                    <input type="hidden" name="id_user" value="<?php echo $id_user; ?>">
                    <input type="hidden" name="id_cabang" value="<?php echo $id_cabang; ?>">
                    <input type="hidden" name="filter_tahun" value="<?php echo $tahun_aktif; ?>">

                    <div style="display: flex; gap: 15px; align-items: flex-end;">
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: bold; margin-bottom: 8px; color: #4a5568;">Pilih
                                Indikator Kinerja</label>
                            <select name="id_indikator" class="input-kontrol"
                                style="width: 100%; border: 2px solid #e2e8f0; border-radius: 8px; padding: 10px;"
                                onchange="this.form.submit()">
                                <?php if (count($indikators) == 0): ?>
                                    <option value="">Belum ada target yang diatur</option>
                                <?php else: ?>
                                    <?php foreach ($indikators as $ind): ?>
                                        <option value="<?php echo $ind['id_indikator']; ?>" <?php echo ($id_indikator_aktif == $ind['id_indikator']) ? 'selected' : ''; ?>>
                                            <?php echo $ind['nama_indikator']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($id_indikator_aktif > 0): ?>
                <div class="kartu">
                    <h3 style="text-align: center; color: #2d3748; margin-bottom: 25px;">
                        Grafik Pertumbuhan: <span
                            style="color: var(--biru-utama);"><?php echo $nama_indikator_aktif; ?></span>
                    </h3>
                    <div style="width: 100%; height: 400px;">
                        <canvas id="indikatorChart"></canvas>
                    </div>
                </div>

                <script>
                    const ctx = document.getElementById('indikatorChart').getContext('2d');
                    const indikatorChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode($chart_labels); ?>,
                            datasets: [{
                                    label: 'Realisasi',
                                    data: <?php echo json_encode($chart_realisasi); ?>,
                                    backgroundColor: '#3182ce',
                                    borderWidth: 0,
                                    barPercentage: 0.6,
                                    categoryPercentage: 0.8,
                                    order: 2
                                },
                                {
                                    label: 'Target',
                                    data: <?php echo json_encode($chart_target); ?>,
                                    backgroundColor: '#cbd5e0',
                                    borderWidth: 0,
                                    barPercentage: 0.6,
                                    categoryPercentage: 0.8,
                                    order: 3
                                }
                            ]
                        },
                        plugins: [{
                            id: 'achievementLabels',
                            afterDatasetsDraw(chart, args, options) {
                                const {
                                    ctx,
                                    data
                                } = chart;
                                ctx.save();

                                const realisasiMeta = chart.getDatasetMeta(0);
                                const targetMeta = chart.getDatasetMeta(1);

                                const realisasiData = data.datasets[0].data;
                                const targetData = data.datasets[1].data;

                                realisasiMeta.data.forEach((bar, index) => {
                                    const rVal = realisasiData[index];
                                    const tVal = targetData[index];

                                    if (rVal !== null && tVal !== null && tVal > 0) {
                                        const percent = (rVal / tVal) * 100;
                                        const percentText = percent.toFixed(1) + '%';

                                        const targetBar = targetMeta.data[index];
                                        const yPos = Math.min(bar.y, targetBar.y) - 10;
                                        const xPos = (bar.x + targetBar.x) / 2;

                                        ctx.font = 'bold 10px sans-serif';
                                        const textWidth = ctx.measureText(percentText).width;
                                        const rectWidth = textWidth + 8;
                                        const rectHeight = 16;
                                        const rectX = xPos - rectWidth / 2;
                                        const rectY = yPos - 11;

                                        // Background badge
                                        ctx.fillStyle = '#ebf8ff';
                                        ctx.beginPath();
                                        if (ctx.roundRect) {
                                            ctx.roundRect(rectX, rectY, rectWidth, rectHeight, 4);
                                        } else {
                                            ctx.rect(rectX, rectY, rectWidth, rectHeight);
                                        }
                                        ctx.fill();

                                        // Border badge
                                        ctx.strokeStyle = '#bee3f8';
                                        ctx.lineWidth = 1;
                                        ctx.stroke();

                                        // Text pencapaian
                                        ctx.fillStyle = '#2b6cb0';
                                        ctx.textAlign = 'center';
                                        ctx.textBaseline = 'middle';
                                        ctx.fillText(percentText, xPos, rectY + rectHeight / 2);
                                    }
                                });
                                ctx.restore();
                            }
                        }],
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 14
                                        }
                                    }
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            let value = context.parsed.y;
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (value !== null) {
                                                label += new Intl.NumberFormat('id-ID', {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                }).format(value);
                                            }
                                            return label;
                                        },
                                        afterBody: function(contexts) {
                                            // Ambil konteks realisasi (bar)
                                            const realisasiCtx = contexts.find(c => c.dataset.label === 'Realisasi');
                                            if (realisasiCtx) {
                                                const value = realisasiCtx.parsed.y;
                                                const targetDataset = realisasiCtx.chart.data.datasets.find(ds => ds.label === 'Target');
                                                if (targetDataset && value !== null) {
                                                    const targetValue = targetDataset.data[realisasiCtx.dataIndex];
                                                    if (targetValue && targetValue > 0) {
                                                        const percent = (value / targetValue) * 100;
                                                        return '\nPencapaian: ' + percent.toFixed(2) + '%';
                                                    }
                                                }
                                            }
                                            return null;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grace: '15%',
                                    title: {
                                        display: true,
                                        text: 'Nilai (Format Lokal)',
                                        font: {
                                            size: 13,
                                            weight: 'bold'
                                        }
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Bulan (Periode <?php echo $tahun_aktif; ?>)',
                                        font: {
                                            size: 13,
                                            weight: 'bold'
                                        }
                                    }
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    });
                </script>
            <?php else: ?>
                <div class="kartu" style="text-align: center; padding: 50px;">
                    <i class="fas fa-folder-open" style="font-size: 3em; color: #cbd5e0; margin-bottom: 20px;"></i>
                    <h3 style="color: #4a5568;">Tidak ada Indikator Kinerja</h4>
                        <p style="color: #718096;">Cabang ini belum memiliki data pengaturan target untuk tahun
                            <?php echo $tahun_aktif; ?>.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>