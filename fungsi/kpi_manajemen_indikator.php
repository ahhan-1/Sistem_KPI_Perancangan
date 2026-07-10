<?php
// FILE MANAJEMEN INDIKATOR KPI
// Digunakan untuk mengelola struktur KPI: Tambah, Update (Satuan/Masal), Hapus, dan Skala Nilai.

session_start();
require_once "koneksi.php";
/** @var mysqli $koneksi */

// Cek Otoritas (Hanya Divisi yang boleh mengelola struktur KPI)
if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    // Jika request via AJAX (untuk skala_proses)
    if (isset($_GET['aksi']) && $_GET['aksi'] == 'skala_proses') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
        exit();
    }
    header('Location: ../index.php');
    exit();
}

// Ambil parameter aksi dari POST atau GET
$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : (isset($_GET['aksi']) ? $_GET['aksi'] : '');

// --- 0. SIMPAN SASARAN STRATEGIS ---
if ($aksi == 'simpan_sasaran' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $filter_tahun = (int) $_POST['filter_tahun'];
    $sasarans = $_POST['sasaran']; // array [id_perspektif => nama_sasaran]

    mysqli_begin_transaction($koneksi);
    try {
        foreach ($sasarans as $id_p => $nama) {
            $id_p = (int) $id_p;
            $nama = mysqli_real_escape_string($koneksi, $nama);

            if (empty(trim($nama))) {
                mysqli_query($koneksi, "DELETE FROM sasaran_strategis WHERE tahun = $filter_tahun AND id_perspektif = $id_p");
                continue;
            }

            $q_cek = "SELECT id_sasaran FROM sasaran_strategis WHERE tahun = $filter_tahun AND id_perspektif = $id_p";
            $res_cek = mysqli_query($koneksi, $q_cek);

            if (mysqli_num_rows($res_cek) > 0) {
                mysqli_query($koneksi, "UPDATE sasaran_strategis SET nama_sasaran = '$nama' WHERE tahun = $filter_tahun AND id_perspektif = $id_p");
            } else {
                mysqli_query($koneksi, "INSERT INTO sasaran_strategis (id_perspektif, tahun, nama_sasaran) VALUES ($id_p, $filter_tahun, '$nama')");
            }
        }

        mysqli_commit($koneksi);
        header("Location: ../halaman/divisi/kpi_daftar.php?status=sukses_sasaran&filter_tahun=$filter_tahun");
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal simpan sasaran: " . $e->getMessage());
    }
    exit();
}

