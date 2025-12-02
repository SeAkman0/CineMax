<?php 
// Hata gösterme (Geliştirme modu)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/db.php'; 
include 'header.php'; 

if (!isset($_GET['id'])) { echo "<script>window.location.href='index.php';</script>"; exit; }
$film_id = $_GET['id'];

// 1. Film Verisini Çek
$stmt = $pdo->prepare("SELECT * FROM films WHERE id = ?");
$stmt->execute([$film_id]);
$film = $stmt->fetch();

if (!$film) { echo "<div class='container'>Film bulunamadı.</div>"; include 'footer.php'; exit; }

// 2. Seans Verisini Çek (Tarih ayrımı yapmadan hepsini çekiyoruz, aşağıda PHP ile filtreleyeceğiz)
$sql = "SELECT sessions.*, halls.name as hall_name 
        FROM sessions 
        LEFT JOIN halls ON sessions.hall_id = halls.id 
        WHERE sessions.film_id = ? 
        ORDER BY halls.name ASC, sessions.start_time ASC";
$stmtSessions = $pdo->prepare($sql);
$stmtSessions->execute([$film_id]);
$all_sessions = $stmtSessions->fetchAll();

// Gruplama İşlemi
$grouped = [];
foreach ($all_sessions as $sess) {
    $salonAdi = $sess['hall_name'] ? $sess['hall_name'] : "Diğer Salon";
    if (!isset($grouped[$salonAdi])) { $grouped[$salonAdi] = []; }
    $grouped[$salonAdi][] = $sess;
}
?>

<div style="background-color: #f8f9fa; min-height: 100vh; padding: 40px 0;">
    
    <div class="container">
        
        <div class="movie-detail-card">
            <div class="poster-wrapper">
                <img src="<?php echo $film['poster_url']; ?>" alt="<?php echo $film['title']; ?>">
            </div>
            
            <div class="info-wrapper">
                <h1 class="movie-title"><?php echo $film['title']; ?></h1>
                
                <div class="meta-tags">
                    <span><i class="fas fa-clock"></i> <?php echo $film['duration']; ?> dk</span>
                    <span><i class="fas fa-calendar-alt"></i> 2025</span>
                    <span class="rating"><i class="fas fa-star"></i> 8.9</span>
                </div>

                <p class="movie-desc"><?php echo $film['description']; ?></p>
            </div>
        </div>

        <div class="sessions-wrapper">
            <h3 class="section-header">Seans Seçimi</h3>

            <?php if (count($grouped) == 0): ?>
                <div class="no-session-box">
                    <i class="fas fa-film"></i>
                    <p>Bu film için henüz seans eklenmemiş.</p>
                </div>
            <?php else: ?>
                
                <div class="halls-grid">
                    <?php foreach ($grouped as $salonIsmi => $seanslar): ?>
                        <div class="hall-card">
                            <div class="hall-header">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $salonIsmi; ?>
                            </div>
                            
                            <div class="times-grid">
                                <?php foreach ($seanslar as $seans): ?>
                                    
                                    <?php 
                                        // --- ZAMAN KONTROLÜ (YENİ) ---
                                        $simdi = time();
                                        $seans_zamani = strtotime($seans['start_time']);
                                        $gecmis = $seans_zamani < $simdi; // True ise zaman geçmiş

                                        // CSS ve Link Ayarı
                                        $btnClass = $gecmis ? 'time-btn disabled' : 'time-btn';
                                        $href = $gecmis ? 'javascript:void(0)' : "koltuk-sec.php?id=" . $seans['id'];
                                        $title = $gecmis ? 'Bu seansın süresi geçti' : 'Bilet Al';
                                    ?>

                                    <a href="<?php echo $href; ?>" class="<?php echo $btnClass; ?>" title="<?php echo $title; ?>">
                                        <span class="time"><?php echo date("H:i", $seans_zamani); ?></span>
                                        <span class="date"><?php echo date("d.m", $seans_zamani); ?></span>
                                        <span class="price">
                                            <?php echo $gecmis ? 'KAPANDI' : $seans['price'] . ' ₺'; ?>
                                        </span>
                                    </a>

                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>

    </div>
</div>

<style>
    /* ... Önceki CSS Kodları Aynı ... */
    .movie-detail-card { background: white; border-radius: 16px; padding: 30px; display: flex; gap: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 40px; }
    .poster-wrapper img { width: 250px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    .info-wrapper { flex: 1; }
    .movie-title { font-size: 2.5rem; color: #2c3e50; margin-bottom: 15px; font-weight: 700; }
    .meta-tags { display: flex; gap: 20px; color: #7f8c8d; font-size: 1rem; margin-bottom: 25px; }
    .meta-tags i { color: #1e90ff; margin-right: 5px; }
    .movie-desc { color: #555; line-height: 1.8; font-size: 1.05rem; }
    .section-header { font-size: 1.5rem; color: #333; margin-bottom: 20px; border-left: 5px solid #1e90ff; padding-left: 15px; }
    .halls-grid { display: flex; flex-direction: column; gap: 20px; }
    .hall-card { background: white; border-radius: 12px; padding: 25px; border: 1px solid #eee; }
    .hall-header { font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .hall-header i { color: #1e90ff; }
    .times-grid { display: flex; flex-wrap: wrap; gap: 15px; }
    
    .time-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; border: 2px solid #e1e4e8; color: #333; padding: 10px 20px; border-radius: 10px; text-decoration: none; transition: all 0.2s ease; min-width: 100px; }
    .time-btn .time { font-size: 1.2rem; font-weight: 700; color: #1e90ff; }
    .time-btn .date { font-size: 0.8rem; color: #999; margin: 3px 0; }
    .time-btn .price { font-size: 0.9rem; font-weight: 600; color: #333; }
    .time-btn:hover { border-color: #1e90ff; background: #f0f7ff; transform: translateY(-2px); }
    .no-session-box { text-align: center; padding: 40px; background: white; border-radius: 12px; color: #999; }
    
    /* --- YENİ EKLENEN DISABLED STİLİ --- */
    .time-btn.disabled {
        background-color: #f2f2f2;
        border-color: #ddd;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .time-btn.disabled .time, 
    .time-btn.disabled .date, 
    .time-btn.disabled .price {
        color: #aaa !important;
    }
    .time-btn.disabled:hover {
        transform: none;
        background-color: #f2f2f2;
        border-color: #ddd;
    }

    @media (max-width: 768px) {
        .movie-detail-card { flex-direction: column; align-items: center; text-align: center; }
        .meta-tags { justify-content: center; }
        .movie-title { font-size: 2rem; }
    }
</style>

<?php include 'footer.php'; ?>