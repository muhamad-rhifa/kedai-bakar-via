<?php
// auth/lupa_password.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $db->escape($_POST['email']);
    
    // Cek apakah email terdaftar
    $check = mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE email = '$email'");
    
    if (mysqli_num_rows($check) > 0) {
        $user = mysqli_fetch_assoc($check);
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Simpan token ke database
        mysqli_query($conn, "UPDATE users SET reset_token = '$token', reset_expiry = '$expiry' WHERE email = '$email'");
        
        // Kirim email (simulasi)
        $reset_link = BASE_URL . "auth/reset_password.php?token=" . $token;
        
        // Tampilkan link (untuk demo)
        $success = "Link reset password telah dikirim ke email Anda.<br>";
        $success .= "<strong>Demo Link:</strong> <a href='$reset_link'>$reset_link</a>";
    } else {
        $error = "Email tidak terdaftar!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1>Lupa Password</h1>
                <p>Masukkan email Anda untuk mereset password</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Kirim Link Reset
                </button>
            </form>
            
            <div class="auth-footer">
                <p><a href="login.php">Kembali ke Login</a></p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
</body>
</html>