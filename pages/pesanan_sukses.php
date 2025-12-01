<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user']) || !isset($_GET['order'])) {
    header("Location: login.php");
    exit;
}

$no_pesanan = $_GET['order'];
$id_user = $_SESSION['user']['id_user'];

// Ambil detail pesanan
$query = "
    SELECT p.*, i.no_invoice, i.jatuh_tempo, u.nama_lengkap as user_nama_lengkap
    FROM tbl_pesanan p
    LEFT JOIN tbl_invoice i ON p.id_pesanan = i.id_pesanan
    INNER JOIN tbl_user u ON p.id_user = u.id_user
    WHERE p.no_pesanan = ? AND p.id_user = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("si", $no_pesanan, $id_user);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();

if (!$pesanan) {
    echo "<script>alert('Pesanan tidak ditemukan!'); window.location='../index.php';</script>";
    exit;
}

// Ambil detail produk
$detail_items = $conn->query("
    SELECT * FROM tbl_detail_pesanan 
    WHERE id_pesanan = {$pesanan['id_pesanan']}
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - SAYURPKY</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary: #6366f1;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #f8fafc;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Success Header */
        .success-header {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            position: relative;
            overflow: hidden;
        }

        .success-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .success-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--success), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3);
            animation: successPulse 2s ease-in-out infinite;
        }

        @keyframes successPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 25px 50px rgba(16, 185, 129, 0.4);
            }
        }

        .success-icon i {
            font-size: 45px;
            color: white;
        }

        .success-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .success-header p {
            color: var(--gray);
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Order Number Cards */
        .order-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 2px solid var(--border);
            transition: all 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .order-card.primary {
            border-color: var(--primary);
            background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
        }

        .order-card.secondary {
            border-color: var(--warning);
            background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
        }

        .order-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            color: var(--gray);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-card.primary .order-card-header {
            color: var(--primary-dark);
        }

        .order-card.secondary .order-card-header {
            color: #d97706;
        }

        .order-card-number {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
        }

        .order-card.primary .order-card-number {
            color: var(--primary-dark);
        }

        .order-card.secondary .order-card-number {
            color: #d97706;
        }

        /* Info Card */
        .info-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 3px solid var(--light-gray);
        }

        .section-header i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-light), #a7f3d0);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 18px;
        }

        .section-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.3px;
        }

        /* Info Rows */
        .info-grid {
            display: grid;
            gap: 20px;
        }

        .info-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray);
            font-weight: 500;
            font-size: 15px;
        }

        .info-label i {
            color: var(--primary);
            font-size: 16px;
            width: 20px;
        }

        .info-value {
            text-align: right;
            color: var(--dark);
            font-weight: 600;
            font-size: 15px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        /* Product Items */
        .product-list {
            display: grid;
            gap: 12px;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: var(--light-gray);
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .product-item:hover {
            background: #e2e8f0;
            transform: translateX(5px);
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 700;
            color: var(--dark);
            font-size: 16px;
            margin-bottom: 6px;
        }

        .product-qty {
            color: var(--gray);
            font-size: 14px;
        }

        .product-price {
            color: var(--primary);
            font-weight: 800;
            font-size: 18px;
            white-space: nowrap;
        }

        /* Summary Section */
        .summary-section {
            margin-top: 30px;
            padding-top: 24px;
            border-top: 2px dashed var(--border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            font-size: 15px;
        }

        .summary-row .label {
            color: var(--gray);
            font-weight: 500;
        }

        .summary-row .value {
            color: var(--dark);
            font-weight: 700;
        }

        .summary-row.discount .value {
            color: var(--danger);
        }

        /* Total Section */
        .total-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 28px;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }

        .total-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .total-amount {
            color: white;
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.5px;
        }

        /* Alert Box */
        .alert-box {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
            border-left: 5px solid var(--secondary);
            border-radius: 16px;
            padding: 28px;
            margin-top: 30px;
        }

        .alert-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: #1e40af;
            font-weight: 700;
            font-size: 17px;
        }

        .alert-header i {
            font-size: 24px;
        }

        .alert-list {
            list-style: none;
            display: grid;
            gap: 14px;
        }

        .alert-list li {
            display: flex;
            align-items: start;
            gap: 14px;
            color: #1e40af;
            font-size: 15px;
            line-height: 1.6;
        }

        .alert-list li i {
            margin-top: 2px;
            color: var(--secondary);
            font-size: 18px;
            flex-shrink: 0;
        }

        /* Action Buttons */
        .action-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 36px;
        }

        .btn {
            padding: 18px 32px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: none;
            text-transform: none;
            letter-spacing: 0.3px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
        }

        /* Footer Link */
        .footer-link {
            text-align: center;
            margin-top: 36px;
        }

        .footer-link a {
            color: var(--gray);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .footer-link a:hover {
            color: var(--primary);
            background: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px 16px;
            }

            .success-header {
                padding: 40px 24px;
            }

            .success-header h1 {
                font-size: 26px;
            }

            .order-cards {
                grid-template-columns: 1fr;
            }

            .info-card {
                padding: 24px;
            }

            .info-item {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .info-value {
                text-align: left;
            }

            .product-item {
                flex-direction: column;
                align-items: start;
                gap: 12px;
            }

            .total-section {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .total-amount {
                font-size: 28px;
            }

            .action-section {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-header {
            animation: fadeInUp 0.6s ease;
        }

        .order-cards {
            animation: fadeInUp 0.6s ease 0.1s both;
        }

        .info-card {
            animation: fadeInUp 0.6s ease 0.2s both;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Pesanan Berhasil Dibuat!</h1>
            <p>Terima kasih telah berbelanja di SAYURPKY. Pesanan Anda sedang kami proses dengan baik.</p>
        </div>

        <!-- Order Number Cards -->
        <div class="order-cards">
            <div class="order-card primary">
                <div class="order-card-header">
                    <i class="fas fa-receipt"></i>
                    <span>Nomor Pesanan</span>
                </div>
                <div class="order-card-number"><?php echo $pesanan['no_pesanan']; ?></div>
            </div>

            <div class="order-card secondary">
                <div class="order-card-header">
                    <i class="fas fa-file-invoice"></i>
                    <span>Nomor Invoice</span>
                </div>
                <div class="order-card-number"><?php echo $pesanan['no_invoice']; ?></div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="info-card">
            <div class="section-header">
                <i class="fas fa-info-circle"></i>
                <h2>Detail Pesanan</h2>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-calendar"></i>
                        <span>Tanggal Pesanan</span>
                    </div>
                    <div class="info-value"><?php echo date('d F Y, H:i', strtotime($pesanan['tgl_pesanan'])); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Status Pesanan</span>
                    </div>
                    <div class="info-value">
                        <span class="status-badge status-pending">
                            <?php echo strtoupper($pesanan['status_pesanan']); ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-credit-card"></i>
                        <span>Status Pembayaran</span>
                    </div>
                    <div class="info-value">
                        <span class="status-badge status-pending">
                            <?php echo strtoupper(str_replace('_', ' ', $pesanan['status_pembayaran'])); ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-user"></i>
                        <span>Penerima</span>
                    </div>
                    <div class="info-value">
                        <?php 
                        if (!empty($pesanan['nama_penerima']) && $pesanan['nama_penerima'] !== '0') {
                            echo htmlspecialchars($pesanan['nama_penerima']);
                        } else {
                            echo htmlspecialchars($pesanan['user_nama_lengkap']);
                        }
                        ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-phone"></i>
                        <span>No. Telepon</span>
                    </div>
                    <div class="info-value"><?php echo $pesanan['no_telepon_penerima']; ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Alamat Pengiriman</span>
                    </div>
                    <div class="info-value">
                        <?php echo $pesanan['alamat_pengiriman']; ?>
                    </div>
                </div>

                <?php if ($pesanan['jatuh_tempo']): ?>
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-clock"></i>
                        <span>Jatuh Tempo Pembayaran</span>
                    </div>
                    <div class="info-value" style="color: var(--danger);">
                        <?php echo date('d F Y', strtotime($pesanan['jatuh_tempo'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Products -->
        <div class="info-card">
            <div class="section-header">
                <i class="fas fa-shopping-bag"></i>
                <h2>Produk yang Dipesan</h2>
            </div>

            <div class="product-list">
                <?php foreach ($detail_items as $item): ?>
                <div class="product-item">
                    <div class="product-details">
                        <div class="product-name"><?php echo $item['nama_produk']; ?></div>
                        <div class="product-qty">
                            <?php echo $item['jumlah']; ?> × Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="product-price">
                        Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-row">
                    <span class="label">Subtotal Produk</span>
                    <span class="value">Rp <?php echo number_format($pesanan['total_harga_produk'], 0, ',', '.'); ?></span>
                </div>
                <div class="summary-row">
                    <span class="label"><i class="fas fa-truck"></i> Ongkos Kirim</span>
                    <span class="value">Rp <?php echo number_format($pesanan['ongkir'], 0, ',', '.'); ?></span>
                </div>
                <?php if ($pesanan['diskon'] > 0): ?>
                <div class="summary-row discount">
                    <span class="label"><i class="fas fa-tag"></i> Diskon</span>
                    <span class="value">- Rp <?php echo number_format($pesanan['diskon'], 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Total -->
            <div class="total-section">
                <span class="total-label">Total Pembayaran</span>
                <span class="total-amount">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
            </div>
        </div>

        <!-- Alert Box -->
        <div class="alert-box">
            <div class="alert-header">
                <i class="fas fa-lightbulb"></i>
                <span>Langkah Selanjutnya</span>
            </div>
            <ul class="alert-list">
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Silakan lakukan pembayaran sesuai metode yang Anda pilih</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Upload bukti pembayaran di halaman "Pesanan Saya"</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Pesanan akan diproses setelah pembayaran dikonfirmasi</span>
                </li>
                <li>
                    <i class="fas fa-check-circle"></i>
                    <span>Anda akan mendapat notifikasi saat pesanan dikirim</span>
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="action-section">
            <a href="upload_pembayaran.php?order=<?php echo $pesanan['no_pesanan']; ?>" class="btn btn-primary">
                <i class="fas fa-upload"></i>
                <span>Upload Bukti Pembayaran</span>
            </a>
            <a href="pesanan.php" class="btn btn-secondary">
                <i class="fas fa-list"></i>
                <span>Lihat Semua Pesanan</span>
            </a>
        </div>

        <!-- Footer -->
        <div class="footer-link">
            <a href="../index.php">
                <i class="fas fa-home"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </div>

    <script>
        // Auto-redirect after 30 seconds if no action
        setTimeout(() => {
            const userAction = confirm('Apakah Anda ingin upload bukti pembayaran sekarang?');
            if (userAction) {
                window.location.href = 'upload_pembayaran.php?order=<?php echo $pesanan['no_pesanan']; ?>';
            } else {
                window.location.href = 'pesanan.php';
            }
        }, 30000);
    </script>
</body>
</html>