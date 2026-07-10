<?php
session_start();
require "../koneksi.php";
/** @var mysqli $koneksi */

// Router Utama
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

// --- 1. Proses Login ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $aksi == '') {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Cari user di database (ambil tahun terbaru)
    $query = "SELECT * FROM users WHERE username = '$username' ORDER BY tahun DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verifikasi password hash
        if (password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['id_user']   = $user['id_user'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['jabatan']   = $user['jabatan'];
            $_SESSION['id_cabang'] = $user['id_cabang'];

            // Redirect berdasarkan jabatan
            switch ($user['jabatan']) {
                case 'Divisi':
                    header("Location: ../../halaman/divisi/beranda.php");
                    break;
                case 'Direksi':
                    header("Location: ../../halaman/direksi/beranda.php");
                    break;
                case 'Pemimpin Cabang':
                    header("Location: ../../halaman/pinca/beranda.php");
                    break;
                default:
                    header("Location: ../../index.php?pesan=gagal");
                    break;
            }
            exit();
        }
    }

    // Jika gagal login
    header("Location: ../../index.php?pesan=gagal");
    exit();
}

// --- 2. Proses Logout ---
if ($aksi == 'logout') {
    session_unset();
    session_destroy();
    header("Location: ../../index.php");
    exit();
}

// Default redirect jika akses tidak valid
header("Location: ../../index.php");
exit();
