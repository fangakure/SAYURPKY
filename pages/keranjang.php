<?php
session_start();
include "../config/db.php";

// Cek jika user belum login
if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: login.php");
    exit;
}

// Fungsi untuk sync keranjang session ke database
function syncCartToDatabase($conn, $user_id) {
    if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])) {
        // Jika keranjang kosong, hapus semua item user dari database
        $delete_query = $conn->prepare("DELETE FROM tbl_keranjang WHERE id_user = ?");
        $delete_query->bind_param("i", $user_id);
        $delete_query->execute();
        return;
    }

    // Ambil data keranjang user dari database
    $db_cart = array();
    $query = $conn->prepare("SELECT id_produk, jumlah FROM tbl_keranjang WHERE id_user = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $db_cart[$row['id_produk']] = $row['jumlah'];
    }

    // Sync dari session ke database
    foreach ($_SESSION['keranjang'] as $id_produk => $jumlah) {
        $produk = $conn->query("SELECT harga FROM tbl_produk WHERE id_produk = $id_produk")->fetch_assoc();
        $subtotal = $produk['harga'] * $jumlah;
        
        if (isset($db_cart[$id_produk])) {
            // Update item yang sudah ada
            $update_query = $conn->prepare("UPDATE tbl_keranjang SET jumlah = ?, subtotal = ?, updated_at = NOW() WHERE id_user = ? AND id_produk = ?");
            $update_query->bind_param("idii", $jumlah, $subtotal, $user_id, $id_produk);
            $update_query->execute();
        } else {
            // Tambah item baru
            $insert_query = $conn->prepare("INSERT INTO tbl_keranjang (id_user, id_produk, jumlah, subtotal, tgl_ditambahkan, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $insert_query->bind_param("iiid", $user_id, $id_produk, $jumlah, $subtotal);
            $insert_query->execute();
        }
    }

    // Hapus item dari database yang tidak ada di session
    foreach ($db_cart as $id_produk => $jumlah) {
        if (!isset($_SESSION['keranjang'][$id_produk])) {
            $delete_query = $conn->prepare("DELETE FROM tbl_keranjang WHERE id_user = ? AND id_produk = ?");
            $delete_query->bind_param("ii", $user_id, $id_produk);
            $delete_query->execute();
        }
    }
}

// Fungsi untuk load keranjang dari database ke session
function loadCartFromDatabase($conn, $user_id) {
    $query = $conn->prepare("SELECT k.id_produk, k.jumlah, p.stok 
                            FROM tbl_keranjang k 
                            JOIN tbl_produk p ON k.id_produk = p.id_produk 
                            WHERE k.id_user = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    
    $_SESSION['keranjang'] = array();
    
    while ($row = $result->fetch_assoc()) {
        // Cek stok tersedia
        if ($row['stok'] >= $row['jumlah']) {
            $_SESSION['keranjang'][$row['id_produk']] = $row['jumlah'];
        }
    }
}

// Load keranjang dari database jika session keranjang kosong
if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])) {
    $user_id = $_SESSION['user']['id_user']; // Asumsi session user menyimpan id_user
    loadCartFromDatabase($conn, $user_id);
}

// Tambah produk ke keranjang
if (isset($_POST['action']) && isset($_POST['id_produk']) && isset($_POST['jumlah'])) {
    $id_produk = $_POST['id_produk'];
    $jumlah = (int)$_POST['jumlah'];
    $user_id = $_SESSION['user']['id_user'];
    
    // Cek stok produk
    $query = $conn->prepare("SELECT stok FROM tbl_produk WHERE id_produk = ?");
    $query->bind_param("i", $id_produk);
    $query->execute();
    $result = $query->get_result();
    $produk = $result->fetch_assoc();
    
    if ($produk && $produk['stok'] >= $jumlah) {
        // Inisialisasi keranjang jika belum ada
        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = array();
        }
        
        // Tambah atau update jumlah di keranjang
        if (isset($_SESSION['keranjang'][$id_produk])) {
            $_SESSION['keranjang'][$id_produk] += $jumlah;
        } else {
            $_SESSION['keranjang'][$id_produk] = $jumlah;
        }
        
        // Sync ke database
        syncCartToDatabase($conn, $user_id);
        
        // Redirect berdasarkan aksi
        if ($_POST['action'] === 'buy') {
            header("Location: checkout.php");
        } else {
            header("Location: keranjang.php");
        }
        exit;
    } else {
        echo "<script>alert('Stok tidak mencukupi!'); window.history.back();</script>";
        exit;
    }
}

