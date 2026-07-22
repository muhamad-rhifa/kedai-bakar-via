<?php
// includes/header.php
if (!isset($conn)) {
    require_once 'db_connect.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <meta name="description" content="<?php echo APP_NAME; ?> - Nikmati kelezatan bakaran dengan bumbu khas pilihan">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="<?php echo BASE_URL; ?>index.php">
                    <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="<?php echo APP_NAME; ?>" class="logo">
                    <span><?php echo APP_NAME; ?></span>
                </a>
            </div>
            
            <button class="navbar-toggler" id="navbarToggler">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="navbar-menu" id="navbarMenu">
                <ul class="navbar-nav">
                    <li><a href="<?php echo BASE_URL; ?>index.php" class="nav-link">Beranda</a></li>
                    <li><a href="<?php echo BASE_URL; ?>user/index.php" class="nav-link">Menu</a></li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>user/keranjang.php" class="nav-link">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-count" id="cartCount">
                                    <?php echo countKeranjang($_SESSION['user_id']); ?>
                                </span>
                            </a>
                        </li>
                        <li class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle">
                                <i class="fas fa-user"></i>
                                <?php echo $_SESSION['nama_lengkap']; ?>
                                <i class="fas fa-chevron-down"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo BASE_URL; ?>auth/edit_profil.php"><i class="fas fa-user-edit"></i> Edit Profil</a></li>
                                <li><a href="<?php echo BASE_URL; ?>auth/ubah_password.php"><i class="fas fa-key"></i> Ubah Password</a></li>
                                <li><a href="<?php echo BASE_URL; ?>user/pesanan_saya.php"><i class="fas fa-history"></i> Pesanan Saya</a></li>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <li class="dropdown-divider"></li>
                                    <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard Admin</a></li>
                                <?php endif; ?>
                                <li class="dropdown-divider"></li>
                                <li><a href="<?php echo BASE_URL; ?>auth/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>auth/login.php" class="nav-link">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>auth/register.php" class="btn btn-primary">Daftar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>