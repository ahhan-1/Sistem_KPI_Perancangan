<?php
session_start();
require_once "../../fungsi/koneksi.php";
require_once "../../fungsi/perhitungan_kpi.php";
/** @var mysqli $koneksi */

if (!isset($_SESSION['jabatan']) || ($_SESSION['jabatan'] != 'Pemimpin Cabang' && $_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi')) {
    header('Location: ../../index.php');
    exit();
}

$id_user = (int)(isset($_GET['id_user']) ? $_GET['id_user'] : ($_SESSION['id_user'] ?? 0));
$id_cabang = (int)(isset($_GET['id_cabang']) ? $_GET['id_cabang'] : ($_SESSION['id_cabang'] ?? 0));

$tahun = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan_long = get_nama_bulan();
$title = "Penilaian Kompetensi";

// --- 1. Ambil Profil User ---
$q_user = "SELECT u.*, c.nama_cabang 
           FROM users u 
           JOIN cabang c ON u.id_cabang = c.id_cabang
           WHERE u.id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

// --- Guard: Cek Profil Lengkap ---

// --- 2. Validasi Ketat Seksi A (KPI) ---
$q_stats = "SELECT 
    COUNT(i.id_indikator) as total_ind,
    COUNT(t.id_target) as total_target,
    COUNT(r.id_realisasi) as total_realisasi
FROM indikator i
LEFT JOIN target t ON i.id_indikator = t.id_indikator AND t.id_cabang = $id_cabang AND t.tahun = $tahun AND t.bulan = 12
LEFT JOIN realisasi r ON i.id_indikator = r.id_indikator AND r.id_cabang = $id_cabang AND r.tahun = $tahun AND r.bulan = $bulan
WHERE i.tahun = $tahun AND i.id_cabang = $id_cabang";
$res_stats = mysqli_query($koneksi, $q_stats);
$stats = mysqli_fetch_assoc($res_stats);

$total_indikator = (int)($stats['total_ind'] ?? 0);
$is_lengkap = ($total_indikator > 0);

// Cek Status Kesiapan KPI
$kpi_siap = ($total_indikator > 0 &&
    $stats['total_target'] == $total_indikator &&
    $stats['total_realisasi'] == $total_indikator);

// --- 3. Hitung Skor KPI (Live) ---
$nilai_kpi_a = 0;
if ($kpi_siap) {
    $q_calc = "SELECT i.id_indikator, i.nama_indikator, i.bobot, i.terbalik, i.satuan, r.realisasi
               FROM indikator i 
               JOIN realisasi r ON i.id_indikator = r.id_indikator AND r.id_cabang = $id_cabang AND r.tahun = $tahun AND r.bulan = $bulan
               WHERE i.tahun = $tahun AND i.id_cabang = $id_cabang";
    $res_calc = mysqli_query($koneksi, $q_calc);
    while ($row = mysqli_fetch_assoc($res_calc)) {
        // Kalkulasi target dinamis bulan berjalan
        $target_period = calculate_target_period($koneksi, (int)$row['id_indikator'], $id_cabang, $tahun, $bulan);
        $pencapaian = calculate_pencapaian($row['nama_indikator'], $target_period, (float)$row['realisasi'], (int)$row['terbalik'], (string)$row['satuan']);
        $skor = calculate_skor_indikator($koneksi, (int)$row['id_indikator'], (float)$pencapaian);
        $nilai_kpi_a += ($skor * ((float)$row['bobot'] / 100));
    }
}
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
        .competency-sheet {
            background: white;
            padding: 30px;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            font-size: 0.85em;
            color: #000;
        }

        .sheet-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .excel-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
        }

        .input-plain {
            width: 100%;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 1.1em;
            font-weight: bold;
            text-align: center;
            outline: none;
            padding: 8px 0;
        }

        .input-plain:focus {
            background: #fffde7;
        }

        .excel-box {
            background: #d9e1f2;
            border: 1px solid #000 !important;
            padding: 10px;
            min-width: 150px;
            position: relative;
            font-size: 1.4em;
            font-weight: bold;
            text-align: center;
        }

        .excel-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            border-style: solid;
            border-width: 8px 8px 0 0;
            border-color: #008000 transparent transparent transparent;
        }

        .label-excel {
            text-align: right;
            padding-right: 15px !important;
            font-weight: bold;
            font-size: 1.2em;
        }

        .label-jumlah {
            font-style: italic;
        }

        /* CSS Lokal yang benar-benar unik untuk halaman ini */
        .input-kontrol {
            padding: 8px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
        }

        .tombol {
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: bold;
        }

        .tombol-utama {
            background: #2b5797;
            color: white;
        }

        @media print {

            .no-print,
            .sidebar {
                display: none !important;
            }

            .competency-sheet {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                background: white;
            }
        }
    </style>
