<?php
session_start();
if (!isset($_SESSION['admin'])) { 
    header("Location: login.php"); 
    exit; 
}
include "../config/db.php";

// Handle Delete
if (isset($_GET['action']) && $_GET['action']=='delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get image to delete
    $img_query = $conn->query("SELECT gambar FROM tbl_resep WHERE id_resep=$id");
    if ($img_query && $img_query->num_rows > 0) {
        $img_data = $img_query->fetch_assoc();
        if (!empty($img_data['gambar']) && file_exists("../assets/img/{$img_data['gambar']}")) {
            unlink("../assets/img/{$img_data['gambar']}");
        }
    }
    
    $conn->query("DELETE FROM tbl_resep WHERE id_resep=$id");
    header("Location: resep.php?msg=success&text=" . urlencode('Resep berhasil dihapus'));
    exit;
}

// Handle Add or Edit submission
if (isset($_POST['submit'])) {
    $judul = $conn->real_escape_string($_POST['judul_resep']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    $bahan = $conn->real_escape_string($_POST['bahan']);
    $cara = $conn->real_escape_string($_POST['cara_membuat']);
    $waktu = intval($_POST['waktu_memasak']);
    $porsi = intval($_POST['porsi']);
    $tingkat = $conn->real_escape_string($_POST['tingkat_kesulitan']);
    // video_url is optional and the database may not have the column; only read if provided
    $video = isset($_POST['video_url']) ? $conn->real_escape_string($_POST['video_url']) : '';

    $gambar = '';
    if (!empty($_FILES['gambar']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            $gambar = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gambar']['name']);
            move_uploaded_file($_FILES['gambar']['tmp_name'], "../assets/img/$gambar");
        }
    }

    if ($_POST['mode']=='add') {
        // note: video_url column may not exist in the current DB schema, so do not include it in the INSERT
        $conn->query("INSERT INTO tbl_resep (judul_resep, deskripsi, bahan, cara_membuat, waktu_memasak, porsi, tingkat_kesulitan, gambar, created_at) VALUES ('$judul','$deskripsi','$bahan','$cara',$waktu,$porsi,'$tingkat','$gambar', NOW())");
        header("Location: resep.php?msg=success&text=" . urlencode('Resep berhasil ditambahkan'));
    } elseif ($_POST['mode']=='edit' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        // Delete old image if new one uploaded
        if ($gambar) {
            $old_img = $conn->query("SELECT gambar FROM tbl_resep WHERE id_resep=$id");
            if ($old_img && $old_img->num_rows > 0) {
                $old_data = $old_img->fetch_assoc();
                if (!empty($old_data['gambar']) && file_exists("../assets/img/{$old_data['gambar']}")) {
                    unlink("../assets/img/{$old_data['gambar']}");
                }
            }
        }
        
    // Do not update video_url because the column may not exist in the DB
    $update_sql = "UPDATE tbl_resep SET judul_resep='$judul', deskripsi='$deskripsi', bahan='$bahan', cara_membuat='$cara', waktu_memasak=$waktu, porsi=$porsi, tingkat_kesulitan='$tingkat', updated_at=NOW()";
        if ($gambar) $update_sql .= ", gambar='$gambar'";
        $update_sql .= " WHERE id_resep=$id";
        $conn->query($update_sql);
        header("Location: resep.php?msg=success&text=" . urlencode('Resep berhasil diperbarui'));
    }
    exit;
}

// Determine action
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch data for edit or view
$data = [];
if (($action=='edit' || $action=='view') && $id>0) {
    $res = $conn->query("SELECT * FROM tbl_resep WHERE id_resep=$id");
    if ($res && $res->num_rows>0) $data = $res->fetch_assoc();
}

// Get statistics
$total_resep = 0;
$count_query = $conn->query("SELECT COUNT(*) as total FROM tbl_resep");
if ($count_query) {
    $total_resep = $count_query->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Resep - Admin SAYURPKY</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2d6a4f;
            --primary-dark: #1b4332;
            --primary-light: #40916c;
            --secondary: #52b788;
            --danger: #e63946;
            --warning: #f77f00;
            --success: #06d6a0;
            --info: #118ab2;
            --white: #ffffff;
            --gray-50: #f8f9fa;
            --gray-100: #f1f3f5;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.12);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Header */
        .page-header {
            background: var(--white);
            padding: 25px 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            font-size: 28px;
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .breadcrumb {
            display: flex;
            gap: 8px;
            align-items: center;
            color: var(--gray-600);
            font-size: 14px;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Alert */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background: #d1f2eb;
            color: #0c5132;
            border-left: 4px solid var(--success);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Statistics */
        .stats-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--white);
        }

        .stat-content h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .stat-content p {
            color: var(--gray-600);
            font-size: 14px;
        }

        /* Card */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 2px solid var(--gray-100);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #00b894);
            color: var(--white);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #ffa94d);
            color: var(--white);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #ff6b6b);
            color: var(--white);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info), #0984e3);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:hover {
            background: var(--gray-50);
        }

        .recipe-preview {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .recipe-img {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--gray-200);
        }

        .recipe-info strong {
            display: block;
            color: var(--gray-900);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .recipe-info small {
            color: var(--gray-600);
            font-size: 12px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-mudah {
            background: linear-gradient(135deg, var(--success), #00b894);
            color: var(--white);
        }

        .badge-sedang {
            background: linear-gradient(135deg, var(--warning), #ffa94d);
            color: var(--white);
        }

        .badge-sulit {
            background: linear-gradient(135deg, var(--danger), #ff6b6b);
            color: var(--white);
        }

        /* Form */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 6px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .image-preview {
            margin-top: 15px;
            border-radius: var(--radius-md);
            overflow: hidden;
            display: inline-block;
        }

        .image-preview img {
            max-width: 300px;
            width: 100%;
            height: auto;
            display: block;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid var(--gray-100);
        }

        /* View Recipe */
        .recipe-detail {
            max-width: 900px;
        }

        .recipe-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .recipe-header h2 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .recipe-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }

        .meta-item i {
            font-size: 24px;
            color: var(--primary);
        }

        .meta-item span {
            font-weight: 600;
            color: var(--gray-800);
        }

        .recipe-section {
            margin: 30px 0;
            padding: 25px;
            background: var(--gray-50);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
        }

        .recipe-section h3 {
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recipe-section p,
        .recipe-section ul {
            color: var(--gray-700);
            line-height: 1.8;
        }

        .recipe-section ul {
            list-style: none;
            padding-left: 0;
        }

        .recipe-section ul li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }

        .recipe-section ul li:before {
            content: "•";
            color: var(--primary);
            font-weight: bold;
            position: absolute;
            left: 0;
            font-size: 20px;
        }

        .recipe-image-large {
            width: 100%;
            max-width: 600px;
            height: auto;
            border-radius: var(--radius-lg);
            margin: 20px auto;
            display: block;
            box-shadow: var(--shadow-lg);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: var(--gray-400);
            margin-bottom: 20px;
        }

        .empty-state h4 {
            font-size: 20px;
            color: var(--gray-700);
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .page-header {
                padding: 20px;
            }

            .card-body {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .recipe-meta {
                gap: 15px;
            }

            .meta-item {
                padding: 10px 15px;
            }

            th, td {
                padding: 12px 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-utensils"></i> Kelola Resep</h1>
                <div class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <span>/</span>
                    <span>Kelola Resep</span>
                </div>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Alert -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($_GET['text'] ?? 'Operasi berhasil!') ?></span>
            </div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <!-- Statistics -->
            <div class="stats-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_resep ?></h3>
                    <p>Total Resep</p>
                </div>
            </div>

            <!-- Recipe List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Resep</h3>
                    <a href="resep.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Resep
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php
                        $res = $conn->query("SELECT * FROM tbl_resep ORDER BY created_at DESC");
                        if ($res && $res->num_rows > 0):
                        ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Resep</th>
                                        <th>Waktu</th>
                                        <th>Porsi</th>
                                        <th>Tingkat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $res->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="recipe-preview">
                                                    <?php if (!empty($row['gambar'])): ?>
                                                        <img src="../assets/img/<?= htmlspecialchars($row['gambar']) ?>" 
                                                             class="recipe-img" 
                                                             alt="<?= htmlspecialchars($row['judul_resep']) ?>">
                                                    <?php else: ?>
                                                        <div class="recipe-img" style="background: var(--gray-300); display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-image" style="font-size: 24px; color: var(--gray-500);"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="recipe-info">
                                                        <strong><?= htmlspecialchars($row['judul_resep']) ?></strong>
                                                        <small><?= htmlspecialchars(substr($row['deskripsi'], 0, 60)) ?>...</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-clock" style="color: var(--primary);"></i> 
                                                <?= $row['waktu_memasak'] ?> menit
                                            </td>
                                            <td>
                                                <i class="fas fa-users" style="color: var(--primary);"></i> 
                                                <?= $row['porsi'] ?> porsi
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($row['tingkat_kesulitan']) ?>">
                                                    <?= htmlspecialchars($row['tingkat_kesulitan']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <a href="?action=view&id=<?= $row['id_resep'] ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> Lihat
                                                    </a>
                                                    <a href="?action=edit&id=<?= $row['id_resep'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="?action=delete&id=<?= $row['id_resep'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus resep ini?')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-book-open"></i>
                                <h4>Belum Ada Resep</h4>
                                <p>Mulai tambahkan resep masakan dengan klik tombol "Tambah Resep"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-<?= $action == 'add' ? 'plus-circle' : 'edit' ?>"></i> 
                        <?= $action == 'add' ? 'Tambah Resep Baru' : 'Edit Resep' ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mode" value="<?= $action ?>">
                        <?php if ($action == 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Judul Resep *</label>
                            <input type="text" name="judul_resep" required 
                                   value="<?= htmlspecialchars($data['judul_resep'] ?? '') ?>" 
                                   placeholder="Contoh: Tumis Kangkung Saus Tiram">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Deskripsi *</label>
                            <textarea name="deskripsi" required 
                                      placeholder="Deskripsi singkat tentang resep ini"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Waktu Memasak (menit) *</label>
                                <input type="number" name="waktu_memasak" required min="1"
                                       value="<?= htmlspecialchars($data['waktu_memasak'] ?? '') ?>" 
                                       placeholder="30">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Porsi *</label>
                                <input type="number" name="porsi" required min="1"
                                       value="<?= htmlspecialchars($data['porsi'] ?? '') ?>" 
                                       placeholder="4">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-signal"></i> Tingkat Kesulitan *</label>
                                <select name="tingkat_kesulitan" required>
                                    <option value="">Pilih Tingkat</option>
                                    <option value="mudah" <?= ($data['tingkat_kesulitan'] ?? '') == 'mudah' ? 'selected' : '' ?>>Mudah</option>
                                    <option value="sedang" <?= ($data['tingkat_kesulitan'] ?? '') == 'sedang' ? 'selected' : '' ?>>Sedang</option>
                                    <option value="sulit" <?= ($data['tingkat_kesulitan'] ?? '') == 'sulit' ? 'selected' : '' ?>>Sulit</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-list-ul"></i> Bahan-Bahan *</label>
                            <textarea name="bahan" required rows="6"
                                      placeholder="Tuliskan bahan-bahan yang diperlukan (pisahkan dengan enter)"><?= htmlspecialchars($data['bahan'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-tasks"></i> Cara Membuat *</label>
                            <textarea name="cara_membuat" required rows="8"
                                      placeholder="Tuliskan langkah-langkah memasak secara detail"><?= htmlspecialchars($data['cara_membuat'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-image"></i> Gambar Resep</label>
                            <input type="file" name="gambar" accept="image/*">
                            <small style="color: var(--gray-600); display: block; margin-top: 5px;">
                                Format: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)
                            </small>
                            <?php if (!empty($data['gambar'])): ?>
                                <div class="image-preview">
                                    <img src="../assets/img/<?= htmlspecialchars($data['gambar']) ?>" 
                                         alt="Preview">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> 
                                <?= $action == 'add' ? 'Tambah Resep' : 'Simpan Perubahan' ?>
                            </button>
                            <a href="resep.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action == 'view'): ?>
            <!-- View Recipe Detail -->
            <div class="card">
                <div class="card-body">
                    <div class="recipe-detail">
                        <div class="recipe-header">
                            <h2><?= htmlspecialchars($data['judul_resep']) ?></h2>
                            <p style="color: var(--gray-600); font-size: 16px;">
                                <?= htmlspecialchars($data['deskripsi']) ?>
                            </p>

                            <?php if (!empty($data['gambar'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($data['gambar']) ?>" 
                                     class="recipe-image-large" 
                                     alt="<?= htmlspecialchars($data['judul_resep']) ?>">
                            <?php endif; ?>

                            <div class="recipe-meta">
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <small style="display: block; color: var(--gray-600); font-size: 12px;">Waktu</small>
                                        <span><?= $data['waktu_memasak'] ?> menit</span>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <div>
                                        <small style="display: block; color: var(--gray-600); font-size: 12px;">Porsi</small>
                                        <span><?= $data['porsi'] ?> porsi</span>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-signal"></i>
                                    <div>
                                        <small style="display: block; color: var(--gray-600); font-size: 12px;">Tingkat</small>
                                        <span class="badge badge-<?= strtolower($data['tingkat_kesulitan']) ?>">
                                            <?= htmlspecialchars($data['tingkat_kesulitan']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="recipe-section">
                            <h3><i class="fas fa-list-ul"></i> Bahan-Bahan</h3>
                            <ul>
                                <?php
                                $bahan_list = explode("\n", $data['bahan']);
                                foreach ($bahan_list as $bahan) {
                                    $bahan = trim($bahan);
                                    if (!empty($bahan)) {
                                        echo "<li>" . htmlspecialchars($bahan) . "</li>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>

                        <div class="recipe-section">
                            <h3><i class="fas fa-tasks"></i> Cara Membuat</h3>
                            <ol style="padding-left: 20px;">
                                <?php
                                $steps = explode("\n", $data['cara_membuat']);
                                $step_number = 1;
                                foreach ($steps as $step) {
                                    $step = trim($step);
                                    if (!empty($step)) {
                                        echo "<li style='padding: 10px 0; color: var(--gray-700);'>" . htmlspecialchars($step) . "</li>";
                                        $step_number++;
                                    }
                                }
                                ?>
                            </ol>
                        </div>

                        <?php if (!empty($data['video_url'])): ?>
                            <div class="recipe-section">
                                <h3><i class="fas fa-video"></i> Video Tutorial</h3>
                                <a href="<?= htmlspecialchars($data['video_url']) ?>" 
                                   target="_blank" 
                                   class="btn btn-info"
                                   style="display: inline-flex;">
                                    <i class="fas fa-play"></i> Tonton Video Tutorial
                                </a>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 30px; display: flex; gap: 12px; justify-content: center;">
                            <a href="resep.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <a href="?action=edit&id=<?= $data['id_resep'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Resep
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto hide alert after 5 seconds
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.animation = 'slideUp 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // Add slideUp animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideUp {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        `;
        document.head.appendChild(style);

        // Image preview before upload
        const imageInput = document.querySelector('input[type="file"][name="gambar"]');
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Check file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar! Maksimal 5MB');
                        this.value = '';
                        return;
                    }

                    // Preview image
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        let preview = document.querySelector('.image-preview');
                        if (!preview) {
                            preview = document.createElement('div');
                            preview.className = 'image-preview';
                            imageInput.parentNode.appendChild(preview);
                        }
                        preview.innerHTML = `<img src="${event.target.result}" alt="Preview">`;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Form validation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const judul = document.querySelector('input[name="judul_resep"]').value.trim();
                const deskripsi = document.querySelector('textarea[name="deskripsi"]').value.trim();
                const bahan = document.querySelector('textarea[name="bahan"]').value.trim();
                const cara = document.querySelector('textarea[name="cara_membuat"]').value.trim();

                if (!judul || !deskripsi || !bahan || !cara) {
                    e.preventDefault();
                    alert('Mohon lengkapi semua field yang wajib diisi (*)');
                    return false;
                }
            });
        }
    </script>
</body>
</html>