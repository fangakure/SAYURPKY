<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_sayurpky";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

mysqli_set_charset($conn, "utf8mb4");
?>
