<?php
/**
 * TEMPLATE: KERTAS KERJA
 * @var mysqli $koneksi
 * @var array $u
 * @var int $id_cabang
 * @var int $tahun
 * @var int $bulan
 * @var float $multiplier
 * @var array $nama_bulan_long
 * @var string $tgl_pbg
 */

// Centralized Logic is loaded via perhitungan_kpi.php
$nama_bulan_long = get_nama_bulan();

// Data Fetching
$q_data = "SELECT i.*, s.nama_subperspektif, p.nama_perspektif, t_ann.nilai_target as target_tahunan, t_mon.nilai_target as target_bulanan,
           (SELECT realisasi FROM realisasi WHERE id_indikator = i.id_indikator AND id_cabang = $id_cabang AND tahun = $tahun AND bulan = $bulan) as total_realisasi
           FROM indikator i
           JOIN subperspektif s ON i.id_subperspektif = s.id_subperspektif
           JOIN perspektif p ON s.id_perspektif = p.id_perspektif
           LEFT JOIN target t_ann ON i.id_indikator = t_ann.id_indikator AND t_ann.id_cabang = $id_cabang AND t_ann.tahun = $tahun AND t_ann.bulan = 12
           LEFT JOIN target t_mon ON i.id_indikator = t_mon.id_indikator AND t_mon.id_cabang = $id_cabang AND t_mon.tahun = $tahun AND t_mon.bulan = $bulan
           WHERE i.tahun = $tahun AND i.id_cabang = $id_cabang
           ORDER BY p.id_perspektif ASC, i.id_indikator ASC";
$res_data = mysqli_query($koneksi, $q_data);
$grouped_kk = [];
while($row = mysqli_fetch_assoc($res_data)) $grouped_kk[$row['nama_subperspektif']][] = $row;

$label_periode = ($bulan == 1)
    ? $nama_bulan_long[$bulan] . " " . $tahun
    : "Januari - " . $nama_bulan_long[$bulan] . " " . $tahun;

// Calculate signature date if not provided
if (!isset($tgl_pbg)) {
    $cabang_display = $u['nama_cabang'] ?? '-';
    if (stripos($cabang_display, 'Kapten A. Rivai') !== false) { $cabang_display = 'Palembang'; }
    $cabang_display = ucwords(strtolower($cabang_display));
    $tgl_pbg = $cabang_display . ", " . $nama_bulan_long[$bulan] . " " . $tahun;
}

