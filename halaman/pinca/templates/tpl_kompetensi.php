<?php
/**
 * TEMPLATE: KOMPETENSI
 * @var mysqli $koneksi
 * @var int $id_user
 * @var int $tahun
 * @var int $bulan
 */

$saved_k = []; $res_k = mysqli_query($koneksi, "SELECT kd.nama_subkompetensi, kd.nilai_subkompetensi FROM kompetensi k JOIN kompetensi_rincian kd ON k.id_kompetensi = kd.id_kompetensi WHERE k.id_user = $id_user AND k.tahun = $tahun AND k.bulan = $bulan");
while($rk = mysqli_fetch_assoc($res_k)) $saved_k[$rk['nama_subkompetensi']] = $rk['nilai_subkompetensi'];

$komp_data = [
    'Core Competency' => [
        ['no' => 1, 'nama' => 'Profesional (Professionalism)', 'levels' => [
            1 => 'Butuh dorongan dan arahan detail untuk melaksanakan tugas dan tanggung jawab sesuai dengan lingkup tugas.',
            2 => 'Melaksanakan tugas dan tanggung jawab sesuai dengan lingkup tugas dan berdasarkan peraturan yang ada.',
            3 => 'Mengambil keputusan untuk penyelesaian masalah yang umum terjadi sesuai lingkup pekerjaannya dan bertanggung jawab atas konsekuensi yang mengikutinya.',
            4 => 'Mendorong orang lain dalam melaksanakan komitmen, kesepakatan maupun keputusan yang telah disepakati bersama.',
            5 => 'Membangun komitmen dan kesepakatan bersama dari berbagai pihak yang berkepentingan untuk menjalankan keputusan yang telah disepakati bersama.'
        ]],
        ['no' => 2, 'nama' => 'Integritas (Integrity)', 'levels' => [
            1 => 'Butuh bimbingan dan pengawasan atasan dalam menjalankan perilaku yang sesuai dengan standar etika bisnis maupun etika profesi.',
            2 => 'Menjadi sumber yang terpercaya terkait dengan kegiatan sesuai dengan bidang tugasnya.',
            3 => 'Menghargai privasi pihak/orang lain dengan menjaga, menyimpan dan tidak menyebarluaskan data pihak/orang lain tanpa seizin mereka.',
            4 => 'Mendorong orang lain dalam melaksanakan komitmen, kesepakatan maupun keputusan yang telah disepakati bersama.',
            5 => 'Meningkatkan tata kelola perusahaan dengan menerapkan fungsi pengawasan melekat.'
        ]],
        ['no' => 3, 'nama' => 'Fokus Pelanggan (Customer Focus)', 'levels' => [
            1 => 'Membutuhkan arahan detail dalam mempelajari kebutuhan-kebutuhan para pemangku kepentingan, terutama pelanggan internal maupun eksternal.',
            2 => 'Mengidentifikasi kebutuhan, harapan, dan tuntutan kebutuhan para pemangku kepentingan yang spesifik, terutama pelanggan internal maupun eksternal dan merespons sesuai dengan lingkup tugasnya berdasarkan arahan serta bimbingan atasannya.',
            3 => 'Mengikuti perkembangan kebutuhan, harapan, dan tuntutan para pemangku kepentingan, terutama pelanggan internal maupun eksternal dan melakukan upaya pemenuhannya.',
            4 => 'Memodifikasi dan menyusun rekomendasi cara kerja yang selaras dengan perkembangan kebutuhan, harapan, dan tuntutan kebutuhan para pemangku kepentingan, terutama pelanggan internal maupun eksternal.',
            5 => 'Memberi advis rancangan proses, prosedur, dan sistem maupun produk dan layanan baru yang sejalan dengan perkembangan dan perubahan-perubahan yang terjadi terkait kebutuhan, harapan, dan tuntutan para pemangku kepentingan, terutama pelanggan internal maupun eksternal.'
        ]],
        ['no' => 4, 'nama' => 'Kelincahan & Adaptasi (Agility & Adaptability)', 'levels' => [
            1 => 'Melakukan perubahan sesuai arahan dan bimbingan atasan.',
            2 => 'Melakukan penyesuaian-penyesuaian diri dan cara kerja secara berkesinambungan mengikuti tuntutan perubahan.',
            3 => 'Siap secara fisik dan mental dalam menghadapi dan melaksanakan berbagai bentuk perubahan yang harus dilakukan.',
            4 => 'Keluar dari zona nyaman dan menularkan kepada orang lain serta tetap produktif dalam menyelesaikan tugas dan tanggung jawabnya walaupun menghadapi situasi yang tidak pasti, kompleks, ambigu dengan melakukan berbagai upaya mencari cara-cara terbaik yang paling tepat yang kemungkinan berbeda dari cara-cara sebelumnya.',
            5 => 'Mencari makna (insights) dari berbagai pengalaman yang diperoleh dan mengidentifikasi adanya situasi yang sulit untuk diprediksi serta mengatur sumber daya, proses dan pengetahuan dan kemampuan secara fleksibel and melakukan upaya perubahan secara berkelanjutan.'
        ]],
        ['no' => 5, 'nama' => 'Kolaborasi (Collaboration)', 'levels' => [
            1 => 'Menerapkan nilai kerja sama dengan orang lain dalam berinteraksi dan berpartisipasi dalam kelompok sehari-hari.',
            2 => 'Menciptakan sinergi dalam kelompok dan antar kelompok yang lebih besar.',
            3 => 'Meningkatkan kerja sama dan kolaborasi dengan berbagai pihak baik dari internal maupun dari eksternal dalam upaya meningkatkan keberhasilan organisasi.',
            4 => 'Meninjau ulang nilai bekerja sama dan berkolaborasi dengan orang lain.',
            5 => 'Mengembangkan dan menciptakan pendekatan baru and lebih baik (new & better approach) terhadap nilai bekerja sama dan berkolaborasi dengan orang lain.'
        ]]
    ],
    'Leadership Competency' => [
        ['no' => 1, 'nama' => 'Kepemimpinan Digital (Digital Leadership)', 'levels' => [
            1 => 'Mengikuti perkembangan teknologi baru, terutama digital dengan membutuhkan arahan.',
            2 => 'Mempelajari dan menggunakan teknologi digital secara efektif.',
            3 => 'Mempelajari, menganalisis dan memberikan rekomendasi berbagai alternatif perangkat, teknologi digital, baik hardware maupun software.',
            4 => 'Mempelajari, menganalisis, dan memilih perangkat dan teknologi digital yang reliable dan sesuai dengan kebutuhan perusahaan.',
            5 => 'Merencanakan, mengembangkan, dan memimpin transformasi digital, serta mengembangkan perangkat dan teknologi digital yang terintegrasi.'
        ]],
        ['no' => 2, 'nama' => 'Ketajaman Bisnis (Business Acumen)', 'levels' => [
            1 => 'Memahami fundamental bisnis yang dijalankan, namun masih membutuhkan arahan.',
            2 => 'Memahami fundamental bisnis yang dijalankan di dalam unit kerjanya.',
            3 => 'Menerapkan pengetahuan mengenai industri, pasar, dan tren bisnis dalam menetapkan skala prioritas yang akan diambil.',
            4 => 'Memanfaatkan peluang usaha dan dinamika pasar untuk melaksanakan strategi perusahaan.',
            5 => 'Mengembangkan tingkat pemahaman yang luas dan kemampuan komersial perusahaan dalam merespons lingkungan bisnis.'
        ]],
        ['no' => 3, 'nama' => 'Dampak & Pengaruh (Impact & Influence)', 'levels' => [
            1 => 'Berusaha mengkomunikasikan ide, pendapat, dan masukan kepada orang lain, namun belum sepenuhnya efektif dalam mempengaruhi, meyakinkan, atau memberi kesan positif.',
            2 => 'Mempengaruhi orang lain dalam kelompok kecil untuk mengikuti, melaksanakan, mendukung, dan bertindak sesuai yang direncanakan.',
            3 => 'Mempengaruhi orang lain dalam kelompok yang lebih besar (unit kerja) untuk mengikuti, melaksanakan, mendukung, dan bertindak sesuai yang direncanakan.',
            4 => 'Mempengaruhi orang lain dalam tingkatan korporasi dengan menggunakan pengaruh yang dimiliki untuk mengikuti, melaksanakan, mendukung, dan bertindak sesuai yang direncanakan.',
            5 => 'Mempengaruhi orang lain dengan menggunakan strategi untuk mengikuti, melaksanakan, mendukung, and bertindak sesuai yang direncanakan.'
        ]],
        ['no' => 4, 'nama' => 'Kesadaran Organisasi (Organizational & Social Awareness)', 'levels' => [
            1 => 'Mampu menguraikan konsep umum sosiologi dan politik.',
            2 => 'Mampu menjelaskan keadaan ekonomi, sosial, politik, keamanan dan/atau norma-norma, dan pihak-pihak yang terlibat dalam proses pengambilan keputusan di dalam pemerintahan/komunitas/institusi di suatu wilayah atau negara yang berpengaruh terhadap keberlangsungan bisnis perusahaan.',
            3 => 'Cepat tanggap dan merespons suatu keadaan dengan analisis data dan informasi terkait keadaan ekonomi, sosial, politik, keamanan dan/atau norma-norma, mematuhi pihak-pihak yang terlibat dalam proses pengambilan keputusan, kecenderungan perilaku dan tindakan di dalam pemerintahan, atau komunitas, atau institusi di suatu wilayah atau negara, serta dapat menggambarkan keadaan secara utuh dan komprehensif dan keterkaitannya dengan keberlangsungan bisnis perusahaan.',
            4 => 'Memahami dan memanfaatkan posisi diri di antara pihak-pihak yang terlibat di dalam kepentingan bisnis perusahaan, baik internal dan eksternal, serta dapat menyesuaikan diri dan mengambil sikap/tindakan yang tepat dalam merespons keadaan tersebut.',
            5 => 'Mengambil sikap dan tindakan yang tepat dalam merespons keadaan dengan menganalisis dan mengevaluasi arah perubahan keadaan ekonomi, sosial, politik dan keamanan dan atau norma-norma dan pihak-pihak yang terlibat dalam proses pengambilan keputusan di dalam pemerintahan, atau komunitas, atau institusi di suatu wilayah atau negara.'
        ]],
        ['no' => 5, 'nama' => 'Perencanaan, Pengelolaan & Pengendalian (Planning, Organizing, Controlling)', 'levels' => [
            1 => 'Membutuhkan arahan dalam merencanakan dan melaksanakan kegiatan dalam mencapai sasaran yang ditetapkan.',
            2 => 'Menyusun rencana, prioritas, and alokasi kebutuhan sumber daya secara mandiri untuk penyelesaian target individu dan memahami keterkaitannya dengan target unit kerja dan perusahaan.',
            3 => 'Menyusun, mengelola, melaksanakan, and mengendalikan kegiatan-kegiatan yang prioritas untuk pencapaian target unit kerja and penurunannya ke target individu di dalam unit kerja terkait.',
            4 => 'Mensinergikan berbagai unit kerja di dalam perusahaan dalam menyusun, mengelola, melaksanakan, and mengendalikan kegiatan-kegiatan yang prioritas untuk pencapaian target perusahaan.',
            5 => 'Melakukan perbaikan-perbaikan aspek perencanaan, pengelolaan, pengendalian, and meningkatkan efektivitas eksekusi program perusahaan.'
        ]]
    ]
];

