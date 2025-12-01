<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='login.php';</script>";
    exit;
}

// Koordinat SAYURPKY - Jalan G.Obos, Palangka Raya
define('SAYURPKY_LAT', -2.231092);
define('SAYURPKY_LNG', 113.898411);

// Hitung total dan detail keranjang
$total_harga = 0;
$total_item = 0;
$detail_keranjang = [];

if (isset($_SESSION['keranjang']) && !empty($_SESSION['keranjang'])) {
    foreach ($_SESSION['keranjang'] as $id => $qty) {
        $produk = $conn->query("SELECT * FROM tbl_produk WHERE id_produk=$id")->fetch_assoc();
        if ($produk) {
            $subtotal = $produk['harga'] * $qty;
            $total_harga += $subtotal;
            $total_item += $qty;
            $detail_keranjang[] = [
                'produk' => $produk,
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
}

// Ambil data user
$user_telepon = $_SESSION['user']['telepon'] ?? '';
$user_alamat = $_SESSION['user']['alamat'] ?? '';
$user_lat = $_SESSION['user']['latitude'] ?? '';
$user_lng = $_SESSION['user']['longitude'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SAYURPKY</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-card {
            background: white;
            border-radius: 16px;
            padding: 24px 32px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        .header-card i {
            font-size: 36px;
            color: #4CAF50;
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-card h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .checkout-wrapper {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 24px;
            align-items: start;
        }

        .checkout-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            animation: slideUp 0.6s ease;
            border: 1px solid rgba(255,255,255,0.8);
            position: relative;
            overflow: hidden;
        }

        .checkout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4CAF50, #66BB6A, #4CAF50);
            background-size: 200% 100%;
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title i {
            color: #4CAF50;
            font-size: 20px;
        }

        /* Map Container */
        #map {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 2px solid #f0f0f0;
        }

        .store-info {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #4CAF50;
        }

        .store-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 14px;
            color: #2e7d32;
        }

        .store-info-item:last-child {
            margin-bottom: 0;
        }

        .store-info-item i {
            color: #4CAF50;
            width: 20px;
        }

        .store-info-item strong {
            color: #1b5e20;
            min-width: 120px;
        }

        .location-info {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #FF9800;
        }

        .location-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }

        .location-info-item:last-child {
            margin-bottom: 0;
        }

        .location-info-item i {
            color: #FF9800;
            width: 20px;
        }

        .location-info-item strong {
            color: #333;
            min-width: 100px;
        }

        /* Form Input */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-location {
            width: 100%;
            background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-location:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 133, 244, 0.4);
        }

        .btn-location:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .alert-box {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe9a1 100%);
            border-left: 4px solid #ffc107;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: start;
            gap: 15px;
        }

        .alert-box i {
            font-size: 22px;
            color: #ff9800;
            margin-top: 2px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content strong {
            color: #856404;
            font-size: 15px;
            display: block;
            margin-bottom: 6px;
        }

        .alert-content p {
            color: #856404;
            font-size: 13px;
            line-height: 1.6;
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            background: #fafafa;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .product-item:hover {
            background: #f5f5f5;
            transform: translateX(5px);
        }

        .product-item:last-child {
            margin-bottom: 0;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .product-meta {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-price {
            color: #4CAF50;
            font-size: 17px;
            font-weight: 700;
        }

        .product-qty {
            color: #888;
            font-size: 14px;
            background: white;
            padding: 6px 15px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
        }

        .payment-methods {
            margin: 20px 0;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .payment-option:hover {
            border-color: #4CAF50;
            background: #f1f8f4;
            transform: translateX(5px);
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
            accent-color: #4CAF50;
        }

        .payment-option.selected {
            border-color: #4CAF50;
            background: #f1f8f4;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
        }

        .payment-icon {
            font-size: 24px;
            margin-right: 12px;
            color: #4CAF50;
        }

        .payment-info {
            flex: 1;
        }

        .payment-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .payment-desc {
            font-size: 12px;
            color: #888;
        }

        .summary-card {
            position: sticky;
            top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            font-size: 14px;
            color: #555;
        }

        .summary-row.divider {
            border-top: 2px dashed #e0e0e0;
            margin-top: 8px;
            padding-top: 20px;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .summary-row.total .amount {
            color: #4CAF50;
            font-size: 26px;
        }

        .summary-row.highlight {
            background: #fff3e6;
            margin: 0 -10px;
            padding: 14px 10px;
            border-radius: 8px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-secondary {
            flex: 1;
            background: white;
            color: #4CAF50;
            border: 2px solid #4CAF50;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #4CAF50;
            color: white;
            transform: translateY(-2px);
        }

        .security-badge {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .security-badge i {
            color: #4CAF50;
        }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-cart i {
            font-size: 100px;
            color: #ddd;
            margin-bottom: 25px;
        }

        .empty-cart h3 {
            color: #666;
            font-size: 22px;
            margin-bottom: 12px;
        }

        .empty-cart p {
            color: #999;
            margin-bottom: 30px;
            font-size: 15px;
        }

        .btn-back {
            background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
            color: white;
            padding: 14px 35px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .loading-content p {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Leaflet Custom Styles */
        .leaflet-popup-content-wrapper {
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
        }

        .leaflet-popup-content {
            margin: 15px 18px;
            font-size: 14px;
        }

        .leaflet-popup-content h3 {
            margin: 0 0 8px 0;
            color: #4CAF50;
            font-size: 16px;
        }

        .leaflet-popup-content p {
            margin: 5px 0;
        }

        /* Leaflet Routing Machine Custom Styles - Hide instructions */
        .leaflet-routing-container {
            display: none !important;
        }

        @media (max-width: 1024px) {
            .checkout-wrapper {
                grid-template-columns: 1fr;
            }

            .summary-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header-card {
                padding: 15px 20px;
            }

            .header-card h1 {
                font-size: 20px;
            }

            .checkout-card {
                padding: 20px;
            }

            #map {
                height: 300px;
            }

            .product-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .product-meta {
                width: 100%;
                justify-content: space-between;
                margin-top: 8px;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Mengambil lokasi Anda...</p>
        </div>
    </div>

    <div class="container">
        <div class="header-card">
            <i class="fas fa-shopping-cart"></i>
            <h1>Checkout - SAYURPKY</h1>
        </div>

        <?php if (empty($detail_keranjang)): ?>
            <div class="checkout-card empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Keranjang Belanja Kosong</h3>
                <p>Belum ada produk dalam keranjang Anda. Mulai belanja sekarang!</p>
                <a href="produk.php" class="btn-back">
                    <i class="fas fa-shopping-bag"></i> Mulai Belanja
                </a>
            </div>
        <?php else: ?>
            <form method="post" action="proses_checkout.php" id="checkoutForm">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="jarak" id="jarak">
                <input type="hidden" name="ongkir" id="ongkir">

                <div class="checkout-wrapper">
                    <!-- Left Column -->
                    <div>
                        <!-- Alert Box -->
                        <div class="alert-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="alert-content">
                                <strong>Perhatian!</strong>
                                <p>Pastikan lokasi pengiriman dan nomor telepon sudah benar. Ongkir dihitung Rp 2 per meter (Rp 2.000/km) dari SAYURPKY Jalan G.Obos, Palangka Raya.</p>
                            </div>
                        </div>

                        <!-- Lokasi Pengiriman -->
                        <div class="checkout-card">
                            <div class="section-title">
                                <i class="fas fa-map-marker-alt"></i>
                                Lokasi Pengiriman
                            </div>

                            <!-- Info Toko -->
                            <div class="store-info">
                                <div class="store-info-item">
                                    <i class="fas fa-store"></i>
                                    <strong>Toko SAYURPKY</strong>
                                </div>
                                <div class="store-info-item">
                                    <i class="fas fa-map-marked-alt"></i>
                                    <strong>Alamat Toko:</strong>
                                    <span>Jalan G.Obos, Palangka Raya, Kalimantan Tengah</span>
                                </div>
                                <div class="store-info-item">
                                    <i class="fas fa-crosshairs"></i>
                                    <strong>Koordinat Toko:</strong>
                                    <span><?php echo SAYURPKY_LAT; ?>, <?php echo SAYURPKY_LNG; ?></span>
                                </div>
                            </div>

                            <button type="button" class="btn-location" id="btnGetLocation">
                                <i class="fas fa-crosshairs"></i>
                                <span>Gunakan Lokasi Saya Sekarang</span>
                            </button>

                            <div id="map"></div>

                            <div class="location-info">
                                <div class="location-info-item">
                                    <i class="fas fa-map-pin"></i>
                                    <strong>Koordinat Anda:</strong>
                                    <span id="koordinatText">Belum diatur</span>
                                </div>
                                <div class="location-info-item">
                                    <i class="fas fa-route"></i>
                                    <strong>Jarak Rute:</strong>
                                    <span id="jarakText">- km</span>
                                </div>
                                <div class="location-info-item">
                                    <i class="fas fa-clock"></i>
                                    <strong>Perkiraan Waktu:</strong>
                                    <span id="waktuText">- menit</span>
                                </div>
                                <div class="location-info-item">
                                    <i class="fas fa-truck"></i>
                                    <strong>Ongkir:</strong>
                                    <span id="ongkirText">Rp 0</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="no_telepon">
                                    <i class="fas fa-phone"></i> Nomor Telepon / WhatsApp *
                                </label>
                                <input type="tel" id="no_telepon" name="no_telepon" 
                                       value="<?php echo htmlspecialchars($user_telepon); ?>" 
                                       placeholder="Contoh: 081234567890" required>
                            </div>

                            <div class="form-group">
                                <label for="alamat_lengkap">
                                    <i class="fas fa-home"></i> Alamat Lengkap *
                                </label>
                                <textarea id="alamat_lengkap" name="alamat_lengkap" 
                                          placeholder="Alamat akan terisi otomatis saat Anda menggunakan lokasi. Anda bisa mengedit jika perlu." 
                                          required><?php echo htmlspecialchars($user_alamat); ?></textarea>
                                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                                    <i class="fas fa-info-circle"></i> Klik tombol "Gunakan Lokasi Saya" untuk mengisi alamat otomatis
                                </small>
                            </div>
                        </div>

                        <!-- Produk yang Dibeli -->
                        <div class="checkout-card" style="margin-top: 20px;">
                            <div class="section-title">
                                <i class="fas fa-box-open"></i>
                                Produk Dipesan (<?php echo $total_item; ?> Item)
                            </div>
                            <?php foreach ($detail_keranjang as $item): ?>
                                <div class="product-item">
                                    <div class="product-info">
                                        <div class="product-name">
                                            <?php echo htmlspecialchars($item['produk']['nama_produk']); ?>
                                        </div>
                                        <div class="product-meta">
                                            <div class="product-price">
                                                Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                            </div>
                                            <div class="product-qty">
                                                x<?php echo $item['qty']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="checkout-card" style="margin-top: 20px;">
                            <div class="section-title">
                                <i class="fas fa-credit-card"></i>
                                Metode Pembayaran
                            </div>
                            <div class="payment-methods">
                                <label class="payment-option selected">
                                    <input type="radio" name="payment" value="transfer" checked required>
                                    <i class="fas fa-university payment-icon"></i>
                                    <div class="payment-info">
                                        <div class="payment-name">Transfer Bank</div>
                                        <div class="payment-desc">BCA, Mandiri, BNI, BRI, BSI</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Summary -->
                    <div class="summary-card checkout-card">
                        <div class="section-title">
                            <i class="fas fa-receipt"></i>
                            Ringkasan Belanja
                        </div>
                        
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $total_item; ?> item)</span>
                            <span>Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="summary-row highlight">
                            <span><i class="fas fa-shipping-fast" style="color: #FF9800; margin-right: 5px;"></i> Ongkos Kirim</span>
                            <span id="ongkirSummary">Rp 0</span>
                        </div>
                        
                        <div class="summary-row divider total">
                            <span>Total Pembayaran</span>
                            <span class="amount" id="totalPembayaran">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></span>
                        </div>

                        <div class="button-group">
                            <a href="keranjang.php" class="btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" name="checkout" class="btn-secondary" style="flex: 2; background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%); color: white; border: none; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);">
                                <i class="fas fa-check-circle"></i> Buat Pesanan
                            </button>
                        </div>

                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>Pembayaran 100% Aman & Terpercaya</span>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine JS -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    
    <script>
        let map;
        let marker;
        let storeMarker;
        let routingControl;
        
        // Koordinat SAYURPKY - Jalan G.Obos, Palangka Raya
        const SAYURPKY_LAT = -2.231092;
        const SAYURPKY_LNG = 113.898411;
        const ONGKIR_PER_METER = 2; // Rp 2 per meter
        const ONGKIR_PER_KM = 2000; // Rp 2.000 per km
        const subtotal = <?php echo $total_harga; ?>;

        // Initialize Map dengan OpenStreetMap
        function initMap() {
            // Set default location ke SAYURPKY
            const storeLocation = [SAYURPKY_LAT, SAYURPKY_LNG];
            
            // Inisialisasi peta
            map = L.map('map').setView(storeLocation, 14);

            // Tambahkan tile layer OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);

            // Marker untuk SAYURPKY (Toko)
            storeMarker = L.marker(storeLocation, {
                icon: L.divIcon({
                    html: '<i class="fas fa-store" style="color: #4CAF50; font-size: 24px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>',
                    iconSize: [30, 30],
                    className: 'store-marker'
                })
            }).addTo(map);

            // Popup untuk toko
            storeMarker.bindPopup(`
                <div style="padding: 10px; font-family: Inter, sans-serif; min-width: 200px;">
                    <h3 style="margin: 0 0 8px 0; color: #4CAF50; font-size: 16px;">
                        <i class="fas fa-store"></i> SAYURPKY
                    </h3>
                    <p style="margin: 5px 0; font-size: 13px;">
                        <i class="fas fa-map-marker-alt"></i> Jalan G.Obos, Palangka Raya
                    </p>
                    <p style="margin: 5px 0; font-size: 13px;">
                        <i class="fas fa-crosshairs"></i> ${SAYURPKY_LAT}, ${SAYURPKY_LNG}
                    </p>
                </div>
            `).openPopup();

            // Marker untuk lokasi pengiriman customer (draggable)
            marker = L.marker(storeLocation, {
                draggable: true,
                icon: L.divIcon({
                    html: '<i class="fas fa-map-marker-alt" style="color: #ff4444; font-size: 32px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>',
                    iconSize: [30, 42],
                    className: 'user-marker'
                })
            }).addTo(map);

            // Event ketika marker pengiriman dipindah (drag)
            marker.on('dragend', function(event) {
                const position = marker.getLatLng();
                updateLocation(position.lat, position.lng);
                getAddressFromCoordinates(position.lat, position.lng);
                calculateRoute(position.lat, position.lng);
            });

            // Event ketika map diklik
            map.on('click', function(event) {
                const clickedLocation = event.latlng;
                marker.setLatLng(clickedLocation);
                updateLocation(clickedLocation.lat, clickedLocation.lng);
                getAddressFromCoordinates(clickedLocation.lat, clickedLocation.lng);
                calculateRoute(clickedLocation.lat, clickedLocation.lng);
            });

            // Load saved location if exists
            <?php if ($user_lat && $user_lng): ?>
                const savedLocation = [<?php echo $user_lat; ?>, <?php echo $user_lng; ?>];
                marker.setLatLng(savedLocation);
                map.setView(savedLocation, 16);
                updateLocation(<?php echo $user_lat; ?>, <?php echo $user_lng; ?>);
                calculateRoute(<?php echo $user_lat; ?>, <?php echo $user_lng; ?>);
            <?php endif; ?>
        }

        // Calculate route using OSRM (Open Source Routing Machine)
        function calculateRoute(userLat, userLng) {
            // Remove existing route if any
            if (routingControl) {
                map.removeControl(routingControl);
            }

            // Calculate route using OSRM
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(SAYURPKY_LAT, SAYURPKY_LNG), // Start: Toko SAYURPKY
                    L.latLng(userLat, userLng) // End: Lokasi pengiriman
                ],
                routeWhileDragging: false,
                showAlternatives: false,
                show: false, // Hide instructions panel
                lineOptions: {
                    styles: [
                        {color: '#4CAF50', opacity: 0.8, weight: 6}
                    ]
                },
                createMarker: function() { return null; }, // No markers for waypoints
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1'
                })
            }).addTo(map);

            // Hide the routing instructions container
            const routingContainer = document.querySelector('.leaflet-routing-container');
            if (routingContainer) {
                routingContainer.style.display = 'none';
            }

            // Listen for route calculation
            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                if (routes && routes.length > 0) {
                    const route = routes[0];
                    const distance = route.summary.totalDistance; // in meters
                    const duration = route.summary.totalTime; // in seconds
                    
                    // Convert to km and minutes
                    const distanceKm = (distance / 1000).toFixed(2);
                    const durationMinutes = Math.round(duration / 60);
                    
                    // Calculate ongkir based on actual route distance
                    const ongkir = Math.ceil(distance) * ONGKIR_PER_METER;
                    
                    // Update UI
                    document.getElementById('jarak').value = distanceKm;
                    document.getElementById('ongkir').value = ongkir;
                    document.getElementById('jarakText').textContent = distanceKm + ' km';
                    document.getElementById('waktuText').textContent = durationMinutes + ' menit';
                    document.getElementById('ongkirText').textContent = 'Rp ' + formatRupiah(ongkir);
                    document.getElementById('ongkirSummary').textContent = 'Rp ' + formatRupiah(ongkir);

                    // Update total payment
                    const totalPembayaran = subtotal + ongkir;
                    document.getElementById('totalPembayaran').textContent = 'Rp ' + formatRupiah(totalPembayaran);
                }
            });
            
            routingControl.on('routingerror', function(e) {
                console.error('Routing error:', e);
                // Fallback to straight-line distance if routing fails
                const distance = calculateDistance(SAYURPKY_LAT, SAYURPKY_LNG, userLat, userLng);
                const ongkir = Math.ceil(distance * 1000) * ONGKIR_PER_METER;
                
                document.getElementById('jarak').value = distance.toFixed(2);
                document.getElementById('ongkir').value = ongkir;
                document.getElementById('jarakText').textContent = distance.toFixed(2) + ' km (perkiraan)';
                document.getElementById('waktuText').textContent = 'Estimasi tidak tersedia';
                document.getElementById('ongkirText').textContent = 'Rp ' + formatRupiah(ongkir);
                document.getElementById('ongkirSummary').textContent = 'Rp ' + formatRupiah(ongkir);

                const totalPembayaran = subtotal + ongkir;
                document.getElementById('totalPembayaran').textContent = 'Rp ' + formatRupiah(totalPembayaran);
            });
        }

        // Get current location dengan auto-fill alamat
        document.getElementById('btnGetLocation').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mengambil Lokasi...</span>';
            document.getElementById('loadingOverlay').classList.add('active');

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const userLocation = [lat, lng];

                        marker.setLatLng(userLocation);
                        map.setView(userLocation, 16);

                        updateLocation(lat, lng);
                        
                        // OTOMATIS ISI ALAMAT
                        getAddressFromCoordinates(lat, lng);
                        
                        // Calculate route
                        calculateRoute(lat, lng);

                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check-circle"></i> <span>Lokasi Berhasil Diambil!</span>';
                        document.getElementById('loadingOverlay').classList.remove('active');

                        // Animasi pada marker
                        marker.setIcon(L.divIcon({
                            html: '<i class="fas fa-map-marker-alt" style="color: #4CAF50; font-size: 32px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); animation: bounce 0.5s 4;"></i>',
                            iconSize: [30, 42],
                            className: 'user-marker'
                        }));

                        setTimeout(() => {
                            marker.setIcon(L.divIcon({
                                html: '<i class="fas fa-map-marker-alt" style="color: #ff4444; font-size: 32px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>',
                                iconSize: [30, 42],
                                className: 'user-marker'
                            }));
                        }, 2000);

                        setTimeout(() => {
                            btn.innerHTML = '<i class="fas fa-crosshairs"></i> <span>Gunakan Lokasi Saya Sekarang</span>';
                        }, 3000);
                    },
                    function(error) {
                        let errorMessage = 'Gagal mengambil lokasi!';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Izin lokasi ditolak. Mohon izinkan akses lokasi di pengaturan browser Anda.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Informasi lokasi tidak tersedia.";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "Waktu permintaan lokasi habis.";
                                break;
                        }
                        alert(errorMessage);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-crosshairs"></i> <span>Gunakan Lokasi Saya Sekarang</span>';
                        document.getElementById('loadingOverlay').classList.remove('active');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                alert('Browser Anda tidak mendukung Geolocation. Silakan gunakan browser modern seperti Chrome atau Firefox.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-crosshairs"></i> <span>Gunakan Lokasi Saya Sekarang</span>';
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        });

        // Update location and calculate distance
        function updateLocation(lat, lng) {
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            document.getElementById('koordinatText').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }

        // Calculate distance using Haversine formula (fallback)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius bumi dalam km
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = R * c;
            
            return distance;
        }

        function toRad(degrees) {
            return degrees * (Math.PI / 180);
        }

        // Get address from coordinates (reverse geocoding) - OTOMATIS ISI ALAMAT
        function getAddressFromCoordinates(lat, lng) {
            // Menggunakan Nominatim (OpenStreetMap geocoding service)
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        // Otomatis isi textarea alamat
                        const alamatTextarea = document.getElementById('alamat_lengkap');
                        alamatTextarea.value = data.display_name;
                        
                        // Animasi pada textarea
                        alamatTextarea.style.backgroundColor = '#e8f5e9';
                        setTimeout(() => {
                            alamatTextarea.style.backgroundColor = '';
                        }, 1000);
                        
                        console.log('Alamat berhasil diisi:', data.display_name);
                    } else {
                        console.error('Geocoding gagal:', data);
                        alert('Gagal mendapatkan alamat dari koordinat. Silakan isi alamat secara manual.');
                    }
                })
                .catch(error => {
                    console.error('Error geocoding:', error);
                    alert('Gagal mendapatkan alamat dari koordinat. Silakan isi alamat secara manual.');
                });
        }

        // Format rupiah
        function formatRupiah(angka) {
            return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            const noTelepon = document.getElementById('no_telepon').value;
            const alamat = document.getElementById('alamat_lengkap').value;

            if (!latitude || !longitude) {
                e.preventDefault();
                alert('⚠️ Silakan tentukan lokasi pengiriman Anda terlebih dahulu!\n\nKlik tombol "Gunakan Lokasi Saya Sekarang" atau klik pada peta.');
                document.getElementById('btnGetLocation').focus();
                return false;
            }

            if (!noTelepon.trim()) { 
                e.preventDefault();
                alert('⚠️ Nomor telepon harus diisi!');
                document.getElementById('no_telepon').focus();
                return false;
            }

            if (!alamat.trim()) {
                e.preventDefault();
                alert('⚠️ Alamat lengkap harus diisi!\n\nGunakan tombol "Gunakan Lokasi Saya" untuk mengisi otomatis atau isi secara manual.');
                document.getElementById('alamat_lengkap').focus();
                return false;
            }

            // Validasi format nomor telepon Indonesia
            const phoneRegex = /^(\+62|62|0)[0-9]{9,13}$/;
            const cleanPhone = noTelepon.replace(/[\s-]/g, '');
            
            if (!phoneRegex.test(cleanPhone)) {
                e.preventDefault();
                alert('⚠️ Format nomor telepon tidak valid!\n\nContoh yang benar:\n• 081234567890\n• +6281234567890\n• 6281234567890');
                document.getElementById('no_telepon').focus();
                return false;
            }

            // Validasi jarak maksimal (opsional, sesuaikan dengan kebijakan toko)
            const jarak = parseFloat(document.getElementById('jarak').value);
            const ongkir = parseInt(document.getElementById('ongkir').value);
            
            if (jarak > 50) {
                if (!confirm('⚠️ Jarak pengiriman Anda cukup jauh (' + jarak.toFixed(2) + ' km).\n\nOngkir: Rp ' + formatRupiah(ongkir) + '\n\nApakah Anda yakin ingin melanjutkan?')) {
                    e.preventDefault();
                    return false;
                }
            }

            // Konfirmasi akhir
            const total = subtotal + ongkir;
            
            const konfirmasi = confirm(
                 '✅ Buat Pesanan\n\n' +
                 '📦 Subtotal: Rp ' + formatRupiah(subtotal) + '\n' +
                 '🚚 Ongkir: Rp ' + formatRupiah(ongkir) + ' (' + jarak.toFixed(2) + ' km)\n' +
                 '💰 Total: Rp ' + formatRupiah(total) + '\n\n' +
                 '📍 Alamat: ' + alamat.substring(0, 50) + '...\n' +
                 '📞 Telepon: ' + noTelepon + '\n\n' +
                 'Lanjutkan pesanan?'
             );

            if (!konfirmasi) {
                e.preventDefault(); // Mencegah form dikirim jika pengguna menekan "Batal"
                return false;
            }
        });

        // Initialize map when page loads
        window.onload = function() {
            initMap();
            
            // Auto-trigger lokasi jika belum ada data tersimpan
            <?php if (empty($user_lat) || empty($user_lng)): ?>
                setTimeout(() => {
                    if (confirm('🗺️ Izinkan SAYURPKY mengakses lokasi Anda untuk mengisi alamat secara otomatis?')) {
                        document.getElementById('btnGetLocation').click();
                    }
                }, 1000);
            <?php endif; ?>
        };

        // Auto-save draft (opsional)
        let autoSaveTimeout;
        ['no_telepon', 'alamat_lengkap'].forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('input', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    console.log('Auto-saving draft...');
                    // Bisa implement localStorage atau AJAX untuk save draft
                }, 2000);
            });
        });
    </script>
</body>
</html>