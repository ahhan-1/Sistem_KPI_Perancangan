<?php
session_start();
require "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$id_user = isset($_GET['id_user']) ? (int) $_GET['id_user'] : 0;
$id_cabang = isset($_GET['id_cabang']) ? (int) $_GET['id_cabang'] : 0;

$tahun_aktif = isset($_GET['filter_tahun']) ? (int) $_GET['filter_tahun'] : (int) date('Y');
$bulan_aktif = isset($_GET['filter_bulan']) ? (int) $_GET['filter_bulan'] : (int) date('n');

$nama_bulan_long = get_nama_bulan();

// --- 1. Ambil Profil Cabang ---
$q_cabang = "SELECT * FROM cabang WHERE id_cabang = $id_cabang";
$res_cabang = mysqli_query($koneksi, $q_cabang);
$cabang = mysqli_fetch_assoc($res_cabang);

$q_user = "SELECT * FROM users WHERE id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

$nama_cabang = $cabang['nama_cabang'] ?? '-';
$pinca_name = (!empty($u['nama'])) ? $u['nama'] : ($u['username'] ?? '-');

// --- 2. Ambil Histori Nilai ---
$history_data = array_fill(1, 12, 0);
$q_history = "SELECT bulan, nilai_akhir_kinerja, nilai_kompetensi FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun_aktif ORDER BY bulan ASC";
$res_history = mysqli_query($koneksi, $q_history);

$total_score = 0;
$count_months = 0;

while ($h = mysqli_fetch_assoc($res_history)) {
    $m = (int) $h['bulan'];
    $komp = (float) $h['nilai_kompetensi'];
    // Jika kompetensi belum dinilai, jadikan nol agar grafik tidak rancu
    $score = ($komp > 0) ? round((float) $h['nilai_akhir_kinerja'], 2) : 0;
    $history_data[$m] = $score;
}

for ($i = 1; $i <= 12; $i++) {
    if ($history_data[$i] > 0) {
        $total_score += $history_data[$i];
        $count_months++;
    }
}

