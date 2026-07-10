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

// --- 1. Filter Utama ---
$tahun_aktif = (isset($_GET['filter_tahun']) && $_GET['filter_tahun'] != 0) ? (int)$_GET['filter_tahun'] : (int)date('Y');
$bulan_pilih = (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] != 0) ? (int)$_GET['filter_bulan'] : (int)date('m');

$nama_bulan_long = get_nama_bulan();
$label_periode = ($bulan_pilih == 1)
    ? $nama_bulan_long[$bulan_pilih] . " " . $tahun_aktif
    : "Januari - " . $nama_bulan_long[$bulan_pilih] . " " . $tahun_aktif;

// --- 2. Ambil Profil User Detail ---
$q_user = "SELECT u.*, c.nama_cabang 
           FROM users u 
           JOIN cabang c ON u.id_cabang = c.id_cabang
           WHERE u.id_user = $id_user";
$res_user = mysqli_query($koneksi, $q_user);
$u = mysqli_fetch_assoc($res_user);

// --- Guard: Cek Profil Lengkap ---

// --- 3. Ambil Data KPI ---
$q_data = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, t.nilai_target as target, 
           (SELECT realisasi FROM realisasi 
            WHERE id_indikator = i.id_indikator 
            AND id_cabang = $id_cabang 
            AND tahun = $tahun_aktif 
            AND bulan = $bulan_pilih) as total_realisasi,
           (SELECT COUNT(*) FROM realisasi 
            WHERE id_indikator = i.id_indikator 
            AND id_cabang = $id_cabang 
            AND tahun = $tahun_aktif 
            AND bulan = $bulan_pilih) as cek_realisasi_bulan_ini
           FROM indikator i
           JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
           JOIN perspektif p ON s.id_perspektif = p.id_perspektif
           LEFT JOIN target t ON i.id_indikator = t.id_indikator 
                AND t.id_cabang = $id_cabang 
                AND t.tahun = $tahun_aktif 
                AND t.bulan = 12
           WHERE i.tahun = $tahun_aktif AND i.id_cabang = $id_cabang
           ORDER BY p.id_perspektif ASC, s.id_subperspektif ASC, i.id_indikator ASC";

$res_data = mysqli_query($koneksi, $q_data);
$grouped_data = [];
$is_lengkap = true;
$data_siap = true; // New check for Target & Realization
$total_kpi = 0;
while ($row = mysqli_fetch_assoc($res_data)) {
    $grouped_data[$row['nama_subperspektif']][] = $row;
    $total_kpi++;
    // Jika ada target yang NULL atau realisasi bulan terpilih belum diinput
    if (is_null($row['target']) || $row['cek_realisasi_bulan_ini'] == 0) {
        $data_siap = false;
    }
}
if ($total_kpi == 0) {
    $is_lengkap = false;
    $data_siap = false;
}

// --- 4. Ambil Existing ID Nilai ---
$id_nilai_saved = 0;
$q_cek = "SELECT id_nilai FROM nilai_akhir WHERE id_user = $id_user AND bulan = $bulan_pilih AND tahun = $tahun_aktif";
$res_cek = mysqli_query($koneksi, $q_cek);
if ($row_cek = mysqli_fetch_assoc($res_cek)) {
    $id_nilai_saved = $row_cek['id_nilai'];
}

// --- 5. Ambil Seksi B & C ---
$data_b = [];
if ($id_nilai_saved) {
    $res_b = mysqli_query($koneksi, "SELECT * FROM nilai_tambahan WHERE id_nilai = $id_nilai_saved");
    while ($rb = mysqli_fetch_assoc($res_b)) $data_b[] = $rb;
}

$data_c = [];
if ($id_nilai_saved) {
    $res_c = mysqli_query($koneksi, "SELECT * FROM nilai_pengurang WHERE id_nilai = $id_nilai_saved");
    while ($rc = mysqli_fetch_assoc($res_c)) $data_c[$rc['jenis']] = $rc;
}

