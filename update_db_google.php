<?php
require 'includes/db_connect.php';

$sql1 = "ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email";
if (mysqli_query($conn, $sql1)) {
    echo "Column google_id added successfully.\n";
} else {
    echo "Error adding column: " . mysqli_error($conn) . "\n";
}

$sql2 = "ALTER TABLE users MODIFY password VARCHAR(255) NULL";
if (mysqli_query($conn, $sql2)) {
    echo "Column password modified to NULL successfully.\n";
} else {
    echo "Error modifying column: " . mysqli_error($conn) . "\n";
}