// --- 1. MENYIMPAN INDIKATOR BARU ---
if ($aksi == 'simpan' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $ids_perspektif = $_POST['perspektif'];
    $sasarans_text = $_POST['sasaran_strategis'];
    $subperspektifs = $_POST['subperspektif'];
    $indikators = $_POST['indikator'];
    $satuans = $_POST['satuan'];
    $bobots = $_POST['bobot'];
    $tahun = (int) $_POST['tahun_kontrak'];

    $total_bobot = array_sum($bobots);
    if ($total_bobot != 100) {
        die("Error: Total bobot harus 100%. Total saat ini: $total_bobot%");
    }

    mysqli_begin_transaction($koneksi);
    try {
        for ($i = 0; $i < count($indikators); $i++) {
            $id_p = (int) $ids_perspektif[$i];
            $sasaran_text = trim(mysqli_real_escape_string($koneksi, $sasarans_text[$i] ?? ''));

            if (empty($sasaran_text)) {
                mysqli_rollback($koneksi);
                die("Kesalahan: Sasaran Strategis pada baris " . ($i + 1) . " tidak boleh kosong.");
            }

            $nama_sub = mysqli_real_escape_string($koneksi, $subperspektifs[$i]);
            $nama_ind = mysqli_real_escape_string($koneksi, $indikators[$i]);
            $satuan = mysqli_real_escape_string($koneksi, $satuans[$i]);
            $bobot = (float) $bobots[$i];

            $json_skala = $_POST['skala_data'][$i];
            $d_skala = json_decode($json_skala, true);
            $terbalik = (isset($d_skala['terbalik'])) ? (int) $d_skala['terbalik'] : 0;

            // Cari atau Simpan Subperspektif
            $q_sub = "SELECT id_subperspektif FROM subperspektif WHERE nama_subperspektif = '$nama_sub' AND id_perspektif = $id_p";
            $res_sub = mysqli_query($koneksi, $q_sub);

            if (mysqli_num_rows($res_sub) > 0) {
                $row_sub = mysqli_fetch_assoc($res_sub);
                $id_sub = $row_sub['id_subperspektif'];
            } else {
                mysqli_query($koneksi, "INSERT INTO subperspektif (id_perspektif, nama_subperspektif) VALUES ($id_p, '$nama_sub')");
                $id_sub = mysqli_insert_id($koneksi);
            }

            // Cari atau Simpan Sasaran Strategis
            $q_sasaran = "SELECT id_sasaran FROM sasaran_strategis WHERE nama_sasaran = '$sasaran_text' AND tahun = $tahun AND id_perspektif = $id_p";
            $res_sasaran = mysqli_query($koneksi, $q_sasaran);
            if (mysqli_num_rows($res_sasaran) > 0) {
                $row_sasaran = mysqli_fetch_assoc($res_sasaran);
                $id_sasaran = $row_sasaran['id_sasaran'];
            } else {
                mysqli_query($koneksi, "INSERT INTO sasaran_strategis (id_perspektif, tahun, nama_sasaran) VALUES ($id_p, $tahun, '$sasaran_text')");
                $id_sasaran = mysqli_insert_id($koneksi);
            }

            // Simpan Indikator ke Semua Cabang
            $res_cabang = mysqli_query($koneksi, "SELECT id_cabang FROM cabang");
            while ($row_c = mysqli_fetch_assoc($res_cabang)) {
                $id_cabang_loop = (int)$row_c['id_cabang'];
                
                $q_ind = "INSERT INTO indikator (id_cabang, id_subperspektif, id_sasaran, tahun, nama_indikator, satuan, bobot, terbalik) 
                          VALUES ($id_cabang_loop, $id_sub, $id_sasaran, $tahun, '$nama_ind', '$satuan', $bobot, $terbalik)";
                mysqli_query($koneksi, $q_ind);
                $id_ind_baru = mysqli_insert_id($koneksi);

                // Simpan Skala Nilai untuk masing-masing cabang
                if (isset($d_skala['nilai_minimums'])) {
                    foreach ($d_skala['nilai_minimums'] as $nilai => $val_rating) {
                        if ($val_rating === '')
                            continue;
                        $val_t = mysqli_real_escape_string($koneksi, $val_rating);
                        mysqli_query($koneksi, "INSERT INTO skala_kpi (id_indikator, nilai, nilai_minimum) VALUES ($id_ind_baru, $nilai, '$val_t')");
                    }
                }
            }
        }
        mysqli_commit($koneksi);
        header("Location: ../halaman/divisi/kpi_tambah.php?status=sukses&filter_tahun=$tahun");
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal menyimpan data: " . $e->getMessage());
    }
}

