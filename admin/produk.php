<?php
session_start();
include "../config/db.php";
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Get product for editing
$editing = false;
$editData = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM tbl_produk WHERE id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $editData = $result->fetch_assoc();
        $editing = true;
    }
}

// Update product
if (isset($_POST['update'])) {
    $id_produk = $_POST['id_produk'];
    $kode_produk = $_POST['kode_produk'];
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $satuan = $_POST['satuan'];
    $berat = $_POST['berat'];
    $id_kategori = $_POST['id_kategori'];
    
    // Handle image update
    if (!empty($_FILES['gambar']['name'])) {
        $gambar = $_FILES['gambar']['name'];
        move_uploaded_file($_FILES['gambar']['tmp_name'], "../assets/img/" . $gambar);
        
        $stmt = $conn->prepare("UPDATE tbl_produk SET kode_produk=?, nama_produk=?, deskripsi=?, id_kategori=?, 
                               harga=?, stok=?, satuan=?, berat=?, gambar=? WHERE id_produk=?");
        $stmt->bind_param("sssdiisssi", $kode_produk, $nama, $deskripsi, $id_kategori, $harga, $stok, $satuan, $berat, $gambar, $id_produk);
    } else {
        $stmt = $conn->prepare("UPDATE tbl_produk SET kode_produk=?, nama_produk=?, deskripsi=?, id_kategori=?, 
                               harga=?, stok=?, satuan=?, berat=? WHERE id_produk=?");
        $stmt->bind_param("sssdiissi", $kode_produk, $nama, $deskripsi, $id_kategori, $harga, $stok, $satuan, $berat, $id_produk);
    }
    
    if ($stmt->execute()) {
        echo "<script>alert('✅ Produk berhasil diperbarui!'); window.location='produk.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal memperbarui produk!'); window.location='produk.php';</script>";
    }
}

// Tambah produk baru
if (isset($_POST['tambah'])) {
    $kode_produk = $_POST['kode_produk'];
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $satuan = $_POST['satuan'];
    $berat = $_POST['berat'];
    $id_kategori = $_POST['id_kategori'];
    $gambar = $_FILES['gambar']['name'];

    if (!empty($gambar)) {
        move_uploaded_file($_FILES['gambar']['tmp_name'], "../assets/img/" . $gambar);
    }

    $stmt = $conn->prepare("INSERT INTO tbl_produk (kode_produk, nama_produk, deskripsi, id_kategori, harga, stok, satuan, berat, gambar) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiisss", $kode_produk, $nama, $deskripsi, $id_kategori, $harga, $stok, $satuan, $berat, $gambar);
    $stmt->execute();
    echo "<script>alert('✅ Produk berhasil ditambahkan!'); window.location='produk.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Produk - Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary:#2d6a4f;
    --primary-light:#52b788;
    --secondary:#95d5b2;
    --danger:#e63946;
    --white:#fff;
    --gray-50:#f8f9fa;
    --gray-100:#edf2f4;
    --gray-700:#495057;
    --shadow:0 5px 15px rgba(0,0,0,0.1);
    --radius:12px;
    --transition:all 0.3s ease;
}

body {
    font-family:'Inter',sans-serif;
    background:linear-gradient(135deg,#f6fff9 0%,#e9f8f1 100%);
    margin:0;
    padding:0;
    color:#333;
}

.container {
    max-width:1250px;
    margin:40px auto;
    background:var(--white);
    border-radius:var(--radius);
    padding:30px;
    box-shadow:var(--shadow);
}

.page-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:30px;
    background:var(--white);
    padding:25px 30px;
    border-radius:var(--radius);
    box-shadow:var(--shadow);
}

.page-header h1 {
    color:var(--primary);
    font-size:28px;
    display:flex;
    align-items:center;
    gap:10px;
    margin:0;
}

.page-header .breadcrumb {
    display:flex;
    gap:8px;
    align-items:center;
    color:var(--gray-700);
    font-size:14px;
    margin-top:5px;
}

.page-header .breadcrumb a {
    color:var(--primary);
    text-decoration:none;
    transition:var(--transition);
}

.page-header .breadcrumb a:hover {
    color:var(--primary-dark);
}

.btn-secondary {
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#e8f3ee;
    color:var(--primary);
    padding:10px 20px;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
    transition:var(--transition);
}

.btn-secondary:hover {
    background:#d1e7dd;
    transform:translateY(-2px);
}

.card {
    background:var(--white);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    margin-bottom:40px;
    padding:25px;
}

.card h3 {
    color:var(--primary);
    margin-top:0;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:10px;
}

.form-vertikal {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:20px;
}

.form-group {
    display:flex;
    flex-direction:column;
}

label {
    font-weight:600;
    color:var(--primary);
    margin-bottom:6px;
}

input, textarea, select {
    padding:12px;
    border:2px solid #d3e6df;
    border-radius:8px;
    font-size:14px;
    transition:var(--transition);
}

input:focus, textarea:focus, select:focus {
    border-color:var(--primary-light);
    box-shadow:0 0 0 3px rgba(82,183,136,0.2);
    outline:none;
}

textarea { resize:vertical; min-height:80px; }

.form-actions {
    grid-column:1/-1;
    display:flex;
    gap:10px;
}

.btn-primary {
    background:linear-gradient(135deg,var(--primary-light),#48b694);
    border:none;
    color:var(--white);
    padding:12px 20px;
    border-radius:8px;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    transition:var(--transition);
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.btn-primary:hover {
    background:var(--primary);
    transform:translateY(-2px);
}

button[name="tambah"] {
    background:linear-gradient(135deg,var(--primary-light),#48b694);
    border:none;
    color:var(--white);
    padding:12px 20px;
    border-radius:8px;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    transition:var(--transition);
}

button[name="tambah"]:hover {
    background:var(--primary);
    transform:translateY(-2px);
}

.table-wrapper {
    overflow-x:auto;
}

table {
    width:100%;
    border-collapse:collapse;
}

th, td {
    padding:12px 15px;
    text-align:center;
    border-bottom:1px solid var(--gray-100);
    font-size:14px;
}

th {
    background:linear-gradient(135deg,var(--primary),var(--primary-light));
    color:white;
    text-transform:uppercase;
}

tr:nth-child(even) { background:var(--gray-50); }
tr:hover { background:#ebfbee; }

img {
    width:60px;
    height:60px;
    object-fit:cover;
    border-radius:8px;
    border:1px solid #ccc;
}

.btn-edit {
    background:linear-gradient(135deg,#4a90e2,#357abd);
    color:white;
    padding:7px 12px;
    border-radius:6px;
    text-decoration:none;
    font-size:13px;
    transition:var(--transition);
    display:inline-block;
    margin-right:5px;
}

.btn-edit:hover {
    background:#2c5aa0;
    transform:scale(1.05);
}

.btn-hapus {
    background:linear-gradient(135deg,#ff6b6b,#e63946);
    color:white;
    padding:7px 12px;
    border-radius:6px;
    text-decoration:none;
    font-size:13px;
    transition:var(--transition);
    display:inline-block;
}

.btn-hapus:hover {
    background:#d62828;
    transform:scale(1.05);
}

.current-image {
    margin-top:10px;
}

.current-image img {
    max-width:150px;
    display:block;
    margin-bottom:5px;
}

.current-image small {
    color:var(--gray-700);
    font-style:italic;
}

@media (max-width:768px) {
    .form-vertikal { grid-template-columns:1fr; }
    th, td { font-size:13px; padding:8px; }
}
</style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-boxes"></i> Kelola Produk</h1>
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span>
                <span>Kelola Produk</span>
            </div>
        </div>
        <a href="index.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- FORM TAMBAH/EDIT PRODUK -->
    <div class="card">
        <h3>
            <i class="fas <?= $editing ? 'fa-edit' : 'fa-plus-circle' ?>"></i> 
            <?= $editing ? 'Edit Produk' : 'Tambah Produk Baru' ?>
        </h3>
        <form method="post" enctype="multipart/form-data" class="form-vertikal">
            <?php if ($editing): ?>
                <input type="hidden" name="id_produk" value="<?= $editData['id_produk'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="kode_produk">Kode Produk</label>
                <input type="text" name="kode_produk" id="kode_produk" 
                       value="<?= $editing ? htmlspecialchars($editData['kode_produk']) : '' ?>"
                       placeholder="Masukkan kode produk" required>
            </div>

            <div class="form-group">
                <label for="nama">Nama Produk</label>
                <input type="text" name="nama" id="nama" 
                       value="<?= $editing ? htmlspecialchars($editData['nama_produk']) : '' ?>"
                       placeholder="Masukkan nama produk" required>
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" placeholder="Masukkan deskripsi produk"><?= $editing ? htmlspecialchars($editData['deskripsi']) : '' ?></textarea>
            </div>

            <div class="form-group">
                <label for="harga">Harga</label>
                <input type="number" name="harga" id="harga" 
                       value="<?= $editing ? $editData['harga'] : '' ?>"
                       placeholder="Masukkan harga produk" required>
            </div>

            <div class="form-group">
                <label for="stok">Stok</label>
                <input type="number" name="stok" id="stok" 
                       value="<?= $editing ? $editData['stok'] : '' ?>"
                       placeholder="Masukkan jumlah stok" required>
            </div>

            <div class="form-group">
                <label for="satuan">Satuan</label>
                <input type="text" name="satuan" id="satuan" 
                       value="<?= $editing ? htmlspecialchars($editData['satuan']) : '' ?>"
                       placeholder="Contoh: Kg, Bungkus, Buah" required>
            </div>

            <div class="form-group">
                <label for="berat">Berat (Kg)</label>
                <input type="number" step="0.01" name="berat" id="berat" 
                       value="<?= $editing ? $editData['berat'] : '' ?>"
                       placeholder="Masukkan berat dalam Kg" required>
            </div>

            <div class="form-group">
                <label for="id_kategori">Kategori</label>
                <select name="id_kategori" id="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php
                    $kategori_q = $conn->query("SELECT id_kategori, nama_kategori FROM tbl_kategori ORDER BY nama_kategori ASC");
                    while ($k = $kategori_q->fetch_assoc()) {
                        $selected = ($editing && $editData['id_kategori'] == $k['id_kategori']) ? 'selected' : '';
                        echo "<option value='{$k['id_kategori']}' {$selected}>{$k['nama_kategori']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="gambar">Gambar Produk <?= $editing ? '(Kosongkan jika tidak ingin mengubah gambar)' : '' ?></label>
                <input type="file" name="gambar" id="gambar" accept="image/*" <?= $editing ? '' : 'required' ?>>
                <?php if ($editing && !empty($editData['gambar'])): ?>
                <div class="current-image">
                    <img src="../assets/img/<?= htmlspecialchars($editData['gambar']) ?>" alt="Current Image">
                    <small>Gambar saat ini</small>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <?php if ($editing): ?>
                    <button type="submit" name="update" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="produk.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                <?php else: ?>
                    <button type="submit" name="tambah">
                        <i class="fas fa-save"></i> Simpan Produk
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- DAFTAR PRODUK -->
    <div class="card">
        <h3><i class="fas fa-box-open"></i> Daftar Produk</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kode</th>
                        <th>Nama Produk</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                        <th>Berat (Kg)</th>
                        <th>Kategori</th>
                        <th>Gambar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT p.*, k.nama_kategori FROM tbl_produk p 
                                            LEFT JOIN tbl_kategori k ON p.id_kategori = k.id_kategori
                                            ORDER BY p.id_produk DESC");
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $kategori = $row['nama_kategori'] ?? '-';
                            echo "<tr>
                                <td>{$row['id_produk']}</td>
                                <td>{$row['kode_produk']}</td>
                                <td>".htmlspecialchars($row['nama_produk'])."</td>
                                <td>".htmlspecialchars($row['deskripsi'])."</td>
                                <td>Rp ".number_format($row['harga'], 0, ',', '.')."</td>
                                <td>{$row['stok']}</td>
                                <td>{$row['satuan']}</td>
                                <td>{$row['berat']} Kg</td>
                                <td>".htmlspecialchars($kategori)."</td>
                                <td><img src='../assets/img/{$row['gambar']}' alt='Produk'></td>
                                <td>
                                    <a href='produk.php?edit={$row['id_produk']}' class='btn-edit'>
                                        <i class='fas fa-edit'></i> Edit
                                    </a>
                                    <a href='hapus_produk.php?id={$row['id_produk']}' class='btn-hapus' onclick='return confirm(\"Yakin ingin menghapus produk ini?\")'>
                                        <i class='fas fa-trash-alt'></i> Hapus
                                    </a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='11'>Belum ada produk yang ditambahkan.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>