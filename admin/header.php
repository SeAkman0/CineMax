<?php
// =======================================================
//  1. SİSTEM AYARLARI VE BAŞLATMA
// =======================================================

// Çıktı Tamponunu (Output Buffering) başlatıyoruz.
// Bu, "header already sent" hatasını önlemek ve sayfa başında oluşabilecek
// görünmez boşlukları temizlemek için gereklidir.
ob_start();

// Oturumu başlatıyoruz. Kullanıcı giriş yapmış mı, kimdir?
// Eğer zaten açıksa tekrar başlatmaya çalışıp hata verme.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Veritabanı bağlantı dosyamızı dahil ediyoruz.
// 'config' klasörü bir üst dizinde olduğu için '../' kullanıyoruz.
include '../config/database.php';

// --- TEMİZLİK ---
// Veritabanı dosyasının sonunda veya başında kalmış olabilecek
// boşlukları, satır atlamalarını temizle. (Beyaz çizgi sorununun ilacı)
ob_clean(); 

// =======================================================
//  2. GÜVENLİK KONTROLÜ (KRİTİK)
// =======================================================

// Eğer kullanıcı giriş yapmamışsa (user_id yoksa)
// VEYA giriş yapmış ama yetkisi 'admin' değilse...
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    // Onu derhal giriş sayfasına geri gönder.
    header("Location: ../login.php");
    exit; // Kodun devamını çalıştırma.
}

// =======================================================
//  3. AKTİF SAYFAYI BELİRLEME
// =======================================================

