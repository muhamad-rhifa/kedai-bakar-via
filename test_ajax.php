<?php
// Simulate request to keranjang.php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'user';
session_write_close();

// Read cookie from session
$sess_name = session_name();
$sess_id = session_id();

$url = "http://localhost/kedai-bakar-via/user/keranjang.php?add=1&qty=7&varian=" . urlencode('{"teks":"Jumbo: Ori (+Rp 1.000)","extra_harga":1000}') . "&ajax=1";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, "$sess_name=$sess_id");
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";
