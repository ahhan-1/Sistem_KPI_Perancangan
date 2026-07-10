<?php
session_start();
require "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../index.php');
    exit();
}

$title = "Target Cabang";
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');

// --- 1. Ambil Daftar Cabang ---
$q_cabang = "SELECT * FROM cabang ORDER BY id_cabang ASC";
$res_cabang = mysqli_query($koneksi, $q_cabang);

// --- 2. Hitung Total Indikator per Cabang ---
$q_count_ind = "SELECT id_cabang, COUNT(*) as total FROM indikator WHERE tahun = $tahun_aktif GROUP BY id_cabang";
$res_count_ind = mysqli_query($koneksi, $q_count_ind);
$indikator_cabang_count = [];
while ($row_count_ind = mysqli_fetch_assoc($res_count_ind)) {
    $indikator_cabang_count[$row_count_ind['id_cabang']] = (int)$row_count_ind['total'];
}

// Total indikator default untuk info header (ambil dari cabang 1 / Kapten A. Rivai sebagai representasi)
$q_ref_ind = "SELECT COUNT(*) as total FROM indikator WHERE tahun = $tahun_aktif AND id_cabang = 1";
$res_ref_ind = mysqli_query($koneksi, $q_ref_ind);
$row_ref_ind = mysqli_fetch_assoc($res_ref_ind);
$total_indikator_ref = $row_ref_ind ? (int)$row_ref_ind['total'] : 0;

