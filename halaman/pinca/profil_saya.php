<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Pemimpin Cabang') {
    header('Location: ../../index.php');
    exit();
}

$id_user = $_SESSION['id_user'];
$tahun = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');

// --- 1. Ambil Data Profil ---
$u = get_pinca_profile($koneksi, $id_user, $tahun);
if (!$u) {
    $u = [
        'id_user' => $id_user,
        'id_cabang' => $_SESSION['id_cabang'] ?? 0,
        'nama' => '',
        'NIP' => '',
        'no_hp' => '',
        'pangkat' => '',
        'direktorat' => '',
        'level_kip' => '',
        'tanggal_menjabat' => '',
        'tanggal_masuk_kerja' => '',
        'tanggal_pengangkatan_terakhir' => '',
        'atasan_langsung' => '',
        'atasan_dari_atasan_langsung' => '',
        'direktur_utama' => ''
    ];
}
$q_cab = "SELECT nama_cabang FROM cabang WHERE id_cabang = " . (int)$u['id_cabang'];
$res_cab = mysqli_query($koneksi, $q_cab);
$cab = mysqli_fetch_assoc($res_cab);
$u['nama_cabang'] = $cab['nama_cabang'] ?? '';

$jabatan_display = $_SESSION['jabatan'] ?? 'Pemimpin Cabang';

