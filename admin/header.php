<?php
// Ensure session and shared helper functions are available for admin pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - SAYURPKY</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <h1>Panel Admin SAYURPKY</h1>
    <nav>
        <a href="index.php">Dashboard</a> |
        <a href="produk.php">Produk</a> |
        <a href="pesanan.php">Pesanan</a> |
        <a href="edukasi.php">Edukasi</a> |
        <a href="user.php">User</a> |
        <a href="resep.php">Resep</a> |
        <a href="logout.php">Logout</a>
    </nav>
</header>
<main>
