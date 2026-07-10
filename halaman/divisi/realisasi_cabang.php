<?php
session_start();
require_once "../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_aktif = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan = [
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

$id_cabang = isset($_SESSION['id_cabang']) ? $_SESSION['id_cabang'] : 0;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Realisasi Cabang - KPI BSB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <style>
        .btn-input {
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-input:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }

        .btn-input {
            transition: 0.2s;
        }

        .btn-input:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 69, 163, 0.2);
            filter: brightness(1.1);
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header no-print">
                <h1>Input Realisasi Cabang</h1>
                <p>Kelola data realisasi bulanan seluruh jaringan kantor.</p>
            </div>

            <!-- --- 1. Filter Navigasi --- -->
            <div class="no-print" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                <div class="filter-periode-container">
                    <i class="fas fa-calendar-check"></i>
                    <input type="month" id="periodePicker" class="input-month"
                        value="<?php echo $tahun_aktif . '-' . str_pad($bulan_aktif, 2, '0', STR_PAD_LEFT); ?>"
                        onchange="updateFilter()">
                </div>
            </div>

            <div class="kartu" style="margin-bottom: 25px; padding: 20px; background: #fafcff; border-left: 5px solid #0056b3; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 280px; max-width: 600px;">
                    <h3 style="margin:0 0 5px; color:#1e293b; font-size:1.1em; display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-file-excel" style="color:#28a745;"></i> Import Realisasi (Periode <?php echo $nama_bulan[$bulan_aktif] . ' ' . $tahun_aktif; ?>)
                    </h3>
                    <p style="margin:0; color:#64748b; font-size:0.85em; line-height: 1.4;">Unduh template di samping (format .xlsx), lengkapi data realisasi untuk bulan terpilih, lalu unggah kembali.</p>
                </div>
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content: flex-end;">
                    <a href="../../fungsi/kpi_manajemen_excel.php?aksi=template_realisasi&tahun=<?php echo $tahun_aktif; ?>&bulan=<?php echo $bulan_aktif; ?>" class="btn-input" style="background:#28a745; color:white;">
                        <i class="fas fa-download"></i> Unduh Template (.xlsx)
                    </a>
                    <form action="../../fungsi/kpi_manajemen_excel.php" method="POST" enctype="multipart/form-data" style="display:flex; align-items:center; gap:8px;" id="formImportRealisasi">
                        <input type="hidden" name="aksi_utama" value="import_realisasi">
                        <input type="hidden" name="tahun" value="<?php echo $tahun_aktif; ?>">
                        <input type="hidden" name="bulan" value="<?php echo $bulan_aktif; ?>">
                        <input type="file" name="file" accept=".xlsx, .xls" required style="font-size:0.85em; max-width:200px;">
                        <button type="submit" class="btn-input" style="background:#0056b3; color:white; border:none; cursor:pointer;">
                            <i class="fas fa-upload"></i> Import
                        </button>
                    </form>
                </div>
            </div>

            <!-- --- 2. Tabel Cabang --- -->
            <div class="kartu">
                <table class="tabel-kpi">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Cabang</th>
                            <th style="text-align: center;">Status Pengisian</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q_cabang = "SELECT * FROM cabang ORDER BY kode_cabang ASC";
                        $res_cabang = mysqli_query($koneksi, $q_cabang);
                        $no = 1;
                        while ($c = mysqli_fetch_assoc($res_cabang)):
                            $idc = $c['id_cabang'];
                            // --- 3. Hitung Indikator ---
                            $q_count_ind = "SELECT COUNT(*) as total FROM indikator WHERE tahun = $tahun_aktif AND id_cabang = $idc";
                            $res_count_ind = mysqli_query($koneksi, $q_count_ind);
                            $total_ind = mysqli_fetch_assoc($res_count_ind)['total'];

                            // --- 4. Hitung Realisasi Masuk ---
                            $q_count_real = "SELECT COUNT(*) as total FROM realisasi WHERE id_cabang = $idc AND tahun = $tahun_aktif AND bulan = $bulan_aktif";
                            $res_count_real = mysqli_query($koneksi, $q_count_real);
                            $total_real = mysqli_fetch_assoc($res_count_real)['total'];

                            $is_lengkap = ($total_ind > 0 && $total_real >= $total_ind);
                            $sudah_isi = ($total_real > 0); // Minimal ada 1 record terisi

                            // --- 5. Cek Kelengkapan Target ---
                            $q_count_target = "SELECT COUNT(*) as total FROM target WHERE id_cabang = $idc AND tahun = $tahun_aktif AND bulan = 12";
                            $res_count_target = mysqli_query($koneksi, $q_count_target);
                            $total_target = mysqli_fetch_assoc($res_count_target)['total'];

                            $target_lengkap = ($total_ind > 0 && $total_target >= $total_ind);
                        ?>
                            <tr>
                                <td><?php echo $c['kode_cabang']; ?></td>
                                <td><strong><?php echo $c['nama_cabang']; ?></strong></td>
                                <td style="text-align: center;">
                                    <?php if ($is_lengkap): ?>
                                        <span class="badge-pill badge-hijau">
                                            <i class="fas fa-check-circle"></i> Lengkap
                                        </span>
                                    <?php elseif ($sudah_isi): ?>
                                        <span class="badge-pill badge-kuning" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba;">
                                            <i class="fas fa-list-check"></i> <?php echo $total_real; ?> / <?php echo $total_ind; ?> Terisi
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-pill badge-kuning" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">
                                            <i class="fas fa-clock"></i> Belum Ada Data
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($target_lengkap): ?>
                                        <a href="realisasi_form.php?id_cabang=<?php echo $idc; ?>&tahun=<?php echo $tahun_aktif; ?>&bulan=<?php echo $bulan_aktif; ?>" class="btn-input" style="background: var(--biru-utama); color: white; border: none; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 0.85em; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;">
                                            <i class="fas fa-edit"></i> Input Realisasi
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-input" style="background: #cbd5e1; color: #64748b; border: 1px solid #e2e8f0; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 0.85em; display: inline-flex; align-items: center; gap: 8px; cursor: not-allowed; opacity: 0.8;" onclick="Swal.fire({icon:'warning', title:'Struktur Target Berubah', text:'Ada indikator baru atau target yang belum ditentukan (<?php echo $total_target; ?>/<?php echo $total_ind; ?>). Silakan lengkapi Target Tahunan terlebih dahulu di menu Target Cabang.', confirmButtonColor:'#0245A3'})">
                                            <i class="fas fa-lock"></i> Input Realisasi
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateFilter() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const [thn, bln] = val.split('-');
            window.location.href = `realisasi_cabang.php?filter_tahun=${thn}&filter_bulan=${parseInt(bln)}`;
        }

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses_import'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data realisasi untuk <?php echo isset($_GET['count']) ? (int)$_GET['count'] : 0; ?> cabang telah berhasil di-import.',
                confirmButtonColor: '#0245A3'
            });
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'error_kosong'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Data Kosong!',
                text: '<?php echo isset($_GET['pesan_error']) ? addslashes($_GET['pesan_error']) : "Terdapat sel yang masih kosong."; ?>',
                confirmButtonColor: '#0245A3'
            });
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'error_target_tidak_lengkap'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Target Belum Lengkap!',
                text: 'Struktur KPI telah berubah atau target tahunan untuk cabang ini belum disesuaikan. Harap lengkapi Target Tahunan terlebih dahulu.',
                confirmButtonColor: '#0245A3'
            });
        <?php elseif (isset($_GET['pesan']) && $_GET['pesan'] == 'file_tidak_valid'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Format file tidak sesuai atau data tidak valid.',
                confirmButtonColor: '#0245A3'
            });
        <?php endif; ?>
    </script>

</body>

</html>