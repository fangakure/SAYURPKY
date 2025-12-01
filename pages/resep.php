<?php
// make navigation links relative to site structure when included from /pages/
$base_url = "../";
$page_title = "Resep";
include $base_url . "config/db.php";
include $base_url . "includes/header.php";
?>

<!-- Hero Section -->
<section class="resep-hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Koleksi Resep <span class="highlight">Istimewa</span></h1>
            <p class="hero-subtitle">Temukan inspirasi masakan lezat dari bahan-bahan segar pilihan kami</p>
        </div>
    </div>
</section>

<!-- Resep Grid Section -->
<section class="resep-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2>Resep Terbaru</h2>
                <p class="section-description">Dari dapur kami untuk keluarga Anda</p>
            </div>
        </div>

        <div class="resep-grid">
            <?php
            $result = $conn->query("SELECT r.* FROM tbl_resep r ORDER BY r.created_at DESC");
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Get author name
                    $author = 'Admin SAYURPKY';
                    if (!empty($row['id_user'])) {
                        $uid = intval($row['id_user']);
                        $ures = $conn->query("SELECT nama FROM tbl_user WHERE id_user=$uid LIMIT 1");
                        if ($ures && $ures->num_rows > 0) {
                            $urow = $ures->fetch_assoc();
                            if (isset($urow['nama']) && $urow['nama'] !== '') {
                                $author = htmlspecialchars($urow['nama']);
                            }
                        }
                    }

                    // Ambil data dari database
                    $id_resep = isset($row['id_resep']) ? $row['id_resep'] : 0;
                    $judul_resep = isset($row['judul_resep']) ? htmlspecialchars($row['judul_resep']) : 'Resep Spesial';
                    $deskripsi = isset($row['deskripsi']) ? htmlspecialchars($row['deskripsi']) : '';
                    $bahan = isset($row['bahan']) ? $row['bahan'] : '';
                    $cara_membuat = isset($row['cara_membuat']) ? $row['cara_membuat'] : '';
                    $waktu_memasak = isset($row['waktu_memasak']) ? intval($row['waktu_memasak']) : 30;
                    $porsi = isset($row['porsi']) ? intval($row['porsi']) : 4;
                    $tingkat_kesulitan = isset($row['tingkat_kesulitan']) ? htmlspecialchars($row['tingkat_kesulitan']) : 'Sedang';
                    $gambar = isset($row['gambar']) && !empty($row['gambar']) ? $base_url . 'assets/img/' . $row['gambar'] : $base_url . 'assets/img/recipe-placeholder.jpg';
                    
                    // Split bahan dan cara membuat ke dalam array
                    $bahan_array = array_filter(explode("\n", $bahan));
                    $cara_array = array_filter(explode("\n", $cara_membuat));
                    
                    // Preview deskripsi
                    $preview = !empty($deskripsi) ? (mb_substr($deskripsi, 0, 120) . '...') : 'Resep lezat dan mudah dibuat untuk keluarga tercinta.';
                    
                    // Tentukan warna badge berdasarkan tingkat kesulitan
                    $difficulty_colors = [
                        'Mudah' => '#4caf50',
                        'Sedang' => '#ff9800',
                        'Sulit' => '#f44336'
                    ];
                    $difficulty_color = isset($difficulty_colors[$tingkat_kesulitan]) ? $difficulty_colors[$tingkat_kesulitan] : '#ff9800';
                    ?>
                    
                    <div class="resep-card" data-category="all">
                        <div class="resep-card-header">
                            <div class="resep-image">
                                <img src="<?php echo $gambar; ?>" alt="<?php echo $judul_resep; ?>" onerror="this.src='<?php echo $base_url; ?>assets/img/placeholder.svg'">
                                <div class="recipe-badge" style="background: <?php echo $difficulty_color; ?>;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    <?php echo $tingkat_kesulitan; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="resep-card-body">
                            <h3 class="resep-title"><?php echo $judul_resep; ?></h3>
                            
                            <div class="resep-meta">
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    <?php echo $author; ?>
                                </span>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <?php echo $waktu_memasak; ?> menit
                                </span>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                    <?php echo $porsi; ?> porsi
                                </span>
                            </div>
                            
                            <p class="resep-preview"><?php echo $preview; ?></p>
                            
                            <div class="resep-quick-info">
                                <div class="quick-info-item">
                                    <span class="info-label">Bahan</span>
                                    <span class="info-value"><?php echo count($bahan_array); ?> item</span>
                                </div>
                                <div class="quick-info-item">
                                    <span class="info-label">Langkah</span>
                                    <span class="info-value"><?php echo count($cara_array); ?> step</span>
                                </div>
                            </div>
                            
                            <button class="btn-detail" onclick="openModal(<?php echo $id_resep; ?>)">
                                Lihat Resep Lengkap
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                    <polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Hidden full content for modal -->
                        <div class="resep-full-content" id="content-<?php echo $id_resep; ?>" style="display:none;">
                            <div class="modal-image-wrapper">
                                <img src="<?php echo $gambar; ?>" alt="<?php echo $judul_resep; ?>" class="modal-featured-image">
                            </div>
                            
                            <div class="modal-header-content">
                                <h2><?php echo $judul_resep; ?></h2>
                                <p class="modal-author">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                    Oleh: <?php echo $author; ?>
                                </p>
                                
                                <?php if (!empty($deskripsi)): ?>
                                <p class="modal-description"><?php echo htmlspecialchars($deskripsi); ?></p>
                                <?php endif; ?>
                                
                                <div class="modal-stats">
                                    <div class="stat-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        <div>
                                            <span class="stat-label">Waktu</span>
                                            <span class="stat-value"><?php echo $waktu_memasak; ?> menit</span>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                        </svg>
                                        <div>
                                            <span class="stat-label">Porsi</span>
                                            <span class="stat-value"><?php echo $porsi; ?> orang</span>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        <div>
                                            <span class="stat-label">Tingkat</span>
                                            <span class="stat-value"><?php echo $tingkat_kesulitan; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($bahan_array)): ?>
                            <div class="modal-section">
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/>
                                        <path d="m3 9 2.45-4.9A2 2 0 0 1 7.24 3h9.52a2 2 0 0 1 1.8 1.1L21 9"/>
                                        <path d="M12 3v6"/>
                                    </svg>
                                    Bahan-Bahan
                                </h3>
                                <ul class="ingredient-list">
                                    <?php foreach ($bahan_array as $bahan_item): 
                                        $bahan_item = trim($bahan_item);
                                        if (!empty($bahan_item)):
                                    ?>
                                        <li><?php echo htmlspecialchars($bahan_item); ?></li>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($cara_array)): ?>
                            <div class="modal-section">
                                <h3>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="8" y1="6" x2="21" y2="6"/>
                                        <line x1="8" y1="12" x2="21" y2="12"/>
                                        <line x1="8" y1="18" x2="21" y2="18"/>
                                        <line x1="3" y1="6" x2="3.01" y2="6"/>
                                        <line x1="3" y1="12" x2="3.01" y2="12"/>
                                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                                    </svg>
                                    Cara Membuat
                                </h3>
                                <ol class="instruction-list">
                                    <?php foreach ($cara_array as $cara_item): 
                                        $cara_item = trim($cara_item);
                                        if (!empty($cara_item)):
                                    ?>
                                        <li><?php echo htmlspecialchars($cara_item); ?></li>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </ol>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php
                }
            } else {
                ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M2 12h20"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </div>
                    <h3>Belum Ada Resep</h3>
                    <p>Resep masakan akan segera hadir. Pantau terus halaman ini untuk inspirasi memasak!</p>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</section>

<!-- Modal -->
<div id="resepModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <div id="modalBody"></div>
    </div>
</div>

<style>
    /* Hero Section */
    .resep-hero {
        background: linear-gradient(135deg, #2d5016 0%, #4a7c2c 100%);
        padding: 80px 0;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .resep-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".1" fill="%23ffffff"/></svg>') no-repeat;
        background-size: cover;
        opacity: 0.1;
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1rem;
        line-height: 1.2;
    }
    
    .highlight {
        color: #a8d08d;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
        opacity: 0.95;
        line-height: 1.6;
    }
    
    /* Section */
    .resep-section {
        padding: 80px 0;
        background: #fafafa;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 50px;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .section-header h2 {
        font-size: 2.5rem;
        color: #1a1a1a;
        margin-bottom: 0.5rem;
        font-weight: 700;
    }
    
    .section-description {
        color: #666;
        font-size: 1.1rem;
    }
    
    /* Resep Grid */
    .resep-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
    }
    
    .resep-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .resep-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    
    .resep-card-header {
        position: relative;
    }
    
    .resep-image {
        position: relative;
        height: 220px;
        overflow: hidden;
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    }
    
    .resep-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .resep-card:hover .resep-image img {
        transform: scale(1.05);
    }
    
    .recipe-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(74, 124, 44, 0.95);
        color: white;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        backdrop-filter: blur(10px);
    }
    
    .resep-card-body {
        padding: 25px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    
    .resep-title {
        font-size: 1.5rem;
        color: #1a1a1a;
        margin-bottom: 15px;
        font-weight: 700;
        line-height: 1.3;
    }
    
    .resep-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #666;
        font-size: 0.9rem;
    }
    
    .meta-item svg {
        color: #4a7c2c;
    }
    
    .resep-preview {
        color: #555;
        line-height: 1.7;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }
    
    .resep-quick-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 12px;
    }
    
    .quick-info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .info-label {
        font-size: 0.85rem;
        color: #666;
        font-weight: 500;
    }
    
    .info-value {
        font-size: 1.1rem;
        color: #4a7c2c;
        font-weight: 700;
    }
    
    .btn-detail {
        width: 100%;
        padding: 14px 24px;
        background: linear-gradient(135deg, #4a7c2c 0%, #5a9c3c 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: auto;
    }
    
    .btn-detail:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(74, 124, 44, 0.3);
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .modal-content {
        background: white;
        margin: 30px auto;
        padding: 0;
        max-width: 900px;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 20px;
        position: relative;
        animation: slideUp 0.3s ease;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .modal-close {
        position: sticky;
        top: 20px;
        right: 20px;
        float: right;
        font-size: 2rem;
        font-weight: 700;
        color: #666;
        background: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 1;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .modal-close:hover {
        background: #f44336;
        color: white;
        transform: rotate(90deg);
    }
    
    #modalBody {
        padding: 0;
    }
    
    .modal-image-wrapper {
        width: 100%;
        height: 400px;
        overflow: hidden;
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    }
    
    .modal-featured-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .modal-header-content {
        padding: 40px;
        padding-bottom: 30px;
        border-bottom: 2px solid #e9ecef;
    }
    
    .modal-header-content h2 {
        font-size: 2.5rem;
        color: #1a1a1a;
        margin-bottom: 15px;
        font-weight: 700;
        line-height: 1.2;
    }
    
    .modal-author {
        color: #666;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 20px;
    }
    
    .modal-description {
        color: #555;
        font-size: 1.05rem;
        line-height: 1.8;
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border-left: 4px solid #4a7c2c;
    }
    
    .modal-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 25px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e9 100%);
        border-radius: 12px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .stat-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-color: #4a7c2c;
    }
    
    .stat-item svg {
        color: #4a7c2c;
        flex-shrink: 0;
    }
    
    .stat-item div {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: #666;
        font-weight: 500;
    }
    
    .stat-value {
        font-size: 1.1rem;
        color: #1a1a1a;
        font-weight: 700;
    }
    
    .modal-section {
        padding: 40px;
        padding-top: 35px;
    }
    
    .modal-section h3 {
        font-size: 1.75rem;
        color: #2d5016;
        margin-bottom: 25px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 15px;
        border-bottom: 3px solid #e9ecef;
    }
    
    .ingredient-list,
    .instruction-list {
        padding-left: 0;
        list-style: none;
    }
    
    .ingredient-list li {
        padding: 15px 20px;
        margin-bottom: 10px;
        background: #f8f9fa;
        border-radius: 10px;
        border-left: 4px solid #4a7c2c;
        transition: all 0.3s ease;
    }

    .ingredient-list li:hover {
        background: #e8f5e9;
        transform: translateX(5px);
    }

    .instruction-list li {
        padding: 15px 20px;
        margin-bottom: 10px;
        background: #f8f9fa;
        border-radius: 10px;
        border-left: 4px solid #4a7c2c;
        transition: all 0.3s ease;
        counter-increment: step-counter;
        position: relative;
    }

    .instruction-list li::before {
        content: counter(step-counter);
        position: absolute;
        left: -35px;
        top: 50%;
        transform: translateY(-50%);
        background: #4a7c2c;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .instruction-list li:hover {
        background: #e8f5e9;
        transform: translateX(5px);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 100px 20px;
        color: #666;
    }

    .empty-icon {
        margin-bottom: 20px;
        color: #4a7c2c;
    }

    .empty-state h3 {
        font-size: 1.8rem;
        margin-bottom: 10px;
        font-weight: 700;
    }

    .empty-state p {
        font-size: 1.1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .resep-grid {
            grid-template-columns: 1fr;
        }

        .modal-content {
            width: 95%;
            margin: 20px auto;
        }

        .modal-image-wrapper {
            height: 250px;
        }

        .modal-header-content {
            padding: 25px;
        }

        .modal-section {
            padding: 25px;
        }

        .hero-title {
            font-size: 2.2rem;
        }
    }
</style>

<script>
    function openModal(id) {
        const modal = document.getElementById("resepModal");
        const modalBody = document.getElementById("modalBody");
        const content = document.getElementById("content-" + id);

        if (content && modal && modalBody) {
            modalBody.innerHTML = content.innerHTML;
            modal.style.display = "block";
            document.body.style.overflow = "hidden";
        }
    }

    function closeModal() {
        const modal = document.getElementById("resepModal");
        const modalBody = document.getElementById("modalBody");

        if (modal && modalBody) {
            modal.style.display = "none";
            modalBody.innerHTML = "";
            document.body.style.overflow = "auto";
        }
    }

    // Tutup modal saat klik di luar konten
    window.onclick = function(event) {
        const modal = document.getElementById("resepModal");
        if (event.target === modal) {
            closeModal();
        }
    }
</script>

<?php include $base_url . "includes/footer.php"; ?>
