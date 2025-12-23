<?php 
include 'includes/header.php'; 
// Veritabanı bağlantısı header içinde yoksa buraya include 'config/database.php'; ekle

require_once 'config/database.php';

// Tüm salonları çek
$halls = $pdo->query("SELECT * FROM halls")->fetchAll();
?>

<div class="page-banner">
    <div class="banner-overlay"></div>
    <div class="banner-content">
        <h1>SALONLARIMIZ</h1>
        <p>En son teknolojiyle donatılmış sinema deneyimini keşfedin</p>
    </div>
</div>

<div class="container" style="padding: 60px 20px;">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
        
        <?php foreach($halls as $hall): ?>
        <div class="hall-card">
            
            <div class="hall-img-box">
                <img src="assets/images/halls/<?php echo !empty($hall['image_path']) ? $hall['image_path'] : '../assets/images/default-hall.jpg'; ?>" 
                     alt="<?php echo $hall['name']; ?>">
            </div>
            
            <div class="hall-info">
                <h3><?php echo $hall['name']; ?></h3>
                
                <p class="hall-desc">
                    <?php echo substr($hall['description'], 0, 90) . '...'; ?>
                </p>
                
                <div class="hall-footer">
                    <span class="capacity-badge">
                        <i class="fas fa-users"></i> <?php echo $hall['capacity']; ?> Kişilik
                    </span>
                    
                    <a href="hall-details.php?id=<?php echo $hall['id']; ?>" class="btn-inspect">
                        İncele <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<style>
    /* 1. BANNER AYARLARI (Arka Plan Resmi Buraya) */
    .page-banner {
        /* 👇 RESİM YOLUNU BURAYA YAZ 👇 */
        background-image: url('assets/images/halls/default-hall.jpg'); 
        
        background-size: cover;
        background-position: center;
        background-attachment: fixed; /* Parallax efekti (kayarken resim sabit kalır) */
        height: 400px; /* Banner yüksekliği */
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        
        /* Header ile birleşmesi için yukarı çekiyoruz */
        margin-top: -80px; 
        padding-top: 80px;
    }

    /* Karartma Katmanı (Resim üzerine siyah perde) */
    .banner-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6); /* %60 Siyahlık */
        z-index: 1;
    }

    /* Başlık Yazısı */
    .banner-content {
        position: relative;
        z-index: 2; /* Karartmanın üstünde durmalı */
    }
    .banner-content h1 {
        font-size: 4rem; /* KOCAMAN YAZI */
        font-weight: 800;
        letter-spacing: 5px;
        margin-bottom: 10px;
        text-shadow: 0 5px 15px rgba(0,0,0,0.5);
        text-transform: uppercase;
    }
    .banner-content p {
        font-size: 1.2rem;
        opacity: 0.9;
        font-weight: 300;
    }

    /* 2. KART TASARIMI (Daha modern hale getirdim) */
    .hall-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #eee;
    }
    .hall-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .hall-img-box {
        height: 220px;
        overflow: hidden;
    }
    .hall-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .hall-card:hover .hall-img-box img {
        transform: scale(1.1); /* Resim hoverda zoom yapar */
    }

    .hall-info { padding: 25px; }
    .hall-info h3 { margin: 0 0 10px 0; color: #333; font-size: 1.5rem; }
    .hall-desc { color: #777; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; height: 45px; overflow: hidden; }

    .hall-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f1f1f1;
        padding-top: 15px;
    }

    .capacity-badge {
        background: #f1f2f6;
        color: #555;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .btn-inspect {
        text-decoration: none;
        background: linear-gradient(135deg, #1e90ff 0%, #0077b6 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: 0.3s;
    }
    .btn-inspect:hover {
        background: linear-gradient(135deg, #0077b6 0%, #005f8b 100%);
        box-shadow: 0 5px 15px rgba(30, 144, 255, 0.3);
    }
</style>

<?php include 'includes/footer.php'; // Varsa footer ekle ?>