<?php 
include "../config/db.php"; 
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edukasi - SAYURPKY</title>
<style>
/* ====== Styles sama seperti kode terbaru Anda ====== */
* {margin:0;padding:0;box-sizing:border-box;}
:root {
    --primary-color:#2d6a4f; --primary-dark:#1b4332; --accent-color:#52b788;
    --white:#fff; --gray-50:#f8f9fa; --gray-200:#e9ecef; --gray-600:#6c757d; --gray-700:#495057; --gray-800:#343a40; --gray-900:#212529;
    --spacing-sm:1rem; --spacing-md:1.5rem; --spacing-lg:2rem; --spacing-xl:3rem; --spacing-2xl:4rem;
    --font-main:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
    --shadow-sm:0 1px 3px rgba(0,0,0,0.1); --shadow-lg:0 10px 25px rgba(0,0,0,0.1);
    --radius-md:8px; --radius-lg:12px; --transition-base:250ms ease;
}
body {font-family:var(--font-main); background-color:var(--gray-50); color:var(--gray-800);}
.container {max-width:1200px;margin:0 auto;padding:0 var(--spacing-md);}
header {background-color:#fff; box-shadow:var(--shadow-sm); position:sticky; top:0; z-index:1000;}
nav {display:flex; justify-content:space-between; align-items:center; padding:var(--spacing-sm) 0;}
.logo {font-size:1.5rem; font-weight:700; color:var(--primary-color); text-decoration:none;}
.nav-links {display:flex; list-style:none; gap:var(--spacing-lg);}
.nav-links a {text-decoration:none; color:#495057; font-weight:500; transition:color var(--transition-base);}
.nav-links a:hover, .nav-links a.active {color:var(--primary-color);}
.page-hero {background:linear-gradient(135deg,var(--primary-dark),var(--primary-color)); color:var(--white); padding:var(--spacing-xl) 0; text-align:center; margin-bottom:var(--spacing-xl);}
.page-hero h1 {font-size:2.5rem;margin-bottom:var(--spacing-sm);}
.page-hero p {font-size:1.125rem; opacity:0.9;}
.filter-tabs {display:flex; gap:var(--spacing-sm); margin-bottom:var(--spacing-lg); flex-wrap:wrap; justify-content:center;}
.filter-tab {padding:10px 24px; border:2px solid #dee2e6; background:#fff; border-radius:var(--radius-md); cursor:pointer; transition:all var(--transition-base); font-weight:500; color:#495057; text-decoration:none; display:inline-block;}
.filter-tab:hover, .filter-tab.active {background:var(--primary-color); color:#fff; border-color:var(--primary-color);}
.edukasi-grid {display:grid; grid-template-columns:repeat(auto-fill, minmax(320px,1fr)); gap:var(--spacing-lg); margin-bottom:var(--spacing-2xl);}
.edukasi-card {background:#fff; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm); transition:all var(--transition-base); border:1px solid #e9ecef;}
.edukasi-card:hover {transform:translateY(-8px); box-shadow:var(--shadow-lg);}
.edukasi-image {width:100%; height:200px; object-fit:cover; background:#f1f3f5;}
.edukasi-content {padding:var(--spacing-md);}
.edukasi-badge {display:inline-block; padding:4px 12px; background:var(--accent-color); color:#fff; border-radius:20px; font-size:0.875rem; font-weight:600; margin-bottom:var(--spacing-sm);}
.edukasi-card h3 {font-size:1.25rem; color:#343a40; margin-bottom:var(--spacing-sm); line-height:1.3;}
.edukasi-excerpt {color:#6c757d; font-size:0.95rem; margin-bottom:var(--spacing-md); display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;}
.edukasi-meta {display:flex; justify-content:space-between; align-items:center; padding-top:var(--spacing-sm); border-top:1px solid #e9ecef; font-size:0.875rem; color:#6c757d;}
.btn-detail {padding:8px 20px; background:var(--primary-color); color:#fff; text-decoration:none; border-radius:var(--radius-md); font-weight:600; transition:all var(--transition-base); display:inline-block; margin-top: var(--spacing-sm);}
.btn-detail:hover {background:var(--primary-dark);}
.empty-state {text-align:center; padding:var(--spacing-2xl); grid-column:1/-1;}
.empty-state h3 {font-size:1.5rem; color:#495057; margin-bottom:var(--spacing-sm);}
.video-wrapper {position:relative; width:100%; padding-bottom:56.25%; height:0; overflow:hidden; background:#000;}
.video-wrapper iframe {position:absolute; top:0; left:0; width:100%; height:100%; border:none;}

footer {background-color:var(--gray-900); color:#dee2e6; padding:var(--spacing-xl) 0 var(--spacing-md);}
.footer-content {display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:var(--spacing-lg); margin-bottom:var(--spacing-lg);}
.footer-section h3 {color:#fff; margin-bottom:var(--spacing-sm);}
.footer-section ul {list-style:none;}
.footer-section a {color:#dee2e6; text-decoration:none; transition:color var(--transition-base);}
.footer-section a:hover {color:var(--accent-color);}
.footer-bottom {text-align:center; padding-top:var(--spacing-md); border-top:1px solid #495057;}
@media (max-width:768px){.page-hero h1{font-size:2rem;}.edukasi-grid{grid-template-columns:1fr;} nav{flex-direction:column;gap:var(--spacing-sm);}.nav-links{flex-wrap:wrap; justify-content:center;}.data-table{font-size:0.8rem;}.data-table th,.data-table td{padding:8px;}}
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
                <li><a href="edukasi.php" class="active">Edukasi</a></li>
                <li><a href="resep.php">Resep</a></li>
                <li><a href="about.php">Tentang</a></li>
            </ul>
        </nav>
    </div>
</header>

<section class="page-hero">
    <div class="container">
        <h1>📚 Edukasi Kesehatan</h1>
        <p>Tips, artikel, dan informasi seputar kesehatan, nutrisi, dan gaya hidup sehat</p>
    </div>
</section>

<section class="container">
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <?php
        $kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
        $tabs = ['semua'=>'📋 Semua','tips'=>'💡 Tips','artikel'=>'📰 Artikel','video'=>'🎥 Video'];
        foreach($tabs as $key=>$label){
            echo '<a href="edukasi.php'.($key=='semua'?'':'?kategori='.$key).'" class="filter-tab '.($kategori_filter==$key?'active':'').'">'.$label.'</a>';
        }
        ?>
    </div>

    <!-- Grid Edukasi -->
    <div class="edukasi-grid">
        <?php
        $sql = "SELECT * FROM tbl_edukasi WHERE is_published=1";
        if($kategori_filter!='semua') $sql .= " AND kategori='".$conn->real_escape_string($kategori_filter)."'";
        $sql .= " ORDER BY created_at DESC";
        $query = $conn->query($sql);

        if($query && $query->num_rows>0){
            while($row=$query->fetch_assoc()){
                $id = $row['id_edukasi'];
                $judul = htmlspecialchars($row['judul']);
                $konten = htmlspecialchars($row['konten']);
                $kategori = ucfirst($row['kategori']);
                $gambar = $row['gambar'] ? '../assets/img/'.$row['gambar'] : '../assets/img/placeholder.svg';
                $video_url = $row['video_url'];
                $views = $row['views'];
                $tanggal = date('d M Y', strtotime($row['created_at']));
                $excerpt = strlen($konten)>150?substr($konten,0,150).'...':$konten;
                
                $badge_icons = ['tips'=>'💡','artikel'=>'📰','video'=>'🎥'];
                $badge_icon = isset($badge_icons[strtolower($row['kategori'])]) ? $badge_icons[strtolower($row['kategori'])] : '📋';
                
                // Konversi URL YouTube ke embed URL
                $embed_url = $video_url;
                if (!empty($video_url)) {
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
                }
                ?>
                <div class="edukasi-card">
                    <?php if($row['kategori']=='video' && !empty($embed_url)): ?>
                        <div class="video-wrapper">
                            <iframe src="<?php echo htmlspecialchars($embed_url); ?>" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo $gambar; ?>" alt="<?php echo $judul; ?>" class="edukasi-image">
                    <?php endif; ?>
                    <div class="edukasi-content">
                        <span class="edukasi-badge"><?php echo $badge_icon; ?> <?php echo $kategori; ?></span>
                        <h3><?php echo $judul; ?></h3>
                        <?php if($row['kategori']!='video'){ ?>
                            <p class="edukasi-excerpt"><?php echo $excerpt; ?></p>
                        <?php } ?>
                        <div class="edukasi-meta">
                            <span>👁️ <?php echo number_format($views); ?> views</span>
                            <span>📅 <?php echo $tanggal; ?></span>
                        </div>
                        <a href="detail_edukasi.php?id=<?php echo $id; ?>" class="btn-detail">Baca Selengkapnya</a>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="empty-state"><h3>🔍 Tidak Ada Konten</h3><p>Belum ada konten edukasi untuk kategori ini.</p></div>';
        }
        ?>
    </div>

</section>

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
                <h3>Kontak Kami</h3>
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