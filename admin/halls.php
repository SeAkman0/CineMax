<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİLİKLER
// =======================================================

include 'header.php'; 

// =======================================================
//  2. SALON EKLEME İŞLEMİ (POST)
// =======================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Senin orijinal verilerin
    $name = $_POST['name'];         
    $rows = $_POST['total_rows'];   
    $cols = $_POST['total_cols'];   
    
    // YENİ EKLENEN: Açıklama
    $description = $_POST['description'];

    // YENİ EKLENEN: Resim Yükleme Mantığı
    $uploadDir = '../assets/images/halls/'; // Resim klasörü
    $imagePath = '../assets/images/default-hall.jpg'; // Varsayılan resim

    // Klasör yoksa oluştur (Hata almamak için)
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

    // Dosya seçilmiş mi kontrol et
    if (isset($_FILES['hall_image']) && $_FILES['hall_image']['error'] == 0) {
        $fileName = basename($_FILES['hall_image']['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = "hall_" . time() . "." . $fileType; // Benzersiz isim
        $targetPath = $uploadDir . $newFileName;
        
        $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['hall_image']['tmp_name'], $targetPath)) {
                $imagePath = $newFileName;
            }
        }
    }

    // Basit Doğrulama: Alanlar boş değilse ve sayılar 0'dan büyükse
    if (!empty($name) && $rows > 0 && $cols > 0) {
        
        // Veritabanına Ekleme Sorgusu (GÜNCELLENDİ: description ve image_path eklendi)
        $sql = "INSERT INTO halls (name, total_rows, total_cols, description, image_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $rows, $cols, $description, $imagePath]);
        
        // İşlem bittikten sonra sayfayı yenile
        echo "<script>window.location.href='halls.php';</script>";
        exit;
    }
}


// =======================================================
//  3. SIRALAMA (SORTING) MANTIĞI (SENİN KODUN AYNEN KALDI)
// =======================================================

$sortable_columns = [
    'id' => 'id',
    'name' => 'name',
    'rows' => 'total_rows',
    'capacity' => '(total_rows * total_cols)' 
];

$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

$sql = "SELECT *, (total_rows * total_cols) as capacity FROM halls ORDER BY " . $sortable_columns[$sort] . " " . $dir;
$stmt = $pdo->query($sql);
$halls = $stmt->fetchAll(); 


// YARDIMCI FONKSİYON
function createSortLink($column, $label, $currentSort, $currentDir) {
    $newDir = ($currentSort == $column && $currentDir == 'ASC') ? 'DESC' : 'ASC';
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

<h1>Salon Yönetimi</h1>
<p style="color:#666; margin-bottom:20px;">Sinema salonlarını ve oturma düzenlerini buradan yönetebilirsiniz.</p>

<div style="display: flex; gap: 30px; flex-wrap: wrap; align-items: flex-start;">

    <div style="flex: 1; min-width: 300px;">
        <div class="table-box" style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1); position:sticky; top:20px;">
            
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">
                <i class="fas fa-plus-circle" style="color:#2ecc71;"></i> Yeni Salon Ekle
            </h3>
            
            <form method="POST" action="" enctype="multipart/form-data">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Salon Adı</label>
                    <input type="text" name="name" placeholder="Örn: Salon 3 (IMAX)" required 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Açıklama</label>
                    <textarea name="description" placeholder="Salon özellikleri..." rows="3"
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-family:inherit;"></textarea>
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

                <div style="margin-top:15px; margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Salon Görseli</label>
                    <input type="file" name="hall_image" accept="images/*"
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; background:#f9f9f9;">
                </div>
                
                <p style="font-size:0.8rem; color:#888; margin-top:5px;">
                    * Oturma düzeni (Grid) otomatik oluşturulacaktır.
                </p>

                <button type="submit" class="btn btn-sm" style="background:#2ecc71; color:white; border:none; padding:10px 20px; font-size:1rem; cursor:pointer; width:100%; margin-top:10px; border-radius:5px;">
                    Kaydet
                </button>
            </form>
        </div>
    </div>

    <div style="flex: 2; min-width: 400px;">
        <div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Mevcut Salonlar</h3>
            
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                            <th style="padding:12px; width:60px;">Görsel</th> <th style="padding:12px;"><?php echo createSortLink('id', 'ID', $sort, $dir); ?></th>
                            <th style="padding:12px;"><?php echo createSortLink('name', 'Salon Adı', $sort, $dir); ?></th>
                            <th style="padding:12px;"><?php echo createSortLink('rows', 'Boyutlar', $sort, $dir); ?></th>
                            <th style="padding:12px;"><?php echo createSortLink('capacity', 'Kapasite', $sort, $dir); ?></th>
                            <th style="padding:12px; text-align:left;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($halls as $hall): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            
                            <td style="padding:10px;">
                                <img src="../assets/images/halls/<?php echo !empty($hall['image_path']) ? $hall['image_path'] : '../assets/images/default-hall.jpg'; ?>" 
                                     style="width:50px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #eee;">
                            </td>

                            <td style="padding:12px;">#<?php echo $hall['id']; ?></td>
                            <td style="padding:12px;"><strong><?php echo $hall['name']; ?></strong></td>
                            
                            <td style="padding:12px; color:#555;">
                                <?php echo $hall['total_rows']; ?> Sıra x <?php echo $hall['total_cols']; ?> Koltuk
                            </td>
                            
                            <td style="padding:12px;">
                                <span style="background:#eafaf1; color:#2ecc71; padding:3px 8px; border-radius:10px; font-weight:bold; font-size:0.85rem;">
                                    <?php echo $hall['total_rows'] * $hall['total_cols']; ?> Kişi
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
                        
                        <?php if(count($halls) == 0): ?>
                            <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">Henüz salon yok.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>