<?php
// db.php
$DB_server = 'localhost'; 
$DB_NAME = 'blood_donation_and_inventory_system';
$DB_USER = 'root';
$DB_PASS = ''; 
$conn = mysqli_connect($DB_server, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
