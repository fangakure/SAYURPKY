<?php
session_start();
include "../config/db.php";

// Security check
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get image to delete
    $img_query = $conn->query("SELECT gambar FROM tbl_edukasi WHERE id_edukasi=$id");
    if ($img_query && $img_query->num_rows > 0) {
        $img_data = $img_query->fetch_assoc();
        if (!empty($img_data['gambar']) && file_exists("../assets/img/{$img_data['gambar']}")) {
            unlink("../assets/img/{$img_data['gambar']}");
        }
    }
    
    $conn->query("DELETE FROM tbl_edukasi WHERE id_edukasi=$id");
    header("Location: edukasi.php?msg=success&text=" . urlencode('Artikel berhasil dihapus'));
    exit;
}

// Handle Add or Edit
if (isset($_POST['submit'])) {
    $judul = $conn->real_escape_string($_POST['judul']);
    $slug = $conn->real_escape_string($_POST['slug']);
    $konten = $conn->real_escape_string($_POST['konten']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $video_url = $conn->real_escape_string($_POST['video_url']);
    $tags = $conn->real_escape_string($_POST['tags']);

    $gambar = '';
    if (!empty($_FILES['gambar']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_ext)) {
            $gambar = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gambar']['name']);
            move_uploaded_file($_FILES['gambar']['tmp_name'], "../assets/img/$gambar");
        }
    }

    if ($_POST['mode'] == 'add') {
        $stmt = $conn->prepare("INSERT INTO tbl_edukasi (judul, slug, konten, kategori, gambar, video_url, tags, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssss", $judul, $slug, $konten, $kategori, $gambar, $video_url, $tags);
        
        if ($stmt->execute()) {
            header("Location: edukasi.php?msg=success&text=" . urlencode('Artikel berhasil ditambahkan'));
        } else {
            header("Location: edukasi.php?msg=error&text=" . urlencode('Gagal menambahkan artikel'));
        }
    } elseif ($_POST['mode'] == 'edit' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        
        // Delete old image if new one uploaded
        if ($gambar) {
            $old_img = $conn->query("SELECT gambar FROM tbl_edukasi WHERE id_edukasi=$id");
            if ($old_img && $old_img->num_rows > 0) {
                $old_data = $old_img->fetch_assoc();
                if (!empty($old_data['gambar']) && file_exists("../assets/img/{$old_data['gambar']}")) {
                    unlink("../assets/img/{$old_data['gambar']}");
                }
            }
        }
        
        $update_sql = "UPDATE tbl_edukasi SET judul='$judul', slug='$slug', konten='$konten', kategori='$kategori', video_url='$video_url', tags='$tags', updated_at=NOW()";
        if ($gambar) $update_sql .= ", gambar='$gambar'";
        $update_sql .= " WHERE id_edukasi=$id";
        
        if ($conn->query($update_sql)) {
            header("Location: edukasi.php?msg=success&text=" . urlencode('Artikel berhasil diperbarui'));
        } else {
            header("Location: edukasi.php?msg=error&text=" . urlencode('Gagal memperbarui artikel'));
        }
    }
    exit;
}

// Determine action
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch data for edit or view
$data = [];
if (($action == 'edit' || $action == 'view') && $id > 0) {
    $res = $conn->query("SELECT * FROM tbl_edukasi WHERE id_edukasi=$id");
    if ($res && $res->num_rows > 0) $data = $res->fetch_assoc();
}

