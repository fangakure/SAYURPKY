<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Ambil data gambar untuk dihapus dari folder
    $result = $conn->query("SELECT gambar FROM tbl_produk WHERE id_produk = $id");
    if ($result && $row = $result->fetch_assoc()) {
        $gambarPath = "../assets/img/" . $row['gambar'];
        if (file_exists($gambarPath)) {
            unlink($gambarPath); // hapus file gambar
        }
    }

    // Hapus produk dari database
    $conn->query("DELETE FROM tbl_produk WHERE id_produk = $id");

    echo "<script>alert('🗑️ Produk berhasil dihapus!'); window.location='produk.php';</script>";
} else {
    echo "<script>alert('ID produk tidak ditemukan!'); window.location='produk.php';</script>";
}
?>