$title = "Profil Saya";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - BSB KPI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <style>
        .profile-wrapper {
            width: 100%;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
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

        .section-title i {
            font-size: 1.2em;
        }

        .section-title h3 {
            margin: 0;
            font-size: 1.25em;
            font-weight: 800;
        }

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

        /* Read-only value box — mirip input tapi static */
        .input-with-icon .readonly-val {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95em;
            color: #2d3748;
            background-color: #f8fafc;
            min-height: 46px;
            display: flex;
            align-items: center;
            box-sizing: border-box;
        }

        .readonly-val.empty {
            color: #94a3b8;
            font-style: italic;
        }


        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .profile-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="profile-wrapper">
                <div class="welcome-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div>
                        <h1>Profil Saya</h1>
                        <p>Informasi biodata jabatan Anda yang digunakan dalam dokumen KPI.</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fas fa-minus" style="font-size: 12px;"></i>
                        </button>
                        <div class="filter-periode-container no-chevron">
                            <i class="fas fa-calendar-check"></i>
                            <select id="tahunFilter" class="input-year-select" onchange="updateFilter(this.value)">
                                <!-- Akan diisi oleh JS -->
                            </select>
                        </div>
                        <button type="button" onclick="tambahTahun()" title="Tambah Tahun" style="background: var(--biru-utama); color: white; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fas fa-plus" style="font-size: 12px; color: white;"></i>
                        </button>
                    </div>
                </div>



                <div class="profile-card">
                    <div class="profile-header-accent"></div>
                    <div class="profile-body">

                        <!-- --- 2. Biodata Pegawai --- -->
                        <div class="section-title">
                            <i class="fas fa-user-tie"></i>
                            <h3>Biodata Pegawai</h3>
                        </div>

                        <div class="form-grid">
                            <!-- Nama Lengkap -->
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-id-card"></i>
                                    <div class="readonly-val <?php echo empty($u['nama']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['nama']) ? htmlspecialchars($u['nama']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- NIP -->
                            <div class="form-group">
                                <label>Nomor Induk Pegawai (NIP)</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-fingerprint"></i>
                                    <div class="readonly-val <?php echo empty($u['NIP']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['NIP']) ? htmlspecialchars($u['NIP']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Nomor HP -->
                            <div class="form-group">
                                <label>Nomor HP</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-phone"></i>
                                    <div class="readonly-val <?php echo empty($u['no_hp']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['no_hp']) ? htmlspecialchars($u['no_hp']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Pangkat -->
                            <div class="form-group">
                                <label>Pangkat / Golongan</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-medal"></i>
                                    <div class="readonly-val <?php echo empty($u['pangkat']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['pangkat']) ? htmlspecialchars($u['pangkat']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Direktorat -->
                            <div class="form-group">
                                <label>Direktorat</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-building"></i>
                                    <div class="readonly-val <?php echo empty($u['direktorat']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['direktorat']) ? htmlspecialchars($u['direktorat']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Level KIP -->
                            <div class="form-group">
                                <label>Level KIP</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-layer-group"></i>
                                    <div class="readonly-val <?php echo empty($u['level_kip']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['level_kip']) ? htmlspecialchars($u['level_kip']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Jabatan -->
                            <div class="form-group">
                                <label>Jabatan</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user-tie"></i>
                                    <div class="readonly-val">
                                        <?php echo htmlspecialchars($jabatan_display); ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Kantor Cabang -->
                            <div class="form-group">
                                <label>Kantor Cabang</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div class="readonly-val <?php echo empty($u['nama_cabang']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['nama_cabang']) ? htmlspecialchars($u['nama_cabang']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Tanggal Masuk Kerja -->
                            <div class="form-group">
                                <label>Tanggal Masuk Kerja</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-door-open"></i>
                                    <div class="readonly-val <?php echo empty($u['tanggal_masuk_kerja']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['tanggal_masuk_kerja']) ? date('d-m-Y', strtotime($u['tanggal_masuk_kerja'])) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Tanggal Pengangkatan Terakhir -->
                            <div class="form-group">
                                <label>Tanggal Pengangkatan Terakhir</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-file-invoice"></i>
                                    <div class="readonly-val <?php echo empty($u['tanggal_pengangkatan_terakhir']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['tanggal_pengangkatan_terakhir']) ? date('d-m-Y', strtotime($u['tanggal_pengangkatan_terakhir'])) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Tanggal Menjabat -->
                            <div class="form-group">
                                <label>Tanggal Menjabat Saat Ini</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-calendar-check"></i>
                                    <div class="readonly-val <?php echo empty($u['tanggal_menjabat']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['tanggal_menjabat']) ? date('d-m-Y', strtotime($u['tanggal_menjabat'])) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- --- 3. Hierarki Atasan --- -->
                        <div class="section-title">
                            <i class="fas fa-sitemap"></i>
                            <h3>Hierarki Atasan</h3>
                        </div>

                        <div class="form-grid">
                            <!-- Atasan Langsung -->
                            <div class="form-group">
                                <label>Atasan Langsung</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user-shield"></i>
                                    <div class="readonly-val <?php echo empty($u['atasan_langsung']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['atasan_langsung']) ? htmlspecialchars($u['atasan_langsung']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Atasan dari Atasan -->
                            <div class="form-group">
                                <label>Atasan dari Atasan Langsung</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user-check"></i>
                                    <div class="readonly-val <?php echo empty($u['atasan_dari_atasan_langsung']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['atasan_dari_atasan_langsung']) ? htmlspecialchars($u['atasan_dari_atasan_langsung']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Direktur Utama</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user-tie"></i>
                                    <div class="readonly-val <?php echo empty($u['direktur_utama']) ? 'empty' : ''; ?>">
                                        <?php echo !empty($u['direktur_utama']) ? htmlspecialchars($u['direktur_utama']) : 'Belum diisi'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const tahunAktif = <?php echo $tahun; ?>;
        const selectTahun = document.getElementById('tahunFilter');

        function renderTahun() {
            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
            if (maxYear < 2026) {
                maxYear = 2026;
                localStorage.setItem('max_year_kpi', maxYear);
            }
            if (tahunAktif > maxYear) maxYear = tahunAktif;

            selectTahun.innerHTML = '';
            for (let t = 2026; t <= maxYear; t++) {
                const opt = document.createElement('option');
                opt.value = t;
                opt.text = 'Tahun ' + t;
                if (t === tahunAktif) opt.selected = true;
                selectTahun.appendChild(opt);
            }
        }

        function updateFilter(val) {
            window.location.href = 'profil_saya.php?filter_tahun=' + val;
        }

        function tambahTahun() {
            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
            if (maxYear < 2026) maxYear = 2026;
            maxYear++;
            localStorage.setItem('max_year_kpi', maxYear);
            renderTahun();

            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'success',
                title: 'Tahun ' + maxYear + ' ditambahkan ke daftar'
            });
        }

        function kurangTahun() {
            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
            if (maxYear > 2026) {
                maxYear--;
                localStorage.setItem('max_year_kpi', maxYear);
                renderTahun();
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: 'success',
                    title: 'Tahun ' + (maxYear + 1) + ' dihapus dari daftar'
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Batas Minimum',
                    text: 'Tahun tidak boleh kurang dari 2026.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }

        // Inisialisasi
        renderTahun();
    </script>
</body>

</html>