// Hapus item dari keranjang jika ada
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $user_id = $_SESSION['user']['id_user'];
    
    if (isset($_SESSION['keranjang'][$id_hapus])) {
        unset($_SESSION['keranjang'][$id_hapus]);
        
        // Hapus dari database juga
        $delete_query = $conn->prepare("DELETE FROM tbl_keranjang WHERE id_user = ? AND id_produk = ?");
        $delete_query->bind_param("ii", $user_id, $id_hapus);
        $delete_query->execute();
    }
    header("Location: keranjang.php");
    exit;
}

// Fungsi bantu
function getCartCount() {
    return isset($_SESSION['keranjang']) ? array_sum($_SESSION['keranjang']) : 0;
}

function getCartTotal($conn) {
    $total = 0;
    if (!isset($_SESSION['keranjang'])) return $total;

    foreach ($_SESSION['keranjang'] as $id => $qty) {
        $produk = $conn->query("SELECT harga FROM tbl_produk WHERE id_produk=$id")->fetch_assoc();
        $total += $produk['harga'] * $qty;
    }
    return $total;
}

function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Keranjang Belanja | SAYURPKY</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2d6a4f;
    --primary-light: #40916c;
    --primary-dark: #1b4332;
    --accent: #52b788;
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --success: #10b981;
    --white: #ffffff;
    --gray-50: #f8f9fa;
    --gray-100: #f1f3f5;
    --gray-200: #e9ecef;
    --gray-300: #dee2e6;
    --gray-600: #6c757d;
    --gray-700: #495057;
    --gray-800: #343a40;
    --gray-900: #212529;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15);
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f6fff9 0%, #e9f8f1 100%);
    color: var(--gray-800);
    min-height: 100vh;
    padding: 24px 16px;
}

/* Header Section */
.page-header {
    text-align: center;
    margin-bottom: 32px;
    animation: fadeInDown 0.5s ease;
}

.page-header h1 {
    font-size: 36px;
    font-weight: 800;
    color: var(--primary-dark);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.page-header p {
    color: var(--gray-600);
    font-size: 16px;
}

/* Navigation */
.nav-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--white);
    color: var(--primary);
    border: 2px solid var(--primary);
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.btn-back:hover {
    background: var(--primary);
    color: var(--white);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
    animation: fadeInUp 0.6s ease;
}

.cart-wrapper {
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

/* Summary Card */
.summary-card {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    padding: 24px 32px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    border-bottom: 4px solid var(--primary-dark);
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.summary-label {
    color: rgba(255,255,255,0.9);
    font-size: 14px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.summary-value {
    color: var(--white);
    font-size: 28px;
    font-weight: 800;
}

/* Table Styles */
.table-wrapper {
    overflow-x: auto;
    padding: 32px;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
}

thead tr {
    background: transparent;
}

thead th {
    padding: 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-700);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--gray-200);
}

tbody tr {
    background: var(--white);
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    transition: var(--transition);
}

tbody tr:hover {
    border-color: var(--primary-light);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

tbody td {
    padding: 20px 16px;
    vertical-align: middle;
}

tbody tr td:first-child {
    border-top-left-radius: var(--radius-lg);
    border-bottom-left-radius: var(--radius-lg);
}

tbody tr td:last-child {
    border-top-right-radius: var(--radius-lg);
    border-bottom-right-radius: var(--radius-lg);
}

/* Product Image */
.product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    border: 2px solid var(--gray-200);
}

/* Product Info */
.product-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.product-name {
    font-weight: 700;
    color: var(--gray-900);
    font-size: 16px;
}

.product-meta {
    font-size: 13px;
    color: var(--gray-600);
}

/* Price & Quantity */
.price {
    font-weight: 700;
    color: var(--primary);
    font-size: 16px;
}

.quantity-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-light), var(--accent));
    color: var(--white);
    font-weight: 700;
    font-size: 16px;
    padding: 8px 16px;
    border-radius: 20px;
    min-width: 50px;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition);
    cursor: pointer;
    border: none;
    box-shadow: var(--shadow-sm);
}

