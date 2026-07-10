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
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_aktif = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan_long = get_nama_bulan();

// --- 1. Ambil Riwayat Sanggahan ---
$q_dispute = "SELECT * FROM laporan_sanggahan WHERE id_cabang = $id_cabang ORDER BY tgl_protes DESC";
$res_dispute = mysqli_query($koneksi, $q_dispute);

// --- 2. Ambil Profil User ---
$q_user = "SELECT u.* FROM users u WHERE u.id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

// --- 3. Cek Status Penilaian Akhir (Skor KPI & Kompetensi > 0) ---
$q_nilai = "SELECT * FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun_aktif AND bulan = $bulan_aktif";
$res_nilai = mysqli_query($koneksi, $q_nilai);
$na = mysqli_fetch_assoc($res_nilai);

$nilai_kpi = (float)($na['nilai_akhir_kpi'] ?? 0);
$nilai_kompetensi = (float)($na['nilai_kompetensi'] ?? 0);
$nilai_akhir_kinerja = (float)($na['nilai_akhir_kinerja'] ?? 0);

// Penilaian akhir siap jika KPI > 0 DAN Kompetensi > 0 DAN nilai akhir kinerja > 0
$is_nilai_ada = ($nilai_kpi > 0 && $nilai_kompetensi > 0 && $nilai_akhir_kinerja > 0);

// --- Guard: Cek Profil Lengkap ---