ob_start();
?>
    <style>
        .sheet-area, .sheet-area table, .sheet-area td, .sheet-area th, .sheet-area div, .sheet-area span { font-family: Calibri, sans-serif; }
        .sheet-area .sheet-title { font-family: Arial, sans-serif !important; font-weight: bold; font-size: 11px; margin-bottom: 10px; text-transform: uppercase; text-align: left; }
        .excel-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; table-layout: fixed; margin-bottom: 5px; }
        .excel-table th { border: 1px solid #000; background: #d9e1f2; padding: 2px 1px; text-align: center; font-weight: bold; font-size: 7.5px; }
        .excel-table td { border: 1px solid #000; padding: 1.5px 3px; vertical-align: middle; word-wrap: break-word; font-size: 7.5px; }
        .section-header { background: #fff; color: #1976d2; font-weight: bold; padding: 3px !important; font-size: 8.5px; }
        .bg-hijau-deskripsi { background-color: #e2efda; }
        .footer-total { background: #d9e1f2; font-weight: bold; font-size: 11px; }
        .footer-total td { padding: 4px 8px !important; }
        .nilai-final { font-size: 13px; font-weight: bold; }
        .input-plain { width: 100%; border: none; background: transparent; font-family: inherit; font-size: 9px; font-weight: bold; text-align: center; outline: none; }
        
        @media print { 
            .no-print-btn { display: none !important; } 
            
            /* KOMPRESI CERDAS: LEGA TAPI TETAP 1 HALAMAN */
            .sheet-area {
                zoom: 0.95; /* Menciutkan sedikit saja agar ada ruang buat padding */
                margin: 0;
            }
            .excel-table td { 
                padding: 1px 3px !important; 
                line-height: 1.5 !important; 
                font-size: 7.2px !important; 
            }
            .excel-table th { font-size: 7.8px !important; padding: 2px !important; }
            .section-header { padding: 3px !important; font-size: 8.5px !important; }
            .sheet-title { margin-bottom: 5px !important; font-size: 11px !important; }
            .footer-total td { padding: 3px 8px !important; font-size: 10px !important; }
            .nilai-final { font-size: 10px !important; }
        }
    </style>
    <div class="sheet-title">D. KOMPETENSI</div>
    <table class="excel-table">
        <thead>
            <tr><th width="3%">No</th><th width="20%">Kompetensi</th><th width="5%">Lvl</th><th width="65%">Deskripsi</th><th width="7%">Nilai</th></tr>
        </thead>
        <tbody>
        <?php 
        $maxAllowedPhp = (isset($nilai_kpi_a) && (float)$nilai_kpi_a < 2.8) ? 3 : 4;
        $grand_k = 0;
        foreach($komp_data as $sec => $items): 
        ?>
            <tr><td colspan="5" class="section-header"><?= $sec ?></td></tr>
            <?php 
            foreach($items as $item): 
                $is_saved = isset($saved_k[$item['nama']]);
                $v = $is_saved ? $saved_k[$item['nama']] : $maxAllowedPhp; 
                $grand_k += (float)$v;
                foreach($item['levels'] as $lvl => $desc):
            ?>
                <tr>
                    <?php if($lvl==1): ?>
                        <td rowspan="5" style="text-align:center;"><?= $item['no'] ?></td>
                        <td rowspan="5" style="font-weight:bold;"><?= $item['nama'] ?></td>
                    <?php endif; ?>
                    <td style="text-align:center;"><?= $lvl ?></td>
                    <td class="bg-hijau-deskripsi"><?= $desc ?></td>
                    <?php if($lvl==1): ?>
                        <td rowspan="5" style="text-align:center; font-weight:bold;">
                            <?php 
                            $cell_val = ($v ? number_format((float)$v, 0, ',', '.') : "-");
                            if(isset($is_web) && $is_web) {
                                $sec_code = ($sec == 'Core Competency') ? 'core' : 'leadership';
                                echo '<input type="number" 
                                       name="skor['.$sec.']['.$item['nama'].']" 
                                       value="'.(($v !== null && $v !== "") ? number_format((float)$v, 0, '', '') : "").'" 
                                       min="0" max="'.$maxAllowedPhp.'" step="1" 
                                       class="input-plain input-skor" 
                                       data-section="'.$sec_code.'"
                                       oninput="validateAndCalc(this)" 
                                       placeholder="0" 
                                       autocomplete="off"
                                       required '.(($_SESSION['jabatan'] == "Divisi" || $_SESSION['jabatan'] == "Direksi") ? "readonly" : "").'>';
                            } else {
                                echo $cell_val;
                            }
                            ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php 
                endforeach; 
            endforeach; 
        endforeach; 
        ?>
        </tbody>
        <tfoot>
            <tr class="footer-total"><td colspan="4" style="text-align:right; padding-right: 15px;">Jumlah</td><td style="text-align:center;" class="nilai-final" id="total_jumlah"><?= number_format($grand_k, 2, ",", ".") ?></td></tr>
            <tr class="footer-total"><td colspan="4" style="text-align:right; padding-right: 15px;">Nilai ( (Jumlah/40) * 5 )</td><td style="text-align:center;" class="nilai-final" id="nilai_final"><?= number_format(($grand_k/40)*5, 2, ",", ".") ?></td></tr>
        </tfoot>
    </table>

    <?php if (isset($is_web) && $is_web): ?>
    <script>
    function validateAndCalc(el) {
        const nilaiKPI = <?= (float)($nilai_kpi_a ?? 0) ?>;
        const section = el.getAttribute("data-section");
        let val = parseFloat(el.value) || 0;
        
        // Aturan Maksimal
        let maxAllowed = 4;
        let reason = "";
        if (nilaiKPI < 2.8) {
            maxAllowed = 3;
            reason = " karena Skor KPI Anda " + nilaiKPI.toFixed(2) + " (< 2.8)";
        }

        if (val > maxAllowed) { 
            el.value = maxAllowed; 
            Swal.fire({
                icon: "warning",
                title: "Batas Maksimal",
                text: "Maksimal nilai Kompetensi pada bagian ini adalah " + maxAllowed + reason + ".",
                timer: 3000,
                showConfirmButton: false
            });
        } else if (val < 0 && el.value !== "") { 
            el.value = 0; 
            Swal.fire({
                icon: "warning",
                title: "Nilai Tidak Valid",
                text: "Nilai tidak boleh negatif (kurang dari 0).",
                timer: 3000,
                showConfirmButton: false
            });
        }

        calculateTotals();
    }
    function calculateTotals() {
        let total = 0;
        document.querySelectorAll(".input-skor").forEach(input => { total += parseInt(input.value) || 0; });
        document.getElementById("total_jumlah").innerText = total.toLocaleString("id-ID", {minimumFractionDigits: 2, maximumFractionDigits: 2});
        let final = (total / 40) * 5;
        document.getElementById("nilai_final").innerText = final.toLocaleString("id-ID", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    </script>
    <?php endif; ?>

<?php
    $html_k = ob_get_clean();
    $html_k = '<div class="sheet-area">' . $html_k . '</div>';
?>


