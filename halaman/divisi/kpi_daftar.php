<?php
session_start();
require_once "../../fungsi/koneksi.php";

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../index.php');
    exit();
}

$title = "Daftar KPI";

// --- 1. Filter Utama ---
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$id_cabang = isset($_GET['id_cabang']) ? (int)$_GET['id_cabang'] : 0;

global $koneksi;

$nama_cabang_aktif = "";
if ($id_cabang > 0) {
    $res_cab_aktif = mysqli_query($koneksi, "SELECT nama_cabang FROM cabang WHERE id_cabang = $id_cabang");
    if ($row_cab_aktif = mysqli_fetch_assoc($res_cab_aktif)) {
        $nama_cabang_aktif = $row_cab_aktif['nama_cabang'];
    }
}

// --- 2. Ambil Data Indikator (jika cabang dipilih) ---
$res_list = null;
$res_branches = null;
if ($id_cabang > 0) {
    $q_list = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, p.id_perspektif, ss.nama_sasaran,
                      (SELECT COUNT(*) FROM skala_kpi WHERE id_indikator = i.id_indikator) as jml_skala 
               FROM indikator i 
               JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif 
               JOIN perspektif p ON s.id_perspektif = p.id_perspektif 
               LEFT JOIN sasaran_strategis ss ON i.id_sasaran = ss.id_sasaran
               WHERE i.tahun = $tahun_aktif AND i.id_cabang = $id_cabang
               ORDER BY p.id_perspektif ASC, i.id_indikator ASC";
    $res_list = mysqli_query($koneksi, $q_list);
} else {
    // Ambil daftar 27 cabang
    $q_branches = "SELECT c.*, 
                    (SELECT SUM(bobot) FROM indikator WHERE id_cabang = c.id_cabang AND tahun = $tahun_aktif) as total_bobot,
                    (SELECT COUNT(*) FROM indikator WHERE id_cabang = c.id_cabang AND tahun = $tahun_aktif) as jml_indikator
                    FROM cabang c 
                    ORDER BY c.id_cabang ASC";
    $res_branches = mysqli_query($koneksi, $q_branches);
}

// --- 3. Ambil Sasaran Strategis ---
$sasaran_list = [];
$q_s = "SELECT * FROM sasaran_strategis WHERE tahun = $tahun_aktif";
$res_s = mysqli_query($koneksi, $q_s);
while ($s = mysqli_fetch_assoc($res_s)) {
    $sasaran_list[] = $s;
}

