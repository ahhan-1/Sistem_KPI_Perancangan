<?php
session_start();
require_once "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../index.php');
    exit();
}

$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan_filter = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$nama_bulan_long = [
    1 => "Januari",
    2 => "Februari",
    3 => "Maret",
    4 => "April",
    5 => "Mei",
    6 => "Juni",
    7 => "Juli",
    8 => "Agustus",
    9 => "September",
    10 => "Oktober",
    11 => "November",
    12 => "Desember"
];

// --- 1. Ambil Laporan Masuk ---
$q_app = "SELECT la.*, c.nama_cabang, u.nama as nama_pinca 
          FROM laporan_persetujuan la
          JOIN cabang c ON la.id_cabang = c.id_cabang
          JOIN users u ON la.id_user = u.id_user
          WHERE la.tahun = $tahun_filter AND la.bulan = $bulan_filter
          ORDER BY la.tgl_unggah DESC";
$res_app = mysqli_query($koneksi, $q_app);

$title = "Approval Laporan Cabang";
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
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 800;
        }

        .st-dikirim {
            background: #ebf8ff;
            color: #2b6cb0;
            border: 1px solid #bee3f8;
        }

        .st-disetujui {
            background: #f0fff4;
            color: #276749;
            border: 1px solid #c6f6d5;
        }

        .st-ditolak {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Approval Laporan Final</h1>
                    <p>Verifikasi laporan berstempel yang diupload oleh Pemimpin Cabang.</p>
                </div>
                <div class="filter-periode-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="month" id="periodePicker" class="input-month"
                        value="<?php echo $tahun_filter . '-' . str_pad($bulan_filter, 2, '0', STR_PAD_LEFT); ?>"
                        onchange="updateFilter()">
                </div>
            </div>

            <div class="kartu" style="padding: 0; overflow: hidden;">
                <table class="tabel-kpi">
                    <thead>
                        <tr>
                            <th>Kantor Cabang / Pinca</th>
                            <th class="text-center">Jenis</th>
                            <th class="text-center">Tgl Upload</th>
                            <th class="text-center">File PDF</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($res_app) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($res_app)): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: bold;"><?php echo $row['nama_cabang']; ?></div>
                                        <div style="font-size: 0.8em; color: #666;"><?php echo $row['nama_pinca']; ?></div>
                                        <?php if ($row['status'] == 'ditolak' && !empty($row['catatan_bku'])): ?>
                                            <div style="margin-top: 5px; font-size: 0.8em; color: #c53030; background: #fff5f5; border: 1px dashed #feb2b2; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                                <i class="fas fa-comment-dots"></i> <strong>Alasan Tolak:</strong> <?php echo htmlspecialchars($row['catatan_bku']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span style="font-size: 0.85em; font-weight: 700; color: #4a5568; text-transform: uppercase;">
                                            <?php echo ($row['jenis_laporan'] == 'kontrak') ? 'Kontrak Kerja' : 'Laporan Lengkap'; ?>
                                        </span>
                                    </td>
                                    <td class="text-center" style="font-size: 0.9em;">
                                        <?php echo date('d/m/Y H:i', strtotime($row['tgl_unggah'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="../../berkas/laporan/<?php echo rawurlencode($row['berkas_pdf']); ?>" target="_blank" class="tombol" style="font-size: 0.8em; padding: 5px 12px; background: #2b5797; color: white;">
                                            <i class="fas fa-file-pdf"></i> Lihat PDF
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge st-<?php echo $row['status']; ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] == 'dikirim'): ?>
                                            <button onclick="prosesApproval(<?php echo $row['id_persetujuan']; ?>, 'disetujui')" class="tombol" style="background: #38a169; color: white; padding: 5px 10px; font-size: 0.8em;">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                            <button onclick="prosesApproval(<?php echo $row['id_persetujuan']; ?>, 'ditolak')" class="tombol" style="background: #e53e3e; color: white; padding: 5px 10px; font-size: 0.8em;">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8em;">Sudah Diproses</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 40px; color: #999;">
                                    <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                    <p>Tidak ada laporan yang masuk untuk periode <?php echo $nama_bulan_long[$bulan_filter] . ' ' . $tahun_filter; ?>.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function updateFilter() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const [thn, bln] = val.split('-');
            window.location.href = 'persetujuan_laporan.php?tahun=' + thn + '&bulan=' + parseInt(bln);
        }

        function prosesApproval(id, status) {
            const aksi = status === 'disetujui' ? 'menyetujui' : 'menolak';
            const warna = status === 'disetujui' ? '#38a169' : '#e53e3e';

            if (status === 'ditolak') {
                Swal.fire({
                    title: 'Tolak Laporan',
                    text: 'Silakan isi keterangan atas apa yang tidak sesuai:',
                    input: 'textarea',
                    inputPlaceholder: 'Tuliskan alasan penolakan agar Pemimpin Cabang dapat merevisinya...',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: warna,
                    cancelButtonColor: '#718096',
                    confirmButtonText: 'Tolak Laporan',
                    cancelButtonText: 'Batal',
                    preConfirm: (catatan) => {
                        if (!catatan || catatan.trim() === '') {
                            Swal.showValidationMessage('Keterangan penolakan wajib diisi!');
                        }
                        return catatan;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        kirimApproval(id, status, result.value);
                    }
                });
            } else {
                Swal.fire({
                    title: 'Konfirmasi Approval',
                    text: `Yakin ingin menyetujui laporan ini?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: warna,
                    cancelButtonColor: '#718096',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        kirimApproval(id, status, '');
                    }
                });
            }
        }

        function kirimApproval(id, status, catatan) {
            const params = new URLSearchParams();
            params.append('aksi', 'persetujuan');
            params.append('id_persetujuan', id);
            params.append('status', status);
            params.append('catatan', catatan);
            params.append('tahun', '<?php echo $tahun_filter; ?>');
            params.append('bulan', '<?php echo $bulan_filter; ?>');

            fetch('aksi/proses_divisi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            }).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: `Laporan berhasil ${status}.`,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => window.location.reload());
            });
        }
    </script>
</body>

</html>