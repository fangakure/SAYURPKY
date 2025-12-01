<?php
session_start();
include "../config/db.php";

// Cek login admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Filter
$status_filter = $_GET['status'] ?? 'all';
$payment_filter = $_GET['payment'] ?? 'all';
$search = $_GET['search'] ?? '';

// Query pesanan
$query = "
    SELECT 
        p.*,
        u.nama_lengkap,
        u.email,
        i.no_invoice,
        pay.status as payment_status,
        pay.bukti_transfer,
        pay.tgl_pembayaran,
        pg.status as pengiriman_status,
        COUNT(dp.id_detail) as jumlah_item
    FROM tbl_pesanan p
    INNER JOIN tbl_user u ON p.id_user = u.id_user
    LEFT JOIN tbl_invoice i ON p.id_pesanan = i.id_pesanan
    LEFT JOIN tbl_pembayaran pay ON p.id_pesanan = pay.id_pesanan
    LEFT JOIN tbl_pengiriman pg ON p.id_pesanan = pg.id_pesanan
    LEFT JOIN tbl_detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    WHERE 1=1
";

if ($status_filter != 'all') {
    $query .= " AND p.status_pesanan = '$status_filter'";
}

if ($payment_filter != 'all') {
    $query .= " AND p.status_pembayaran = '$payment_filter'";
}

