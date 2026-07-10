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
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');

if ($id_cabang == 0) {
    header('Location: realisasi_cabang.php');
    exit();
}

$nama_bulan = [
    1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 
    5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus", 
    9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"
];

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

$multiplier = 1 / 12; // Tampilan target bulanan flat (sesuai request: jangan kumulatif)

// --- 3. Ambil Target Cabang ---
$targets_ref = [];
$q_target = "SELECT id_indikator, nilai_target FROM `target` 
             WHERE id_cabang = $id_cabang AND tahun = $tahun 
             AND bulan = 12";
$res_target = mysqli_query($koneksi, $q_target);
while($t = mysqli_fetch_assoc($res_target)) {
    $targets_ref[$t['id_indikator']] = $t['nilai_target'];
}

// --- 4. Proteksi Kelengkapan Target ---
$q_total_ind = "SELECT COUNT(*) as total FROM indikator WHERE tahun = $tahun AND id_cabang = $id_cabang";
$res_total_ind = mysqli_query($koneksi, $q_total_ind);
$total_ind_tahunan = (int)mysqli_fetch_assoc($res_total_ind)['total'];

if (count($targets_ref) < $total_ind_tahunan) {
    header("Location: realisasi_cabang.php?filter_tahun=$tahun&filter_bulan=$bulan&status=error_target_tidak_lengkap");
    exit();
}

// --- 5. Ambil Realisasi Tersimpan ---
$realisasi_exist = [];
$q_real = "SELECT * FROM realisasi WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan";
$res_real = mysqli_query($koneksi, $q_real);
while($r = mysqli_fetch_assoc($res_real)) {
    $realisasi_exist[$r['id_indikator']] = $r['realisasi'];
}

// --- 6. Ambil Referensi Bulan Lalu ---
$realisasi_prev = [];
$bulan_lalu = $bulan - 1;
if ($bulan_lalu > 0) {
    $q_prev = "SELECT id_indikator, realisasi FROM realisasi WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan_lalu";
    $res_prev = mysqli_query($koneksi, $q_prev);
    while($rp = mysqli_fetch_assoc($res_prev)) {
        $realisasi_prev[$rp['id_indikator']] = $rp['realisasi'];
    }
}