// --- 4. Ambil Data Perspektif ---
$master_p = [];
$res_p = mysqli_query($koneksi, "SELECT * FROM perspektif ORDER BY id_perspektif ASC");
while ($p = mysqli_fetch_assoc($res_p)) {
    $master_p[] = $p;
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
        /* CSS Modal Sederhana Konsisten dengan kpi_tambah */
        .modal-sederhana {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
        }

        /* Paksa SweetAlert selalu di paling depan */
        .swal2-container {
            z-index: 9999 !important;
        }

        .modal-konten {
            background-color: #fff;
            margin: 2% auto;
            padding: 0;
            border-radius: 12px;
            width: 550px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: none;
        }

        .modal-header {
            background: var(--biru-utama);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.1em;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 12px 20px;
            border-top: 1px solid #eee;
            text-align: right;
            background: #fafafa;
            flex-shrink: 0;
        }

        .skala-input-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            gap: 15px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #f0f0f0;
            transition: 0.2s;
        }

        .skala-input-row:hover {
            background: #f9f9f9;
        }

        .skala-label {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            flex-shrink: 0;
        }

        .s-5 {
            background: #28a745;
        }

        .s-4 {
            background: #1e88e5;
        }

        .s-3 {
            background: #ffa000;
        }

        .s-2 {
            background: #f4511e;
        }

        .s-1 {
            background: #d32f2f;
        }

        .input-group-custom {
            flex: 1;
        }

        .input-group-custom label {
            display: block;
            font-size: 0.8em;
            color: #888;
            margin-bottom: 2px;
        }

        .inverse-box {
            background: #fff5f5;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #feb2b2;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Edit Mode Locking */
        .btn-aksi-group {
            display: none;
            /* Sembunyikan secara default */
            justify-content: center;
            gap: 10px;
            align-items: center;
        }

        .badge-siap {
            background: #e6fffa;
            color: #28a745;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7em;
            font-weight: 600;
            border: 1px solid #b2f5ea;
        }

        .badge-belum {
            background: #fffaf0;
            color: #856404;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7em;
            font-weight: 600;
            border: 1px solid #feebc8;
        }

        .indikator-wrapper {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include "../komponen/sidebar.php"; ?>

        <!-- Main Content -->
        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Daftar Struktur KPI <?php echo $id_cabang > 0 ? "- " . htmlspecialchars($nama_cabang_aktif) : ""; ?></h1>
                    <p>Monitoring dan pembaruan struktur target tahunan.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-minus" style="font-size: 12px;"></i>
                    </button>
                    <div class="filter-periode-container no-chevron">
                        <i class="fas fa-calendar-check"></i>
                        <select id="tahunSelect" class="input-year-select" onchange="gantiTahunFilter(this.value)">
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



            <?php if ($id_cabang > 0): ?>
                <a href="kpi_daftar.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="tombol" style="background: #e2e8f0; color: #4a5568; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Cabang
                </a>

                <div class="kartu" style="margin-bottom: 80px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 class="teks-utama" style="margin: 0;">
                            <i class="fas fa-file-signature"></i> Struktur KPI Tahun <?php echo $tahun_aktif; ?>
                        </h3>
                        <?php if ($res_list && mysqli_num_rows($res_list) > 0): ?>
                            <button type="button" class="tombol bg-cyan" id="btnEditMode" onclick="aktifkanEditMode()">
                                <i class="fas fa-pen-to-square"></i> Edit Struktur
                            </button>
                            <button type="button" class="tombol bg-cyan" id="btnTambahBaris" style="display: none;" onclick="tambahBaris()">
                                <i class="fas fa-plus"></i> Tambah Indikator
                            </button>
                        <?php endif; ?>
                    </div>

                    <form action="../../fungsi/kpi_manajemen_indikator.php" method="POST" id="formDaftar">
                        <input type="hidden" name="aksi" value="update_masal">
                        <input type="hidden" name="filter_tahun" value="<?php echo $tahun_aktif; ?>">
                        <input type="hidden" name="id_cabang" value="<?php echo $id_cabang; ?>">
                        <style>
                            .tabel-kpi {
                                border-radius: 12px !important;
                                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                            }

                            .tabel-kpi th {
                                background-color: #0245A3 !important;
                                color: #ffffff;
                                padding: 15px 12px;
                                font-weight: 700;
                                text-transform: capitalize;
                                border: none;
                            }

                            .tabel-kpi input.input-kontrol-sm,
                            .tabel-kpi select.input-kontrol-sm {
                                width: 100% !important;
                                box-sizing: border-box;
                                border: 1px solid #e2e8f0;
                                border-radius: 6px;
                                padding: 8px 10px;
                                font-size: 0.85em;
                                background-color: #fff !important;
                                transition: all 0.2s;
                            }

                            .tabel-kpi input.input-edit:focus,
                            .tabel-kpi select.input-edit:focus {
                                border-color: #0245A3;
                                outline: none;
                                box-shadow: 0 0 0 3px rgba(2, 69, 163, 0.1);
                            }

                            .indikator-wrapper {
                                display: flex;
                                flex-direction: column;
                                gap: 4px;
                                width: 100%;
                            }
                        </style>
                        <table class="tabel-kpi" style="table-layout: fixed; width: 100%;">
                            <thead>
                                <tr>
                                    <th width="10%">Perspektif</th>
                                    <th width="20%">Sasaran Strategis</th>
                                    <th width="15%">Sub-Perspektif</th>
                                    <th width="28%">Indikator</th>
                                    <th width="8%">Satuan</th>
                                    <th width="10%">Bobot (%)</th>
                                    <th width="9%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($res_list) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($res_list)): ?>
                                        <tr class="baris-data" data-id-p="<?php echo $row['id_perspektif']; ?>">
                                            <input type="hidden" name="id_indikator[]" value="<?php echo $row['id_indikator']; ?>">
                                            <input type="hidden" name="perspektif[]" value="<?php echo $row['id_perspektif']; ?>">
                                            <input type="hidden" name="skala_data[]" class="skala-data-hidden" value="">
                                            <td style="font-size: 0.8em; color: #666; font-weight: bold;"><?php echo $row['nama_perspektif']; ?></td>
                                            <td>
                                                <div class="view-mode"><?php echo htmlspecialchars($row['nama_sasaran'] ?? $row['sasaran_strategis'] ?? '-'); ?></div>
                                                <div class="edit-mode" style="display: none;">
                                                    <input type="text" name="sasaran_strategis[]" class="input-kontrol-sm" value="<?php echo htmlspecialchars($row['nama_sasaran'] ?? $row['sasaran_strategis'] ?? ''); ?>" placeholder="Input Sasaran Strategis..." oninput="hitungTotal()">
                                                </div>
                                            </td>
                                            <td><input type="text" name="subperspektif[]" class="input-kontrol-sm input-edit" value="<?php echo $row['nama_subperspektif']; ?>" readonly oninput="hitungTotal()"></td>
                                            <td>
                                                <div class="indikator-wrapper">
                                                    <input type="text" name="indikator[]" class="input-kontrol-sm input-edit" value="<?php echo $row['nama_indikator']; ?>" readonly oninput="hitungTotal()">
                                                    <div class="status-skala-badge" data-id="<?php echo $row['id_indikator']; ?>">
                                                        <?php if ($row['jml_skala'] > 0): ?>
                                                            <span class="badge-siap"><i class="fas fa-check-circle"></i> Skala OK</span>
                                                        <?php else: ?>
                                                            <span class="badge-belum"><i class="fas fa-circle-exclamation"></i> Skala?</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <select name="satuan[]" class="input-kontrol-sm input-edit" disabled onchange="hitungTotal()">
                                                    <option value="Rupiah" <?php echo ($row['satuan'] == 'Rupiah') ? 'selected' : ''; ?>>Rp</option>
                                                    <option value="Score" <?php echo ($row['satuan'] == 'Score') ? 'selected' : ''; ?>>Score</option>
                                                    <option value="Persen" <?php echo ($row['satuan'] == 'Persen') ? 'selected' : ''; ?>>%</option>
                                                </select>
                                            </td>
                                            <td style="text-align: center;">
                                                <div class="input-bobot-container">
                                                    <input type="number" name="bobot[]" class="input-kontrol-sm input-edit bobot-field" step="0.01" value="<?php echo $row['bobot']; ?>" readonly>
                                                    <span class="persen-suffix">%</span>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <div class="btn-aksi-group" data-id="<?php echo $row['id_indikator']; ?>">
                                                    <button type="button" class="tombol bg-cyan" style="padding: 4px 8px; font-size: 0.75em;" title="Skala" onclick="bukaModalSkala(<?php echo $row['id_indikator']; ?>, this)">
                                                        <i class="fas fa-sliders"></i>
                                                    </button>
                                                    <button type="button" class="tombol" style="padding: 4px; border:none; background:none; font-size: 1.2em; color: #ff4d4d;" title="Hapus" onclick="hapusBarisUI(this, <?php echo $row['id_indikator']; ?>)">
                                                        <i class="fas fa-trash-can"></i>
                                                    </button>
                                                </div>
                                                <div class="aksi-placeholder">
                                                    <?php if ($row['jml_skala'] > 0): ?>
                                                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-lock" style="opacity: 0.3;"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 50px 20px;">
                                            <i class="fas fa-folder-open" style="font-size: 4em; color: #ddd; margin-bottom: 20px; display: block;"></i>
                                            <h3 style="color: #666; font-weight: 500;">Belum Ada Struktur Kontrak</h3>
                                            <p style="color: #999; margin-bottom: 25px;">Data struktur kontrak untuk tahun <?php echo $tahun_aktif; ?> belum tersedia.</p>
                                            <a href="kpi_tambah.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="tombol tombol-utama">
                                                <i class="fas fa-circle-plus"></i> Buat Struktur Sekarang
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- Sticky Footer Bobot (Global) -->
                        <div class="sticky-bobot">
                            <div class="bobot-info">
                                <div class="info-teks">
                                    <strong>Total Bobot (<?php echo $tahun_aktif; ?>): </strong>
                                    <span id="labelTotalBobot">0%</span>
                                </div>
                                <div class="progress-container">
                                    <div id="progressBar" class="progress-bar"></div>
                                </div>
                            </div>
                            <div class="bobot-aksi">
                                <span id="statusPesan" style="margin-right: 20px; font-weight: 600;"></span>
                                <button type="submit" id="btnSimpanPerubahan" class="tombol btn-simpan-kpi" style="display: none;">
                                    <i class="fas fa-circle-check"></i> Simpan Perubahan
                                </button>
                                <p id="teksInfo" style="font-size: 0.8em; color: #888;">Klik 'Edit Struktur' untuk mengubah data.</p>
                            </div>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- DAFTAR 27 CABANG -->
                <div class="kartu" style="margin-bottom: 80px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3 class="teks-utama" style="margin: 0;">
                            <i class="fas fa-code-branch"></i> Daftar Cabang (Struktur KPI Tahun <?php echo $tahun_aktif; ?>)
                        </h3>
                        <div style="position: relative; width: 250px;">
                            <input type="text" id="cariCabang" class="input-kontrol" placeholder="Cari cabang..." onkeyup="filterCabang()" style="padding-left: 35px; border-radius: 20px;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa;"></i>
                        </div>
                    </div>
                    <div class="tabel-container">
                        <table class="tabel-kpi" id="tabelCabang">
                            <thead>
                                <tr>
                                    <th style="text-align: center;" width="10%">No</th>
                                    <th width="45%">Nama Kantor Cabang</th>
                                    <th style="text-align: center;" width="15%">Jumlah Indikator</th>
                                    <th style="text-align: center;" width="15%">Total Bobot</th>
                                    <th style="text-align: center;" width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                while ($cab = mysqli_fetch_assoc($res_branches)):
                                    $bobot = (float)($cab['total_bobot'] ?? 0);
                                    $jml = (int)($cab['jml_indikator'] ?? 0);

                                    // Badge status bobot
                                    if ($bobot == 100) {
                                        $badge = '<span class="badge-siap" style="padding: 5px 10px; border-radius: 20px;"><i class="fas fa-check-circle"></i> Valid (100%)</span>';
                                    } else {
                                        $badge = '<span class="badge-belum" style="padding: 5px 10px; border-radius: 20px;"><i class="fas fa-circle-exclamation"></i> Belum Lengkap (' . number_format($bobot, 2, ',', '.') . '%)</span>';
                                    }
                                ?>
                                    <tr class="row-cabang">
                                        <td style="text-align: center; font-weight: bold;"><?php echo $no++; ?></td>
                                        <td style="font-weight: 600; color: #333;"><?php echo $cab['nama_cabang']; ?></td>
                                        <td style="text-align: center;"><?php echo $jml; ?> Indikator</td>
                                        <td style="text-align: center;"><?php echo $badge; ?></td>
                                        <td style="text-align: center;">
                                            <a href="kpi_daftar.php?id_cabang=<?php echo $cab['id_cabang']; ?>&filter_tahun=<?php echo $tahun_aktif; ?>" class="tombol" style="background-color: var(--biru-utama); color: white; padding: 6px 12px; border-radius: 6px; font-size: 0.85em; font-weight: 600; text-decoration: none; display: inline-block;">
                                                <i class="fas fa-sliders" style="margin-right: 5px;"></i> Detail Struktur
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Skala (Konsisten dengan kpi_tambah) -->
    <div id="modalSkala" class="modal-sederhana">
        <div class="modal-konten">
            <div class="modal-header">
                <h3 id="namaIndikatorModal">Atur Skala Nilai</h3>
                <span style="cursor:pointer; font-size: 1.5em;" onclick="tutupModalSkala()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalIdIndikator">
                <div class="inverse-box">
                    <div>
                        <strong style="color: #c53030;">Indikator Terbalik?</strong>
                        <p style="margin: 2px 0 0; font-size: 0.75em; color: #742a2a;">Centang jika semakin kecil angka semakin baik (misal: Fraud).</p>
                    </div>
                    <input type="checkbox" id="modalInverse" style="width: 20px; height: 20px; cursor: pointer;">
                </div>

                <div id="skalaInputs">
                    <?php
                    $skors = [
                        5 => ['label' => 'Istimewa', 'class' => 's-5'],
                        4 => ['label' => 'Melampaui Target', 'class' => 's-4'],
                        3 => ['label' => 'Sesuai Target', 'class' => 's-3'],
                        2 => ['label' => 'Di Bawah Target', 'class' => 's-2'],
                        1 => ['label' => 'Tidak Tercapai', 'class' => 's-1']
                    ];
                    foreach ($skors as $s => $info):
                    ?>
                        <div class="skala-input-row">
                            <div class="skala-label <?php echo $info['class']; ?>"><?php echo $s; ?></div>
                            <div class="input-group-custom">
                                <label>Rating Minimum (%) untuk Skor <?php echo $s; ?> (<?php echo $info['label']; ?>)</label>
                                <div class="input-persen-wrapper" style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" id="t_<?php echo $s; ?>" step="0.01" class="input-kontrol" placeholder="0.00" required style="width: 100%;">
                                    <span style="color: #aaa;">%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tombol" placeholder="Batal" style="background: #eee;" onclick="tutupModalSkala()">Batal</button>
                <button type="button" id="btnSimpanSkala" class="tombol-utama tombol" onclick="simpanSkala()">
                    <i class="fas fa-save"></i> Simpan Skala
                </button>
            </div>
        </div>
    </div>

    <script>
        // --- 5. Mencegah Auto-Submit saat Enter ---
        document.getElementById('formDaftar')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                e.preventDefault();
            }
        });

        function gantiTahunFilter(tahun) {
            window.location.href = "kpi_daftar.php?filter_tahun=" + tahun + "<?php echo $id_cabang > 0 ? '&id_cabang=' . $id_cabang : ''; ?>";
        }

        function filterCabang() {
            const input = document.getElementById('cariCabang');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('tabelCabang');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[1];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        // --- 6. Aktifkan Mode Edit ---
        function aktifkanEditMode() {
            const inputs = document.querySelectorAll('.input-edit');
            inputs.forEach(el => {
                el.removeAttribute('readonly');
                el.removeAttribute('disabled');
                el.style.backgroundColor = "#fff";
                el.style.border = "1px solid var(--biru-muda)";
            });

            // Toggle Mode View/Edit untuk Sasaran
            document.querySelectorAll('.view-mode').forEach(el => el.style.display = "none");
            document.querySelectorAll('.edit-mode').forEach(el => el.style.display = "block");

            document.getElementById('btnEditMode').style.display = "none";
            document.getElementById('btnTambahBaris').style.display = "inline-block";
            document.getElementById('btnSimpanPerubahan').style.display = "inline-block";
            document.getElementById('teksInfo').style.display = "none";

            // Tampilkan tombol aksi (Skala & Hapus)
            document.querySelectorAll('.btn-aksi-group').forEach(el => el.style.display = "flex");
            document.querySelectorAll('.aksi-placeholder').forEach(el => el.style.display = "none");

            Swal.fire({
                icon: 'info',
                title: 'Mode Edit Aktif',
                text: 'Anda sekarang dapat mengubah data. Jangan lupa klik Simpan!',
                timer: 2000,
                showConfirmButton: false
            });

            setupEnterNavigation();
            hitungTotal();
        }

        function hapusBarisUI(btn, id) {
            Swal.fire({
                title: 'Hapus Indikator?',
                text: "Indikator akan dihapus dari daftar. Anda harus menyesuaikan bobot hingga 100% sebelum dapat menyimpan perubahan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const row = btn.closest('tr');
                    row.remove();
                    hitungTotal();

                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Baris dihapus. Silakan sesuaikan bobot.'
                    });
                }
            });
        }

        // --- 7. Fungsi Hitung Total Bobot ---
        function hitungTotal() {
            const inputs = document.querySelectorAll('.bobot-field');
            const rows = document.querySelectorAll('table.tabel-kpi tbody tr');

            const sasarans = document.querySelectorAll('input[name="sasaran_strategis[]"]');

            let total = 0;
            const perspektifTerpilih = new Set();
            let sasaransLengkap = true;

            inputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            rows.forEach(row => {
                const teksPerspektif = row.cells[0].innerText.trim();
                if (teksPerspektif !== "") {
                    perspektifTerpilih.add(teksPerspektif);
                }
            });

            sasarans.forEach(s => {
                if (s.value.trim() === "") sasaransLengkap = false;
            });

            // Bulatkan total untuk menghindari masalah floating point
            total = parseFloat(total.toFixed(2));

            const label = document.getElementById('labelTotalBobot');
            const progressBar = document.getElementById('progressBar');
            const status = document.getElementById('statusPesan');
            const btnSimpan = document.getElementById('btnSimpanPerubahan');

            if (!label || !progressBar || !status || !btnSimpan) return;

            label.innerText = total.toFixed(2) + "%";

            const progressWidth = Math.min(total, 100);
            progressBar.style.width = progressWidth + "%";

            const semuaPerspektifLengkap = perspektifTerpilih.size >= 4;
            const skalaLengkap = document.querySelectorAll('.badge-belum').length === 0;

            if (total === 100 && semuaPerspektifLengkap && skalaLengkap && sasaransLengkap) {
                progressBar.style.backgroundColor = "#28a745";
                status.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check-circle"></i> Struktur Valid (100%)</span>';
                btnSimpan.classList.add('ready');
                btnSimpan.disabled = false;
            } else {
                progressBar.style.backgroundColor = (total > 100) ? "#dc3545" : "#ffc107";
                if (total > 100) {
                    status.innerHTML = '<span style="color: #dc3545;">Melebihi 100%!</span>';
                } else {
                    status.innerHTML = '<span style="color: #f4511e;"><i class="fas fa-circle-exclamation"></i> Lengkapi data untuk simpan</span>';
                }
                btnSimpan.classList.remove('ready');
                btnSimpan.disabled = true;
            }
        }

        // Pasang listeners
        document.querySelectorAll('.bobot-field').forEach(input => {
            input.addEventListener('input', hitungTotal);
            input.addEventListener('focus', function() {
                if (this.value == '0') this.value = '';
            });
            input.addEventListener('blur', function() {
                if (this.value == '') this.value = '0';
            });
        });

        // Jalankan hitung pertama kali
        hitungTotal();

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'sukses_update'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Diperbarui!',
                    text: 'Data indikator telah berhasil diperbarui.',
                    confirmButtonColor: '#0245A3'
                });
            <?php elseif ($_GET['status'] == 'sukses_hapus'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Dihapus!',
                    text: 'Indikator telah berhasil dihapus.',
                    confirmButtonColor: '#0245A3'
                });
            <?php endif; ?>
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                window.history.replaceState({
                    path: url.href
                }, '', url.href);
            }
        <?php endif; ?>
    </script>

    <script>
        // --- 8. Logic Modal Skala ---
        function tutupModalSkala() {
            document.getElementById('modalSkala').style.display = 'none';
        }

        let activeRowForSkala = null;

        function bukaModalSkala(id, btn) {
            const modal = document.getElementById('modalSkala');
            const title = document.getElementById('namaIndikatorModal');
            const idField = document.getElementById('modalIdIndikator');
            const inverseCheck = document.getElementById('modalInverse');
            const btnSimpan = document.getElementById('btnSimpanSkala');

            activeRowForSkala = btn.closest('.baris-data') || btn.closest('tr');

            // Reset inputs
            idField.value = id;
            inverseCheck.checked = false;
            for (let i = 1; i <= 5; i++) document.getElementById('t_' + i).value = '';

            modal.style.display = 'block';

            if (id == 0) {
                // Mode Lokal (Baris Baru)
                title.innerText = "Atur Skala: Baris Baru";
                const existingData = activeRowForSkala.querySelector('.skala-data-hidden').value;
                if (existingData) {
                    const d = JSON.parse(existingData);
                    inverseCheck.checked = d.terbalik == 1;
                    for (let i = 1; i <= 5; i++) {
                        if (d.nilai_minimums[i]) document.getElementById('t_' + i).value = d.nilai_minimums[i];
                    }
                }
                btnSimpan.disabled = false;
                return;
            }

            title.innerText = "Loading data...";
            btnSimpan.disabled = true;

            // Fetch data via AJAX (Existing)
            fetch(`../../fungsi/kpi_manajemen_indikator.php?aksi=skala_proses&id=${id}`)
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        title.innerText = "Skala: " + res.data.nama_indikator;
                        inverseCheck.checked = res.data.terbalik == 1;

                        // Fill nilai_minimums
                        for (let i = 1; i <= 5; i++) {
                            if (res.data.nilai_minimums[i] !== undefined) {
                                document.getElementById('t_' + i).value = res.data.nilai_minimums[i];
                            }
                        }
                        btnSimpan.disabled = false;
                    } else {
                        Swal.fire('Error', res.message, 'error');
                        tutupModalSkala();
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Gagal mengambil data skala.', 'error');
                    console.error(err);
                    tutupModalSkala();
                });
        }

        function simpanSkala() {
            const id = document.getElementById('modalIdIndikator').value;
            const isInverse = document.getElementById('modalInverse').checked ? 1 : 0;
            const btnSimpan = document.getElementById('btnSimpanSkala');

            if (id == 0) {
                // SIMPAN LOKAL (Baris Baru)
                const data = {
                    terbalik: isInverse,
                    nilai_minimums: {}
                };
                let filledCount = 0;
                for (let i = 1; i <= 5; i++) {
                    const val = document.getElementById('t_' + i).value;
                    if (val !== '') {
                        data.nilai_minimums[i] = val;
                        filledCount++;
                    }
                }
                if (filledCount === 0) {
                    Swal.fire('Peringatan', 'Harap isi minimal satu ambang batas skala!', 'warning');
                    return;
                }

                activeRowForSkala.querySelector('.skala-data-hidden').value = JSON.stringify(data);
                const badge = activeRowForSkala.querySelector('.status-skala-badge');
                if (badge) badge.innerHTML = '<span class="badge-siap"><i class="fas fa-check-circle"></i> Skala OK</span>';

                hitungTotal();
                tutupModalSkala();
                return;
            }

            // Build FormData (Existing AJAX)
            const formData = new FormData();
            formData.append('aksi', 'skala_proses');
            formData.append('id_indikator', id);
            formData.append('terbalik', isInverse);

            let filledCount = 0;
            for (let i = 1; i <= 5; i++) {
                const val = document.getElementById('t_' + i).value;
                if (val !== '') {
                    formData.append(`nilai_minimum[${i}]`, val);
                    filledCount++;
                }
            }

            if (filledCount === 0) {
                Swal.fire('Peringatan', 'Harap isi minimal satu ambang batas skala!', 'warning');
                return;
            }

            btnSimpan.disabled = true;
            btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            fetch('../../fungsi/kpi_manajemen_indikator.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Update Badge di UI secara real-time (target badge di kolom Indikator)
                        const badgeContainer = document.querySelector(`.status-skala-badge[data-id="${id}"]`);
                        if (badgeContainer) {
                            badgeContainer.innerHTML = '<span class="badge-siap"><i class="fas fa-check-circle"></i> Skala Terkonfigurasi</span>';
                        }

                        // Jalankan validasi ulang agar tombol Simpan Perubahan bisa aktif
                        hitungTotal();

                        tutupModalSkala();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Gagal menyimpan data ke server.', 'error');
                    console.error(err);
                })
                .finally(() => {
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML = '<i class="fas fa-save"></i> Simpan Skala';
                });
        }

        function setupEnterNavigation() {
            const inputs = document.querySelectorAll('#formDaftar input:not([readonly]), #formDaftar select:not([disabled])');

            inputs.forEach(input => {
                input.removeEventListener('keydown', handleEnterKey);
                input.addEventListener('keydown', handleEnterKey);
            });
        }

        function updatePerspektifRow(sel) {
            sel.closest('tr').querySelector('input[name="perspektif[]"]').value = sel.value;
            hitungTotal();
        }

        function tambahBaris() {
            const tbody = document.querySelector('.tabel-kpi tbody');
            const newRow = document.createElement('tr');
            newRow.className = 'baris-data';

            let optPerspektif = '';
            <?php foreach ($master_p as $p): ?>
                optPerspektif += '<option value="<?php echo $p['id_perspektif']; ?>"><?php echo $p['nama_perspektif']; ?></option>';
            <?php endforeach; ?>

            newRow.innerHTML = `
        <input type="hidden" name="id_indikator[]" value="0">
        <input type="hidden" name="perspektif[]" value="1"> <!-- Default value, will be updated by select -->
        <input type="hidden" name="skala_data[]" class="skala-data-hidden" value="">
        <td style="vertical-align: middle;">
            <select name="perspektif_select" class="input-kontrol-sm input-edit" onchange="updatePerspektifRow(this)" style="font-weight: bold; font-size: 0.8em; color: #666;">
                ${optPerspektif}
            </select>
        </td>
        <td>
            <input type="text" name="sasaran_strategis[]" class="input-kontrol-sm input-edit" placeholder="Input Sasaran Strategis..." oninput="hitungTotal()">
        </td>
        <td>
            <input type="text" name="subperspektif[]" class="input-kontrol-sm input-edit" placeholder="Input Sub..." oninput="hitungTotal()">
        </td>
        <td>
            <div class="indikator-wrapper">
                <input type="text" name="indikator[]" class="input-kontrol-sm input-edit" placeholder="Input Indikator..." oninput="hitungTotal()">
                <div class="status-skala-badge">
                    <span class="badge-belum"><i class="fas fa-circle-exclamation"></i> Skala?</span>
                </div>
            </div>
        </td>
        <td>
            <select name="satuan[]" class="input-kontrol-sm input-edit">
                <option value="Rupiah">Rp</option>
                <option value="Score">Score</option>
                <option value="Persen">%</option>
            </select>
        </td>
        <td>
            <div class="input-bobot-container">
                <input type="number" name="bobot[]" class="input-kontrol-sm input-edit bobot-field" step="0.01" value="0" oninput="hitungTotal()">
                <span class="persen-suffix">%</span>
            </div>
        </td>
        <td style="text-align: center;">
             <div class="btn-aksi-group" style="display: flex; gap: 8px; justify-content: center;">
                <button type="button" class="tombol bg-cyan" style="padding: 4px 8px; font-size: 0.75em;" title="Skala" onclick="bukaModalSkala(0, this)">
                    <i class="fas fa-sliders"></i>
                </button>
                <button type="button" class="tombol" style="padding: 4px; font-size: 1em; color: #ff4d4d;" title="Hapus" onclick="this.closest('tr').remove(); hitungTotal();">
                    <i class="fas fa-trash-can"></i>
                </button>
             </div>
        </td>
    `;

            tbody.appendChild(newRow);
            setupEnterNavigation();
            hitungTotal();
        }

        function handleEnterKey(e) {
            if (e.key === 'Enter') {
                const activeEl = e.target;

                // Jangan cegah Enter jika di dalam modal atau elemen lain
                if (activeEl.closest('.modal')) return;

                e.preventDefault();
                const currentRow = activeEl.closest('tr');
                if (!currentRow) return;

                // Cari semua input/select di baris saat ini untuk menentukan indeks kolom
                const inputsInRow = Array.from(currentRow.querySelectorAll('input:not([type="hidden"]), select'));
                const colIndex = inputsInRow.indexOf(activeEl);

                const nextRow = currentRow.nextElementSibling;
                if (nextRow && nextRow.classList.contains('baris-data')) {
                    const nextInputs = nextRow.querySelectorAll('input:not([type="hidden"]), select');
                    if (nextInputs[colIndex]) {
                        const target = nextInputs[colIndex];
                        target.focus();
                        if (target.tagName === 'INPUT') target.select();
                    }
                }
            }
        }
    </script>
</body>

</html>