<div class="sidebar">
    <div class="sidebar-header">
        <h3>KPI BSB</h3>
    </div>
    <div class="nav-menu">
        <!-- Menu Universal (Semua Role) -->
        <?php 
        /** @var mysqli $koneksi */
        
        // --- RESOLUSI ID USER PINCA BERDASARKAN TAHUN AKTIF ---
        $tahun_filter_side = isset($_GET['filter_tahun']) ? (int)$_GET['filter_tahun'] : (isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y'));
        $bulan_filter_side = isset($_GET['filter_bulan']) ? (int)$_GET['filter_bulan'] : (isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m'));

        if (isset($_SESSION['jabatan']) && $_SESSION['jabatan'] == 'Pemimpin Cabang') {
            $q_u_year = "SELECT id_user FROM users WHERE id_cabang = " . (int)$_SESSION['id_cabang'] . " AND jabatan = 'Pemimpin Cabang' AND tahun = $tahun_filter_side LIMIT 1";
            $res_u_year = mysqli_query($koneksi, $q_u_year);
            if ($res_u_year && mysqli_num_rows($res_u_year) > 0) {
                $row_u_year = mysqli_fetch_assoc($res_u_year);
                $_SESSION['id_user'] = (int)$row_u_year['id_user'];
            }
        }

        $current_page = basename($_SERVER['PHP_SELF']); 
        // Deteksi folder saat ini untuk menyesuaikan link
        $current_dir = basename(dirname($_SERVER['PHP_SELF']));
        $path_pinca = ($current_dir == 'pinca') ? '' : '../pinca/';
        $path_divisi = ($current_dir == 'divisi') ? '' : '../divisi/';
        $path_direksi = ($current_dir == 'direksi') ? '' : '../direksi/';
        $path_komponen = ($current_dir == 'komponen') ? '' : '../komponen/';

        // Global check profil untuk Pinca (digunakan untuk lock dashboard & menu)
        $is_profil_lengkap = true;
        ?>
        <?php 
        $base_beranda = $path_pinca . "beranda.php";
        if ($_SESSION['jabatan'] == 'Divisi') {
            $base_beranda = $path_divisi . "beranda.php";
        } elseif ($_SESSION['jabatan'] == 'Direksi') {
            $base_beranda = $path_direksi . "beranda.php";
        }
        ?>
        <a href="<?php echo $base_beranda; ?><?php 
            if ($_SESSION['jabatan'] == 'Pemimpin Cabang') {
                echo '?filter_tahun='.$tahun_filter_side.'&filter_bulan='.$bulan_filter_side;
            } elseif ($_SESSION['jabatan'] == 'Divisi') {
                echo '?tahun='.$tahun_filter_side;
            }
        ?>" class="nav-item <?php echo ($current_page == 'beranda.php') ? 'aktif' : ''; ?>">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>

        <!-- Profil Saya: tampil di bawah Dashboard khusus Pinca -->
        <?php if ($_SESSION['jabatan'] == 'Pemimpin Cabang'): ?>
            <a href="<?php echo $path_pinca; ?>profil_saya.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'profil_saya.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-user-circle"></i> Profil Saya
            </a>
        <?php endif; ?>

        <!-- Menu Khusus DIREKSI -->
        <?php if ($_SESSION['jabatan'] == 'Direksi'): ?>
            <div style="padding: 15px 20px 5px; font-size: 0.7em; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Menu Direksi</div>
            <a href="<?php echo $path_direksi; ?>monitoring_cabang.php" class="nav-item <?php echo ($current_page == 'monitoring_cabang.php' || $current_page == 'monitoring_detail.php' || $current_page == 'monitoring_grafik.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-desktop"></i> Monitoring Cabang
            </a>
        <?php endif; ?>

        <!-- Menu Khusus DIVISI -->
        <?php if ($_SESSION['jabatan'] == 'Divisi'): ?>
            <a href="<?php echo $path_divisi; ?>daftar_pinca.php?filter_tahun=<?php echo $tahun_filter_side; ?>" class="nav-item <?php echo ($current_page == 'daftar_pinca.php' || $current_page == 'profil_pinca.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-user-gear"></i> Pemimpin Cabang
            </a>
            <div style="padding: 15px 20px 5px; font-size: 0.7em; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Manajemen Kontrak</div>
            <a href="<?php echo $path_divisi; ?>kpi_tambah.php?filter_tahun=<?php echo $tahun_filter_side; ?>" class="nav-item <?php echo ($current_page == 'kpi_tambah.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-circle-plus"></i> Manajemen KPI
            </a>
            <a href="<?php echo $path_divisi; ?>kpi_daftar.php?filter_tahun=<?php echo $tahun_filter_side; ?>" class="nav-item <?php echo ($current_page == 'kpi_daftar.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-file-signature"></i> Daftar KPI
            </a>
            <a href="<?php echo $path_divisi; ?>target_cabang.php?filter_tahun=<?php echo $tahun_filter_side; ?>" class="nav-item <?php echo ($current_page == 'target_cabang.php' || $current_page == 'target_form.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-bullseye"></i> Target Cabang
            </a>
            <a href="<?php echo $path_divisi; ?>realisasi_cabang.php?filter_tahun=<?php echo $tahun_filter_side; ?>" class="nav-item <?php echo ($current_page == 'realisasi_cabang.php' || $current_page == 'realisasi_form.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-chart-line"></i> Realisasi KPI
            </a>
            <a href="<?php echo $path_divisi; ?>monitoring_cabang.php?filter_tahun=<?php echo $tahun_filter_side; ?>" class="nav-item <?php echo ($current_page == 'monitoring_cabang.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-desktop"></i> Monitoring Cabang
            </a>
            <a href="<?php echo $path_divisi; ?>persetujuan_laporan.php?tahun=<?php echo $tahun_filter_side; ?>" class="nav-item <?php echo ($current_page == 'persetujuan_laporan.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-stamp"></i> Persetujuan Laporan
            </a>
            <a href="<?php echo $path_divisi; ?>sanggah_cabang.php" class="nav-item <?php echo ($current_page == 'sanggah_cabang.php') ? 'aktif' : ''; ?>">
                <i class="fas fa-circle-exclamation"></i> Sanggah Data
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['jabatan'] == 'Pemimpin Cabang'): ?>
            <div style="padding: 15px 20px 5px; font-size: 0.7em; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Menu Cabang</div>

                <a href="<?php echo $path_pinca; ?>target_saya.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'target_saya.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-bullseye"></i> Target Cabang
                </a>

                <a href="<?php echo $path_pinca; ?>kontrak_kerja.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'kontrak_kerja.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-file-contract"></i> Kontrak Kerja
                </a>

                <a href="<?php echo $path_pinca; ?>realisasi_saya.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'realisasi_saya.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Capaian Realisasi
                </a>
                
                <a href="<?php echo $path_pinca; ?>kertas_kerja.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'kertas_kerja.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-file-invoice"></i> Kertas Kerja (KPI)
                </a>

                <a href="<?php echo $path_pinca; ?>formulir_penilaian.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'formulir_penilaian.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-file-circle-check"></i> Formulir Penilaian
                </a>

                <a href="<?php echo $path_pinca; ?>penilaian_kompetensi.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'penilaian_kompetensi.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-brain"></i> Penilaian Kompetensi
                </a>

                <a href="<?php echo $path_pinca; ?>penilaian_akhir.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'penilaian_akhir.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i> Penilaian Akhir
                </a>

                <a href="<?php echo $path_pinca; ?>upload_laporan.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'upload_laporan.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-cloud-arrow-up"></i> Upload Laporan
                </a>

                <a href="<?php echo $path_pinca; ?>sanggah_data.php?filter_tahun=<?php echo $tahun_filter_side; ?>&filter_bulan=<?php echo $bulan_filter_side; ?>" class="nav-item <?php echo ($current_page == 'sanggah_data.php') ? 'aktif' : ''; ?>">
                    <i class="fas fa-circle-exclamation"></i> Sanggah Data
                </a>
        <?php endif; ?>

        <!-- Keluar (Universal) -->
        <a href="javascript:void(0)" onclick="tampilModalLogout()" class="nav-item" style="margin-top: 50px; color: #ffabaf;">
            <i class="fas fa-right-from-bracket"></i> Keluar
        </a>
    </div>
</div>

<!-- Modal Konfirmasi Logout -->
<div id="modalLogout" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(3px); align-items: center; justify-content: center; animation: fadeIn 0.2s ease-out;">
    <div style="background: white; padding: 32px; border-radius: 20px; width: 360px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.15); animation: slideUp 0.3s ease-out;">
        <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #fff5f5, #fed7d7); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 4px 10px rgba(245, 101, 101, 0.2);">
            <i class="fas fa-power-off" style="font-size: 1.8em; color: #e53e3e;"></i>
        </div>
        <h3 style="margin: 0 0 8px; color: #1a202c; font-size: 1.25em; font-weight: 700;">Keluar Aplikasi?</h3>
        <p style="margin: 0 0 24px; color: #718096; font-size: 0.95em; line-height: 1.5;">Sesi Anda akan diakhiri. Pastikan semua pekerjaan telah disimpan.</p>
        <div style="display: flex; gap: 12px;">
            <button onclick="tutupModalLogout()" style="flex: 1; padding: 12px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f7fafc; color: #4a5568; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#edf2f7'" onmouseout="this.style.background='#f7fafc'">Batal</button>
            <button onclick="eksekusiLogout()" style="flex: 1; padding: 12px; border-radius: 10px; border: none; background: #e53e3e; color: white; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px rgba(229, 62, 62, 0.2);" onmouseover="this.style.background='#c53030'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#e53e3e'; this.style.transform='translateY(0)'">Ya, Keluar</button>
        </div>
    </div>
</div>
<style>
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function pincaAlertWajibProfil() {
    Swal.fire({
        icon: 'warning',
        title: 'Akses Dibatasi',
        text: 'Mohon lengkapi biodata Anda terlebih dahulu di menu "Profil Saya" untuk membuka akses Dashboard dan fitur lainnya.',
        confirmButtonColor: '#0245a3',
        confirmButtonText: 'Lengkapi Sekarang',
        showCancelButton: true,
        cancelButtonText: 'Nanti Saja'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?php echo $path_pinca; ?>profil_saya.php';
        }
    });
}

function tampilModalLogout() {
    document.getElementById('modalLogout').style.display = 'flex';
}

function tutupModalLogout() {
    document.getElementById('modalLogout').style.display = 'none';
}

function eksekusiLogout() {
    window.location.href = '../../fungsi/auth/kpi_auth_manajemen.php?aksi=logout';
}

// Tutup modal jika klik di luar area modal
window.onclick = function(event) {
    const modal = document.getElementById('modalLogout');
    if (event.target == modal) {
        tutupModalLogout();
    }
}
</script>
<script src="../../aset/js/blueprint.js"></script>




