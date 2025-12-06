<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİ DOSYALAR
// =======================================================
include 'config/database.php'; 
include 'includes/header.php'; 

// A. HERO FİLMİ (En Üstteki Büyük Film)
// Vizyondaki en son eklenen filmi seçiyoruz.
$stmtHero = $pdo->query("SELECT * FROM films WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$heroFilm = $stmtHero->fetch();

// B. FİLM LİSTESİ (Aşağıdaki Kartlar)
// Vizyondaki tüm filmleri listeliyoruz.
$stmtAll = $pdo->query("SELECT * FROM films WHERE is_active = 1 ORDER BY id DESC");
$films = $stmtAll->fetchAll();
?>

    <?php if ($heroFilm): ?>
    <section class="hero">
        <div class="container" style="display:flex; align-items:center; width:100%;">
            
            <div class="hero-content">
                <span style="color:#ffd700; font-weight:600; text-transform:uppercase; letter-spacing:2px; display:block; margin-bottom:10px;">VİZYONDAKİ EN YENİ FİLM</span>
                <h1 class="hero-title"><?php echo $heroFilm['title']; ?></h1>
                <p class="hero-subtitle"><?php echo mb_substr($heroFilm['description'], 0, 150) . '...'; ?></p>
                
                <div class="hero-buttons">
                    <a href="booking.php?id=<?php echo $heroFilm['id']; ?>#seanslar" class="btn btn-primary">
                        <i class="fas fa-ticket-alt"></i> Bilet Al
                    </a>
                    <a href="movie-details.php?id=<?php echo $heroFilm['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-info-circle"></i> Detaylar
                    </a>
                </div>
            </div>

            <div class="hero-image">
                <div class="movie-poster-hero">
                    <img src="<?php echo $heroFilm['poster_url']; ?>" alt="Film Posteri" style="width:100%; border-radius:20px; display:block;">
                </div>
            </div>

        </div>
    </section>
    <?php endif; ?>


    <div class="container" id="filmler">
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin: 40px 0 20px 0; flex-wrap:wrap; gap:20px;">
            <h2 class="section-title" style="margin:0;">Vizyondaki Filmler</h2>
            
            <div style="position:relative;">
                <input type="text" id="movieSearchInput" placeholder="Film ara..." 
                       style="padding: 10px 15px 10px 40px; border-radius: 25px; border: 1px solid #ddd; width: 250px; outline:none; transition:0.3s;">
                <i class="fas fa-search" style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#aaa;"></i>
            </div>
        </div>
        
        <div class="movies-grid">
            <?php foreach($films as $film): ?>
            
            <div class="movie-card">
                <img src="<?php echo $film['poster_url']; ?>" alt="<?php echo $film['title']; ?>">
                
                <div class="movie-info">
                    <h3><?php echo $film['title']; ?></h3>
                    <div style="display:flex; justify-content:space-between; color:#666; font-size:0.9rem; margin-top:5px;">
                        <span><i class="fas fa-clock"></i> <?php echo $film['duration']; ?> dk</span>
                        <span class="rating"><i class="fas fa-star"></i> 8.5</span>
                    </div>
                </div>
                
                <div class="movie-footer" style="padding: 15px; display: flex; gap: 10px; border-top: 1px solid #eee;">
                    
                    <a href="booking.php?id=<?php echo $film['id']; ?>#seanslar" class="btn btn-primary" 
                       style="flex: 1; font-size: 0.9rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px;">
                        <i class="fas fa-ticket-alt"></i> Bilet
                    </a>

                    <a href="movie-details.php?id=<?php echo $film['id']; ?>" class="btn-outline-custom"
                       style="flex: 1; font-size: 0.9rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px; text-decoration: none;">
                        <i class="fas fa-info-circle"></i> Detay
                    </a>

                </div>

            </div>
            <?php endforeach; ?>
        </div>
    </div>


    <section style="background:white; padding:50px 0;">
        <div class="container">
            <h2 class="section-title" style="margin-top:0; font-size:2rem;">Hızlı Erişim</h2>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px; text-align:center;">
                <div style="padding:20px; border:1px solid #eee; border-radius:10px;">
                    <i class="fas fa-calendar-check" style="font-size:2rem; color:#1e90ff; margin-bottom:10px;"></i>
                    <h3>Seanslar</h3>
                    <p>Günlük program</p>
                </div>
                <div style="padding:20px; border:1px solid #eee; border-radius:10px;">
                    <i class="fas fa-gift" style="font-size:2rem; color:#1e90ff; margin-bottom:10px;"></i>
                    <h3>Kampanyalar</h3>
                    <p>Fırsatları kaçırma</p>
                </div>
                <div style="padding:20px; border:1px solid #eee; border-radius:10px;">
                    <i class="fas fa-map-marker-alt" style="font-size:2rem; color:#1e90ff; margin-bottom:10px;"></i>
                    <h3>Salonlarımız</h3>
                    <p>Konforlu koltuklar</p>
                </div>
            </div>
        </div>
    </section>

<style>
    /* Detay Butonu için özel stil */
    .btn-outline-custom {
        background-color: transparent;
        color: #1e90ff;
        border: 1px solid #1e90ff;
        border-radius: 50px; /* Yuvarlak kenar */
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
    }
    .btn-outline-custom:hover {
        background-color: #1e90ff;
        color: white;
    }
</style>

<?php include 'includes/footer.php'; ?>