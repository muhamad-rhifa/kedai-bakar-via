<?php
// auth/reset_password.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? $db->escape($_GET['token']) : '';

// Validasi token
if ($token) {
    $check = mysqli_query($conn, "SELECT id, email FROM users 
                                   WHERE reset_token = '$token' 
                                   AND reset_expiry > NOW()");
    
    if (mysqli_num_rows($check) == 0) {
        $error = "Token tidak valid atau sudah kadaluarsa!";
        $token = '';
    }
} else {
    $error = "Token tidak ditemukan!";
}

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password']) && $token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password != $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update = mysqli_query($conn, "UPDATE users 
                                        SET password = '$hashed_password', 
                                            reset_token = NULL, 
                                            reset_expiry = NULL 
                                        WHERE reset_token = '$token'");
        
        if ($update) {
            $success = "Password berhasil direset! Silakan login dengan password baru.";
        } else {
            $error = "Gagal mereset password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>Reset Password</h1>
                <p>Buat password baru Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="auth-footer">
                    <p><a href="login.php">Login sekarang</a></p>
                </div>
            <?php elseif ($token): ?>
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="token" value="<?php echo $token; ?>">
                    
                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <input type="password" id="password" name="password" required>
                        <small>Minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="reset_password" class="btn btn-primary btn-block">
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>