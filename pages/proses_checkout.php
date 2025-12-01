<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user']) || !isset($_POST['checkout'])) {
    header("Location: login.php");
    exit;
}

// Ambil data user
$id_user = $_SESSION['user']['id_user'];
$nama_lengkap = $_SESSION['user']['nama_lengkap'];

// Ambil data dari form
$latitude = floatval($_POST['latitude']);
$longitude = floatval($_POST['longitude']);
$jarak = floatval($_POST['jarak']);
$ongkir = intval($_POST['ongkir']);
$no_telepon = trim($_POST['no_telepon']);
$alamat_lengkap = trim($_POST['alamat_lengkap']);
$payment_method = $_POST['payment'];

// Validasi input
if (empty($latitude) || empty($longitude) || empty($no_telepon) || empty($alamat_lengkap)) {
    echo "<script>alert('Data tidak lengkap!'); window.location='checkout.php';</script>";
    exit;
}

// Validasi keranjang
if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])) {
    echo "<script>alert('Keranjang kosong!'); window.location='produk.php';</script>";
    exit;
}

// Hitung total harga produk
$total_harga_produk = 0;
$detail_items = [];

foreach ($_SESSION['keranjang'] as $id_produk => $qty) {
    $produk = $conn->query("SELECT * FROM tbl_produk WHERE id_produk=$id_produk")->fetch_assoc();
    if ($produk) {
        // Cek stok
        if ($produk['stok'] < $qty) {
            echo "<script>alert('Stok {$produk['nama_produk']} tidak mencukupi!'); window.location='keranjang.php';</script>";
            exit;
        }
        
        $subtotal = $produk['harga'] * $qty;
        $total_harga_produk += $subtotal;
        
        $detail_items[] = [
            'id_produk' => $id_produk,
            'nama_produk' => $produk['nama_produk'],
            'harga_satuan' => $produk['harga'],
            'jumlah' => $qty,
            'subtotal' => $subtotal
        ];
    }
}

// Total pembayaran
$total_harga = $total_harga_produk + $ongkir;

// Generate nomor pesanan unik
$tgl = date('Ymd');
$random = strtoupper(substr(md5(time() . $id_user), 0, 6));
$no_pesanan = "ORD-{$tgl}-{$random}";

// Mulai transaksi
$conn->begin_transaction();

try {
    // 1. INSERT ke tbl_pesanan
    $stmt = $conn->prepare("
        INSERT INTO tbl_pesanan 
        (no_pesanan, id_user, tgl_pesanan, total_harga_produk, ongkir, diskon, total_harga, 
         status_pesanan, status_pembayaran, nama_penerima, no_telepon_penerima, alamat_pengiriman)
        VALUES (?, ?, NOW(), ?, ?, 0, ?, 'pending', 'belum_bayar', ?, ?, ?)
    ");
    
    $stmt->bind_param("sidddiss", 
        $no_pesanan, 
        $id_user, 
        $total_harga_produk, 
        $ongkir, 
        $total_harga,
        $nama_lengkap,
        $no_telepon,
        $alamat_lengkap
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Gagal menyimpan pesanan: " . $stmt->error);
    }
    
    $id_pesanan = $conn->insert_id;
    
    // 2. INSERT ke tbl_detail_pesanan & Update stok produk (PERBAIKAN DI SINI)
    $stmt_detail = $conn->prepare("
        INSERT INTO tbl_detail_pesanan 
        (id_pesanan, id_produk, nama_produk, harga_satuan, jumlah, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt_update_stok = $conn->prepare("
        UPDATE tbl_produk 
        SET stok = GREATEST(0, stok - ?),
            status = IF(stok - ? <= 0, 'habis', status)
        WHERE id_produk = ?
    ");
    
    foreach ($detail_items as $item) {
        // Insert detail pesanan
        $stmt_detail->bind_param("iisdid",
            $id_pesanan,
            $item['id_produk'],
            $item['nama_produk'],
            $item['harga_satuan'],
            $item['jumlah'],
            $item['subtotal']
        );
        
        if (!$stmt_detail->execute()) {
            throw new Exception("Gagal menyimpan detail pesanan: " . $stmt_detail->error);
        }
        
        // Update stok produk (HANYA 1X DI SINI)
        $stmt_update_stok->bind_param("iii",
            $item['jumlah'],
            $item['jumlah'],
            $item['id_produk']
        );
        
        if (!$stmt_update_stok->execute()) {
            throw new Exception("Gagal update stok produk: " . $stmt_update_stok->error);
        }
        
        // LOG untuk debugging (opsional, bisa dihapus di production)
        error_log("Stok produk ID {$item['id_produk']} dikurangi {$item['jumlah']}");
    }
    
    // 3. INSERT ke tbl_pengiriman (status: diproses)
    $stmt_pengiriman = $conn->prepare("
        INSERT INTO tbl_pengiriman 
        (id_pesanan, kurir, status, keterangan, nama_penerima)
        VALUES (?, 'SAYURPKY Express', 'diproses', ?, ?)
    ");
    
    $keterangan = "Jarak: {$jarak} km | Koordinat: {$latitude}, {$longitude}";
    
    $stmt_pengiriman->bind_param("iss", 
        $id_pesanan, 
        $keterangan,
        $nama_lengkap
    );
    
    if (!$stmt_pengiriman->execute()) {
        throw new Exception("Gagal membuat pengiriman: " . $stmt_pengiriman->error);
    }
    
    // 4. INSERT ke tbl_invoice
    $no_invoice = "INV-{$tgl}-{$random}";
    $jatuh_tempo = date('Y-m-d', strtotime('+3 days'));
    
    $stmt_invoice = $conn->prepare("
        INSERT INTO tbl_invoice 
        (no_invoice, id_pesanan, tgl_invoice, subtotal, ongkir, total, status, jatuh_tempo)
        VALUES (?, ?, NOW(), ?, ?, ?, 'draft', ?)
    ");
    
    $stmt_invoice->bind_param("siddds",
        $no_invoice,
        $id_pesanan,
        $total_harga_produk,
        $ongkir,
        $total_harga,
        $jatuh_tempo
    );
    
    if (!$stmt_invoice->execute()) {
        throw new Exception("Gagal membuat invoice: " . $stmt_invoice->error);
    }
    
    // 5. Simpan koordinat ke tbl_user untuk next order
    $stmt_user = $conn->prepare("
        UPDATE tbl_user 
        SET alamat = ?, no_telepon = ?
        WHERE id_user = ?
    ");
    
    $stmt_user->bind_param("ssi", $alamat_lengkap, $no_telepon, $id_user);
    $stmt_user->execute();
    
    // Commit transaksi
    $conn->commit();
    
    // 6. Hapus keranjang setelah sukses
    unset($_SESSION['keranjang']);
    
    // 7. Hapus dari database keranjang jika ada
    $conn->query("DELETE FROM tbl_keranjang WHERE id_user = $id_user");
    
    // 8. Redirect ke halaman pembayaran atau sukses
    $_SESSION['pesanan_baru'] = [
        'id_pesanan' => $id_pesanan,
        'no_pesanan' => $no_pesanan,
        'total' => $total_harga,
        'payment_method' => $payment_method
    ];
    
    header("Location: pesanan_sukses.php?order=" . $no_pesanan);
    exit;
    
} catch (Exception $e) {
    // Rollback jika ada error
    $conn->rollback();
    
    // Log error untuk debugging
    error_log("Checkout Error: " . $e->getMessage());
    
    echo "<script>
        alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "');
        window.location='checkout.php';
    </script>";
    exit;
}
?>