$title = "Input Realisasi - " . $cab['nama_cabang'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            grid-template-columns: 2fr 1.2fr;
            align-items: center;
            gap: 15px;
        }
        .item-indikator:last-child { border-bottom: none; }
        
        .label-indikator { font-weight: 500; }
        .label-indikator small { display: block; color: #888; font-weight: normal; }

        .target-box {
            text-align: center;
            background: #f9f9f9;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #eee;
        }
        .target-box small { color: #999; font-size: 0.75em; display: block; margin-bottom: 2px; }
        .target-box strong { color: #333; font-size: 0.95em; }

        .input-box {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #ddd;
            border-left: 4px solid var(--biru-utama);
            border-radius: 6px;
            padding: 0 12px;
            transition: 0.2s;
        }
        .input-box:focus-within {
            border-color: var(--biru-utama);
            box-shadow: 0 0 0 3px rgba(2, 69, 163, 0.1);
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
        }
        .input-box .satuan-addon {
            color: #999;
            font-size: 0.85em;
            margin-left: 8px;
            white-space: nowrap;
        }

        /* Progress Styling */
        .progress-kabur { color: #999; font-size: 0.85em; }
        .progress-siap { color: var(--biru-utama); font-weight: bold; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include "../komponen/sidebar.php"; ?>

    <div class="main-content">
        <div class="halaman-header">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <a href="realisasi_cabang.php?filter_tahun=<?php echo $tahun; ?>&filter_bulan=<?php echo $bulan; ?>" class="tombol" style="background:#eee; color:#333; padding: 5px 10px; border-radius: 5px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <span style="color: #999;">/ Realisasi / <?php echo $nama_bulan[$bulan] . " " . $tahun; ?></span>
            </div>
            <h1>Input Realisasi: <span class="teks-utama"><?php echo $cab['nama_cabang']; ?></span></h1>
            <p>Masukkan angka capaian riil (realisasi) sesuai periode yang dipilih.</p>
        </div>

        <form action="../../fungsi/kpi_manajemen_data.php" method="POST" id="formRealisasi">
            <input type="hidden" name="aksi_utama" value="simpan_realisasi">
            <input type="hidden" name="id_cabang" value="<?php echo $id_cabang; ?>">
            <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
            <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">

            <?php foreach($indikator_list as $subperspektif => $indikators): ?>
                <div class="grup-perspektif">
                    <div class="header-perspektif">
                        <i class="fas fa-layer-group"></i> <?php echo $subperspektif; ?>
                        <span style="margin-left: auto; font-size: 0.7em; font-weight: normal; background: #eef; padding: 2px 10px; border-radius: 10px;">
                            <?php echo count($indikators); ?> Indikator
                        </span>
                    </div>
                    <?php foreach($indikators as $ind): ?>
                        <div class="item-indikator">
                            <div class="label-indikator">
                                <?php echo $ind['nama_indikator']; ?>
                                <small>Perspektif: <?php echo $ind['nama_perspektif']; ?></small>
                            </div>
                            


                            <div class="input-box-wrapper">
                                <div class="input-box">
                                    <input type="text" 
                                           class="realisasi-input number-format"
                                           data-target-id="<?php echo $ind['id_indikator']; ?>"
                                           value="<?php echo isset($realisasi_exist[$ind['id_indikator']]) ? number_format($realisasi_exist[$ind['id_indikator']], 2, ',', '.') : ''; ?>"
                                           required>
                                    <input type="hidden" 
                                           name="realisasi[<?php echo $ind['id_indikator']; ?>]" 
                                           id="raw_real_<?php echo $ind['id_indikator']; ?>"
                                           value="<?php echo isset($realisasi_exist[$ind['id_indikator']]) ? $realisasi_exist[$ind['id_indikator']] : ''; ?>">
                                    <span class="satuan-addon" style="<?php echo $ind['satuan'] == 'Persen' ? 'font-weight: bold; color: #333; font-size: 1.1em;' : ''; ?>"><?php echo $ind['satuan'] == 'Persen' ? '%' : $ind['satuan']; ?></span>
                                </div>
                                <?php if($bulan > 1): ?>
                                    <div style="font-size: 0.75em; color: #718096; margin-top: 4px; display: flex; justify-content: space-between;">
                                        <span>Bulan Lalu: <b><?php echo number_format($realisasi_prev[$ind['id_indikator']] ?? 0, 2, ',', '.'); ?></b></span>
                                        <span id="diff_<?php echo $ind['id_indikator']; ?>" style="font-weight: bold;"></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="sticky-bobot" style="position: sticky; bottom: 20px; border-radius: 15px; margin-top: 30px; border-left: 10px solid var(--biru-utama);">
                <div class="bobot-info">
                    <strong>Progres Input Realisasi</strong>
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 5px;">
                        <div style="flex: 1; height: 8px; background: #eee; border-radius: 4px; overflow: hidden; max-width: 200px;">
                            <div id="realProgressBar" style="width: 0%; height: 100%; background: var(--biru-utama); transition: 0.3s;"></div>
                        </div>
                        <span id="realStatusText" class="progress-kabur">0 / 0 Terisi</span>
                    </div>
                </div>
                <div class="bobot-aksi" style="gap: 15px;">
                    <button type="submit" name="aksi" value="simpan_kembali" class="tombol tombol-utama" style="padding: 12px 30px;">
                        <i class="fas fa-save" style="margin-right: 8px;"></i> Simpan & Selesai
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// --- 7. Navigasi Enter ---
document.getElementById('formRealisasi').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.classList.contains('realisasi-input')) {
        e.preventDefault();
        const inputs = Array.from(document.querySelectorAll('.realisasi-input'));
        const currentIndex = inputs.indexOf(e.target);
        if (currentIndex > -1 && currentIndex < inputs.length - 1) {
            inputs[currentIndex + 1].focus();
            inputs[currentIndex + 1].select();
        }
    }
});

// --- 8. Update Progres UI ---
function updateRealProgress() {
    const inputs = document.querySelectorAll('.realisasi-input');
    const total = inputs.length;
    let filled = 0;

    inputs.forEach(input => {
        if (input.value !== '') {
            filled++;
        }
    });

    const percent = total > 0 ? (filled / total) * 100 : 0;
    const progressBar = document.getElementById('realProgressBar');
    const statusText = document.getElementById('realStatusText');

    progressBar.style.width = percent + '%';
    statusText.innerText = `${filled} / ${total} Terisi`;

    if (filled === total) {
        statusText.classList.add('progress-siap');
        statusText.classList.remove('progress-kabur');
        progressBar.style.backgroundColor = '#28a745';
    } else {
        statusText.classList.add('progress-kabur');
        statusText.classList.remove('progress-siap');
        progressBar.style.backgroundColor = 'var(--biru-utama)';
    }

    return filled === total;
}

// --- 9. Format Ribuan ---
function FormatAngkaInput(input) {
    let value = input.value.replace(/[^0-9,]/g, ""); 
    let parts = value.split(",");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    input.value = parts.join(",");

    const rawId = "raw_real_" + input.getAttribute('data-target-id');
    const rawVal = value.replace(/\./g, "").replace(",", ".");
    document.getElementById(rawId).value = rawVal;
}

document.querySelectorAll('.realisasi-input').forEach(input => {
    input.addEventListener('input', function() {
        FormatAngkaInput(this);
        updateRealProgress();
    });
});

// --- 10. Validasi Form ---
document.getElementById('formRealisasi').addEventListener('submit', function(e) {
    const isComplete = updateRealProgress();
    if (!isComplete) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Belum Lengkap!',
            text: 'Lengkapi data untuk simpan',
            confirmButtonColor: '#0245A3'
        });
    }
});

// Jalankan pertama kali
updateRealProgress();
</script>

</body>
</html>
