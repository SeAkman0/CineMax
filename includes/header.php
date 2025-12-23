<?php
// Session başlatma kontrolü
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Aktif sayfayı bul (Link parlatmak için)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinemaMax - Sinema Bilet Sistemi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>

    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <a href="index.php" class="nav-logo">
                    <i class="fas fa-film"></i>
                    <span>CinemaMax</span>
                </a>
                
                <ul class="nav-menu">
                    <li>
                        <a href="index.php" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Ana Sayfa
                        </a>
                    </li>
                    
                    <li>
                        <a href="index.php#filmler" class="nav-link <?php echo ($current_page == 'booking.php' || $current_page == 'seat-selection.php' || $current_page == 'checkout.php') ? 'active' : ''; ?>">
                            <i class="fas fa-video"></i> Filmler
                        </a>
                    </li>
                    
                    <li>
                        <a href="halls.php" class="nav-link <?php echo ($current_page == 'halls.php') ? 'active' : ''; ?>">
                            <i class="fas fa-chair"></i> Salonlar
                        </a>
                    </li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        
                        <li>
                            <a href="my-tickets.php" class="nav-link <?php echo ($current_page == 'my-tickets.php') ? 'active' : ''; ?>">
                                <i class="fas fa-ticket-alt"></i> Biletlerim
                            </a>
                        </li>

                        <?php if($_SESSION['role'] == 'admin'): ?>
                            <li><a href="admin/dashboard.php" class="nav-link" style="color:#ff4757;"><i class="fas fa-user-shield"></i> Yönetim</a></li>
                        <?php endif; ?>

                    <?php endif; ?>
                </ul>
                
                <div class="nav-actions">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        
                        <div style="display:flex; align-items:center; color:white; font-size:0.95rem; margin-right:10px;">
                            <span style="margin-right:15px;">
                                <strong style="color:#ffd700; border-bottom:1px dashed #ffd700;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                            </span>
                            
                            <a href="logout.php" class="btn-icon" title="Güvenli Çıkış">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        </div>

                    <?php else: ?>
                        
                        <a href="login.php" class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>"><i class="fas fa-user"></i> Giriş Yap</a>
                        <a href="register.php" class="btn btn-primary" style="padding: 0.5rem 1.2rem; font-size: 0.9rem;">Kayıt Ol</a>
                    
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>