<?php
// auth/login.php
require_once '../includes/functions.php'; // functions.php sudah include db_connect.php

// Jika sudah login, redirect ke halaman sesuai role
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

$error = '';
$success = '';

// Cek jika ada pesan sukses dari registrasi
if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $db->escape($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validasi input tidak kosong
    if (empty($username) || empty($password)) {
        $error = "Username/Email dan password harus diisi!";
    } else {
        // Query cek user
        $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['foto_profil'] = $user['foto_profil'];
                
                // Remember me (30 hari)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    // Simpan token ke database (implementasi sesuai kebutuhan)
                }
                
                // Redirect sesuai role
                if ($user['role'] == 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../index.php");
                }
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username/Email tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Tambahan CSS untuk tombol kembali */
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ff6b35;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            background: white;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .back-home:hover {
            background: #ff6b35;
            color: white;
            transform: translateX(-5px);
        }
        
        .back-home i {
            font-size: 16px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .back-home {
                top: 10px;
                left: 10px;
                padding: 5px 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="auth-page">
    <!-- Tombol Kembali ke Beranda -->
    <a href="../index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> 
        <span>Kembali ke Beranda</span>
    </a>

    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Login ke akun Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" data-validate="true">
                <div class="form-group">
                    <label for="username">Username atau Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group" style="display: flex; align-items: center;">
                    <input type="checkbox" name="remember" id="remember" style="width: auto; margin-right: 10px;">
                    <label for="remember" style="display: inline; margin: 0;">Ingat saya</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>
            
            <div class="auth-footer">
                <p><a href="lupa_password.php">Lupa password?</a></p>
                <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
            </div>

            <!-- Tambahan Login dengan Google -->
            <div style="display: flex; align-items: center; text-align: center; margin: 20px 0;">
                <hr style="flex: 1; border: none; border-top: 1px solid #ddd;">
                <span style="padding: 0 10px; color: #777; font-size: 14px;">Atau lanjutkan dengan</span>
                <hr style="flex: 1; border: none; border-top: 1px solid #ddd;">
            </div>

            <a href="google_login.php" class="btn btn-block" style="background-color: white; color: #444; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s;">
                <img src="https://www.google.com/favicon.ico" alt="Google" style="width: 20px; height: 20px;">
                Google
            </a>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>