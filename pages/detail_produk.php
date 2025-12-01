<?php 
session_start();
$base_url = "../";
include $base_url."config/db.php"; 

// Get product ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id > 0) {
    $query = $conn->prepare("SELECT * FROM tbl_produk WHERE id_produk = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();
    
    if($result->num_rows > 0) {
        $produk = $result->fetch_assoc();
        $page_title = $produk['nama_produk'];
    } else {
        header("Location: produk.php");
        exit;
    }
} else {
    header("Location: produk.php");
    exit;
}

include $base_url."includes/header.php"; 
?>

<style>
.detail-container {
    max-width: 1200px;
    margin: 60px auto;
    padding: 0 24px;
}

.breadcrumb {
    display: flex;
    gap: 8px;
    margin-bottom: 40px;
    font-size: 14px;
    color: var(--text-muted);
}

.breadcrumb a {
    color: var(--primary);
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.product-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    background: white;
    padding: 40px;
    border-radius: 14px;
    box-shadow: var(--shadow);
    margin-bottom: 60px;
}

.product-image {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.product-image img {
    width: 100%;
    height: 500px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
}

.product-info h1 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 16px;
    color: var(--text-dark);
    letter-spacing: -0.5px;
}

.product-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-size: 15px;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert.warning {
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    color: #856404;
}

.alert-link {
    color: inherit;
    font-weight: 600;
    text-decoration: underline;
}

.alert-link:hover {
    opacity: 0.8;
}

.product-price {
    font-size: 42px;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 28px;
    letter-spacing: -1px;
}

.product-description {
    margin-bottom: 32px;
    line-height: 1.8;
    color: var(--text-dark);
    font-size: 16px;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.quantity-selector label {
    font-weight: 600;
    font-size: 15px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 2px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
}

.quantity-controls button {
    background: white;
    border: none;
    padding: 12px 20px;
    font-size: 18px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    color: var(--text-dark);
}

.quantity-controls button:hover {
    background: var(--bg-light);
}

.quantity-controls input {
    border: none;
    width: 60px;
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    padding: 12px 0;
    border-left: 1px solid var(--border);
    border-right: 1px solid var(--border);
}

.quantity-controls input:focus {
    outline: none;
}

.action-buttons {
    display: flex;
    gap: 16px;
    margin-bottom: 32px;
}

.action-buttons .btn {
    flex: 1;
    padding: 18px 32px;
    font-size: 16px;
}

.product-specs {
    background: var(--bg-light);
    padding: 24px;
    border-radius: 12px;
    margin-top: 32px;
}

.product-specs h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
}

.specs-list {
    list-style: none;
}

.specs-list li {
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    font-size: 15px;
}

.specs-list li:last-child {
    border-bottom: none;
}

.specs-list strong {
    color: var(--text-dark);
    font-weight: 600;
}

.related-products {
    margin-top: 80px;
}

.related-products h2 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 40px;
    text-align: center;
}

@media (max-width: 968px) {
    .product-detail {
        grid-template-columns: 1fr;
        gap: 40px;
        padding: 30px;
    }
    
    .product-image {
        position: relative;
        top: 0;
    }
    
    .product-image img {
        height: 400px;
    }
    
    .product-info h1 {
        font-size: 28px;
    }
    
    .product-price {
        font-size: 32px;
    }
}