// --- 2. UPDATE MASAL STRUKTUR KPI ---
elseif ($aksi == 'update_masal' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $ids_indikator = $_POST['id_indikator'];
    $sasarans_text = $_POST['sasaran_strategis'];
    $subperspektifs = $_POST['subperspektif'];
    $indikators = $_POST['indikator'];
    $satuans = $_POST['satuan'];
    $bobots = $_POST['bobot'];
    $filter_tahun = (int) $_POST['filter_tahun'];
    $id_cabang = (int) $_POST['id_cabang'];

    $total_bobot = array_sum($bobots);
    if (round($total_bobot, 2) != 100) {
        die("Error: Total bobot harus tepat 100% (Total: $total_bobot%). Perubahan ditolak.");
    }

    $ids_perspektif = $_POST['perspektif'];
    $skala_data_raw = $_POST['skala_data'];

    mysqli_begin_transaction($koneksi);
    try {
        // Ambil indikator saat ini di DB untuk cabang ini
        $q_current = "SELECT id_indikator FROM indikator WHERE tahun = $filter_tahun AND id_cabang = $id_cabang";
        $res_current = mysqli_query($koneksi, $q_current);
        $current_ids = [];
        while ($c = mysqli_fetch_assoc($res_current)) {
            $current_ids[] = (int) $c['id_indikator'];
        }

        // Cari ID yang dihapus (ada di DB tapi tidak ada di POST)
        $post_ids = array_map('intval', array_filter($ids_indikator, function ($id) {
            return $id > 0; }));
        $ids_to_delete = array_diff($current_ids, $post_ids);

        if (!empty($ids_to_delete)) {
            $str_delete = implode(',', $ids_to_delete);
            // Hapus Indikator
            mysqli_query($koneksi, "DELETE FROM indikator WHERE id_indikator IN ($str_delete)");
            // Hapus data terkait
            mysqli_query($koneksi, "DELETE FROM target WHERE id_indikator IN ($str_delete)");
            mysqli_query($koneksi, "DELETE FROM realisasi WHERE id_indikator IN ($str_delete)");
            mysqli_query($koneksi, "DELETE FROM skala_kpi WHERE id_indikator IN ($str_delete)");

            // Reset Nilai Akhir karena bobot pasti berubah (untuk cabang spesifik ini)
            mysqli_query($koneksi, "UPDATE nilai_akhir SET nilai_akhir_kpi = 0, nilai_akhir_kinerja = 0, indeks_nilai_akhir = 'F' WHERE tahun = $filter_tahun AND id_user IN (SELECT id_user FROM users WHERE id_cabang = $id_cabang)");
        }

        // Proses insert/update indikator
        for ($i = 0; $i < count($ids_indikator); $i++) {
            $id_ind = (int) $ids_indikator[$i];
            $id_p = (int) $ids_perspektif[$i];
            $sasaran_text = trim(mysqli_real_escape_string($koneksi, $sasarans_text[$i] ?? ''));

            if (empty($sasaran_text)) {
                mysqli_rollback($koneksi);
                die("Kesalahan: Sasaran Strategis pada baris " . ($i + 1) . " tidak boleh kosong.");
            }

            $nama_sub = mysqli_real_escape_string($koneksi, $subperspektifs[$i]);
            $nama_ind = mysqli_real_escape_string($koneksi, $indikators[$i]);
            $satuan = mysqli_real_escape_string($koneksi, $satuans[$i]);
            $bobot = (float) $bobots[$i];

            if ($id_ind == 0) {
                // Insert data baru
                $d_skala = json_decode($skala_data_raw[$i], true);
                $terbalik = (isset($d_skala['terbalik'])) ? (int) $d_skala['terbalik'] : 0;

                // Cari atau Simpan Subperspektif
                $q_sub = "SELECT id_subperspektif FROM subperspektif WHERE nama_subperspektif = '$nama_sub' AND id_perspektif = $id_p";
                $res_sub = mysqli_query($koneksi, $q_sub);

                if (mysqli_num_rows($res_sub) > 0) {
                    $row_sub = mysqli_fetch_assoc($res_sub);
                    $id_sub = $row_sub['id_subperspektif'];
                } else {
                    mysqli_query($koneksi, "INSERT INTO subperspektif (id_perspektif, nama_subperspektif) VALUES ($id_p, '$nama_sub')");
                    $id_sub = mysqli_insert_id($koneksi);
                }

                // Cari atau Simpan Sasaran Strategis
                $q_sasaran = "SELECT id_sasaran FROM sasaran_strategis WHERE nama_sasaran = '$sasaran_text' AND tahun = $filter_tahun AND id_perspektif = $id_p";
                $res_sasaran = mysqli_query($koneksi, $q_sasaran);
                if (mysqli_num_rows($res_sasaran) > 0) {
                    $row_sasaran = mysqli_fetch_assoc($res_sasaran);
                    $id_sasaran = $row_sasaran['id_sasaran'];
                } else {
                    mysqli_query($koneksi, "INSERT INTO sasaran_strategis (id_perspektif, tahun, nama_sasaran) VALUES ($id_p, $filter_tahun, '$sasaran_text')");
                    $id_sasaran = mysqli_insert_id($koneksi);
                }

                // Simpan Indikator Baru (cabang spesifik)
                $q_new_ind = "INSERT INTO indikator (id_cabang, id_subperspektif, id_sasaran, tahun, nama_indikator, satuan, bobot, terbalik) 
                              VALUES ($id_cabang, $id_sub, $id_sasaran, $filter_tahun, '$nama_ind', '$satuan', $bobot, $terbalik)";
                mysqli_query($koneksi, $q_new_ind);
                $id_ind_baru = mysqli_insert_id($koneksi);

                // Simpan Skala Nilai Baru
                if (!empty($d_skala['nilai_minimums'])) {
                    foreach ($d_skala['nilai_minimums'] as $skor => $val) {
                        if ($val === '')
                            continue;
                        $val_t = mysqli_real_escape_string($koneksi, $val);
                        mysqli_query($koneksi, "INSERT INTO skala_kpi (id_indikator, nilai, nilai_minimum) VALUES ($id_ind_baru, $skor, '$val_t')");
                    }
                }
            } else {
                // Update data lama
                $res = mysqli_query($koneksi, "SELECT id_subperspektif FROM indikator WHERE id_indikator = $id_ind");
                $row = mysqli_fetch_assoc($res);
                $id_sub = $row['id_subperspektif'];

                // Cari atau Simpan Sasaran Strategis (update)
                $q_sasaran = "SELECT id_sasaran FROM sasaran_strategis WHERE nama_sasaran = '$sasaran_text' AND tahun = $filter_tahun AND id_perspektif = $id_p";
                $res_sasaran = mysqli_query($koneksi, $q_sasaran);
                if (mysqli_num_rows($res_sasaran) > 0) {
                    $row_sasaran = mysqli_fetch_assoc($res_sasaran);
                    $id_sasaran = $row_sasaran['id_sasaran'];
                } else {
                    mysqli_query($koneksi, "INSERT INTO sasaran_strategis (id_perspektif, tahun, nama_sasaran) VALUES ($id_p, $filter_tahun, '$sasaran_text')");
                    $id_sasaran = mysqli_insert_id($koneksi);
                }

                mysqli_query($koneksi, "UPDATE subperspektif SET nama_subperspektif = '$nama_sub' WHERE id_subperspektif = $id_sub");
                mysqli_query($koneksi, "UPDATE indikator SET id_sasaran = $id_sasaran, nama_indikator = '$nama_ind', satuan = '$satuan', bobot = $bobot WHERE id_indikator = $id_ind");
            }
        }
        // Cek jika bobot berubah sebelum reset nilai akhir
        $bobot_berubah = false;
        foreach ($ids_indikator as $idx => $id_cek) {
            $id_cek = (int) $id_cek;
            if ($id_cek == 0) {
                // Indikator baru selalu dianggap perubahan
                $bobot_berubah = true;
                break;
            }
            $res_bobot_lama = mysqli_query($koneksi, "SELECT bobot FROM indikator WHERE id_indikator = $id_cek");
            $row_bobot_lama = mysqli_fetch_assoc($res_bobot_lama);
            if ($row_bobot_lama && round((float) $row_bobot_lama['bobot'], 2) != round((float) $bobots[$idx], 2)) {
                $bobot_berubah = true;
                break;
            }
        }

        if ($bobot_berubah) {
            // Bobot berubah → reset nilai akhir agar tidak ada data KPI yang keliru
            mysqli_query($koneksi, "UPDATE nilai_akhir SET nilai_akhir_kpi = 0, nilai_akhir_kinerja = 0, indeks_nilai_akhir = 'F' WHERE tahun = $filter_tahun AND id_user IN (SELECT id_user FROM users WHERE id_cabang = $id_cabang)");
        }

        // Recalculate final scores for all months to keep rankings and resumes automatically updated
        require_once "perhitungan_kpi.php";
        for ($m = 1; $m <= 12; $m++) {
            update_final_score($koneksi, $id_cabang, $filter_tahun, $m);
        }

        mysqli_commit($koneksi);
        header("Location: ../halaman/divisi/kpi_daftar.php?id_cabang=$id_cabang&status=sukses_update&filter_tahun=$filter_tahun");
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal memperbarui data: " . $e->getMessage());
    }
}

