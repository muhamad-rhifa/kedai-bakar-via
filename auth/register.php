<?php
// auth/register.php
require_once '../includes/functions.php'; // functions.php sudah include db_connect.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $no_telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    // Validasi
    $errors = [];
    
    // Cek username sudah ada
    $check_username = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_username) > 0) {
        $errors[] = "Username sudah digunakan!";
    }
    
    // Cek email sudah ada
    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email sudah terdaftar!";
    }
    
    // Validasi password
    if (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }
    
    if ($password != $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok!";
    }
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, email, password, nama_lengkap, no_telepon, alamat, role) 
                  VALUES ('$username', '$email', '$hashed_password', '$nama_lengkap', '$no_telepon', '$alamat', 'user')";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['register_success'] = "Registrasi berhasil! Silakan login.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Gagal registrasi: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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
            font-size: 14px;
        }
        .back-home:hover {
            background: #ff6b35;
            color: white;
            transform: translateX(-5px);
        }
        @media (max-width: 768px) {
            .back-home { top: 10px; left: 10px; padding: 5px 12px; font-size: 13px; }
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
                <p>Buat akun baru</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap *</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required
                           value="<?php echo isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="no_telepon">No. Telepon</label>
                    <input type="tel" id="no_telepon" name="no_telepon"
                           value="<?php echo isset($_POST['no_telepon']) ? htmlspecialchars($_POST['no_telepon']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="alamat">Alamat Lengkap *</label>
                    <textarea id="alamat" name="alamat" rows="3" placeholder="Ketik manual atau klik tombol di bawah untuk mendeteksi lokasi otomatis..."><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                    <button type="button" id="btn-map" onclick="getLocation()" style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;background:#4285F4;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;">
                        <i class="fas fa-location-arrow"></i> Gunakan Lokasi Saat Ini
                    </button>
                    <small style="display:block;margin-top:6px;color:#888;">Pastikan Anda mengizinkan akses lokasi (GPS) pada browser.</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                    <small>Minimal 6 karakter</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group" style="display: flex; align-items: center;">
                    <input type="checkbox" id="terms" required style="width: auto; margin-right: 10px;">
                    <label for="terms" style="display: inline; margin: 0; font-size: 14px;">Saya setuju dengan syarat dan ketentuan</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>
            
            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
            </div>

            <!-- Tambahan Login dengan Google -->
            <div style="display: flex; align-items: center; text-align: center; margin: 20px 0;">
                <hr style="flex: 1; border: none; border-top: 1px solid #ddd;">
                <span style="padding: 0 10px; color: #777; font-size: 14px;">Atau mendaftar dengan</span>
                <hr style="flex: 1; border: none; border-top: 1px solid #ddd;">
            </div>

            <a href="google_login.php" class="btn btn-block" style="background-color: white; color: #444; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s;">
                <img src="https://www.google.com/favicon.ico" alt="Google" style="width: 20px; height: 20px;">
                Google
            </a>
        </div>
    </div>
    
    <script>
    function getLocation() {
        const btn = document.getElementById('btn-map');
        const alamatInput = document.getElementById('alamat');
        
        if (navigator.geolocation) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendeteksi Lokasi...';
            btn.style.pointerEvents = 'none';
            
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    
                    // Menggunakan OpenStreetMap Nominatim untuk mengubah titik kordinat menjadi alamat lengkap (Gratis)
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                        headers: {
                            'Accept-Language': 'id'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data && data.display_name) {
                                let addr = data.address || {};
                                
                                // Mapping spesifik untuk wilayah Indonesia
                                let jalan = addr.road || addr.pedestrian || "";
                                let rt_rw = addr.neighbourhood || "";
                                let kelurahan = addr.village || addr.suburb || addr.residential || addr.hamlet || "";
                                let kecamatan = addr.city_district || addr.subdistrict || addr.town || "";
                                let kota = addr.city || addr.county || addr.municipality || "";
                                let provinsi = addr.state || addr.region || "";
                                
                                // Susun format alamat yang rapi
                                let customFormat = "";
                                if (jalan) customFormat += "Jl. " + jalan + ", ";
                                if (rt_rw) customFormat += rt_rw + ", ";
                                if (kelurahan) customFormat += "Kel/Desa. " + kelurahan + ", ";
                                // Jangan tambah "Kec." jika namanya sudah mengandung kata "Kecamatan"
                                if (kecamatan) {
                                    customFormat += (kecamatan.toLowerCase().includes("kecamatan") ? kecamatan : "Kec. " + kecamatan) + ", ";
                                }
                                if (kota) {
                                    customFormat += (kota.toLowerCase().includes("kota") || kota.toLowerCase().includes("kabupaten") ? kota : kota) + ", ";
                                }
                                if (provinsi) customFormat += provinsi;
                                
                                // Hapus koma berlebih di akhir jika ada
                                customFormat = customFormat.replace(/,\s*$/, "");
                                
                                // Jika kosong (gagal parsing manual), gunakan display_name default dari sistem
                                if (!customFormat || customFormat.length < 10) {
                                    customFormat = data.display_name;
                                }
                                
                                // Tambahkan template untuk diisi manual oleh user agar lebih lengkap
                                alamatInput.value = customFormat + "\n\nRT/RW: \nDetail Rumah/Patokan: ";
                            } else {
                                alert("Alamat tidak ditemukan untuk koordinat Anda.");
                            }
                        })
                        .catch(err => {
                            alert("Terjadi kesalahan saat memproses lokasi. Silakan isi alamat manual.");
                            console.error(err);
                        })
                        .finally(() => {
                            btn.innerHTML = originalText;
                            btn.style.pointerEvents = 'auto';
                        });
                },
                function(error) {
                    btn.innerHTML = originalText;
                    btn.style.pointerEvents = 'auto';
                    alert("Gagal mengakses lokasi. Pastikan Anda memberikan izin akses GPS di browser Anda.");
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            alert("Browser Anda tidak mendukung fitur lokasi GPS.");
        }
    }
    </script>
    <script src="../assets/js/script.js"></script>
</body>
</html>