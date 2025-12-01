<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include "../config/db.php";

function isAdmin() {
    return isset($_SESSION['admin']) && $_SESSION['admin']['role'] === 'admin';
}
if (!isAdmin()) {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION['admin']['nama'] ?? $_SESSION['admin']['email'] ?? 'Admin';

// Ambil data statistik sederhana (opsional)
$totalProduk = $conn->query("SELECT COUNT(*) AS jml FROM tbl_produk")->fetch_assoc()['jml'] ?? 0;
$totalPesanan = $conn->query("SELECT COUNT(*) AS jml FROM tbl_pesanan")->fetch_assoc()['jml'] ?? 0;
$totalUser = $conn->query("SELECT COUNT(*) AS jml FROM tbl_user")->fetch_assoc()['jml'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - SAYURPKY</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --primary: #29942eff;
    --primary-dark: #198029ff;
    --bg: #f6f8fa;
    --white: #fff;
    --gray: #7a7a7a;
    --shadow: 0 6px 16px rgba(0,0,0,0.08);
}
* {margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif;}
body {
    display: flex;
    background: var(--bg);
    min-height: 100vh;
    color: #333;
}

/* SIDEBAR */
.sidebar {
    width: 270px;
    background: linear-gradient(180deg, var(--primary-dark), var(--primary));
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 25px 0;
    position: fixed;
    height: 100vh;
    box-shadow: var(--shadow);
}
.sidebar h2 {
    text-align: center;
    margin-bottom: 25px;
    font-size: 20px;
    letter-spacing: 1px;
    font-weight: 700;
}
.menu {list-style: none; padding: 0;}
.menu li {margin: 6px 0;}
.menu a {
    display: flex;
    align-items: center;
    color: white;
    text-decoration: none;
    padding: 12px 25px;
    font-weight: 500;
    transition: all 0.2s;
}
.menu a:hover, .menu a.active {
    background: rgba(255,255,255,0.18);
    padding-left: 35px;
}
.menu i {width: 22px; margin-right: 10px; font-size: 16px;}

/* LOGOUT */
.logout-section {
    border-top: 1px solid rgba(255,255,255,0.25);
    padding: 20px 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
}
.logout-left {display: flex; align-items: center;}
.logout-avatar {
    width: 50px; height: 50px; border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    margin-right: 10px;
}
.logout-avatar i {font-size: 22px; color: #fff;}
.logout-info {font-size: 13px; color: #fff;}
.logout-info span:first-child {font-weight: 600;}
.logout-btn {
    background: rgba(255,255,255,0.3);
    border: none; border-radius: 8px;
    color: white; padding: 6px 12px;
    cursor: pointer; transition: 0.3s;
}
.logout-btn:hover {background: rgba(255,255,255,0.45);}

/* MAIN AREA */
.main {
    margin-left: 270px;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* TOPBAR */
.topbar {
    background: var(--white);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.topbar h1 {
    font-size: 22px;
    font-weight: 600;
    color: var(--primary-dark);
}
.topbar .time {
    font-size: 14px;
    color: #666;
}

/* CONTENT */
.content {
    padding: 40px;
}
.content h2 {
    font-size: 20px;
    color: var(--primary-dark);
    margin-bottom: 10px;
}
.content p {color: #555; margin-bottom: 25px;}

/* STATS CARD */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.stat {
    background: var(--white);
    border-radius: 14px;
    padding: 25px 30px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: 0.3s;
}
.stat:hover {transform: translateY(-4px);}
.stat i {
    font-size: 30px;
    color: var(--primary);
}
.stat .details h3 {
    font-size: 16px;
    margin-bottom: 5px;
    color: #777;
}
.stat .details span {
    font-size: 20px;
    font-weight: 700;
    color: #333;
}

/* MENU CARD */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 25px;
}
.card {
    background: var(--white);
    border-radius: 14px;
    padding: 30px;
    text-align: center;
    box-shadow: var(--shadow);
    transition: 0.3s;
    text-decoration: none;
    color: inherit;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.card i {
    font-size: 32px;
    color: var(--primary);
    margin-bottom: 10px;
}
.card h3 {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 5px;
}
.card p {
    font-size: 14px;
    color: #666;
}
footer {
    margin-top: auto;
    background: #fff;
    text-align: center;
    padding: 15px;
    font-size: 13px;
    color: #888;
    border-top: 1px solid #eee;
}
</style>
</head>
<body>

<aside class="sidebar">
    <div>
        <h2><i class="fas fa-leaf"></i> SAYURPKY</h2>
        <ul class="menu">
            <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> Produk</a></li>
            <li><a href="pesanan.php"><i class="fas fa-shopping-cart"></i> Pesanan</a></li>
            <li><a href="edukasi.php"><i class="fas fa-chalkboard-teacher"></i> Edukasi</a></li>
            <li><a href="resep.php"><i class="fas fa-utensils"></i> Resep</a></li>
            <li><a href="user.php"><i class="fas fa-users"></i> User</a></li>
        </ul>
    </div>
    <div class="logout-section" onclick="confirmLogout()">
        <div class="logout-left">
            <div class="logout-avatar"><i class="fas fa-user-circle"></i></div>
            <div class="logout-info">
                <span><?php echo htmlspecialchars($adminName); ?></span>
                <small>Administrator</small>
            </div>
        </div>
        <button class="logout-btn"><i class="fas fa-sign-out-alt"></i></button>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <h1>Dashboard Admin</h1>
        <div class="time" id="time"></div>
    </div>

    <div class="content">
        <h2>Selamat Datang, <?php echo htmlspecialchars($adminName); ?></h2>
        <p>Berikut ringkasan aktivitas sistem Anda hari ini.</p>

        <div class="stats">
            <div class="stat">
                <div class="details">
                    <h3>Total Produk</h3>
                    <span><?php echo $totalProduk; ?></span>
                </div>
                <i class="fas fa-box"></i>
            </div>
            <div class="stat">
                <div class="details">
                    <h3>Total Pesanan</h3>
                    <span><?php echo $totalPesanan; ?></span>
                </div>
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat">
                <div class="details">
                    <h3>Total Pengguna</h3>
                    <span><?php echo $totalUser; ?></span>
                </div>
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="cards">
            <a href="produk.php" class="card">
                <i class="fas fa-box"></i>
                <h3>Kelola Produk</h3>
                <p>Tambah, edit, dan atur stok produk.</p>
            </a>
            <a href="pesanan.php" class="card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Kelola Pesanan</h3>
                <p>Monitor dan proses transaksi.</p>
            </a>
            <a href="edukasi.php" class="card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3>Kelola Edukasi</h3>
                <p>Kelola artikel edukatif.</p>
            </a>
            <a href="resep.php" class="card">
                <i class="fas fa-utensils"></i>
                <h3>Kelola Resep</h3>
                <p>Bagikan resep sehat dan menarik.</p>
            </a>
            <a href="user.php" class="card">
                <i class="fas fa-users"></i>
                <h3>Kelola User</h3>
                <p>Atur hak akses pengguna.</p>
            </a>
        </div>
    </div>

    <footer>© <?php echo date('Y'); ?> Admin Dashboard SAYURPKY</footer>
</main>

<script>
function confirmLogout() {
    if (confirm("Apakah Anda yakin ingin logout?")) {
        window.location.href = 'logout.php';
    }
}

function updateTime() {
    const now = new Date();
    const options = { weekday: 'long', hour: '2-digit', minute: '2-digit' };
    document.getElementById("time").textContent = now.toLocaleDateString('id-ID', options);
}
setInterval(updateTime, 1000);
updateTime();
</script>

</body>
</html>
