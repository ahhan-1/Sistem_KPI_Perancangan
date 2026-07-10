<?php
// FILE MANAJEMEN PENILAIAN KPI
// Digunakan untuk mengelola hasil evaluasi: Formulir Penilaian, Kertas Kerja, Kompetensi, dan Finalisasi Nilai Akhir.

session_start();
require_once "koneksi.php";
require_once "perhitungan_kpi.php";
/** @var mysqli $koneksi */

// Ambil parameter aksi utama
$aksi_utama = isset($_POST['aksi_utama']) ? $_POST['aksi_utama'] : '';

// --- 1. Simpan Formulir Penilaian (Seksi B & C) ---
if ($aksi_utama == 'simpan_formulir' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id_user = $_SESSION['id_user'];
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    
    $b_tugas = $_POST['b_tugas'] ?? [];
    $total_b = (float)($_POST['b_nilai_total'] ?? 0);
    if($total_b > 0.25) $total_b = 0.25; 
    
    $c_jumlah = $_POST['c_jumlah'] ?? [];
    $pengurang_config = ['TT1' => -0.1, 'TT2' => -0.15, 'TT3' => -0.2, 'Konseling' => -0.05, 'Absensi' => -0.01, 'THTK' => -0.03];

    mysqli_begin_transaction($koneksi);
    try {
        $q_cek = "SELECT id_nilai FROM nilai_akhir WHERE id_user = $id_user AND bulan = $bulan AND tahun = $tahun";
        $res_cek = mysqli_query($koneksi, $q_cek);
        if (mysqli_num_rows($res_cek) > 0) {
            $id_nilai = mysqli_fetch_assoc($res_cek)['id_nilai'];
        } else {
            mysqli_query($koneksi, "INSERT INTO nilai_akhir (id_user, bulan, tahun) VALUES ($id_user, $bulan, $tahun)");
            $id_nilai = mysqli_insert_id($koneksi);
        }

        mysqli_query($koneksi, "DELETE FROM nilai_tambahan WHERE id_nilai = $id_nilai");
        foreach($b_tugas as $index => $tgs_raw) {
            $tgs = mysqli_real_escape_string($koneksi, $tgs_raw);
            if(!empty($tgs)) mysqli_query($koneksi, "INSERT INTO nilai_tambahan (id_nilai, tugas, nilai) VALUES ($id_nilai, '$tgs', $total_b)");
        }

        mysqli_query($koneksi, "DELETE FROM nilai_pengurang WHERE id_nilai = $id_nilai");
        $total_c = 0;
        foreach($c_jumlah as $jenis => $jml) {
            $n = (int)$jml;
            $base = $pengurang_config[$jenis] ?? 0;
            $hasil = $n * $base;
            if($n > 0) {
                mysqli_query($koneksi, "INSERT INTO nilai_pengurang (id_nilai, jenis, jumlah, bobot_faktor, nilai_hasil) VALUES ($id_nilai, '$jenis', $n, $base, $hasil)");
                $total_c += $hasil;
            }
        }

        mysqli_query($koneksi, "UPDATE nilai_akhir SET nilai_tambahan_total = '$total_b', nilai_pengurang_total = '$total_c' WHERE id_nilai = $id_nilai");

        // Panggil sinkronisasi single source of truth untuk hitung KPI (Seksi A) & Kinerja Akhir
        $res_u = mysqli_query($koneksi, "SELECT id_cabang FROM users WHERE id_user = $id_user");
        $row_u = mysqli_fetch_assoc($res_u);
        $id_cabang = (int)$row_u['id_cabang'];
        update_final_score($koneksi, $id_cabang, $tahun, $bulan);

        mysqli_commit($koneksi);
        header("Location: ../halaman/pinca/formulir_penilaian.php?status=sukses&filter_tahun=$tahun&filter_bulan=$bulan");
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal simpan formulir: " . $e->getMessage());
    }
}