// Output HTML Start
$html_kk = '
    <style>
        body, table, td, th, div, p, span, small, label { font-family: Calibri, sans-serif !important; }
        .kk-header h2, .kk-header h3 { font-family: Arial, sans-serif !important; }
        .fas, .far, .fab, .fa { font-family: "Font Awesome 6 Free" !important; }
        .kk-container { width: 100%; color: #000; }
        .kk-header { width: 100%; display: block; text-align: center; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px; }
        .kk-header h2 { margin: 0; text-transform: uppercase; font-size: 14px; font-weight: bold; width: 100%; display: block; }
        .kk-header h3 { margin: 3px 0 0; font-size: 11px; font-weight: bold; width: 100%; display: block; }
        
        .info-grid { width: 100%; margin-bottom: 10px; border-collapse: collapse; table-layout: fixed; }
        .info-grid td { vertical-align: top; font-size: 8px; padding: 1px 0; border: none !important; }
        .info-label { width: 100px; font-weight: bold; display: inline-block; }
        
        .tabel-excel { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        .tabel-excel th { background-color: #d9e1f2; border: 1px solid #000; padding: 4px 2px; text-align: center; font-weight: bold; font-size: 7px; vertical-align: middle; }
        .tabel-excel td { border: 1px solid #000; padding: 3px 4px; font-size: 7px; vertical-align: middle; }
        
        .bg-blue { background-color: #d9e1f2; }
        .bg-green { background-color: #e2efda; }
        .bg-grey { background-color: #f2f2f2; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .signature-table { width: 100%; margin-top: 20px; border: none !important; }
        .signature-table td { width: 33.33%; text-align: center; vertical-align: top; border: none !important; font-size: 8px; }
        .sig-name { font-weight: bold; text-decoration: underline; margin-top: 45px; display: block; font-size: 8.5px; }
    </style>

    <div class="kk-container">
        <div class="kk-header">
            <h2>KERTAS KERJA PENILAIAN KINERJA PEGAWAI</h2>
            <h3>PEMIMPIN CABANG</h3>
        </div>

        <table class="info-grid" style="width:100% !important; border-collapse:collapse !important; table-layout:fixed !important;">
            <tr>
                <!-- Sisi Kiri (Nama, NIP, dll) -->
                <td style="width: 59% !important; vertical-align: top; text-align: left; border:none !important;">
                    <div><span class="info-label">Nama</span> : '.($u['nama'] ?? '-').'</div>
                    <div><span class="info-label">NIP</span> : '.($u['NIP'] ?? '-').'</div>
                    <div><span class="info-label">Jabatan</span> : Pemimpin Cabang</div>
                    <div><span class="info-label">Unit Kerja</span> : '.ucwords(strtolower($u['nama_cabang'] ?? '-')).'</div>
                </td>
                <!-- Sisi Kanan (Periode, dll) - Lurus di atas kolom NILAI -->
                <td style="width: 41% !important; vertical-align: top; text-align: left; border:none !important;">
                    <div><span class="info-label">Periode Penilaian</span> : '.$label_periode.'</div>
                    <div><span class="info-label">Penilai</span> : '.($u['atasan_langsung'] ?? 'Ika Puspitasari').'</div>
                    <div><span class="info-label">Atasan Penilai</span> : '.($u['atasan_dari_atasan_langsung'] ?? 'Marzuki').'</div>
                    <div><span class="info-label">Tanggal Menjabat</span> : '.($u['tanggal_menjabat'] ?? '-').'</div>
                </td>
            </tr>
        </table>

        <table class="tabel-excel">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">NO</th>
                    <th rowspan="2" style="width: 26%;">KPI</th>
                    <th rowspan="2" style="width: 11%;">TARGET</th>
                    <th rowspan="2" style="width: 11%;">REALISASI</th>
                    <th rowspan="2" style="width: 8%;">PENCAPAIAN</th>
                    <th rowspan="2" style="width: 4%;">NILAI</th>
                    <th colspan="2" style="width: 9%;">BOBOT</th>
                    <th rowspan="2" style="width: 8%;">JUMLAH</th>
                    <th rowspan="2" style="width: 8%;">NILAI RATA-RATA</th>
                    <th rowspan="2" style="width: 8%;" class="bg-green">NILAI KINERJA PA</th>
                </tr>
                <tr>
                    <th class="bg-blue">RINCI</th>
                    <th class="bg-blue">TOTAL</th>
                </tr>
            </thead>
            <tbody>';

            $no_kk = 1; $grand_total_pa = 0;
            foreach($grouped_kk as $subp => $inds) {
                $bobot_subp = 0; foreach($inds as $i) $bobot_subp += $i['bobot'];
                $nilai_rata_subp = 0; $rows_html = "";
                
                foreach($inds as $ind) {
                    // HITUNG TARGET SMART (Monthly - bukan YTD lagi agar sesuai ekspektasi user)
                    $target_p = calculate_target_period($koneksi, (int)$ind['id_indikator'], $id_cabang, $tahun, $bulan);
                    
                    $real_p = $ind['total_realisasi'] ?: 0;
                    
                    // Use centralized helper functions
                    $penc = calculate_pencapaian($ind['nama_indikator'], (float)$target_p, (float)$real_p, (int)$ind['terbalik'], (string)$ind['satuan']);
                    $skor = calculate_skor_indikator($koneksi, (int)$ind['id_indikator'], (float)$penc);
                    // Jika target 0, kolom pencapaian dikosongkan (tidak bisa dihitung %)
                    $penc_display = ((float)$target_p == 0) ? '-' : number_format($penc, 2, ',', '.').'%';

                    $jml = $skor * $ind['bobot'];
                    $nilai_rata_ind = ($bobot_subp > 0) ? ($ind['bobot'] / $bobot_subp) * $skor : 0;
                    $nilai_rata_subp += $nilai_rata_ind;
                    $s = trim(strtolower($ind['satuan']));
                    $suffix = (stripos($s, 'persen') !== false || $s == '%' || stripos($ind['nama_indikator'], 'Rate') !== false) ? '%' : '';
                    
                    // Hidden inputs for Web Saving functionality
                    $hidden_inputs = "";
                    if (isset($is_web) && $is_web) {
                        $hidden_inputs = "
                            <input type='hidden' name='indikator_ids[]' value='{$ind['id_indikator']}'>
                            <input type='hidden' name='target[{$ind['id_indikator']}]' value='{$target_p}'>
                            <input type='hidden' name='realisasi[{$ind['id_indikator']}]' value='{$real_p}'>
                            <input type='hidden' name='pencapaian[{$ind['id_indikator']}]' value='{$penc}'>
                            <input type='hidden' name='skor[{$ind['id_indikator']}]' value='{$skor}'>
                            <input type='hidden' name='bobot[{$ind['id_indikator']}]' value='{$ind['bobot']}'>
                            <input type='hidden' name='jumlah[{$ind['id_indikator']}]' value='{$jml}'>
                            <input type='hidden' name='rata_rata[{$ind['id_indikator']}]' value='{$nilai_rata_ind}'>
                        ";
                    }

                    $rows_html .= '<tr>
                        <td></td>
                        <td style="padding-left: 15px;">
                            '.$ind['nama_indikator'].'
                            '.$hidden_inputs.'
                        </td>
                        <td class="text-right">'.number_format($target_p, 2, ',', '.').$suffix.'</td>
                        <td class="text-right">'.number_format($real_p, 2, ',', '.').$suffix.'</td>
                        <td class="text-center">'.$penc_display.'</td>
                        <td class="text-center font-bold">'.$skor.'</td>
                        <td class="text-center">'.number_format($ind['bobot'], 2, ',', '.').'%</td>
                        <td></td>
                        <td class="text-center">'.number_format($jml, 2, ',', '.').'</td>
                        <td class="text-center">'.number_format($nilai_rata_ind, 2, ',', '.').'</td>
                        <td></td>
                    </tr>';
                }
                
                $nilai_pa_subp = $nilai_rata_subp * ($bobot_subp / 100);
                $grand_total_pa += $nilai_pa_subp;
                
                $html_kk .= '<tr class="bg-grey">
                    <td class="text-center">'.$no_kk++.'</td>
                    <td>'.$subp.'</td>
                    <td></td><td></td><td></td><td></td><td></td>
                    <td class="text-center">'.number_format($bobot_subp, 2, ',', '.').'%</td>
                    <td></td>
                    <td class="text-center">'.number_format($nilai_rata_subp, 2, ',', '.').'</td>
                    <td class="text-center bg-green font-bold">'.number_format($nilai_pa_subp, 2, ',', '.').'</td>
                </tr>' . $rows_html;
            }

            $html_kk .= '
                <tr class="bg-grey" style="font-size: 8px;">
                    <td colspan="6" class="text-right" style="padding-right: 15px;">TOTAL</td>
                    <td class="text-center">100,00%</td>
                    <td class="text-center">100,00%</td>
                    <td></td>
                    <td></td>
                    <td class="text-center bg-green font-bold" style="font-size: 10px;">
                        '.number_format($grand_total_pa, 2, ',', '.').'
                        '.( (isset($is_web) && $is_web) ? "<input type='hidden' name='total_kinerja' value='{$grand_total_pa}'>" : "" ).'
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="signature-table" style="width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 10px;">
            <tr>
                <td style="width: 33.3%; text-align: center; vertical-align: top;">
                    <div style="height: 15px;"></div> <!-- Spacer biar sejajar -->
                    Atasan Penilai,<br><br><br><br>
                    <span class="sig-name">'.($u['atasan_dari_atasan_langsung'] ?? 'Marzuki').'</span>
                    Direktur Bisnis
                </td>
                <td style="width: 33.3%; text-align: center; vertical-align: top;">
                    <div style="height: 15px;"></div> <!-- Spacer biar sejajar -->
                    Penilai,<br><br><br><br>
                    <span class="sig-name">'.($u['atasan_langsung'] ?? 'Ika Puspitasari').'</span>
                    Pemimpin Divisi
                </td>
                <td style="width: 33.3%; text-align: center; vertical-align: top;">
                    '.$tgl_pbg.'<br>
                    Pegawai Yang Dinilai,<br><br><br><br>
                    <span class="sig-name">'.($u['nama'] ?? '-').'</span>
                    Pemimpin Cabang
                </td>
            </tr>
        </table>
    </div>';
$html_kk = '<div class="sheet-area">' . $html_kk . '</div>';
?>


