<?php
session_start();
require "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../index.php');
    exit();
}

$id_cabang = isset($_GET['id_cabang']) ? (int) $_GET['id_cabang'] : 0;
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : 1;

if ($id_cabang == 0) {
    header('Location: target_cabang.php');
    exit();
}

// --- 1. Ambil Detail Cabang ---
$q_cab = "SELECT * FROM cabang WHERE id_cabang = $id_cabang";
$res_cab = mysqli_query($koneksi, $q_cab);
$cab = mysqli_fetch_assoc($res_cab);

// --- 2. Ambil Daftar Indikator ---
$q_ind = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, t.nilai_target as target_tahunan 
          FROM indikator i
          JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
          JOIN perspektif p ON s.id_perspektif = p.id_perspektif
          LEFT JOIN target t ON i.id_indikator = t.id_indikator AND t.id_cabang = $id_cabang AND t.tahun = $tahun AND t.bulan = 12
          WHERE i.tahun = $tahun AND i.id_cabang = $id_cabang
          ORDER BY p.id_perspektif ASC, i.id_indikator ASC";
$res_ind = mysqli_query($koneksi, $q_ind);
$indikator_list = [];
while ($row = mysqli_fetch_assoc($res_ind)) {
    $indikator_list[$row['nama_subperspektif']][] = $row;
}

// --- 3. Ambil Target Koreksi (Bulan Ini) ---
$targets_exist = [];
$q_target_kor = "SELECT * FROM target WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan";
$res_target_kor = mysqli_query($koneksi, $q_target_kor);
while ($t = mysqli_fetch_assoc($res_target_kor)) {
    $targets_exist[$t['id_indikator']] = $t['nilai_target'];
}

// --- 4. Cek Status Approval ---
$is_locked = false;
$q_status = "SELECT id_persetujuan FROM laporan_persetujuan WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan AND status = 'disetujui' LIMIT 1";
$res_status = mysqli_query($koneksi, $q_status);
if (mysqli_num_rows($res_status) > 0) {
    $is_locked = true;
}

$title = "Input Target Bulanan - " . $cab['nama_cabang'];
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
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
            gap: 20px;
            transition: 0.2s;
        }

        .item-indikator:last-child {
            border-bottom: none;
        }

        .item-indikator:hover {
            background-color: #fafafa;
        }

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
            background: white;
            border: 1px solid #ddd;
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

        .input-disabled {
            background-color: #f5f5f5;
            color: #777;
            border-color: #eee;
        }
    </style>
</head>

