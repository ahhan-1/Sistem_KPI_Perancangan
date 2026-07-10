<?php
/**
 * TEMPLATE: RESUME PENILAIAN AKHIR
 * @var mysqli $koneksi
 * @var array $u
 * @var int $id_nilai
 * @var int $tahun
 * @var int $bulan
 * @var float $nilai_kinerja
 * @var float $nilai_perilaku
 * @var float $nilai_tambahan
 * @var float $nilai_pengurang
 * @var float $nilai_akhir_total
 * @var string $indeks_akhir
 * @var float $weighted_kinerja
 * @var float $weighted_perilaku
 * @var array $pertimbangan
 * @var string $rekomendasi
 * @var array $pelatihan
 * @var string $tgl_pbg
 * @var array $nama_bulan_long
 */

$html_r = '
    <style>
        .sheet-area, .sheet-area table, .sheet-area td, .sheet-area th, .sheet-area div, .sheet-area span { font-family: Calibri, sans-serif; }
        .sheet-area .sheet-title { font-family: Arial, sans-serif !important; font-weight: bold; font-size: 10px; margin-bottom: 10px; text-transform: uppercase; text-align: left; }
        
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        .meta-table td { padding: 3px 6px; border: 1px solid #000; font-size: 8px; vertical-align: middle; }
        .meta-label { background: #f2f2f2; font-weight: bold; width: 20%; }
        .meta-val { width: 30%; }

        .excel-table { width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 5px; table-layout: fixed; }
        .excel-table th, .excel-table td { border: 1px solid #000; padding: 2px 5px; font-size: 7.2px; }
        .excel-header { background: #f2f2f2; font-weight: bold; text-align: center; }
        .bg-blue { background: #d9e1f2; }
        .legend-table { width: 60%; font-size: 6.8px; margin-bottom: 5px; }
        .legend-table td { padding: 0px 5px; border: none !important; }
        .box { width: 10px; height: 10px; border: 1px solid #000; display: inline-block; text-align: center; line-height: 10px; font-weight: bold; }
        .input-plain { width: 100%; border: none; background: transparent; font-family: inherit; font-size: inherit; outline: none; }
        @media print { .no-print-btn { display: none !important; } }
    </style>

    <div class="sheet-title">PENILAIAN AKHIR (RESUME)</div>
    
    <table class="excel-table">
        <thead>
            <tr class="excel-header"><th width="50%"></th><th width="15%">Nilai</th><th width="15%" class="bg-blue">Bobot</th><th width="20%">Nilai akhir</th></tr>
        </thead>
        <tbody>
            <tr><td>Rata-rata Nilai Kinerja</td><td style="text-align:center;">'.number_format($nilai_kinerja, 2, ",", ".").'</td><td style="text-align:center;" class="bg-blue">85%</td><td style="text-align:center;">'.number_format($weighted_kinerja, 2, ",", ".").'</td></tr>
            <tr><td>Rata-rata perilaku-kompetensi</td><td style="text-align:center;">'.number_format($nilai_perilaku, 2, ",", ".").'</td><td style="text-align:center;" class="bg-blue">15%</td><td style="text-align:center;">'.number_format($weighted_perilaku, 2, ",", ".").'</td></tr>
            <tr><td>Penugasan Khusus, Rangkap Jabatan, Inovasi & Kreatifitas(jika ada)</td><td></td><td></td><td style="text-align:center;">'.number_format($nilai_tambahan, 2, ",", ".").'</td></tr>
            <tr><td>Faktor Pengurang KPI (jika ada)</td><td></td><td></td><td style="text-align:center;">'.number_format($nilai_pengurang, 2, ",", ".").'</td></tr>
        </tbody>
        <tfoot>
            <tr><td colspan="3" style="text-align:right; font-weight:bold; font-style:italic;">Nilai Akhir</td><td style="text-align:center; font-weight:bold; font-size:10px;">'.number_format($nilai_akhir_total, 2, ",", ".").'</td></tr>
            <tr><td colspan="3" style="text-align:right; font-weight:bold; font-style:italic;">Indeks</td><td style="text-align:center; font-weight:bold; background:#c6efce; font-size:10px;">'.$indeks_akhir.'</td></tr>
        </tfoot>
    </table>
    <table class="legend-table">
        <tr><td width="20">A :</td><td width="60">4.51 - 5.00</td><td>Memuaskan</td></tr>
        <tr><td>B :</td><td>3.00 - 4.50</td><td>Baik</td></tr>
        <tr><td>C :</td><td>2.01 - 2.99</td><td>Cukup</td></tr>
        <tr><td>D :</td><td>1.01 - 2.00</td><td>Perlu Peningkatan</td></tr>
        <tr><td>E :</td><td>&le; 1.00</td><td>Kurang</td></tr>
        <tr><td>F :</td><td>Tanpa Penilaian</td><td>Perlu Perhatian Khusus</td></tr>
    </table>
    <div style="font-weight:bold; text-decoration:underline; margin-top:5px; font-size:8px;">CATATAN</div>
    <p style="margin: 0 0 5px 0; font-size:7.2px;">Keterangan: Pilihlah salah satu pilihan rekomendasi dengan tanda <b>V</b></p>
    <table class="excel-table">';
        
        $html_r .= '<tr><td width="30%" rowspan="3" style="text-align:center;">Pertimbangan Khusus</td><td width="30%">1. Tetap di posisi saat ini</td><td width="5%" style="text-align:center;">';
        if(isset($is_web) && $is_web) {
            $html_r .= '<input type="checkbox" name="pertimbangan[]" value="Tetap" '.(in_array("Tetap", $pertimbangan)?"checked":"").' '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'onclick="return false;"' : '').'>';
        } else {
            $html_r .= '<div class="box">'.(in_array("Tetap", $pertimbangan)?"V":"").'</div>';
        }
        $html_r .= '</td><td width="30%">3. Promosi</td><td width="5%" style="text-align:center;">';
        if(isset($is_web) && $is_web) {
            $html_r .= '<input type="checkbox" name="pertimbangan[]" value="Promosi" '.(in_array("Promosi", $pertimbangan)?"checked":"").' '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'onclick="return false;"' : '').'>';
        } else {
            $html_r .= '<div class="box">'.(in_array("Promosi", $pertimbangan)?"V":"").'</div>';
        }
        $html_r .= '</td></tr>
        <tr><td>2. Mutasi</td><td style="text-align:center;">';
        if(isset($is_web) && $is_web) {
            $html_r .= '<input type="checkbox" name="pertimbangan[]" value="Mutasi" '.(in_array("Mutasi", $pertimbangan)?"checked":"").' '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'onclick="return false;"' : '').'>';
        } else {
            $html_r .= '<div class="box">'.(in_array("Mutasi", $pertimbangan)?"V":"").'</div>';
        }
        $html_r .= '</td><td>4. Demosi</td><td style="text-align:center;">';
        if(isset($is_web) && $is_web) {
            $html_r .= '<input type="checkbox" name="pertimbangan[]" value="Demosi" '.(in_array("Demosi", $pertimbangan)?"checked":"").' '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'onclick="return false;"' : '').'>';
        } else {
            $html_r .= '<div class="box">'.(in_array("Demosi", $pertimbangan)?"V":"").'</div>';
        }
        $html_r .= '</td></tr>
        <tr><td>3. Rotasi</td><td style="text-align:center;">';
        if(isset($is_web) && $is_web) {
            $html_r .= '<input type="checkbox" name="pertimbangan[]" value="Rotasi" '.(in_array("Rotasi", $pertimbangan)?"checked":"").' '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'onclick="return false;"' : '').'>';
        } else {
            $html_r .= '<div class="box">'.(in_array("Rotasi", $pertimbangan)?"V":"").'</div>';
        }
        $html_r .= '</td><td>5. Cut</td><td style="text-align:center;">';
        if(isset($is_web) && $is_web) {
            $html_r .= '<input type="checkbox" name="pertimbangan[]" value="Cut" '.(in_array("Cut", $pertimbangan)?"checked":"").' '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'onclick="return false;"' : '').'>';
        } else {
            $html_r .= '<div class="box">'.(in_array("Cut", $pertimbangan)?"V":"").'</div>';
        }
        $html_r .= '</td></tr>';
    $html_r .= '</table>
    <table class="excel-table">
        <thead><tr class="excel-header"><th width="40%">SUB STREAM KOMPETENSI</th><th width="10%"></th><th width="50%">NAMA PELATIHAN</th></tr></thead>
        <tbody>';
        $streams = ["Performa Bisnis", "Pembiayaan/Kredit", "Produk Dana & Layanan", "Pemasaran & Layanan", "Riset & Pengembangan Bisnis", "Treasury", "Operasional Bank", "Layanan IT", "Infrastruktur IT", "Rencana Keuangan", "Akuntansi Keuangan", "Manajemen Aset", "Manajemen HC", "Desain Organisasi & Pengembangan", "Administrasi", "Komunikasi Perusahaan", "Manajemen Data", "Logistik", "Kompetensi lainnya"];
        foreach($streams as $s) { 
            $val_pel = $pelatihan[$s] ?? "";
            $box_pel = "";
            if(isset($is_web) && $is_web) {
                $box_pel = '<input type="checkbox" name="pel_streams[]" value="'.$s.'" '.(isset($pelatihan[$s])?"checked":"").' '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'onclick="return false;"' : '').'>';
                $input_pel = '<input type="text" name="pel_names['.$s.']" class="input-plain" value="'.$val_pel.'" placeholder="..." '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'readonly' : '').'>';
            } else {
                $box_pel = '<div class="box">'.(isset($pelatihan[$s])?"V":"").'</div>';
                $input_pel = $val_pel;
            }
            $html_r .= '<tr><td>'.$s.'</td><td style="text-align:center;">'.$box_pel.'</td><td>'.$input_pel.'</td></tr>'; 
        }
        $html_r .= '</tbody></table>
    <p style="font-weight:bold; margin-top:5px; font-size:7.2px;">Rekomendasi, Usul atau Saran dari Penilai :</p>
    <div style="border:1px solid #000; min-height:30px; padding:3px; font-size:7.2px;">';
    if(isset($is_web) && $is_web) {
        $html_r .= '<textarea name="rekomendasi" style="width:100%; border:none; outline:none; background:transparent; font-family:inherit; font-size:inherit; resize:vertical; min-height:40px;" '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'readonly' : '').'>'.$rekomendasi.'</textarea>';
    } else {
        $html_r .= $rekomendasi;
    }
    $html_r .= '</div>
    <p style="font-weight:bold; margin-top:5px; font-size:7.2px;">Penjelasan :</p>
    <table class="excel-table"><tr class="excel-header"><td>N a m a</td><td>J a b a t a n</td><td>T a n g g a l</td><td>Tanda Tangan</td></tr><tr><td height="15"></td><td></td><td></td><td></td></tr></table>
    <p style="margin: 5px 0; font-size:7.2px;">Tanggal Penilaian : <strong>'.($nama_bulan_long[$bulan] ?? "-").' '.$tahun.'</strong></p>
    <table style="width:100%; margin-top:10px; text-align:center; font-size:8px; line-height: 1.2;">
        <tr>
            <td style="width:33.3%;">Atasan Penilai,<br><br><br><br><br><br><span style="font-weight:bold; text-decoration:underline;">'.($u['atasan_dari_atasan_langsung'] ?? "Marzuki").'</span><br>Direktur Bisnis</td>
            <td style="width:33.3%;">Penilai,<br><br><br><br><br><br><span style="font-weight:bold; text-decoration:underline;">'.($u['atasan_langsung'] ?? "Ika Puspitasari").'</span><br>Pemimpin Divisi</td>
            <td style="width:33.3%;"><strong>'.$tgl_pbg.'</strong><br>Pegawai Yang Dinilai,<br><br><br><br><br><br><span style="font-weight:bold; text-decoration:underline;">'.($u['nama'] ?? "M.Dava Prayoga").'</span><br>Pemimpin Cabang</td>
        </tr>
    </table>';
    $html_r = '<div class="sheet-area">' . $html_r . '</div>';
?>