</head>

<body>

    <?php
    $is_iframe = isset($_GET['iframe']) && (int)$_GET['iframe'] == 1;
    $q_btn = "SELECT id_kompetensi FROM kompetensi WHERE id_user = $id_user AND tahun = $tahun AND bulan = $bulan LIMIT 1";
    $sudah_dinilai = (mysqli_num_rows(mysqli_query($koneksi, $q_btn)) > 0);
    ?>
    <div class="dashboard-container">
        <?php if (!$is_iframe) include "../komponen/sidebar.php"; ?>

        <div class="main-content" <?php if ($is_iframe) echo 'style="margin-left: 0 !important; width: 100% !important; padding: 10px !important;"'; ?>>
            <?php if (!$is_iframe): ?>
                <div class="halaman-header no-print">
                    <h1>Monitoring Penilaian Kompetensi</h1>
                    <p>Verifikasi penilaian Core & Leadership Competencies Cabang <?php echo $u['nama_cabang'] ?? '-'; ?>.</p>
                </div>

                <!-- FILTER NAVIGASI (SELALU NONGOL) -->
                <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <?php if ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi'): ?>
                            <a href="../<?= ($_SESSION['jabatan'] == 'Direksi') ? 'direksi' : 'divisi'; ?>/monitoring_cabang.php" class="tombol" style="background: #4a5568; color: white; padding: 8px 15px;">
                                <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
                            </a>
                        <?php endif; ?>

                        <div class="filter-periode-container">
                            <i class="fas fa-calendar-check"></i>
                            <input type="month" id="periodePicker" class="input-month"
                                value="<?php echo $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT); ?>"
                                onchange="updateNav()">
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <?php
                        // Cek ketersediaan data untuk tombol PDF
                        $q_btn = "SELECT id_kompetensi FROM kompetensi WHERE id_user = $id_user AND tahun = $tahun AND bulan = $bulan LIMIT 1";
                        $sudah_dinilai = (mysqli_num_rows(mysqli_query($koneksi, $q_btn)) > 0);
                        ?>

                        <?php if ($is_lengkap && $sudah_dinilai): ?>
                            <button onclick="cetakPDF()" class="tombol" style="background: #edf2f7; color: #4a5568;"><i class="fas fa-print"></i> Cetak</button>
                        <?php endif; ?>

                        <?php if ($is_lengkap && $kpi_siap && $_SESSION['jabatan'] == 'Pemimpin Cabang'): ?>
                            <button type="submit" form="formKompetensi" class="tombol tombol-utama"><i class="fas fa-save"></i> Simpan Penilaian</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="kartu" style="background: #e6fffa; border: 1px solid #b2f5ea; color: #234e52; margin: 0 0 20px 0; padding: 15px;">
                    <i class="fas fa-circle-check"></i> <strong>Berhasil!</strong> Penilaian kompetensi periode <?php echo $nama_bulan_long[$bulan] . " " . $tahun; ?> berhasil disimpan.
                </div>
            <?php endif; ?>

            <?php if (!$is_lengkap || !$kpi_siap || (!$sudah_dinilai && ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi'))): ?>
                <div class="kartu" style="text-align: center; padding: 80px 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 15px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <?php if (!$sudah_dinilai && ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi')): ?>
                        <i class="fas fa-hourglass-half" style="font-size: 4em; color: #ecc94b; margin-bottom: 20px;"></i>
                        <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Menunggu Penilaian Pinca</h3>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto;">
                            Pemimpin Cabang belum melakukan pengisian <strong>Self-Assessment Kompetensi</strong> untuk periode <strong><?php echo $nama_bulan_long[$bulan] . ' ' . $tahun; ?></strong>.
                        </p>
                    <?php elseif (!$kpi_siap && $_SESSION['jabatan'] == 'Pemimpin Cabang'): ?>
                        <i class="fas fa-lock" style="font-size: 4em; color: #a0aec0; margin-bottom: 20px;"></i>
                        <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Akses Terkunci</h3>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto; margin-bottom: 25px;">
                            Anda belum bisa mengisi kompetensi karena data <strong>KPI (Seksi A)</strong> belum lengkap (Target atau Realisasi ada yang kosong).
                        </p>
                        <a href="formulir_penilaian.php" class="tombol" style="background: #4a5568; color: white; padding: 12px 25px; display: inline-flex; align-items: center; gap: 10px;">
                            <i class="fas fa-eye"></i> Cek Kelengkapan KPI
                        </a>
                    <?php else: ?>
                        <i class="fas fa-folder-open" style="font-size: 4em; color: #a0aec0; margin-bottom: 20px;"></i>
                        <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Data Belum Tersedia</h3>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto;">Data penilaian kompetensi untuk periode <?php echo $nama_bulan_long[$bulan] . ' ' . $tahun; ?> belum tersedia.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- 3. TAMPILAN UTAMA: FORM PENILAIAN (PINCA NGISI / BKU LIAT HASIL) -->
                <div class="competency-sheet">
                    <form id="formKompetensi" action="../../fungsi/kpi_manajemen_penilaian.php" method="POST">
                        <input type="hidden" name="aksi_utama" value="simpan_kompetensi">
                        <input type="hidden" name="id_user" value="<?php echo $id_user; ?>">
                        <input type="hidden" name="id_cabang" value="<?php echo $id_cabang; ?>">
                        <input type="hidden" name="tahun" value="<?php echo $tahun; ?>">
                        <input type="hidden" name="bulan" value="<?php echo $bulan; ?>">

                        <?php
                        $is_web = true;
                        include __DIR__ . "/templates/tpl_kompetensi.php";
                        echo $html_k;
                        ?>

                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="../../aset/js/app.js"></script>
    <script>
        function cetakPDF() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const parts = val.split('-');
            const thn = parts[0];
            const bln = parseInt(parts[1]);
            const urlParams = new URLSearchParams(window.location.search);
            const idUser = urlParams.get('id_user') || '';
            const idCabang = urlParams.get('id_cabang') || '';

            let url = `cetak/cetak_browser.php?mod=k&filter_tahun=${thn}&filter_bulan=${bln}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            printViaIframe(url);
        }

        function updateNav() {
            const val = document.getElementById('periodePicker').value;
            if (!val) return;
            const parts = val.split('-');
            const thn = parts[0];
            const bln = parseInt(parts[1]);
            const urlParams = new URLSearchParams(window.location.search);
            const idUser = urlParams.get('id_user') || '';
            const idCabang = urlParams.get('id_cabang') || '';

            let url = `penilaian_kompetensi.php?filter_tahun=${thn}&filter_bulan=${bln}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            window.location.href = url;
        }

        function printViaIframe(url) {
            let frame = document.getElementById('printFrame');
            if (!frame) {
                frame = document.createElement('iframe');
                frame.id = 'printFrame';
                frame.style.position = 'fixed';
                frame.style.right = '100%';
                frame.style.bottom = '100%';
                frame.style.width = '0';
                frame.style.height = '0';
                frame.style.border = 'none';
                document.body.appendChild(frame);
            }
            frame.onload = function() {
                setTimeout(function() {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                }, 200);
            };
            frame.src = url;
        }

        // Validasi Simpan
        const formK = document.getElementById('formKompetensi');
        if (formK) {
            formK.addEventListener('submit', function(e) {
                let inputs = document.querySelectorAll('.input-skor');
                let lengkap = true;
                inputs.forEach(input => {
                    if (input.value === "") lengkap = false;
                });
                if (!lengkap) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Data Belum Lengkap',
                        text: 'Harap isi seluruh 10 poin kompetensi sebelum menyimpan.',
                        confirmButtonColor: '#d33'
                    });
                }
            });

            formK.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.classList.contains('input-skor')) {
                    e.preventDefault();
                    const inputs = Array.from(document.querySelectorAll('.input-skor'));
                    const currentIndex = inputs.indexOf(e.target);
                    if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                        inputs[currentIndex + 1].focus();
                        inputs[currentIndex + 1].select();
                    }
                }
            });
        }
    </script>

</body>

</html>