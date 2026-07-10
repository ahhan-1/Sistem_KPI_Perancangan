<?php
session_start();
require_once "../../../fungsi/koneksi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../../../index.php');
    exit();
}

$aksi = $_POST['aksi'] ?? '';

// --- 1. Proses Persetujuan Laporan ---
if ($aksi == 'persetujuan') {
    $id_persetujuan = (int)$_POST['id_persetujuan'];
    $status         = mysqli_real_escape_string($koneksi, $_POST['status']);
    $catatan        = isset($_POST['catatan']) ? mysqli_real_escape_string($koneksi, $_POST['catatan']) : '';
    $tahun          = (int)$_POST['tahun'];
    $bulan          = (int)$_POST['bulan'];

    $sql = "UPDATE laporan_persetujuan SET 
            status = '$status', 
            catatan_bku = '$catatan' 
            WHERE id_persetujuan = $id_persetujuan";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: ../persetujuan_laporan.php?tahun=$tahun&bulan=$bulan&pesan=proses_berhasil");
    } else {
        header("Location: ../persetujuan_laporan.php?tahun=$tahun&bulan=$bulan&pesan=gagal_db");
    }
    exit();
}

// --- 2. Proses Balasan Sanggahan ---
if ($aksi == 'sanggah') {
    $id_sanggahan = (int)$_POST['id_sanggahan'];
    $status       = mysqli_real_escape_string($koneksi, $_POST['status']);
    $balasan      = mysqli_real_escape_string($koneksi, $_POST['balasan']);

    $sql = "UPDATE laporan_sanggahan SET 
            status = '$status', 
            balasan_bku = '$balasan' 
            WHERE id_sanggahan = $id_sanggahan";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: ../sanggah_cabang.php?pesan=proses_berhasil");
    } else {
        header("Location: ../sanggah_cabang.php?pesan=gagal_db");
    }
    exit();
}

// Default: aksi tidak dikenal
header('Location: ../../../index.php');
exit();
?>
