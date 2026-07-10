/**
 * app.js — Global shared functions for KPI BSB system
 * Di-include oleh semua halaman Pinca yang membutuhkan fungsi cetak & navigasi.
 */

/**
 * printViaIframe - Cetak konten dari URL menggunakan hidden iframe.
 * Mencegah tab baru terbuka dan langsung memunculkan dialog print browser.
 * @param {string} url - URL halaman yang akan dicetak
 */
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

/**
 * updateNav - Navigasi ulang halaman saat ini dengan filter periode (bulan/tahun) baru.
 * Otomatis mendeteksi nama halaman dari URL sehingga satu fungsi berlaku di semua halaman.
 * Parameter id_user dan id_cabang dipertahankan jika ada di URL sebelumnya.
 */
function updateNav() {
    const val = document.getElementById('periodePicker').value;
    if (!val) return;
    const parts = val.split('-');
    const thn = parts[0];
    const bln = parseInt(parts[1]);
    const urlParams = new URLSearchParams(window.location.search);
    const idUser = urlParams.get('id_user');
    const idCabang = urlParams.get('id_cabang');

    // Auto-detect nama halaman dari path saat ini
    const page = window.location.pathname.split('/').pop();

    let url = `${page}?filter_tahun=${thn}&filter_bulan=${bln}`;
    if (idUser) url += `&id_user=${idUser}`;
    if (idCabang) url += `&id_cabang=${idCabang}`;

    window.location.href = url;
}
