<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİLİKLER
// =======================================================

// Admin paneli için ortak üst menüyü ve veritabanı bağlantısını çağırıyoruz.
// Bu dosyanın içinde session başlatma ve admin yetki kontrolü zaten yapılıyor.
include 'header.php';

// =======================================================
//  2. ÖZET İSTATİSTİKLERİ ÇEKME (ÜST KARTLAR)
// =======================================================

// --- A. TOPLAM SATILAN BİLET SAYISI ---
// tickets tablosundaki tüm satırları sayıyoruz.
$stmtTicket = $pdo->query("SELECT COUNT(*) FROM tickets");
$totalTickets = $stmtTicket->fetchColumn(); // Tek bir değer (sayı) döner.

// --- B. TOPLAM HASILAT (KAZANÇ) ---
// Bilet tablosunda fiyat yazmaz, seans tablosunda yazar.
// Bu yüzden tickets tablosunu sessions tablosuyla birleştiriyoruz (JOIN).
// SUM(s.price): Tüm biletlerin fiyatlarını topluyoruz.
$stmtRevenue = $pdo->query("SELECT SUM(s.price) FROM tickets t JOIN sessions s ON t.session_id = s.id");
$totalRevenue = $stmtRevenue->fetchColumn(); // Toplam kazanç (TL) döner.

// --- C. TOPLAM FİLM SAYISI ---
// Veritabanındaki tüm filmleri sayıyoruz.
$stmtFilms = $pdo->query("SELECT COUNT(*) FROM films");
$totalFilms = $stmtFilms->fetchColumn();

// --- D. KAYITLI KULLANICI SAYISI ---
// Sadece normal üyeleri ('user') sayıyoruz, adminleri saymıyoruz.
$stmtUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$totalUsers = $stmtUsers->fetchColumn();


// =======================================================
//  3. DETAYLI TABLO VERİLERİNİ ÇEKME
// =======================================================

// --- E. EN ÇOK İZLENEN FİLMLER (TOP 5) ---
// Amacımız: Hangi filmden kaç bilet satılmış ve ne kadar kazanılmış?
// 1. COUNT(t.id): O filme ait bilet sayısını bul.
// 2. SUM(s.price): O filmin toplam hasılatını bul.
// 3. GROUP BY f.id: Sonuçları filme göre grupla (Aynı filmleri tek satır yap).
// 4. ORDER BY ticket_count DESC: En çok bilet satanı en üste koy.
// 5. LIMIT 5: Sadece ilk 5 tanesini getir.
$sqlTopFilms = "SELECT f.title, COUNT(t.id) as ticket_count, SUM(s.price) as total_earnings
                FROM tickets t
                JOIN sessions s ON t.session_id = s.id
                JOIN films f ON s.film_id = f.id
                GROUP BY f.id
                ORDER BY ticket_count DESC
                LIMIT 5";
$topFilms = $pdo->query($sqlTopFilms)->fetchAll();

// --- F. SON SATILAN BİLETLER (SON 10 İŞLEM) ---
// Amacımız: Sisteme düşen en son satışları görmek.
// 1. ORDER BY t.id DESC: En son eklenen (ID'si en büyük olan) en üstte olsun.
// 2. LIMIT 10: Sadece son 10 işlemi göster.
$sqlRecentSales = "SELECT t.*, u.username, f.title, s.start_time, s.price, t.verification_code
                   FROM tickets t
                   JOIN users u ON t.user_id = u.id
                   JOIN sessions s ON t.session_id = s.id
                   JOIN films f ON s.film_id = f.id
                   ORDER BY t.id DESC
                   LIMIT 10";
$recentSales = $pdo->query($sqlRecentSales)->fetchAll();
?>

