<?php 
$base_url = "../";
$page_title = "Semua Produk";
if (session_status() === PHP_SESSION_NONE) session_start();
include $base_url."config/db.php"; 

if (isset($_GET['add'])) {
    $add_id = intval($_GET['add']);
    $res = $conn->query("SELECT stok FROM tbl_produk WHERE id_produk=$add_id");
    if ($res && $res->num_rows > 0) {
        $rowp = $res->fetch_assoc();
        $stok = intval($rowp['stok']);
        if ($stok > 0) {
            if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];
            if (!isset($_SESSION['keranjang'][$add_id])) $_SESSION['keranjang'][$add_id] = 0;
            if ($_SESSION['keranjang'][$add_id] < $stok) {
                $_SESSION['keranjang'][$add_id]++;
                $_SESSION['flash_msg'] = 'Produk berhasil ditambahkan ke keranjang.';
            } else {
                $_SESSION['flash_msg'] = 'Jumlah yang ingin ditambahkan melebihi stok.';
            }
        } else {
            $_SESSION['flash_msg'] = 'Maaf, stok produk habis.';
        }
    }
    header('Location: produk.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | SAYURPKY</title>
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css">
    <style>
        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2d6a4f;
            --primary-dark: #1b4332;
            --primary-light: #40916c;
            --secondary-color: #f77f00;
            --accent-color: #52b788;
            --white: #ffffff;
            --gray-50: #f8f9fa;
            --gray-100: #f1f3f5;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 4rem;
            --font-main: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            --font-heading: 'Georgia', serif;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15);
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition-fast: 150ms ease;
            --transition-base: 250ms ease;
            --transition-slow: 350ms ease;
        }

        body {
            font-family: var(--font-main);
            font-size: 16px;
            line-height: 1.6;
            color: var(--gray-800);
            background-color: var(--gray-50);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--spacing-md);
        }

        img { max-width: 100%; height: auto; display: block; }

        header {
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: var(--spacing-lg);
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            transition: all var(--transition-base);
        }

        .nav-links a:hover { color: var(--primary-color); }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            border-radius: var(--radius-md);
            transition: all var(--transition-base);
            cursor: pointer;
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: var(--shadow-sm);
            border: 2px solid var(--primary-color);
        }

        .btn:hover, .btn-login:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--white);
        }

        .btn.secondary {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn.secondary:hover {
            background-color: var(--primary-color);
            color: var(--white);
        }

        footer {
            background-color: var(--gray-900);
            color: var(--gray-300);
            padding: var(--spacing-xl) 0 var(--spacing-md);
            margin-top: var(--spacing-2xl);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
        }

        .footer-section h3 {
            color: var(--white);
            margin-bottom: var(--spacing-sm);
            font-size: 1.125rem;
        }

        .footer-section p, .footer-section ul {
            font-size: 0.95rem;
            line-height: 1.8;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: var(--spacing-xs);
        }

        .footer-section a {
            color: var(--gray-300);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .footer-section a:hover {
            color: var(--accent-color);
        }

        .footer-bottom {
            text-align: center;
            padding-top: var(--spacing-md);
            border-top: 1px solid var(--gray-700);
            font-size: 0.875rem;
        }

        .site-header {
            background: white;
            box-shadow: var(--shadow-sm);
            padding: 16px 24px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .site-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .site-header .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .main-nav ul {
            display: flex;
            gap: 24px;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }
        
        .main-nav a {
            text-decoration: none;
            font-weight: 600;
            color: var(--gray-700);
            transition: var(--transition-base);
        }
        
        .main-nav a:hover { 
            color: var(--primary-color); 
        }
        
        .nav-toggle {
            display: none;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .main-nav ul {
                display: none;
                flex-direction: column;
                gap: 16px;
                background: white;
                position: absolute;
                top: 100%;
                right: 0;
                width: 200px;
                padding: 16px;
                box-shadow: var(--shadow-md);
                z-index: 999;
            }
            .main-nav.active ul {
                display: flex;
            }
            .nav-toggle { display: block; }
        }

        .products-container {
            max-width: 1200px;
            margin: 40px auto 80px;
            padding: 0 24px;
        }

        .page-header { 
            position: relative; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            gap: 20px; 
            margin-bottom: 50px; 
            max-width: 1200px; 
            margin-left: auto; 
            margin-right: auto; 
            padding: 0 24px; 
        }
        
        .page-title-wrap { 
            position: absolute; 
            left: 50%; 
            transform: translateX(-50%); 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            max-width: 760px; 
            width: calc(100% - 200px); 
            justify-content: center; 
        }
        
        .page-title-wrap > div { 
            text-align: center; 
        }
        
        .page-icon { 
            font-size: 40px; 
            line-height: 1; 
        }
        
        .page-header h1 { 
            font-size: 42px; 
            font-weight: 800; 
            margin: 0; 
            letter-spacing: -0.5px; 
        }
        
        .page-header p { 
            font-size: 18px; 
            color: var(--gray-600); 
            max-width: 600px; 
            margin: 8px 0 0 0; 
        }

        .cart-summary { 
            flex: 0 0 auto; 
            z-index: 2; 
            display: flex; 
            flex-direction: column; 
            align-items: flex-end; 
            gap: 6px; 
        }
        
        .cart-link { 
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            background: var(--primary-color); 
            color: #fff; 
            padding: 10px 14px; 
            border-radius: 10px; 
            text-decoration: none; 
            font-weight: 700; 
            transition: all var(--transition-base);
        }

        .cart-link:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .cart-link .count { 
            background: #fff; 
            color: var(--primary-color); 
            padding: 4px 8px; 
            border-radius: 999px; 
            font-weight: 800; 
        }
        
        .cart-sub { 
            font-size: 13px; 
            color: var(--gray-600); 
        }

        /* ===== FILTER SECTION - PROFESSIONAL DESIGN ===== */
        .filter-section-new {
            background: linear-gradient(135deg, var(--white) 0%, #f8fafb 100%);
            border-radius: 12px;
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(45, 106, 79, 0.08);
        }

        .filter-container {
            padding: 32px 28px;
            max-width: 100%;
        }

        .filter-header {
            margin-bottom: 28px;
        }

        .filter-heading {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .filter-description {
            font-size: 0.95rem;
            color: var(--gray-600);
            margin: 0;
        }

        .filter-buttons-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .filter-btn-new {
            position: relative;
            padding: 18px 20px;
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition-base);
            display: flex;
            justify-content: space-between;
            align-items: center;
            overflow: hidden;
        }

        .filter-btn-new::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(45, 106, 79, 0.03) 0%, rgba(64, 145, 108, 0.03) 100%);
            transition: left var(--transition-base);
            pointer-events: none;
        }

        .filter-btn-new:hover {
            border-color: var(--primary-light);
            box-shadow: 0 4px 12px rgba(45, 106, 79, 0.1);
            transform: translateY(-2px);
        }

        .filter-btn-new:hover::before {
            left: 0;
        }

        .btn-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .btn-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .btn-count {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
        }

        .btn-indicator {
            position: relative;
            z-index: 2;
            width: 20px;
            height: 20px;
            border: 2px solid var(--gray-300);
            border-radius: 50%;
            margin-left: 12px;
            transition: all var(--transition-base);
        }

        .filter-btn-new:hover .btn-indicator {
            border-color: var(--primary-light);
        }

        .filter-btn-new.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-color: var(--primary-color);
            color: var(--white);
            box-shadow: 0 6px 16px rgba(45, 106, 79, 0.2);
        }

        .filter-btn-new.active .btn-label {
            color: var(--white);
        }

        .filter-btn-new.active .btn-count {
            color: rgba(255, 255, 255, 0.85);
        }

        .filter-btn-new.active .btn-indicator {
            background: var(--white);
            border-color: var(--white);
            box-shadow: inset 0 0 0 4px var(--primary-color);
        }

        .active-filter {
            padding: 14px 16px;
            background: rgba(45, 106, 79, 0.06);
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            font-size: 0.95rem;
        }

        .filter-tag {
            color: var(--gray-700);
        }

        .filter-tag strong {
            color: var(--primary-color);
            font-weight: 700;
        }

        .clear-filter {
            background: var(--primary-color);
            color: var(--white);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all var(--transition-base);
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }

        .clear-filter:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Product Grid Styles */
        .grid-produk {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: var(--spacing-lg);
            margin: var(--spacing-xl) 0;
        }

        .card-produk {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-base);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--gray-200);
        }

        .card-produk:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .card-produk img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
            background-color: var(--gray-100);
        }

        .card-produk h3 {
            font-size: 1.25rem;
            color: var(--gray-900);
            margin-bottom: var(--spacing-xs);
            font-weight: 600;
            line-height: 1.3;
        }

        .card-produk .muted {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: var(--spacing-sm);
            gap: var(--spacing-sm);
        }

        .card-produk .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
            margin-top: auto;
        }

        .card-produk .btn {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: var(--white);
            border-radius: var(--radius-md);
            text-align: center;
            font-weight: 600;
            transition: all var(--transition-base);
            border: 2px solid var(--primary-color);
        }

        .card-produk .btn:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .products-info { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 24px; 
            flex-wrap: wrap; 
            gap: 16px; 
        }

        .products-count { 
            color: var(--gray-600); 
            font-size: 15px; 
        }
        
        .products-count strong { 
            color: var(--gray-900); 
            font-weight: 700; 
        }

        .pagination { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 8px; 
            margin-top: 60px; 
            flex-wrap: wrap; 
        }
        
        .pagination a, .pagination span {
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            font-weight: 600;
            min-width: 44px;
            text-align: center;
            text-decoration: none;
            color: var(--gray-700);
            transition: all var(--transition-base);
        }
        
        .pagination a:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .pagination .active { 
            background: var(--primary-color); 
            color: white; 
            border-color: var(--primary-color); 
        }
        
        .pagination .disabled { 
            opacity: 0.4; 
            pointer-events: none; 
        }

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: var(--spacing-xl);
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
        }

        .no-products h3 {
            font-size: 1.5rem;
            margin-bottom: var(--spacing-md);
        }

        .no-products p {
            color: var(--gray-600);
            margin-bottom: var(--spacing-lg);
        }

        .btn-login {
            display: inline-block;
            padding: 8px 20px;
            background-color: #04591cff;
            color: white !important;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #4CAF50;
        }

        .btn-login:hover {
            background-color: #45a049;
            color: white !important;
            transform: translateY(-2px);
        }

        .main-nav ul li a.btn-login {
            color: white !important;
        }

        .main-nav ul li a.btn-login:visited {
            color: white !important;
        }

        @media (max-width: 768px) {
            .page-header { 
                position: static; 
                flex-direction: column; 
                text-align: center; 
                padding: 0 16px; 
            }
            .page-title-wrap { 
                position: static; 
                transform: none; 
                width: 100%; 
                justify-content: center; 
            }
            .cart-summary { 
                align-items: center; 
                margin-top: 12px; 
            }
            .page-header h1 { 
                font-size: 32px; 
            }

            .filter-container {
                padding: 24px 20px;
            }

            .filter-heading {
                font-size: 1.25rem;
            }

            .filter-buttons-wrapper {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 12px;
            }

            .filter-btn-new {
                padding: 16px 16px;
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-indicator {
                margin-left: 0;
                margin-top: 8px;
            }

            .active-filter {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }

            .grid-produk {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .products-container {
                padding: 0 var(--spacing-sm);
            }

            .filter-container {
                padding: 20px 16px;
            }

            .filter-heading {
                font-size: 1.1rem;
            }

            .filter-buttons-wrapper {
                grid-template-columns: 1fr;
            }

            .filter-btn-new {
                flex-direction: row;
                align-items: center;
            }

            .btn-indicator {
                margin-left: auto;
                margin-top: 0;
            }

            .grid-produk {
                grid-template-columns: 1fr;
            }

            .card-produk img {
                height: 180px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .page-header p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="site-header">
    <div class="container">
        <a href="<?= $base_url ?>index.php" class="logo">SAYURPKY</a>
        <nav class="main-nav">
            <ul>
                <li><a href="<?= $base_url ?>index.php">Beranda</a></li>
                <li><a href="<?= $base_url ?>pages/produk.php">Produk</a></li>
                <li><a href="<?= $base_url ?>pages/edukasi.php">Edukasi</a></li>
                <li><a href="<?= $base_url ?>pages/resep.php">Resep</a></li>
                <li><a href="<?= $base_url ?>pages/about.php">Tentang</a></li>
                <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="<?= $base_url ?>pages/logout.php" class="btn-login">Keluar</a></li>
                <?php else: ?>
                    <li><a href="<?= $base_url ?>pages/login.php" class="btn-login">Masuk</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <button class="nav-toggle" aria-label="Toggle Navigation">☰</button>
    </div>
</header>

<script>
const navToggle = document.querySelector('.nav-toggle');
const mainNav = document.querySelector('.main-nav');
navToggle.addEventListener('click', () => { mainNav.classList.toggle('active'); });
</script>

<?php
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$filter_kategori_id = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;

$where = "WHERE status='tersedia'";

if ($filter_kategori_id > 0) {
    $where .= " AND id_kategori = $filter_kategori_id";
}

$count_query = $conn->query("SELECT COUNT(*) as total FROM tbl_produk $where");
$total_products = $count_query ? $count_query->fetch_assoc()['total'] : 0;
$total_pages = $total_products > 0 ? ceil($total_products / $limit) : 1;

$query = $conn->query("SELECT * FROM tbl_produk $where ORDER BY id_produk DESC LIMIT $limit OFFSET $offset");
?>

<div class="products-container">
    <div class="page-header">
        <div class="page-title-wrap">
            <div>
                <h1>Semua Produk Kami</h1>
                <p>Temukan berbagai pilihan sayuran segar organik berkualitas tinggi langsung dari petani lokal</p>
            </div>
        </div>

        <a class="cart-link" href="<?= $base_url ?>pages/pesanan.php" style="flex-shrink: 0;">
            <span style="font-size:18px;">📦</span>
            <span>Pesanan Saya</span>
        </a>

        <div class="cart-summary">
            <?php $cart_count = isset($_SESSION['keranjang']) ? array_sum($_SESSION['keranjang']) : 0; ?>
            <a class="cart-link" href="<?= $base_url ?>pages/keranjang.php">
                <span style="font-size:18px;">🛒</span>
                <span>Keranjang</span>
                <span class="count"><?= $cart_count; ?></span>
            </a>
            <div class="cart-sub"><?php echo $cart_count > 0 ? $cart_count . ' item' : 'Keranjang kosong'; ?></div>
        </div>
    </div>

    <!-- Filter Section - Professional Design -->
    <div class="filter-section-new">
        <div class="filter-container">
            <!-- Filter Header -->
            <div class="filter-header">
                <h2 class="filter-heading">Filter Produk</h2>
                <p class="filter-description">Pilih kategori untuk melihat produk yang tersedia</p>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons-wrapper">
                <a href="produk.php" class="filter-btn-new <?= $filter_kategori_id === 0 ? 'active' : '' ?>">
                    <div class="btn-content">
                        <span class="btn-label">Semua Kategori</span>
                        <span class="btn-count"><?php $c = $conn->query("SELECT COUNT(*) as total FROM tbl_produk WHERE status='tersedia'")->fetch_assoc(); echo $c['total']; ?></span>
                    </div>
                    <span class="btn-indicator"></span>
                </a>

                <?php
                $kategori_query = $conn->query("SELECT * FROM tbl_kategori ORDER BY nama_kategori ASC");
                if ($kategori_query) {
                    while ($kat = $kategori_query->fetch_assoc()) {
                        $id_kat = $kat['id_kategori'];
                        $nama_kat = htmlspecialchars($kat['nama_kategori']);
                        $count_res = $conn->query("SELECT COUNT(*) as total FROM tbl_produk WHERE status='tersedia' AND id_kategori=$id_kat");
                        $count = $count_res ? $count_res->fetch_assoc()['total'] : 0;
                        $is_active = ($filter_kategori_id === $id_kat) ? 'active' : '';
                        echo "
                        <a href='produk.php?kategori=$id_kat' class='filter-btn-new $is_active'>
                            <div class='btn-content'>
                                <span class='btn-label'>$nama_kat</span>
                                <span class='btn-count'>$count</span>
                            </div>
                            <span class='btn-indicator'></span>
                        </a>";
                    }
                }
                ?>
            </div>

            <!-- Active Filter Display -->
            <?php if($filter_kategori_id > 0): ?>
            <div class="active-filter">
                <span class="filter-tag">Filter Aktif: <strong><?php $kat_name_q = $conn->query("SELECT nama_kategori FROM tbl_kategori WHERE id_kategori=$filter_kategori_id"); echo $kat_name_q ? htmlspecialchars($kat_name_q->fetch_assoc()['nama_kategori']) : 'Kategori'; ?></strong></span>
                <a href="produk.php" class="clear-filter">✕ Hapus Filter</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="grid-produk">
        <?php
        if($query && $query->num_rows > 0){
            while ($row = $query->fetch_assoc()) {
                $img = $base_url.'assets/img/'.($row['gambar'] ? $row['gambar'] : 'placeholder.svg');
                $nama = htmlspecialchars($row['nama_produk']);
                $stok = intval($row['stok']);
                $berat = htmlspecialchars($row['berat']);
                $harga = number_format($row['harga'], 0, ',', '.');
                $id = $row['id_produk'];
                ?>
                <div class="card-produk">
                    <img src="<?= $img;?>" alt="<?= $nama;?>" loading="lazy">
                    <h3><?= $nama;?></h3>
                    <p class="muted">
                        <span>📦 Stok: <?= $stok;?></span>
                        <span>⚖️ <?= $berat;?> kg</span>
                    </p>
                    <p class="price">Rp <?= $harga;?></p>
                    <a href="detail_produk.php?id=<?= $id;?>" class="btn">Lihat Detail</a>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="no-products">
                <h3>🔍 Produk Tidak Ditemukan</h3>_
                <p>Maaf, tidak ada produk di kategori ini yang tersedia saat ini.</p>
                <a href="produk.php" class="btn">Lihat Semua Produk</a>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?kategori=<?= $filter_kategori_id; ?>&page=<?= ($page-1); ?>">← Sebelumnya</a>
        <?php else: ?>
            <span class="disabled">← Sebelumnya</span>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        if ($start > 1): ?>
            <a href="?kategori=<?= $filter_kategori_id; ?>&page=1">1</a>
            <?php if ($start > 2): ?>
                <span>...</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for($i = $start; $i <= $end; $i++): ?>
            <?php if($i == $page): ?>
                <span class="active"><?= $i; ?></span>
            <?php else: ?>
                <a href="?kategori=<?= $filter_kategori_id; ?>&page=<?= $i; ?>"><?= $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $total_pages): ?>
            <?php if ($end < $total_pages - 1): ?>
                <span>...</span>
            <?php endif; ?>
            <a href="?kategori=<?= $filter_kategori_id; ?>&page=<?= $total_pages; ?>"><?= $total_pages; ?></a>
        <?php endif; ?>

        <?php if($page < $total_pages): ?>
            <a href="?kategori=<?= $filter_kategori_id; ?>&page=<?= ($page+1); ?>">Selanjutnya →</a>
        <?php else: ?>
            <span class="disabled">Selanjutnya →</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>SAYURPKY</h3>
                <p>Menyediakan sayuran organik segar langsung dari petani lokal ke meja Anda. Sehat, segar, dan berkualitas.</p>
            </div>
            <div class="footer-section">
                <h3>Link Cepat</h3>
                <ul>
                    <li><a href="../index.php">Beranda</a></li>
                    <li><a href="produk.php">Produk</a></li>
                    <li><a href="edukasi.php">Edukasi</a></li>
                    <li><a href="resep.php">Resep</a></li>
                    <li><a href="about.php">Tentang Kami</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Kontak Kami</h3>
                <ul>
                    <li>📧 Email: info@sayursegar.com</li>
                    <li>📱 Telp: 0823-5700-0187</li>
                    <li>📍 Palangka Raya, Kalimantan Tengah</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 SAYURPKY. Semua hak dilindungi.</p>
        </div>
    </div>
</footer>

</body>
</html>