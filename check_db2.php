<?php
require 'includes/db_connect.php';
$res = mysqli_query($conn, 'SHOW COLUMNS FROM users');
print_r(mysqli_fetch_all($res, MYSQLI_ASSOC));
