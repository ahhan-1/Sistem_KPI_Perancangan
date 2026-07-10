<?php
/**
 * TEMPLATE: KONTRAK KERJA
 * @var array $u
 * @var int $tahun
 * @var array $data_kontrak
 * @var float $total_bobot_kpi
 * @var string $grade
 * @var string $lokasi_kerja
 */

$html_kk_raw = '
    <style>
        .sheet-area, .sheet-area table, .sheet-area td, .sheet-area th, .sheet-area div, .sheet-area p { font-family: "Segoe UI", Arial, sans-serif; }
        
        /* Ubah semua font-weight bold menjadi Semi-Bold (600) agar tidak terlalu tebal */
        .sheet-area strong, 
        .sheet-area b, 
        .sheet-area th, 
        .sheet-area [style*="font-weight: bold"], 
        .sheet-area [style*="font-weight:bold"] {
            font-weight: 600 !important;
        }
        .contract-header-k { text-align: center; margin-bottom: 15px; }
        .contract-header-k .title-k, .contract-header-k .subtitle-k { font-family: Arial, sans-serif !important; width: 100%; display: block; text-align: center; }
        .contract-header-k .title-k { font-weight: bold; font-size: 18px; margin-bottom: 3px; text-transform: uppercase; }
        .contract-header-k .subtitle-k { font-weight: bold; font-size: 13px; margin-bottom: 3px; }
        .contract-header-k .periode-k { font-weight: bold; font-size: 11px; margin-bottom: 10px; font-family: Arial, sans-serif !important; }
        
        .paper-kontrak {
            background: white;
            padding: 5px;
            color: #000;
            line-height: 1.15;
            position: relative;
        }

        .form-info-k {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78em;
            margin-bottom: 8px;
        }
        .form-info-k td { padding: 2px 6px; border: 1px solid #000; }
        .bg-label-k { background-color: #f2f2f2; width: 22%; white-space: nowrap; }

        .excel-table-k {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.72em;
            border: 1px solid #000;
        }
        .excel-table-k th {
            background: #A2D8FA;
            color: #000;
            padding: 4px 2px;
            border: 1px solid #000;
            text-transform: uppercase;
            font-weight: bold;
        }
        .excel-table-k td { border: 1px solid #000; vertical-align: middle; padding: 1.5px 3px; }
        
        .row-perspektif-k { background: #f2f2f2; font-weight: bold; }
        .row-perspektif-k td { border-bottom: 1px solid #000 !important; font-size: 0.95em; }
        .row-sub-k { background: #f9f9f9; font-weight: bold; }
        
        .sub-total-bobot-k { font-weight: bold; background: #fffde7; }
        .sub-total-bobot-k td { border-top: 1px solid #000 !important; padding: 3px 8px; }
        
        .total-bobot-kpi-k { background: #A2D8FA; color: #000; font-weight: bold; text-align: center; }

        .footer-signatures-k {
            margin-top: 25px;
            width: 100%;
            font-size: 0.85em;
        }
        .sign-area-k { height: 50px; }
        .sign-name-k { font-weight: bold; margin-bottom: 4px; }
        .sign-name-k u { text-decoration: underline; font-weight: bold; }

        @media print {
            .excel-table-k th { background: #A2D8FA !important; color: #000 !important; -webkit-print-color-adjust: exact; }
            .row-perspektif-k { background: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
            .sub-total-bobot-k, .sub-total-bobot-k td { background: #fffde7 !important; -webkit-print-color-adjust: exact; }
            .total-bobot-kpi-k { background: #A2D8FA !important; color: #000 !important; -webkit-print-color-adjust: exact; }
        }
    </style>

    <div class="paper-kontrak">
        <div class="contract-header-k">
            <span class="title-k">PT. BANK PEMBANGUNAN DAERAH SUMATERA SELATAN BANGKA BELITUNG</span>
            <span class="title-k" style="font-size: 15px;">FORMULIR KONTRAK KINERJA CABANG ' . strtoupper($lokasi_kerja) . '</span>
            <span class="title-k" style="font-size: 15px;">TAHUN ' . $tahun . '</span>
        </div>

        <div style="text-align: right; font-size: 0.75em; font-weight: bold; margin-bottom: 3px;">Kontrak Kinerja ' . $label_tahunan . '</div>
        <table class="form-info-k">
            <tr>
                <td class="bg-label-k">Kontrak Awal</td>
                <td width="30%">1 Januari s/d 31 Desember ' . $tahun . '</td>
                <td class="bg-label-k">Pangkat</td>
                <td width="30%">' . ($u['pangkat'] ?? '...') . '</td>
            </tr>
            <tr>
                <td class="bg-label-k">Nama Pemegang Kontrak</td>
                <td>' . ($u['nama'] ?? '...') . '</td>
                <td class="bg-label-k">Level / KIP</td>
                <td>' . $grade . '</td>
            </tr>
            <tr>
                <td class="bg-label-k">Jabatan</td>
                <td>' . ($jabatan_display ?? $_SESSION['jabatan'] ?? '...') . '</td>
                <td class="bg-label-k">Tanggal Menjabat</td>
                <td>' . ($u['tanggal_menjabat'] ?? '...') . '</td>
            </tr>
            <tr>
                <td class="bg-label-k">Lokasi Kerja</td>
                <td>' . $lokasi_kerja . '</td>
                <td class="bg-label-k">Atasan Langsung</td>
                <td>' . ($u['atasan_langsung'] ?? '...') . '</td>
            </tr>
            <tr>
                <td class="bg-label-k">Direktorat</td>
                <td>' . ($u['direktorat'] ?? '...') . '</td>
                <td class="bg-label-k">Atasan dari Atasan Langsung</td>
                <td>' . ($u['atasan_dari_atasan_langsung'] ?? '...') . '</td>
            </tr>
        </table>

        <p style="font-size: 0.8em; font-weight: bold; margin-bottom: 1px; text-transform: uppercase;">TUGAS DAN HASIL KERJA</p>
        <p style="font-size: 0.68em; color: #555; margin-bottom: 8px;">Pedoman Penetapan Penilaian Kinerja Faktor</p>

        <table class="excel-table-k">
            <thead>
                <tr style="background: #A2D8FA; color: #000; text-align: center; font-weight: bold;">
                    <th width="12%" style="border: 1px solid #000; color: #000;">PERSPEKTIF</th>
                    <th width="3%" style="border: 1px solid #000; color: #000;"></th>
                    <th width="25%" style="border: 1px solid #000; color: #000;">SASARAN STRATEGIS</th>
                    <th width="35%" style="border: 1px solid #000; color: #000;">KPI</th>
                    <th width="8%" style="border: 1px solid #000; color: #000;">SATUAN</th>
                    <th width="10%" style="border: 1px solid #000; color: #000;">TARGET</th>
                    <th width="7%" style="border: 1px solid #000; color: #000;">BOBOT</th>
                </tr>
            </thead>
            <tbody>';
$no_sasaran = 1;
foreach ($data_kontrak as $p_name => $p_data) {
    $sasarans = $p_data['sasaran'];
    $total_rows_p = 0;
    foreach ($sasarans as $s_name => $subs) {
        foreach ($subs as $sub_name => $indikators) {
            $total_rows_p += count($indikators);
            if (!empty($sub_name))
                $total_rows_p++; // Baris header sub-perspektif
        }
    }

    $first_p = true;
    foreach ($sasarans as $s_name => $subs) {
        $total_rows_s = 0;
        foreach ($subs as $sub_name => $indikators) {
            $total_rows_s += count($indikators);
            if (!empty($sub_name))
                $total_rows_s++;
        }

        $first_s = true;
        foreach ($subs as $sub_name => $indikators) {
            // Cetak baris header Sub-Perspektif jika ada
            if (!empty($sub_name)) {
                $html_kk_raw .= '<tr>';
                if ($first_p) {
                    $html_kk_raw .= '<td rowspan="' . $total_rows_p . '" style="text-align: center; font-weight: bold; text-transform: uppercase; vertical-align: middle;">' . $p_name . '</td>';
                    $first_p = false;
                }
                if ($first_s) {
                    $html_kk_raw .= '<td rowspan="' . $total_rows_s . '" style="text-align: center; vertical-align: middle;">' . $no_sasaran++ . '</td>';
                    $html_kk_raw .= '<td rowspan="' . $total_rows_s . '" style="padding: 10px; vertical-align: middle; text-transform: uppercase;">' . $s_name . '</td>';
                    $first_s = false;
                }
                $html_kk_raw .= '<td colspan="4" style="background: #f2f2f2; font-weight: bold; padding-left: 5px;">' . $sub_name . '</td>';
                $html_kk_raw .= '</tr>';
            }

            foreach ($indikators as $ind) {
                $html_kk_raw .= '<tr>';
                if ($first_p) {
                    $html_kk_raw .= '<td rowspan="' . $total_rows_p . '" style="text-align: center; font-weight: bold; text-transform: uppercase; vertical-align: middle;">' . $p_name . '</td>';
                    $first_p = false;
                }
                if ($first_s) {
                    $html_kk_raw .= '<td rowspan="' . $total_rows_s . '" style="text-align: center; vertical-align: middle;">' . $no_sasaran++ . '</td>';
                    $html_kk_raw .= '<td rowspan="' . $total_rows_s . '" style="padding: 10px; vertical-align: middle; text-transform: uppercase;">' . $s_name . '</td>';
                    $first_s = false;
                }

                $s = trim(strtolower($ind['satuan']));
                $suffix = ($s == 'persen' || $s == '%') ? '%' : '';
                $target_val = !is_null($ind['target']) ? number_format($ind['target'], 2, ',', '.') . $suffix : '0,00' . $suffix;

                $satuan_display = $ind['satuan'];
                if (strtolower($satuan_display) == 'rupiah')
                    $satuan_display = 'Rp';
                if (strtolower($satuan_display) == 'persen')
                    $satuan_display = '%';

                $html_kk_raw .= '<td style="padding-left: 10px;">' . $ind['nama_indikator'] . '</td>';
                $html_kk_raw .= '<td style="text-align: center;">' . $satuan_display . '</td>';
                $html_kk_raw .= '<td style="text-align: right; padding-right: 8px;">' . $target_val . '</td>';
                $html_kk_raw .= '<td style="text-align: center;">' . number_format($ind['bobot'], 2, ',', '.') . '%</td>';
                $html_kk_raw .= '</tr>';
            }
        }
    }
}

$html_kk_raw .= '
                <tr style="font-weight: bold; background: #A2D8FA; color: #000;">
                    <td colspan="6" style="text-align: center; padding: 5px; color: #000;">Total</td>
                    <td style="text-align: center; color: #000;">' . number_format($total_bobot_kpi, 2, ',', '.') . '%</td>
                </tr>
            </tbody>
        </table>



        <div style="margin-top: 10px; font-size: 0.75em; font-weight: bold;">
            <p>TANGGAL PERSETUJUAN KONTRAK KERJA : ' . $bulan_nama . ' ' . $tahun . '</p>
        </div>

        <!-- Tanda Tangan Sesuai Gambar 2 - Struktur 5-Kolom Konsisten -->
        <table class="footer-signatures-k" style="margin-top: 15px; width: 100%; border-collapse: collapse; font-size: 0.8em;">
            <!-- Baris 1: Pinca dan Pemdiv (Mengambil 2 kolom di kiri dan 2 di kanan) -->
            <tr>
                <td colspan="2" style="width: 40%; vertical-align: top; text-align: center;">
                    <p style="font-weight: bold; margin-bottom: 0;">Cabang ' . $lokasi_kerja . '</p>
                    <div style="height: 65px;"></div>
                    <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">' . ($u['nama'] ?? '..........................') . '</p>
                    <p style="margin-top: 3px;">Pemimpin Cabang ' . $lokasi_kerja . '</p>
                </td>
                <td style="width: 20%;"></td>
                <td colspan="2" style="width: 40%; vertical-align: top; text-align: center;">
                    <p style="font-weight: bold; margin-bottom: 0;">Menyetujui,</p>
                    <div style="height: 65px;"></div>
                    <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">' . ($u['atasan_langsung'] ?? '..........................') . '</p>
                    <p style="margin-top: 3px;">Pemimpin Divisi Bisnis Ritel, Konsumer dan UMKM</p>
                </td>
            </tr>

            <!-- Baris 2: Mengetahui (Full Width) -->
            <tr>
                <td colspan="5" style="text-align: center; padding: 15px 0 5px;">
                    <p style="font-weight: bold; margin: 0;">Mengetahui,</p>
                </td>
            </tr>

            <!-- Baris 3: Direktur (Menggunakan 5 kolom untuk efek geser ke tengah) -->
            <tr>
                <td style="width: 15%;"></td>
                <td style="width: 25%; vertical-align: top; text-align: center;">
                    <div style="height: 65px;"></div>
                    <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">' . (!empty($u['direktur_utama']) ? $u['direktur_utama'] : 'Festero Mohamad Pepeko') . '</p>
                    <p style="margin-top: 3px;">Direktur Utama</p>
                </td>
                <td style="width: 20%;"></td>
                <td style="width: 25%; vertical-align: top; text-align: center;">
                    <div style="height: 65px;"></div>
                    <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">' . ($u['atasan_dari_atasan_langsung'] ?? '..........................') . '</p>
                    <p style="margin-top: 3px;">Direktur Bisnis</p>
                </td>
                <td style="width: 15%;"></td>
            </tr>
        </table>
    </div>';

$html_kk_web = '<div class="sheet-area">' . $html_kk_raw . '</div>';
?>