$title = "Laporan Ketidaksesuaian Data";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - KPI BSB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Laporan Ketidaksesuaian Data</h1>
                    <p>Sampaikan keluhan jika terdapat data Target, Realisasi, atau Nilai yang tidak sesuai dengan data Cabang.</p>
                </div>
                <div class="filter-periode-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="month" id="periodePicker" class="input-month"
                        value="<?php echo $tahun_aktif . '-' . str_pad($bulan_aktif, 2, '0', STR_PAD_LEFT); ?>"
                        onchange="updateFilter()">
                </div>
            </div>

            <div>
                <?php if (!$is_nilai_ada): ?>
                    <div class="kartu" style="padding: 40px; margin-bottom: 30px;">
                        <div style="background: #fff5f5; color: #c53030; padding: 25px; border-radius: 12px; border: 1px solid #feb2b2; display: flex; align-items: center; gap: 20px;">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                            <div>
                                <strong style="font-size: 1.1em;">Data Penilaian Akhir Belum Diterbitkan!</strong>
                                <p style="margin-top: 5px; font-size: 0.9em;">Halaman sanggah data ini hanya dapat diakses setelah <strong>Penilaian Akhir</strong> untuk periode <strong><?php echo $nama_bulan_long[$bulan_aktif] . ' ' . $tahun_aktif; ?></strong> diterbitkan oleh Pinca (KPI Seksi A dan Kompetensi Seksi D harus sudah diisi dan disimpan).</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start;">

                        <!-- --- 3. Form Sanggahan --- -->
                        <div class="kartu" style="padding: 30px;">
                            <h3 style="color: #2b5797; margin-bottom: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">
                                <i class="fas fa-edit"></i> Buat Laporan Baru
                            </h3>
                            <form action="aksi/proses_pinca.php" method="POST">
                                <input type="hidden" name="aksi" value="simpan_sanggah">
                                <input type="hidden" name="bulan" value="<?php echo $bulan_aktif; ?>">
                                <input type="hidden" name="tahun" value="<?php echo $tahun_aktif; ?>">

                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; font-weight: 700; color: #4a5568; margin-bottom: 8px; font-size: 0.9em;">PERIODE DATA</label>
                                    <div style="background: #edf2f7; padding: 12px; border-radius: 6px; font-weight: bold; color: #4a5568;">
                                        <i class="fas fa-calendar-alt" style="margin-right: 8px;"></i> <?php echo strtoupper($nama_bulan_long[$bulan_aktif]) . " " . $tahun_aktif; ?>
                                    </div>
                                </div>

                                <div style="margin-bottom: 20px;">
                                    <label style="display: block; font-weight: 700; color: #4a5568; margin-bottom: 8px; font-size: 0.9em;">KATEGORI DATA</label>
                                    <select name="kategori" class="input-kontrol" style="width: 100%;" required>
                                        <option value="target">Target KPI (Tahunan)</option>
                                        <option value="realisasi">Realisasi KPI (Bulanan)</option>
                                        <option value="nilai">Nilai / Skor Akhir</option>
                                    </select>
                                </div>

                                <div style="margin-bottom: 25px;">
                                    <label style="display: block; font-weight: 700; color: #4a5568; margin-bottom: 8px; font-size: 0.9em;">PENJELASAN KESALAHAN</label>
                                    <textarea name="pesan" class="input-kontrol" style="width: 100%; height: 120px; padding: 12px;" placeholder="Contoh: Realisasi Dana Pihak Ketiga di sistem tercatat 10M, namun data Cabang per April adalah 12M..." required></textarea>
                                </div>

                                <button type="submit" class="tombol tombol-utama" style="width: 100%; padding: 15px; font-weight: 800; border-radius: 8px;">
                                    <i class="fas fa-paper-plane"></i> KIRIM LAPORAN KE BKU
                                </button>
                            </form>
                        </div>

                        <!-- --- 4. Riwayat Sanggahan --- -->
                        <div class="kartu" style="padding: 30px;">
                            <h3 style="color: #2b5797; margin-bottom: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px;">
                                <i class="fas fa-history"></i> Riwayat Laporan Anda
                            </h3>

                            <div style="overflow-x: auto;">
                                <table class="tabel-kpi" style="width: 100%; font-size: 0.9em;">
                                    <thead>
                                        <tr style="background: #2b5797; color: white;">
                                            <th>Periode / Kategori</th>
                                            <th>Laporan & Balasan BKU</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($res_dispute) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($res_dispute)): ?>
                                                <tr style="border-bottom: 1px solid #edf2f7;">
                                                    <td style="padding: 15px; vertical-align: top; width: 180px;">
                                                        <div style="font-weight: 800; color: #2d3748;"><?php echo strtoupper($row['kategori']); ?></div>
                                                        <div style="font-size: 0.85em; color: #718096;"><?php echo $nama_bulan_long[$row['bulan']] . " " . $row['tahun']; ?></div>
                                                        <div style="font-size: 0.75em; color: #a0aec0; margin-top: 5px;"><?php echo date('d/m/Y', strtotime($row['tgl_protes'])); ?></div>
                                                    </td>
                                                    <td style="padding: 15px; vertical-align: top;">
                                                        <div style="background: #f8fafc; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #cbd5e0;">
                                                            <strong>Protes:</strong> <?php echo $row['pesan_protes']; ?>
                                                        </div>
                                                        <?php if ($row['balasan_bku']): ?>
                                                            <div style="background: #ebf8ff; padding: 10px; border-radius: 6px; border-left: 3px solid #3182ce; color: #2c5282;">
                                                                <strong>BKU:</strong> <?php echo $row['balasan_bku']; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 15px; text-align: center; vertical-align: top; width: 120px;">
                                                        <?php
                                                        $st = $row['status'];
                                                        $color = '#d69e2e';
                                                        $lbl = 'DIKIRIM';
                                                        if ($st == 'disetujui') {
                                                            $color = '#38a169';
                                                            $lbl = 'DISETUJUI';
                                                        } elseif ($st == 'ditolak') {
                                                            $color = '#e53e3e';
                                                            $lbl = 'DITOLAK';
                                                        }
                                                        ?>
                                                        <span style="font-size: 0.75em; font-weight: 800; color: <?php echo $color; ?>; border: 1px solid <?php echo $color; ?>; padding: 3px 8px; border-radius: 4px;">
                                                            <?php echo $lbl; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" style="padding: 30px; text-align: center; color: #cbd5e0;">Belum ada laporan ketidaksesuaian data.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    </div>
            </div>
        </div>

        <script>
            function updateFilter() {
                const val = document.getElementById('periodePicker').value;
                if (!val) return;
                const [thn, bln] = val.split('-');
                window.location.href = 'sanggah_data.php?filter_tahun=' + thn + '&filter_bulan=' + parseInt(bln);
            }
        </script>
</body>

</html>