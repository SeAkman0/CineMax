<?php 
include 'config/database.php'; 
include 'includes/header.php';

if (!isset($_GET['id'])) { echo "<script>window.location.href='index.php';</script>"; exit; }
$film_id = $_GET['id'];

// 1. Ana Film Bilgisi
$stmt = $pdo->prepare("SELECT * FROM films WHERE id = ?");
$stmt->execute([$film_id]);
$film = $stmt->fetch();

if (!$film) { echo "<div class='container'>Film bulunamadı.</div>"; include 'includes/footer.php'; exit; }

// 2. Diğer Vizyondaki Filmler (Bu film hariç rastgele 4 tane)
$stmtOther = $pdo->prepare("SELECT * FROM films WHERE is_active = 1 AND id != ? ORDER BY RAND() LIMIT 4");
$stmtOther->execute([$film_id]);
$otherFilms = $stmtOther->fetchAll();
?>

<div style="background: linear-gradient(to right, #000, #333); color: white; padding: 60px 0;">
    <div class="container" style="display:flex; gap:40px; align-items:flex-start; flex-wrap:wrap;">
        
        <img src="<?php echo $film['poster_url']; ?>" alt="<?php echo $film['title']; ?>" 
             style="width:300px; border-radius:10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        
        <div style="flex:1;">
            <h1 style="font-size:3rem; margin-bottom:10px;"><?php echo $film['title']; ?></h1>
            
            <div style="display:flex; gap:20px; font-size:1.1rem; color:#ccc; margin-bottom:20px;">
                <span><i class="fas fa-clock"></i> <?php echo $film['duration']; ?> dakika</span>
                <span><i class="fas fa-star" style="color:#f1c40f;"></i> 8.9 / 10</span>
                <span><i class="fas fa-calendar"></i> 2025</span>
            </div>

            <h3 style="margin-bottom:10px; color:#1e90ff;">Film Özeti</h3>
            <p style="font-size:1.1rem; line-height:1.8; opacity:0.9; margin-bottom:30px;">
                <?php echo $film['description']; ?>
            </p>

            <div style="display:flex; gap:15px;">
                <a href="booking.php?id=<?php echo $film['id']; ?>" class="btn btn-primary" style="padding:12px 30px; font-size:1.1rem;">
                    <i class="fas fa-ticket-alt"></i> Hemen Bilet Al
                </a>
                
                <?php if (!empty($film['trailer_url'])): ?>
                <a href="<?php echo $film['trailer_url']; ?>" target="_blank" class="btn btn-secondary" style="padding:12px 30px; font-size:1.1rem; display:inline-flex; align-items:center; gap:8px;">
                    <i class="fas fa-play"></i> Fragmanı İzle
                </a>
                <?php else: ?>
                <button class="btn btn-secondary" style="padding:12px 30px; font-size:1.1rem; opacity:0.5; cursor:not-allowed;" disabled>
                    <i class="fas fa-play"></i> Fragman Yok
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding: 60px 20px;">
    <h2 class="section-title">Bunları da Beğenebilirsiniz</h2>
    
    <div class="movies-grid">
        <?php foreach($otherFilms as $other): ?>
        <div class="movie-card">
            <img src="<?php echo $other['poster_url']; ?>" alt="<?php echo $other['title']; ?>">
            <div class="movie-info">
                <h3><?php echo $other['title']; ?></h3>
                <div style="display:flex; justify-content:space-between; color:#666; font-size:0.9rem; margin-top:5px;">
                    <span><i class="fas fa-clock"></i> <?php echo $other['duration']; ?> dk</span>
                    <span class="rating"><i class="fas fa-star"></i> 8.5</span>
                </div>
            </div>
            <div class="movie-footer">
                <a href="booking.php?id=<?php echo $other['id']; ?>" class="btn btn-primary" style="width:100%;">Bilet Al</a>
            </div>
            <div class="movie-footer" style="padding-top:0;">
                <a href="movie-details.php?id=<?php echo $other['id']; ?>" class="btn btn-secondary" style="width:100%; border:1px solid #ddd; color:#333;">İncele</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>