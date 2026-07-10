<?php
// FILE MANAJEMEN DATA KPI
// Digunakan untuk mengelola data angka: Simpan Target Tahunan, Koreksi Target Bulanan, dan Realisasi Bulanan.

session_start();
require_once "koneksi.php";
require_once "perhitungan_kpi.php";
/** @var mysqli $koneksi */

// Ambil parameter aksi utama
$aksi_utama = isset($_POST['aksi_utama']) ? $_POST['aksi_utama'] : '';

// --- 1. Simpan Target Tahunan ---
if ($aksi_utama == 'simpan_target' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cabang      = (int)$_POST['id_cabang'];
    $tahun          = (int)$_POST['filter_tahun'];
    $target_data    = $_POST['target']; // Array: [id_indikator => nilai]
    $aksi_sub       = $_POST['aksi'];   // 'simpan_kembali' atau 'simpan_lanjut'
    $id_berikutnya  = isset($_POST['id_berikutnya']) ? (int)$_POST['id_berikutnya'] : null;

    mysqli_begin_transaction($koneksi);
    try {
        foreach ($target_data as $id_indikator => $nilai_target) {
            $id_indikator = (int)$id_indikator;
            $nilai_target = (float)$nilai_target;

            $q_cek = "SELECT id_target FROM target WHERE id_cabang = $id_cabang AND id_indikator = $id_indikator AND tahun = $tahun AND bulan = 12";
            $res_cek = mysqli_query($koneksi, $q_cek);

            if (mysqli_num_rows($res_cek) > 0) {
                $row = mysqli_fetch_assoc($res_cek);
                mysqli_query($koneksi, "UPDATE target SET nilai_target = $nilai_target WHERE id_target = " . $row['id_target']);
            } else {
                mysqli_query($koneksi, "INSERT INTO target (id_indikator, id_cabang, tahun, bulan, nilai_target) VALUES ($id_indikator, $id_cabang, $tahun, 12, $nilai_target)");
            }
        }
        mysqli_commit($koneksi);
        
        if ($aksi_sub == 'simpan_lanjut' && $id_berikutnya !== null) {
            header("Location: ../halaman/divisi/target_form.php?id_cabang=$id_berikutnya&tahun=$tahun&status=sukses_lanjut");
        } else {
            header("Location: ../halaman/divisi/target_cabang.php?status=sukses&filter_tahun=$tahun");
        }
        exit();
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal menyimpan target: " . $e->getMessage());
    }
}

// --- 2. Simpan Koreksi Target Bulanan ---
elseif ($aksi_utama == 'simpan_koreksi' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cabang      = (int)$_POST['id_cabang'];
    $tahun          = (int)$_POST['filter_tahun'];
    $bulan          = (int)$_POST['filter_bulan'];
    $target_koreksi = $_POST['target_koreksi'] ?? [];
    $aksi_sub       = isset($_POST['aksi']) ? $_POST['aksi'] : '';

    mysqli_begin_transaction($koneksi);
    try {
        foreach ($target_koreksi as $id_indikator => $nilai) {
            $id_indikator = (int)$id_indikator;
            
            if ($nilai === '' || $nilai === null) {
                mysqli_query($koneksi, "DELETE FROM target WHERE id_cabang = $id_cabang AND id_indikator = $id_indikator AND tahun = $tahun AND bulan = $bulan");
            } else {
                $nilai = (float)$nilai;
                $q_cek = "SELECT id_target FROM target WHERE id_cabang = $id_cabang AND id_indikator = $id_indikator AND tahun = $tahun AND bulan = $bulan";
                $res_cek = mysqli_query($koneksi, $q_cek);

                if (mysqli_num_rows($res_cek) > 0) {
                    $row = mysqli_fetch_assoc($res_cek);
                    mysqli_query($koneksi, "UPDATE target SET nilai_target = $nilai WHERE id_target = " . $row['id_target']);
                } else {
                    mysqli_query($koneksi, "INSERT INTO target (id_indikator, id_cabang, tahun, bulan, nilai_target) VALUES ($id_indikator, $id_cabang, $tahun, $bulan, $nilai)");
                }
            }
        }
        mysqli_commit($koneksi);
        
        update_final_score($koneksi, $id_cabang, $tahun, $bulan);
        
        if ($aksi_sub == 'simpan_lanjut' && $bulan < 12) {
            $next_bulan = $bulan + 1;
            header("Location: ../halaman/divisi/target_koreksi.php?id_cabang=$id_cabang&tahun=$tahun&bulan=$next_bulan&status=sukses");
        } else {
            header("Location: ../halaman/divisi/target_koreksi.php?id_cabang=$id_cabang&tahun=$tahun&bulan=$bulan&status=sukses");
        }
        exit();
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal menyimpan koreksi: " . $e->getMessage());
    }
}

// --- 3. Simpan Realisasi Bulanan ---
elseif ($aksi_utama == 'simpan_realisasi' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cabang = (int)$_POST['id_cabang'];
    $tahun = (int)$_POST['tahun'];
    $bulan = (int)$_POST['bulan'];
    $realisasi_data = isset($_POST['realisasi']) ? $_POST['realisasi'] : [];
    $aksi_sub = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $id_berikutnya = isset($_POST['id_berikutnya']) ? $_POST['id_berikutnya'] : null;

    mysqli_begin_transaction($koneksi);
    try {
        foreach ($realisasi_data as $id_indikator => $nilai) {
            $id_indikator = (int)$id_indikator;
            $nilai = (float)$nilai;

            $q_cek = "SELECT id_realisasi FROM realisasi WHERE id_indikator = $id_indikator AND id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan";
            $res_cek = mysqli_query($koneksi, $q_cek);

            if (mysqli_num_rows($res_cek) > 0) {
                $row = mysqli_fetch_assoc($res_cek);
                mysqli_query($koneksi, "UPDATE realisasi SET realisasi = $nilai WHERE id_realisasi = " . $row['id_realisasi']);
            } else {
                mysqli_query($koneksi, "INSERT INTO realisasi (id_indikator, id_cabang, realisasi, tahun, bulan) VALUES ($id_indikator, $id_cabang, $nilai, $tahun, $bulan)");
            }
        }
        mysqli_commit($koneksi);
        
        update_final_score($koneksi, $id_cabang, $tahun, $bulan);

        if ($aksi_sub == 'simpan_lanjut' && $id_berikutnya) {
            header("Location: ../halaman/divisi/realisasi_form.php?id_cabang=$id_berikutnya&tahun=$tahun&bulan=$bulan&status=sukses");
        } else {
            header("Location: ../halaman/divisi/realisasi_cabang.php?filter_tahun=$tahun&filter_bulan=$bulan&status=sukses");
        }
        exit();
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal menyimpan realisasi: " . $e->getMessage());
    }
}

// Default Redirect
else {
    header("Location: ../index.php");
    exit();
}
