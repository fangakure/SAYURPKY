<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// set a sane default base URL (adjust if your project is served from a different path)
if (!isset($base_url)) {
    // If header is included from a file under /pages, use ../ else use root relative path
    $caller = dirname(__FILE__);
    // default to site relative folder
    $base_url = '/SAYURPKY/';
    // If we detect this header is included from a pages/ file, prefer ../
    if (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false) {
        $base_url = '../';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sayur segar organik langsung dari petani ke meja Anda">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Toko Sayur Segar</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/style.css">
    <link rel="icon" type="image/svg+xml" href="<?php echo $base_url; ?>assets/img/favicon.svg">
    <style>
        /* Header & Navigation Styles */
        header {
            background-color: var(--white, #ffffff);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav.container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.5rem 1rem;
        }

        /* Tombol Login/Masuk styling */
        .btn-login {
            display: inline-block;
            padding: 8px 20px;
            background-color: #04591cff;
            color: white !important;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #4CAF50;
            font-weight: 600;
        }

        .btn-login:hover {
            background-color: #45a049;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-login:active,
        .btn-login:focus {
            background-color: #74e8a0ff;
            color: white !important;
            outline: none;
            transform: translateY(0);
        }

        /* Memastikan link di dalam nav mengikuti aturan */
        .nav-menu li a.btn-login {
            color: white !important;
        }

        .nav-menu li a.btn-login:visited {
            color: white !important;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d6a4f;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .nav-menu li a {
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-menu li a:hover {
            color: #2d6a4f;
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

        .btn-login:active,
        .btn-login:focus {
            background-color: #74e8a0ff;
            color: white !important;
            outline: none;
            transform: translateY(0);
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 6px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
        }

        .menu-toggle span {
            display: block;
            width: 25px;
            height: 2px;
            background-color: #2d6a4f;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-menu li {
                width: 100%;
                text-align: center;
            }

            .nav-menu li a {
                display: block;
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <a href="<?php echo isset($base_url) ? $base_url : ''; ?>index.php" class="logo">SAYURPKY</a>
            
            <div class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <?php if (session_status() === PHP_SESSION_NONE) { session_start(); }
                    // Detect if current request is for a page under /pages/ so we can use correct relative links
                    $in_pages = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false);
                    // Helper hrefs
                    $href_index = $in_pages ? '../index.php' : (isset($base_url) ? $base_url . 'index.php' : 'index.php');
                    $href_produk = $in_pages ? 'produk.php' : (isset($base_url) ? $base_url . 'pages/produk.php' : 'pages/produk.php');
                    $href_edukasi = $in_pages ? 'edukasi.php' : (isset($base_url) ? $base_url . 'pages/edukasi.php' : 'pages/edukasi.php');
                    $href_resep = $in_pages ? 'resep.php' : (isset($base_url) ? $base_url . 'pages/resep.php' : 'pages/resep.php');
                    $href_about = $in_pages ? 'about.php' : (isset($base_url) ? $base_url . 'pages/about.php' : 'pages/about.php');
                    $href_login = $in_pages ? 'login.php' : (isset($base_url) ? $base_url . 'pages/login.php' : 'pages/login.php');
            ?>
                    <ul class="nav-menu" id="navMenu">
                        <li><a href="<?php echo $href_index; ?>">Beranda</a></li>
                        <li><a href="<?php echo $href_produk; ?>">Produk</a></li>
                        <li><a href="<?php echo $href_edukasi; ?>">Edukasi</a></li>
                        <li><a href="<?php echo $href_resep; ?>">Resep</a></li>
                        <li><a href="<?php echo $href_about; ?>">Tentang</a></li>
                        <?php 
                        // Check if we're on the resep page
                        $current_page = basename($_SERVER['SCRIPT_NAME']);

                        // Use the session key 'user' (set in pages/login.php) to detect login
                        // Hide both Masuk and Keluar buttons on resep.php
                        if($current_page != 'resep.php') {
                            if(isset($_SESSION['user'])) {
                                echo '<li><a href="' . ($in_pages ? 'logout.php' : $base_url . 'pages/logout.php') . '" class="btn-login">Keluar</a></li>';
                            } else {
                                echo '<li><a href="' . $href_login . '" class="btn-login">Masuk</a></li>';
                            }
                        }
                        ?>
                    </ul>
        </nav>
    </header>

    <script>
    function toggleMenu() {
        const navMenu = document.getElementById('navMenu');
        navMenu.classList.toggle('active');
    }
    
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const navMenu = document.getElementById('navMenu');
        const menuToggle = document.querySelector('.menu-toggle');
        
        if (!navMenu.contains(event.target) && !menuToggle.contains(event.target)) {
            navMenu.classList.remove('active');
        }
    });
    </script>