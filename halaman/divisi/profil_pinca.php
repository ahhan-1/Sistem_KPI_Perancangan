<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$id_user = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
$id_cabang = isset($_GET['id_cabang']) ? (int)$_GET['id_cabang'] : 0;
$tahun = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y'));

if ($id_user == 0 && $id_cabang == 0) {
    echo "ID User atau ID Cabang tidak valid.";
    exit();
}

$success = false;
$error = "";

// --- 1. Ambil Profil / Inisialisasi Baru ---
if ($id_user > 0) {
    $u = get_pinca_profile($koneksi, $id_user, $tahun);
    $id_cabang = $u['id_cabang'];
    
    // --- 2. Validasi Akses ---
    if ($u['jabatan'] != 'Pemimpin Cabang') {
        echo "User ini bukan Pemimpin Cabang.";
        exit();
    }
} else {
    // Inisialisasi data kosong untuk form tambah baru
    $u = [
        'id_user' => 0,
        'id_cabang' => $id_cabang,
        'username' => '',
        'nama' => '',
        'NIP' => '',
        'no_hp' => '',
        'pangkat' => '',
        'direktorat' => '',
        'tanggal_menjabat' => '',
        'tanggal_masuk_kerja' => '',
        'tanggal_pengangkatan_terakhir' => '',
        'level_kip' => '',
        'atasan_langsung' => '',
        'atasan_dari_atasan_langsung' => '',
        'direktur_utama' => '',
        'jabatan' => 'Pemimpin Cabang'
    ];
}

// Ambil Nama Cabang untuk Judul Halaman
$nama_cabang = "";
if ($id_cabang > 0) {
    $q_cab = "SELECT nama_cabang FROM cabang WHERE id_cabang = $id_cabang";
    $res_cab = mysqli_query($koneksi, $q_cab);
    if ($res_cab && mysqli_num_rows($res_cab) > 0) {
        $row_cab = mysqli_fetch_assoc($res_cab);
        $nama_cabang = $row_cab['nama_cabang'];
    }
}

