<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user']['id_user'];

// Ambil semua pesanan user
$query = "
    SELECT 
        p.*,
        i.no_invoice,
        pay.status as payment_status,
        pay.bukti_transfer,
        COUNT(dp.id_detail) as jumlah_item
    FROM tbl_pesanan p
    LEFT JOIN tbl_invoice i ON p.id_pesanan = i.id_pesanan
    LEFT JOIN tbl_pembayaran pay ON p.id_pesanan = pay.id_pesanan
    LEFT JOIN tbl_detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    WHERE p.id_user = ?
    GROUP BY p.id_pesanan
    ORDER BY p.tgl_pesanan DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$pesanan_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle batal pesanan
if (isset($_POST['batal_pesanan'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    
    // Validasi bahwa pesanan ini milik user menggunakan prepared statement
    $stmt_check = $conn->prepare("SELECT status_pesanan FROM tbl_pesanan WHERE id_pesanan = ? AND id_user = ?");
    $stmt_check->bind_param("ii", $id_pesanan, $id_user);
    $stmt_check->execute();
    $check = $stmt_check->get_result()->fetch_assoc();
    
    if ($check && $check['status_pesanan'] == 'pending') {
        $conn->begin_transaction();
        try {
            // Kembalikan stok produk
            $stmt_stok = $conn->prepare("
                UPDATE tbl_produk p
                INNER JOIN tbl_detail_pesanan dp ON p.id_produk = dp.id_produk
                SET p.stok = p.stok + dp.jumlah,
                    p.status = 'tersedia'
                WHERE dp.id_pesanan = ?
            ");
            $stmt_stok->bind_param("i", $id_pesanan);
            $stmt_stok->execute();
            
            // Update status pesanan
            $stmt_status = $conn->prepare("
                UPDATE tbl_pesanan 
                SET status_pesanan = 'dibatalkan',
                    status_pembayaran = 'ditolak'
                WHERE id_pesanan = ?
            ");
            $stmt_status->bind_param("i", $id_pesanan);
            $stmt_status->execute();
            
            $conn->commit();
            echo "<script>alert('✅ Pesanan berhasil dibatalkan!'); window.location='pesanan.php';</script>";
        } catch (Exception $e) {
            $conn->rollback();
            // Tampilkan pesan error yang lebih detail untuk debugging (opsional)
            echo "<script>alert('❌ Gagal membatalkan pesanan: " . addslashes($e->getMessage()) . "');</script>";
        }
    } else {
        echo "<script>alert('❌ Pesanan tidak dapat dibatalkan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - SAYURPKY</title>
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
            max-width: 1200px;
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

        .filter-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
            background: white;
            padding: 16px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .tab {
            padding: 10px 20px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .tab:hover {
            background: var(--gray-100);
            transform: translateY(-1px);
        }

        .tab.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .order-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
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

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .order-card:hover::before {
            opacity: 1;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
            margin-bottom: 16px;
        }

        .order-number {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
        }

        .order-date {
            color: var(--gray-600);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
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

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-label {
            color: var(--gray-600);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-value {
            color: var(--gray-900);
            font-weight: 600;
            font-size: 15px;
        }

        .info-value.price {
            color: var(--primary);
            font-size: 18px;
        }

        .order-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .btn {
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }

        .btn-detail {
            background: linear-gradient(135deg, var(--secondary), var(--secondary-light));
            color: white;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-upload {
            background: linear-gradient(135deg, var(--warning), #FFB74D);
            color: white;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-cancel {
            background: white;
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
        }

        .empty-state i {
            font-size: 80px;
            color: var(--gray-300);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--gray-700);
            margin-bottom: 10px;
            font-size: 22px;
        }

        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 25px;
            font-size: 16px;
        }

        .btn-shop {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 14px 28px;
            border-radius: var(--radius-md);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-pending .stat-icon {
            background: rgba(255, 193, 7, 0.1);
            color: #F57F17;
        }

        .stat-active .stat-icon {
            background: rgba(33, 150, 243, 0.1);
            color: #1565C0;
        }

        .stat-completed .stat-icon {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-dark);
        }

        .stat-cancelled .stat-icon {
            background: rgba(244, 67, 54, 0.1);
            color: #C62828;
        }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-info p {
            font-size: 14px;
            color: var(--gray-600);
        }

        @media (max-width: 768px) {
            .header-card {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .header-left {
                width: 100%;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .order-info {
                grid-template-columns: 1fr;
            }

            .order-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .filter-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
                padding: 12px;
            }

            .tab {
                white-space: nowrap;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .header-card {
                padding: 20px;
            }
            
            .header-card h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <div class="header-left">
                <i class="fas fa-box"></i>
                <div>
                    <h1>Pesanan Saya</h1>
                    <p>Kelola pesanan Anda</p>
                </div>
            </div>
            <a href="../index.php" class="btn-back">
                <i class="fas fa-home"></i>
                Beranda
            </a>
        </div>

        <?php 
        // Hitung statistik pesanan
        $pending_count = 0;
        $active_count = 0;
        $completed_count = 0;
        $cancelled_count = 0;
        
        foreach ($pesanan_list as $pesanan) {
            switch ($pesanan['status_pesanan']) {
                case 'pending':
                    $pending_count++;
                    break;
                case 'dikonfirmasi':
                case 'diproses':
                case 'dikirim':
                    $active_count++;
                    break;
                case 'selesai':
                    $completed_count++;
                    break;
                case 'dibatalkan':
                    $cancelled_count++;
                    break;
            }
        }
        ?>
        
        <div class="stats-container">
            <div class="stat-card stat-pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $pending_count; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $active_count; ?></h3>
                    <p>Aktif</p>
                </div>
            </div>
            <div class="stat-card stat-completed">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $completed_count; ?></h3>
                    <p>Selesai</p>
                </div>
            </div>
            <div class="stat-card stat-cancelled">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $cancelled_count; ?></h3>
                    <p>Dibatalkan</p>
                </div>
            </div>
        </div>

        <div class="filter-tabs">
            <div class="tab active" data-status="all">
                <i class="fas fa-list"></i> Semua
            </div>
            <div class="tab" data-status="pending">
                <i class="fas fa-clock"></i> Pending
            </div>
            <div class="tab" data-status="dikonfirmasi">
                <i class="fas fa-check"></i> Dikonfirmasi
            </div>
            <div class="tab" data-status="diproses">
                <i class="fas fa-cog"></i> Diproses
            </div>
            <div class="tab" data-status="dikirim">
                <i class="fas fa-truck"></i> Dikirim
            </div>
            <div class="tab" data-status="selesai">
                <i class="fas fa-check-circle"></i> Selesai
            </div>
            <div class="tab" data-status="dibatalkan">
                <i class="fas fa-times-circle"></i> Dibatalkan
            </div>
        </div>

        <div id="orderList">
            <?php if (empty($pesanan_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-basket"></i>
                    <h3>Belum Ada Pesanan</h3>
                    <p>Anda belum pernah melakukan pemesanan</p>
                    <a href="produk.php" class="btn-shop">
                        <i class="fas fa-shopping-cart"></i>
                        Mulai Belanja
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($pesanan_list as $pesanan): ?>
                    <div class="order-card" data-status="<?php echo $pesanan['status_pesanan']; ?>">
                        <div class="order-header">
                            <div>
                                <div class="order-number">
                                    <i class="fas fa-receipt"></i>
                                    <?php echo $pesanan['no_pesanan']; ?>
                                    <span class="status-badge status-<?php echo $pesanan['status_pesanan']; ?>">
                                        <i class="fas fa-circle" style="font-size: 6px;"></i>
                                        <?php echo strtoupper($pesanan['status_pesanan']); ?>
                                    </span>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d M Y, H:i', strtotime($pesanan['tgl_pesanan'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-info">
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-file-invoice"></i> Invoice</span>
                                <span class="info-value"><?php echo $pesanan['no_invoice'] ?? '-'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-box"></i> Jumlah Item</span>
                                <span class="info-value"><?php echo $pesanan['jumlah_item']; ?> Produk</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-money-bill-wave"></i> Total Pembayaran</span>
                                <span class="info-value price">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><i class="fas fa-credit-card"></i> Status Pembayaran</span>
                                <span class="info-value">
                                    <?php 
                                    $payment_status = str_replace('_', ' ', $pesanan['status_pembayaran']);
                                    echo ucwords($payment_status);
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="order-actions">
                            <a href="detail_pesanan.php?id=<?php echo $pesanan['id_pesanan']; ?>" class="btn btn-detail">
                                <i class="fas fa-eye"></i>
                                Detail Pesanan
                            </a>

                            <?php if ($pesanan['status_pembayaran'] == 'belum_bayar' && $pesanan['status_pesanan'] == 'pending'): ?>
                                <a href="upload_pembayaran.php?order=<?php echo $pesanan['no_pesanan']; ?>" class="btn btn-upload">
                                    <i class="fas fa-upload"></i>
                                    Upload Pembayaran
                                </a>
                            <?php endif; ?>

                            <?php if ($pesanan['status_pesanan'] == 'pending' && $pesanan['status_pembayaran'] == 'belum_bayar'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('❓ Yakin ingin membatalkan pesanan ini?\n\nStok produk akan dikembalikan.');">
                                    <input type="hidden" name="id_pesanan" value="<?php echo $pesanan['id_pesanan']; ?>">
                                    <button type="submit" name="batal_pesanan" class="btn btn-cancel">
                                        <i class="fas fa-times-circle"></i>
                                        Batalkan Pesanan
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter tabs
        const tabs = document.querySelectorAll('.tab');
        const orderCards = document.querySelectorAll('.order-card');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                
                // Add active to clicked tab
                this.classList.add('active');
                
                const filterStatus = this.getAttribute('data-status');
                
                // Filter orders
                orderCards.forEach(card => {
                    if (filterStatus === 'all') {
                        card.style.display = 'block';
                    } else {
                        if (card.getAttribute('data-status') === filterStatus) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });

                // Check if no orders after filter
                const visibleOrders = document.querySelectorAll('.order-card[style="display: block;"]');
                if (visibleOrders.length === 0) {
                    if (!document.querySelector('.empty-filter')) {
                        const emptyDiv = document.createElement('div');
                        emptyDiv.className = 'empty-state empty-filter';
                        emptyDiv.innerHTML = `
                            <i class="fas fa-inbox"></i>
                            <h3>Tidak Ada Pesanan</h3>
                            <p>Tidak ada pesanan dengan status "${this.textContent.trim()}"</p>
                        `;
                        document.getElementById('orderList').appendChild(emptyDiv);
                    }
                } else {
                    const emptyFilter = document.querySelector('.empty-filter');
                    if (emptyFilter) {
                        emptyFilter.remove();
                    }
                }
            });
        });
    </script>
</body>
</html>