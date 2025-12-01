<?php
// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Cek apakah admin sudah login
function isAdmin() {
    return isset($_SESSION['admin']);
}

// Redirect helper
function redirect($url) {
    echo "<script>window.location='$url';</script>";
    exit;
}

// Format harga jadi Rupiah
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Sanitasi input agar lebih aman
function cleanInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Hitung total belanja di keranjang
function getCartTotal($conn) {
    if (!isset($_SESSION['keranjang'])) return 0;
    $total = 0;
    foreach ($_SESSION['keranjang'] as $id => $qty) {
        $produk = $conn->query("SELECT harga FROM tbl_produk WHERE id_produk=$id")->fetch_assoc();
        $total += $produk['harga'] * $qty;
    }
    return $total;
}

// Hitung jumlah item di keranjang
function getCartCount() {
    if (!isset($_SESSION['keranjang'])) return 0;
    return array_sum($_SESSION['keranjang']);
}
?>
