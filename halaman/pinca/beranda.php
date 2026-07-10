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
$username = $_SESSION['username'];

$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');

// --- 1. Cek Profil Lengkap ---
$is_profil_lengkap = true;

$title = "Dashboard Performa";
$bulan_sekarang = (int)date('m');

// --- 2. Ambil Profil & Cabang ---
$q_user = "SELECT u.*, c.nama_cabang FROM users u JOIN cabang c ON u.id_cabang = c.id_cabang WHERE u.id_user = $id_user";
$u = mysqli_fetch_assoc(mysqli_query($koneksi, $q_user));
$display_name = $u['nama'] ?? $username;

// --- 3. Ambil Data History ---
$history_data = array_fill(1, 12, 0);
$q_history = "SELECT bulan, nilai_akhir_kinerja, nilai_kompetensi FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun_aktif ORDER BY bulan ASC";
$res_history = mysqli_query($koneksi, $q_history);

$total_score = 0;
$count_months = 0;
$bulan_kurang = [];
$bulan_kosong = [];

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

while ($h = mysqli_fetch_assoc($res_history)) {
    $m = (int)$h['bulan'];
    $komp = (float)$h['nilai_kompetensi'];
    // Jika kompetensi masih 0, paksa score jadi 0 (untuk grafik)
    $score = ($komp > 0) ? round((float)$h['nilai_akhir_kinerja'], 2) : 0;
    $history_data[$m] = $score;
}

for ($i = 1; $i <= 12; $i++) {
    $score = $history_data[$i];
    if ($score > 0) {
        $total_score += $score;
        $count_months++;
        if ($score < 3.00) $bulan_kurang[] = $nama_bulan_long[$i];
    } else {
        // Jika tahun lalu, semua bulan yang 0 dianggap kosong. 
        // Jika tahun sekarang, hanya sampai bulan ini.
        if ($tahun_aktif < (int)date('Y')) {
            $bulan_kosong[] = $nama_bulan_long[$i];
        } elseif ($tahun_aktif == (int)date('Y') && $i <= $bulan_sekarang) {
            $bulan_kosong[] = $nama_bulan_long[$i];
        }
    }
}

$average_score = ($count_months > 0) ? ($total_score / $count_months) : 0;

