<?php
session_start();
include "../config/db.php";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT * FROM tbl_user WHERE email=? LIMIT 1");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        if (isset($user['role']) && $user['role'] === 'admin') {
            $_SESSION['admin'] = $user;
            echo "<script>alert('Login admin berhasil!'); window.location='../admin/index.php';</script>";
        } else {
            $_SESSION['user'] = $user;
            // Cek apakah ada halaman redirect setelah login
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']); // Hapus session redirect
                echo "<script>alert('Login berhasil!'); window.location='" . $redirect . "';</script>";
            } else {
                echo "<script>alert('Login berhasil!'); window.location='../index.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Email atau password salah!');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | SayurSegar</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #2e8b57;
    --primary-dark: #256e45;
    --gradient: linear-gradient(135deg, #2e8b57 0%, #43b774 100%);
    --bg: #f4f8f5;
    --radius: 14px;
    --shadow: 0 10px 25px rgba(0,0,0,0.15);
    --transition: all 0.3s ease;
}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
    background: var(--gradient);
    display:flex;justify-content:center;align-items:center;
    min-height:100vh;padding:20px;
}
.login-container{
    background:rgba(255,255,255,0.97);
    backdrop-filter:blur(12px);
    display:flex;
    border-radius:var(--radius);
    overflow:hidden;
    max-width:950px;
    width:100%;
    box-shadow:var(--shadow);
    animation:fadeIn 0.6s ease;
    position: relative;
}
@keyframes fadeIn{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}

.close-button {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 35px;
    height: 35px;
    background: white;
    border: 2px solid var(--primary);
    border-radius: 50%;
    color: var(--primary);
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    z-index: 10;
    text-decoration: none;
}

.close-button:hover {
    background: var(--primary);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(46,139,87,0.2);
}

/* LEFT PANEL */

.close-button {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 35px;
    height: 35px;
    background: var(--primary);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    z-index: 10;
}

.close-button:hover {
    background: var(--primary-dark);
    transform: scale(1.1);
}

/* LEFT PANEL */
.login-left{
    flex:1;
    background:var(--gradient);
    color:#fff;
    display:flex;
    flex-direction:column;
    justify-content:center;
    padding:60px 50px;
    position:relative;
}
.login-left::before{
    content:'';
    position:absolute;
    top:-60px;right:-60px;
    width:220px;height:220px;
    border-radius:50%;
    background:rgba(255,255,255,0.1);
    animation:float 6s ease-in-out infinite alternate;
}
@keyframes float{from{transform:translateY(0);}to{transform:translateY(20px);}}
.login-left h1{
    font-size:36px;
    font-weight:700;
    margin-bottom:20px;
}
.login-left p{
    font-size:16px;
    line-height:1.6;
    opacity:0.9;
}
/* RIGHT PANEL */
.login-right{
    flex:1;
    padding:60px 50px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}
.login-header{
    text-align:center;
    margin-bottom:40px;
}
.login-header h2{
    font-size:30px;
    font-weight:700;
    color:var(--primary);
}
.login-header p{
    color:#666;
    font-size:14px;
}
/* FORM ELEMENTS */
.form-group{margin-bottom:20px;}
.form-group label{
    display:block;
    margin-bottom:6px;
    font-weight:500;
    color:#333;
    font-size:14px;
}
.input-wrapper{position:relative;}
.input-icon{
    position:absolute;
    left:14px;top:50%;
    transform:translateY(-50%);
    color:#aaa;
}
.form-control{
    width:100%;
    padding:12px 15px 12px 42px;
    border:1.8px solid #e0e0e0;
    border-radius:var(--radius);
    font-size:14px;
    transition:var(--transition);
    background:#fff;
}
.form-control:focus{
    outline:none;
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(46,139,87,0.15);
}
.btn-login{
    width:100%;
    padding:14px;
    background:var(--gradient);
    color:#fff;
    border:none;
    border-radius:var(--radius);
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:var(--transition);
}
.btn-login:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(46,139,87,0.3);
}
.form-options{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}
.remember-me{
    display:flex;align-items:center;
    font-size:14px;color:#666;
}
.remember-me input{margin-right:8px;}
.forgot-password{
    color:var(--primary);
    text-decoration:none;
    font-weight:500;
}
.forgot-password:hover{text-decoration:underline;}
.register-link{
    text-align:center;
    margin-top:25px;
    font-size:14px;
    color:#666;
}
.register-link a{
    color:var(--primary);
    font-weight:600;
    text-decoration:none;
}
.register-link a:hover{text-decoration:underline;}
@media(max-width:850px){
    .login-container{flex-direction:column;max-width:500px;}
    .login-left{padding:40px 30px;text-align:center;}
    .login-right{padding:40px 30px;}
    .login-left::before{display:none;}
}
</style>
</head>
<body>
<div class="login-container">
    <a href="../index.php" class="close-button" title="Kembali ke Beranda">
        <i class="fas fa-times"></i>
    </a>
    <div class="login-left">
        <h1>Selamat Datang Kembali</h1>
        <p>Masuk ke akun Anda dan nikmati kemudahan berbelanja SAYURPKY, belajar lewat edukasi bermanfaat, serta menemukan berbagai resep lezat dalam satu platform terpadu.</p>
    </div>

    <div class="login-right">
        <div class="login-header">
            <h2>Masuk ke Akun</h2>
            <p>Gunakan email dan password Anda</p>
        </div>

        <form method="post">
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
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" name="login" class="btn-login">
                <i class="fas fa-sign-in-alt" style="margin-right:8px;"></i> Login
            </button>

            <div class="register-link">
                Belum punya akun? <a href="register.php">Daftar Sekarang</a>
            </div>
        </form>
    </div>
</div>

<script>
// Password toggle
const passwordInput = document.getElementById('password');
const toggleIcon = document.createElement('i');
toggleIcon.className = 'fas fa-eye';
toggleIcon.style.cssText = 'position:absolute;right:15px;top:50%;transform:translateY(-50%);cursor:pointer;color:#999;';
passwordInput.parentElement.appendChild(toggleIcon);
toggleIcon.addEventListener('click',()=>{
  const type=passwordInput.type==='password'?'text':'password';
  passwordInput.type=type;
  toggleIcon.className=type==='password'?'fas fa-eye':'fas fa-eye-slash';
});
</script>
</body>
</html>