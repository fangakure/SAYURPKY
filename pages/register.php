<?php
include "../config/db.php";

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $alamat = $_POST['alamat'] ?? '';
    $no_telepon = $_POST['no_telepon'];

    $query = $conn->prepare("INSERT INTO tbl_user (username, nama_lengkap, email, password, alamat, no_telepon) VALUES (?,?,?,?,?,?)");
    $query->bind_param("ssssss", $username, $nama_lengkap, $email, $password, $alamat, $no_telepon);

    if ($query->execute()) {
        echo "<script>alert('✅ Registrasi berhasil! Silakan login.'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('❌ Registrasi gagal. Coba lagi.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi | SAYURPKY</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2e8b57;
    --primary-dark: #256e45;
    --gradient: linear-gradient(135deg, #2e8b57 0%, #43b774 100%);
    --bg: #f7f9fc;
    --text-dark: #222;
    --text-muted: #6c757d;
    --radius: 14px;
    --shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    --transition: all 0.3s ease;
}

* {
    margin: 0; padding: 0; box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: var(--bg);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #2e8b57 0%, #43b774 100%);
    padding: 20px;
}

/* === Card container === */
.register-container {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(12px);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    max-width: 950px;
    width: 100%;
    display: flex;
    overflow: hidden;
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* === Left section === */
.register-left {
    flex: 1;
    background: var(--gradient);
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px 50px;
    position: relative;
}

.register-left::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 200px; height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 6s ease-in-out infinite alternate;
}

@keyframes float {
    from { transform: translateY(0); }
    to { transform: translateY(20px); }
}

.register-left h1 {
    font-size: 38px;
    font-weight: 700;
    margin-bottom: 20px;
}

.register-left p {
    font-size: 16px;
    line-height: 1.6;
    opacity: 0.9;
}

/* === Right section (form) === */
.register-right {
    flex: 1;
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.register-header {
    text-align: center;
    margin-bottom: 40px;
}

.register-header h2 {
    font-size: 30px;
    color: var(--primary);
    font-weight: 700;
    margin-bottom: 10px;
}

.register-header p {
    color: var(--text-muted);
    font-size: 14px;
}

/* === Form fields === */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    color: var(--text-dark);
    font-weight: 500;
    margin-bottom: 6px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper .input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
}

.form-control {
    width: 100%;
    padding: 12px 15px 12px 42px;
    border: 1.8px solid #e0e0e0;
    border-radius: var(--radius);
    font-size: 14px;
    transition: var(--transition);
    background: #fff;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(46,139,87,0.15);
}

textarea.form-control {
    height: 80px;
    resize: none;
}

/* === Button === */
.btn-register {
    width: 100%;
    padding: 14px;
    background: var(--gradient);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(46,139,87,0.3);
}

.register-link {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
    color: var(--text-muted);
}

.register-link a {
    color: var(--primary);
    font-weight: 600;
    text-decoration: none;
}

.register-link a:hover {
    text-decoration: underline;
}

/* === Responsive === */
@media (max-width: 850px) {
    .register-container {
        flex-direction: column;
        max-width: 500px;
    }

    .register-left {
        padding: 40px 30px;
        text-align: center;
    }

    .register-right {
        padding: 40px 30px;
    }

    .register-left::before { display: none; }
}
</style>
</head>

<body>
<div class="register-container">
    <!-- Left Section -->
    <div class="register-left">
        <h1>Selamat Datang di SAYURPKY!</h1>
        <p>Daftar sekarang dan nikmati pengalaman belanja sayuran segar langsung dari petani lokal, dengan kualitas terbaik setiap harinya.</p>
    </div>

    <!-- Right Section -->
    <div class="register-right">
        <div class="register-header">
            <h2>Buat Akun Baru</h2>
            <p>Isi data berikut untuk membuat akun Anda</p>
        </div>

        <form method="post">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="form-control" placeholder="Username unik Anda" required>
                </div>
            </div>

            <div class="form-group">
                <label>Nama Lengkap</label>
                <div class="input-wrapper">
                    <i class="fas fa-id-card input-icon"></i>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama lengkap sesuai KTP" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password aman" required>
                </div>
            </div>

            <div class="form-group">
                <label>Nomor Telepon</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone input-icon"></i>
                    <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" required>
                </div>
            </div>

            <button type="submit" name="register" class="btn-register">
                <i class="fas fa-user-plus" style="margin-right:8px;"></i> Daftar Sekarang
            </button>

            <div class="register-link">
                Sudah punya akun? <a href="login.php">Masuk di sini</a>
            </div>
        </form>
    </div>
</div>

<script>
// Password show/hide toggle
const passwordInput = document.getElementById('password');
const toggleIcon = document.createElement('i');
toggleIcon.className = 'fas fa-eye';
toggleIcon.style.cssText = 'position:absolute; right:15px; top:50%; transform:translateY(-50%); color:#999; cursor:pointer;';
passwordInput.parentElement.appendChild(toggleIcon);
toggleIcon.addEventListener('click', () => {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;
    toggleIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
});
</script>
</body>
</html>