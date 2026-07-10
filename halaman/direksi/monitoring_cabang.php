<?php
session_start();
require "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Direksi') {
    header('Location: ../../index.php');
    exit();
}

$title = "Monitoring Dokumen Cabang";
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : date('Y');

// --- 1. Ambil Daftar Cabang ---
$q_cabang = "SELECT * FROM cabang ORDER BY id_cabang ASC";
$res_cabang = mysqli_query($koneksi, $q_cabang);

// --- 2. Ambil Data Pemimpin Cabang ---
$pincas = [];
$q_pinca = "SELECT id_user, id_cabang, username, nama, no_hp FROM users WHERE jabatan = 'Pemimpin Cabang'";
$res_pinca = mysqli_query($koneksi, $q_pinca);
while ($p = mysqli_fetch_assoc($res_pinca)) {
    $pincas[$p['id_cabang']] = $p;
}
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
        .row-cabang {
            transition: all 0.3s;
        }

        .row-cabang:hover {
            background-color: #f0f7ff !important;
        }

        .btn-monitor {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.75em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: bold;
            transition: 0.2s;
            border: 1px solid transparent;
        }

        .btn-kontrak {
            background: #e3f2fd;
            color: #1976d2;
            border-color: #bbdefb;
        }

        .btn-kertas {
            background: #fff8e1;
            color: #fbc02d;
            border-color: #ffecb3;
        }

        .btn-formulir {
            background: #e8f5e9;
            color: #388e3c;
            border-color: #c8e6c9;
        }

        .btn-monitor:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h1>Monitoring Dokumen Cabang</h1>
                    <p>Pantau Kontrak Kerja, Kertas Kerja, dan Formulir Penilaian Pemimpin Cabang.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-minus" style="font-size: 12px;"></i>
                    </button>
                    <div class="filter-periode-container no-chevron">
                        <i class="fas fa-calendar-check"></i>
                        <select id="tahunSelect" class="input-year-select" onchange="location.href='monitoring_cabang.php?filter_tahun='+this.value">
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

            <div class="kartu">
                <table class="tabel-kpi">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Kantor Cabang</th>
                            <th>Pemimpin Cabang</th>
                            <th>Nomor HP</th>
                            <th style="text-align: center;">Aksi Monitoring</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($cab = mysqli_fetch_assoc($res_cabang)):
                            $pinca = isset($pincas[$cab['id_cabang']]) ? $pincas[$cab['id_cabang']] : null;
                        ?>
                            <tr class="row-cabang">
                                <td style="text-align: center; color: #999;"><?php echo $no++; ?></td>
                                <td>
                                    <strong class="teks-utama"><?php echo $cab['nama_cabang']; ?></strong>
                                </td>
                                <td>
                                    <?php if ($pinca): ?>
                                        <span style="color: #4a5568; font-weight: 500;">
                                            <i class="fas fa-user-tie" style="margin-right: 8px; color: #cbd5e0;"></i>
                                            <?php
                                            $display_name = (!empty($pinca['nama'])) ? $pinca['nama'] : "Pemimpin Cabang " . $cab['nama_cabang'];
                                            echo $display_name;
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <small style="color: #fc8181; font-style: italic;">
                                            <i class="fas fa-user-slash" style="margin-right: 5px; opacity: 0.5;"></i>
                                            Belum Terdaftar
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($pinca && !empty($pinca['no_hp'])): ?>
                                        <span style="color: #4a5568; font-weight: 500;">
                                            <i class="fas fa-phone" style="margin-right: 8px; color: #cbd5e0;"></i>
                                            <?php echo htmlspecialchars($pinca['no_hp']); ?>
                                        </span>
                                    <?php elseif ($pinca): ?>
                                        <small style="color: #a0aec0; font-style: italic;">-</small>
                                    <?php else: ?>
                                        <span style="color: #ccc;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($pinca): ?>
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <a href="monitoring_detail.php?id_user=<?php echo $pinca['id_user']; ?>&id_cabang=<?php echo $cab['id_cabang']; ?>&filter_tahun=<?php echo $tahun_aktif; ?>" class="btn-monitor" style="background: #e3f2fd; color: #1976d2; border-color: #bbdefb;" title="Lihat Detail Monitoring">
                                                <i class="fas fa-list-check"></i> Detail Monitoring
                                            </a>
                                            <a href="monitoring_grafik.php?id_user=<?php echo $pinca['id_user']; ?>&id_cabang=<?php echo $cab['id_cabang']; ?>&filter_tahun=<?php echo $tahun_aktif; ?>" class="btn-monitor" style="background: #f3e5f5; color: #7b1fa2; border-color: #e1bee7;" title="Lihat Grafik Nilai Akhir">
                                                <i class="fas fa-chart-line"></i> Lihat Grafik
                                            </a>
                                            <a href="monitoring_grafik_indikator.php?id_user=<?php echo $pinca['id_user']; ?>&id_cabang=<?php echo $cab['id_cabang']; ?>&filter_tahun=<?php echo $tahun_aktif; ?>" class="btn-monitor" style="background: #e8f5e9; color: #388e3c; border-color: #c8e6c9;" title="Lihat Grafik Indikator">
                                                <i class="fas fa-chart-bar"></i> Grafik Indikator
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #ccc;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>