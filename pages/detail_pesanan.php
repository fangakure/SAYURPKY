<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$id_pesanan = intval($_GET['id']);
$id_user = $_SESSION['user']['id_user'];

// Ambil detail pesanan
$query = "
    SELECT p.*, i.no_invoice, i.jatuh_tempo,
           pay.metode_pembayaran, pay.bank, pay.no_rekening, pay.atas_nama,
           pay.jumlah_transfer, pay.bukti_transfer, pay.tgl_pembayaran, pay.status as payment_status,
           pay.tgl_konfirmasi, pay.keterangan as payment_keterangan,
           pg.kurir, pg.no_resi, pg.tgl_kirim, pg.estimasi_tiba,
           pg.tgl_diterima, pg.status as pengiriman_status, pg.keterangan as pengiriman_keterangan
           , u.nama_lengkap AS user_nama_lengkap
           , pg.nama_penerima AS pengiriman_nama_penerima
    FROM tbl_pesanan p
    INNER JOIN tbl_user u ON p.id_user = u.id_user
    LEFT JOIN tbl_invoice i ON p.id_pesanan = i.id_pesanan
    LEFT JOIN tbl_pembayaran pay ON p.id_pesanan = pay.id_pesanan
    LEFT JOIN tbl_pengiriman pg ON p.id_pesanan = pg.id_pesanan
    WHERE p.id_pesanan = ? AND p.id_user = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_pesanan, $id_user);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();

if (!$pesanan) {
    echo "<script>alert('Pesanan tidak ditemukan!'); window.location='pesanan.php';</script>";
    exit;
}

