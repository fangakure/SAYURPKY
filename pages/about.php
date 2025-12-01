<?php include "../config/db.php"; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - SAYURPKY</title>
    <style>
        /* ===== RESET & BASE STYLES ===== */
        * {margin:0; padding:0; box-sizing:border-box;}
        :root {
            --primary-color: #2d6a4f;
            --primary-dark: #1b4332;
            --primary-light: #40916c;
            --secondary-color: #f77f00;
            --accent-color: #52b788;
            --accent-light: #a8d08d;
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
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.15);
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --transition-fast: 150ms ease;
            --transition-base: 250ms ease;
            --transition-slow: 350ms ease;
        }
        body {font-family: var(--font-main); font-size:16px; line-height:1.6; color:var(--gray-800); background-color:var(--gray-50); min-height:100vh;}
        .container {max-width:1200px; margin:0 auto; padding:0 var(--spacing-md);}
        img {max-width:100%; height:auto; display:block;}

        /* ===== HEADER / NAVIGATION ===== */
        header {background-color:var(--white); box-shadow:var(--shadow-sm); position:sticky; top:0; z-index:1000;}
        nav {display:flex; justify-content:space-between; align-items:center; padding:var(--spacing-sm) 0;}
        .logo {font-size:1.5rem; font-weight:700; color:var(--primary-color); text-decoration:none; display:flex; align-items:center; gap:0.5rem;}
        .nav-links {display:flex; list-style:none; gap:var(--spacing-lg); align-items:center;}
        .nav-links a {text-decoration:none; color:var(--gray-700); font-weight:500; transition: color var(--transition-fast);}
        .nav-links a:hover, .nav-links a.active {color:var(--primary-color);}

        /* ===== HERO SECTION ===== */
        .about-hero {background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%); padding:var(--spacing-2xl) 0; color:var(--white); text-align:center; position:relative; overflow:hidden;}
        .about-hero::before {content:''; position:absolute; top:0; left:0; right:0; bottom:0; background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".1" fill="%23ffffff"/></svg>') no-repeat; background-size: cover; opacity:0.1;}
        .hero-content {position:relative; z-index:1; max-width:800px; margin:0 auto;}
        .hero-title {font-family: var(--font-heading); font-size:3rem; font-weight:700; margin-bottom:var(--spacing-md); line-height:1.2;}
        .highlight {color:var(--accent-light);}
        .hero-subtitle {font-size:1.25rem; opacity:0.95; line-height:1.6;}

        /* ===== STORY SECTION ===== */
        .about-story {padding: var(--spacing-2xl) 0; background:var(--white);}
        .story-grid {display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-xl); align-items:center;}
        .story-image img {width:100%; border-radius:var(--radius-xl); box-shadow:var(--shadow-xl);}
        .label {display:inline-block; color:var(--primary-color); font-weight:600; font-size:0.9rem; text-transform:uppercase; letter-spacing:2px; margin-bottom:var(--spacing-sm);}
        .story-content h2 {font-family: var(--font-heading); font-size:2.25rem; color:var(--gray-900); margin-bottom:var(--spacing-md); line-height:1.3;}
        .story-content p {font-size:1.1rem; line-height:1.8; color:var(--gray-700); margin-bottom:var(--spacing-md);}

        /* ===== VISION MISSION SECTION ===== */
        .vision-mission {padding: var(--spacing-2xl) 0; background:var(--gray-50);}
        .vm-grid {display:grid; grid-template-columns:1fr 1fr; gap:var(--spacing-lg);}
        .vm-card {padding:var(--spacing-xl); border-radius:var(--radius-xl); box-shadow:var(--shadow-md); transition:transform var(--transition-base), box-shadow var(--transition-base);}
        .vm-card:hover {transform:translateY(-5px); box-shadow:var(--shadow-xl);}
        .vision-card {background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%); color:var(--white);}
        .mission-card {background:var(--white);}
        .vm-icon {width:60px; height:60px; margin-bottom:var(--spacing-md);}
        .vm-icon svg {width:100%; height:100%; stroke:currentColor;}
        .vm-card h3 {font-family:var(--font-heading); font-size:1.75rem; margin-bottom:var(--spacing-md); font-weight:700;}
        .vm-card > p {font-size:1.1rem; line-height:1.8; opacity:0.95;}
        .mission-list {display:flex; flex-direction:column; gap:var(--spacing-lg); margin-top:var(--spacing-md);}
        .mission-item {display:flex; gap:var(--spacing-md); align-items:flex-start;}
        .mission-number {font-size:1.5rem; font-weight:700; color:var(--primary-color); min-width:50px;}
        .mission-item p {font-size:1rem; line-height:1.7; color:var(--gray-700); margin:0;}

        /* ===== VALUES SECTION ===== */
        .values {padding:var(--spacing-2xl) 0; background:var(--white);}
        .section-header {text-align:center; margin-bottom:var(--spacing-xl);}
        .section-header h2 {font-family:var(--font-heading); font-size:2.25rem; color:var(--gray-900); margin-bottom:var(--spacing-sm); font-weight:700;}
        .section-header p {font-size:1.1rem; color:var(--gray-600);}
        .values-grid {display:grid; grid-template-columns:repeat(4,1fr); gap:var(--spacing-lg);}
        .value-card {background:var(--gray-50); padding:var(--spacing-lg); border-radius:var(--radius-lg); text-align:center; border:2px solid var(--gray-200); transition:all var(--transition-base);}
        .value-card:hover {transform:translateY(-8px); box-shadow:var(--shadow-lg); border-color:var(--primary-light); background:var(--white);}
        .value-icon {font-size:3rem; margin-bottom:var(--spacing-md);}
        .value-card h4 {font-size:1.25rem; color:var(--gray-900); margin-bottom:var(--spacing-sm); font-weight:600;}
        .value-card p {font-size:0.95rem; color:var(--gray-600); line-height:1.6;}

        /* ===== TEAM SECTION ===== */
        .team {padding:var(--spacing-2xl) 0; background:var(--gray-50);}
        .team-grid {display:grid; grid-template-columns:repeat(4,1fr); gap:var(--spacing-lg); text-align:center;}
        .team-card {background:var(--white); border-radius:var(--radius-xl); box-shadow:var(--shadow-md); padding:var(--spacing-md); transition:transform var(--transition-base), box-shadow var(--transition-base);}
        .team-card:hover {transform:translateY(-5px); box-shadow:var(--shadow-xl);}
        .team-card img {
            width: 120px; 
            height: 120px; 
            object-fit: cover; 
            border-radius: 50%; 
            margin: 0 auto var(--spacing-md);
            border: 3px solid var(--primary-light);
            display: block;
        }
        .team-card h4 {
            font-size: 1.2rem; 
            margin-bottom: var(--spacing-xs); 
            font-weight: 600; 
            color: var(--gray-900);
            text-align: center;
        }
        .team-card p {
            font-size: 0.95rem; 
            color: var(--gray-600);
            text-align: center;
        }
        
        /* ===== CTA SECTION ===== */
        .cta-section {padding:var(--spacing-2xl) 0; background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%); color:var(--white); text-align:center;}
        .cta-content h2 {font-family:var(--font-heading); font-size:2.25rem; margin-bottom:var(--spacing-sm); font-weight:700;}
        .cta-content p {font-size:1.2rem; margin-bottom:var(--spacing-lg); opacity:0.95;}
        .cta-button {display:inline-block; background:var(--white); color:var(--primary-color); padding:16px 48px; border-radius:50px; font-weight:700; font-size:1.1rem; text-decoration:none; transition:all var(--transition-base); box-shadow:var(--shadow-md);}
        .cta-button:hover {transform:translateY(-3px); box-shadow:var(--shadow-xl); background:var(--gray-100);}

        /* ===== FOOTER ===== */
        footer {background-color:var(--gray-900); color:var(--gray-300); padding:var(--spacing-xl) 0 var(--spacing-md); margin-top:0;}
        .footer-content {display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:var(--spacing-lg); margin-bottom:var(--spacing-lg);}
        .footer-section h3 {color:var(--white); margin-bottom:var(--spacing-sm); font-size:1.125rem;}
        .footer-section p, .footer-section ul {font-size:0.95rem; line-height:1.8;}
        .footer-section ul {list-style:none;}
        .footer-section ul li {margin-bottom:var(--spacing-xs);}
        .footer-section a {color:var(--gray-300); text-decoration:none; transition: color var(--transition-fast);}
        .footer-section a:hover {color:var(--accent-color);}
        .footer-bottom {text-align:center; padding-top:var(--spacing-md); border-top:1px solid var(--gray-700); font-size:0.875rem;}

        /* ===== RESPONSIVE ===== */
        @media(max-width:968px){.story-grid,.vm-grid{grid-template-columns:1fr; gap:var(--spacing-lg);}.values-grid{grid-template-columns:repeat(2,1fr);}.team-grid{grid-template-columns:repeat(2,1fr);}.nav-links{gap:var(--spacing-md);}}
        @media(max-width:768px){.hero-title{font-size:2rem;}.hero-subtitle{font-size:1.1rem;}.story-content h2,.section-header h2,.cta-content h2{font-size:1.75rem;}.about-hero,.about-story,.vision-mission,.values,.cta-section{padding:var(--spacing-xl) 0;}.values-grid{grid-template-columns:1fr;}nav{flex-direction:column; gap:var(--spacing-sm);}.nav-links{flex-wrap:wrap; justify-content:center;}}
        @media(max-width:480px){.container{padding:0 var(--spacing-sm);}.hero-title{font-size:1.75rem;}.vm-card{padding:var(--spacing-lg);}.team-card img{width:100px; height:100px;}.team-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<!-- Header / Navigation -->
<header>
    <div class="container">
        <nav>
            <a href="../index.php" class="logo">SAYURPKY</a>
            <ul class="nav-links">
                <li><a href="../index.php">Beranda</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="edukasi.php">Edukasi</a></li>
                <li><a href="resep.php">Resep</a></li>
                <li><a href="about.php" class="active">Tentang</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Kesegaran Alami, <span class="highlight">Langsung ke Pintu Anda</span></h1>
            <p class="hero-subtitle">Revolusi belanja kebutuhan dapur yang menghubungkan kesegaran alam dengan kemudahan digital</p>
        </div>
    </div>
</section>

<!-- Story Section -->
<section class="about-story">
    <div class="container">
        <div class="story-grid">
            <div class="story-image">
                <img src="../assets/img/2.jpeg" alt="Sayuran Segar SAYURPKY">
            </div>
            <div class="story-content">
                <span class="label">Tentang Kami</span>
                <h2>SAYURPKY: Jembatan Antara Petani dan Keluarga Indonesia</h2>
                <p>Kami percaya bahwa setiap keluarga berhak mendapatkan bahan makanan segar berkualitas tinggi tanpa harus meninggalkan kenyamanan rumah. SAYURPKY hadir sebagai solusi inovatif yang menggabungkan kesegaran produk lokal dengan teknologi belanja online terkini.</p>
                <p>Dengan jaringan petani lokal terpercaya dan sistem logistik yang efisien, kami memastikan setiap sayur, buah, dan bumbu yang sampai ke tangan Anda tetap dalam kondisi prima—seperti baru dipetik dari kebun.</p>
            </div>
        </div>
    </div>
</section>

<!-- Vision Mission Section -->
<section class="vision-mission">
    <div class="container">
        <div class="vm-grid">
            <div class="vm-card vision-card">
                <div class="vm-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </div>
                <h3>Visi Kami</h3>
                <p>Menjadi ekosistem digital terdepan untuk kebutuhan dapur segar yang menghubungkan jutaan keluarga dengan hasil tani berkualitas premium di Palangkaraya dan seluruh Indonesia.</p>
            </div>
            
            <div class="vm-card mission-card">
                <div class="vm-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M2 12h20"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                </div>
                <h3>Misi Kami</h3>
                <div class="mission-list">
                    <div class="mission-item">
                        <span class="mission-number">01</span>
                        <p>Menghadirkan sayur, buah, dan bumbu dengan standar kesegaran tertinggi melalui rantai pasokan cold-chain yang terintegrasi.</p>
                    </div>
                    <div class="mission-item">
                        <span class="mission-number">02</span>
                        <p>Memberdayakan petani lokal dengan akses pasar digital yang adil dan menguntungkan, menciptakan dampak ekonomi berkelanjutan.</p>
                    </div>
                    <div class="mission-item">
                        <span class="mission-number">03</span>
                        <p>Menghadirkan pengalaman belanja yang seamless dengan teknologi terkini, jaminan kualitas 100%, dan pengiriman same-day.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="values">
    <div class="container">
        <div class="section-header">
            <h2>Nilai-Nilai Kami</h2>
            <p>Prinsip yang memandu setiap langkah perjalanan kami</p>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">🌿</div>
                <h4>Kesegaran Terjamin</h4>
                <p>Komitmen mutlak pada kualitas dan kesegaran setiap produk</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🤝</div>
                <h4>Kemitraan Berkelanjutan</h4>
                <p>Membangun hubungan jangka panjang dengan petani lokal</p>
            </div>
            <div class="value-card">
                <div class="value-icon">⚡</div>
                <h4>Inovasi Tanpa Henti</h4>
                <p>Terus berinovasi untuk pengalaman belanja yang lebih baik</p>
            </div>
            <div class="value-card">
                <div class="value-icon">💚</div>
                <h4>Peduli Lingkungan</h4>
                <p>Praktik ramah lingkungan di setiap aspek operasional</p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team">
    <div class="container">
        <div class="section-header">
            <h2>Tim Kami</h2>
            <p>Orang-orang hebat di balik SAYURPKY</p>
        </div>
        <div class="team-grid">
            <div class="team-card">
                <img src="../assets/img/Fandy.jpg" alt="Anggota Tim 1">
                <h4>Fandy</h4>
            </div>
            <div class="team-card">
                <img src="../assets/img/Arkhal.jpg" alt="Anggota Tim 2">
                <h4>Izzu</h4>
            </div>
            <div class="team-card">
                <img src="../assets/img/Agung.jpg" alt="Anggota Tim 3">
                <h4>Agung</h4>
            </div>
            <div class="team-card">
                <img src="../assets/img/Della.jpg" alt="Anggota Tim 4">
                <h4>Della</h4>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Siap Merasakan Perbedaannya?</h2>
            <p>Bergabunglah dengan SAYURPKY untuk kebutuhan dapur anda</p>
            <a href="produk.php" class="cta-button">Mulai Belanja Sekarang</a>
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
                    <li>📧 Email: info@sayurpky.com</li>
                    <li>📱 Telp: 0823-5700-0187</li>
                    <li>📍 Palangkaraya, Kalimantan Tengah</li>
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