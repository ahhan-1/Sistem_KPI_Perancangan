<?php
session_start();
require "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../index.php');
    exit();
}

$id_cabang = isset($_GET['id_cabang']) ? (int)$_GET['id_cabang'] : 0;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

if ($id_cabang == 0) {
    header('Location: target_cabang.php');
    exit();
}

// --- 1. Ambil Detail Cabang ---
$q_cab = "SELECT * FROM cabang WHERE id_cabang = $id_cabang";
$res_cab = mysqli_query($koneksi, $q_cab);
$cab = mysqli_fetch_assoc($res_cab);

// --- 2. Ambil Daftar Indikator ---
$q_ind = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif 
          FROM indikator i
          JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
          JOIN perspektif p ON s.id_perspektif = p.id_perspektif
          WHERE i.tahun = $tahun AND i.id_cabang = $id_cabang
          ORDER BY p.id_perspektif ASC, i.id_indikator ASC";
$res_ind = mysqli_query($koneksi, $q_ind);
$indikator_list = [];
while($row = mysqli_fetch_assoc($res_ind)) {
    $indikator_list[$row['nama_subperspektif']][] = $row;
}

// --- 3. Ambil Target Tersimpan dari Bulan Desember (Bulan 12) ---
$targets_exist = [];
$q_target = "SELECT * FROM target WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = 12";
$res_target = mysqli_query($koneksi, $q_target);
while($t = mysqli_fetch_assoc($res_target)) {
    $targets_exist[$t['id_indikator']] = $t['nilai_target'];
}

$title = "Target Tahunan - " . $cab['nama_cabang'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <style>
        .grup-perspektif {
            margin-bottom: 30px;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .header-perspektif {
            background-color: #f8f9fa;
            padding: 15px 20px;
            font-weight: bold;
            color: var(--biru-utama);
            border-bottom: 2px solid var(--biru-muda);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .item-indikator {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: grid;
            grid-template-columns: 2fr 1fr 1.2fr;
            align-items: center;
            gap: 20px;
            transition: 0.2s;
        }
        .item-indikator:last-child { border-bottom: none; }
        .item-indikator:hover { background-color: #fafafa; }
        
        .label-indikator {
            font-weight: 500;
        }
        .label-indikator small {
            display: block;
            color: #888;
            font-weight: normal;
        }
        .input-box {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0 12px;
            transition: 0.2s;
        }
        .input-box input {
            flex: 1;
            border: none;
            outline: none;
            padding: 10px 0;
            font-weight: 600;
            text-align: right;
            width: 100%;
            background: transparent;
            color: #334155;
        }
        .input-box .satuan-addon {
            color: #64748b;
            font-size: 0.85em;
            margin-left: 8px;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include "../komponen/sidebar.php"; ?>

    <div class="main-content">
        <div class="halaman-header">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <a href="target_cabang.php?filter_tahun=<?php echo $tahun; ?>" class="tombol" style="background:#eee; color:#333; padding: 5px 10px; border-radius: 5px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <span style="color: #999;">/ Manajemen Target / <?php echo $tahun; ?></span>
            </div>
            <h1>Target Tahunan (Bulan Desember): <span class="teks-utama"><?php echo $cab['nama_cabang']; ?></span></h1>
            <p>Berikut adalah nilai target tahunan yang diambil secara otomatis dari data target bulan Desember.</p>
        </div>

        <div>
            <?php foreach($indikator_list as $subperspektif => $indikators): ?>
                <div class="grup-perspektif">
                    <div class="header-perspektif">
                        <i class="fas fa-layer-group"></i> <?php echo $subperspektif; ?>
                        <span style="margin-left: auto; font-size: 0.8em; font-weight: normal; background: #eef; padding: 2px 10px; border-radius: 10px;">
                            <?php echo count($indikators); ?> Indikator
                        </span>
                    </div>
                    <?php foreach($indikators as $ind): ?>
                        <div class="item-indikator">
                            <div class="label-indikator">
                                <?php echo $ind['nama_indikator']; ?>
                                <small>Perspektif: <?php echo $ind['nama_perspektif']; ?></small>
                            </div>
                            <div style="text-align: center; color: #666; font-size: 0.9em;">
                                Bobot: <strong><?php echo $ind['bobot']; ?>%</strong>
                            </div>
                            <div class="input-box">
                                <input type="text" 
                                       class="target-input number-format"
                                       value="<?php echo isset($targets_exist[$ind['id_indikator']]) ? number_format($targets_exist[$ind['id_indikator']], 2, ',', '.') : '0,00'; ?>"
                                       readonly>
                                <span class="satuan-addon" style="<?php echo $ind['satuan'] == 'Persen' ? 'font-weight: bold; color: #333; font-size: 1.1em;' : ''; ?>"><?php echo $ind['satuan'] == 'Persen' ? '%' : $ind['satuan']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>