<style>
    /* Kartların düzeni (4'lü yan yana) */
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    /* Tekil Kart Tasarımı */
    .card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.3s;
    }
    .card:hover { transform: translateY(-5px); } /* Hover Efekti */
    
    .card-info h3 { margin: 0; font-size: 2rem; color: #333; }
    .card-info p { margin: 0; color: #777; font-size: 0.9rem; }
    
    .card-icon { font-size: 2.5rem; opacity: 0.3; } /* Silik İkon */
    
    /* Tabloların Düzeni (Yan Yana) */
    .dashboard-tables {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 30px;
    }
    
    /* Tablo Kutusu */
    .table-box {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        overflow-x: auto; /* Tablo taşarsa kaydır */
    }
    .table-box h3 { margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    
    /* Tablo İçeriği */
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
    th { color: #555; font-weight: 600; background-color: #f9f9f9; }
    
    /* Renk Yardımcıları */
    .text-green { color: #2ecc71; font-weight: bold; }
    .bg-green { background-color: #eafaf1; color: #2ecc71; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; }
</style>

<h1>Yönetim Paneli</h1>
<p style="margin-bottom: 30px; color: #666;">Sinema sisteminizin genel durumunu buradan takip edebilirsiniz.</p>

<div class="dashboard-cards">
    <div class="card">
        <div class="card-info">
            <h3><?php echo $totalTickets ?: 0; ?></h3>
            <p>Satılan Bilet</p>
        </div>
        <div class="card-icon" style="color: #3498db;"><i class="fas fa-ticket-alt"></i></div>
    </div>
    
    <div class="card">
        <div class="card-info">
            <h3 class="text-green"><?php echo number_format($totalRevenue ?: 0, 2); ?> ₺</h3>
            <p>Toplam Hasılat</p>
        </div>
        <div class="card-icon" style="color: #2ecc71;"><i class="fas fa-money-bill-wave"></i></div>
    </div>
    
    <div class="card">
        <div class="card-info">
            <h3><?php echo $totalFilms; ?></h3>
            <p>Aktif Film</p>
        </div>
        <div class="card-icon" style="color: #e74c3c;"><i class="fas fa-film"></i></div>
    </div>
    
    <div class="card">
        <div class="card-info">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Kayıtlı Kullanıcı</p>
        </div>
        <div class="card-icon" style="color: #f1c40f;"><i class="fas fa-users"></i></div>
    </div>
</div>


<div class="dashboard-tables">
    
    <div class="table-box">
        <h3>🏆 En Çok İzlenen Filmler</h3>
        <table>
            <thead>
                <tr>
                    <th>Film Adı</th>
                    <th>Satılan Bilet</th>
                    <th>Toplam Kazanç</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php foreach($topFilms as $film): ?>
                <tr>
                    <td><strong><?php echo $film['title']; ?></strong></td>
                    <td><?php echo $film['ticket_count']; ?> Adet</td>
                    <td class="text-green"><?php echo number_format($film['total_earnings'], 2); ?> ₺</td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($topFilms) == 0): ?>
                    <tr><td colspan="3" style="text-align:center; padding:20px; color:#999;">Henüz veri yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-box">
        <h3>🕒 Son Satılan Biletler</h3>
        <table>
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Film</th>
                    <th>Koltuk</th>
                    <th>Kod / QR</th>
                    <th>Tutar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentSales as $sale): ?>
                <tr>
                    <td><?php echo $sale['username']; ?></td>
                    <td><?php echo $sale['title']; ?></td>
                    
                    <td>
                        <span style="background:#3498db; color:white; padding:2px 6px; border-radius:4px; font-size:0.8rem;">
                            <?php echo $sale['seat_number']; ?>
                        </span>
                    </td>
                    
                    <td>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <code style="background:#f1f2f6; color:#e74c3c; padding:2px 5px; border-radius:3px; font-size:0.8rem;">
                                <?php echo $sale['verification_code']; ?>
                            </code>
                            <a href="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $sale['verification_code']; ?>" target="_blank" title="QR Kodu Gör">
                                <i class="fas fa-qrcode" style="color:#333;"></i>
                            </a>
                        </div>
                    </td>
                    
                    <td><span class="bg-green">+<?php echo $sale['price']; ?> ₺</span></td>
                </tr>
                <?php endforeach; ?>

                <?php if(count($recentSales) == 0): ?>
                    <tr><td colspan="5" style="text-align:center; padding:20px; color:#999;">Henüz işlem yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

