<?php 
/** @var int $tahun_aktif */
/** @var int $bulan_aktif */
/** @var int $id_cabang */
?>
<form action="aksi/proses_pinca.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="aksi" value="upload_laporan">
    <input type="hidden" name="tahun" value="<?php echo $tahun_aktif; ?>">
    <input type="hidden" name="bulan" value="<?php echo $bulan_aktif; ?>">
    <input type="hidden" name="id_cabang" value="<?php echo $id_cabang; ?>">
    
    <div style="margin-bottom: 25px;">
        <label style="display: block; font-size: 0.95em; font-weight: 700; color: #4a5568; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">1. Pilih Jenis Dokumen</label>
        <select name="jenis_laporan" class="input-kontrol" style="width: 100%; height: 50px; font-size: 1em; border-color: #cbd5e0;" required>
            <option value="laporan">Laporan Lengkap (Bulanan)</option>
            <option value="kontrak">Kontrak Kerja (Tahunan)</option>
        </select>
    </div>

    <div style="margin-bottom: 35px;">
        <label style="display: block; font-size: 0.95em; font-weight: 700; color: #4a5568; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">2. Lampirkan File (PDF)</label>
        <div style="background: #f8fafc; border: 2px dashed #cbd5e0; padding: 30px; border-radius: 12px; text-align: center; position: relative; transition: 0.3s;" id="dropZone">
            <label for="fileLaporan" style="cursor: pointer; display: block;">
                <i class="fas fa-file-pdf fa-3x" style="color: #e53e3e; margin-bottom: 15px;" id="fileIcon"></i>
                <div style="font-weight: 700; color: #2d3748; margin-bottom: 5px;" id="fileNameText">Klik di sini untuk memilih file PDF</div>
                <p style="margin: 0; font-size: 0.85em; color: #a0aec0;">atau tarik file ke sini (Maks 5MB)</p>
            </label>
            <input type="file" id="fileLaporan" name="file_laporan" accept=".pdf" required style="display: none;" onchange="handleFileUpload(this)">
        </div>
    </div>

    <script>
        function handleFileUpload(input) {
            const fileName = input.files[0] ? input.files[0].name : "Klik di sini untuk memilih file PDF";
            const fileNameText = document.getElementById('fileNameText');
            const dropZone = document.getElementById('dropZone');
            const fileIcon = document.getElementById('fileIcon');
            
            fileNameText.innerText = fileName;
            
            if (input.files[0]) {
                dropZone.style.borderColor = '#38a169';
                dropZone.style.background = '#f0fff4';
                fileNameText.style.color = '#2f855a';
                fileIcon.classList.remove('fa-file-pdf');
                fileIcon.classList.add('fa-file-circle-check');
                fileIcon.style.color = '#38a169';
            } else {
                dropZone.style.borderColor = '#cbd5e0';
                dropZone.style.background = '#f8fafc';
                fileNameText.style.color = '#2d3748';
                fileIcon.classList.remove('fa-file-circle-check');
                fileIcon.classList.add('fa-file-pdf');
                fileIcon.style.color = '#e53e3e';
            }
        }
    </script>
    
    <button type="submit" class="tombol tombol-utama" style="width: 100%; padding: 18px; border-radius: 10px; font-weight: 800; font-size: 1.1em; letter-spacing: 1px; box-shadow: 0 4px 6px rgba(43, 87, 151, 0.2);">
        <i class="fas fa-paper-plane" style="margin-right: 10px;"></i> KIRIM DOKUMEN SEKARANG
    </button>
</form>


