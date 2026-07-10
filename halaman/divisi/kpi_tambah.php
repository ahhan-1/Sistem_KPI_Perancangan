<?php
session_start();
require_once "../../fungsi/koneksi.php";

if (!isset($_SESSION['jabatan']) || $_SESSION['jabatan'] != 'Divisi') {
    header('Location: ../../index.php');
    exit();
}

$title = "Manajemen KPI Tahunan";

// --- 1. Filter Utama ---
$tahun_aktif = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (int)date('Y');

/** @var mysqli $koneksi */
global $koneksi;

// --- 2. Ambil Data Perspektif ---
$res_perspektif = mysqli_query($koneksi, "SELECT * FROM perspektif");
$perspektif_data = [];
while ($p = mysqli_fetch_assoc($res_perspektif)) {
    $perspektif_data[] = $p;
}

// --- 3. Cek Data Tersimpan ---
$q_list = "SELECT i.id_indikator FROM indikator i WHERE i.tahun = $tahun_aktif LIMIT 1";
$res_list = mysqli_query($koneksi, $q_list);

// --- 4. Ambil Sasaran Strategis ---
$sasaran_list = [];
$q_s = "SELECT * FROM sasaran_strategis WHERE tahun = $tahun_aktif";
$res_s = mysqli_query($koneksi, $q_s);
while ($s = mysqli_fetch_assoc($res_s)) {
    $sasaran_list[] = $s;
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
        /* CSS Modal Sederhana ala Mahasiswa yang Lebih Rapi */
        .modal-sederhana {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
        }

        /* Paksa SweetAlert selalu di paling depan */
        .swal2-container {
            z-index: 9999 !important;
        }

        .modal-konten {
            background-color: #fff;
            margin: 2% auto;
            /* Ubah margin agar lebih naik */
            padding: 0;
            border-radius: 12px;
            width: 550px;
            max-height: 90vh;
            /* Batas tinggi maksimal */
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: none;
        }

        .modal-header {
            background: var(--biru-utama);
            color: white;
            padding: 15px 20px;
            /* Dipersempit */
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.1em;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            /* Aktifkan scroll di sini */
            flex: 1;
        }

        .modal-footer {
            padding: 12px 20px;
            border-top: 1px solid #eee;
            text-align: right;
            background: #fafafa;
            flex-shrink: 0;
        }

        .skala-input-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            /* Lebih rapat */
            gap: 15px;
            padding: 8px 12px;
            /* Lebih rapat */
            border-radius: 8px;
            border: 1px solid #f0f0f0;
            transition: 0.2s;
        }

        .skala-input-row:hover {
            background: #f9f9f9;
        }

        .skala-label {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            flex-shrink: 0;
        }

        /* Pewarnaan Skor */
        .s-5 {
            background: #28a745;
        }

        .s-4 {
            background: #1e88e5;
        }

        .s-3 {
            background: #ffa000;
        }

        .s-2 {
            background: #f4511e;
        }

        .s-1 {
            background: #d32f2f;
        }

        .input-group-custom {
            flex: 1;
        }

        .input-group-custom label {
            display: block;
            font-size: 0.8em;
            color: #888;
            margin-bottom: 2px;
        }

        .inverse-box {
            background: #fff5f5;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #feb2b2;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .badge-siap {
            background: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: 600;
        }

        .badge-belum {
            background: #ffe082;
            color: #856404;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="dashboard-container">
        <?php include "../komponen/sidebar.php"; ?>

        <div class="main-content">
            <div class="halaman-header">
                <h1>Manajemen KPI Tahunan</h1>
                <p>Tentukan Struktur KPI dan Skala Penilaian sebelum menghasilkan Kontrak Kerja.</p>
            </div>

            <div class="kartu">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 class="teks-utama" style="margin: 0;">
                        <i class="fas fa-list-check"></i> Konfigurasi Indikator & Skala
                    </h3>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="kurangTahun()" title="Kurangi Tahun" style="background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fas fa-minus" style="font-size: 12px;"></i>
                        </button>
                        <div class="filter-periode-container no-chevron">
                            <i class="fas fa-calendar-check"></i>
                            <select id="tahunSelect" class="input-year-select" onchange="gantiTahunFilter(this.value)">
                                <!-- Akan diisi oleh JS -->
                            </select>
                        </div>
                        <button type="button" onclick="tambahTahun()" title="Tambah Tahun" style="background: var(--biru-utama); color: white; border: none; border-radius: 8px; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fas fa-plus" style="font-size: 12px; color: white;"></i>
                        </button>
                    </div>

                    <script>
                        const tahunAktif = <?php echo $tahun_aktif; ?>;
                        const selectTahun = document.getElementById('tahunSelect');

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

                        function tambahTahun() {
                            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
                            if (maxYear < 2026) maxYear = 2026;
                            maxYear++;
                            localStorage.setItem('max_year_kpi', maxYear);
                            renderTahun();

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Tahun ditambahkan',
                                    text: 'Tahun ' + maxYear + ' ditambahkan ke daftar',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            }
                        }

                        function kurangTahun() {
                            let maxYear = parseInt(localStorage.getItem('max_year_kpi')) || 2029;
                            if (maxYear > 2026) {
                                maxYear--;
                                localStorage.setItem('max_year_kpi', maxYear);
                                renderTahun();
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Tahun Dikurangi',
                                        text: 'Tahun ' + (maxYear + 1) + ' berhasil dihapus dari daftar.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                }
                            } else {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Batas Minimum',
                                        text: 'Tahun tidak boleh kurang dari 2026.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                }
                            }
                        }

                        renderTahun();
                    </script>
                </div>

                <?php if (mysqli_num_rows($res_list) > 0): ?>
                    <div style="text-align: center; padding: 50px 20px;">
                        <i class="fas fa-lock" style="font-size: 4em; color: #ffc107; margin-bottom: 20px;"></i>
                        <h2 style="color: #333;">Data Tahun <?php echo $tahun_aktif; ?> Sudah Ada</h2>
                        <p style="color: #666; margin-bottom: 30px;">
                            Struktur KPI untuk tahun ini sudah dibuat. Silakan kelola di halaman Daftar KPI.
                        </p>
                        <a href="kpi_daftar.php?filter_tahun=<?php echo $tahun_aktif; ?>" class="tombol tombol-utama">
                            <i class="fas fa-external-link-alt"></i> Ke Daftar KPI
                        </a>
                    </div>
                <?php else: ?>
                    <form action="../../fungsi/kpi_manajemen_indikator.php" method="POST" id="formKontrak">
                        <input type="hidden" name="aksi" value="simpan">
                        <input type="hidden" name="tahun_kontrak" value="<?php echo $tahun_aktif; ?>">
                        <table class="tabel-kpi" id="tabelInput">
                            <thead>
                                <tr>
                                    <th width="15%">Perspektif</th>
                                    <th width="20%">Sasaran Strategis</th>
                                    <th width="15%">Sub-Perspektif</th>
                                    <th width="20%">Indikator</th>
                                    <th width="10%">Satuan</th>
                                    <th width="8%">Bobot (%)</th>
                                    <th width="12%" style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="barisKontrak">
                                <tr class="baris">
                                    <td>
                                        <select name="perspektif[]" class="input-kontrol sel-perspektif" required onchange="updateSasaranOptions(this)">
                                            <option value="">Pilih...</option>
                                            <?php foreach ($perspektif_data as $pd): ?>
                                                <option value="<?php echo $pd['id_perspektif']; ?>"><?php echo $pd['nama_perspektif']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="sasaran_strategis[]" class="input-kontrol" placeholder="Input Sasaran Strategis..." required>
                                    </td>
                                    <td><input type="text" name="subperspektif[]" class="input-kontrol" placeholder="Sub"></td>
                                    <td><input type="text" name="indikator[]" class="input-kontrol" placeholder="Indikator" required></td>
                                    <td>
                                        <select name="satuan[]" class="input-kontrol" required>
                                            <option value="Rupiah">Rp</option>
                                            <option value="Score">Score</option>
                                            <option value="Persen">%</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-bobot-container">
                                            <input type="number" name="bobot[]" class="input-kontrol hitung-bobot" step="0.01" placeholder="0" required>
                                            <span class="persen-suffix">%</span>
                                        </div>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <input type="hidden" name="skala_data[]" class="data-skala-hidden">
                                        <div style="display: flex; gap: 5px; justify-content: center;">
                                            <button type="button" class="tombol bg-cyan" onclick="bukaModalSkala(this)" title="Atur Skala" style="padding: 6px 10px;">
                                                <i class="fas fa-sliders"></i>
                                            </button>
                                            <button type="button" class="tombol" style="color: #ff4d4d; background: #fff5f5; border: 1px solid #ffabaf; padding: 6px 10px;" onclick="hapusBaris(this)" title="Hapus Baris">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </div>
                                        <div class="status-skala" style="margin-top: 4px; font-size: 0.7em;">
                                            <span class="badge-belum">Skala: Belum</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div style="margin-top: 20px; margin-bottom: 120px;">
                            <button type="button" class="tombol bg-cyan" onclick="tambahBaris()">
                                <i class="fas fa-plus"></i> Tambah Indikator
                            </button>
                        </div>

                        <div class="sticky-bobot">
                            <div class="bobot-info">
                                <div class="info-teks">
                                    <strong>Akumulasi Bobot: </strong>
                                    <span id="labelTotalBobot">0%</span>
                                </div>
                                <div class="progress-container">
                                    <div id="progressBar" class="progress-bar"></div>
                                </div>
                            </div>
                            <div class="bobot-aksi">
                                <span id="statusPesan" style="margin-right: 20px; font-weight: 600;"></span>
                                <button type="submit" id="btnSimpan" class="tombol btn-simpan-kpi" onclick="validasiSubmit(event)">
                                    <i class="fas fa-save"></i> Simpan Kinerja PA
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="modalSkala" class="modal-sederhana">
        <div class="modal-konten">
            <div class="modal-header">
                <h3 id="namaIndikatorModal">Atur Skala Nilai</h3>
                <span style="cursor:pointer; font-size: 1.5em;" onclick="tutupModalSkala()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="inverse-box">
                    <div>
                        <strong style="color: #c53030;">Indikator Terbalik?</strong>
                        <p style="margin: 2px 0 0; font-size: 0.75em; color: #742a2a;">Centang jika semakin kecil angka semakin baik (misal: Fraud).</p>
                    </div>
                    <input type="checkbox" id="modalInverse" style="width: 20px; height: 20px; cursor: pointer;">
                </div>

                <div id="skalaInputs">
                    <?php
                    $skors = [
                        5 => ['label' => 'Istimewa', 'class' => 's-5'],
                        4 => ['label' => 'Melampaui Target', 'class' => 's-4'],
                        3 => ['label' => 'Sesuai Target', 'class' => 's-3'],
                        2 => ['label' => 'Di Bawah Target', 'class' => 's-2'],
                        1 => ['label' => 'Tidak Tercapai', 'class' => 's-1']
                    ];
                    foreach ($skors as $s => $info):
                    ?>
                        <div class="skala-input-row">
                            <div class="skala-label <?php echo $info['class']; ?>"><?php echo $s; ?></div>
                            <div class="input-group-custom">
                                <label>Rating Minimum (%) untuk Skor <?php echo $s; ?> (<?php echo $info['label']; ?>)</label>
                                <div style="position: relative;">
                                    <input type="number" id="t_<?php echo $s; ?>" step="0.01" class="input-kontrol" placeholder="0.00" required>
                                    <span style="position: absolute; right: 15px; top: 10px; color: #aaa;">%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tombol" style="background: #eee;" onclick="tutupModalSkala()">Batal</button>
                <button type="button" class="tombol-utama tombol" onclick="simpanSkalaModal()">Terapkan Konfigurasi</button>
            </div>
        </div>
    </div>

    <script>
        let barisAktif = null;

        function bukaModalSkala(btn) {
            barisAktif = btn.closest('tr');
            const namaInd = barisAktif.querySelector('input[name="indikator[]"]').value || "Indikator Baru";
            document.getElementById('namaIndikatorModal').innerText = "Skala: " + namaInd;

            // Reset/Load existing data
            const existing = barisAktif.querySelector('.data-skala-hidden').value;
            if (existing) {
                const d = JSON.parse(existing);
                document.getElementById('modalInverse').checked = d.terbalik == 1;
                for (let i = 1; i <= 5; i++) {
                    document.getElementById('t_' + i).value = d.nilai_minimums[i];
                }
            } else {
                document.getElementById('modalInverse').checked = false;
                for (let i = 1; i <= 5; i++) document.getElementById('t_' + i).value = '';
            }

            document.getElementById('modalSkala').style.display = 'block';
        }

        function tutupModalSkala() {
            document.getElementById('modalSkala').style.display = 'none';
        }

        function simpanSkalaModal() {
            const data = {
                terbalik: document.getElementById('modalInverse').checked ? 1 : 0,
                nilai_minimums: {}
            };

            let filledCount = 0;
            for (let i = 1; i <= 5; i++) {
                const val = document.getElementById('t_' + i).value;
                if (val !== '') {
                    data.nilai_minimums[i] = val;
                    filledCount++;
                }
            }

            if (filledCount === 0) {
                alert("Harap isi minimal satu ambang batas skala!");
                return;
            }

            barisAktif.querySelector('.data-skala-hidden').value = JSON.stringify(data);
            barisAktif.querySelector('.status-skala').innerHTML = '<span class="badge-siap"><i class="fas fa-check"></i> Siap</span>';
            tutupModalSkala();
            hitungTotal();
        }

        function tambahBaris() {
            const tbody = document.getElementById('barisKontrak');
            const firstRow = tbody.querySelector('.baris');
            const newRow = firstRow.cloneNode(true);

            // Reset values
            newRow.querySelectorAll('input').forEach(i => {
                if (i.type !== 'hidden') i.value = '';
                else if (i.classList.contains('data-skala-hidden')) i.value = '';
            });
            newRow.querySelectorAll('select').forEach(s => s.selectedIndex = 0);

            // Reset status skala
            newRow.querySelector('.status-skala').innerHTML = '<span class="badge-belum">Skala: Belum</span>';

            tbody.appendChild(newRow);
            attachListeners();
            setupEnterNavigation();
        }

        function hapusBaris(btn) {
            if (document.querySelectorAll('.baris').length > 1) {
                btn.closest('tr').remove();
                hitungTotal();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Hapus',
                    text: 'Minimal satu indikator wajib ada.'
                });
            }
        }

        function hitungTotal() {
            const inputs = document.querySelectorAll('.hitung-bobot');
            const selects = document.querySelectorAll('select[name="perspektif[]"]');
            const hiddenSkala = document.querySelectorAll('.data-skala-hidden');
            const sasarans = document.querySelectorAll('input[name="sasaran_strategis[]"]');

            let total = 0;
            const pUnique = new Set();
            let skalaLengkap = true;
            let sasaransLengkap = true;

            inputs.forEach(input => total += parseFloat(input.value) || 0);
            selects.forEach(s => {
                if (s.value) pUnique.add(s.value);
            });
            hiddenSkala.forEach(h => {
                if (!h.value) skalaLengkap = false;
            });
            sasarans.forEach(s => {
                if (!s.value.trim()) sasaransLengkap = false;
            });

            // Bulatkan total untuk menghindari masalah floating point
            total = parseFloat(total.toFixed(2));

            const label = document.getElementById('labelTotalBobot');
            const progressBar = document.getElementById('progressBar');
            const btn = document.getElementById('btnSimpan');
            const status = document.getElementById('statusPesan');

            label.innerText = total.toFixed(2) + "%";
            progressBar.style.width = Math.min(total, 100) + "%";

            const siap = (total === 100 && pUnique.size === 4 && skalaLengkap && sasaransLengkap);

            if (siap) {
                progressBar.style.backgroundColor = "#28a745";
                status.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check-circle"></i> Data Lengkap & Siap Simpan</span>';
                btn.classList.add('ready');
            } else {
                progressBar.style.backgroundColor = "#ffc107";
                status.innerHTML = '<span style="color: #856404;"><i class="fas fa-circle-exclamation"></i> Lengkapi data untuk simpan</span>';
                btn.classList.remove('ready');
            }
        }

        function validasiSubmit(e) {
            hitungTotal();
            const btn = document.getElementById('btnSimpan');
            if (!btn.classList.contains('ready')) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum Lengkap!',
                    text: 'Harap lengkapi semua data sebelum menyimpan.',
                    confirmButtonColor: '#0245A3'
                });
            }
        }

        function attachListeners() {
            document.querySelectorAll('.hitung-bobot').forEach(i => i.addEventListener('input', hitungTotal));
        }

        function gantiTahunFilter(tahun) {
            window.location.href = "kpi_tambah.php?filter_tahun=" + tahun;
        }

        attachListeners();

        // --- 5. Navigasi Tombol Enter ---
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeEl = document.activeElement;
                const form = document.getElementById('formKontrak');

                // Pastikan kita berada di dalam form manajemen KPI
                if (form && form.contains(activeEl)) {
                    // Cek apakah element yang fokus adalah input atau select
                    if (activeEl.tagName === 'INPUT' || activeEl.tagName === 'SELECT') {
                        e.preventDefault(); // Cegah form submit otomatis

                        const currentRow = activeEl.closest('tr');
                        if (!currentRow || !currentRow.classList.contains('baris')) return;

                        // Cari indeks kolom saat ini
                        const inputsInRow = Array.from(currentRow.querySelectorAll('.input-kontrol, .hitung-bobot'));
                        const colIndex = inputsInRow.indexOf(activeEl);

                        const nextRow = currentRow.nextElementSibling;
                        if (nextRow && nextRow.classList.contains('baris')) {
                            // Fokus ke kolom yang sama di baris bawah
                            const targetInputs = nextRow.querySelectorAll('.input-kontrol, .hitung-bobot');
                            if (targetInputs[colIndex]) {
                                targetInputs[colIndex].focus();
                                if (targetInputs[colIndex].tagName === 'INPUT') targetInputs[colIndex].select();
                            }
                        } else {
                            // Jika sudah di baris terakhir, otomatis tambah baris baru
                            tambahBaris();
                            // Beri sedikit jeda untuk render DOM baris baru
                            setTimeout(() => {
                                const tbody = document.getElementById('barisKontrak');
                                const newRow = tbody.lastElementChild;
                                const targetInputs = newRow.querySelectorAll('.input-kontrol, .hitung-bobot');
                                if (targetInputs[colIndex]) {
                                    targetInputs[colIndex].focus();
                                    if (targetInputs[colIndex].tagName === 'INPUT') targetInputs[colIndex].select();
                                }
                            }, 50);
                        }
                    }
                }
            }
        });

        <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: 'Struktur KPI Tahunan telah berhasil dibuat.',
                confirmButtonColor: '#0245A3'
            });
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                window.history.replaceState({
                    path: url.href
                }, '', url.href);
            }
        <?php endif; ?>
    </script>

</body>

</html>