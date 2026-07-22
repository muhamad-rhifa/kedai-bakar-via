<?php
require_once 'includes/db_connect.php';
require_once 'includes/config.php';

// Mendapatkan data JSON dari Midtrans
$json_result = file_get_contents('php://input');
$result = json_decode($json_result, true);

if ($result) {
    $order_id = $result['order_id'];
    $status_code = $result['status_code'];
    $transaction_status = $result['transaction_status'];
    
    // Hash untuk validasi signature
    $server_key = MIDTRANS_SERVER_KEY;
    $hashed = hash("sha512", $result['order_id'].$status_code.$result['gross_amount'].$server_key);
    
    if ($hashed == $result['signature_key']) {
        if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
            // Pembayaran sukses
            $q = "UPDATE pesanan SET status_pembayaran = 'sudah_bayar' WHERE no_pesanan = '$order_id'";
            mysqli_query($conn, $q);
        } else if ($transaction_status == 'cancel' || $transaction_status == 'deny' || $transaction_status == 'expire') {
            // Pembayaran gagal atau kadaluarsa
            $q = "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE no_pesanan = '$order_id' AND status_pesanan = 'menunggu'";
            mysqli_query($conn, $q);
        }
        echo "OK";
    } else {
        header("HTTP/1.1 403 Forbidden");
        echo "Invalid Signature";
    }
} else {
    header("HTTP/1.1 400 Bad Request");
    echo "No Data";
}
?>
