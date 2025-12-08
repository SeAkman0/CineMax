<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİLİKLER
// =======================================================

// Admin paneli üst menüsünü (header) dahil ediyoruz.
// Bu dosyanın içinde oturum kontrolü, veritabanı bağlantısı vb. var.
include 'header.php'; 


// =======================================================
//  2. SEANS EKLEME İŞLEMİ (POST)
// =======================================================

// Eğer sayfaya bir form (POST) gönderildiyse bu blok çalışır.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Formdan gelen verileri değişkenlere alıyoruz.
    $film_id = $_POST['film_id'];       // Hangi film?
    $hall_id = $_POST['hall_id'];       // Hangi salon?
    $start_time = $_POST['start_time']; // Tarih ve Saat
    $price = $_POST['price'];           // Bilet Ücreti

    // Veritabanına Kayıt Sorgusu
    // (Prepare kullanarak SQL Injection saldırılarını önlüyoruz)
    $sql = "INSERT INTO sessions (film_id, hall_id, start_time, price) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // Sorguyu çalıştır
    $stmt->execute([$film_id, $hall_id, $start_time, $price]);
    
    // İşlem bitince sayfayı yenile (Formun tekrar gönderilmesini önlemek için)
    echo "<script>window.location.href='showtimes.php';</script>";
    exit; // Kodun devamını çalıştırma
}


// =======================================================
//  3. VERİLERİ ÇEKME (DROPDOWN MENÜLER İÇİN)
// =======================================================

// A. FİLMLERİ ÇEKME
// Sadece vizyonda olan (is_active = 1) filmleri listeliyoruz.
// İsim sırasına göre (A-Z) getiriyoruz ki admin kolay bulsun.
$films = $pdo->query("SELECT * FROM films WHERE is_active = 1 ORDER BY title ASC")->fetchAll();

// B. SALONLARI ÇEKME
// Tüm salonları isim sırasına göre getiriyoruz.
$halls = $pdo->query("SELECT * FROM halls ORDER BY name ASC")->fetchAll();


// =======================================================
//  4. SIRALAMA (SORTING) MANTIĞI
// =======================================================

// İzin verilen sıralama sütunları (Whitelist - Güvenlik İçin)
// URL'deki 'sort' parametresine göre SQL'de hangi sütunu kullanacağımızı belirliyoruz.
$sortable_columns = [
    'date' => 'sessions.start_time', // Tarihe göre
    'film' => 'films.title',         // Film adına göre
    'hall' => 'halls.name',          // Salon adına göre
    'price' => 'sessions.price'      // Fiyata göre
];

// URL'den gelen parametreleri al (Yoksa varsayılanları kullan)
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'date';
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

// SEANSLARI LİSTELEME SORGUSU
// Seans tablosunda sadece film_id ve hall_id var (Sayılar).
// Bize Film Adı ve Salon Adı lazım olduğu için JOIN ile diğer tablolara bağlanıyoruz.
$sql = "SELECT sessions.*, films.title as film_name, halls.name as hall_name 
        FROM sessions 
        JOIN films ON sessions.film_id = films.id 
        JOIN halls ON sessions.hall_id = halls.id 
        ORDER BY " . $sortable_columns[$sort] . " " . $dir;

$stmt = $pdo->query($sql);
$sessions = $stmt->fetchAll();


// --- YARDIMCI FONKSİYON: BAŞLIK LİNKİ OLUŞTURMA ---
// Tablo başlıklarına tıklandığında sıralama yönünü değiştiren link üretir.
function createSortLink($column, $label, $currentSort, $currentDir) {
    // Yön değiştirme mantığı: Şu an ASC ise DESC yap, değilse ASC yap.
    $newDir = ($currentSort == $column && $currentDir == 'ASC') ? 'DESC' : 'ASC';
    
    // İkon belirleme
    $icon = '<i class="fas fa-sort" style="color:#ccc; opacity:0.3; font-size:0.8rem;"></i>';
    if ($currentSort == $column) {
        $icon = ($currentDir == 'ASC') 
            ? '<i class="fas fa-sort-up" style="color:#333;"></i>' 
            : '<i class="fas fa-sort-down" style="color:#333;"></i>';
    }

    return '<a href="?sort='.$column.'&dir='.$newDir.'" style="text-decoration:none; color:#555; display:flex; align-items:center; gap:5px;">
                '.$label.' '.$icon.'
            </a>';
}
?>

<h1>Seans Yönetimi</h1>
<p style="color:#666; margin-bottom:20px;">Filmlerin gösterim saatlerini ve salonlarını buradan planlayabilirsiniz.</p>

<div style="display: flex; gap: 30px; flex-wrap: wrap;">

    <div style="flex: 1; min-width: 300px;">
        <div class="table-box" style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">
                <i class="fas fa-clock" style="color:#2ecc71;"></i> Yeni Seans Planla
            </h3>
            
            <form method="POST" action="">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Film Seç</label>
                    <select name="film_id" required style="width: 100%; padding: 10px; border:1px solid #ddd; border-radius:5px;">
                        <option value="">Film Seçiniz...</option>
                        <?php foreach ($films as $film): ?>
                            <option value="<?php echo $film['id']; ?>"><?php echo $film['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Salon Seç</label>
                    <select name="hall_id" required style="width: 100%; padding: 10px; border:1px solid #ddd; border-radius:5px;">
                        <option value="">Salon Seçiniz...</option>
                        <?php foreach ($halls as $hall): ?>
                            <option value="<?php echo $hall['id']; ?>"><?php echo $hall['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Tarih ve Saat</label>
                        <input type="datetime-local" name="start_time" required 
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                    
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Ücret (TL)</label>
                        <input type="number" name="price" step="0.50" value="150" required 
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                </div>

                <button type="submit" class="btn btn-sm" style="background:#2ecc71; color:white; border:none; padding:10px 20px; font-size:1rem; cursor:pointer; width:100%; margin-top:15px;">
                    Seans Oluştur
                </button>
            </form>
        </div>
    </div>

    <div style="flex: 2; min-width: 450px;">
        <div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Planlanan Seanslar</h3>
            
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                        <th style="padding:12px;"><?php echo createSortLink('date', 'Tarih/Saat', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('film', 'Film', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('hall', 'Salon', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('price', 'Ücret', $sort, $dir); ?></th>
                        <th style="padding:12px; text-align:left;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        
                        <td style="padding:12px; color:#1e90ff; font-weight:bold;">
                            <?php echo date("d.m.Y H:i", strtotime($session['start_time'])); ?>
                        </td>
                        
                        <td style="padding:12px;"><strong><?php echo $session['film_name']; ?></strong></td>
                        
                        <td style="padding:12px; color:#555;"><?php echo $session['hall_name']; ?></td>
                        
                        <td style="padding:12px;">
                            <span style="background:#eafaf1; color:#2ecc71; padding:3px 8px; border-radius:10px; font-weight:bold; font-size:0.85rem;">
                                <?php echo $session['price']; ?> ₺
                            </span>
                        </td>
                        
                        <td style="padding:12px;">
                            <a href="delete-showtime.php?id=<?php echo $session['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               style="background:#ff4757; color:white; text-decoration:none; padding:5px 10px; border-radius:5px;"
                               onclick="return confirm('Bu seansı silmek istediğinize emin misiniz? Satılan biletler de silinecektir.')">
                               <i class="fas fa-trash"></i> Sil
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(count($sessions) == 0): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px;">Henüz seans planlanmamış.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

