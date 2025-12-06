<?php 
// =======================================================
//  1. GELİŞTİRME VE AYARLAR
// =======================================================

// Geliştirme aşamasında hataları ekranda görmek için bu iki satırı açıyoruz.
// Canlıya (Production) geçerken bunları kapatmak güvenlik için iyidir.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantısı ve üst menü (Header) dosyasını çağırıyoruz.
include 'config/database.php'; 
include 'includes/header.php'; 


// =======================================================
//  2. GÜVENLİK VE FİLM KONTROLÜ
// =======================================================

// Eğer URL'de ?id=... yoksa, kullanıcıyı ana sayfaya yönlendiriyoruz.
// Çünkü hangi filmin detayını göstereceğimizi bilmiyoruz.
if (!isset($_GET['id'])) { 
    echo "<script>window.location.href='index.php';</script>"; 
    exit; 
}

$film_id = $_GET['id']; // URL'den gelen film ID'sini alıyoruz (Örn: 5)

// --- Veritabanından Film Bilgilerini Çekme ---
// SQL Injection saldırılarına karşı 'prepare' ve 'execute' kullanıyoruz.
$stmt = $pdo->prepare("SELECT * FROM films WHERE id = ?");
$stmt->execute([$film_id]);
$film = $stmt->fetch(); // Tek bir satır veri döner (Film bilgileri)

// Eğer böyle bir film yoksa veya silinmişse kullanıcıya mesaj verip duruyoruz.
if (!$film) { 
    echo "<div class='container'>Film bulunamadı.</div>"; 
    include 'includes/footer.php'; 
    exit; 
}


// =======================================================
//  3. SEANS VERİLERİNİ ÇEKME VE GRUPLAMA
// =======================================================

// Amacımız: Bu filme ait TÜM seansları ve bu seansların hangi salonda olduğunu çekmek.
// LEFT JOIN kullanıyoruz ki salon bilgisi de gelsin.
// Sıralama: Önce Salon Adına göre (A-Z), sonra Seans Saatine göre (Erkenden geçe).
$sql = "SELECT sessions.*, halls.name as hall_name 
        FROM sessions 
        LEFT JOIN halls ON sessions.hall_id = halls.id 
        WHERE sessions.film_id = ? 
        ORDER BY halls.name ASC, sessions.start_time ASC";

$stmtSessions = $pdo->prepare($sql);
$stmtSessions->execute([$film_id]);
$all_sessions = $stmtSessions->fetchAll(); // Tüm seansları bir dizi (array) olarak aldık.

// --- GRUPLAMA MANTIĞI (ÖNEMLİ) ---
// Veritabanından gelen veriler düz bir liste halindedir. 
// Biz bunları ekranda "Salon 1", "Salon 2" gibi başlıklar altında göstermek istiyoruz.
// Bu yüzden salon adına göre bir gruplama yapıyoruz.

$grouped = []; // Boş bir dizi oluşturuyoruz.

foreach ($all_sessions as $sess) {
    // Eğer salon adı boş gelirse (Silinmişse vb.) "Diğer Salon" diye isim ver.
    $salonAdi = $sess['hall_name'] ? $sess['hall_name'] : "Diğer Salon";
    
    // Eğer bu salon adı daha önce dizimize eklenmediyse, boş bir dizi olarak aç.
    if (!isset($grouped[$salonAdi])) { 
        $grouped[$salonAdi] = []; 
    }
    
    // Seansı ilgili salonun altına ekle.
    // Sonuç Yapısı: ['Salon 1' => [Seans1, Seans2], 'Salon 2' => [Seans3]]
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
                                        // --- ZAMAN KONTROLÜ (KRİTİK) ---
                                        // Şu anki zaman ile seansın zamanını karşılaştırıyoruz.
                                        $simdi = time();
                                        $seans_zamani = strtotime($seans['start_time']);
                                        
                                        // Eğer seans zamanı şimdiden küçükse, geçmiş demektir.
                                        $gecmis = $seans_zamani < $simdi; 

                                        // --- CSS ve LİNK AYARLARI ---
                                        // Geçmişse 'disabled' sınıfı ekle (gri yap) ve tıklanmasını engelle.
                                        // Değilse normal link ver.
                                        $btnClass = $gecmis ? 'time-btn disabled' : 'time-btn';
                                        $href = $gecmis ? 'javascript:void(0)' : "seat-selection.php?id=" . $seans['id'];
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
    /* Film Detay Kartı (Beyaz Kutu) */
    .movie-detail-card { 
        background: white; border-radius: 16px; padding: 30px; 
        display: flex; gap: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 40px; 
    }
    .poster-wrapper img { width: 250px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    .info-wrapper { flex: 1; }
    .movie-title { font-size: 2.5rem; color: #2c3e50; margin-bottom: 15px; font-weight: 700; }
    
    /* Etiketler (Süre, Yıl vb.) */
    .meta-tags { display: flex; gap: 20px; color: #7f8c8d; font-size: 1rem; margin-bottom: 25px; }
    .meta-tags i { color: #1e90ff; margin-right: 5px; }
    .movie-desc { color: #555; line-height: 1.8; font-size: 1.05rem; }
    
    /* Seans Başlıkları */
    .section-header { font-size: 1.5rem; color: #333; margin-bottom: 20px; border-left: 5px solid #1e90ff; padding-left: 15px; }
    .halls-grid { display: flex; flex-direction: column; gap: 20px; }
    .hall-card { background: white; border-radius: 12px; padding: 25px; border: 1px solid #eee; }
    .hall-header { font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .hall-header i { color: #1e90ff; }
    .times-grid { display: flex; flex-wrap: wrap; gap: 15px; }
    
    /* Seans Butonları (Aktif) */
    .time-btn { 
        display: flex; flex-direction: column; align-items: center; justify-content: center; 
        background: #fff; border: 2px solid #e1e4e8; color: #333; padding: 10px 20px; 
        border-radius: 10px; text-decoration: none; transition: all 0.2s ease; min-width: 100px; 
    }
    .time-btn .time { font-size: 1.2rem; font-weight: 700; color: #1e90ff; }
    .time-btn .date { font-size: 0.8rem; color: #999; margin: 3px 0; }
    .time-btn .price { font-size: 0.9rem; font-weight: 600; color: #333; }
    .time-btn:hover { border-color: #1e90ff; background: #f0f7ff; transform: translateY(-2px); }
    
    /* Seans Butonları (Pasif / Geçmiş) */
    .time-btn.disabled {
        background-color: #f2f2f2;
        border-color: #ddd;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .time-btn.disabled .time, 
    .time-btn.disabled .date, 
    .time-btn.disabled .price {
        color: #aaa !important; /* Yazıları gri yap */
    }
    .time-btn.disabled:hover {
        transform: none; /* Hover efektini iptal et */
        background-color: #f2f2f2;
        border-color: #ddd;
    }

    /* Mobil Uyum (Responsive) */
    @media (max-width: 768px) {
        .movie-detail-card { flex-direction: column; align-items: center; text-align: center; }
        .meta-tags { justify-content: center; }
        .movie-title { font-size: 2rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>