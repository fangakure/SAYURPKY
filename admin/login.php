<?php
session_start();
include "../config/db.php";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT * FROM tbl_user WHERE email=? AND role='admin' LIMIT 1");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = $admin;
        echo "<script>alert('Login berhasil!'); window.location='index.php';</script>";
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
    <title>Login Admin - Sistem Aplikasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #178644ff 0%, #1d8d33ff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #1c792dff 0%, #2a8c2cff 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-left-content {
            position: relative;
            z-index: 1;
        }

        .login-left h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .login-left p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: #298d2cff;
            box-shadow: 0 0 0 3px rgba(102, 187, 165, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2c9133ff 0%, #138a3bff 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 187, 165, 0.4);
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-left {
                padding: 40px 30px;
            }

            .login-left h1 {
                font-size: 28px;
            }

            .login-right {
                padding: 40px 30px;
            }

            .login-header h2 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-left-content">
                <h1>Login Admin</h1>
                <p>Masuk ke panel administrasi untuk mengelola pengguna, data, dan sistem aplikasi.</p>
                <div style="margin-top: 40px;">
                    <div style="display:flex;align-items:center;margin-bottom:20px;">
                        <div style="width:50px;height:50px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-right:15px;">
                            <i class="fas fa-user-shield" style="font-size:20px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:16px;margin-bottom:5px;">Akses Aman</h3>
                            <p style="font-size:13px;opacity:0.8;">Hanya untuk administrator sistem</p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;">
                        <div style="width:50px;height:50px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-right:15px;">
                            <i class="fas fa-cogs" style="font-size:20px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:16px;margin-bottom:5px;">Kontrol Penuh</h3>
                            <p style="font-size:13px;opacity:0.8;">Kelola semua fitur sistem dengan mudah</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Masuk Sebagai Admin</h2>
                <p>Gunakan akun administrator Anda</p>
            </div>

            <form method="post">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control" placeholder="admin@email.com" required>
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
                    <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                    Login
                </button>
            </form>
        </div>
    </div>

    <script>
        // Animasi fokus input
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.style.transform = 'scale(1.01)';
                this.parentElement.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.parentElement.style.transform = 'scale(1)';
            });
        });

        // Toggle password visibility
        const passwordInput = document.getElementById('password');
        const togglePassword = document.createElement('i');
        togglePassword.className = 'fas fa-eye';
        togglePassword.style.cssText = 'position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999;';
        passwordInput.parentElement.appendChild(togglePassword);

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    </script>
</body>
</html>