$grand_total_a = 0; // Initialize early for JS
$title = "Formulir Penilaian Kinerja Karyawan";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../aset/css/gaya.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .input-kontrol {
            padding: 8px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
        }

        .printable-sheet {
            background: white;
            padding: 40px;
            width: 100%;
            margin: 20px 0;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            color: #000;
        }

        .btn-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        @media print {

            .no-print,
            .no-print-btn,
            .sidebar {
                display: none !important;
            }

            .main-content {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>

<body>

    <?php $is_iframe = isset($_GET['iframe']) && (int)$_GET['iframe'] == 1; ?>
    <div class="dashboard-container">
        <?php if (!$is_iframe) include "../komponen/sidebar.php"; ?>

        <div class="main-content" <?php if ($is_iframe) echo 'style="margin-left: 0 !important; width: 100% !important; padding: 10px !important;"'; ?>>
            <?php if (!$is_iframe): ?>
                <div class="halaman-header no-print">
                    <h1>Formulir Penilaian Kinerja</h1>
                    <p>Evaluasi capaian KPI untuk Cabang <?php echo $u['nama_cabang'] ?? '-'; ?> periode terpilih.</p>
                </div>

                <div class="btn-action no-print">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <?php if ($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi'): ?>
                            <a href="../<?= ($_SESSION['jabatan'] == 'Direksi') ? 'direksi' : 'divisi'; ?>/monitoring_cabang.php" class="tombol" style="background: #4a5568; color: white; padding: 8px 15px;">
                                <i class="fas fa-arrow-left"></i> Kembali ke Monitoring
                            </a>
                        <?php endif; ?>

                        <div class="filter-periode-container">
                            <i class="fas fa-calendar-check"></i>
                            <input type="month" id="periodePicker" class="input-month"
                                value="<?php echo $tahun_aktif . '-' . str_pad($bulan_pilih, 2, '0', STR_PAD_LEFT); ?>"
                                onchange="updateNav()">
                        </div>
                    </div>
                    <div style="flex-shrink: 0; display: flex; gap: 10px;">
                        <?php if ($is_lengkap && $data_siap): ?>
                            <button onclick="cetakPDF()" type="button" class="tombol" style="background: #edf2f7; color: #4a5568;"><i class="fas fa-print"></i> Cetak</button>
                            <?php if ($_SESSION['jabatan'] == 'Pemimpin Cabang'): ?>
                                <button type="submit" form="mainAppraisalForm" class="tombol tombol-utama"><i class="fas fa-save"></i> Simpan Penilaian</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="kartu" style="background: #e6fffa; border: 1px solid #b2f5ea; color: #234e52; margin-bottom: 20px; padding: 15px; width: 100%;">
                    <i class="fas fa-circle-check"></i> <strong>Berhasil!</strong> Data penilaian formulir periode <?php echo $label_periode; ?> telah disimpan ke database.
                </div>
            <?php endif; ?>

            <?php if ($is_lengkap && $data_siap) { ?>
                <!-- BKU & PINCA BISA LIAT FORM ASAL DATA TARGET & REALISASI ADA -->
                <div class="printable-sheet">
                    <form id="mainAppraisalForm" action="../../fungsi/kpi_manajemen_penilaian.php" method="POST">
                        <input type="hidden" name="aksi_utama" value="simpan_formulir">
                        <input type="hidden" name="tahun" value="<?php echo $tahun_aktif; ?>">
                        <input type="hidden" name="bulan" value="<?php echo $bulan_pilih; ?>">

                        <?php
                        // Mapping variabel agar sesuai dengan yang diminta template
                        $tahun = $tahun_aktif;
                        $bulan = $bulan_pilih;
                        $id_nilai = $id_nilai_saved;
                        $is_web = true;
                        $cabang_display = ucwords(strtolower($u['nama_cabang'] ?? '-'));

                        // Gunakan path absolut biar pasti ketemu
                        include __DIR__ . "/templates/tpl_formulir.php";
                        echo $html_f;
                        ?>
                    </form>
                </div>
            <?php } else { ?>
                <div class="kartu" style="text-align: center; padding: 80px 20px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 15px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <i class="fas fa-file-circle-question" style="font-size: 4em; color: #a0aec0; margin-bottom: 20px;"></i>
                    <h3 style="color: #4a5568; font-size: 1.5em; margin-bottom: 10px;">Formulir Penilaian Belum Tersedia</h3>

                    <?php if ($_SESSION['jabatan'] == 'Divisi'): ?>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto; margin-bottom: 25px;">
                            Data target atau realisasi untuk periode <strong><?php echo $label_periode; ?></strong> belum di-input secara lengkap.
                        </p>
                        <a href="../divisi/realisasi_cabang.php" target="_parent" class="tombol tombol-utama" style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 25px; background: #c05621;">
                            <i class="fas fa-edit"></i> Input Realisasi Sekarang
                        </a>
                    <?php else: ?>
                        <p style="color: #718096; font-size: 1.1em; max-width: 500px; margin: 0 auto;">
                            Data target atau realisasi untuk periode <?php echo $label_periode; ?> belum di-input secara lengkap oleh Divisi BKU. Silakan hubungi Divisi terkait.
                        </p>
                    <?php endif; ?>
                </div>
            <?php } ?>
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
            const idUser = urlParams.get('id_user');
            const idCabang = urlParams.get('id_cabang');

            let url = `cetak/cetak_browser.php?mod=f&filter_tahun=${thn}&filter_bulan=${bln}`;
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
            const idUser = urlParams.get('id_user');
            const idCabang = urlParams.get('id_cabang');

            let url = `formulir_penilaian.php?filter_tahun=${thn}&filter_bulan=${bln}`;
            if (idUser) url += `&id_user=${idUser}`;
            if (idCabang) url += `&id_cabang=${idCabang}`;

            window.location.href = url;
        }

        function calcTotal() {
            // Total B (Merged Input)
            const inputB = document.getElementById('b_nilai_total');
            let totalB = 0;
            if (inputB) {
                totalB = parseFloat(inputB.value) || 0;
                if (totalB > 0.25) {
                    totalB = 0.25;
                    inputB.value = 0.25;
                    inputB.style.color = 'red';
                    inputB.style.fontWeight = 'bold';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Batas Maksimal',
                            text: 'Nilai tambahan (Seksi B) maksimal adalah 0.25',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    inputB.style.color = 'inherit';
                    inputB.style.fontWeight = 'bold';
                }
            }

            // Total C
            let totalC = 0;
            const rowsC = document.querySelectorAll('.val-c-n');
            const displayC = document.querySelectorAll('.val-c-res');
            if (rowsC.length > 0) {
                rowsC.forEach((input, index) => {
                    let n = parseInt(input.value) || 0;
                    let base = parseFloat(input.getAttribute('data-val'));
                    let res = n * base;
                    if (displayC[index]) displayC[index].innerText = res.toLocaleString('id-ID', {
                        minimumFractionDigits: 2
                    });
                    totalC += res;
                });
                const totalCElem = document.getElementById('totalC');
                if (totalCElem) totalCElem.innerText = totalC.toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                });
            }

            // Final
            let scoreA = parseFloat("<?php echo $grand_total_a; ?>") || 0;
            const totalABox = document.getElementById('totalA_box');
            if (totalABox) totalABox.innerText = scoreA.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            let final = scoreA + totalB + totalC;
            const finalScoreElem = document.getElementById('finalScore');
            if (finalScoreElem) finalScoreElem.innerText = final.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Init on load
        window.onload = calcTotal;
    </script>

</body>

</html>