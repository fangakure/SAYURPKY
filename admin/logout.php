<?php
session_start();

// Jika admin menekan tombol konfirmasi logout
if (isset($_POST['confirm_logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php?logout=success");
    exit;
}

// Jika membatalkan logout
if (isset($_POST['cancel'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logout - Admin Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #197e19ff, #228530ff);
        color: #333;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .logout-container {
        background: #fff;
        padding: 40px 50px;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        text-align: center;
        width: 400px;
        animation: fadeIn 0.6s ease;
    }
    @keyframes fadeIn {from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);}}
    h2 {margin-bottom: 10px; font-weight: 600;}
    p {color: #666; margin-bottom: 25px;}
    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        transition: 0.3s;
        margin: 5px;
        width: 140px;
    }
    .btn-confirm {
        background-color: #21801cff;
        color: #fff;
    }
    .btn-confirm:hover {background-color: #449684;}
    .btn-cancel {
        background-color: #e0e0e0;
        color: #333;
    }
    .btn-cancel:hover {background-color: #cfcfcf;}
    i {
        font-size: 50px;
        color: #1c861eff;
        margin-bottom: 20px;
    }
</style>
</head>
<body>

<div class="logout-container">
    <i class="fas fa-sign-out-alt"></i>
    <h2>Konfirmasi Logout</h2>
    <p>Apakah Anda yakin ingin keluar dari akun admin Anda?</p>

    <form method="post">
        <button type="submit" name="confirm_logout" class="btn btn-confirm">Ya, Logout</button>
        <button type="submit" name="cancel" class="btn btn-cancel">Batal</button>
    </form>
</div>

</body>
</html>
