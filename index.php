<?php 
include 'config/db.php'; 
include 'header.php'; // Tasarladığın Header

// 1. En son eklenen filmi "Hero" (Manşet) için çek
$stmtHero = $pdo->query("SELECT * FROM films WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$heroFilm = $stmtHero->fetch();

// 2. Tüm filmleri çek (Hero film dahil hepsi listede de olsun)
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
                    <a href="film-detay.php?id=<?php echo $heroFilm['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-ticket-alt"></i> Bilet Al
                    </a>
                    <a href="film-detay.php?id=<?php echo $heroFilm['id']; ?>" class="btn btn-secondary">
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
        <h2 class="section-title">Vizyondaki Filmler</h2>
        
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
                <div class="movie-footer">
                    <a href="film-detay.php?id=<?php echo $film['id']; ?>" class="btn btn-primary" style="width:100%;">Bilet Al</a>
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

<?php include 'footer.php'; ?>