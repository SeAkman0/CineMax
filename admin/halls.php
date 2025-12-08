<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİLİKLER
// =======================================================

// Admin paneli üst menüsünü (header) dahil ediyoruz.
// Oturum açma kontrolü ve veritabanı bağlantısı header içinde yapılıyor.
include 'header.php'; 


// =======================================================
//  2. SALON EKLEME İŞLEMİ (POST)
// =======================================================

// Eğer sayfaya form gönderildiyse (POST metoduyla):
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Formdan gelen verileri alıyoruz
    $name = $_POST['name'];         // Salon Adı (Örn: Salon 1)
    $rows = $_POST['total_rows'];   // Sıra Sayısı
    $cols = $_POST['total_cols'];   // Sütun (Koltuk) Sayısı

    // Basit Doğrulama: Alanlar boş değilse ve sayılar 0'dan büyükse
    if (!empty($name) && $rows > 0 && $cols > 0) {
        
        // Veritabanına Ekleme Sorgusu
        $sql = "INSERT INTO halls (name, total_rows, total_cols) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $rows, $cols]);
        
        // İşlem bittikten sonra sayfayı yenile (Formun tekrar gönderilmesini önlemek için)
        echo "<script>window.location.href='halls.php';</script>";
        exit;
    }
}


// =======================================================
//  3. SIRALAMA (SORTING) MANTIĞI
// =======================================================

// İzin verilen sıralama sütunları (Whitelist - Güvenlik İçin)
// URL'den gelebilecek zararlı SQL kodlarını engellemek için sadece bu sütunlara izin veriyoruz.
$sortable_columns = [
    'id' => 'id',
    'name' => 'name',
    'rows' => 'total_rows',
    'capacity' => '(total_rows * total_cols)' // Kapasiteyi veritabanında hesaplatarak sıralıyoruz
];

// URL'den gelen 'sort' (sütun) ve 'dir' (yön) parametrelerini alıyoruz.
// Eğer parametre yoksa veya geçersizse varsayılan değerleri (id, DESC) kullanıyoruz.
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

// Veritabanı Sorgusunu Hazırla
// (total_rows * total_cols) as capacity: SQL içinde çarpma yaparak kapasiteyi hesaplıyoruz.
$sql = "SELECT *, (total_rows * total_cols) as capacity FROM halls ORDER BY " . $sortable_columns[$sort] . " " . $dir;
$stmt = $pdo->query($sql);
$halls = $stmt->fetchAll(); // Tüm salonları çek


// --- YARDIMCI FONKSİYON: BAŞLIK LİNKİ OLUŞTURMA ---
// Tablo başlıklarına tıklandığında sıralama yönünü değiştiren (ASC <-> DESC) linki üretir.
function createSortLink($column, $label, $currentSort, $currentDir) {
    // Eğer şu an bu sütuna göre sıralanıyorsa ve yön ASC ise, yeni yön DESC olsun (Tersi).
    $newDir = ($currentSort == $column && $currentDir == 'ASC') ? 'DESC' : 'ASC';
    
    // İkon belirleme (Ok işareti)
    $icon = '<i class="fas fa-sort" style="color:#ccc; opacity:0.3; font-size:0.8rem;"></i>'; // Pasif ikon
    
    // Eğer aktif sütun buysa, yönüne göre ok işaretini değiştir
    if ($currentSort == $column) {
        $icon = ($currentDir == 'ASC') 
            ? '<i class="fas fa-sort-up" style="color:#333;"></i>' 
            : '<i class="fas fa-sort-down" style="color:#333;"></i>';
    }

    // HTML Linkini döndür
    return '<a href="?sort='.$column.'&dir='.$newDir.'" style="text-decoration:none; color:#555; display:flex; align-items:center; gap:5px;">
                '.$label.' '.$icon.'
            </a>';
}
?>

<h1>Salon Yönetimi</h1>
<p style="color:#666; margin-bottom:20px;">Sinema salonlarını ve oturma düzenlerini buradan yönetebilirsiniz.</p>

<div style="display: flex; gap: 30px; flex-wrap: wrap;">

    <div style="flex: 1; min-width: 300px;">
        <div class="table-box" style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">
                <i class="fas fa-plus-circle" style="color:#2ecc71;"></i> Yeni Salon Ekle
            </h3>
            
            <form method="POST" action="">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Salon Adı</label>
                    <input type="text" name="name" placeholder="Örn: Salon 3 (IMAX)" required 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>
                
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Sıra Sayısı</label>
                        <input type="number" name="total_rows" placeholder="Örn: 10" min="1" required 
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Sütun (Koltuk)</label>
                        <input type="number" name="total_cols" placeholder="Örn: 15" min="1" required 
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                </div>
                
                <p style="font-size:0.8rem; color:#888; margin-top:5px;">
                    * Oturma düzeni (Grid) otomatik oluşturulacaktır.
                </p>

                <button type="submit" class="btn btn-sm" style="background:#2ecc71; color:white; border:none; padding:10px 20px; font-size:1rem; cursor:pointer; width:100%; margin-top:10px;">
                    Kaydet
                </button>
            </form>
        </div>
    </div>

    <div style="flex: 2; min-width: 400px;">
        <div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Mevcut Salonlar</h3>
            
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                        <th style="padding:12px;"><?php echo createSortLink('id', 'ID', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('name', 'Salon Adı', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('rows', 'Boyutlar', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('capacity', 'Kapasite', $sort, $dir); ?></th>
                        <th style="padding:12px; text-align:left;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($halls as $hall): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;">#<?php echo $hall['id']; ?></td>
                        <td style="padding:12px;"><strong><?php echo $hall['name']; ?></strong></td>
                        
                        <td style="padding:12px; color:#555;">
                            <?php echo $hall['total_rows']; ?> Sıra x <?php echo $hall['total_cols']; ?> Koltuk
                        </td>
                        
                        <td style="padding:12px;">
                            <span style="background:#eafaf1; color:#2ecc71; padding:3px 8px; border-radius:10px; font-weight:bold; font-size:0.85rem;">
                                <?php echo $hall['capacity']; ?> Kişi
                            </span>
                        </td>
                        
                        <td style="padding:12px;">
                            <a href="delete-hall.php?id=<?php echo $hall['id']; ?>" 
                               onclick="return confirm('DİKKAT! Bu salonu silerseniz, bu salondaki TÜM SEANSLAR ve BİLETLER de silinir. Onaylıyor musunuz?');"
                               style="color:#ff4757; text-decoration:none;">
                               <i class="fas fa-trash"></i> Sil
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