// --- 3. Ambil Statistik Pengisian Target ---
$stats_input = [];
$q_stats = "SELECT id_cabang, COUNT(id_target) as terisi FROM target WHERE tahun = $tahun_aktif GROUP BY id_cabang";
$res_stats = mysqli_query($koneksi, $q_stats);
while ($s = mysqli_fetch_assoc($res_stats)) {
    $stats_input[$s['id_cabang']] = $s['terisi'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - KPI BSB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <style>
        .row-cabang {
            transition: all 0.3s;
        }

        .row-cabang:hover {
            background-color: #f0f7ff !important;
        }

        .btn-kelola {
            background-color: var(--biru-utama);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-kelola:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 69, 163, 0.2);
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header no-print">
                <h1>Data Target Cabang</h1>
                <p>Monitor dan kelola angka target tahunan untuk seluruh kantor cabang.</p>
            </div>

            <!-- --- 4. Filter Navigasi --- -->
            <div class="no-print" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fas fa-minus" style="font-size: 12px;"></i>
                        </button>
                        <div class="filter-periode-container no-chevron">
                            <i class="fas fa-calendar-check"></i>
                            <select id="tahunSelect" class="input-year-select" onchange="window.location.href='target_cabang.php?filter_tahun='+this.value">
                                <!-- Akan diisi oleh JS -->
                            </select>
                        </div>
                        <button type="button" onclick="tambahTahun()" title="Tambah Tahun" style="background: var(--biru-utama); color: white; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fas fa-plus" style="font-size: 12px; color: white;"></i>
                        </button>
                    </div>

                    <div class="kartu-info-kecil" style="background: white; padding: 8px 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; align-items: center;">
                        <small style="color: #64748b; margin-right: 5px;">Total Indikator (<?php echo $tahun_aktif; ?>):</small>
                        <strong style="color: var(--biru-utama);"><?php echo $total_indikator_ref; ?></strong>
                    </div>
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

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Tahun ' + maxYear + ' ditambahkan ke daftar'
                        });
                    }

                    function kurangTahun() {
                        let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
                        if (maxYear > 2026) {
                            maxYear--;
                            localStorage.setItem('max_year_kpi', maxYear);
                            renderTahun();
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            });
                            Toast.fire({
                                icon: 'success',
                                title: 'Tahun ' + (maxYear + 1) + ' dihapus dari daftar'
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Batas Minimum',
                                text: 'Tahun tidak boleh kurang dari 2026.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    }

                    renderTahun();
                </script>
            </div>

            <!-- --- 5. Import Template Excel --- -->
            <div class="kartu" style="margin-bottom: 25px; padding: 20px; background: #fafcff; border-left: 5px solid #0056b3; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 280px; max-width: 600px;">
                    <h3 style="margin:0 0 5px; color:#1e293b; font-size:1.1em; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-file-excel" style="color:#28a745;"></i> Import Target Cabang
                    </h3>
                    <p style="margin:0; color:#64748b; font-size:0.85em; line-height: 1.4;">Unduh template di samping (format .xlsx), lengkapi data target, lalu unggah kembali.</p>
                </div>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content: flex-end;">
                    <a href="../../fungsi/kpi_manajemen_excel.php?aksi=template_target&tahun=<?php echo $tahun_aktif; ?>" class="btn-kelola" style="background:#28a745; color:white;">
                        <i class="fas fa-download"></i> Unduh Template (.xlsx)
                    </a>
                    <form action="../../fungsi/kpi_manajemen_excel.php" method="POST" enctype="multipart/form-data" style="display:flex; align-items:center; gap:8px;" id="formImportTarget">
                        <input type="hidden" name="aksi_utama" value="import_target">
                        <input type="hidden" name="tahun" value="<?php echo $tahun_aktif; ?>">
                        <input type="file" name="file" accept=".xlsx" required style="font-size:0.85em; max-width:200px;">
                        <button type="submit" class="btn-kelola" style="background:#0245a3; color:white; border:none; cursor:pointer;">
                            <i class="fas fa-upload"></i> Import
                        </button>
                    </form>
                </div>
            </div>

            <div class="kartu">
                <table class="tabel-kpi">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Kantor Cabang</th>
                            <th width="200" style="text-align: center;">Status Target</th>
                            <th width="150" style="text-align: center;">Progres</th>
                            <th width="180" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_indikator_ref == 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 50px 20px;">
                                    <i class="fas fa-folder-open" style="font-size: 4em; color: #ddd; margin-bottom: 20px; display: block;"></i>
                                    <h3 style="color: #666; font-weight: 500;">Belum Ada Struktur Indikator</h3>
                                    <p style="color: #999; margin-bottom: 25px;">Struktur target tahun <?php echo $tahun_aktif; ?> belum diatur oleh Divisi.</p>
                                    <a href="kpi_daftar.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="tombol tombol-utama">
                                        <i class="fas fa-circle-plus"></i> Atur Struktur Sekarang
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $no = 1;
                            while ($cab = mysqli_fetch_assoc($res_cabang)):
                                $total_ind_cab = isset($indikator_cabang_count[$cab['id_cabang']]) ? $indikator_cabang_count[$cab['id_cabang']] : 0;
                                $total_required = $total_ind_cab * 12;
                                $terisi = isset($stats_input[$cab['id_cabang']]) ? $stats_input[$cab['id_cabang']] : 0;
                                $persen = ($total_required > 0) ? round(($terisi / $total_required) * 100) : 0;
                            ?>
                                <tr class="row-cabang">
                                    <td style="text-align: center; color: #999;"><?php echo $no++; ?></td>
                                    <td>
                                        <strong class="teks-utama"><?php echo $cab['nama_cabang']; ?></strong><br>
                                        <small style="color: #888;">Kode: <?php echo $cab['kode_cabang']; ?></small>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if ($total_required > 0 && $terisi >= $total_required): ?>
                                            <span class="badge-pill badge-hijau">
                                                <i class="fas fa-check-circle"></i> Lengkap
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-pill badge-kuning">
                                                <i class="fas fa-clock"></i> Belum Selesai
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="flex: 1; height: 8px; background: #eee; border-radius: 4px; overflow: hidden;">
                                                <div style="width: <?php echo $persen; ?>%; height: 100%; background: var(--biru-utama); border-radius: 4px;"></div>
                                            </div>
                                            <small style="font-weight: bold; min-width: 35px;"><?php echo $persen; ?>%</small>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; flex-direction: column; gap: 6px; align-items: center; justify-content: center;">
                                            <a href="target_form.php?id_cabang=<?php echo $cab['id_cabang']; ?>&tahun=<?php echo $tahun_aktif; ?>" class="btn-kelola" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; max-width: 200px; padding: 10px 12px; border-radius: 6px; font-size: 0.85em; margin: 0; box-sizing: border-box;">
                                                <i class="fas fa-eye"></i> Lihat Target Tahunan
                                            </a>
                                            <a href="target_koreksi.php?id_cabang=<?php echo $cab['id_cabang']; ?>&tahun=<?php echo $tahun_aktif; ?>" class="btn-kelola" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #0284c7; color: white; border-color: #0284c7; width: 100%; max-width: 200px; padding: 10px 12px; border-radius: 6px; font-size: 0.85em; margin: 0; box-sizing: border-box;">
                                                <i class="fas fa-calendar-check"></i> Kelola Target Bulanan
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses_import'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Sebanyak <?php echo isset($_GET['count']) ? (int)$_GET['count'] : 0; ?> target cabang telah berhasil di-import.',
                confirmButtonColor: '#0245A3'
            });
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'error_kosong'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Data Kosong!',
                text: '<?php echo isset($_GET['pesan_error']) ? addslashes($_GET['pesan_error']) : "Terdapat sel yang masih kosong."; ?>',
                confirmButtonColor: '#0245A3'
            });
        <?php elseif (isset($_GET['pesan']) && $_GET['pesan'] == 'gagal_upload'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Upload file gagal atau file tidak ditemukan.',
                confirmButtonColor: '#0245A3'
            });
        <?php endif; ?>
    </script>

</body>

</html>