if ($search) {
    $query .= " AND (p.no_pesanan LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$query .= " GROUP BY p.id_pesanan ORDER BY p.tgl_pesanan DESC";

$result = $conn->query($query);
$pesanan_list = $result->fetch_all(MYSQLI_ASSOC);

// Statistik
$stats = [
    'pending' => $conn->query("SELECT COUNT(*) as total FROM tbl_pesanan WHERE status_pesanan='pending'")->fetch_assoc()['total'],
    'dikonfirmasi' => $conn->query("SELECT COUNT(*) as total FROM tbl_pesanan WHERE status_pesanan='dikonfirmasi'")->fetch_assoc()['total'],
    'diproses' => $conn->query("SELECT COUNT(*) as total FROM tbl_pesanan WHERE status_pesanan='diproses'")->fetch_assoc()['total'],
    'dikirim' => $conn->query("SELECT COUNT(*) as total FROM tbl_pesanan WHERE status_pesanan='dikirim'")->fetch_assoc()['total'],
    'selesai' => $conn->query("SELECT COUNT(*) as total FROM tbl_pesanan WHERE status_pesanan='selesai'")->fetch_assoc()['total'],
    'belum_bayar' => $conn->query("SELECT COUNT(*) as total FROM tbl_pesanan WHERE status_pembayaran='belum_bayar'")->fetch_assoc()['total'],
    'menunggu_konfirmasi' => $conn->query("SELECT COUNT(*) as total FROM tbl_pesanan WHERE status_pembayaran='menunggu_konfirmasi'")->fetch_assoc()['total']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Admin SAYURPKY</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            font-family: 'Inter', sans-serif;
            background: #f8f9fc;
            min-height: 100vh;
            color: #2c3e50;
        }

        /* ============= SIDEBAR ============= */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a7c2a 0%, #2ba942 100%);
            position: fixed;
            height: 100vh;
            color: white;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 24px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 32px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            background: rgba(0,0,0,0.1);
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            letter-spacing: 0.5px;
        }

        .sidebar-header h2 i {
            font-size: 28px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .menu {
            padding: 20px 0;
            list-style: none;
        }

        .menu li {
            margin: 4px 12px;
        }

        .menu a {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 15px;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .menu a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .menu a:hover {
            background: rgba(255,255,255,0.15);
            color: white;
            transform: translateX(4px);
        }

        .menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .menu a.active::before {
            transform: scaleY(1);
        }

        .menu i {
            width: 24px;
            font-size: 18px;
            text-align: center;
        }

        .logout-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid rgba(255,255,255,0.15);
            padding: 20px;
            background: rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logout-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .logout-avatar i {
            font-size: 24px;
            color: white;
        }

        .logout-info {
            flex: 1;
            line-height: 1.4;
        }

        .logout-info strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: white;
        }

        .logout-info small {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        /* ============= MAIN CONTENT ============= */
        .main {
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: white;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            padding: 24px 40px;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #e8ecef;
        }

        .topbar h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar h1 i {
            color: #2ba942;
        }

        .content {
            padding: 32px 40px;
            flex: 1;
        }

        /* ============= STATS SECTION ============= */
        .stats-section {
            margin-bottom: 32px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            border: 1px solid #e8ecef;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--color-start), var(--color-end));
        }

        .stat-card.pending { --color-start: #FFA726; --color-end: #FB8C00; }
        .stat-card.konfirmasi { --color-start: #42A5F5; --color-end: #1E88E5; }
        .stat-card.proses { --color-start: #AB47BC; --color-end: #8E24AA; }
        .stat-card.kirim { --color-start: #26A69A; --color-end: #00897B; }
        .stat-card.selesai { --color-start: #66BB6A; --color-end: #43A047; }
        .stat-card.bayar { --color-start: #EF5350; --color-end: #E53935; }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: linear-gradient(135deg, var(--color-start), var(--color-end));
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }

        /* ============= FILTER SECTION ============= */
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid #e8ecef;
        }

        .filter-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 14px 50px 14px 20px;
            border: 2px solid #e8ecef;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #f8f9fc;
        }

        .search-box input:focus {
            outline: none;
            border-color: #2ba942;
            background: white;
            box-shadow: 0 0 0 4px rgba(43, 169, 66, 0.1);
        }

        .search-box button {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #2ba942, #1a7c2a);
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(43, 169, 66, 0.3);
        }

        .search-box button:hover {
            background: linear-gradient(135deg, #1a7c2a, #2ba942);
            box-shadow: 0 4px 12px rgba(43, 169, 66, 0.4);
        }

        select {
            padding: 14px 20px;
            border: 2px solid #e8ecef;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fc;
            color: #334155;
        }

        select:focus {
            outline: none;
            border-color: #2ba942;
            background: white;
            box-shadow: 0 0 0 4px rgba(43, 169, 66, 0.1);
        }

        select:hover {
            border-color: #cbd5e1;
        }

        /* ============= TABLE SECTION ============= */
        .table-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            border: 1px solid #e8ecef;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #f8f9fc 0%, #f1f3f8 100%);
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 2px solid #e8ecef;
        }

        td {
            padding: 20px;
            border-bottom: 1px solid #f1f3f5;
            color: #334155;
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f8f9fc;
            transform: scale(1.001);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* ============= BADGES ============= */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 24px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .status-pending { background: #FFF4E6; color: #E65100; border: 1px solid #FFE0B2; }
        .status-dikonfirmasi { background: #E3F2FD; color: #0D47A1; border: 1px solid #BBDEFB; }
        .status-diproses { background: #F3E5F5; color: #4A148C; border: 1px solid #E1BEE7; }
        .status-dikirim { background: #E0F2F1; color: #004D40; border: 1px solid #B2DFDB; }
        .status-selesai { background: #E8F5E9; color: #1B5E20; border: 1px solid #C8E6C9; }
        .status-dibatalkan { background: #FFEBEE; color: #B71C1C; border: 1px solid #FFCDD2; }
        .status-belum-bayar { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
        .status-menunggu { background: #FFF9C4; color: #F57F17; border: 1px solid #FFF59D; }
        .status-lunas { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }

        .status-badge i {
            font-size: 8px;
        }

        /* ============= BUTTONS ============= */
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-detail {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-reset {
            background: #f1f3f5;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        .btn-reset:hover {
            background: #e9ecef;
        }

        /* ============= EMPTY STATE ============= */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state i {
            font-size: 72px;
            color: #cbd5e1;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #475569;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 14px;
        }

        /* ============= ORDER INFO ============= */
        .order-number {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
        }

        .order-invoice {
            color: #64748b;
            font-size: 12px;
            margin-top: 4px;
        }

        .customer-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 14px;
        }

        .customer-email {
            color: #64748b;
            font-size: 12px;
            margin-top: 4px;
        }

        .order-date {
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .order-time {
            color: #64748b;
            font-size: 12px;
            margin-top: 4px;
        }

        .order-total {
            font-weight: 700;
            font-size: 16px;
            color: #2ba942;
        }

        .item-count {
            color: #64748b;
            font-weight: 500;
        }

        /* ============= RESPONSIVE ============= */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main {
                margin-left: 0;
            }

            .content {
                padding: 24px 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: 100%;
            }

            select {
                width: 100%;
            }

            .table-container {
                overflow-x: scroll;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-leaf"></i> SAYURPKY</h2>
        </div>
        
        <ul class="menu">
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> Produk</a></li>
            <li><a href="pesanan.php" class="active"><i class="fas fa-shopping-cart"></i> Pesanan</a></li>
            <li><a href="edukasi.php"><i class="fas fa-chalkboard-teacher"></i> Edukasi</a></li>
            <li><a href="resep.php"><i class="fas fa-utensils"></i> Resep</a></li>
            <li><a href="user.php"><i class="fas fa-users"></i> User</a></li>
        </ul>

        <div class="logout-section" onclick="window.location.href='logout.php'" style="cursor: pointer;">
            <div class="logout-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="logout-info">
                <strong><?php echo htmlspecialchars($_SESSION['admin']['nama_lengkap'] ?? 'Admin'); ?></strong>
                <small>Administrator</small>
            </div>
            <button class="logout-btn"><i class="fas fa-sign-out-alt"></i></button>
        </div>
    </div>

    <main class="main">
        <div class="topbar">
            <h1><i class="fas fa-shopping-cart"></i> Kelola Pesanan</h1>
        </div>

        <div class="content">
            <!-- Statistics Section -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card pending">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        </div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    </div>
                    <div class="stat-card konfirmasi">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        </div>
                        <div class="stat-label">Dikonfirmasi</div>
                        <div class="stat-value"><?php echo $stats['dikonfirmasi']; ?></div>
                    </div>
                    <div class="stat-card proses">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-cog"></i></div>
                        </div>
                        <div class="stat-label">Diproses</div>
                        <div class="stat-value"><?php echo $stats['diproses']; ?></div>
                    </div>
                    <div class="stat-card kirim">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-shipping-fast"></i></div>
                        </div>
                        <div class="stat-label">Dikirim</div>
                        <div class="stat-value"><?php echo $stats['dikirim']; ?></div>
                    </div>
                    <div class="stat-card selesai">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                        </div>
                        <div class="stat-label">Selesai</div>
                        <div class="stat-value"><?php echo $stats['selesai']; ?></div>
                    </div>
                    <div class="stat-card bayar">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        </div>
                        <div class="stat-label">Belum Bayar</div>
                        <div class="stat-value"><?php echo $stats['belum_bayar']; ?></div>
                    </div>
                    <div class="stat-card pending">
                        <div class="stat-header">
                            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                        </div>
                        <div class="stat-label">Menunggu Konfirmasi</div>
                        <div class="stat-value"><?php echo $stats['menunggu_konfirmasi']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-card">
                <form method="GET" class="filter-row">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="🔍 Cari pesanan, nama customer, atau email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <select name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>📦 Semua Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                        <option value="dikonfirmasi" <?php echo $status_filter == 'dikonfirmasi' ? 'selected' : ''; ?>>✅ Dikonfirmasi</option>
                        <option value="diproses" <?php echo $status_filter == 'diproses' ? 'selected' : ''; ?>>⚙️ Diproses</option>
                        <option value="dikirim" <?php echo $status_filter == 'dikirim' ? 'selected' : ''; ?>>🚚 Dikirim</option>
                        <option value="selesai" <?php echo $status_filter == 'selesai' ? 'selected' : ''; ?>>✔️ Selesai</option>
                        <option value="dibatalkan" <?php echo $status_filter == 'dibatalkan' ? 'selected' : ''; ?>>❌ Dibatalkan</option>
                    </select>

                    <select name="payment" onchange="this.form.submit()">
                        <option value="all" <?php echo $payment_filter == 'all' ? 'selected' : ''; ?>>💳 Semua Pembayaran</option>
                        <option value="belum_bayar" <?php echo $payment_filter == 'belum_bayar' ? 'selected' : ''; ?>>❗ Belum Bayar</option>
                        <option value="menunggu_konfirmasi" <?php echo $payment_filter == 'menunggu_konfirmasi' ? 'selected' : ''; ?>>⏱️ Menunggu Konfirmasi</option>
                        <option value="lunas" <?php echo $payment_filter == 'lunas' ? 'selected' : ''; ?>>✅ Lunas</option>
                        <option value="ditolak" <?php echo $payment_filter == 'ditolak' ? 'selected' : ''; ?>>⛔ Ditolak</option>
                    </select>

                    <?php if ($search || $status_filter != 'all' || $payment_filter != 'all'): ?>
                        <a href="pesanan.php" class="btn btn-reset">
                            <i class="fas fa-times"></i> Reset Filter
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Section -->
            <div class="table-card">
                <?php if (empty($pesanan_list)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Tidak Ada Pesanan Ditemukan</h3>
                        <p>Belum ada pesanan yang sesuai dengan filter pencarian Anda.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Customer</th>
                                    <th>Item</th>
                                    <th>Total</th>
                                    <th>Status Pesanan</th>
                                    <th>Pembayaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pesanan_list as $pesanan): ?>
                                <tr>
                                    <td>
                                        <div class="order-number"><?php echo $pesanan['no_pesanan']; ?></div>
                                        <div class="order-invoice"><?php echo $pesanan['no_invoice']; ?></div>
                                    </td>
                                    <td>
                                        <div class="order-date"><?php echo date('d/m/Y', strtotime($pesanan['tgl_pesanan'])); ?></div>
                                        <div class="order-time"><?php echo date('H:i', strtotime($pesanan['tgl_pesanan'])); ?> WIB</div>
                                    </td>
                                    <td>
                                        <div class="customer-name"><?php echo htmlspecialchars($pesanan['nama_lengkap']); ?></div>
                                        <div class="customer-email"><?php echo htmlspecialchars($pesanan['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="item-count"><?php echo $pesanan['jumlah_item']; ?> item</span>
                                    </td>
                                    <td>
                                        <div class="order-total">
                                            Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $pesanan['status_pesanan']; ?>">
                                            <i class="fas fa-circle"></i>
                                            <?php echo strtoupper($pesanan['status_pesanan']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $pesanan['status_pembayaran']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $pesanan['status_pembayaran'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="detail_pesanan.php?id=<?php echo $pesanan['id_pesanan']; ?>" class="btn btn-detail">
                                            <i class="fas fa-eye"></i>
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Optional: Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Optional: Add loading animation to form submission
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
            });
        }
    </script>
</body>
</html>