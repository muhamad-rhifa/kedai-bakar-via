<?php
// admin/api_cek_pesanan.php
require_once '../includes/db_connect.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['new_orders' => 0, 'paid_orders' => []]);
    exit();
}

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if ($last_id == 0) {
    // If first load, just return the current max ID and list of already paid orders so we don't notify them
    $q = mysqli_query($conn, "SELECT MAX(id) as max_id FROM pesanan");
    $row = mysqli_fetch_assoc($q);
    
    $paid_q = mysqli_query($conn, "SELECT id FROM pesanan WHERE status_pembayaran = 'sudah_bayar'");
    $paid_orders = [];
    while($pr = mysqli_fetch_assoc($paid_q)) {
        $paid_orders[] = (int)$pr['id'];
    }

    echo json_encode(['new_orders' => 0, 'latest_id' => (int)$row['max_id'], 'paid_orders' => $paid_orders]);
    exit();
}

// Check for newly created orders
$q = mysqli_query($conn, "SELECT id, no_pesanan FROM pesanan WHERE id > $last_id ORDER BY id ASC");
$new_orders = [];
$latest_id = $last_id;

while($row = mysqli_fetch_assoc($q)) {
    $new_orders[] = $row;
    $latest_id = $row['id'];
}

// Check for ALL paid orders
$paid_q = mysqli_query($conn, "SELECT id, no_pesanan FROM pesanan WHERE status_pembayaran = 'sudah_bayar'");
$paid_orders = [];
while($pr = mysqli_fetch_assoc($paid_q)) {
    $paid_orders[] = [
        'id' => (int)$pr['id'],
        'no_pesanan' => $pr['no_pesanan']
    ];
}

echo json_encode([
    'new_orders' => count($new_orders),
    'orders_data' => $new_orders,
    'latest_id' => $latest_id,
    'paid_orders' => $paid_orders
]);
?>
