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

$tahun = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');

// --- Guard: Cek Profil Lengkap ---

$bulan = 0; // PAKSA TAHUNAN: Kontrak Kerja adalah komitmen janji kinerja 1 tahun penuh.
$is_tahunan_forced = true;

$nama_bulan = get_nama_bulan();

$bulan_nama = "Januari"; // Default tanggal persetujuan di awal tahun (Januari)
$label_tahunan = "Tahunan";

// --- 1. Ambil Profil & Grade ---
$q_user = "SELECT u.*, c.nama_cabang 
           FROM users u 
           JOIN cabang c ON u.id_cabang = c.id_cabang
           WHERE u.id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

// Penentuan Grade: Ambil dari input profil jika ada, jika tidak pakai default cerdas
$grade = (!empty($u['level_kip'])) ? $u['level_kip'] : "G-10";

// Nama Cabang Format: Capital Each Word (e.g. Baturaja)
$lokasi_kerja = isset($u['nama_cabang']) ? ucwords(strtolower($u['nama_cabang'])) : 'Baturaja';

// --- 2. Ambil Sasaran Strategis ---
$sasaran_mapping = [];
$q_sasaran = "SELECT id_perspektif, nama_sasaran FROM sasaran_strategis WHERE tahun = $tahun";
$res_sasaran = mysqli_query($koneksi, $q_sasaran);
while ($s = mysqli_fetch_assoc($res_sasaran)) {
    $sasaran_mapping[$s['id_perspektif']] = $s['nama_sasaran'];
}

// --- 3. Ambil Data Indikator & Target ---
// Ambil target tahunan master
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

// Ambil seluruh target bulanan manual
$q_all_mon = "SELECT id_indikator, bulan, nilai_target FROM target WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan IS NOT NULL";
$res_all_mon = mysqli_query($koneksi, $q_all_mon);
$all_monthly_targets = [];
while ($t = mysqli_fetch_assoc($res_all_mon)) {
    $all_monthly_targets[$t['id_indikator']][$t['bulan']] = (float)$t['nilai_target'];
}

$data_kontrak = [];
$total_bobot_kpi = 0;
$target_ditemukan = false;

