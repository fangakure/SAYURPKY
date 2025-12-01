<?php
session_start();
include "../config/db.php";

// Security check
if (!isset($_SESSION['admin'])) { 
    header("Location: login.php"); 
    exit; 
}

// Helper to determine if a status value should be considered "active".
function is_status_active($val) {
    if ($val === null) return false;
    $v = trim(strtolower((string)$val));
    // Accept common variants stored in the database: 'active', 'aktif', '1', 'yes', 'true', 'on'
    $activeVariants = ['active', 'aktif', '1', 'yes', 'true', 'on'];
    return in_array($v, $activeVariants, true);
}

// Add user
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $no = trim($_POST['no_telepon']);
    $alamat = trim($_POST['alamat']);
    $role = 'user'; // Set default role as user
    $status_raw = $_POST['status'] ?? 'active';
    $status = is_status_active($status_raw) ? 'active' : 'inactive';

    if ($username === '' || $password === '' || $nama === '' || $email === '') {
        header('Location: user.php?msg=error&text=' . urlencode('Lengkapi semua field yang diperlukan.'));
        exit;
    }

    // Check uniqueness
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM tbl_user WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res && $res['c'] > 0) {
        header('Location: user.php?msg=error&text=' . urlencode('Username atau email sudah digunakan.'));
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO tbl_user (username, password, password_plain, nama_lengkap, email, no_telepon, alamat, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $ins->bind_param('sssssssss', $username, $hash, $password, $nama, $email, $no, $alamat, $role, $status);
    
    if ($ins->execute()) {
        header('Location: user.php?msg=success&text=' . urlencode('User berhasil ditambahkan'));
        exit;
    } else {
        header('Location: user.php?msg=error&text=' . urlencode('Gagal menambahkan user'));
        exit;
    }
}

// Update user
if (isset($_POST['update_user'])) {
    $id = intval($_POST['id_user']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $no = trim($_POST['no_telepon']);
    $alamat = trim($_POST['alamat']);
    $role = in_array($_POST['role'] ?? 'user', ['admin','user']) ? $_POST['role'] : 'user';
    $status_raw = $_POST['status'] ?? 'active';
    $status = is_status_active($status_raw) ? 'active' : 'inactive';

    if ($username === '' || $nama === '' || $email === '') {
        header('Location: user.php?msg=error&text=' . urlencode('Lengkapi field yang diperlukan.'));
        exit;
    }

    // Check uniqueness excluding current user
    $chk = $conn->prepare("SELECT COUNT(*) as c FROM tbl_user WHERE (username = ? OR email = ?) AND id_user <> ?");
    $chk->bind_param('ssi', $username, $email, $id);
    $chk->execute();
    $cres = $chk->get_result()->fetch_assoc();
    if ($cres && $cres['c'] > 0) {
        header('Location: user.php?msg=error&text=' . urlencode('Username atau email sudah digunakan oleh user lain.'));
        exit;
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE tbl_user SET username=?, password=?, password_plain=?, nama_lengkap=?, email=?, no_telepon=?, alamat=?, role=?, status=?, updated_at=NOW() WHERE id_user=?");
        $up->bind_param('sssssssssi', $username, $hash, $password, $nama, $email, $no, $alamat, $role, $status, $id);
    } else {
        $up = $conn->prepare("UPDATE tbl_user SET username=?, nama_lengkap=?, email=?, no_telepon=?, alamat=?, role=?, status=?, updated_at=NOW() WHERE id_user=?");
        $up->bind_param('sssssssi', $username, $nama, $email, $no, $alamat, $role, $status, $id);
    }

    if ($up->execute()) {
        header('Location: user.php?msg=success&text=' . urlencode('Perubahan berhasil disimpan'));
        exit;
    } else {
        header('Location: user.php?msg=error&text=' . urlencode('Gagal menyimpan perubahan'));
        exit;
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $del = $conn->prepare("DELETE FROM tbl_user WHERE id_user = ?");
    $del->bind_param('i', $id);
    
    if ($del->execute()) {
        header('Location: user.php?msg=success&text=' . urlencode('User berhasil dihapus'));
        exit;
    } else {
        header('Location: user.php?msg=error&text=' . urlencode('Gagal menghapus user'));
        exit;
    }
}

// Get user for editing
$editing = false;
$editData = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $q = $conn->prepare("SELECT * FROM tbl_user WHERE id_user = ?");
    $q->bind_param('i', $id);
    $q->execute();
    $res = $q->get_result();
    if ($res && $res->num_rows === 1) {
        $editData = $res->fetch_assoc();
        $editing = true;
    }
}

// Fetch all users with statistics
$users = [];
$total_users = 0;
$total_admins = 0;
$total_active = 0;

$r = $conn->query("SELECT *, 
    CASE 
        WHEN LOWER(TRIM(status)) IN ('active','aktif','1','yes','true','on') THEN 'active'
        ELSE 'inactive'
    END as status_normalized
    FROM tbl_user ORDER BY id_user DESC");
if ($r) { 
    while ($row = $r->fetch_assoc()) {
        $users[] = $row;
        $total_users++;
        if ($row['role'] == 'admin') $total_admins++;
        if ($row['status_normalized'] == 'active') $total_active++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin SAYURPKY</title>
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
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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

        .page-header h1 i {
            font-size: 32px;
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
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-dark);
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-card.primary {
            border-left-color: var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--white);
        }

        .stat-icon.primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-icon.success { background: linear-gradient(135deg, var(--success), #00b894); }
        .stat-icon.info { background: linear-gradient(135deg, var(--info), #0984e3); }

        .stat-content h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .stat-content p {
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 500;
        }

        /* Alert Messages */
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

        .alert i {
            font-size: 20px;
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
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Main Grid Layout */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 25px;
        }

        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 2px solid var(--gray-100);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
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

        /* Table Styles */
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
            letter-spacing: 0.5px;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 16px;
        }

        .user-details strong {
            display: block;
            color: var(--gray-900);
            font-weight: 600;
        }

        .user-details small {
            color: var(--gray-600);
            font-size: 12px;
        }

        /* Password Display */
        .password-display {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-text {
            font-family: 'Courier New', monospace;
            background: var(--gray-100);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            min-width: 100px;
        }

        .password-hidden {
            color: var(--gray-500);
            letter-spacing: 2px;
        }

        .eye-btn {
            cursor: pointer;
            color: var(--info);
            font-size: 18px;
            transition: var(--transition);
        }

        .eye-btn:hover {
            color: var(--primary);
            transform: scale(1.1);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-admin {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: var(--white);
        }

        .badge-user {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: var(--white);
        }

        .badge-active {
            background: linear-gradient(135deg, var(--success), #00b894);
            color: var(--white);
        }

        .badge-inactive {
            background: linear-gradient(135deg, var(--gray-400), var(--gray-500));
            color: var(--white);
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            white-space: nowrap;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #ffa94d);
            color: var(--white);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #ff8c00, var(--warning));
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #ff6b6b);
            color: var(--white);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #d62828, var(--danger));
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        /* Form Styles */
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

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid var(--gray-100);
        }

        .form-actions button,
        .form-actions a {
            flex: 1;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .card-body {
                padding: 20px;
            }

            th, td {
                padding: 12px 10px;
                font-size: 13px;
            }

            .btn-group {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-600);
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-users"></i> Kelola User</h1>
                <div class="breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                    <span>/</span>
                    <span>Kelola User</span>
                </div>
            </div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <!-- Alert Messages -->
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

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_users ?></h3>
                    <p>Total Pengguna</p>
                </div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon info">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_admins ?></h3>
                    <p>Administrator</p>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_active ?></h3>
                    <p>User Aktif</p>
                </div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="main-grid">
            <!-- User List -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar User</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (count($users) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h4>Belum Ada User</h4>
                                <p>Tambahkan user baru menggunakan form di samping</p>
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Password</th>
                                        <th>Kontak</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                                                    </div>
                                                    <div class="user-details">
                                                        <strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong>
                                                        <small>@<?= htmlspecialchars($u['username']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="password-display">
                                                    <span class="password-text password-hidden" id="pwd-<?= $u['id_user'] ?>">••••••••</span>
                                                    <i class="fas fa-eye eye-btn" onclick="togglePassword(<?= $u['id_user'] ?>, '<?= htmlspecialchars($u['password_plain'] ?? 'N/A') ?>')"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <small style="display: block; color: var(--gray-700);">
                                                        <i class="fas fa-envelope" style="color: var(--primary);"></i> 
                                                        <?= htmlspecialchars($u['email']) ?>
                                                    </small>
                                                    <?php if (!empty($u['no_telepon'])): ?>
                                                        <small style="display: block; color: var(--gray-700); margin-top: 4px;">
                                                            <i class="fas fa-phone" style="color: var(--primary);"></i> 
                                                            <?= htmlspecialchars($u['no_telepon']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $u['role'] ?>">
                                                    <?= $u['role'] == 'admin' ? 'Admin' : 'User' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $u['status_normalized'] ?>">
                                                    <?= $u['status_normalized'] == 'active' ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="user.php?edit=<?= $u['id_user'] ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="user.php?delete=<?= $u['id_user'] ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Yakin ingin menghapus user <?= htmlspecialchars($u['username']) ?>?')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-<?= $editing ? 'edit' : 'plus-circle' ?>"></i> 
                        <?= $editing ? 'Edit User' : 'Tambah User Baru' ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="user.php">
                        <?php if ($editing): ?>
                            <input type="hidden" name="id_user" value="<?= intval($editData['id_user']) ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Username *</label>
                            <input type="text" name="username" required 
                                   value="<?= $editing ? htmlspecialchars($editData['username']) : '' ?>" 
                                   placeholder="Masukkan username">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Password <?= $editing ? '(Kosongkan jika tidak diubah)' : '*' ?></label>
                            <input type="password" name="password" 
                                   <?= $editing ? '' : 'required' ?> 
                                   placeholder="Masukkan password">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" required 
                                   value="<?= $editing ? htmlspecialchars($editData['nama_lengkap']) : '' ?>" 
                                   placeholder="Masukkan nama lengkap">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" required 
                                   value="<?= $editing ? htmlspecialchars($editData['email']) : '' ?>" 
                                   placeholder="contoh@email.com">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> No. Telepon</label>
                            <input type="text" name="no_telepon" 
                                   value="<?= $editing ? htmlspecialchars($editData['no_telepon']) : '' ?>" 
                                   placeholder="08xxxxxxxxxx">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on"></i> Status *</label>
                            <select name="status">
                                <option value="active" <?= ($editing && $editData['status'] == 'active') ? 'selected' : (!$editing ? 'selected' : '') ?>>Aktif</option>
                                <option value="inactive" <?= ($editing && $editData['status'] == 'inactive') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <?php if ($editing): ?>
                                <button type="submit" name="update_user" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                                <a href="user.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Batal
                                </a>
                            <?php else: ?>
                                <button type="submit" name="add_user" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah User
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(userId, plainPassword) {
            const element = document.getElementById('pwd-' + userId);
            const icon = event.target;
            
            if (element.classList.contains('password-hidden')) {
                element.textContent = plainPassword;
                element.classList.remove('password-hidden');
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                element.textContent = '••••••••';
                element.classList.add('password-hidden');
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

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
    </script>
</body>
</html>