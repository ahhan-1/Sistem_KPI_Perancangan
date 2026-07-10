<?php
// Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "kpi_kp_dft";

// Membuat Koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Periksa Koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset ke utf8mb4 agar mendukung karakter khusus
mysqli_set_charset($koneksi, "utf8mb4");

