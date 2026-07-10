<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - KPI Bank Sumsel Babel</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Custom -->
    <link rel="stylesheet" href="aset/css/gaya.css">
</head>

<body>

    <div class="split-login-container">
        <!-- Sisi Kiri: Visual/Branding -->
        <div class="login-visual">
            <div class="visual-content">
                <h1 style="font-size: 4rem; font-weight: 800; text-transform: uppercase; letter-spacing: -1.5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); line-height: 1.1; margin-bottom: 25px;">
                    <span style="color: var(--biru-muda); display: block;">Key Performance</span>
                    <span style="color: white;">Indicator</span>
                </h1>
                <p style="font-style: italic; font-weight: 300; line-height: 1.6; letter-spacing: 0.5px; opacity: 0.9;">
                    <strong style="color: white; font-weight: 600; font-size: 1.1em; display: block; margin-bottom: 5px; border-left: 3px solid var(--biru-muda); padding-left: 15px;">Change to Accelerate :</strong>
                    <span style="padding-left: 18px; display: block;">Transforming Performance Through Innovation and Efficiency.</span>
                </p>
            </div>
        </div>

        <!-- Sisi Kanan: Form Login -->
        <div class="login-form-side">
            <div class="login-form-box">
                <div class="login-header">
                    <h2>Masuk Ke Akun</h2>
                    <p>Silakan masuk ke Website Key Performance Indicator Bank Sumsel Babel untuk Monitoring dan Evaluasi Kinerja.</p>
                </div>

                <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'gagal'): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Username atau Password salah!</span>
                    </div>
                <?php endif; ?>

                <form action="fungsi/auth/kpi_auth_manajemen.php" method="POST">
                    <div class="input-grup">
                        <label for="username">Username</label>
                        <div class="input-icon-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="username" id="username" class="input-kontrol" placeholder="Username Anda" required autocomplete="off">
                        </div>
                    </div>

                    <div class="input-grup">
                        <label for="password">Password</label>
                        <div class="input-icon-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" class="input-kontrol" placeholder="Password Anda" required style="padding-right: 45px;">
                            <i class="fas fa-eye-slash" id="togglePassword" style="position: absolute; right: 15px; left: auto; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; z-index: 10; font-size: 0.95em;"></i>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" class="tombol tombol-utama" style="width: 100%; height: 50px; border-radius: 8px; font-size: 1em;">
                            <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
                        </button>
                    </div>
                </form>

                <div style="text-align: center; margin-top: 40px; font-size: 0.8em; color: #bbb;">
                    &copy; 2026 Bank Sumsel Babel. <br> Dikembangkan untuk Laporan KP.
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            
            // Adjust color slightly when active for better UX
            if(type === 'text') {
                this.style.color = '#3182ce';
            } else {
                this.style.color = '#a0aec0';
            }
        });
    </script>
    <script src="aset/js/blueprint.js"></script>
</body>

</html>