// Get statistics
$total_articles = 0;
$count_query = $conn->query("SELECT COUNT(*) as total FROM tbl_edukasi");
if ($count_query) {
    $total_articles = $count_query->fetch_assoc()['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Edukasi - Admin SAYURPKY</title>
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
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

        .article-preview {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .article-img {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--gray-200);
        }

        .article-info strong {
            display: block;
            color: var(--gray-900);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .article-info small {
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
            background: linear-gradient(135deg, var(--info), #0984e3);
            color: var(--white);
        }

        /* Form */
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
            min-height: 150px;
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

        /* View Article */
        .article-detail {
            max-width: 900px;
        }

        .article-header {
            margin-bottom: 30px;
        }

        .article-header h2 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .article-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin: 15px 0;
            color: var(--gray-600);
            font-size: 14px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: var(--primary);
        }

        .article-image-large {
            width: 100%;
            max-width: 700px;
            height: auto;
            border-radius: var(--radius-lg);
            margin: 20px 0;
            box-shadow: var(--shadow-lg);
        }

        .article-content {
            margin: 30px 0;
            padding: 25px;
            background: var(--gray-50);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
            line-height: 1.8;
            color: var(--gray-700);
        }

        .tags-container {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 15px 0;
        }

        .tag {
            background: var(--gray-200);
            color: var(--gray-700);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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
                <h1><i class="fas fa-graduation-cap"></i> Kelola Edukasi</h1>
                <div class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <span>/</span>
                    <span>Kelola Edukasi</span>
                </div>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Alert -->
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] == 'success'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($_GET['text'] ?? 'Operasi berhasil!') ?></span>
                </div>
            <?php elseif ($_GET['msg'] == 'error'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($_GET['text'] ?? 'Terjadi kesalahan!') ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <!-- Statistics -->
            <div class="stats-card">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_articles ?></h3>
                    <p>Total Artikel</p>
                </div>
            </div>

            <!-- Article List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar Artikel Edukasi</h3>
                    <a href="edukasi.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Artikel
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php
                        $res = $conn->query("SELECT * FROM tbl_edukasi ORDER BY created_at DESC");
                        if ($res && $res->num_rows > 0):
                        ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Artikel</th>
                                        <th>Kategori</th>
                                        <th>Tags</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $res->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="article-preview">
                                                    <?php if (!empty($row['gambar'])): ?>
                                                        <img src="../assets/img/<?= htmlspecialchars($row['gambar']) ?>" 
                                                             class="article-img" 
                                                             alt="<?= htmlspecialchars($row['judul']) ?>">
                                                    <?php else: ?>
                                                        <div class="article-img" style="background: var(--gray-300); display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-image" style="font-size: 24px; color: var(--gray-500);"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="article-info">
                                                        <strong><?= htmlspecialchars($row['judul']) ?></strong>
                                                        <small><?= htmlspecialchars(substr($row['konten'], 0, 80)) ?>...</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge"><?= htmlspecialchars($row['kategori']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $tags = explode(',', $row['tags']);
                                                foreach (array_slice($tags, 0, 2) as $tag) {
                                                    $tag = trim($tag);
                                                    if (!empty($tag)) {
                                                        echo "<span class='tag'>" . htmlspecialchars($tag) . "</span> ";
                                                    }
                                                }
                                                if (count($tags) > 2) echo "<small>+" . (count($tags) - 2) . "</small>";
                                                ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <a href="?action=view&id=<?= $row['id_edukasi'] ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> Lihat
                                                    </a>
                                                    <a href="?action=edit&id=<?= $row['id_edukasi'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="?action=delete&id=<?= $row['id_edukasi'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus artikel ini?')">
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
                                <h4>Belum Ada Artikel</h4>
                                <p>Mulai tambahkan artikel edukasi dengan klik tombol "Tambah Artikel"</p>
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
                        <?= $action == 'add' ? 'Tambah Artikel Baru' : 'Edit Artikel' ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mode" value="<?= $action ?>">
                        <?php if ($action == 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label><i class="fas fa-heading"></i> Judul Artikel *</label>
                            <input type="text" name="judul" required 
                                   value="<?= htmlspecialchars($data['judul'] ?? '') ?>" 
                                   placeholder="Contoh: Manfaat Bayam untuk Kesehatan">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-link"></i> Slug *</label>
                            <input type="text" name="slug" required 
                                   value="<?= htmlspecialchars($data['slug'] ?? '') ?>" 
                                   placeholder="manfaat-bayam-untuk-kesehatan">
                            <small style="color: var(--gray-600); display: block; margin-top: 5px;">
                                URL-friendly (gunakan huruf kecil dan tanda hubung)
                            </small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> Konten *</label>
                            <textarea name="konten" required 
                                      placeholder="Tulis konten artikel edukasi secara detail"><?= htmlspecialchars($data['konten'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <select name="kategori" required>
                                <option value="tips" <?= ($data['kategori'] ?? '') == 'tips' ? 'selected' : '' ?>>Tips</option>
                                <option value="artikel" <?= ($data['kategori'] ?? '') == 'artikel' ? 'selected' : '' ?>>Artikel</option>
                                <option value="video" <?= ($data['kategori'] ?? '') == 'video' ? 'selected' : '' ?>>Video</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-tags"></i> Tags</label>
                            <input type="text" name="tags" 
                                   value="<?= htmlspecialchars($data['tags'] ?? '') ?>" 
                                   placeholder="sayur, sehat, organik (pisahkan dengan koma)">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-image"></i> Gambar</label>
                            <input type="file" name="gambar" accept="image/*">
                            <small style="color: var(--gray-600); display: block; margin-top: 5px;">
                                Format: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)
                            </small>
                            <?php if (!empty($data['gambar'])): ?>
                                <div class="image-preview">
                                    <img src="../assets/img/<?= htmlspecialchars($data['gambar']) ?>" alt="Preview">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-video"></i> URL Video (Opsional)</label>
                            <input type="text" name="video_url" 
                                   value
                                                                      value="<?= htmlspecialchars($data['video_url'] ?? '') ?>" 
                                   placeholder="https://www.youtube.com/watch?v=xxxxx">
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="edukasi.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action == 'view' && !empty($data)): ?>
            <!-- View Artikel -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-eye"></i> Lihat Artikel</h3>
                </div>
                <div class="card-body article-detail">
                    <div class="article-header">
                        <h2><?= htmlspecialchars($data['judul']) ?></h2>
                        <div class="article-meta">
                            <div class="meta-item">
                                <i class="fas fa-folder"></i> <?= htmlspecialchars($data['kategori']) ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i> 
                                <?= date('d M Y H:i', strtotime($data['created_at'])) ?>
                            </div>
                            <?php if (!empty($data['updated_at'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-edit"></i> Diperbarui: 
                                    <?= date('d M Y H:i', strtotime($data['updated_at'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($data['gambar'])): ?>
                        <img src="../assets/img/<?= htmlspecialchars($data['gambar']) ?>" 
                             alt="<?= htmlspecialchars($data['judul']) ?>" 
                             class="article-image-large">
                    <?php endif; ?>

                    <div class="article-content">
                        <?= nl2br(htmlspecialchars($data['konten'])) ?>
                    </div>

                    <?php if (!empty($data['video_url'])): ?>
                        <div style="margin: 30px 0;">
                            <h4><i class="fas fa-video"></i> Video Terkait</h4>
                            <div style="margin-top: 15px;">
                                <iframe width="560" height="315" 
                                        src="<?= htmlspecialchars($data['video_url']) ?>" 
                                        frameborder="0" allowfullscreen></iframe>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($data['tags'])): ?>
                        <div class="tags-container">
                            <?php 
                            $tags = explode(',', $data['tags']);
                            foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                <span class="tag"><?= htmlspecialchars($tag) ?></span>
                            <?php endif; endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <a href="edukasi.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <a href="?action=edit&id=<?= $data['id_edukasi'] ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="?action=delete&id=<?= $data['id_edukasi'] ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