// --- 2. Simpan Kertas Kerja (Sync Nilai KPI) ---
elseif ($aksi_utama == 'simpan_kertas_kerja' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id_user = $_SESSION['id_user'];
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];
    $total_kinerja = (float)$_POST['total_kinerja']; 

    mysqli_begin_transaction($koneksi);
    try {
        $q_cek = "SELECT id_nilai FROM nilai_akhir WHERE id_user = $id_user AND bulan = $bulan AND tahun = $tahun";
        $res_cek = mysqli_query($koneksi, $q_cek);

        if (mysqli_num_rows($res_cek) > 0) {
            $id_nilai = mysqli_fetch_assoc($res_cek)['id_nilai'];
            mysqli_query($koneksi, "UPDATE nilai_akhir SET nilai_akhir_kpi = '$total_kinerja' WHERE id_nilai = $id_nilai");
        } else {
            mysqli_query($koneksi, "INSERT INTO nilai_akhir (id_user, bulan, tahun, nilai_akhir_kpi) VALUES ($id_user, $bulan, $tahun, '$total_kinerja')");
            $id_nilai = mysqli_insert_id($koneksi);
        }

        // Cari id_cabang untuk di-update dengan helper update_final_score
        $res_u = mysqli_query($koneksi, "SELECT id_cabang FROM users WHERE id_user = $id_user");
        $row_u = mysqli_fetch_assoc($res_u);
        $id_cabang = (int)$row_u['id_cabang'];
        update_final_score($koneksi, $id_cabang, $tahun, $bulan);

        mysqli_commit($koneksi);
        header("Location: ../halaman/pinca/kertas_kerja.php?status=sukses&filter_tahun=$tahun&filter_bulan=$bulan");
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal simpan kertas kerja: " . $e->getMessage());
    }
}

// --- 3. Simpan Penilaian Kompetensi ---
elseif ($aksi_utama == 'simpan_kompetensi' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = isset($_POST['id_user']) ? (int)$_POST['id_user'] : $_SESSION['id_user'];
    $id_cabang = isset($_POST['id_cabang']) ? (int)$_POST['id_cabang'] : $_SESSION['id_cabang'];
    $tahun = (int)$_POST['tahun'];
    $bulan = (int)$_POST['bulan'];
    $skor_data = $_POST['skor'];

    mysqli_begin_transaction($koneksi);
    try {
        // Ambil nilai KPI akhir dari database untuk keperluan validasi
        $q_na_val = "SELECT nilai_akhir_kpi FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun AND bulan = $bulan";
        $res_na_val = mysqli_query($koneksi, $q_na_val);
        $na_val = mysqli_fetch_assoc($res_na_val);
        $nilai_kpi_a = $na_val ? (float)$na_val['nilai_akhir_kpi'] : 0.0;

        $total_skor = 0;
        // Aturan: Jika KPI < 2.8, SEMUA item (Core + Leadership) maksimal nilai = 3
        $max_skor = ($nilai_kpi_a < 2.8) ? 3 : 4;
        foreach($skor_data as $jenis => $subs) {
            foreach($subs as $nama => $skor) {
                $skor = (int)$skor;
                if ($skor < 0) $skor = 0;
                if ($skor > $max_skor) $skor = $max_skor;
                
                $skor_data[$jenis][$nama] = $skor;
                $total_skor += $skor;
            }
        }
        $nilai_rata_komp = ($total_skor / 40) * 5;

        $q_cek = "SELECT id_kompetensi FROM kompetensi WHERE id_user = $id_user AND tahun = $tahun AND bulan = $bulan";
        $res_cek = mysqli_query($koneksi, $q_cek);
        if (mysqli_num_rows($res_cek) > 0) {
            $id_kompetensi = mysqli_fetch_assoc($res_cek)['id_kompetensi'];
            mysqli_query($koneksi, "UPDATE kompetensi SET nilai_kompetensi = $nilai_rata_komp WHERE id_kompetensi = $id_kompetensi");
        } else {
            mysqli_query($koneksi, "INSERT INTO kompetensi (id_user, tahun, bulan, nilai_kompetensi) VALUES ($id_user, $tahun, $bulan, $nilai_rata_komp)");
            $id_kompetensi = mysqli_insert_id($koneksi);
        }

        foreach($skor_data as $jenis => $subs) {
            foreach($subs as $nama => $skor) {
                $q_det = "SELECT id_kompetensi_rincian FROM kompetensi_rincian WHERE id_kompetensi = $id_kompetensi AND nama_subkompetensi = '$nama'";
                if (mysqli_num_rows(mysqli_query($koneksi, $q_det)) > 0) {
                    mysqli_query($koneksi, "UPDATE kompetensi_rincian SET nilai_subkompetensi = $skor WHERE id_kompetensi = $id_kompetensi AND nama_subkompetensi = '$nama'");
                } else {
                    mysqli_query($koneksi, "INSERT INTO kompetensi_rincian (id_kompetensi, nama_kompetensi, nama_subkompetensi, nilai_subkompetensi) VALUES ($id_kompetensi, '$jenis', '$nama', $skor)");
                }
            }
        }

        $q_na = "SELECT id_nilai FROM nilai_akhir WHERE id_user = $id_user AND bulan = $bulan AND tahun = $tahun";
        $res_na = mysqli_query($koneksi, $q_na);
        if (mysqli_num_rows($res_na) > 0) {
            $id_nilai = mysqli_fetch_assoc($res_na)['id_nilai'];
            mysqli_query($koneksi, "UPDATE nilai_akhir SET id_kompetensi = $id_kompetensi, nilai_kompetensi = $nilai_rata_komp WHERE id_nilai = $id_nilai");
        } else {
            mysqli_query($koneksi, "INSERT INTO nilai_akhir (id_user, bulan, tahun, id_kompetensi, nilai_kompetensi) VALUES ($id_user, $bulan, $tahun, $id_kompetensi, $nilai_rata_komp)");
        }

        update_final_score($koneksi, $id_cabang, $tahun, $bulan);
        mysqli_commit($koneksi);
        
        $redir = "Location: ../halaman/pinca/penilaian_kompetensi.php?filter_tahun=$tahun&filter_bulan=$bulan&status=sukses";
        if(isset($_POST['id_user'])) $redir .= "&id_user=$id_user&id_cabang=$id_cabang";
        header($redir);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal simpan kompetensi: " . $e->getMessage());
    }
}

