<?php
require 'includes/db_connect.php';
$res = mysqli_query($conn, 'SHOW COLUMNS FROM keranjang');
if (!$res) {
    echo "Error: " . mysqli_error($conn) . "\n";
} else {
    print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
}

$test = mysqli_query($conn, "SELECT * FROM keranjang WHERE user_id = 1 AND menu_id = 1 AND varian = '{\"teks\":\"Jumbo: Ori\",\"extra_harga\":1000}'");
if (!$test) {
    echo "Query Error: " . mysqli_error($conn) . "\n";
} else {
    echo "Query OK. Rows: " . mysqli_num_rows($test) . "\n";
}
