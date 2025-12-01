<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$id_pesanan = intval($_GET['id']);
$id_admin = $_SESSION['admin']['id_user'];

// Proses konfirmasi pembayaran
if (isset($_POST['konfirmasi_bayar'])) {
    $action = $_POST['action']; // approve / reject
    $keterangan = trim($_POST['keterangan']);
    
    if ($action == 'approve') {
        $conn->query("
            UPDATE tbl_pembayaran 
            SET status = 'dikonfirmasi',
                tgl_konfirmasi = NOW(),
                dikonfirmasi_oleh = $id_admin,
                keterangan = '$keterangan'
            WHERE id_pesanan = $id_pesanan
        ");
        
        $conn->query("
            UPDATE tbl_pesanan 
            SET status_pembayaran = 'lunas',
                status_pesanan = 'dikonfirmasi'
            WHERE id_pesanan = $id_pesanan
        ");
        
        $conn->query("
            UPDATE tbl_invoice 
            SET status = 'lunas'
            WHERE id_pesanan = $id_pesanan
        ");
        
        echo "<script>alert('✅ Pembayaran berhasil dikonfirmasi!'); window.location.href='detail_pesanan.php?id=$id_pesanan';</script>";
    } else {
        // Kembalikan stok produk
        $detail_items_query = $conn->query("SELECT id_produk, jumlah FROM tbl_detail_pesanan WHERE id_pesanan = $id_pesanan");
        if ($detail_items_query) {
            while ($item = $detail_items_query->fetch_assoc()) {
                $id_produk = $item['id_produk'];
                $jumlah = $item['jumlah'];
                $conn->query("
                    UPDATE tbl_produk 
                    SET stok = stok + $jumlah,
                        status = 'tersedia'
                    WHERE id_produk = $id_produk
                ");
            }
        }

        $conn->query("
            UPDATE tbl_pembayaran 
            SET status = 'ditolak',
                tgl_konfirmasi = NOW(),
                dikonfirmasi_oleh = $id_admin,
                keterangan = '$keterangan'
            WHERE id_pesanan = $id_pesanan
        ");
        
        $conn->query("
            UPDATE tbl_pesanan 
            SET status_pembayaran = 'ditolak',
                status_pesanan = 'dibatalkan'
            WHERE id_pesanan = $id_pesanan
        ");
        
        echo "<script>alert('❌ Pembayaran ditolak!'); window.location.href='detail_pesanan.php?id=$id_pesanan';</script>";
    }
}

// Proses update status pesanan
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status_pesanan'];
    
    $conn->query("
        UPDATE tbl_pesanan 
        SET status_pesanan = '$new_status'
        WHERE id_pesanan = $id_pesanan
    ");
    
    echo "<script>alert('✅ Status pesanan diupdate!'); window.location.href='detail_pesanan.php?id=$id_pesanan';</script>";
}

// Proses update pengiriman
if (isset($_POST['update_pengiriman'])) {
    $no_resi = trim($_POST['no_resi']);
    $estimasi_tiba_menit = intval($_POST['estimasi_tiba']);
    $keterangan = trim($_POST['keterangan_kirim']);
    
    // Format keterangan untuk menyimpan menit
    $keterangan_dengan_menit = "Estimasi: {$estimasi_tiba_menit} menit";
    if (!empty($keterangan)) {
        $keterangan_dengan_menit .= " | " . $keterangan;
    }
    
    // Hitung tanggal estimasi berdasarkan menit dari sekarang
    $estimasi_tiba_tanggal = date('Y-m-d H:i:s', strtotime("+{$estimasi_tiba_menit} minutes"));
    
    $check_pengiriman = $conn->query("SELECT * FROM tbl_pengiriman WHERE id_pesanan = $id_pesanan")->fetch_assoc();
    
    if ($check_pengiriman) {
        $conn->query("
            UPDATE tbl_pengiriman 
            SET no_resi = '$no_resi',
                tgl_kirim = NOW(),
                estimasi_tiba = '$estimasi_tiba_tanggal',
                status = 'dikirim',
                keterangan = '$keterangan_dengan_menit',
                updated_at = NOW()
            WHERE id_pesanan = $id_pesanan
        ");
    } else {
        $conn->query("
            INSERT INTO tbl_pengiriman 
            (id_pesanan, kurir, no_resi, tgl_kirim, estimasi_tiba, status, keterangan)
            VALUES ($id_pesanan, 'SAYURPKY Express', '$no_resi', NOW(), '$estimasi_tiba_tanggal', 'dikirim', '$keterangan_dengan_menit')
        ");
    }
    
    $conn->query("
        UPDATE tbl_pesanan 
        SET status_pesanan = 'dikirim'
        WHERE id_pesanan = $id_pesanan
    ");
    
    echo "<script>alert('✅ Info pengiriman berhasil diupdate!'); window.location.href='detail_pesanan.php?id=$id_pesanan';</script>";
}

// Ambil detail pesanan
$query = "
    SELECT p.*, u.nama_lengkap, u.email, u.no_telepon,
           i.no_invoice, i.jatuh_tempo,
           pay.metode_pembayaran, pay.bank, pay.no_rekening, pay.atas_nama,
           pay.jumlah_transfer, pay.bukti_transfer, pay.tgl_pembayaran, 
           pay.status as payment_status, pay.tgl_konfirmasi, pay.keterangan as payment_keterangan,
           pg.kurir, pg.no_resi, pg.tgl_kirim, pg.estimasi_tiba, 
           pg.tgl_diterima, pg.status as pengiriman_status, pg.keterangan as pengiriman_keterangan,
           admin.nama_admin as confirmed_by
    FROM tbl_pesanan p
    INNER JOIN tbl_user u ON p.id_user = u.id_user
    LEFT JOIN tbl_invoice i ON p.id_pesanan = i.id_pesanan
    LEFT JOIN tbl_pembayaran pay ON p.id_pesanan = pay.id_pesanan
    LEFT JOIN tbl_pengiriman pg ON p.id_pesanan = pg.id_pesanan
    LEFT JOIN tbl_admin admin ON pay.dikonfirmasi_oleh = admin.id_admin
    WHERE p.id_pesanan = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_pesanan);
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
    return '';
}

// Fungsi untuk membersihkan keterangan dari bagian estimasi
function cleanKeterangan($keterangan) {
    if (!$keterangan) return '';
    
    $cleaned = preg_replace('/Estimasi:\s*\d+\s*menit\s*\|\s*/', '', $keterangan);
    $cleaned = preg_replace('/Estimasi:\s*\d+\s*menit/', '', $cleaned);
    return trim($cleaned, " |");
}

$estimasi_menit_value = getEstimasiMenit($pesanan['pengiriman_keterangan'] ?? '');
$keterangan_clean = cleanKeterangan($pesanan['pengiriman_keterangan'] ?? '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Admin SAYURPKY</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            min-height: 100vh;
            padding: 32px;
            color: #2c3e50;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ============= HEADER ============= */
        .header-card {
            background: white;
            border-radius: 20px;
            padding: 32px 40px;
            margin-bottom: 32px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e8ecef;
            position: relative;
            overflow: hidden;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #2ba942, #1a7c2a);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .header-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #2ba942, #1a7c2a);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 24px rgba(43, 169, 66, 0.3);
        }

        .header-info h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .header-info p {
            color: #64748b;
            font-size: 15px;
            font-weight: 500;
        }

        .btn-back {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        /* ============= GRID LAYOUT ============= */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 32px;
            align-items: start;
        }

        /* ============= CARD STYLES ============= */
        .card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
            border: 1px solid #e8ecef;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 28px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f1f3f8;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #2ba942, #1a7c2a);
            border-radius: 2px;
        }

        .section-title i {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2ba942;
            font-size: 18px;
        }

        /* ============= INFO ROWS ============= */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid #f8f9fc;
            transition: all 0.2s ease;
        }

        .info-row:hover {
            background: #f8f9fc;
            padding-left: 12px;
            padding-right: 12px;
            margin-left: -12px;
            margin-right: -12px;
            border-radius: 10px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label i {
            width: 32px;
            height: 32px;
            background: #f8f9fc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2ba942;
            font-size: 14px;
        }

        .info-value {
            color: #1e293b;
            font-weight: 600;
            text-align: right;
            font-size: 15px;
        }

        /* ============= STATUS BADGES ============= */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 24px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .status-pending { 
            background: linear-gradient(135deg, #FFF4E6, #FFE4CC); 
            color: #E65100; 
            border: 2px solid #FFE0B2; 
        }
        .status-dikonfirmasi { 
            background: linear-gradient(135deg, #E3F2FD, #BBDEFB); 
            color: #0D47A1; 
            border: 2px solid #90CAF9; 
        }
        .status-diproses { 
            background: linear-gradient(135deg, #F3E5F5, #E1BEE7); 
            color: #4A148C; 
            border: 2px solid #CE93D8; 
        }
        .status-dikirim { 
            background: linear-gradient(135deg, #E0F2F1, #B2DFDB); 
            color: #004D40; 
            border: 2px solid #80CBC4; 
        }
        .status-selesai { 
            background: linear-gradient(135deg, #E8F5E9, #C8E6c9); 
            color: #1B5E20; 
            border: 2px solid #A5D6A7; 
        }
        .status-dibatalkan { 
            background: linear-gradient(135deg, #FFEBEE, #FFCDD2); 
            color: #B71C1C; 
            border: 2px solid #EF9A9A; 
        }

        .status-badge i {
            font-size: 10px;
        }

        /* ============= PRODUCT ITEMS ============= */
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fc, #ffffff);
            border-radius: 14px;
            margin-bottom: 12px;
            border: 1px solid #e8ecef;
            transition: all 0.3s ease;
        }

        .product-item:hover {
            transform: translateX(8px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border-color: #2ba942;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 6px;
        }

        .product-meta {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-price {
            color: #2ba942;
            font-weight: 800;
            font-size: 20px;
            white-space: nowrap;
            margin-left: 20px;
        }

        /* ============= TOTAL SECTION ============= */
        .total-section {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            padding: 28px;
            border-radius: 16px;
            margin-top: 24px;
            border: 2px solid #a5d6a7;
            box-shadow: 0 4px 16px rgba(43, 169, 66, 0.15);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            color: #1e293b;
            font-weight: 600;
        }

        .total-row.grand {
            border-top: 3px solid #2ba942;
            margin-top: 16px;
            padding-top: 20px;
            font-size: 18px;
        }

        .total-row.grand .total-value {
            font-size: 32px;
            font-weight: 800;
            color: #2ba942;
        }

        /* ============= BUKTI TRANSFER ============= */
        .bukti-transfer {
            text-align: center;
            margin-top: 28px;
            padding: 24px;
            background: #f8f9fc;
            border-radius: 16px;
        }

        .bukti-transfer h4 {
            margin-bottom: 20px;
            color: #1e293b;
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .bukti-transfer img {
            max-width: 100%;
            max-height: 600px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid #e8ecef;
        }

        .bukti-transfer img:hover {
            transform: scale(1.02);
            box-shadow: 0 12px 48px rgba(0,0,0,0.2);
            border-color: #2ba942;
        }

        /* ============= ACTION CARD ============= */
        .action-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: sticky;
            top: 32px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }

        .action-card .section-title {
            color: white;
            border-color: rgba(255,255,255,0.2);
        }

        .action-card .section-title::after {
            background: white;
        }

        .action-card .section-title i {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* ============= FORMS ============= */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
            color: white;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .form-group select {
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 20px;
            padding-right: 45px;
            cursor: pointer;
        }

        .form-group select option {
            background: #667eea;
            color: white;
            padding: 10px;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: white;
            background: rgba(255,255,255,0.25);
            box-shadow: 0 0 0 4px rgba(255,255,255,0.1);
        }

        /* ============= BUTTONS ============= */
        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: white;
            box-shadow: 0 4px 16px rgba(76, 175, 80, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(76, 175, 80, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336, #e57373);
            color: white;
            box-shadow: 0 4px 16px rgba(244, 67, 54, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(244, 67, 54, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #FF9800, #FFB74D);
            color: white;
            box-shadow: 0 4px 16px rgba(255, 152, 0, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(255, 152, 0, 0.4);
        }

        /* ============= ALERT BOX ============= */
        .alert-box {
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: start;
            gap: 16px;
            border: 2px solid;
        }

        .alert-warning {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-color: #a5d6a7;
        }

        .alert-box i {
            font-size: 28px;
            margin-top: 2px;
        }

        .alert-box strong {
            display: block;
            margin-bottom: 6px;
            font-size: 15px;
        }

        .alert-box p {
            font-size: 13px;
            line-height: 1.6;
            opacity: 0.95;
        }

        /* ============= MODAL ============= */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 24px;
            max-width: 560px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px); 
            }
            to { 
                opacity: 1;
                transform: translateY(0); 
            }
        }

        .modal-header {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 28px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .modal-actions .btn {
            margin-bottom: 0;
        }

        /* ============= RESPONSIVE ============= */
        @media (max-width: 1200px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .action-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .header-card {
                flex-direction: column;
                gap: 20px;
                padding: 24px;
            }

            .header-left {
                flex-direction: column;
                text-align: center;
            }

            .header-info h1 {
                font-size: 24px;
            }

            .card {
                padding: 20px;
            }

            .section-title {
                font-size: 18px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .info-value {
                text-align: left;
            }

            .product-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .product-price {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-card">
            <div class="header-left">
                <div class="header-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="header-info">
                    <h1>Detail Pesanan</h1>
                    <p><?php echo $pesanan['no_pesanan']; ?></p>
                </div>
            </div>
            <a href="pesanan.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali
            </a>
        </div>

        <div class="grid-2">
            <div>
                <!-- Info Pesanan -->
                <div class="card">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Informasi Pesanan
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-receipt"></i> No. Pesanan</span>
                        <span class="info-value"><?php echo $pesanan['no_pesanan']; ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-file-invoice"></i> No. Invoice</span>
                        <span class="info-value"><?php echo $pesanan['no_invoice']; ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar"></i> Tanggal</span>
                        <span class="info-value"><?php echo date('d F Y, H:i', strtotime($pesanan['tgl_pesanan'])); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-hourglass-half"></i> Status Pesanan</span>
                        <span class="info-value">
                            <span class="status-badge status-<?php echo $pesanan['status_pesanan']; ?>">
                                <i class="fas fa-circle"></i>
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
                </div>

                <!-- Info Customer -->
                <div class="card">
                    <div class="section-title">
                        <i class="fas fa-user"></i>
                        Informasi Customer
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user-circle"></i> Nama</span>
                        <span class="info-value"><?php echo $pesanan['nama_lengkap']; ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="info-value"><?php echo $pesanan['email']; ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-phone"></i> Telepon</span>
                        <span class="info-value"><?php echo $pesanan['no_telepon_penerima']; ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Alamat</span>
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
                                <i class="fas fa-box"></i>
                                <?php echo $item['jumlah']; ?> × Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?>
                            </div>
                        </div>
                        <div class="product-price">
                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="total-section">
                        <div class="total-row">
                            <span><i class="fas fa-shopping-bag"></i> Subtotal Produk</span>
                            <span>Rp <?php echo number_format($pesanan['total_harga_produk'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="total-row">
                            <span><i class="fas fa-truck"></i> Ongkos Kirim</span>
                            <span>Rp <?php echo number_format($pesanan['ongkir'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="total-row grand">
                            <span>TOTAL PEMBAYARAN</span>
                            <span class="total-value">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Pembayaran -->
                <?php if ($pesanan['metode_pembayaran']): ?>
                <div class="card">
                    <div class="section-title">
                        <i class="fas fa-credit-card"></i>
                        Informasi Pembayaran
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-wallet"></i> Metode</span>
                        <span class="info-value"><?php echo strtoupper($pesanan['metode_pembayaran']); ?></span>
                    </div>

                    <?php if ($pesanan['bank']): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-university"></i> Bank</span>
                        <span class="info-value"><?php echo $pesanan['bank']; ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-hashtag"></i> No. Rekening</span>
                        <span class="info-value"><?php echo $pesanan['no_rekening']; ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user-tag"></i> Atas Nama</span>
                        <span class="info-value"><?php echo $pesanan['atas_nama']; ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-money-bill-wave"></i> Jumlah Transfer</span>
                        <span class="info-value" style="color: #2ba942; font-size: 20px; font-weight: 800;">
                            Rp <?php echo number_format($pesanan['jumlah_transfer'], 0, ',', '.'); ?>
                        </span>
                    </div>

                    <?php if ($pesanan['tgl_pembayaran']): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar-check"></i> Tanggal Transfer</span>
                        <span class="info-value"><?php echo date('d F Y, H:i', strtotime($pesanan['tgl_pembayaran'])); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($pesanan['confirmed_by']): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-user-check"></i> Dikonfirmasi Oleh</span>
                        <span class="info-value"><?php echo $pesanan['confirmed_by']; ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($pesanan['bukti_transfer']): ?>
                    <div class="bukti-transfer">
                        <h4>
                            <i class="fas fa-file-image"></i> Bukti Transfer
                        </h4>
                        <img src="../assets/img/bukti_transfer/<?php echo $pesanan['bukti_transfer']; ?>" 
                             alt="Bukti Transfer"
                             onclick="window.open(this.src, '_blank')">
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions Sidebar -->
            <div>
                <!-- Konfirmasi Pembayaran -->
                <?php if ($pesanan['status_pembayaran'] == 'menunggu_konfirmasi' && $pesanan['payment_status'] == 'pending'): ?>
                <div class="card action-card">
                    <div class="section-title">
                        <i class="fas fa-check-circle"></i>
                        Konfirmasi Pembayaran
                    </div>

                    <div class="alert-box alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong>Perhatian!</strong>
                            <p>Periksa bukti transfer dengan teliti sebelum melakukan konfirmasi pembayaran.</p>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success" onclick="showModal('approve')">
                        <i class="fas fa-check"></i>
                        Terima Pembayaran
                    </button>

                    <button type="button" class="btn btn-danger" onclick="showModal('reject')">
                        <i class="fas fa-times"></i>
                        Tolak Pembayaran
                    </button>
                </div>
                <?php endif; ?>

                <!-- Update Status Pesanan -->
                <?php if ($pesanan['status_pembayaran'] == 'lunas' && $pesanan['status_pesanan'] != 'selesai'): ?>
                <div class="card action-card">
                    <div class="section-title">
                        <i class="fas fa-edit"></i>
                        Update Status Pesanan
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label>Status Pesanan</label>
                            <select name="status_pesanan" required>
                                <option value="dikonfirmasi" <?php echo $pesanan['status_pesanan'] == 'dikonfirmasi' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                                <option value="diproses" <?php echo $pesanan['status_pesanan'] == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="dikirim" <?php echo $pesanan['status_pesanan'] == 'dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="selesai" <?php echo $pesanan['status_pesanan'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                        </div>

                        <button type="submit" name="update_status" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Simpan Status
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Update Pengiriman -->
                <?php if ($pesanan['status_pesanan'] == 'diproses' || $pesanan['status_pesanan'] == 'dikirim'): ?>
                <div class="card action-card">
                    <div class="section-title">
                        <i class="fas fa-shipping-fast"></i>
                        Update Pengiriman
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label>Nomor Pesanan</label>
                            <input type="text" name="no_resi" 
                                   value="<?php echo $pesanan['no_resi'] ?? ''; ?>" 
                                   placeholder="Masukkan nomor pesanan pengiriman" required>
                        </div>

                        <div class="form-group">
                            <label>Estimasi Tiba (dalam menit)</label>
                            <input type="number" name="estimasi_tiba" 
                                   value="<?php echo $estimasi_menit_value; ?>" 
                                   placeholder="Contoh: 5 untuk 5 menit, 60 untuk 1 jam"
                                   min="1" required>
                            <small style="display: block; margin-top: 8px; opacity: 0.8; font-size: 12px;">
                                💡 Contoh: <strong>5</strong> = 5 menit, <strong>60</strong> = 1 jam, <strong>1440</strong> = 1 hari
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Keterangan (opsional)</label>
                            <textarea name="keterangan_kirim" rows="3" 
                                      placeholder="Catatan pengiriman..."><?php echo $keterangan_clean; ?></textarea>
                        </div>

                        <button type="submit" name="update_pengiriman" class="btn btn-warning">
                            <i class="fas fa-truck"></i>
                            Update Pengiriman
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Pembayaran -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">
                <i class="fas fa-check-circle"></i>
                Konfirmasi Pembayaran
            </div>

            <form method="POST" id="confirmForm">
                <input type="hidden" name="action" id="modalAction">
                
                <div class="form-group">
                    <label style="color: #1e293b;">Keterangan</label>
                    <textarea name="keterangan" rows="4" 
                              placeholder="Masukkan keterangan konfirmasi..." 
                              style="color: #1e293b; background: #f8f9fc; border-color: #e8ecef;" required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn" style="background: #e8ecef; color: #1e293b;" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Batal
                    </button>
                    <button type="submit" name="konfirmasi_bayar" class="btn" id="modalSubmitBtn">
                        <i class="fas fa-check"></i>
                        Konfirmasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(action) {
            const modal = document.getElementById('confirmModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalAction = document.getElementById('modalAction');
            const modalSubmitBtn = document.getElementById('modalSubmitBtn');

            modal.classList.add('active');
            modalAction.value = action;

            if (action === 'approve') {
                modalTitle.innerHTML = '<i class="fas fa-check-circle"></i> Terima Pembayaran';
                modalSubmitBtn.className = 'btn btn-success';
                modalSubmitBtn.innerHTML = '<i class="fas fa-check"></i> Terima';
            } else {
                modalTitle.innerHTML = '<i class="fas fa-times-circle"></i> Tolak Pembayaran';
                modalSubmitBtn.className = 'btn btn-danger';
                modalSubmitBtn.innerHTML = '<i class="fas fa-times"></i> Tolak';
            }
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>