// --- 3. UPDATE SATUAN ---
elseif ($aksi == 'update' && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id_ind = (int) $_POST['id_indikator'];
    $nama_sub = mysqli_real_escape_string($koneksi, $_POST['subperspektif']);
    $nama_ind = mysqli_real_escape_string($koneksi, $_POST['indikator']);
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $bobot = (float) $_POST['bobot'];
    $tahun = (int) $_POST['filter_tahun'];

    mysqli_begin_transaction($koneksi);
    try {
        $res = mysqli_query($koneksi, "SELECT id_subperspektif FROM indikator WHERE id_indikator = $id_ind");
        $row = mysqli_fetch_assoc($res);
        $id_sub = $row['id_subperspektif'];

        mysqli_query($koneksi, "UPDATE subperspektif SET nama_subperspektif = '$nama_sub' WHERE id_subperspektif = $id_sub");
        mysqli_query($koneksi, "UPDATE indikator SET nama_indikator = '$nama_ind', satuan = '$satuan', bobot = $bobot WHERE id_indikator = $id_ind");

        mysqli_commit($koneksi);
        header("Location: ../halaman/divisi/kpi_daftar.php?status=sukses_update&filter_tahun=$tahun");
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal memperbarui: " . $e->getMessage());
    }
}

// --- 4. HAPUS INDIKATOR ---
elseif ($aksi == 'hapus' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $tahun = (int) $_GET['tahun'];

    // Cari id_cabang sebelum dihapus
    $res_cab = mysqli_query($koneksi, "SELECT id_cabang FROM indikator WHERE id_indikator = $id");
    $row_cab = mysqli_fetch_assoc($res_cab);
    $id_cabang = $row_cab ? (int) $row_cab['id_cabang'] : 0;

    if (mysqli_query($koneksi, "DELETE FROM indikator WHERE id_indikator = $id")) {
        // Hapus juga target dan realisasi agar tidak jadi sampah (orphaned data)
        mysqli_query($koneksi, "DELETE FROM target WHERE id_indikator = $id");
        mysqli_query($koneksi, "DELETE FROM realisasi WHERE id_indikator = $id");

        if ($id_cabang > 0) {
            // Reset nilai akhir KPI untuk cabang tersebut karena komposisi bobot berubah
            mysqli_query($koneksi, "UPDATE nilai_akhir SET 
                nilai_akhir_kpi = 0, 
                nilai_akhir_kinerja = 0, 
                indeks_nilai_akhir = 'F' 
                WHERE tahun = $tahun AND id_user IN (SELECT id_user FROM users WHERE id_cabang = $id_cabang)");

            // Recalculate final scores for all months to keep rankings and resumes automatically updated
            require_once "perhitungan_kpi.php";
            for ($m = 1; $m <= 12; $m++) {
                update_final_score($koneksi, $id_cabang, $tahun, $m);
            }
        }

        header("Location: ../halaman/divisi/kpi_daftar.php?id_cabang=$id_cabang&status=sukses_hapus&filter_tahun=$tahun");
    } else {
        die("Gagal menghapus: " . mysqli_error($koneksi));
    }
}

// --- 5. PROSES SKALA NILAI (AJAX) ---
elseif ($aksi == 'skala_proses') {
    header('Content-Type: application/json');

    // Ambil Data Skala (GET)
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
        $id_indikator = (int) $_GET['id'];
        $q_ind = "SELECT nama_indikator, terbalik FROM indikator WHERE id_indikator = $id_indikator";
        $res_ind = mysqli_query($koneksi, $q_ind);
        $ind = mysqli_fetch_assoc($res_ind);

        if (!$ind) {
            echo json_encode(['status' => 'error', 'message' => 'Indikator tidak ditemukan.']);
            exit();
        }

        $skala = [];
        $res_skala = mysqli_query($koneksi, "SELECT nilai, nilai_minimum FROM skala_kpi WHERE id_indikator = $id_indikator ORDER BY nilai ASC");
        while ($s = mysqli_fetch_assoc($res_skala)) {
            $skala[$s['nilai']] = (float) $s['nilai_minimum'];
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'nama_indikator' => $ind['nama_indikator'],
                'terbalik' => (int) $ind['terbalik'],
                'nilai_minimums' => $skala
            ]
        ]);
    }
    // Simpan Data Skala (POST)
    elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_indikator'])) {
        $id_indikator = (int) $_POST['id_indikator'];
        $is_inverse = isset($_POST['terbalik']) ? (int) $_POST['terbalik'] : 0;
        $rating_mins = isset($_POST['nilai_minimum']) ? $_POST['nilai_minimum'] : [];

        if (empty($rating_mins)) {
            echo json_encode(['status' => 'error', 'message' => 'Data skala tidak lengkap.']);
            exit();
        }

        mysqli_begin_transaction($koneksi);
        try {
            mysqli_query($koneksi, "UPDATE indikator SET terbalik = $is_inverse WHERE id_indikator = $id_indikator");
            mysqli_query($koneksi, "DELETE FROM skala_kpi WHERE id_indikator = $id_indikator");

            foreach ($rating_mins as $nilai => $val_rating) {
                if ($val_rating === '')
                    continue;
                $nilai = (int) $nilai;
                $val_rating = mysqli_real_escape_string($koneksi, $val_rating);
                mysqli_query($koneksi, "INSERT INTO skala_kpi (id_indikator, nilai, nilai_minimum) VALUES ($id_indikator, $nilai, '$val_rating')");
            }
            mysqli_commit($koneksi);
            echo json_encode(['status' => 'success', 'message' => 'Skala berhasil disimpan.']);
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid scale request.']);
    }
    exit();
}