$title = "Grafik Performa Cabang";
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .chart-container {
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            margin-top: 25px;
        }

        .empty-chart-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-top: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h1>Grafik Performa Cabang</h1>
                    <p>Grafik tren nilai akhir performa cabang <strong><?php echo $nama_cabang; ?></strong>.</p>
                </div>
                <div>
                    <a href="monitoring_cabang.php" class="btn-kembali">
                        <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
                    </a>
                </div>
            </div>

            <!-- --- 3. Filter Navigasi --- -->
            <div class="kartu"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-minus" style="font-size: 12px;"></i>
                    </button>
                    <div class="filter-periode-container no-chevron">
                        <i class="fas fa-calendar-check"></i>
                        <select id="tahunSelect" class="input-year-select" onchange="updateNav()">
                            <!-- Akan diisi oleh JS -->
                        </select>
                    </div>
                    <button type="button" onclick="tambahTahun()" title="Tambah Tahun" style="background: var(--biru-utama); color: white; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-plus" style="font-size: 12px; color: white;"></i>
                    </button>
                </div>
                <div style="color: #4a5568;">
                    <i class="fas fa-user-tie" style="margin-right: 5px; color: #a0aec0;"></i>
                    Pinca: <strong><?php echo $pinca_name; ?></strong>
                </div>
            </div>

            <?php if ($count_months == 0): ?>
                <!-- --- 4. Tampilan Kosong --- -->
                <div class="empty-chart-state">
                    <div
                        style="background: #ebf8ff; width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; border: 2px solid #bee3f8;">
                        <i class="fas fa-chart-area" style="font-size: 3em; color: #3182ce;"></i>
                    </div>
                    <h2 style="color: #2d3748; margin-bottom: 10px;">Data Grafik Belum Tersedia</h2>

                    <?php if ($_SESSION['jabatan'] == 'Divisi'): ?>
                        <p style="color: #718096; max-width: 500px; margin: 0 auto 25px;">
                            Grafik tren belum dapat ditampilkan karena data realisasi atau penilaian tahun
                            <strong><?php echo $tahun_aktif; ?></strong> belum lengkap.
                        </p>
                        <a href="realisasi_cabang.php" class="btn-sub pc-resume"
                            style="padding: 12px 25px; font-size: 1em; border-radius: 10px; background: #c05621; color: white;">
                            <i class="fas fa-edit"></i> Input Realisasi KPI Sekarang
                        </a>
                    <?php else: ?>
                        <p style="color: #718096; max-width: 500px; margin: 0 auto;">
                            Grafik tren performa tahun <strong><?php echo $tahun_aktif; ?></strong> belum tersedia. Silakan
                            tunggu hingga proses penilaian bulanan diselesaikan oleh Divisi terkait.
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- CHART DISPLAY -->
                <div class="chart-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: #1e293b; margin: 0; font-size: 1.1em; font-weight: 700;">
                            <i class="fas fa-chart-line" style="color: #0245a3; margin-right: 8px;"></i> Tren Performa
                            Bulanan
                        </h3>
                        <span
                            style="font-size: 0.85em; background: #e2f1ff; color: #0056b3; font-weight: bold; padding: 4px 10px; border-radius: 6px;">
                            Tahun <?php echo $tahun_aktif; ?>
                        </span>
                    </div>
                    <div style="height: 380px;">
                        <canvas id="performaChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($count_months > 0): ?>
        <script>
            const ctx = document.getElementById('performaChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_values($nama_bulan_long)); ?>,
                    datasets: [{
                        label: 'Nilai Akhir',
                        data: <?php echo json_encode(array_values($history_data)); ?>,
                        borderColor: '#0245a3',
                        backgroundColor: 'rgba(2, 69, 163, 0.05)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#0245a3'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' Nilai Akhir: ' + context.parsed.y.toLocaleString('id-ID', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 5,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>

    <script>
        const tahunAktif = <?php echo $tahun_aktif; ?>;
        const selectTahun = document.getElementById('tahunSelect');

        function renderTahun() {
            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
            if (maxYear < 2026) {
                maxYear = 2026;
                localStorage.setItem('max_year_kpi', maxYear);
            }
            if (tahunAktif > maxYear) maxYear = tahunAktif;

            selectTahun.innerHTML = '';
            for (let t = 2026; t <= maxYear; t++) {
                const opt = document.createElement('option');
                opt.value = t;
                opt.text = 'Tahun ' + t;
                if (t === tahunAktif) opt.selected = true;
                selectTahun.appendChild(opt);
            }
        }

        function tambahTahun() {
            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
            if (maxYear < 2026) maxYear = 2026;
            maxYear++;
            localStorage.setItem('max_year_kpi', maxYear);
            renderTahun();
            Swal.fire({
                icon: 'success',
                title: 'Tahun Ditambahkan',
                text: 'Tahun ' + maxYear + ' berhasil ditambahkan ke daftar.',
                timer: 2000,
                showConfirmButton: false
            });
        }

        function kurangTahun() {
            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
            if (maxYear > 2026) {
                maxYear--;
                localStorage.setItem('max_year_kpi', maxYear);
                renderTahun();
                Swal.fire({
                    icon: 'success',
                    title: 'Tahun Dikurangi',
                    text: 'Tahun ' + (maxYear + 1) + ' berhasil dihapus dari daftar.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Batas Minimum',
                    text: 'Tahun tidak boleh kurang dari 2026.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        }

        renderTahun();

        function updateNav() {
            const thn = document.getElementById('tahunSelect').value;
            const urlParams = new URLSearchParams(window.location.search);
            const idUser = urlParams.get('id_user') || '';
            const idCabang = urlParams.get('id_cabang') || '';

            let url = `monitoring_grafik.php?filter_tahun=${thn}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            window.location.href = url;
        }
    </script>

</body>

</html>