<body class="bg-halaman">
    <div class="dashboard-container">
        <?php include '../komponen/sidebar.php'; ?>

        <div class="main-content">
            <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                <a href="target_cabang.php?filter_tahun=<?php echo $tahun; ?>" class="tombol"
                    style="background:#eee; color:#333; padding: 5px 10px; border-radius: 5px;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Cabang
                </a>
            </div>

            <div class="kartu-header" style="margin-bottom:20px;">
                <h1>Input Target Bulanan: <span class="teks-utama"><?php echo $cab['nama_cabang']; ?></span></h1>
                <p>Masukkan angka target bulanan secara mandiri.</p>
            </div>

            <?php if ($is_locked): ?>
                <div class="kartu"
                    style="background: #fff4f4; border-left: 5px solid #d32f2f; margin-bottom: 25px; padding: 15px 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-lock" style="font-size: 1.2em; color: #d32f2f;"></i>
                        <span style="font-weight: bold; color: #d32f2f;">Bulan ini sudah Disetujui. Target tidak dapat
                            diubah lagi</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- --- 5. Filter Periode --- -->
            <div class="filter-periode-container">
                <i class="fas fa-calendar-check"></i>
                <input type="month" id="periodePicker" class="input-month"
                    value="<?php echo $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT); ?>"
                    min="<?php echo $tahun; ?>-01" max="<?php echo $tahun; ?>-12" onchange="updatePeriode()">
            </div>

            <form action="../../fungsi/kpi_manajemen_data.php" method="POST">
                <input type="hidden" name="aksi_utama" value="simpan_koreksi">
                <input type="hidden" name="id_cabang" value="<?php echo $id_cabang; ?>">
                <input type="hidden" name="filter_tahun" value="<?php echo $tahun; ?>">
                <input type="hidden" name="filter_bulan" value="<?php echo $bulan; ?>">

                <?php foreach ($indikator_list as $subp => $inds): ?>
                    <div class="grup-perspektif">
                        <div class="header-perspektif">
                            <i class="fas fa-list-check"></i> <?php echo $subp; ?>
                        </div>
                        <?php foreach ($inds as $ind): ?>
                            <div class="item-indikator">
                                <div class="label-indikator">
                                    <?php echo $ind['nama_indikator']; ?>
                                    <small>Bobot: <?php echo number_format($ind['bobot'], 2, ',', '.'); ?>% (Target Tahunan Ref:
                                        <?php echo number_format($ind['target_tahunan'] ?: 0, 2, ',', '.'); ?>)</small>
                                </div>
                                <div>
                                    <label style="font-size:0.75em; color:#777; display:block; margin-bottom:4px;">Input Target
                                        Bulanan:</label>
                                    <div class="input-box">
                                        <input type="text" class="target-input number-format"
                                            placeholder="Masukkan angka target..."
                                            data-target-id="<?php echo $ind['id_indikator']; ?>"
                                            value="<?php echo isset($targets_exist[$ind['id_indikator']]) ? number_format($targets_exist[$ind['id_indikator']], 2, ',', '.') : ''; ?>"
                                            <?php echo $is_locked ? 'disabled' : ''; ?>>
                                        <input type="hidden" name="target_koreksi[<?php echo $ind['id_indikator']; ?>]"
                                            id="raw_target_<?php echo $ind['id_indikator']; ?>"
                                            value="<?php echo isset($targets_exist[$ind['id_indikator']]) ? $targets_exist[$ind['id_indikator']] : ''; ?>">
                                        <span
                                            style="color: #999; font-size: 0.85em; margin-left: 8px; white-space: nowrap;"><?php echo $ind['satuan'] == 'Persen' ? '%' : $ind['satuan']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!$is_locked): ?>
                    <div class="kartu" style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="submit" name="aksi" value="simpan" class="tombol tombol-utama">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <?php if ($bulan < 12): ?>
                            <button type="submit" name="aksi" value="simpan_lanjut" class="tombol"
                                style="background-color: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-forward"></i> Simpan & Lanjut</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
        function updatePeriode() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const parts = val.split('-');
            const thn = parts[0];
            const bln = parseInt(parts[1]);
            window.location.href = `target_koreksi.php?id_cabang=<?php echo $id_cabang; ?>&tahun=${thn}&bulan=${bln}`;
        }

        // --- 6. Navigasi Enter ---
        document.querySelector('form').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('target-input')) {
                e.preventDefault();
                const inputs = Array.from(document.querySelectorAll('.target-input'));
                const currentIndex = inputs.indexOf(e.target);
                if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                    inputs[currentIndex + 1].focus();
                    inputs[currentIndex + 1].select();
                }
            }
        });

        // Inisialisasi & Listeners untuk Format Ribuan
        document.querySelectorAll('.target-input').forEach(input => {
            input.addEventListener('input', function(e) {
                FormatAngkaInput(this);
            });
        });

        // --- 7. Format Angka Ribuan ---
        function FormatAngkaInput(input) {
            let value = input.value.replace(/[^0-9,]/g, ""); // Hanya angka dan koma
            let parts = value.split(",");

            // Format bagian ribuan
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");

            // Gabungkan kembali
            input.value = parts.join(",");

            // Simpan nilai asli ke hidden input (ubah koma ke titik untuk DB)
            const rawId = "raw_target_" + input.getAttribute('data-target-id');
            const rawVal = value.replace(/\./g, "").replace(",", ".");
            document.getElementById(rawId).value = rawVal;
        }

        // Target bulanan kini bersifat independen tanpa validasi sisa plafon.

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Target bulanan telah berhasil disimpan.',
                timer: 2000,
                showConfirmButton: false
            });
        <?php endif; ?>
    </script>
</body>

</html>