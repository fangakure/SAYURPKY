<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user']) || !isset($_GET['order'])) {
    header("Location: login.php");
    exit;
}

$no_pesanan = $_GET['order'];
$id_user = $_SESSION['user']['id_user'];

// Ambil data pesanan
$query = "SELECT * FROM tbl_pesanan WHERE no_pesanan = ? AND id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $no_pesanan, $id_user);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();

if (!$pesanan) {
    echo "<script>alert('Pesanan tidak ditemukan!'); window.location='pesanan.php';</script>";
    exit;
}

// Cek apakah sudah ada pembayaran
$existing_payment = $conn->query("
    SELECT * FROM tbl_pembayaran WHERE id_pesanan = {$pesanan['id_pesanan']}
")->fetch_assoc();

// Proses upload
if (isset($_POST['upload_bukti'])) {
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $bank = $_POST['bank'] ?? '';
    $no_rekening = $_POST['no_rekening'] ?? '';
    $atas_nama = $_POST['atas_nama'] ?? '';
    $jumlah_transfer = floatval($_POST['jumlah_transfer']);
    
    // Validasi
    if ($jumlah_transfer < $pesanan['total_harga']) {
        echo "<script>alert('Jumlah transfer kurang dari total pembayaran!');</script>";
    } else {
        // Upload file
        $target_dir = "../assets/img/bukti_transfer/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_name = "BT_" . $no_pesanan . "_" . time() . "." . pathinfo($_FILES["bukti_transfer"]["name"], PATHINFO_EXTENSION);
        $target_file = $target_dir . $file_name;
        $uploadOk = 1;
        
        // Validasi file
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        
        if (!in_array($imageFileType, $allowed_types)) {
            echo "<script>alert('Hanya file JPG, JPEG, PNG, GIF & PDF yang diizinkan!');</script>";
            $uploadOk = 0;
        }
        
        if ($_FILES["bukti_transfer"]["size"] > 5000000) { // 5MB
            echo "<script>alert('File terlalu besar! Maksimal 5MB');</script>";
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["bukti_transfer"]["tmp_name"], $target_file)) {
                // Insert atau update pembayaran
                if ($existing_payment) {
                    // Update
                    $stmt = $conn->prepare("
                        UPDATE tbl_pembayaran 
                        SET metode_pembayaran = ?, bank = ?, no_rekening = ?, atas_nama = ?,
                            jumlah_transfer = ?, bukti_transfer = ?, tgl_pembayaran = NOW(),
                            status = 'pending'
                        WHERE id_pesanan = ?
                    ");
                    $stmt->bind_param("ssssdsi", 
                        $metode_pembayaran, $bank, $no_rekening, $atas_nama,
                        $jumlah_transfer, $file_name, $pesanan['id_pesanan']
                    );
                } else {
                    // Insert
                    $stmt = $conn->prepare("
                        INSERT INTO tbl_pembayaran 
                        (id_pesanan, metode_pembayaran, bank, no_rekening, atas_nama, 
                         jumlah_transfer, bukti_transfer, tgl_pembayaran, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
                    ");
                    $stmt->bind_param("issssds",
                        $pesanan['id_pesanan'], $metode_pembayaran, $bank, $no_rekening,
                        $atas_nama, $jumlah_transfer, $file_name
                    );
                }
                
                if ($stmt->execute()) {
                    // Update status pembayaran pesanan
                    $conn->query("
                        UPDATE tbl_pesanan 
                        SET status_pembayaran = 'menunggu_konfirmasi'
                        WHERE id_pesanan = {$pesanan['id_pesanan']}
                    ");
                    
                    echo "<script>
                        alert('✅ Bukti pembayaran berhasil diupload! Pesanan Anda akan segera diproses.');
                        window.location='pesanan.php';
                    </script>";
                } else {
                    echo "<script>alert('Gagal menyimpan data pembayaran!');</script>";
                }
            } else {
                echo "<script>alert('Gagal upload file!');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bukti Pembayaran - SAYURPKY</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary: #6366f1;
            --accent: #f59e0b;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --gray: #64748b;
            --light-gray: #f1f5f9;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%);
            min-height: 100vh;
            padding: 30px 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Progress Steps */
        .progress-steps {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary) 66.66%, var(--border) 66.66%, var(--border) 100%);
        }

        .steps-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 24px;
            font-weight: 700;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .step.completed .step-icon {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
        }

        .step.active .step-icon {
            background: linear-gradient(135deg, var(--secondary), #4f46e5);
            color: white;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
            animation: pulse 2s infinite;
        }

        .step.pending .step-icon {
            background: var(--light-gray);
            color: var(--gray);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .step-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray);
            margin-top: 8px;
        }

        .step.active .step-label,
        .step.completed .step-label {
            color: var(--dark);
        }

        .step-line {
            position: absolute;
            top: 30px;
            left: 50%;
            width: 100%;
            height: 3px;
            background: var(--border);
            z-index: 1;
        }

        .step.completed .step-line {
            background: var(--primary);
        }

        .step:last-child .step-line {
            display: none;
        }

        /* Header Card */
        .header-card {
            background: linear-gradient(135deg, white 0%, #fafafa 100%);
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(16, 185, 129, 0.1);
            position: relative;
            overflow: hidden;
        }

        .header-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 25px;
            position: relative;
            z-index: 1;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
            flex-shrink: 0;
        }

        .header-text h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .header-text .order-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray);
            font-size: 15px;
        }

        .order-badge {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 6px 14px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
        }

        /* Main Card */
        .card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
            border: 1px solid var(--border);
        }

        /* Total Payment Box */
        .total-payment-box {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 35px;
            box-shadow: 0 15px 30px rgba(16, 185, 129, 0.3);
            position: relative;
            overflow: hidden;
        }

        .total-payment-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .total-payment-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .total-amount {
            color: white;
            font-size: 38px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        /* Bank Info Grid */
        .bank-info-card {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #bfdbfe;
        }

        .bank-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .bank-info-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3);
        }

        .bank-info-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e40af;
        }

        .bank-info-subtitle {
            color: #3b82f6;
            font-size: 14px;
            margin-top: 4px;
        }

        .bank-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .bank-item {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .bank-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .bank-logo {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }

        .bank-name {
            font-weight: 700;
            color: #1e40af;
            font-size: 16px;
            margin-bottom: 6px;
        }

        .bank-account {
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
            font-family: 'Courier New', monospace;
        }

        /* Alert Box */
        .alert-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 5px solid var(--warning);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
        }

        .alert-icon {
            width: 50px;
            height: 50px;
            background: var(--warning);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 6px 12px rgba(245, 158, 11, 0.3);
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 700;
            color: #92400e;
            font-size: 16px;
            margin-bottom: 12px;
        }

        .alert-list {
            list-style: none;
            padding: 0;
        }

        .alert-list li {
            color: #78350f;
            padding: 6px 0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-list li::before {
            content: '✓';
            width: 20px;
            height: 20px;
            background: var(--warning);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            flex-shrink: 0;
        }

        /* Form Styling */
        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }

        .section-title i {
            color: var(--primary);
            font-size: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .form-label i {
            color: var(--primary);
            font-size: 16px;
        }

        .form-label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .form-hint {
            color: var(--gray);
            font-size: 13px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* File Upload */
        .file-upload-area {
            border: 3px dashed var(--border);
            border-radius: 20px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light-gray);
            position: relative;
            overflow: hidden;
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: var(--primary-light);
            transform: scale(1.02);
        }

        .file-upload-area.has-file {
            border-color: var(--success);
            background: #dcfce7;
        }

        .upload-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .upload-text {
            font-size: 17px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .upload-hint {
            color: var(--gray);
            font-size: 14px;
        }

        .file-input {
            display: none;
        }

        .file-preview {
            margin-top: 25px;
            display: none;
        }

        .preview-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid var(--success);
        }

        .preview-image {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .preview-name {
            margin-top: 15px;
            font-weight: 600;
            color: var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 15px;
        }

        .preview-name i {
            font-size: 20px;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }

        .btn {
            flex: 1;
            padding: 18px 30px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(16, 185, 129, 0.45);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.25);
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }

            .header-card {
                padding: 25px;
            }

            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .header-icon {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }

            .header-text h1 {
                font-size: 24px;
            }

            .card {
                padding: 25px;
            }

            .total-payment-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .total-amount {
                font-size: 32px;
            }

            .bank-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .steps-container {
                flex-direction: column;
                gap: 20px;
            }

            .step-line {
                display: none;
            }

            .progress-steps {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="steps-container">
                <div class="step completed">
                    <div class="step-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="step-label">Pesanan Dibuat</div>
                    <div class="step-line"></div>
                </div>
                <div class="step active">
                    <div class="step-icon">2</div>
                    <div class="step-label">Upload Bukti</div>
                    <div class="step-line"></div>
                </div>
                <div class="step pending">
                    <div class="step-icon">3</div>
                    <div class="step-label">Konfirmasi</div>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="header-card">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="header-text">
                    <h1>Upload Bukti Pembayaran</h1>
                    <div class="order-info">
                        <span>Nomor Pesanan:</span>
                        <span class="order-badge"><?php echo $no_pesanan; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <!-- Total Payment -->
            <div class="total-payment-box">
                <div class="total-payment-content">
                    <div class="total-label">
                        <i class="fas fa-wallet"></i>
                        Total yang harus dibayar
                    </div>
                    <div class="total-amount">
                        Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>
                    </div>
                </div>
            </div>

            <!-- Bank Information -->
            <div class="bank-info-card">
                <div class="bank-info-header">
                    <div class="bank-info-icon">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div>
                        <div class="bank-info-title">Rekening Tujuan SAYURPKY</div>
                        <div class="bank-info-subtitle">Silakan transfer ke salah satu rekening berikut</div>
                    </div>
                </div>
                
                <div class="bank-grid">
                    <div class="bank-item">
                        <span class="bank-logo">🏦</span>
                        <div class="bank-name">Mandiri</div>
                        <div class="bank-account">9876543210</div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;">SAYURPKY</div>
                    </div>
                    <div class="bank-item">
                        <span class="bank-logo">🏦</span>
                        <div class="bank-name">BNI</div>
                        <div class="bank-account">1122334455</div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;">SAYURPKY</div>
                    </div>
                    <div class="bank-item">
                        <span class="bank-logo">🏦</span>
                        <div class="bank-name">BRI</div>
                        <div class="bank-account">5544332211</div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;">SAYURPKY</div>
                    </div>
                    <div class="bank-item">
                        <span class="bank-logo">🏦</span>
                        <div class="bank-name">BSI</div>
                        <div class="bank-account">7788990011</div>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;">SAYURPKY</div>
                    </div>
                </div>
            </div>

            <!-- Alert Box -->
            <div class="alert-box">
                <div class="alert-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Petunjuk Upload Bukti Transfer</div>
                    <ul class="alert-list">
                        <li>Transfer sesuai dengan jumlah total pembayaran</li>
                        <li>Upload bukti transfer/struk dalam format JPG, PNG, atau PDF</li>
                        <li>Pastikan bukti transfer jelas dan dapat dibaca</li>
                        <li>Maksimal ukuran file 5MB</li>
                        <li>Pembayaran akan dikonfirmasi dalam 1x24 jam</li>
                    </ul>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Detail Pembayaran
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-credit-card"></i>
                                Metode Pembayaran
                                <span class="required">*</span>
                            </label>
                            <input type="text" class="form-input" value="Transfer Bank" disabled style="color: black;">
                            <input type="hidden" name="metode_pembayaran" value="transfer">
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-building-columns"></i>
                                Bank Tujuan
                                <span class="required">*</span>
                            </label>
                            <select name="bank" class="form-select" required>
                                <option value="">Pilih Bank</option>
                                <option value="BCA">BCA - 1234567890</option>
                                <option value="Mandiri">Mandiri - 9876543210</option>
                                <option value="BNI">BNI - 1122334455</option>
                                <option value="BRI">BRI - 5544332211</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-hashtag"></i>
                                Nomor Rekening Pengirim
                                <span class="required">*</span>
                            </label>
                            <input type="text" name="no_rekening" class="form-input" placeholder="Contoh: 1234567890" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i>
                                Atas Nama Pengirim
                                <span class="required">*</span>
                            </label>
                            <input type="text" name="atas_nama" class="form-input" placeholder="Nama sesuai rekening" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Jumlah Transfer
                            <span class="required">*</span>
                        </label>
                        <input type="number" name="jumlah_transfer" class="form-input"
                               value="<?php echo $pesanan['total_harga']; ?>" 
                               min="<?php echo $pesanan['total_harga']; ?>" 
                               placeholder="Jumlah yang ditransfer" required>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            Minimal transfer: Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Upload Bukti Transfer
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-file-image"></i>
                            Bukti Transfer
                            <span class="required">*</span>
                        </label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="upload-text">Klik untuk upload atau drag & drop</div>
                            <div class="upload-hint">JPG, PNG, PDF (Maksimal 5MB)</div>
                            <input type="file" name="bukti_transfer" id="buktiTransfer" class="file-input" accept="image/*,application/pdf" required>
                        </div>
                        <div class="file-preview" id="filePreview">
                            <div class="preview-container">
                                <img id="previewImage" class="preview-image" src="" alt="Preview">
                                <div class="preview-name" id="previewName">
                                    <i class="fas fa-check-circle"></i>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="pesanan.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Kembali
                    </a>
                    <button type="submit" name="upload_bukti" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Kirim Bukti Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fileUploadArea = document.getElementById('fileUploadArea');
        const buktiTransfer = document.getElementById('buktiTransfer');
        const filePreview = document.getElementById('filePreview');
        const previewImage = document.getElementById('previewImage');
        const previewName = document.getElementById('previewName');

        // Click to upload
        fileUploadArea.addEventListener('click', () => {
            buktiTransfer.click();
        });

        // File change handler
        buktiTransfer.addEventListener('change', handleFile);

        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                buktiTransfer.files = files;
                handleFile();
            }
        });

        function handleFile() {
            const file = buktiTransfer.files[0];
            
            if (file) {
                // Validate file size
                if (file.size > 5000000) {
                    alert('File terlalu besar! Maksimal 5MB');
                    buktiTransfer.value = '';
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau PDF');
                    buktiTransfer.value = '';
                    return;
                }

                // Show preview
                filePreview.style.display = 'block';
                previewName.querySelector('span').textContent = file.name;

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImage.style.display = 'none';
                }

                // Change upload area style
                fileUploadArea.classList.add('has-file');
            }
        }

        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const jumlahTransfer = parseFloat(document.querySelector('[name="jumlah_transfer"]').value);
            const totalPembayaran = <?php echo $pesanan['total_harga']; ?>;

            if (jumlahTransfer < totalPembayaran) {
                e.preventDefault();
                alert('⚠️ Jumlah transfer kurang dari total pembayaran!\n\nTotal: Rp ' + totalPembayaran.toLocaleString('id-ID') + '\nAnda transfer: Rp ' + jumlahTransfer.toLocaleString('id-ID'));
                return false;
            }

            if (!buktiTransfer.files[0]) {
                e.preventDefault();
                alert('⚠️ Silakan upload bukti transfer terlebih dahulu!');
                return false;
            }

            return confirm('✅ Kirim bukti pembayaran?\n\nPastikan data yang Anda masukkan sudah benar.');
        });
    </script>
</body>
</html>