// --- 3. Proses Update / Simpan Profil ---
if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $nip = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $no_hp = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $pangkat = mysqli_real_escape_string($koneksi, $_POST['pangkat']);
    $direktorat = mysqli_real_escape_string($koneksi, $_POST['direktorat']);
    $tgl_menjabat = mysqli_real_escape_string($koneksi, $_POST['tgl_menjabat']);
    $atasan_langsung = mysqli_real_escape_string($koneksi, $_POST['atasan_langsung']);
    $atasan_kedua = mysqli_real_escape_string($koneksi, $_POST['atasan_kedua']);
    $direktur_utama = mysqli_real_escape_string($koneksi, $_POST['direktur_utama']);
    $level_kip = mysqli_real_escape_string($koneksi, $_POST['level_kip']);
    $tgl_masuk = mysqli_real_escape_string($koneksi, $_POST['tgl_masuk']);
    $tgl_pengangkatan = mysqli_real_escape_string($koneksi, $_POST['tgl_pengangkatan']);

    if ($id_user == 0) {
        // Mode: Tambah User Baru
        $username = mysqli_real_escape_string($koneksi, $_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Validasi ketersediaan username di tahun ini
        $q_dup = "SELECT id_user FROM users WHERE username = '$username' AND tahun = $tahun";
        $res_dup = mysqli_query($koneksi, $q_dup);
        if (mysqli_num_rows($res_dup) > 0) {
            $error = "Username '$username' sudah terdaftar di tahun $tahun. Harap gunakan username lain.";
        } else {
            $q_save = "INSERT INTO users (id_cabang, username, password, jabatan, nama, NIP, no_hp, pangkat, direktorat, tanggal_menjabat, tanggal_masuk_kerja, tanggal_pengangkatan_terakhir, level_kip, atasan_langsung, atasan_dari_atasan_langsung, direktur_utama, tahun) 
                       VALUES ($id_cabang, '$username', '$password', 'Pemimpin Cabang', '$nama', '$nip', '$no_hp', '$pangkat', '$direktorat', '$tgl_menjabat', '$tgl_masuk', '$tgl_pengangkatan', '$level_kip', '$atasan_langsung', '$atasan_kedua', '$direktur_utama', $tahun)";
            if (mysqli_query($koneksi, $q_save)) {
                $id_user = mysqli_insert_id($koneksi);
                $success = true;
                header("Location: profil_pinca.php?id_user=$id_user&filter_tahun=$tahun&pesan=sukses_tambah");
                exit();
            } else {
                $error = "Gagal menambah data: " . mysqli_error($koneksi);
            }
        }
    } else {
        // Mode: Update User
        $q_save = "UPDATE users SET 
                     nama = '$nama', 
                     NIP = '$nip', 
                     no_hp = '$no_hp', 
                     pangkat = '$pangkat', 
                     direktorat = '$direktorat', 
                     tanggal_menjabat = '$tgl_menjabat', 
                     tanggal_masuk_kerja = '$tgl_masuk',
                     tanggal_pengangkatan_terakhir = '$tgl_pengangkatan',
                     level_kip = '$level_kip',
                     atasan_langsung = '$atasan_langsung', 
                     atasan_dari_atasan_langsung = '$atasan_kedua',
                     direktur_utama = '$direktur_utama' 
                     WHERE id_user = $id_user";

        if (mysqli_query($koneksi, $q_save)) {
            $success = true;
            $u = get_pinca_profile($koneksi, $id_user, $tahun);
        } else {
            $error = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

$title = ($id_user == 0) ? "Tambah Pemimpin Cabang - " . $nama_cabang : "Kelola Profil Pemimpin Cabang - " . $nama_cabang;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - BSB KPI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <style>
        .profile-wrapper {
            width: 100%;
        }
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            border: 1px solid #edf2f7;
        }
        .profile-header-accent {
            height: 10px;
            background: linear-gradient(to right, var(--biru-utama), #4299e1);
        }
        .profile-body {
            padding: 40px;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #edf2f7;
            color: var(--biru-utama);
        }
        .section-title i { font-size: 1.2em; }
        .section-title h3 { margin: 0; font-size: 1.25em; font-weight: 800; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px 35px;
            margin-bottom: 40px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-size: 0.85em;
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-with-icon {
            position: relative;
        }
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 0.9em;
        }
        .input-with-icon input, .input-with-icon select {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95em;
            transition: all 0.3s ease;
            color: #2d3748;
            background-color: #f8fafc;
        }
        .input-with-icon input:focus, .input-with-icon select:focus {
            border-color: var(--biru-utama);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(2, 69, 163, 0.08);
            outline: none;
        }
        .btn-save {
            background: var(--biru-utama);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            border: none;
            font-weight: 800;
            font-size: 1em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 12px rgba(2, 69, 163, 0.2);
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(2, 69, 163, 0.3);
            background: #013582;
        }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .welcome-header { flex-direction: column; gap: 15px; align-items: flex-start !important; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include "../komponen/sidebar.php"; ?>

    <div class="main-content">
        <div class="profile-wrapper">
            <div class="welcome-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
                <div>
                    <h1 style="margin-bottom: 8px;"><?php echo ($id_user == 0) ? "Tambah Pemimpin Cabang" : "Kelola Profil Pemimpin Cabang"; ?></h1>
                    <p style="color: #718096; font-size: 1.05em;"><?php echo htmlspecialchars($nama_cabang); ?> (Tahun <?php echo $tahun; ?>)</p>
                </div>
                <a href="daftar_pinca.php?filter_tahun=<?php echo $tahun; ?>" class="tombol" style="background: #4a5568; color: white; padding: 12px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-weight: 700; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: all 0.3s ease; white-space: nowrap;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Profil
                </a>
            </div>

            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'wajib_profil' && !$success): ?>
                <div class="kartu" style="border-left: 4px solid #f6ad55; background: #fffaf0; margin-bottom: 30px; padding: 20px 25px; border-radius: 12px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="background: #f6ad55; color: white; width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 1.2em;"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0 0 5px; color: #9c4221; font-weight: 800;">Akses Terbatas!</h4>
                        <p style="margin: 0; color: #9c4221; font-size: 0.9em; line-height: 1.5;">Mohon lengkapi seluruh data pada form <strong>Biodata Pegawai</strong> di bawah ini. Fitur sistem lainnya akan terbuka secara otomatis setelah Anda menyimpan profil lengkap.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'sukses_tambah'): ?>
                <div class="kartu" style="border-left: 4px solid #48bb78; background: #f0fff4; margin-bottom: 30px; padding: 15px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-check-circle" style="color: #48bb78; font-size: 1.2em;"></i>
                    <p style="margin: 0; color: #22543d; font-weight: 600;">Akun Pemimpin Cabang baru berhasil dibuat untuk tahun ini!</p>
                </div>
            <?php endif; ?>

            <?php if ($success && !isset($_GET['pesan'])): ?>
                <div class="kartu" style="border-left: 4px solid #48bb78; background: #f0fff4; margin-bottom: 30px; padding: 15px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-check-circle" style="color: #48bb78; font-size: 1.2em;"></i>
                    <p style="margin: 0; color: #22543d; font-weight: 600;">Data Profil Pemimpin Cabang telah berhasil diperbarui!</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="kartu" style="border-left: 4px solid #f56565; background: #fff5f5; margin-bottom: 30px; padding: 15px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-times-circle" style="color: #f56565; font-size: 1.2em;"></i>
                    <p style="margin: 0; color: #742a2a; font-weight: 600;"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-header-accent"></div>
                <div class="profile-body">
                    <form action="" method="POST">
                        <?php if ($id_user == 0): ?>
                        <!-- --- Kredensial Login (Hanya untuk Tambah Baru) --- -->
                        <div class="section-title">
                            <i class="fas fa-key"></i>
                            <h3>Kredensial Login</h3>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username Login</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="username" required placeholder="Contoh: PemimpinCabangKaptenA.Rivai" style="padding-left: 40px;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Password Login</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" name="password" required placeholder="Masukkan Password Baru" style="padding-left: 40px;">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- --- 5. Data Pribadi --- -->
                        <div class="section-title">
                            <i class="fas fa-user-tie"></i>
                            <h3>Biodata Pegawai</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-id-card"></i>
                                    <input type="text" name="nama" value="<?php echo htmlspecialchars($u['nama'] ?? ''); ?>" required placeholder="Masukkan Nama Tanpa Gelar">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Nomor Induk Pegawai (NIP)</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-fingerprint"></i>
                                    <input type="text" name="nip" value="<?php echo htmlspecialchars($u['NIP'] ?? ''); ?>" required placeholder="Nomor Pegawai Anda">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Nomor HP</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="no_hp" value="<?php echo htmlspecialchars($u['no_hp'] ?? ''); ?>" required placeholder="Contoh: 081234567890">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Pangkat / Golongan</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-medal"></i>
                                    <select name="pangkat" id="pangkatSelect" required onchange="updateLevelOptions()">
                                        <option value="" hidden>-- Pilih Pangkat --</option>
                                        <option value="AVP" <?php echo (($u['pangkat'] ?? '') == 'AVP') ? 'selected' : ''; ?>>Assistant Vice President (AVP)</option>
                                        <option value="MGR" <?php echo (($u['pangkat'] ?? '') == 'MGR') ? 'selected' : ''; ?>>Manajer (MGR)</option>
                                        <option value="AMGR" <?php echo (($u['pangkat'] ?? '') == 'AMGR') ? 'selected' : ''; ?>>Asisten Manajer (AMGR)</option>
                                        <option value="SUPV" <?php echo (($u['pangkat'] ?? '') == 'SUPV') ? 'selected' : ''; ?>>Supervisor (SUPV)</option>
                                        <option value="OFF" <?php echo (($u['pangkat'] ?? '') == 'OFF') ? 'selected' : ''; ?>>Officer (OFF)</option>
                                        <option value="STF" <?php echo (($u['pangkat'] ?? '') == 'STF') ? 'selected' : ''; ?>>Staff (STF)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Direktorat</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-building"></i>
                                    <input type="text" name="direktorat" value="<?php echo htmlspecialchars($u['direktorat'] ?? ''); ?>" required placeholder="Contoh: Bisnis / Operasional">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Level KIP</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-layer-group"></i>
                                    <select name="level_kip" id="levelSelect" required>
                                        <option value="" hidden>-- Pilih Level --</option>
                                        <!-- Opsi akan diisi oleh JavaScript -->
                                    </select>
                                    <input type="hidden" id="hiddenLevel" value="<?php echo htmlspecialchars($u['level_kip'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Masuk Kerja</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-door-open"></i>
                                    <input type="date" name="tgl_masuk" value="<?php echo $u['tanggal_masuk_kerja'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pengangkatan Terakhir</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-file-invoice"></i>
                                    <input type="date" name="tgl_pengangkatan" value="<?php echo $u['tanggal_pengangkatan_terakhir'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Menjabat Saat Ini</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-calendar-check"></i>
                                    <input type="date" name="tgl_menjabat" value="<?php echo $u['tanggal_menjabat'] ?? ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- --- 6. Struktur Hierarki --- -->
                        <div class="section-title">
                            <i class="fas fa-sitemap"></i>
                            <h3>Hierarki Atasan</h3>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Atasan Langsung</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user-shield"></i>
                                    <input type="text" name="atasan_langsung" value="<?php echo $u['atasan_langsung'] ?? ''; ?>" required placeholder="Nama Lengkap Atasan">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Atasan dari Atasan Langsung</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user-check"></i>
                                    <input type="text" name="atasan_kedua" value="<?php echo $u['atasan_dari_atasan_langsung'] ?? ''; ?>" required placeholder="Nama Lengkap Direktur/Jabatan Terkait">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Direktur Utama</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user-tie"></i>
                                    <input type="text" name="direktur_utama" value="<?php echo $u['direktur_utama'] ?? ''; ?>" required placeholder="Nama Lengkap Direktur Utama">
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="simpan" class="btn-save">
                            <i class="fas fa-save"></i>
                            SIMPAN PERUBAHAN DATA PROFIL
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
const levelMappings = {
    'AVP': ['AVP 1', 'AVP 2', 'AVP 3'],
    'MGR': ['MGR 1', 'MGR 2', 'MGR 3'],
    'AMGR': ['AMGR 1', 'AMGR 2', 'AMGR 3'],
    'SUPV': ['SUPV 1', 'SUPV 2', 'SUPV 3'],
    'OFF': ['OFF 1', 'OFF 2', 'OFF 3'],
    'STF': ['STF 1', 'STF 2', 'STF 3']
};

function updateLevelOptions() {
    const pangkat = document.getElementById('pangkatSelect').value;
    const levelSelect = document.getElementById('levelSelect');
    const currentValue = document.getElementById('hiddenLevel').value;
    
    // Clear current options
    levelSelect.innerHTML = '<option value="" hidden>-- Pilih Level --</option>';
    
    if (pangkat && levelMappings[pangkat]) {
        levelMappings[pangkat].forEach(lvl => {
            const option = document.createElement('option');
            option.value = lvl;
            option.text = lvl;
            if (lvl === currentValue) {
                option.selected = true;
            }
            levelSelect.appendChild(option);
        });
    }
}

// --- 7. Inisialisasi Select ---
window.onload = function() {
    updateLevelOptions();
};
</script>

</body>
</html>
