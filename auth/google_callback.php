<?php
// auth/google_callback.php
require_once '../includes/functions.php'; // Termasuk config.php & db_connect.php
require_once 'google_service.php';

if (!isset($_GET['code'])) {
    // Pengguna membatalkan login atau ada error
    header('Location: login.php?error=Google login dibatalkan.');
    exit();
}

$code = $_GET['code'];

// Tukar code dengan token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URL,
    'grant_type' => 'authorization_code',
    'code' => $code
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (isset($token_data['error'])) {
    header('Location: login.php?error=Gagal verifikasi dari Google.');
    exit();
}

$access_token = $token_data['access_token'];

// Ambil profil pengguna
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$profile_response = curl_exec($ch);
curl_close($ch);

$profile_data = json_decode($profile_response, true);

if (!isset($profile_data['id'])) {
    header('Location: login.php?error=Gagal mengambil data profil Google.');
    exit();
}

$google_id = $profile_data['id'];
$email = $profile_data['email'];
$name = $profile_data['name'];
$picture = $profile_data['picture'] ?? 'default.jpg';

// Cek apakah pengguna sudah ada di database berdasarkan google_id atau email
$email_safe = $db->escape($email);
$google_id_safe = $db->escape($google_id);

$query = "SELECT * FROM users WHERE google_id = '$google_id_safe' OR email = '$email_safe'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    
    // Update google_id & foto jika belum ada, atau jika login lagi
    $update_foto = ($user['foto_profil'] == 'default.jpg') ? ", foto_profil = '" . $db->escape($picture) . "'" : "";
    $update_query = "UPDATE users SET google_id = '$google_id_safe' $update_foto WHERE id = " . $user['id'];
    mysqli_query($conn, $update_query);
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['foto_profil'] = $user['foto_profil'] == 'default.jpg' ? $picture : $user['foto_profil'];
    
} else {
    // Buat username dari email
    $username_base = explode('@', $email)[0];
    $username = $username_base;
    
    // Cek apakah username sudah ada
    $check_uname = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_uname) > 0) {
        $username = $username_base . rand(100, 999);
    }
    
    $username_safe = $db->escape($username);
    $name_safe = $db->escape($name);
    $picture_safe = $db->escape($picture);
    
    // Insert pengguna baru
    // Ingat password di set NULL
    $insert_query = "INSERT INTO users (username, email, nama_lengkap, role, foto_profil, google_id) 
                     VALUES ('$username_safe', '$email_safe', '$name_safe', 'user', '$picture_safe', '$google_id_safe')";
    
    if (mysqli_query($conn, $insert_query)) {
        $new_id = mysqli_insert_id($conn);
        
        $_SESSION['user_id'] = $new_id;
        $_SESSION['username'] = $username;
        $_SESSION['nama_lengkap'] = $name;
        $_SESSION['role'] = 'user';
        $_SESSION['foto_profil'] = $picture;
    } else {
        header('Location: login.php?error=Gagal mendaftarkan akun baru.');
        exit();
    }
}

// Redirect ke halaman depan (atau dashboard admin jika role admin)
if ($_SESSION['role'] == 'admin') {
    header("Location: ../admin/dashboard.php");
} else {
    header("Location: ../index.php");
}
exit();