.btn-delete {
    background: linear-gradient(135deg, var(--danger), var(--danger-dark));
    color: var(--white);
}

.btn-delete:hover {
    background: var(--danger-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Total Row */
.total-row {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7) !important;
    border: 2px solid var(--success) !important;
}

.total-row td {
    font-weight: 800 !important;
    color: var(--primary-dark) !important;
    font-size: 18px;
}

/* Checkout Section */
.checkout-section {
    padding: 32px;
    background: var(--gray-50);
    border-top: 2px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.checkout-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkout-label {
    font-size: 14px;
    color: var(--gray-600);
    font-weight: 500;
}

.checkout-total {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-dark);
}

.btn-checkout {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: var(--white);
    padding: 16px 32px;
    font-size: 16px;
    font-weight: 700;
    box-shadow: var(--shadow-md);
}

.btn-checkout:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 80px 40px;
    animation: fadeIn 0.5s ease;
}

.empty-cart-icon {
    font-size: 80px;
    color: var(--gray-300);
    margin-bottom: 24px;
    animation: bounce 2s infinite;
}

.empty-cart h3 {
    font-size: 24px;
    color: var(--gray-800);
    margin-bottom: 12px;
    font-weight: 700;
}

.empty-cart p {
    font-size: 16px;
    color: var(--gray-600);
    margin-bottom: 32px;
}