$indeks_tahunan = 'E';
if ($average_score >= 4.51) $indeks_tahunan = 'A';
elseif ($average_score >= 3.00) $indeks_tahunan = 'B';
elseif ($average_score >= 2.01) $indeks_tahunan = 'C';
elseif ($average_score >= 1.01) $indeks_tahunan = 'D';
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
        :root {
            --p-blue: #0245a3;
            --p-purple: #805ad5;
            --p-green: #38a169;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .welcome-text h1 {
            font-size: 1.5em;
            margin: 0;
            color: #1e293b;
            font-weight: 800;
        }

        .welcome-text p {
            color: #64748b;
            font-size: 0.85em;
            margin: 3px 0 0;
        }

        .filter-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 8px 15px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            font-size: 0.85em;
        }

        .filter-box select {
            border: none;
            font-weight: 700;
            color: #1e293b;
            cursor: pointer;
            outline: none;
            background: transparent;
        }

        .action-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .action-btn {
            background: white;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #1e293b;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            border-color: var(--p-blue);
        }

        .action-btn .icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1em;
            flex-shrink: 0;
        }

        .action-btn h4 {
            margin: 0;
            font-size: 0.85em;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-card .val {
            font-size: 1.4em;
            font-weight: 800;
            color: #0f172a;
            margin-top: 5px;
        }

        .stat-card .label {
            font-size: 0.7em;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .saran-info {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85em;
            color: #c53030;
        }

        .saran-info.warning {
            background: #fffaf0;
            border-color: #feebc8;
            color: #9c4221;
        }

        .saran-info.aman {
            background: #f0fff4;
            border-color: #c6f6d5;
            color: #276749;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 20px;
            width: 100%;
        }

        /* Penyesuaian agar kartu tidak penyet saat zoom in */
        .main-grid>div {
            min-width: 0;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .card-title {
            font-size: 0.9em;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-monthly {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8em;
        }

        .table-monthly td,
        .table-monthly th {
            padding: 8px;
            border-bottom: 1px solid #f1f5f9;
        }

        .idx-badge {
            padding: 3px 10px;
            border-radius: 5px;
            font-weight: 800;
            font-size: 0.85em;
            display: inline-block;
        }

        .idx-A {
            background: #c6efce;
            color: #006100;
        }

        .idx-B {
            background: #e2efda;
            color: #375623;
        }

        .idx-C {
            background: #ffeb9c;
            color: #9c5700;
        }

        .idx-D {
            background: #ffc7ce;
            color: #9c0006;
        }

        .idx-E {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Penyesuaian agar mentok kanan */
        .main-content {
            padding-right: 15px !important;
        }

        .table-scroll {
            height: 320px;
            overflow-y: auto;
            padding-right: 5px;
        }

        /* Kustom Scrollbar biar cantik */
        .table-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .table-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .table-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }

        /* Responsif saat zoom atau layar kecil */
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 15px !important;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>
        <div class="main-content">
            <div class="header-row">
                <div class="welcome-text">
                    <h1>Halo, <?php echo $display_name; ?></h1>
                    <p>Monitor Performa Kantor Cabang <strong><?php echo $u['nama_cabang']; ?></strong></p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-minus" style="font-size: 12px;"></i>
                    </button>
                    <div class="filter-periode-container no-chevron">
                        <i class="fas fa-calendar-check"></i>
                        <select id="tahunSelect" class="input-year-select" onchange="window.location.href='beranda.php?filter_tahun='+this.value">
                            <!-- Akan diisi oleh JS -->
                        </select>
                    </div>
                    <button type="button" onclick="tambahTahun()" title="Tambah Tahun" style="background: var(--biru-utama); color: white; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-plus" style="font-size: 12px; color: white;"></i>
                    </button>
                </div>

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
                </script>
            </div>

            <?php if (!$is_profil_lengkap): ?>
                <div style="display: flex; align-items: center; justify-content: center; min-height: 600px; background: #f8fafc; border-radius: 12px; margin-top: 20px;">
                    <div style="text-align: center; padding: 40px 30px;">
                        <div style="font-size: 4em; margin-bottom: 20px; color: #94a3b8;">📋</div>
                        <h2 style="margin: 0 0 12px; color: #475569; font-size: 1.5em; font-weight: 700;">Data Profil Belum Lengkap</h2>
                        <p style="margin: 0; color: #64748b; max-width: 500px; line-height: 1.6; font-size: 0.95em;">Hubungi Divisi BKU (Bisnis, Retail Konsumen, dan UMKM) untuk melengkapi data profil Pemimpin Cabang Anda terlebih dahulu sebelum mengakses dashboard.</p>
                    </div>
                </div>
            <?php else: ?>

                <div class="action-row">
                    <a href="kertas_kerja.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="action-btn">
                        <div class="icon" style="background: rgba(2, 69, 163, 0.1); color: var(--p-blue);"><i class="fas fa-eye"></i></div>
                        <h4>Lihat Kertas Kerja</h4>
                    </a>
                    <a href="penilaian_kompetensi.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="action-btn">
                        <div class="icon" style="background: rgba(128, 90, 213, 0.1); color: var(--p-purple);"><i class="fas fa-brain"></i></div>
                        <h4>Penilaian Kompetensi</h4>
                    </a>
                    <a href="penilaian_akhir.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="action-btn">
                        <div class="icon" style="background: rgba(56, 161, 105, 0.1); color: var(--p-green);"><i class="fas fa-file-invoice-dollar"></i></div>
                        <h4>Lihat Resume Akhir</h4>
                    </a>
                </div>

                <div class="stats-row">
                    <div class="stat-card">
                        <div style="background: #f8fafc; padding: 12px; border-radius: 10px;"><i class="fas fa-star" style="color: #fbbf24;"></i></div>
                        <div>
                            <div class="label">Rata-rata Skor</div>
                            <div class="val"><?php echo number_format($average_score, 2, ',', '.'); ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div style="background: #f8fafc; padding: 12px; border-radius: 10px;"><i class="fas fa-calendar-check" style="color: #3b82f6;"></i></div>
                        <div>
                            <div class="label">Bulan Terdata</div>
                            <div class="val"><?php echo $count_months; ?> / 12</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div style="background: #f8fafc; padding: 12px; border-radius: 10px;"><i class="fas fa-award" style="color: #10b981;"></i></div>
                        <div>
                            <div class="label">Indeks Tahunan</div>
                            <div class="val"><span class="idx-badge idx-<?php echo $indeks_tahunan; ?>"><?php echo $indeks_tahunan; ?></span></div>
                        </div>
                    </div>
                </div>

                <?php if ($count_months == 0): ?>
                    <div class="saran-info warning">
                        <i class="fas fa-info-circle"></i>
                        <span>Data performa untuk tahun <strong><?php echo $tahun_aktif; ?></strong> belum tersedia atau belum ada yang diselesaikan.</span>
                    </div>
                <?php elseif (!empty($bulan_kosong)): ?>
                    <div class="saran-info warning">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><strong>Lengkapi Data:</strong> Bulan <strong><?php echo implode(', ', $bulan_kosong); ?></strong> belum diisi. Harap segera lengkapi penilaian Anda.</span>
                    </div>
                <?php elseif (!empty($bulan_kurang)): ?>
                    <div class="saran-info">
                        <i class="fas fa-chart-line"></i>
                        <span><strong>Perhatian:</strong> Performa bulan <strong><?php echo implode(', ', $bulan_kurang); ?></strong> masih di bawah kriteria Baik. Segera tinjau kembali realisasi KPI Anda.</span>
                    </div>
                <?php else: ?>
                    <div class="saran-info aman">
                        <i class="fas fa-check-circle"></i>
                        <span>Luar biasa! Seluruh laporan yang terisi pada tahun <strong><?php echo $tahun_aktif; ?></strong> sudah memenuhi kriteria <strong>Baik/Memuaskan</strong>.</span>
                    </div>
                <?php endif; ?>

                <div class="main-grid">
                    <div class="card" style="position: relative;">
                        <div class="card-title"><i class="fas fa-chart-line" style="color: var(--p-blue);"></i> Tren Performa Bulanan</div>
                        <?php if ($count_months == 0): ?>
                            <div style="height: 250px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 10px; color: #94a3b8; flex-direction: column; gap: 10px;">
                                <i class="fas fa-chart-area fa-3x"></i>
                                <p style="margin: 0; font-size: 0.9em; font-weight: 600;">Data Performa Tidak Tersedia</p>
                            </div>
                        <?php else: ?>
                            <div style="height: 250px;">
                                <canvas id="performaChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <div class="card-title"><i class="fas fa-list-ul" style="color: var(--p-purple);"></i> Rincian Nilai</div>
                        <div class="table-scroll">
                            <table class="table-monthly">
                                <thead>
                                    <tr>
                                        <th>Bulan</th>
                                        <th class="text-center">Skor</th>
                                        <th class="text-center">Index</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nama_bulan_long as $m => $nm):
                                        $skor = $history_data[$m];
                                        $idx = '-';
                                        if ($skor > 0) {
                                            if ($skor >= 4.51) $idx = 'A';
                                            elseif ($skor >= 3.00) $idx = 'B';
                                            elseif ($skor >= 2.01) $idx = 'C';
                                            else $idx = 'D';
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo $nm; ?></td>
                                            <td class="text-center"><?php echo ($skor > 0) ? number_format($skor, 2, ',', '.') : '-'; ?></td>
                                            <td class="text-center"><?php if ($idx != '-'): ?><span class="idx-badge idx-<?php echo $idx; ?>"><?php echo $idx; ?></span><?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($is_profil_lengkap && $count_months > 0): ?>
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
</body>

</html>