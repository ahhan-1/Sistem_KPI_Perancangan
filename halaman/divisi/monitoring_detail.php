<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$id_user = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
$id_cabang = isset($_GET['id_cabang']) ? (int)$_GET['id_cabang'] : 0;

$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_aktif = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('n');

$nama_bulan_long = get_nama_bulan();

// --- 1. Ambil Profil Target ---
$q_cabang = "SELECT * FROM cabang WHERE id_cabang = $id_cabang";
$res_cabang = mysqli_query($koneksi, $q_cabang);
$cabang = mysqli_fetch_assoc($res_cabang);

$q_user = "SELECT * FROM users WHERE id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

$nama_cabang = $cabang['nama_cabang'] ?? '-';
$pinca_name = (!empty($u['nama'])) ? $u['nama'] : ($u['username'] ?? '-');

$title = "Detail Monitoring";
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
        .compact-menu {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .btn-sub {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-sub:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
        }

        .btn-sub.active {
            box-shadow: 0 0 0 2px #3182ce;
        }

        /* Premium Compact Menu Colors */
        .pc-kontrak {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }

        .pc-kertas {
            background: #fef9c3;
            color: #a16207;
            border-color: #fef08a;
        }

        .pc-formulir {
            background: #dcfce7;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .pc-kompetensi {
            background: #f3e8ff;
            color: #6b21a8;
            border-color: #e9d5ff;
        }

        .pc-resume {
            background: #d1fae5;
            color: #047857;
            border-color: #a7f3d0;
        }

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

        .iframe-container {
            width: 100%;
            height: 750px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            margin-top: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h1>Aksi Monitoring Cabang</h1>
                    <p>Silakan pilih dokumen untuk memonitoring cabang <strong><?php echo $nama_cabang; ?></strong>.</p>
                </div>
                <div>
                    <a href="monitoring_cabang.php" class="btn-kembali">
                        <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
                    </a>
                </div>
            </div>

            <!-- --- 2. Filter Navigasi --- -->
            <div class="kartu" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div class="filter-periode-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="month" id="periodePicker" class="input-month"
                        value="<?php echo $tahun_aktif . '-' . str_pad($bulan_aktif, 2, '0', STR_PAD_LEFT); ?>"
                        onchange="updateNav()">
                </div>
                <div style="color: #4a5568;">
                    <i class="fas fa-user-tie" style="margin-right: 5px; color: #a0aec0;"></i>
                    Pinca: <strong><?php echo $pinca_name; ?></strong>
                </div>
            </div>

            <!-- --- 3. Menu Horizontal --- -->
            <div class="kartu" style="padding: 15px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 12px; color: #1e293b; font-size: 0.95em; font-weight: 700;">
                    <i class="fas fa-bars-staggered" style="color: #0245a3; margin-right: 6px;"></i> Pilih Dokumen
                </h4>
                <div class="compact-menu">
                    <button onclick="changeDoc('../pinca/kontrak_kerja.php?id_user=<?php echo $id_user; ?>&id_cabang=<?php echo $id_cabang; ?>&filter_tahun=<?php echo $tahun_aktif; ?>&filter_bulan=<?php echo $bulan_aktif; ?>&iframe=1', this)" class="btn-sub pc-kontrak active">
                        <i class="fas fa-file-contract"></i> Kontrak
                    </button>
                    <button onclick="changeDoc('../pinca/kertas_kerja.php?id_user=<?php echo $id_user; ?>&id_cabang=<?php echo $id_cabang; ?>&filter_tahun=<?php echo $tahun_aktif; ?>&filter_bulan=<?php echo $bulan_aktif; ?>&iframe=1', this)" class="btn-sub pc-kertas">
                        <i class="fas fa-file-invoice"></i> Kertas Kerja
                    </button>
                    <button onclick="changeDoc('../pinca/formulir_penilaian.php?id_user=<?php echo $id_user; ?>&id_cabang=<?php echo $id_cabang; ?>&filter_tahun=<?php echo $tahun_aktif; ?>&filter_bulan=<?php echo $bulan_aktif; ?>&iframe=1', this)" class="btn-sub pc-formulir">
                        <i class="fas fa-file-circle-check"></i> Formulir
                    </button>
                    <button onclick="changeDoc('../pinca/penilaian_kompetensi.php?id_user=<?php echo $id_user; ?>&id_cabang=<?php echo $id_cabang; ?>&filter_tahun=<?php echo $tahun_aktif; ?>&filter_bulan=<?php echo $bulan_aktif; ?>&iframe=1', this)" class="btn-sub pc-kompetensi">
                        <i class="fas fa-brain"></i> Kompetensi
                    </button>
                    <button onclick="changeDoc('../pinca/penilaian_akhir.php?id_user=<?php echo $id_user; ?>&id_cabang=<?php echo $id_cabang; ?>&filter_tahun=<?php echo $tahun_aktif; ?>&filter_bulan=<?php echo $bulan_aktif; ?>&iframe=1', this)" class="btn-sub pc-resume">
                        <i class="fas fa-file-invoice-dollar"></i> Resume
                    </button>
                </div>
            </div>

            <!-- IFRAME SEBAGAI CONTAINER AGAR TIDAK PERLU KELUAR MASUK HALAMAN -->
            <div class="iframe-container">
                <iframe id="docFrame" src="../pinca/kontrak_kerja.php?id_user=<?php echo $id_user; ?>&id_cabang=<?php echo $id_cabang; ?>&filter_tahun=<?php echo $tahun_aktif; ?>&filter_bulan=<?php echo $bulan_aktif; ?>&iframe=1"></iframe>
            </div>
        </div>
    </div>

    <script>
        function changeDoc(url, btnElement) {
            document.getElementById('docFrame').src = url;
            const buttons = document.querySelectorAll('.btn-sub');
            buttons.forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');
        }

        function updateNav() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const parts = val.split('-');
            const thn = parts[0];
            const bln = parseInt(parts[1]);

            const urlParams = new URLSearchParams(window.location.search);
            const idUser = urlParams.get('id_user') || '';
            const idCabang = urlParams.get('id_cabang') || '';

            let url = `monitoring_detail.php?filter_tahun=${thn}&filter_bulan=${bln}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            window.location.href = url;
        }
    </script>

</body>

</html>