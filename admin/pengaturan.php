<?php
// admin/pengaturan.php

require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Cek dan tambahkan kolom jika belum ada (gunakan try-catch agar tidak error jika sudah ada)
try { mysqli_query($conn, "ALTER TABLE pengaturan ADD COLUMN promo_title VARCHAR(255) NULL"); } catch (Exception $e) {}
try { mysqli_query($conn, "ALTER TABLE pengaturan ADD COLUMN promo_desc TEXT NULL"); } catch (Exception $e) {}
try { mysqli_query($conn, "ALTER TABLE pengaturan ADD COLUMN promo_discount VARCHAR(50) NULL"); } catch (Exception $e) {}

// Proses update pengaturan
if (isset($_POST['simpan'])) {
    $nama_toko = mysqli_real_escape_string($conn, $_POST['nama_toko']);
    $promo_title = mysqli_real_escape_string($conn, $_POST['promo_title']);
    $promo_desc = mysqli_real_escape_string($conn, $_POST['promo_desc']);
    $promo_discount = mysqli_real_escape_string($conn, $_POST['promo_discount']);
    
    // Cek apakah ada record di tabel pengaturan
    $cek = query("SELECT id FROM pengaturan LIMIT 1");
    if (num_rows($cek) > 0) {
        $update = query("UPDATE pengaturan SET 
                        nama_toko = '$nama_toko',
                        promo_title = '$promo_title',
                        promo_desc = '$promo_desc',
                        promo_discount = '$promo_discount'");
    } else {
        $update = query("INSERT INTO pengaturan (nama_toko, promo_title, promo_desc, promo_discount) 
                         VALUES ('$nama_toko', '$promo_title', '$promo_desc', '$promo_discount')");
    }
    
    if ($update) {
        $success = "Pengaturan berhasil disimpan! Perubahan akan terlihat di seluruh website.";
    } else {
        $error = "Gagal menyimpan pengaturan: " . mysqli_error($conn);
    }
}

// Ambil data pengaturan saat ini
$pengaturan_query = query("SELECT * FROM pengaturan LIMIT 1");
if (num_rows($pengaturan_query) > 0) {
    $pengaturan_data = fetch_assoc($pengaturan_query);
    $current_nama_toko = $pengaturan_data['nama_toko'];
    $current_promo_title = $pengaturan_data['promo_title'] ?: 'Promo Spesial Akhir Pekan! 🎉';
    $current_promo_desc = $pengaturan_data['promo_desc'] ?: 'Diskon 20% untuk semua menu bakaran setiap Jumat–Minggu. Jangan sampai kehabisan!';
    $current_promo_discount = $pengaturan_data['promo_discount'] ?: '20%';
} else {
    $current_nama_toko = 'Kedai Bakar Via'; // Default
    $current_promo_title = 'Promo Spesial Akhir Pekan! 🎉';
    $current_promo_desc = 'Diskon 20% untuk semua menu bakaran setiap Jumat–Minggu. Jangan sampai kehabisan!';
    $current_promo_discount = '20%';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin <?php echo htmlspecialchars($current_nama_toko); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/admin.css?v=2">
    <style>
        .settings-card {
            background: white;
            border-radius: var(--r);
            padding: 32px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            max-width: 600px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dk);
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.2s;
        }
        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--p);
            box-shadow: 0 0 0 3px rgba(158, 22, 22, 0.1);
        }
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.2s;
            resize: vertical;
        }
        .settings-header {
            margin-bottom: 24px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 16px;
        }
        .settings-header h2 {
            font-size: 18px;
            color: var(--dk);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .help-text {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: var(--txl);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="top-nav">
            <div class="page-title">
                <h1><i class="fas fa-cog"></i> Pengaturan</h1>
                <p>Kelola konfigurasi website dan nama toko</p>
            </div>
            <div class="user-info">
                <a href="../index.php" class="beranda-btn" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Website</a>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)) echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> $success</div>"; ?>
        <?php if (isset($error))   echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>

        <div class="settings-card">
            <div class="settings-header">
                <h2><i class="fas fa-store" style="color: var(--p);"></i> Informasi Toko</h2>
            </div>
            
            <form method="POST">
                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="nama_toko">Nama Toko / Brand</label>
                    <input type="text" id="nama_toko" name="nama_toko" value="<?php echo htmlspecialchars($current_nama_toko); ?>" required>
                    <span class="help-text">Nama ini akan ditampilkan pada halaman beranda, navbar, dan seluruh bagian website.</span>
                </div>

                <div class="settings-header" style="margin-top: 32px;">
                    <h2><i class="fas fa-bullhorn" style="color: var(--p);"></i> Pengaturan Promo Beranda</h2>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="promo_title">Judul Promo</label>
                    <input type="text" id="promo_title" name="promo_title" value="<?php echo htmlspecialchars($current_promo_title); ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="promo_desc">Deskripsi Promo</label>
                    <textarea id="promo_desc" name="promo_desc" rows="3" required><?php echo htmlspecialchars($current_promo_desc); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label for="promo_discount">Teks Besaran Diskon</label>
                    <input type="text" id="promo_discount" name="promo_discount" value="<?php echo htmlspecialchars($current_promo_discount); ?>" placeholder="Contoh: 20% atau Rp 10.000" required>
                    <span class="help-text">Akan ditampilkan di dalam kotak di bawah tombol Pesan Sekarang.</span>
                </div>
                
                <button type="submit" name="simpan" class="btn-small btn-success" style="padding: 10px 24px; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</body>
</html>