.btn-shop {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: var(--white);
    padding: 14px 32px;
    font-size: 16px;
    font-weight: 700;
    border-radius: var(--radius-md);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.btn-shop:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Animations */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Responsive */
@media (max-width: 1024px) {
    .summary-card {
        grid-template-columns: repeat(2, 1fr);
    }
    
    thead th:nth-child(4),
    tbody td:nth-child(4) {
        display: none;
    }
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 28px;
    }
    
    .summary-card {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .summary-value {
        font-size: 24px;
    }
    
    .table-wrapper {
        padding: 16px;
    }
    
    thead {
        display: none;
    }
    
    tbody tr {
        display: grid;
        grid-template-columns: 80px 1fr;
        gap: 16px;
        padding: 16px;
        margin-bottom: 16px;
    }
    
    tbody td {
        padding: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    tbody td:nth-child(1) {
        grid-row: 1 / 4;
    }
    
    tbody td:nth-child(2),
    tbody td:nth-child(3),
    tbody td:nth-child(4),
    tbody td:nth-child(5) {
        grid-column: 2;
    }
    
    tbody td:nth-child(6) {
        grid-column: 1 / -1;
        margin-top: 12px;
    }
    
    .checkout-section {
        flex-direction: column;
        text-align: center;
    }
    
    .btn-checkout {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    body {
        padding: 16px 8px;
    }
    
    .nav-actions {
        flex-direction: column;
    }
    
    .btn-back {
        width: 100%;
        justify-content: center;
    }
    
    .empty-cart {
        padding: 60px 20px;
    }
    
    .empty-cart-icon {
        font-size: 60px;
    }
}

/* Loading animation untuk interaksi */
.btn:active {
    transform: scale(0.98);
}

/* Tooltip untuk tombol hapus */
.btn-delete {
    position: relative;
}

.btn-delete:hover::after {
    content: 'Hapus dari keranjang';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--gray-900);
    color: var(--white);
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    margin-bottom: 8px;
    opacity: 0;
    animation: tooltipFade 0.3s ease forwards;
}

@keyframes tooltipFade {
    to { opacity: 1; }
}
</style>
</head>
<body>

<div class="page-header">
    <h1>
        <i class="fas fa-shopping-cart"></i>
        Keranjang Belanja
    </h1>
    <p>Kelola produk yang Anda inginkan sebelum melanjutkan ke pembayaran</p>
</div>

<div class="container">
    <div class="nav-actions">
        <a href="produk.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Lanjut Belanja
        </a>
    </div>

<?php if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])): ?>
    <div class="cart-wrapper">
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <h3>Keranjang Belanja Kosong</h3>
            <p>Sepertinya Anda belum menambahkan produk apapun ke keranjang</p>
            <a href="produk.php" class="btn-shop">
                <i class="fas fa-store"></i>
                Mulai Belanja Sekarang
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="cart-wrapper">
        <!-- Summary Card -->
        <div class="summary-card">
            <div class="summary-item">
                <div class="summary-label">
                    <i class="fas fa-box"></i>
                    Total Item
                </div>
                <div class="summary-value"><?php echo getCartCount(); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">
                    <i class="fas fa-tags"></i>
                    Produk Berbeda
                </div>
                <div class="summary-value"><?php echo count($_SESSION['keranjang']); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">
                    <i class="fas fa-money-bill-wave"></i>
                    Total Belanja
                </div>
                <div class="summary-value"><?php echo rupiah(getCartTotal($conn)); ?></div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Informasi</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['keranjang'] as $id => $qty):
                        $produk = $conn->query("SELECT * FROM tbl_produk WHERE id_produk=$id")->fetch_assoc();
                        $sub = $produk['harga'] * $qty;
                        $total += $sub;
                    ?>
                    <tr>
                        <td>
                            <img src="../assets/img/<?php echo htmlspecialchars($produk['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($produk['nama_produk']); ?>" 
                                 class="product-image">
                        </td>
                        <td>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($produk['nama_produk']); ?></div>
                                <div class="product-meta">
                                    <i class="fas fa-weight"></i> <?php echo $produk['berat']; ?> kg
                                    <span style="margin: 0 8px;">•</span>
                                    <i class="fas fa-box-open"></i> Stok: <?php echo $produk['stok']; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="price"><?php echo rupiah($produk['harga']); ?></span>
                        </td>
                        <td>
                            <span class="quantity-badge"><?php echo intval($qty); ?>x</span>
                        </td>
                        <td>
                            <span class="price" style="font-size: 18px; font-weight: 800;">
                                <?php echo rupiah($sub); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="keranjang.php?hapus=<?php echo $id; ?>" 
                                   class="btn btn-delete"
                                   onclick="return confirm('⚠️ Yakin ingin menghapus produk ini dari keranjang?')">
                                    <i class="fas fa-trash-alt"></i>
                                    Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right; padding-right: 24px;">
                            <i class="fas fa-calculator"></i> TOTAL BELANJA:
                        </td>
                        <td colspan="2" style="font-size: 24px;">
                            <?php echo rupiah($total); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Checkout Section -->
        <div class="checkout-section">
            <div class="checkout-info">
                <div class="checkout-label">Total yang harus dibayar:</div>
                <div class="checkout-total"><?php echo rupiah($total); ?></div>
            </div>
            <a href="checkout.php" class="btn btn-checkout">
                <i class="fas fa-credit-card"></i>
                Checkout 
            </a>
        </div>
    </div>
<?php endif; ?>
</div>

<script>
// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// Add loading state to buttons
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (confirm(this.getAttribute('onclick').replace('return confirm(\'', '').replace('\')', ''))) {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
            this.style.pointerEvents = 'none';
        } else {
            e.preventDefault();
        }
    });
});

// Add animation on page load
window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.3s ease';
        document.body.style.opacity = '1';
    }, 100);
});
</script>

</body>
</html>