<?php
session_start();
require "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$tahun_filter = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y'));
$title = "Daftar Profil Pemimpin Cabang";



// --- 1. Ambil Semua Cabang ---
$q_cabang = "SELECT * FROM cabang ORDER BY id_cabang ASC";
$res_cabang = mysqli_query($koneksi, $q_cabang);

// --- 2. Ambil Data Pemimpin Cabang ---
$pincas = [];
$q_pinca = "SELECT id_user, id_cabang, username, nama, NIP, pangkat, no_hp FROM users WHERE jabatan = 'Pemimpin Cabang' AND tahun = $tahun_filter";
$res_pinca = mysqli_query($koneksi, $q_pinca);
while ($p = mysqli_fetch_assoc($res_pinca)) {
    // Ambil detail profile year-specific
    $pincas[$p['id_cabang']] = get_pinca_profile($koneksi, $p['id_user'], $tahun_filter);
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

        .btn-aksi {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            transition: 0.2s;
            border: 1px solid transparent;
        }

        .btn-profil {
            background: #fff7ed;
            color: #c2410c;
            border-color: #ffedd5;
        }

        .btn-aksi:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .badge-info {
            font-size: 0.75em;
            padding: 2px 8px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #64748b;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Profil Pemimpin Cabang</h1>
                    <p>Daftar dan kelola informasi profil jabatan Pemimpin Cabang di setiap kantor cabang.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-minus" style="font-size: 12px;"></i>
                    </button>
                    <div class="filter-periode-container no-chevron">
                        <i class="fas fa-calendar-check"></i>
                        <select id="tahunSelect" class="input-year-select" onchange="window.location.href='daftar_pinca.php?filter_tahun='+this.value">
                            <!-- Akan diisi oleh JS -->
                        </select>
                    </div>
                    <button type="button" onclick="tambahTahun()" title="Tambah Tahun" style="background: var(--biru-utama); color: white; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i class="fas fa-plus" style="font-size: 12px; color: white;"></i>
                    </button>
                </div>
            </div>

            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'sukses_import'): ?>
                    <script>
                        Swal.fire('Berhasil!', '<?php echo isset($_GET['count']) ? $_GET['count'] : 0; ?> Profil Pimpinan Cabang berhasil diperbarui/ditambahkan.', 'success');
                    </script>
                <?php elseif ($_GET['status'] == 'gagal_upload'): ?>
                    <script>
                        Swal.fire('Gagal!', 'File Excel gagal diunggah.', 'error');
                    </script>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Import Template Excel Profil Pinca -->
            <div class="kartu" style="margin-bottom: 25px; padding: 20px; background: #fafcff; border-left: 5px solid #0056b3; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 280px; max-width: 600px;">
                    <h3 style="margin:0 0 5px; color:#1e293b; font-size:1.1em; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-file-excel" style="color:#28a745;"></i> Import Profil Pimpinan Cabang
                    </h3>
                    <p style="margin:0; color:#64748b; font-size:0.85em; line-height: 1.4;">Unduh template di samping (format .xlsx), lengkapi data profil, lalu unggah kembali.</p>
                </div>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content: flex-end;">
                    <a href="../../fungsi/kpi_manajemen_excel.php?aksi_utama=template_profil&tahun=<?php echo $tahun_filter; ?>" class="btn-kelola" style="background:#28a745; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:bold; font-size:0.85em;">
                        <i class="fas fa-download"></i> Unduh Template (.xlsx)
                    </a>
                    <form action="../../fungsi/kpi_manajemen_excel.php" method="POST" enctype="multipart/form-data" style="display:flex; align-items:center; gap:8px;" id="formImportProfil">
                        <input type="hidden" name="aksi_utama" value="import_profil">
                        <input type="hidden" name="tahun" value="<?php echo $tahun_filter; ?>">
                        <input type="file" name="file" accept=".xlsx" required style="font-size:0.85em; max-width:200px;">
                        <button type="submit" class="btn-kelola" style="background:#0245a3; color:white; border:none; cursor:pointer; padding:8px 16px; border-radius:8px; font-weight:bold; font-size:0.85em;">
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
                            <th>Pemimpin Cabang</th>
                            <th>Nomor HP</th>
                            <th style="text-align: center;">Aksi</th>
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
                                        <span style="color: #4a5568; font-weight: 600;">
                                            <?php
                                            $display_name = (!empty($pinca['nama'])) ? $pinca['nama'] : "Pemimpin Cabang " . $cab['nama_cabang'];
                                            echo $display_name;
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <small style="color: #fc8181; font-style: italic;">Belum Terdaftar</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($pinca && !empty($pinca['no_hp'])): ?>
                                        <span style="color: #4a5568; font-weight: 600;">
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
                                        <a href="profil_pinca.php?id_user=<?php echo $pinca['id_user']; ?>&filter_tahun=<?php echo $tahun_filter; ?>&tahun=<?php echo $tahun_filter; ?>" class="btn-aksi btn-profil">
                                            <i class="fas fa-user-gear"></i> Kelola Profil
                                        </a>
                                    <?php else: ?>
                                        <a href="profil_pinca.php?id_cabang=<?php echo $cab['id_cabang']; ?>&filter_tahun=<?php echo $tahun_filter; ?>&tahun=<?php echo $tahun_filter; ?>" class="btn-aksi btn-profil" style="background: #38a169; color: white;">
                                            <i class="fas fa-user-plus"></i> Tambah Profil
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const tahunAktif = <?php echo $tahun_filter; ?>;
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

        // Inisialisasi
        renderTahun();
    </script>
</body>

</html>