// Ambil detail produk
$detail_items = $conn->query("
    SELECT * FROM tbl_detail_pesanan WHERE id_pesanan = $id_pesanan
")->fetch_all(MYSQLI_ASSOC);

// Fungsi untuk ekstrak estimasi menit dari keterangan
function getEstimasiMenit($keterangan) {
    if ($keterangan && preg_match('/Estimasi:\s*(\d+)\s*menit/', $keterangan, $matches)) {
        return intval($matches[1]);
    }
    return null;
}

// Fungsi untuk membersihkan keterangan dari bagian estimasi
function cleanKeterangan($keterangan) {
    if (!$keterangan) return '';
    
    $cleaned = preg_replace('/Estimasi:\s*\d+\s*menit\s*\|\s*/', '', $keterangan);
    $cleaned = preg_replace('/Estimasi:\s*\d+\s*menit/', '', $cleaned);
    return trim($cleaned, " |");
}

$estimasi_menit = getEstimasiMenit($pesanan['pengiriman_keterangan'] ?? '');
$keterangan_clean = cleanKeterangan($pesanan['pengiriman_keterangan'] ?? '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - SAYURPKY</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --secondary: #2196F3;
            --secondary-light: #42A5F5;
            --warning: #FF9800;
            --danger: #F44336;
            --success: #4CAF50;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-400: #bdbdbd;
            --gray-500: #9e9e9e;
            --gray-600: #757575;
            --gray-700: #616161;
            --gray-800: #424242;
            --gray-900: #212121;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1), 0 5px 10px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.1), 0 10px 20px rgba(0,0,0,0.05);
            --radius-sm: 4px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--gray-800);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header-card {
            background: white;
            border-radius: var(--radius-xl);
            padding: 28px 32px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--primary-light));
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-card i {
            font-size: 36px;
            color: var(--primary);
            background: rgba(76, 175, 80, 0.1);
            padding: 15px;
            border-radius: 50%;
        }

        .header-card h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .header-card p {
            color: var(--gray-600);
            font-size: 15px;
        }

        .btn-back {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .card:hover::before {
            opacity: 1;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
        }

        .section-title i {
            color: var(--primary);
            background: rgba(76, 175, 80, 0.1);
            padding: 10px;
            border-radius: 50%;
            font-size: 16px;
        }

        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            position: relative;
        }

        .status-timeline::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gray-200);
            z-index: 1;
        }

        .timeline-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .timeline-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-100);
            color: var(--gray-400);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 18px;
            transition: all 0.3s ease;
            border: 3px solid white;
            box-shadow: var(--shadow-sm);
        }

        .timeline-item.active .timeline-icon {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: var(--shadow-md);
            transform: scale(1.1);
        }

        .timeline-item.completed .timeline-icon {
            background: var(--primary);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .timeline-label {
            font-size: 13px;
            color: var(--gray-600);
            font-weight: 600;
        }

        .timeline-item.active .timeline-label {
            color: var(--primary);
        }

        .timeline-item.completed .timeline-label {
            color: var(--primary-dark);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-100);
            transition: all 0.2s ease;
        }

        .info-row:hover {
            background: var(--gray-50);
            margin: 0 -16px;
            padding: 16px;
            border-radius: var(--radius-sm);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--gray-600);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .info-value {
            color: var(--gray-900);
            font-weight: 600;
            text-align: right;
            flex: 1;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { 
            background: rgba(255, 193, 7, 0.1); 
            color: #F57F17;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .status-dikonfirmasi { 
            background: rgba(33, 150, 243, 0.1); 
            color: #1565C0;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }
        .status-diproses { 
            background: rgba(3, 169, 244, 0.1); 
            color: #0277BD;
            border: 1px solid rgba(3, 169, 244, 0.3);
        }
        .status-dikirim { 
            background: rgba(76, 175, 80, 0.1); 
            color: var(--primary-dark);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .status-selesai { 
            background: rgba(56, 142, 60, 0.1); 
            color: #1B5E20;
            border: 1px solid rgba(56, 142, 60, 0.3);
        }
        .status-dibatalkan { 
            background: rgba(244, 67, 54, 0.1); 
            color: #C62828;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .product-item {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius-md);
            margin-bottom: 12px;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-100);
        }

        .product-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            border-color: var(--gray-200);
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 6px;
            font-size: 16px;
        }

        .product-meta {
            color: var(--gray-600);
            font-size: 14px;
        }

        .product-price {
            text-align: right;
        }

        .product-unit-price {
            color: var(--gray-600);
            font-size: 14px;
        }

        .product-total-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 18px;
        }

        .total-section {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            padding: 24px;
            border-radius: var(--radius-lg);
            margin-top: 24px;
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
        }

        .total-row.grand {
            border-top: 2px solid var(--primary);
            margin-top: 12px;
            padding-top: 16px;
        }

        .total-label {
            font-weight: 600;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .total-value {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .total-row.grand .total-value {
            font-size: 24px;
            font-weight: 700;
        }

        .bukti-transfer {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
        }

        .bukti-transfer img {
            max-width: 100%;
            max-height: 400px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-300);
        }

        .bukti-transfer img:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .alert-box {
            padding: 24px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            border-left: 4px solid;
            box-shadow: var(--shadow-sm);
        }

        .alert-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-left-color: var(--secondary);
        }

        .alert-success {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left-color: var(--success);
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffe9a1);
            border-left-color: var(--warning);
        }

        .alert-box i {
            font-size: 24px;
            margin-top: 2px;
        }

        .alert-info i { color: var(--secondary); }
        .alert-success i { color: var(--success); }
        .alert-warning i { color: var(--warning); }

        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--warning), #FFB74D);
            color: white;
            padding: 14px 28px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            margin-top: 16px;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Progress bar untuk timeline */
        .progress-bar {
            position: absolute;
            top: 24px;
            left: 0;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            z-index: 1;
            transition: all 0.5s ease;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-card {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .status-timeline {
                flex-direction: column;
                gap: 24px;
            }

            .status-timeline::before {
                width: 3px;
                height: 100%;
                left: 24px;
                top: 0;
            }

            .progress-bar {
                width: 3px;
                height: 100%;
                top: 0;
            }

            .timeline-item {
                text-align: left;
                padding-left: 70px;
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .timeline-icon {
                position: absolute;
                left: 0;
                margin: 0;
            }

            .product-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .product-price {
                text-align: left;
                width: 100%;
                padding-top: 12px;
                border-top: 1px solid var(--gray-200);
            }

            .info-row {
                flex-direction: column;
                gap: 8px;
            }

            .info-value {
                text-align: left;
            }

            .card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .header-card {
                padding: 20px;
            }
            
            .header-card h1 {
                font-size: 24px;
            }
            
            .card {
                padding: 16px;
            }
            
            .total-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <div class="header-left">
                <i class="fas fa-file-invoice"></i>
                <div>
                    <h1>Detail Pesanan</h1>
                    <p><?php echo $pesanan['no_pesanan']; ?></p>
                </div>
            </div>
            <a href="pesanan.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Daftar Pesanan
            </a>
        </div>

        <!-- Timeline Status -->
        <div class="card">
            <div class="section-title">
                <i class="fas fa-route"></i>
                Status Pesanan
            </div>

            <div class="status-timeline">
                <!-- Progress Bar -->
                <div class="progress-bar" style="width: 
                    <?php 
                    $progress = 0;
                    switch($pesanan['status_pesanan']) {
                        case 'pending': $progress = 0; break;
                        case 'dikonfirmasi': $progress = 25; break;
                        case 'diproses': $progress = 50; break;
                        case 'dikirim': $progress = 75; break;
                        case 'selesai': $progress = 100; break;
                        default: $progress = 0;
                    }
                    echo $progress . '%';
                    ?>">
                </div>

                <div class="timeline-item <?php echo in_array($pesanan['status_pesanan'], ['pending','dikonfirmasi','diproses','dikirim','selesai']) ? 'completed' : ''; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="timeline-label">Pesanan Dibuat</div>
                </div>
                <div class="timeline-item <?php echo in_array($pesanan['status_pesanan'], ['dikonfirmasi','diproses','dikirim','selesai']) ? 'completed' : ($pesanan['status_pesanan'] == 'pending' ? 'active' : ''); ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="timeline-label">Dikonfirmasi</div>
                </div>
                <div class="timeline-item <?php echo in_array($pesanan['status_pesanan'], ['diproses','dikirim','selesai']) ? 'completed' : ($pesanan['status_pesanan'] == 'dikonfirmasi' ? 'active' : ''); ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="timeline-label">Diproses</div>
                </div>
                <div class="timeline-item <?php echo in_array($pesanan['status_pesanan'], ['dikirim','selesai']) ? 'completed' : ($pesanan['status_pesanan'] == 'diproses' ? 'active' : ''); ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="timeline-label">Dikirim</div>
                </div>
                <div class="timeline-item <?php echo $pesanan['status_pesanan'] == 'selesai' ? 'completed active' : ''; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="timeline-label">Selesai</div>
                </div>
            </div>

            <?php if ($pesanan['status_pesanan'] == 'dibatalkan'): ?>
                <div class="alert-box alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Pesanan Dibatalkan</strong>
                        <p style="margin-top: 8px; opacity: 0.9;">Pesanan ini telah dibatalkan. Stok produk dikembalikan.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Pesanan -->
        <div class="card">
            <div class="section-title">
                <i class="fas fa-info-circle"></i>
                Informasi Pesanan
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-receipt"></i> Nomor Pesanan</span>
                <span class="info-value"><?php echo $pesanan['no_pesanan']; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-file-invoice"></i> Nomor Invoice</span>
                <span class="info-value"><?php echo $pesanan['no_invoice'] ?? '-'; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-calendar"></i> Tanggal Pesanan</span>
                <span class="info-value"><?php echo date('d F Y, H:i', strtotime($pesanan['tgl_pesanan'])); ?> WIB</span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-hourglass-half"></i> Status Pesanan</span>
                <span class="info-value">
                    <span class="status-badge status-<?php echo $pesanan['status_pesanan']; ?>">
                        <i class="fas fa-circle" style="font-size: 6px;"></i>
                        <?php echo strtoupper($pesanan['status_pesanan']); ?>
                    </span>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-credit-card"></i> Status Pembayaran</span>
                <span class="info-value">
                    <?php echo ucwords(str_replace('_', ' ', $pesanan['status_pembayaran'])); ?>
                </span>
            </div>

            <?php if ($pesanan['jatuh_tempo']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-clock"></i> Jatuh Tempo</span>
                <span class="info-value" style="color: var(--danger);">
                    <?php echo date('d F Y', strtotime($pesanan['jatuh_tempo'])); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Info Penerima -->
        <div class="card">
            <div class="section-title">
                <i class="fas fa-user"></i>
                Informasi Penerima
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-user-circle"></i> Nama Penerima</span>
                <span class="info-value">
                    <?php 
                    $displayed_name = '';
                    // Prioritas 1: nama_penerima dari tbl_pesanan (yang diisi saat checkout)
                    if (!empty($pesanan['nama_penerima']) && $pesanan['nama_penerima'] !== '0') {
                        $displayed_name = $pesanan['nama_penerima'];
                    } 
                    // Prioritas 2: nama_penerima dari tbl_pengiriman (jika ada dan berbeda)
                    elseif (!empty($pesanan['pengiriman_nama_penerima']) && $pesanan['pengiriman_nama_penerima'] !== '0') {
                        $displayed_name = $pesanan['pengiriman_nama_penerima'];
                    }
                    // Prioritas 3: nama_lengkap dari tbl_user (nama user yang login)
                    elseif (!empty($pesanan['user_nama_lengkap'])) {
                        $displayed_name = $pesanan['user_nama_lengkap'];
                    }
                    // Fallback jika semua kosong atau '0'
                    echo htmlspecialchars($displayed_name ?: 'Nama Penerima Tidak Tersedia'); 
                    ?>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-phone"></i> No. Telepon</span>
                <span class="info-value"><?php echo $pesanan['no_telepon_penerima']; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</span>
                <span class="info-value" style="max-width: 60%; text-align: right;">
                    <?php echo $pesanan['alamat_pengiriman']; ?>
                </span>
            </div>
        </div>

        <!-- Produk -->
        <div class="card">
            <div class="section-title">
                <i class="fas fa-box-open"></i>
                Produk yang Dipesan
            </div>

            <?php foreach ($detail_items as $item): ?>
            <div class="product-item">
                <div class="product-info">
                    <div class="product-name"><?php echo $item['nama_produk']; ?></div>
                    <div class="product-meta">
                        <?php echo $item['jumlah']; ?> x Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>
                        <?php if ($item['catatan']): ?>
                            <br><small><i class="fas fa-sticky-note"></i> Catatan: <?php echo $item['catatan']; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="product-price">
                    <div class="product-total-price">
                        Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="total-section">
                <div class="total-row">
                    <span class="total-label"><i class="fas fa-receipt"></i> Subtotal Produk</span>
                    <span class="total-value">Rp <?php echo number_format($pesanan['total_harga_produk'], 0, ',', '.'); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label"><i class="fas fa-truck"></i> Ongkos Kirim</span>
                    <span class="total-value">Rp <?php echo number_format($pesanan['ongkir'], 0, ',', '.'); ?></span>
                </div>
                <?php if ($pesanan['diskon'] > 0): ?>
                <div class="total-row">
                    <span class="total-label"><i class="fas fa-tag"></i> Diskon</span>
                    <span class="total-value" style="color: var(--danger);">- Rp <?php echo number_format($pesanan['diskon'], 0, ',', '.'); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row grand">
                    <span class="total-label"><i class="fas fa-money-bill-wave"></i> TOTAL PEMBAYARAN</span>
                    <span class="total-value">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Info Pembayaran -->
        <?php if ($pesanan['metode_pembayaran']): ?>
        <div class="card">
            <div class="section-title">
                <i class="fas fa-credit-card"></i>
                Informasi Pembayaran
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-wallet"></i> Metode Pembayaran</span>
                <span class="info-value"><?php echo strtoupper($pesanan['metode_pembayaran']); ?></span>
            </div>

            <?php if ($pesanan['bank']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-university"></i> Bank</span>
                <span class="info-value"><?php echo $pesanan['bank']; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-hashtag"></i> No. Rekening Pengirim</span>
                <span class="info-value"><?php echo $pesanan['no_rekening']; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-user"></i> Atas Nama</span>
                <span class="info-value"><?php echo $pesanan['atas_nama']; ?></span>
            </div>
            <?php endif; ?>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-money-bill"></i> Jumlah Transfer</span>
                <span class="info-value" style="color: var(--primary); font-size: 18px;">
                    Rp <?php echo number_format($pesanan['jumlah_transfer'], 0, ',', '.'); ?>
                </span>
            </div>

            <?php if ($pesanan['tgl_pembayaran']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-calendar-check"></i> Tanggal Transfer</span>
                <span class="info-value"><?php echo date('d F Y, H:i', strtotime($pesanan['tgl_pembayaran'])); ?> WIB</span>
            </div>
            <?php endif; ?>

            <?php if ($pesanan['payment_status']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-info-circle"></i> Status Verifikasi</span>
                <span class="info-value">
                    <span class="status-badge status-<?php echo $pesanan['payment_status']; ?>">
                        <?php echo ucwords($pesanan['payment_status']); ?>
                    </span>
                </span>
            </div>
            <?php endif; ?>

            <?php if ($pesanan['bukti_transfer']): ?>
            <div class="bukti-transfer">
                <h4 style="margin-bottom: 16px; color: var(--gray-800); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-file-image"></i> Bukti Transfer
                </h4>
                <img src="../assets/img/bukti_transfer/<?php echo $pesanan['bukti_transfer']; ?>" 
                     alt="Bukti Transfer"
                     onclick="window.open(this.src, '_blank')">
                <p style="margin-top: 12px; color: var(--gray-600); font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-info-circle"></i> Klik gambar untuk memperbesar
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Info Pengiriman -->
        <?php if ($pesanan['kurir']): ?>
        <div class="card">
            <div class="section-title">
                <i class="fas fa-shipping-fast"></i>
                Informasi Pengiriman
            </div>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-truck"></i> Kurir</span>
                <span class="info-value"><?php echo $pesanan['kurir']; ?></span>
            </div>

            <?php if ($pesanan['no_resi']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-barcode"></i> Nomor Pesanan</span>
                <span class="info-value" style="color: var(--secondary); font-weight: 700;">
                    <?php echo $pesanan['no_resi']; ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if ($pesanan['tgl_kirim']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-calendar-alt"></i> Tanggal Kirim</span>
                <span class="info-value"><?php echo date('d F Y, H:i', strtotime($pesanan['tgl_kirim'])); ?> WIB</span>
            </div>
            <?php endif; ?>

            <?php if ($estimasi_menit): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-clock"></i> Estimasi Tiba</span>
                <span class="info-value">
                    <strong style="color: var(--primary); font-size: 16px;">
                        <?php echo $estimasi_menit; ?> menit
                    </strong>
                    <?php if ($pesanan['tgl_kirim']): ?>
                    <br>
                    <small style="color: var(--gray-500); font-size: 12px;">
                        (Perkiraan tiba: <?php echo date('d F Y, H:i', strtotime("+{$estimasi_menit} minutes", strtotime($pesanan['tgl_kirim']))); ?>)
                    </small>
                    <?php endif; ?>
                </span>
            </div>
            <?php elseif ($pesanan['estimasi_tiba']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-clock"></i> Estimasi Tiba</span>
                <span class="info-value">
                    <?php echo date('d F Y', strtotime($pesanan['estimasi_tiba'])); ?>
                </span>
            </div>
            <?php endif; ?>

            <?php if ($pesanan['tgl_diterima']): ?>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-check-circle"></i> Diterima Pada</span>
                <span class="info-value" style="color: var(--success);">
                    <?php echo date('d F Y, H:i', strtotime($pesanan['tgl_diterima'])); ?> WIB
                </span>
            </div>
            <?php endif; ?>

            <div class="info-row">
                <span class="info-label"><i class="fas fa-info-circle"></i> Status Pengiriman</span>
                <span class="info-value">
                    <span class="status-badge status-<?php echo $pesanan['pengiriman_status']; ?>">
                        <?php echo ucwords($pesanan['pengiriman_status']); ?>
                    </span>
                </span>
            </div>

            <?php if ($keterangan_clean): ?>
            <div class="alert-box alert-info" style="margin-top: 20px;">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Catatan Pengiriman:</strong>
                    <p style="margin-top: 8px; opacity: 0.9;"><?php echo $keterangan_clean; ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <?php if ($pesanan['status_pembayaran'] == 'belum_bayar' && $pesanan['status_pesanan'] == 'pending'): ?>
        <div class="alert-box alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Segera Lakukan Pembayaran!</strong>
                <p style="margin-top: 8px; opacity: 0.9;">
                    Silakan upload bukti pembayaran untuk mempercepat proses pesanan Anda.
                </p>
                <a href="upload_pembayaran.php?order=<?php echo $pesanan['no_pesanan']; ?>" 
                   class="action-button">
                    <i class="fas fa-upload"></i> Upload Bukti Pembayaran
                </a>
            </div>
        </div>
        <?php elseif ($pesanan['status_pesanan'] == 'selesai'): ?>
        <div class="alert-box alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong>Pesanan Selesai!</strong>
                <p style="margin-top: 8px; opacity: 0.9;">
                    Terima kasih telah berbelanja di SAYURPKY. Semoga puas dengan produk kami!
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Tambahkan efek interaktif pada timeline
        document.addEventListener('DOMContentLoaded', function() {
            const timelineItems = document.querySelectorAll('.timeline-item');
            
            timelineItems.forEach((item, index) => {
                // Tambahkan delay animasi untuk setiap item
                item.style.transitionDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>