// ====================================================================================================
// SEKSI 4: PROSES AKHIR (FINALISASI REKOMENDASI & PELATIHAN)
// ====================================================================================================
elseif ($aksi_utama == 'proses_akhir' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id_user = $_SESSION["id_user"];
    $id_nilai = (int)$_POST["id_nilai"];
    $tahun = (int)$_POST["tahun"];
    $bulan = (int)$_POST["bulan"];
    
    $pertimbangan = isset($_POST["pertimbangan"]) ? implode(",", $_POST["pertimbangan"]) : "";
    $rekomendasi = mysqli_real_escape_string($koneksi, $_POST["rekomendasi"]);
    $pel_streams = $_POST["pel_streams"] ?? [];
    $pel_names = $_POST['pel_names'] ?? [];

    // Cegah duplikasi dengan memeriksa database terlebih dahulu berdasarkan periode
    if (!$id_nilai) {
        $q_cek_na = "SELECT id_nilai FROM nilai_akhir WHERE id_user = $id_user AND bulan = $bulan AND tahun = $tahun";
        $res_cek_na = mysqli_query($koneksi, $q_cek_na);
        if (mysqli_num_rows($res_cek_na) > 0) {
            $id_nilai = (int)mysqli_fetch_assoc($res_cek_na)['id_nilai'];
        }
    }

    if (!$id_nilai) {
        mysqli_query($koneksi, "INSERT INTO nilai_akhir (id_user, bulan, tahun, pertimbangan_khusus, rekomendasi_penilai) VALUES ($id_user, $bulan, $tahun, '$pertimbangan', '$rekomendasi')");
        $id_nilai = mysqli_insert_id($koneksi);
    } else {
        mysqli_query($koneksi, "UPDATE nilai_akhir SET pertimbangan_khusus = '$pertimbangan', rekomendasi_penilai = '$rekomendasi' WHERE id_nilai = $id_nilai");
    }

    mysqli_query($koneksi, "DELETE FROM riwayat_pelatihan WHERE id_nilai = $id_nilai");
    foreach ($pel_streams as $s) {
        $nama_p = mysqli_real_escape_string($koneksi, $pel_names[$s] ?? "");
        $s_esc = mysqli_real_escape_string($koneksi, $s);
        mysqli_query($koneksi, "INSERT INTO riwayat_pelatihan (id_nilai, nama_bidang, nama_pelatihan) VALUES ($id_nilai, '$s_esc', '$nama_p')");
    }

    $res_u = mysqli_query($koneksi, "SELECT id_cabang FROM users WHERE id_user = $id_user");
    $row_u = mysqli_fetch_assoc($res_u);
    $id_cabang = (int)$row_u['id_cabang'];
    update_final_score($koneksi, $id_cabang, $tahun, $bulan);

    header("Location: ../halaman/pinca/penilaian_akhir.php?status=sukses&filter_tahun=$tahun&filter_bulan=$bulan");
    exit();
}

// Default Redirect
else {
    header("Location: ../index.php");
    exit();
}
