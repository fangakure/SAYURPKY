<?php include "config/db.php"; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sayur Segar - Langsung dari Petani</title>
    <style>
        /* ===== RESET & BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Color Palette */
            --primary-color: #2d6a4f;
            --primary-dark: #1b4332;
            --primary-light: #40916c;
            --secondary-color: #f77f00;
            --accent-color: #52b788;
            
            /* Neutral Colors */
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
            
            /* Spacing */
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 4rem;
            
            /* Typography */
            --font-main: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            --font-heading: 'Georgia', serif;
            
            /* Shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15);
            
            /* Border Radius */
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            
            /* Transitions */
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

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* ===== HEADER / NAVIGATION ===== */
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
            transition: color var(--transition-fast);
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        /* ===== BUTTONS ===== */
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
            border: none;
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
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

        /* ===== HERO SECTION ===== */
        .hero {
            background: linear-gradient(135deg, #d4f1e8 0%, #e8f5e9 100%);
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-xl);
        }

        .hero-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-xl);
            align-items: center;
        }

        .hero-left h1 {
            font-family: var(--font-heading);
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: var(--spacing-md);
            line-height: 1.2;
        }

        .hero-left p {
            font-size: 1.125rem;
            color: var(--gray-700);
            margin-bottom: var(--spacing-lg);
            line-height: 1.7;
        }

        .hero-cta {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
        }

        .hero-right {
            position: relative;
        }

        .hero-right img {
            width: 100%;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            transition: transform var(--transition-slow);
        }

        .hero-right img:hover {
            transform: scale(1.02);
        }

        /* ===== SECTION HEADER ===== */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 2px solid var(--gray-200);
        }

        .section-header h2 {
            font-family: var(--font-heading);
            font-size: 2rem;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .section-header a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition-fast);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-header a:hover {
            color: var(--primary-dark);
        }

        /* ===== PRODUCT GRID ===== */
        .grid-produk {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
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

        .card-produk .muted span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .card-produk .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
            margin-top: auto;
        }

        .card-produk .btn {
            width: 100%;
            padding: 10px;
            font-size: 0.95rem;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl) var(--spacing-md);
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 2px dashed var(--gray-300);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--gray-700);
            margin-bottom: var(--spacing-sm);
        }

        .empty-state p {
            color: var(--gray-600);
            font-size: 1rem;
        }

        /* ===== FEATURES SECTION ===== */
        .features-section {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            padding: var(--spacing-2xl) 0;
            margin-bottom: var(--spacing-xl);
            color: var(--white);
        }

        .features-section h2 {
            text-align: center;
            font-family: var(--font-heading);
            font-size: 2rem;
            margin-bottom: var(--spacing-xl);
            font-weight: 700;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-lg);
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            text-align: center;
            transition: all var(--transition-base);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
        }

        .feature-card .icon {
            font-size: 3rem;
            display: block;
            margin-bottom: var(--spacing-md);
        }

        .feature-card h3 {
            font-size: 1.375rem;
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
        }

        .feature-card p {
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
        }

        /* ===== FOOTER ===== */
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

        .footer-section p,
        .footer-section ul {
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

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 968px) {
            .hero-inner {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }
            
            .hero-left h1 {
                font-size: 2rem;
            }
            
            .hero-right {
                order: -1;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-sm);
            }

            .nav-links {
                gap: var(--spacing-md);
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: var(--spacing-lg) 0;
            }
            
            .hero-left h1 {
                font-size: 1.75rem;
            }
            
            .hero-left p {
                font-size: 1rem;
            }
            
            .hero-cta {
                flex-direction: column;
            }
            
            .hero-cta .btn {
                width: 100%;
            }
            
            .grid-produk {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: var(--spacing-md);
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }

            nav {
                flex-direction: column;
                gap: var(--spacing-sm);
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 var(--spacing-sm);
            }
            
            .hero-left h1 {
                font-size: 1.5rem;
            }
            
            .section-header h2 {
                font-size: 1.5rem;
            }
            
            .grid-produk {
                grid-template-columns: 1fr;
            }
            
            .card-produk img {
                height: 180px;
            }
        }
    </style>
