<?php
// includes/functions.php
require_once 'db_connect.php';

// =====================================================
// FUNGSI AUTENTIKASI
// =====================================================

// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Cek apakah user adalah admin
function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
}

// Redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
}

// Redirect jika bukan admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . BASE_URL . "user/index.php");
        exit();
    }
}

// =====================================================
// FUNGSI FORMAT RUPIAH
// =====================================================
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// =====================================================
// FUNGSI GENERATE NO PESANAN
// =====================================================
function generateNoPesanan() {
    $date = date('Ymd');
    $random = rand(1000, 9999);
    return "INV/KBV/{$date}/{$random}";
}

// =====================================================
// FUNGSI UPLOAD GAMBAR
// =====================================================
function uploadGambar($file, $target_dir = "../assets/images/") {
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Cek apakah file gambar
    $check = getimagesize($file["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        return "File bukan gambar.";
    }
    
    // Cek ukuran file (max 2MB)
    if ($file["size"] > 2000000) {
        return "Ukuran file terlalu besar (max 2MB).";
    }
    
    // Izinkan format tertentu
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return "Hanya file JPG, JPEG, PNG yang diizinkan.";
    }
    
    // Generate nama file unik
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $new_filename;
    } else {
        return "Gagal upload file.";
    }
}

// =====================================================
// FUNGSI HITUNG TOTAL KERANJANG
// =====================================================
function getTotalKeranjang($user_id) {
    global $conn;
    $query = "SELECT SUM(m.harga * k.jumlah) as total 
              FROM keranjang k 
              JOIN menu m ON k.menu_id = m.id 
              WHERE k.user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['total'] ?? 0;
}

// =====================================================
// FUNGSI HITUNG JUMLAH ITEM KERANJANG
// =====================================================
function countKeranjang($user_id) {
    global $conn;
    $query = "SELECT SUM(jumlah) as total FROM keranjang WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['total'] ?? 0;
}

// =====================================================
// FUNGSI CEK STOK
// =====================================================
function cekStok($menu_id, $jumlah) {
    global $conn;
    $query = "SELECT stok FROM menu WHERE id = $menu_id";
    $result = mysqli_query($conn, $query);
    $menu = mysqli_fetch_assoc($result);
    return $menu['stok'] >= $jumlah;
}

// =====================================================
// FUNGSI KURANGI STOK
// =====================================================
function kurangiStok($menu_id, $jumlah) {
    global $conn;
    $query = "UPDATE menu SET stok = stok - $jumlah WHERE id = $menu_id";
    return mysqli_query($conn, $query);
}

// =====================================================
// FUNGSI TAMBAH STOK
// =====================================================
function tambahStok($menu_id, $jumlah) {
    global $conn;
    $query = "UPDATE menu SET stok = stok + $jumlah WHERE id = $menu_id";
    return mysqli_query($conn, $query);
}

// =====================================================
// FUNGSI GET USER BY ID
// =====================================================
function getUserById($user_id) {
    global $conn;
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// =====================================================
// FUNGSI GET MENU BY ID
// =====================================================
function getMenuById($menu_id) {
    global $conn;
    $query = "SELECT m.*, k.nama_kategori 
              FROM menu m 
              LEFT JOIN kategori_menu k ON m.kategori_id = k.id 
              WHERE m.id = $menu_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// =====================================================
// FUNGSI GET PESANAN BY ID
// =====================================================
function getPesananById($pesanan_id) {
    global $conn;
    $query = "SELECT p.*, u.nama_lengkap, u.email, u.no_telepon 
              FROM pesanan p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.id = $pesanan_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// =====================================================
// FUNGSI GET DETAIL PESANAN
// =====================================================
function getDetailPesanan($pesanan_id) {
    global $conn;
    $query = "SELECT dp.*, m.nama_menu, m.gambar 
              FROM detail_pesanan dp 
              JOIN menu m ON dp.menu_id = m.id 
              WHERE dp.pesanan_id = $pesanan_id";
    $result = mysqli_query($conn, $query);
    return fetch_all($result);
}

// =====================================================
// FUNGSI TAMPILKAN ALERT
// =====================================================
function showAlert($message, $type = 'success') {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}

// =====================================================
// FUNGSI GENERATE SLUG
// =====================================================
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// =====================================================
// FUNGSI MIDTRANS SNAP TOKEN
// =====================================================
function getMidtransSnapToken($pesanan, $user) {
    $serverKey = MIDTRANS_SERVER_KEY;
    $isProduction = MIDTRANS_IS_PRODUCTION;
    $apiUrl = $isProduction ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    
    $params = array(
        'transaction_details' => array(
            'order_id' => $pesanan['no_pesanan'],
            'gross_amount' => (int) $pesanan['total_harga'],
        ),
        'customer_details' => array(
            'first_name' => $user['nama_lengkap'],
            'email' => $user['email'],
            'phone' => $user['no_telepon'],
            'shipping_address' => array(
                'first_name' => $user['nama_lengkap'],
                'address' => $pesanan['alamat_pengiriman'],
            )
        )
    );

    // Filter metode pembayaran dinonaktifkan sementara untuk mencegah error "No payment channels available" 
    // jika metode yang dipilih belum diaktifkan di Midtrans Dashboard.
    /*
    if (!empty($pesanan['metode_pembayaran']) && $pesanan['metode_pembayaran'] != 'Midtrans Payment Gateway') {
        $params['enabled_payments'] = array($pesanan['metode_pembayaran']);
    }
    */

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // if on localhost with no ssl

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response);
    if ($httpCode != 201 && $httpCode != 200) {
        $error_msg = isset($result->error_messages) ? implode(", ", $result->error_messages) : "Gagal terhubung ke Midtrans (HTTP $httpCode). Pastikan Server Key di config.php sudah benar.";
        return ['token' => null, 'error' => $error_msg];
    }
    
    return ['token' => isset($result->token) ? $result->token : null, 'error' => null];
}

// =====================================================
// FUNGSI FORMAT METODE PEMBAYARAN MIDTRANS
// =====================================================
function formatPaymentMethod($method) {
    $methods = [
        'qris' => 'QRIS',
        'gopay' => 'GoPay',
        'shopeepay' => 'ShopeePay',
        'bca_va' => 'BCA Virtual Account',
        'bni_va' => 'BNI Virtual Account',
        'bri_va' => 'BRIVA',
        'echannel' => 'Mandiri Bill Payment',
        'permata_va' => 'Permata Virtual Account',
        'cimb_va' => 'CIMB Niaga VA'
    ];
    return isset($methods[$method]) ? "Midtrans - " . $methods[$method] : ($method ?: '-');
}

// =====================================================
// FUNGSI CEK STATUS TRANSAKSI MIDTRANS (UNTUK LOCALHOST)
// =====================================================
function checkMidtransStatus($order_id) {
    $serverKey = MIDTRANS_SERVER_KEY;
    $isProduction = MIDTRANS_IS_PRODUCTION;
    $apiUrl = $isProduction ? "https://api.midtrans.com/v2/{$order_id}/status" : "https://api.sandbox.midtrans.com/v2/{$order_id}/status";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($serverKey . ':')
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        return $result;
    }
    return null;
}
?>