// Hangi sayfadayız? (Örn: dashboard.php, movies.php)
// Bunu, menüdeki linkleri parlatmak (active class) için kullanacağız.
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli - CinemaMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    
    <style>
        /* --- ADMIN PANELİNE ÖZEL CSS --- */
        /* Bu stiller sadece admin sayfalarında geçerlidir. */
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f4f6f9; margin: 0 !important; padding: 0 !important; }
        
        /* Ana Taşıyıcı (Flexbox ile yan yana dizilim) */
        .admin-container { display: flex; min-height: 100vh; }
        
        /* --- SIDEBAR (SOL MENÜ) TASARIMI --- */
        .sidebar { 
            width: 260px; /* Geniş Hal */
            background: #343a40; /* Koyu Gri */
            color: #fff; 
            flex-shrink: 0; /* Küçülmesine izin verme */
            padding-top: 50px; /* Üstten boşluk (Toggle butonu için) */
            transition: width 0.3s ease; /* Açılma/Kapanma animasyonu */
            position: relative;
            overflow: hidden; /* Yazılar taşmasın diye */
        }
        
        /* --- KÜÇÜLMÜŞ HALİ (.collapsed sınıfı eklenince) --- */
        .sidebar.collapsed { width: 60px; } /* Dar Hal */
        
        /* Küçülünce yazıları ve başlıkları gizle */
        .sidebar.collapsed span, 
        .sidebar.collapsed h2, 
        .sidebar.collapsed p,
        .sidebar.collapsed hr { display: none; }
        
        /* Küçülünce ikonları ortala */
        .sidebar.collapsed a { justify-content: center; padding: 15px 0; }
        .sidebar.collapsed i { margin-right: 0; font-size: 1.3rem; }
        
        /* --- TOGGLE (HAMBURGER) BUTONU --- */
        .toggle-btn {
            position: absolute;
            top: 15px;
            right: 15px; /* Sağ üst köşe */
            color: #ccc;
            cursor: pointer;
            font-size: 1.4rem;
            z-index: 100;
            transition: 0.3s;
        }
        .toggle-btn:hover { color: white; }
        
        /* Küçülünce butonu ortala */
        .sidebar.collapsed .toggle-btn { right: 50%; transform: translateX(50%); }

        /* Başlık ve Link Stilleri */
        .sidebar h2 { text-align: center; font-size: 1.5rem; margin-bottom: 5px; color: #fff; white-space: nowrap; }
        .sidebar p { text-align: center; font-size: 0.9rem; color: #adb5bd; margin-bottom: 20px; white-space: nowrap; }
        .sidebar hr { border: 0; border-top: 1px solid #4f5962; margin: 10px 0; }
        
        .sidebar a { 
            color: #c2c7d0; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            padding: 12px 20px; 
            transition: 0.3s; 
            border-left: 4px solid transparent; /* Sol çizgi efekti */
            white-space: nowrap; 
        }
        
        .sidebar a:hover { background: #4f5962; color: #fff; }
        .sidebar a.active { background: #007bff; color: #fff; border-left-color: #fff; } /* Mavi Aktif Rengi */
        
        .sidebar i { margin-right: 10px; width: 25px; text-align: center; transition: 0.3s; }
        
        /* --- İÇERİK ALANI (SAĞ TARAF) --- */
        .content { flex: 1; padding: 30px; overflow-y: auto; transition: 0.3s; }
        
        /* Tablo ve Buton Stilleri */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #e9ecef; font-weight: 600; color: #495057; }
        .btn-sm { padding: 5px 10px; font-size: 14px; width: auto; display: inline-block; }
        .btn-danger { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="admin-container">
        
        <div class="sidebar" id="sidebar">
            
            <div class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>

            <h2><i class="fas fa-film"></i> <span>CinemaMax</span></h2>
            <p><span>Yönetim Paneli</span></p>
            <hr>
            
            <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt fa-fw"></i> <span>Özet / İstatistik</span>
            </a>
            
            <a href="movies.php" class="<?php echo ($current_page == 'movies.php') ? 'active' : ''; ?>">
                <i class="fas fa-video fa-fw"></i> <span>Film Yönetimi</span>
            </a>

            <a href="halls.php" class="<?php echo ($current_page == 'halls.php') ? 'active' : ''; ?>">
                <i class="fas fa-chair fa-fw"></i> <span>Salon Yönetimi</span>
            </a>
            
            <a href="showtimes.php" class="<?php echo ($current_page == 'showtimes.php') ? 'active' : ''; ?>">
                <i class="fas fa-clock fa-fw"></i> <span>Seans Yönetimi</span>
            </a>
            

            <a href="tickets.php" class="<?php echo ($current_page == 'tickets.php') ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt fa-fw"></i> <span>Biletler</span>
            </a>

            <a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users fa-fw"></i> <span>Kullanıcılar</span>
            </a>
            
            <a href="scan-ticket.php" class="<?php echo ($current_page == 'scan-ticket.php') ? 'active' : ''; ?>">
                <i class="fas fa-qrcode fa-fw"></i> <span>QR Okut</span>
            </a>

            <a href="logs.php" class="<?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list fa-fw"></i> <span>Giriş Logları</span>
            </a>

            <hr>

            <a href="../index.php" target="_blank">
                <i class="fas fa-globe fa-fw"></i> <span>Siteye Git</span>
            </a>
            
            <a href="../logout.php" style="color: #ff6b6b;">
                <i class="fas fa-sign-out-alt fa-fw"></i> <span>Çıkış Yap</span>
            </a>
        </div>
        
        <script>
            // Sayfa yüklendiğinde hafızayı kontrol et (localStorage)
            document.addEventListener("DOMContentLoaded", function() {
                const sidebar = document.getElementById('sidebar');
                // Kullanıcı daha önce menüyü kapatmış mıydı?
                const isCollapsed = localStorage.getItem('sidebarState') === 'collapsed';
                
                if (isCollapsed) {
                    sidebar.classList.add('collapsed'); // Evetse, kapalı başlat
                }
            });

            // Butona basınca çalışacak fonksiyon
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('collapsed'); // Aç/Kapa
                
                // Durumu hafızaya kaydet (Sayfa yenilenince hatırlasın)
                if (sidebar.classList.contains('collapsed')) {
                    localStorage.setItem('sidebarState', 'collapsed');
                } else {
                    localStorage.setItem('sidebarState', 'expanded');
                }
            }
        </script>

        <div class="content">