</head>
<body>

<!-- Header / Navigation -->
<header>
    <div class="container">
        <nav>
            <a href="index.php" class="logo">SAYURPKY</a>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="pages/produk.php">Produk</a></li>
                <li><a href="pages/edukasi.php">Edukasi</a></li>
                <li><a href="pages/resep.php">Resep</a></li>
                <li><a href="pages/about.php">Tentang</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-left">
            <h1>Sayur Segar, Langsung dari Petani ke Meja Anda</h1>
            <p>Nikmati bahan-bahan organik berkualitas setiap hari. Pilih, pesan, dan kami kirimkan dengan cepat — sehat untuk keluarga, ramah lingkungan.</p>
            <div class="hero-cta">
                <a href="pages/produk.php" class="btn">Belanja Sekarang</a>
                <a href="pages/about.php" class="btn secondary">Tentang Kami</a>
            </div>
        </div>
        <div class="hero-right">
            <img src="assets/img/backround.jpeg" alt="Sayur Segar">
        </div>
    </div>
</section>

<!-- Produk Terbaru Section -->
<section class="container">
    <div class="section-header">
        <h2>Produk Terbaru</h2>
        <a href="pages/produk.php">Lihat semua produk &rarr;</a>
    </div>

    <div class="grid-produk">
        <?php
        $query = $conn->query("SELECT * FROM tbl_produk WHERE status='tersedia' ORDER BY created_at DESC LIMIT 8");
        if($query && $query->num_rows > 0){
            while ($row = $query->fetch_assoc()) {
                $img = 'assets/img/'.($row['gambar'] ? $row['gambar'] : 'placeholder.svg');
                $nama = htmlspecialchars($row['nama_produk']);
                $stok = intval($row['stok']);
                $berat = htmlspecialchars($row['berat']);
                $harga = number_format($row['harga'], 0, ',', '.');
                $id = $row['id_produk'];
                ?>
                <div class="card-produk">
                    <img src="<?php echo $img;?>" alt="<?php echo $nama;?>" loading="lazy">
                    <h3><?php echo $nama;?></h3>
                    <p class="muted">
                        <span>📦 Stok: <?php echo $stok;?></span>
                        <span>⚖️ <?php echo $berat;?> kg</span>
                    </p>
                    <p class="price">Rp <?php echo $harga;?></p>
                    <a href="pages/detail_produk.php?id=<?php echo $id;?>" class="btn">Lihat Detail</a>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <h3>Belum Ada Produk</h3>
                <p>Produk akan segera tersedia. Silakan cek kembali nanti.</p>
            </div>
            <?php
        }
        ?>
    </div>
</section>

<!-- Keunggulan Section -->
<section class="features-section">
    <div class="container">
        <h2>Mengapa Memilih Kami?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <span class="icon">🌿</span>
                <h3>100% Organik</h3>
                <p>Semua produk ditanam tanpa pestisida dan bahan kimia berbahaya</p>
            </div>
            <div class="feature-card">
                <span class="icon">🚚</span>
                <h3>Pengiriman Cepat</h3>
                <p>Kami pastikan sayuran sampai dalam kondisi segar di hari yang sama</p>
            </div>
            <div class="feature-card">
                <span class="icon">👨‍🌾</span>
                <h3>Langsung dari Petani</h3>
                <p>Mendukung petani lokal dengan harga yang adil untuk semua</p>
            </div>
        </div>
    </div>
</section>

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
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="pages/produk.php">Produk</a></li>
                    <li><a href="pages/edukasi.php">Edukasi</a></li>
                    <li><a href="pages/resep.php">Resep</a></li>
                    <li><a href="pages/about.php">Tentang Kami</a></li>
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