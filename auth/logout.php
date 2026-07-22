<?php
// auth/logout.php
// session sudah di-start oleh config.php via db_connect.php
require_once '../includes/db_connect.php';

session_destroy();

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

header("Location: login.php");
exit();
?>