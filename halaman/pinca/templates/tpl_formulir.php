<?php
/**
 * TEMPLATE: FORMULIR PENILAIAN
 * @var mysqli $koneksi
 * @var array $u
 * @var int $id_cabang
 * @var int $tahun
 * @var int $bulan
 * @var float $multiplier
 * @var array $nama_bulan_long
 * @var int $id_nilai
 * @var string $cabang_display
 * @var string $label_periode
 */

// Centralized logic loaded via perhitungan_kpi.php

$q_data = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, t_ann.nilai_target as target_tahunan, t_mon.nilai_target as target_bulanan,
           (SELECT realisasi FROM realisasi WHERE id_indikator = i.id_indikator AND id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan) as total_realisasi
           FROM indikator i
           JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
           JOIN perspektif p ON s.id_perspektif = p.id_perspektif
           LEFT JOIN target t_ann ON i.id_indikator = t_ann.id_indikator AND t_ann.id_cabang = $id_cabang AND t_ann.tahun = $tahun AND t_ann.bulan = 12
           LEFT JOIN target t_mon ON i.id_indikator = t_mon.id_indikator AND t_mon.id_cabang = $id_cabang AND t_mon.tahun = $tahun AND t_mon.bulan = $bulan
           WHERE i.tahun = $tahun AND i.id_cabang = $id_cabang
           ORDER BY p.id_perspektif ASC, s.id_subperspektif ASC, i.id_indikator ASC";
$res_data = mysqli_query($koneksi, $q_data);
$grouped_f = [];
while($row = mysqli_fetch_assoc($res_data)) $grouped_f[$row['nama_subperspektif']][] = $row;

$data_b = []; $res_b = mysqli_query($koneksi, "SELECT * FROM nilai_tambahan WHERE id_nilai = $id_nilai");
while($rb = mysqli_fetch_assoc($res_b)) $data_b[] = $rb;

$data_c = []; $res_c = mysqli_query($koneksi, "SELECT * FROM nilai_pengurang WHERE id_nilai = $id_nilai");
while($rc = mysqli_fetch_assoc($res_c)) $data_c[$rc['jenis']] = $rc;