@media (max-width: 480px) {
    .detail-container {
        padding: 0 16px;
    }
    
    .product-detail {
        padding: 20px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .product-image img {
        height: 300px;
    }
}
</style>

<div class="detail-container">
    <div class="breadcrumb">
        <a href="<?php echo $base_url;?>index.php">Beranda</a>
        <span>/</span>
        <a href="produk.php">Produk</a>
        <span>/</span>
        <span><?php echo htmlspecialchars($produk['nama_produk']); ?></span>
    </div>

    <div class="product-detail">
        <div class="product-image">
            <?php 
            $img = $base_url.'assets/img/'.($produk['gambar'] ? $produk['gambar'] : 'placeholder.svg');
            ?>
            <img src="<?php echo $img;?>" alt="<?php echo htmlspecialchars($produk['nama_produk']);?>">
        </div>

        <div class="product-info">
            <h1><?php echo htmlspecialchars($produk['nama_produk']);?></h1>
            
            <div class="product-meta">
                <div class="meta-item">
                    <?php if($produk['stok'] > 10): ?>
                        <span class="badge success">✓ Tersedia</span>
                    <?php elseif($produk['stok'] > 0): ?>
                        <span class="badge warning">⚠ Stok Terbatas</span>
                    <?php else: ?>
                        <span class="badge danger">✗ Habis</span>
                    <?php endif; ?>
                </div>
                <div class="meta-item">
                    📦 Stok: <strong><?php echo intval($produk['stok']);?></strong>
                </div>
                <div class="meta-item">
                    ⚖️ Berat: <strong><?php echo htmlspecialchars($produk['berat']);?> kg</strong>
                </div>
            </div>

            <div class="product-price">
                Rp <?php echo number_format($produk['harga'], 0, ',', '.');?>
            </div>

            <div class="product-description">
                <p>
                    <?php 
                    if(!empty($produk['deskripsi'])) {
                        echo nl2br(htmlspecialchars($produk['deskripsi']));
                    } else {
                        echo "Produk segar berkualitas tinggi langsung dari petani lokal. Ditanam dengan metode organik tanpa pestisida berbahaya. Cocok untuk kebutuhan harian keluarga Anda.";
                    }
                    ?>
                </p>
            </div>

            <?php if($produk['stok'] > 0): ?>
                <?php if(isset($_SESSION['user'])): ?>
                <form method="POST" action="<?php echo $base_url;?>pages/keranjang.php" id="cartForm">
                    <input type="hidden" name="id_produk" value="<?php echo $produk['id_produk'];?>">
                    
                    <div class="quantity-selector">
                        <label>Jumlah:</label>
                        <div class="quantity-controls">
                            <button type="button" onclick="decreaseQty()">−</button>
                            <input type="number" id="quantity" name="jumlah" value="1" min="1" max="<?php echo $produk['stok'];?>" readonly>
                            <button type="button" onclick="increaseQty(<?php echo $produk['stok'];?>)">+</button>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" name="action" value="add" class="btn">
                            🛒 Tambah ke Keranjang
                        </button>
                        <button type="submit" name="action" value="buy" class="btn secondary">
                            ⚡ Beli Sekarang
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="alert warning">
                    <strong>Login Diperlukan!</strong> Silakan <a href="<?php echo $base_url;?>pages/login.php" class="alert-link">login</a> atau <a href="<?php echo $base_url;?>pages/register.php" class="alert-link">daftar</a> terlebih dahulu untuk melakukan tambah keranjang atau pembelian.
                </div>
                <?php endif; ?>
            <?php else: ?>
            <div class="alert error">
                <strong>Produk Habis!</strong> Maaf, produk ini sedang tidak tersedia. Silakan cek produk lainnya.
            </div>
            <?php endif; ?>

            <div class="product-specs">
                <h3>📋 Informasi Produk</h3>
                <ul class="specs-list">
                    <li>
                        <span>Kategori</span>
                        <strong><?php echo isset($produk['kategori']) ? htmlspecialchars($produk['kategori']) : 'Sayuran'; ?></strong>
                    </li>
                    <li>
                        <span>Kondisi</span>
                        <strong>Segar & Organik</strong>
                    </li>
                    <li>
                        <span>Berat per unit</span>
                        <strong><?php echo htmlspecialchars($produk['berat']);?> kg</strong>
                    </li>
                    <li>
                        <span>Status</span>
                        <strong style="color: <?php echo $produk['stok'] > 0 ? 'var(--success)' : 'red'; ?>">
                            <?php echo $produk['stok'] > 0 ? 'Tersedia' : 'Habis'; ?>
                        </strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <div class="related-products">
        <h2>Produk Terkait</h2>
        <div class="grid-produk">
            <?php
            $related_query = $conn->query("SELECT * FROM tbl_produk WHERE status='tersedia' AND id_produk != $id ORDER BY RAND() LIMIT 4");
            if($related_query && $related_query->num_rows > 0){
                while ($row = $related_query->fetch_assoc()) {
                    $img_related = $base_url.'assets/img/'.($row['gambar'] ? $row['gambar'] : 'placeholder.svg');
                    $nama = htmlspecialchars($row['nama_produk']);
                    $harga = number_format($row['harga'], 0, ',', '.');
                    ?>
                    <div class="card-produk">
                        <img src="<?php echo $img_related;?>" alt="<?php echo $nama;?>" loading="lazy">
                        <h3><?php echo $nama;?></h3>
                        <p class="muted">
                            <span>📦 Stok: <?php echo intval($row['stok']);?></span>
                            <span>⚖️ <?php echo htmlspecialchars($row['berat']);?> kg</span>
                        </p>
                        <p class="price">Rp <?php echo $harga;?></p>
                        <a href="detail_produk.php?id=<?php echo $row['id_produk'];?>" class="btn">Lihat Detail</a>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
function increaseQty(max) {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value);
    if(currentValue < max) {
        input.value = currentValue + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value);
    if(currentValue > 1) {
        input.value = currentValue - 1;
    }
}

function redirectToCheckout() {
    window.location.href = '<?php echo $base_url;?>pages/checkout.php?id_produk=<?php echo $produk['id_produk'];?>&jumlah=' + document.getElementById('quantity').value;
}
</script>

<?php include $base_url."includes/footer.php"; ?>