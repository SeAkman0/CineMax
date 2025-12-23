<?php 
// Header'ı dahil et (Menü ve stil dosyaları gelir)
include 'includes/header.php'; 

// Veritabanı bağlantısını çağır (Hata almamak için garanti olsun)
require_once 'config/database.php'; 

// SADECE SALONLARI ÇEKİYORUZ (Film vs. yok)
$halls = $pdo->query("SELECT * FROM halls ORDER BY id ASC")->fetchAll();
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
                    <?php echo substr($hall['description'], 0, 100) . '...'; ?>
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

        <?php if(count($halls) == 0): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #777;">
                Henüz hiç salon eklenmemiş.
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
    /* 1. BANNER AYARLARI */
    .page-banner {
        /* 👇 Banner resmini buraya koyacaksın 👇 */

        background-image: url('../assets/images/default-hall.jpg'); 
        
        background-size: cover;
        background-position: center;
        background-attachment: fixed; /* Resmi sabitler (Parallax) */
        height: 350px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        margin-top: -80px; /* Header boşluğunu kapatmak için */
        padding-top: 80px;
    }

    /* Karartma (Resim üzerine siyah perde) */
    .banner-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1;
    }

    .banner-content {
        position: relative;
        z-index: 2;
    }
    .banner-content h1 {
        font-size: 3.5rem;
        font-weight: 800;
        letter-spacing: 3px;
        margin-bottom: 10px;
        text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        text-transform: uppercase;
    }
    .banner-content p { font-size: 1.2rem; opacity: 0.9; }

    /* 2. KART TASARIMI */
    .hall-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
    }
    .hall-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .hall-img-box {
        height: 220px;
        overflow: hidden;
        position: relative;
    }
    .hall-img-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .hall-card:hover .hall-img-box img {
        transform: scale(1.05);
    }

    .hall-info { padding: 25px; flex: 1; display: flex; flex-direction: column; }
    .hall-info h3 { margin: 0 0 10px 0; color: #333; font-size: 1.4rem; font-weight: 700; }
    .hall-desc { color: #666; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; flex: 1; }

    .hall-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f1f1f1;
        padding-top: 15px;
        margin-top: auto; /* Footer'ı en alta it */
    }

    .capacity-badge {
        background: #f8f9fa;
        color: #555;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        border: 1px solid #eee;
    }

    .btn-inspect {
        text-decoration: none;
        background: #3498db;
        color: white;
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        transition: 0.3s;
    }
    .btn-inspect:hover {
        background: #2980b9;
        box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
    }
</style>

<?php include 'inculudes/footer.php'; ?>