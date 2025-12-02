<?php
// Session başlatma kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../config/db.php';

// Güvenlik Kontrolü: Admin değilse at!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Aktif sayfayı bul (Link parlatmak için)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli - CinemaMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Admin Paneli Özel CSS */
        body { background-color: #f4f6f9; }
        .admin-container { display: flex; min-height: 100vh; }
        
        /* Sidebar Tasarımı */
        .sidebar { 
            width: 260px; 
            background: #343a40; 
            color: #fff; 
            flex-shrink: 0;
            padding-top: 20px;
        }
        
        .sidebar h2 {
            text-align: center;
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #fff;
        }
        
        .sidebar p {
            text-align: center;
            font-size: 0.9rem;
            color: #adb5bd;
            margin-bottom: 20px;
        }

        .sidebar hr {
            border: 0;
            border-top: 1px solid #4f5962;
            margin: 10px 0;
        }

        .sidebar a { 
            color: #c2c7d0; 
            text-decoration: none; 
            display: block; 
            padding: 12px 20px; 
            transition: 0.3s;
            border-left: 4px solid transparent; /* Sol çizgi efekti için */
        }
        
        .sidebar a:hover { 
            background: #4f5962; 
            color: #fff; 
        }

        /* Aktif Sayfa Stili */
        .sidebar a.active {
            background: #007bff; /* Mavi arka plan */
            color: #fff;
            border-left-color: #fff; /* Beyaz sol çizgi */
        }

        /* İkon ile yazı arasındaki boşluk */
        .sidebar i {
            margin-right: 10px;
            width: 20px; /* İkonların genişliğini eşitle */
            text-align: center;
        }

        /* İçerik Alanı */
        .content { 
            flex: 1; 
            padding: 30px; 
            overflow-y: auto;
        }

        /* Tablo ve Butonlar için genel ayarlar */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #e9ecef; font-weight: 600; color: #495057; }
        .btn-sm { padding: 5px 10px; font-size: 14px; width: auto; display: inline-block; }
        .btn-danger { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="admin-container">
        
        <div class="sidebar">
            <h2><i class="fas fa-film"></i> CinemaMax</h2>
            <p>Yönetim Paneli</p>
            <hr>
            
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt fa-fw"></i> Özet / İstatistik
            </a>
            
            <a href="films.php" class="<?php echo ($current_page == 'films.php') ? 'active' : ''; ?>">
                <i class="fas fa-video fa-fw"></i> Film Yönetimi
            </a>
            
            <a href="sessions.php" class="<?php echo ($current_page == 'sessions.php') ? 'active' : ''; ?>">
                <i class="fas fa-clock fa-fw"></i> Seans Yönetimi
            </a>
            
            <a href="halls.php" class="<?php echo ($current_page == 'halls.php') ? 'active' : ''; ?>">
                <i class="fas fa-chair fa-fw"></i> Salon Yönetimi
            </a>

            <a href="biletler.php" class="<?php echo ($current_page == 'biletler.php') ? 'active' : ''; ?>">
                <i class="fas fa-ticket fa-fw"></i> Biletler
            </a>

            <a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users fa-fw"></i> Kullanıcılar
            </a>

            <a href="logs.php" class="<?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list fa-fw"></i> Giriş Logları
            </a>

            <hr>

            <a href="../index.php" target="_blank">
                <i class="fas fa-globe fa-fw"></i> Siteye Git
            </a>
            
            <a href="../logout.php" style="color: #ff6b6b;">
                <i class="fas fa-sign-out-alt fa-fw"></i> Çıkış Yap
            </a>
        </div>
        
        <div class="content">