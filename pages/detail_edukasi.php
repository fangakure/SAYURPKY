<?php
include "../config/db.php";
session_start();

if (!isset($_GET['id'])) {
    header("Location: edukasi.php");
    exit;
}

$id = intval($_GET['id']);

// Ambil data edukasi berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM tbl_edukasi WHERE id_edukasi = ? AND is_published = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Konten tidak ditemukan!'); window.location='edukasi.php';</script>";
    exit;
}

$data = $result->fetch_assoc();

// Tambahkan jumlah views
$conn->query("UPDATE tbl_edukasi SET views = views + 1 WHERE id_edukasi = $id");

// Variabel data
$judul = htmlspecialchars($data['judul']);
$slug = htmlspecialchars($data['slug']);
$konten = $data['konten']; // Tidak di-escape dulu karena akan diproses
$kategori = ucfirst($data['kategori']);
$gambar = $data['gambar'] ? '../assets/img/' . $data['gambar'] : '../assets/img/placeholder.svg';
$video_url = $data['video_url'];
$tanggal = date('d M Y', strtotime($data['created_at']));
$views = number_format($data['views'] + 1);

// Badge icon berdasarkan kategori
$badge_icons = [
    'tips' => '💡',
    'artikel' => '📰',
    'video' => '🎥'
];
$badge_icon = isset($badge_icons[strtolower($data['kategori'])]) ? $badge_icons[strtolower($data['kategori'])] : '📋';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $judul; ?> - Sayur Segar</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-color: #2d6a4f;
        --primary-dark: #1b4332;
        --accent-color: #52b788;
        --white: #fff;
        --gray-50: #f8f9fa;
        --gray-200: #e9ecef;
        --gray-600: #6c757d;
        --gray-700: #495057;
        --gray-800: #343a40;
        --gray-900: #212529;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
        --radius-md: 8px;
        --radius-lg: 12px;
        --transition: 250ms ease;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: var(--gray-50);
        color: var(--gray-800);
        line-height: 1.6;
    }

    header {
        background-color: var(--white);
        box-shadow: var(--shadow-sm);
        padding: 1rem 0;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-weight: 700;
        color: var(--primary-color);
        text-decoration: none;
        font-size: 1.5rem;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 2rem;
    }

    .nav-links a {
        text-decoration: none;
        color: var(--gray-700);
        font-weight: 500;
        transition: color var(--transition);
    }

    .nav-links a:hover {
        color: var(--primary-color);
    }

    .breadcrumb {
        padding: 1.5rem 0;
        font-size: 0.9rem;
        color: var(--gray-600);
    }

    .breadcrumb a {
        color: var(--primary-color);
        text-decoration: none;
        transition: color var(--transition);
    }

    .breadcrumb a:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }

    main {
        max-width: 900px;
        margin: 0 auto 3rem;
        background: var(--white);
        padding: 2.5rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
    }

    .category-badge {
        display: inline-block;
        padding: 6px 16px;
        background: var(--accent-color);
        color: var(--white);
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    h1 {
        font-size: 2.25rem;
        color: var(--primary-dark);
        margin-bottom: 1rem;
        line-height: 1.3;
    }

    .meta-info {
        display: flex;
        gap: 1.5rem;
        align-items: center;
        font-size: 0.9rem;
        color: var(--gray-600);
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid var(--gray-200);
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .featured-media {
        width: 100%;
        margin-bottom: 2rem;
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .featured-image {
        width: 100%;
        height: 450px;
        object-fit: cover;
        display: block;
    }

    .video-container {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
        height: 0;
        overflow: hidden;
    }

    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        border-radius: var(--radius-md);
    }

    .content {
        font-size: 1.05rem;
        color: var(--gray-800);
        line-height: 1.8;
    }

    .content p {
        margin-bottom: 1.25rem;
    }

    .content h2 {
        font-size: 1.75rem;
        color: var(--primary-dark);
        margin: 2rem 0 1rem;
    }

    .content h3 {
        font-size: 1.4rem;
        color: var(--primary-color);
        margin: 1.5rem 0 0.75rem;
    }

    .content ul, .content ol {
        margin: 1rem 0 1.5rem 2rem;
    }

    .content li {
        margin-bottom: 0.5rem;
    }

    .content blockquote {
        border-left: 4px solid var(--accent-color);
        padding: 1rem 1.5rem;
        margin: 1.5rem 0;
        background: var(--gray-50);
        font-style: italic;
        color: var(--gray-700);
    }

    .content img {
        max-width: 100%;
        height: auto;
        border-radius: var(--radius-md);
        margin: 1.5rem 0;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2.5rem;
        padding: 12px 24px;
        background-color: var(--primary-color);
        color: var(--white);
        text-decoration: none;
        border-radius: var(--radius-md);
        font-weight: 600;
        transition: all var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .back-btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    footer {
        background-color: var(--gray-900);
        color: #dee2e6;
        padding: 3rem 0 1rem;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .footer-section h3 {
        color: var(--white);
        margin-bottom: 1rem;
    }

    .footer-section ul {
        list-style: none;
    }

    .footer-section li {
        margin-bottom: 0.5rem;
    }

    .footer-section a {
        color: #dee2e6;
        text-decoration: none;
        transition: color var(--transition);
    }

    .footer-section a:hover {
        color: var(--accent-color);
    }

    .footer-bottom {
        text-align: center;
        padding-top: 1.5rem;
        border-top: 1px solid var(--gray-700);
    }

    @media (max-width: 768px) {
        main {
            padding: 1.5rem;
            margin: 1rem;
        }

        h1 {
            font-size: 1.75rem;
        }

        .featured-image {
            height: 250px;
        }

        .meta-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
        }

        nav {
            flex-direction: column;
            gap: 1rem;
        }

        .nav-links {
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }

        .content {
            font-size: 1rem;
        }
    }
</style>
</head>
<body>

<header>
    <div class="container">
        <nav>
            <a href="../index.php" class="logo">SAYURPKY</a>
            <ul class="nav-links">
                <li><a href="../index.php">Beranda</a></li>
                <li><a href="produk.php">Produk</a></li>
                <li><a href="edukasi.php">Edukasi</a></li>
                <li><a href="resep.php">Resep</a></li>
                <li><a href="about.php">Tentang</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container">
    <div class="breadcrumb">
        <a href="../index.php">Beranda</a> › 
        <a href="edukasi.php">Edukasi</a> › 
        <a href="edukasi.php?kategori=<?php echo strtolower($data['kategori']); ?>"><?php echo $kategori; ?></a> › 
        <?php echo $judul; ?>
    </div>
</div>

<main>
    <span class="category-badge"><?php echo $badge_icon; ?> <?php echo $kategori; ?></span>
    
    <h1><?php echo $judul; ?></h1>
    
    <div class="meta-info">
        <div class="meta-item">
            <span>📅</span>
            <span><?php echo $tanggal; ?></span>
        </div>
        <div class="meta-item">
            <span>👁️</span>
            <span><?php echo $views; ?> views</span>
        </div>
        <div class="meta-item">
            <span>🔖</span>
            <span><?php echo $slug; ?></span>
        </div>
    </div>

    <?php if ($kategori === 'Video' && !empty($video_url)): ?>
        <!-- Tampilkan Video -->
        <div class="featured-media">
            <div class="video-container">
                <?php
                // Konversi URL YouTube ke format embed
                $embed_url = $video_url;
                
                // Jika URL YouTube biasa, konversi ke embed
                if (strpos($video_url, 'youtube.com/watch?v=') !== false) {
                    preg_match('/v=([^&]+)/', $video_url, $matches);
                    if (isset($matches[1])) {
                        $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
                    }
                } elseif (strpos($video_url, 'youtu.be/') !== false) {
                    preg_match('/youtu\.be\/([^?]+)/', $video_url, $matches);
                    if (isset($matches[1])) {
                        $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
                    }
                }
                ?>
                <iframe src="<?php echo htmlspecialchars($embed_url); ?>" 
                        allowfullscreen 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        </div>
    <?php else: ?>
        <!-- Tampilkan Gambar -->
        <div class="featured-media">
            <img src="<?php echo $gambar; ?>" alt="<?php echo $judul; ?>" class="featured-image">
        </div>
    <?php endif; ?>

    <div class="content">
        <?php 
        // Proses konten untuk menampilkan dengan format yang lebih baik
        // Konversi line breaks ke <p> tags untuk tampilan lebih rapi
        $konten_formatted = nl2br(htmlspecialchars($konten));
        
        // Jika konten mengandung HTML tags (untuk konten yang lebih kompleks)
        // Hapus htmlspecialchars dan gunakan langsung
        if (strip_tags($data['konten']) !== $data['konten']) {
            // Konten sudah berisi HTML
            $konten_formatted = $data['konten'];
        }
        
        echo $konten_formatted;
        ?>
    </div>

    <a href="edukasi.php" class="back-btn">
        <span>←</span>
        <span>Kembali ke Edukasi</span>
    </a>
</main>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>SAYURPKY</h3>
                <p>Menyediakan sayuran organik segar langsung dari petani lokal ke meja Anda.</p>
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
                <h3>Kontak</h3>
                <ul>
                    <li>📧 info@sayursegar.com</li>
                    <li>📱 0823-5700-0187</li>
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