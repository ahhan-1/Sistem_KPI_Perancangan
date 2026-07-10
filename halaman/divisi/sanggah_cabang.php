<?php
session_start();
require_once "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../index.php');
    exit();
}

// --- 1. Ambil Sanggahan Masuk ---
$sql = "SELECT ls.*, c.nama_cabang, u.nama as nama_pinca 
        FROM laporan_sanggahan ls 
        JOIN cabang c ON ls.id_cabang = c.id_cabang 
        JOIN users u ON ls.id_user = u.id_user 
        ORDER BY ls.status = 'dikirim' DESC, ls.tgl_protes DESC";
$res_dispute = mysqli_query($koneksi, $sql);

$title = "Manajemen Sanggahan Data";
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
        <div class="halaman-header">
            <h1>Manajemen Ketidaksesuaian Data</h1>
            <p>Review dan tindak lanjuti laporan ketidaksesuaian data yang diajukan oleh Kantor Cabang.</p>
        </div>

        <div class="kartu" style="padding: 30px;">
            <table class="tabel-kpi" style="width: 100%;">
                <thead>
                    <tr style="background: #2b5797; color: white;">
                        <th>Cabang / Pinca</th>
                        <th>Periode / Kategori</th>
                        <th>Pesan & Respons</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($res_dispute) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($res_dispute)): ?>
                            <tr style="border-bottom: 1px solid #edf2f7;">
                                <td style="padding: 15px; vertical-align: top; width: 220px;">
                                    <div style="font-weight: 800; color: #2d3748;"><?php echo $row['nama_cabang']; ?></div>
                                    <div style="font-size: 0.85em; color: #718096;"><?php echo $row['nama_pinca']; ?></div>
                                </td>
                                <td style="padding: 15px; vertical-align: top; width: 180px;">
                                    <div style="font-weight: 700; color: #2b5797; font-size: 0.9em;"><?php echo strtoupper($row['kategori']); ?></div>
                                    <div style="font-size: 0.85em; color: #4a5568;"><?php echo date('M Y', mktime(0,0,0,$row['bulan'],1,$row['tahun'])); ?></div>
                                    <div style="font-size: 0.75em; color: #a0aec0; margin-top: 5px;">Tgl: <?php echo date('d/m/Y', strtotime($row['tgl_protes'])); ?></div>
                                </td>
                                <td style="padding: 15px; vertical-align: top;">
                                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #cbd5e0; font-size: 0.95em;">
                                        <strong>Laporan Cabang:</strong><br>
                                        <?php echo $row['pesan_protes']; ?>
                                    </div>
                                    <?php if($row['balasan_bku']): ?>
                                        <div style="background: #ebf8ff; padding: 12px; border-radius: 8px; border-left: 4px solid #3182ce; color: #2c5282; font-size: 0.95em;">
                                            <strong>Respon Anda:</strong><br>
                                            <?php echo $row['balasan_bku']; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: center; vertical-align: top; width: 150px;">
                                    <?php 
                                    $st = $row['status'];
                                    $color = '#d69e2e'; $lbl = 'BARU';
                                    if($st == 'disetujui') { $color = '#38a169'; $lbl = 'DISETUJUI'; }
                                    elseif($st == 'ditolak') { $color = '#e53e3e'; $lbl = 'DITOLAK'; }
                                    ?>
                                    <div style="margin-bottom: 10px;">
                                        <span style="font-size: 0.7em; font-weight: 900; color: white; background: <?php echo $color; ?>; padding: 3px 10px; border-radius: 20px;">
                                            <?php echo $lbl; ?>
                                        </span>
                                    </div>
                                    <button onclick='bukaModal(<?php echo json_encode($row); ?>)' class="tombol" style="background: #edf2f7; color: #2b5797; font-size: 0.8em; padding: 5px 12px;">
                                        <i class="fas fa-reply"></i> Tindak Lanjut
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #cbd5e0;">Belum ada laporan ketidaksesuaian data masuk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- --- 2. Modal Tindak Lanjut --- -->
<div id="modalDispute" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="kartu" style="width: 500px; padding: 30px; position:relative;">
        <h3 style="margin-bottom: 20px; color: #2b5797;"><i class="fas fa-clipboard-check"></i> Proses Laporan</h3>
        <form action="aksi/proses_divisi.php" method="POST">
            <input type="hidden" name="aksi" value="sanggah">
            <input type="hidden" name="id_sanggahan" id="mdl_id">
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; font-size: 0.85em; font-weight: 700; color: #4a5568; margin-bottom: 5px;">Update Status:</label>
                <select name="status" id="mdl_status" class="input-kontrol" style="width:100%;" required>
                    <option value="disetujui">Selesai (Data Sudah Diperbaiki)</option>
                    <option value="ditolak">Ditolak (Data Sudah Sesuai)</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; font-size: 0.85em; font-weight: 700; color: #4a5568; margin-bottom: 5px;">Tanggapan / Catatan BKU:</label>
                <textarea name="balasan" id="mdl_balasan" class="input-kontrol" style="width:100%; height:100px; padding: 10px;" placeholder="Tuliskan alasan penolakan atau konfirmasi perbaikan data..." required></textarea>
            </div>

            <div style="display:flex; gap:10px; justify-content: flex-end;">
                <button type="button" onclick="tutupModal()" class="tombol" style="background:#edf2f7; color:#4a5568;">Batal</button>
                <button type="submit" class="tombol tombol-utama">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(data) {
    document.getElementById('mdl_id').value = data.id_sanggahan;
    document.getElementById('mdl_status').value = data.status;
    document.getElementById('mdl_balasan').value = data.balasan_bku || '';
    document.getElementById('modalDispute').style.display = 'flex';
}
function tutupModal() {
    document.getElementById('modalDispute').style.display = 'none';
}
</script>

</body>
</html>