// --- 6. SIMPAN SKALA (REDIRECT) ---
elseif ($aksi == 'skala_simpan' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_indikator = (int) $_POST['id_indikator'];
    $is_inverse = isset($_POST['terbalik']) ? 1 : 0;
    $rating_mins = $_POST['nilai_minimum'];

    mysqli_begin_transaction($koneksi);
    try {
        mysqli_query($koneksi, "UPDATE indikator SET terbalik = $is_inverse WHERE id_indikator = $id_indikator");
        mysqli_query($koneksi, "DELETE FROM skala_kpi WHERE id_indikator = $id_indikator");

        foreach ($rating_mins as $nilai => $val_rating) {
            if ($val_rating === '')
                continue; // Skip nilai kosong agar tidak masuk DB
            $nilai = (int) $nilai;
            $val_rating = mysqli_real_escape_string($koneksi, $val_rating);
            mysqli_query($koneksi, "INSERT INTO skala_kpi (id_indikator, nilai, nilai_minimum) VALUES ($id_indikator, $nilai, '$val_rating')");
        }

        $res_thn = mysqli_query($koneksi, "SELECT tahun FROM indikator WHERE id_indikator = $id_indikator");
        $row_thn = mysqli_fetch_assoc($res_thn);
        $tahun = $row_thn['tahun'];

        mysqli_commit($koneksi);
        header("Location: ../halaman/divisi/kpi_daftar.php?filter_tahun=$tahun&status=sukses_skala");
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal simpan skala: " . $e->getMessage());
    }
}

// Default Redirect jika aksi tidak dikenal
else {
    header("Location: ../index.php");
    exit();
}
?>