while ($row = mysqli_fetch_assoc($res_ind)) {
    $id_ind = $row['id_indikator'];
    $target_ann = !is_null($row['target_tahunan']) ? (float)$row['target_tahunan'] : null;
    $manual_targets = isset($all_monthly_targets[$id_ind]) ? $all_monthly_targets[$id_ind] : [];

    if ($bulan > 0) {
        // TAMPILAN BULANAN
        if (isset($manual_targets[$bulan])) {
            // Gunakan target manual jika ada
            $row['target'] = $manual_targets[$bulan];
        } else {
            // Kalkulasi target dinamis dari sisa tahunan
            if (!is_null($target_ann)) {
                $sum_manual = array_sum($manual_targets);
                $count_manual = count($manual_targets);
                $remaining_months = 12 - $count_manual;

                if ($remaining_months > 0) {
                    $row['target'] = ($target_ann - $sum_manual) / $remaining_months;
                    // Proteksi jika sisa jadi negatif (input manual kegedean)
                    if ($row['target'] < 0) $row['target'] = 0;
                } else {
                    $row['target'] = 0;
                }
            } else {
                $row['target'] = null;
            }
        }
    } else {
        // TAMPILAN TAHUNAN
        $row['target'] = $target_ann;
    }

    if (!is_null($row['target'])) {
        $target_ditemukan = true;
    }

    // Kelompokkan: Perspektif -> Sasaran -> Sub-Perspektif -> Indikator
    $p_name = $row['nama_perspektif'];
    $s_name = !empty($row['nama_sasaran']) ? $row['nama_sasaran'] : (!empty($row['sasaran_strategis']) ? $row['sasaran_strategis'] : 'Tanpa Sasaran');
    $sub_name = $row['nama_subperspektif'];

    $data_kontrak[$p_name]['sasaran'][$s_name][$sub_name][] = $row;
    $total_bobot_kpi += $row['bobot'];
}
$is_iframe = isset($_GET['iframe']) && (int)$_GET['iframe'] == 1;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrak Kerja Pegawai - BSB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <style>
        .input-kontrol {
            padding: 8px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
        }

        .sheet-area {
            background: white;
            padding: 40px;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            color: #000;
        }

        @media print {

            .sidebar,
            .no-print {
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

    <div class="dashboard-container">
        <?php if (!$is_iframe) include "../komponen/sidebar.php"; ?>

        <div class="main-content" <?php if ($is_iframe) echo 'style="margin-left: 0 !important; width: 100% !important; padding: 10px !important;"'; ?>>
            <?php if (!$is_iframe): ?>
                <div class="halaman-header no-print">
                    <h1>Kontrak Kerja KPI</h1>
                    <p>Target dan bobot KPI tahunan untuk Cabang <?php echo $u['nama_cabang'] ?? '-'; ?>.</p>
                </div>

                <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <?php if ($_SESSION['jabatan'] == 'Divisi'): ?>
                            <a href="../divisi/monitoring_cabang.php" class="tombol" style="background: #4a5568; color: white; padding: 8px 15px;">
                                <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
                            </a>
                        <?php endif; ?>

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

                        <script>
                            const tahunAktif = <?php echo $tahun; ?>;
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
                    <?php if ($target_ditemukan): ?>
                        <button onclick="cetakPDF()" type="button" class="tombol" style="background: #edf2f7; color: #4a5568;"><i class="fas fa-print"></i> Cetak</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($target_ditemukan): ?>
                <?php
                $html_kk_web = "";
                require "templates/tpl_kontrak_kerja.php";
                echo $html_kk_web;
                ?>
            <?php else: ?>
                <div class="kartu" style="text-align: center; padding: 80px 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 15px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <i class="fas fa-file-signature" style="font-size: 4em; color: #a0aec0; margin-bottom: 20px;"></i>
                    <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Kontrak Kerja Belum Tersedia</h3>

                    <?php if ($_SESSION['jabatan'] == 'Divisi'): ?>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto; margin-bottom: 25px;">
                            Target dan Bobot KPI untuk tahun <strong><?php echo $tahun; ?></strong> belum diatur oleh Divisi BKU.
                        </p>
                        <a href="../divisi/target_cabang.php" target="_parent" class="tombol tombol-utama" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 25px;">
                            <i class="fas fa-tasks"></i> Atur Target & KPI Sekarang
                        </a>
                    <?php else: ?>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto;">
                            Data Kontrak Kerja untuk tahun <?php echo $tahun; ?> belum tersedia atau belum di-input oleh Divisi BKU. Silakan hubungi Divisi terkait.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateNav() {
            const thn = document.getElementById('tahunSelect').value;
            const urlParams = new URLSearchParams(window.location.search);
            const idUser = urlParams.get('id_user');
            const idCabang = urlParams.get('id_cabang');

            let url = `kontrak_kerja.php?filter_tahun=${thn}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            window.location.href = url;
        }

        function cetakPDF() {
            const thn = document.getElementById('tahunSelect').value;
            const urlParams = new URLSearchParams(window.location.search);
            const bln = urlParams.get('filter_bulan') || 0;
            const idUser = urlParams.get('id_user');
            const idCabang = urlParams.get('id_cabang');

            let url = `cetak/cetak_kontrak.php?filter_tahun=${thn}&filter_bulan=${bln}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            // Gunakan hidden iframe agar dialog print muncul di atas halaman ini
            let frame = document.getElementById('printFrame');
            if (!frame) {
                frame = document.createElement('iframe');
                frame.id = 'printFrame';
                frame.style.display = 'none';
                document.body.appendChild(frame);
            }
            frame.onload = function() {
                setTimeout(function() {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                }, 200);
            };
            frame.src = url;
        }
    </script>

</body>

</html>