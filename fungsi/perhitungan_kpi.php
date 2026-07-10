<?php
// Helper Perhitungan KPI - Single Source of Truth

// Hitung Target Dinamis (Bulan tunggal atau YTD)
// Logika: Ambil target bulanan langsung dari database
if (!function_exists('calculate_target_period')) {
function calculate_target_period(mysqli $koneksi, int $id_indikator, int $id_cabang, int $tahun, int $bulan_target) {
    $q = "SELECT nilai_target FROM target WHERE id_indikator = $id_indikator AND id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan_target";
    $res = mysqli_query($koneksi, $q);
    $row = mysqli_fetch_assoc($res);
    return $row ? (float)$row['nilai_target'] : 0.0;
}
}


// Hitung ulang & simpan total skor KPI ke tabel nilai_akhir
if (!function_exists('update_final_score')) {
function update_final_score(mysqli $koneksi, int $id_cabang, int $tahun, int $bulan) {
    $q_user = "SELECT id_user FROM users WHERE id_cabang = $id_cabang AND jabatan = 'Pemimpin Cabang' AND tahun = $tahun LIMIT 1";
    $res_user = mysqli_query($koneksi, $q_user);
    if (mysqli_num_rows($res_user) == 0) {
        $q_fallback = "SELECT id_user FROM users WHERE id_cabang = $id_cabang AND jabatan = 'Pemimpin Cabang' ORDER BY tahun DESC LIMIT 1";
        $res_user = mysqli_query($koneksi, $q_fallback);
        if (mysqli_num_rows($res_user) == 0) return false;
    }
    $u = mysqli_fetch_assoc($res_user);
    $id_user = $u['id_user'];

    // 2. Ambil seluruh master indikator aktif
    $q_ind = "SELECT id_indikator, nama_indikator, bobot, terbalik FROM indikator WHERE tahun = $tahun AND id_cabang = $id_cabang";
    $res_ind = mysqli_query($koneksi, $q_ind);
    
    $total_kpi_weighted = 0;
    
    while ($ind = mysqli_fetch_assoc($res_ind)) {
        $id_ind = $ind['id_indikator'];
        $nama = $ind['nama_indikator'];
        $bobot = (float)$ind['bobot'];
        $is_inv = (int)$ind['terbalik'];

        // Ambil realisasi bulanan cabang
        $q_real = "SELECT realisasi FROM realisasi WHERE id_cabang = $id_cabang AND id_indikator = $id_ind AND tahun = $tahun AND bulan = $bulan";
        $res_real = mysqli_query($koneksi, $q_real);
        $r_row = mysqli_fetch_assoc($res_real);
        $val_real = (float)($r_row['realisasi'] ?? 0);

        // Dapatkan target dinamis bulan ini (Monthly)
        $target_prorata = calculate_target_period($koneksi, $id_ind, $id_cabang, $tahun, $bulan);
        
        $pencapaian = calculate_pencapaian($nama, $target_prorata, $val_real, $is_inv);
        $skor = calculate_skor_indikator($koneksi, $id_ind, $pencapaian);

        $total_kpi_weighted += ($skor * ($bobot / 100));
    }

    // 3. Update atau Insert ke tabel nilai_akhir
    $q_cek = "SELECT id_nilai, nilai_kompetensi, nilai_tambahan_total, nilai_pengurang_total FROM nilai_akhir WHERE id_user = $id_user AND tahun = $tahun AND bulan = $bulan";
    $res_cek = mysqli_query($koneksi, $q_cek);

    if (mysqli_num_rows($res_cek) > 0) {
        $na = mysqli_fetch_assoc($res_cek);
        $id_nilai = $na['id_nilai'];
        $val_komp = (float)$na['nilai_kompetensi'];
        $val_b = (float)$na['nilai_tambahan_total'];
        $val_c = (float)$na['nilai_pengurang_total'];
        
        if ($val_komp > 0) {
            // Skema Bobot Akhir: KPI 85% + Kompetensi 15% + Tambahan - Pengurang
            $final_score = ($total_kpi_weighted * 0.85) + ($val_komp * 0.15) + $val_b + $val_c;
            $indeks = calculate_indeks($final_score);

            mysqli_query($koneksi, "UPDATE nilai_akhir SET 
                nilai_akhir_kpi = '$total_kpi_weighted', 
                nilai_akhir_kinerja = '$final_score', 
                indeks_nilai_akhir = '$indeks' 
                WHERE id_nilai = $id_nilai");
        } else {
            // Jika kompetensi belum dinilai, final score tetap 0 (belum rampung)
            mysqli_query($koneksi, "UPDATE nilai_akhir SET 
                nilai_akhir_kpi = '$total_kpi_weighted',
                nilai_akhir_kinerja = 0,
                indeks_nilai_akhir = 'F'
                WHERE id_nilai = $id_nilai");
        }
    } else {
        // Insert record baru jika belum ada
        mysqli_query($koneksi, "INSERT INTO nilai_akhir (id_user, tahun, bulan, nilai_akhir_kpi, nilai_akhir_kinerja, indeks_nilai_akhir) 
            VALUES ($id_user, $tahun, $bulan, '$total_kpi_weighted', 0, 'F')");
    }

    return true;
}
}

// Hitung persentase capaian (%)
if (!function_exists('calculate_pencapaian')) {
function calculate_pencapaian(string $nama_indikator, float $target_prorata, float $val_real, int $is_inv = 0, string $satuan = '') {
    // Repayment Rate: pencapaian = realisasi langsung (sudah berbentuk %)
    $is_repayment = (stripos(trim($nama_indikator), 'Repayment Rate') !== false);
    if ($is_repayment) {
        return (float)$val_real;
    }

    if ($target_prorata > 0) {
        // Semua indikator (termasuk Score) dihitung: realisasi / target * 100
        if ($is_inv) {
            return ($val_real <= 0) ? 100 : ($target_prorata / $val_real) * 100;
        } else {
            return ($val_real / $target_prorata) * 100;
        }
    } else {
        // Target 0: tidak bisa dihitung %, kembalikan realisasi mentah agar bisa dicocokkan ke skala
        return (float)$val_real;
    }
}
}

// Konversi capaian ke skor 1-5 berdasarkan skala_kpi
if (!function_exists('calculate_skor_indikator')) {
function calculate_skor_indikator(mysqli $koneksi, int $id_ind, float $pencapaian) {
    // Ambil nilai terendah sebagai fallback jika tidak memenuhi syarat manapun
    $q_min = "SELECT nilai FROM skala_kpi WHERE id_indikator = $id_ind ORDER BY nilai ASC LIMIT 1";
    $res_min = mysqli_query($koneksi, $q_min);
    $skor = ($row = mysqli_fetch_assoc($res_min)) ? (int)$row['nilai'] : 1;
    
    $q = "SELECT nilai, nilai_minimum FROM skala_kpi WHERE id_indikator = $id_ind ORDER BY nilai_minimum DESC, nilai DESC";
    $res = mysqli_query($koneksi, $q);
    while ($sk = mysqli_fetch_assoc($res)) {
        if ($pencapaian >= (float)$sk['nilai_minimum']) {
            $skor = (int)$sk['nilai'];
            break;
        }
    }
    return $skor;
}
}

// Konversi skor ke indeks huruf (A-F)
if (!function_exists('calculate_indeks')) {
function calculate_indeks(float $score) {
    if ($score <= 0) return "F"; // F = Incomplete / Not yet assessed
    if ($score >= 4.51) return "A";
    if ($score >= 3.00) return "B";
    if ($score >= 2.01) return "C";
    if ($score >= 1.01) return "D";
    return "E";
}
}

// Label untuk Indeks Kinerja
if (!function_exists('calculate_indeks_label')) {
function calculate_indeks_label(string $indeks) {
    switch ($indeks) {
        case 'A': return "Memuaskan";
        case 'B': return "Baik";
        case 'C': return "Cukup";
        case 'D': return "Perlu Peningkatan";
        case 'E': return "Kurang";
        case 'F': return "Data Belum Lengkap";
        default: return "Tanpa Penilaian";
    }
}
}

// Mapping Angka ke Nama Bulan
if (!function_exists('get_nama_bulan')) {
function get_nama_bulan() {
    return [
        1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 
        5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus", 
        9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"
    ];
}
}

/**
 * Get Pinca Profile for a specific Year
 */
if (!function_exists('get_pinca_profile')) {
function get_pinca_profile(mysqli $koneksi, int $id_user, int $tahun) {
    // 1. Dapatkan id_cabang dari user saat ini
    $q_base = "SELECT id_cabang, jabatan FROM users WHERE id_user = $id_user";
    $res_base = mysqli_query($koneksi, $q_base);
    $base = mysqli_fetch_assoc($res_base);
    
    if ($base && $base['jabatan'] == 'Pemimpin Cabang') {
        $id_cabang = (int)$base['id_cabang'];
        // 2. Cari pinca untuk cabang tersebut pada tahun tersebut
        $q_pinca = "SELECT * FROM users WHERE id_cabang = $id_cabang AND jabatan = 'Pemimpin Cabang' AND tahun = $tahun LIMIT 1";
        $res_pinca = mysqli_query($koneksi, $q_pinca);
        if ($res_pinca && mysqli_num_rows($res_pinca) > 0) {
            return mysqli_fetch_assoc($res_pinca);
        }
    }
    
    // Jangan fallback ke tahun lain secara tidak sengaja
    $q_user = "SELECT * FROM users WHERE id_user = $id_user AND tahun = $tahun";
    $res_user = mysqli_query($koneksi, $q_user);
    if ($res_user && mysqli_num_rows($res_user) > 0) {
        return mysqli_fetch_assoc($res_user);
    }
    return false;
}
}

/**
 * Check if Pemimpin Cabang profile is fully set by BKU/Divisi
 */
if (!function_exists('is_pinca_profile_complete')) {
function is_pinca_profile_complete(mysqli $koneksi, int $id_user, int $tahun) {
    $u = get_pinca_profile($koneksi, $id_user, $tahun);
    if (!$u) return false;
    $fields_required = ['nama', 'NIP', 'no_hp', 'pangkat', 'direktorat', 'tanggal_menjabat', 'tanggal_masuk_kerja', 'tanggal_pengangkatan_terakhir', 'atasan_langsung', 'direktur_utama'];
    foreach ($fields_required as $f) {
        if (empty($u[$f]) || trim($u[$f]) === '') {
            return false;
        }
    }
    return true;
}
}
