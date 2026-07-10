<?php
session_start();
require_once "../../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Pemimpin Cabang') {
    header('Location: ../../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../../../index.php');
    exit();
}

$aksi = $_POST['aksi'] ?? '';

// --- 1. Simpan Sanggahan / Protes Data ---
if ($aksi == 'simpan_sanggah') {
    $id_user   = $_SESSION['id_user'];
    $id_cabang = $_SESSION['id_cabang'];
    $tahun     = (int)$_POST['tahun'];
    $bulan     = (int)$_POST['bulan'];
    $kategori  = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $pesan     = mysqli_real_escape_string($koneksi, $_POST['pesan']);

    // Cek apakah sudah ada sanggahan dengan status 'dikirim' untuk kategori, tahun, bulan, dan cabang yang sama
    $q_cek = "SELECT id_sanggahan FROM laporan_sanggahan 
              WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan AND kategori = '$kategori' AND status = 'dikirim'";
    $res_cek = mysqli_query($koneksi, $q_cek);

    if (mysqli_num_rows($res_cek) > 0) {
        $row_cek = mysqli_fetch_assoc($res_cek);
        $id_sanggahan = $row_cek['id_sanggahan'];
        // Update pesan protes dan tanggal protes
        $sql = "UPDATE laporan_sanggahan SET 
                id_user = $id_user,
                pesan_protes = '$pesan',
                tgl_protes = NOW()
                WHERE id_sanggahan = $id_sanggahan";
    } else {
        // Insert baru
        $sql = "INSERT INTO laporan_sanggahan (id_user, id_cabang, tahun, bulan, kategori, pesan_protes, status, tgl_protes) 
                VALUES ($id_user, $id_cabang, $tahun, $bulan, '$kategori', '$pesan', 'dikirim', NOW())";
    }

    if (mysqli_query($koneksi, $sql)) {
        header("Location: ../sanggah_data.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=kirim_berhasil");
    } else {
        header("Location: ../sanggah_data.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=gagal_db");
    }
    exit();
}

// --- 2. Upload Laporan PDF ---
if ($aksi == 'upload_laporan') {
    $id_user   = $_SESSION['id_user'];
    $id_cabang = $_POST['id_cabang'];
    $tahun     = $_POST['tahun'];
    $bulan     = $_POST['bulan'];
    $jenis     = $_POST['jenis_laporan'];

    // Folder tujuan
    $target_dir = "../../../berkas/laporan/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Validasi: pastikan upload tidak error dan file tidak kosong
    $upload_error = $_FILES["file_laporan"]["error"] ?? UPLOAD_ERR_NO_FILE;
    if ($upload_error !== UPLOAD_ERR_OK) {
        header("Location: ../upload_laporan.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=gagal_upload");
        exit();
    }
    if ($_FILES["file_laporan"]["size"] <= 0) {
        header("Location: ../upload_laporan.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=gagal_upload");
        exit();
    }

    // Validasi format file
    $file_ext = strtolower(pathinfo($_FILES["file_laporan"]["name"], PATHINFO_EXTENSION));
    if ($file_ext != "pdf") {
        header("Location: ../upload_laporan.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=gagal_format");
        exit();
    }

    // Penamaan file: Laporan/Kontrak_IDCabang_Tahun_Bulan_Timestamp.pdf
    $prefix       = ($jenis == 'kontrak') ? 'Kontrak' : 'Laporan';
    $new_filename = $prefix . "_" . $id_cabang . "_" . $tahun . "_" . $bulan . "_" . time() . "." . $file_ext;
    $target_file  = $target_dir . $new_filename;

    // Proses upload
    if (move_uploaded_file($_FILES["file_laporan"]["tmp_name"], $target_file)) {
        // Cek apakah sudah pernah upload sebelumnya (untuk jenis yang sama)
        $q_cek = "SELECT id_persetujuan FROM laporan_persetujuan WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan AND jenis_laporan = '$jenis'";
        $res_cek = mysqli_query($koneksi, $q_cek);

        if (mysqli_num_rows($res_cek) > 0) {
            // Hapus file lama agar tidak menumpuk
            $row_lama = mysqli_fetch_assoc($res_cek);
            $file_lama = $target_dir . $row_lama['berkas_pdf'];
            if (!empty($row_lama['berkas_pdf']) && file_exists($file_lama)) {
                unlink($file_lama);
            }
            // Update dengan file baru
            $sql = "UPDATE laporan_persetujuan SET 
                    id_user = $id_user, 
                    berkas_pdf = '$new_filename', 
                    status = 'dikirim', 
                    tgl_unggah = NOW() 
                    WHERE id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan AND jenis_laporan = '$jenis'";
        } else {
            // Insert baru
            $sql = "INSERT INTO laporan_persetujuan (id_user, id_cabang, tahun, bulan, jenis_laporan, berkas_pdf, status, tgl_unggah) 
                    VALUES ($id_user, $id_cabang, $tahun, $bulan, '$jenis', '$new_filename', 'dikirim', NOW())";
        }

        if (mysqli_query($koneksi, $sql)) {
            header("Location: ../upload_laporan.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=upload_berhasil");
        } else {
            header("Location: ../upload_laporan.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=gagal_db");
        }
    } else {
        header("Location: ../upload_laporan.php?filter_tahun=$tahun&filter_bulan=$bulan&pesan=gagal_upload");
    }
    exit();
}

// Default: aksi tidak dikenal
header('Location: ../../../index.php');
exit();
?>