$html_f = '
    <style>
        .sheet-area, .sheet-area table, .sheet-area td, .sheet-area th, .sheet-area div, .sheet-area span { font-family: Calibri, sans-serif; }
        .sheet-area .sheet-title-center, .sheet-area .sheet-subtitle { font-family: Arial, sans-serif !important; width: 100% !important; display: block !important; text-align: center !important; }
        .sheet-area .sheet-title-center { font-weight: bold; font-size: 14px; margin-bottom: 2px; text-transform: uppercase; }
        .sheet-area .sheet-subtitle { font-weight: bold; font-size: 12px; margin-bottom: 10px; }
        
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        .meta-table td { padding: 3px 6px; border: 1px solid #000; font-size: 8px; vertical-align: middle; }
        .meta-label { background: #f2f2f2; font-weight: bold; width: 20%; }
        .meta-val { width: 30%; }
        
        .excel-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; table-layout: fixed; margin-bottom: 10px; }
        .excel-table th { border: 1px solid #000; background: #d9e1f2; padding: 4px 1px; text-align: center; font-weight: bold; font-size: 8px; }
        .excel-table td { border: 1px solid #000; padding: 2px 4px; font-size: 7.5px; vertical-align: middle; }
        
        .blue-bar { background: #daeef3; font-weight: bold; }
        .avg-val { display: inline-block; background: #daeef3; border: 2px solid #000; padding: 2px 0; font-weight: bold; font-size: 11px; width: 45px; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .input-plain { width: 100%; border: none; background: transparent; font-family: inherit; font-size: inherit; outline: none; padding: 0; }
        
        @media print {
            .no-print-btn { display: none !important; }
        }
    </style>

    <div class="sheet-title-center">FORMULIR PENILAIAN KINERJA KARYAWAN</div>
    <div class="sheet-subtitle">TAHUN '.$tahun.'</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Periode Penilaian</td><td class="meta-val">'.$label_periode.'</td>
            <td class="meta-label">Lokasi Kerja</td><td class="meta-val">'.$cabang_display.'</td>
        </tr>
        <tr>
            <td class="meta-label">Nama</td><td class="meta-val">'.($u['nama'] ?? '-').'</td>
            <td class="meta-label">Nomor Pegawai (NIP)</td><td class="meta-val">'.($u['NIP'] ?? '-').'</td>
        </tr>
        <tr>
            <td class="meta-label">Jabatan</td><td class="meta-val">Pemimpin Cabang</td>
            <td class="meta-label">Tanggal Masuk Kerja BSB</td><td class="meta-val">'.($u['tanggal_masuk_kerja'] ?? '-').'</td>
        </tr>
        <tr>
            <td class="meta-label">Dept</td><td class="meta-val">'.($cabang_display ?? '-').'</td>
            <td class="meta-label">Tgl Pengangkatan Terakhir</td><td class="meta-val">'.($u['tanggal_pengangkatan_terakhir'] ?? '-').'</td>
        </tr>
        <tr>
            <td class="meta-label">Nama Atasan</td><td class="meta-val">'.($u['atasan_langsung'] ?? 'Ika Puspitasari').'</td>
            <td class="meta-label">Tanggal Penilaian Kinerja</td><td class="meta-val">'.date('d-m-Y').'</td>
        </tr>
    </table>

    <div style="font-weight:bold; font-size:9px; margin-bottom:5px;">A. TUGAS DAN HASIL KERJA</div>
    <table class="excel-table">
        <thead>
            <tr>
                <th width="3%">NO</th>
                <th width="45%">ASPEK PENILAIAN</th>
                <th width="14%">TARGET</th>
                <th width="14%">REALISASI</th>
                <th width="8%">PENC (%)</th>
                <th width="4%">NILAI</th>
                <th width="5%">BOBOT</th>
                <th width="7%">JUMLAH (NxB)</th>
            </tr>
        </thead>
        <tbody>';
        $no_asp = 1; $grand_total_a = 0;
        foreach($grouped_f as $asp => $inds) {
            $bobot_asp = 0; foreach($inds as $i) $bobot_asp += $i['bobot'];
            $nilai_rata_asp = 0; $rows_html = "";
            foreach($inds as $ind) {
                // HITUNG TARGET SMART (Monthly)
                $target_p = calculate_target_period($koneksi, (int)$ind['id_indikator'], $id_cabang, $tahun, $bulan);
                
                $real_p = $ind['total_realisasi'] ?: 0;
                
                // Use centralized helper
                $is_repayment = (stripos(trim($ind['nama_indikator']), 'Repayment Rate') !== false);
                $penc = calculate_pencapaian($ind['nama_indikator'], (float)$target_p, (float)$real_p, (int)$ind['terbalik'], (string)$ind['satuan']);
                $skor = calculate_skor_indikator($koneksi, (int)$ind['id_indikator'], (float)$penc);
                // Jika target 0, kolom pencapaian dikosongkan (tidak bisa dihitung %)
                $penc_display = ((float)$target_p == 0) ? '-' : number_format($penc, 2, ',', '.').'%';

                $jml = $skor * ($ind['bobot'] / 100);
                $nilai_rata_ind = ($bobot_asp > 0) ? ($ind['bobot'] / $bobot_asp) * $skor : 0;
                $nilai_rata_asp += $nilai_rata_ind;
                $s = trim(strtolower($ind['satuan']));
                $suffix = ($s == 'persen' || $s == '%' || $is_repayment) ? '%' : '';
                
                $rows_html .= "<tr>
                    <td class='text-center'></td>
                    <td>{$ind['nama_indikator']}</td>
                    <td class='text-right'>".number_format($target_p, 2, ',', '.').$suffix."</td>
                    <td class='text-right'>".number_format($real_p, 2, ',', '.').$suffix."</td>
                    <td class='text-center'>".($penc_display)."</td>
                    <td class='text-center'>{$skor}</td>
                    <td class='text-center'>".number_format($ind['bobot'], 2, ',', '.')."%</td>
                    <td class='text-center'>".number_format($jml, 2, ',', '.')."</td>
                </tr>";
            }
            $jumlah_asp = $nilai_rata_asp * ($bobot_asp / 100); $grand_total_a += $jumlah_asp;
            $html_f .= '<tr class="blue-bar"><td class="text-center">'.$no_asp++.'</td><td>'.$asp.'</td><td></td><td></td><td></td><td></td><td class="text-center">'.number_format($bobot_asp, 2, ',', '.').'%</td><td class="text-center">'.number_format($jumlah_asp, 2, ',', '.').'</td></tr>' . $rows_html;
        }
        $html_f .= '
            <tr>
                <td colspan="7" class="text-right" style="font-weight:bold; padding-right:15px;">Rata-rata Nilai Kinerja</td>
                <td class="text-center" style="background:#daeef3; font-weight:bold; font-size:10px;">'.number_format($grand_total_a, 2, ',', '.').'</td>
            </tr>
        </tbody>
    </table>

    <div style="display:flex; justify-content:space-between; align-items:flex-end;">
        <div style="font-weight:bold; font-size:9px; margin-top:10px; margin-bottom:5px;">B. TAMBAHAN PENILAIAN (maksimal 0.25)</div>
        '.((isset($is_web) && $is_web && $_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi') ? '<button type="button" class="no-print-btn" onclick="addBarisB()" style="margin-bottom:5px; padding:2px 8px; cursor:pointer; background:#0245a3; color:white; border:none; border-radius:4px; font-size:7px;"><i class="fas fa-plus"></i> Tambah Baris</button>' : '').'
    </div>
    <table class="excel-table">
        <thead>
            <tr><th width="3%">No</th><th width="'.((isset($is_web) && $is_web) ? '82%' : '87%').'">Jenis Penugasan</th>'.((isset($is_web) && $is_web && $_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi') ? '<th width="5%" class="no-print-btn"></th>' : '').'<th width="10%">Nilai Total (B)</th></tr>
        </thead>
        <tbody id="tbody-b">
            <input type="hidden" id="hidden_grand_total_a" value="'.$grand_total_a.'">';
        $jml_b = max(3, count($data_b));
        for($i=0; $i<$jml_b; $i++) {
            $val_b = isset($data_b[$i]) ? $data_b[$i] : null;
            $input_field = (isset($is_web) && $is_web) ? '<input type="text" name="b_tugas[]" class="input-plain" value="'.($val_b['tugas'] ?? '').'" placeholder="..." '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'readonly' : '').'>' : ($val_b['tugas'] ?? '...');
            $delete_btn = (isset($is_web) && $is_web && $_SESSION['jabatan'] != 'Divisi' && $_SESSION['jabatan'] != 'Direksi') ? '<td class="text-center no-print-btn"><button type="button" onclick="hapusBarisB(this)" style="border:none; background:transparent; color:#e53e3e; cursor:pointer; font-size:7px;"><i class="fas fa-times"></i></button></td>' : '';
            
            $html_f .= '<tr class="row-b"><td class="text-center no-b">'.($i+1).'</td><td>'.$input_field.'</td>'.$delete_btn;
            if($i==0) {
                $nilai_total_b = number_format($u['nilai_tambahan']??0, 2, ',', '.');
                if(isset($is_web) && $is_web) {
                    $nilai_total_b = '<input type="number" step="0.01" max="0.25" name="b_nilai_total" id="b_nilai_total" class="input-plain text-center font-bold" style="font-size:10px;" value="'.($u['nilai_tambahan']??0).'" oninput="calcTotal()" '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'readonly' : '').'>';
                }
                $html_f .= '<td rowspan="'.$jml_b.'" id="cell-nilai-b" class="text-center" style="font-weight:bold; font-size:10px;">'.$nilai_total_b.'</td>';
            }
            $html_f .= '</tr>';
        }
        $html_f .= '</tbody>
    </table>

    <div style="font-weight:bold; font-size:9px; margin-top:10px; margin-bottom:5px;">C. FAKTOR PENGURANG KPI</div>
    <table class="excel-table">
        <thead>
            <tr><th width="60%">Tingkat Permasalahan</th><th width="15%">Jumlah Permasalahan (n)</th><th width="10%">Nilai Dasar</th><th width="15%">Bobot X Nilai</th></tr>
        </thead>
        <tbody>';
        $cats = ["TT1"=>["label"=>"Sanksi Ringan (TT1)", "val"=>-0.1], "TT2"=>["label"=>"Sanksi Ringan (TT2)", "val"=>-0.15], "TT3"=>["label"=>"Sanksi Ringan (TT3)", "val"=>-0.2], "Konseling"=>["label"=>"Konseling", "val"=>-0.05], "Absensi"=>["label"=>"Absensi", "val"=>-0.01], "THTK"=>["label"=>"THTK", "val"=>-0.03]];
        foreach($cats as $key => $cfg) {
            $n = $data_c[$key]['jumlah'] ?? 0; $sub = $n * $cfg['val'];
            $input_n = (isset($is_web) && $is_web) ? '<input type="number" name="c_jumlah['.$key.']" class="input-plain text-center val-c-n" data-val="'.$cfg['val'].'" value="'.$n.'" oninput="calcTotal()" '.(($_SESSION['jabatan'] == 'Divisi' || $_SESSION['jabatan'] == 'Direksi') ? 'readonly' : '').'>' : $n;
            
            $html_f .= '<tr><td>'.$cfg['label'].'</td><td class="text-center">'.$input_n.'</td><td class="text-center">'.$cfg['val'].'</td><td class="text-center val-c-res">'.number_format($sub, 2, ',', '.').'</td></tr>';
        }
        $html_f .= '
            <tr><td colspan="3" class="text-right" style="font-weight:bold; padding-right:15px;">Total Pengurang (C)</td><td id="totalC" class="text-center" style="font-weight:bold;">'.number_format($u['nilai_pengurang']??0, 2, ',', '.').'</td></tr>
        </tbody>
    </table>

    '.((isset($is_web) && $is_web) ? '
    <script>
    function addBarisB() {
        const tbody = document.getElementById("tbody-b");
        const rowCount = tbody.querySelectorAll(".row-b").length;
        const newRow = document.createElement("tr");
        newRow.className = "row-b";
        
        newRow.innerHTML = `
            <td class="text-center no-b">${rowCount + 1}</td>
            <td><input type="text" name="b_tugas[]" class="input-plain" value="" placeholder="..."></td>
            <td class="text-center no-print-btn">
                <button type="button" onclick="hapusBarisB(this)" style="border:none; background:transparent; color:#e53e3e; cursor:pointer; font-size:7px;">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
        updateRowspanB();
    }

    function hapusBarisB(btn) {
        const tbody = document.getElementById("tbody-b");
        if (tbody.querySelectorAll(".row-b").length <= 1) return;
        btn.closest(".row-b").remove();
        
        // Re-index
        tbody.querySelectorAll(".row-b").forEach((r, idx) => {
            r.querySelector(".no-b").innerText = idx + 1;
        });
        updateRowspanB();
    }

    function updateRowspanB() {
        const tbody = document.getElementById("tbody-b");
        const cellNilai = document.getElementById("cell-nilai-b");
        if(cellNilai) cellNilai.rowSpan = tbody.querySelectorAll(".row-b").length;
    }
    </script>
    ' : '').'
';
    $html_f = '<div class="sheet-area">' . $html_f . '</div>';
?>


