<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİLİKLER
// =======================================================

// Admin paneli menüsünü ve veritabanı bağlantısını dahil ediyoruz.
include 'header.php'; 


// =======================================================
//  2. SIRALAMA (SORTING) MANTIĞI
// =======================================================

// --- A. GÜVENLİK (WHITELIST) ---
// URL'den gelen 'sort' parametresini direkt SQL'e yazarsak SQL Injection açığı oluşur.
// Bu yüzden sadece izin verdiğimiz sütun isimlerini bir listeye (array) koyuyoruz.
$allowed_columns = ['id', 'username', 'email', 'role', 'created_at'];

// --- B. PARAMETRELERİ ALMA ---
// URL'de ?sort=username var mı? Ve bu değer izin verilenler listesinde mi?
// Evetse o değeri al, yoksa varsayılan olarak 'id'ye göre sırala.
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_columns) ? $_GET['sort'] : 'id';

// URL'de ?dir=ASC var mı?
// Evetse 'ASC' (Artan), yoksa varsayılan olarak 'DESC' (Azalan) kullan.
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

// --- C. YÖN DEĞİŞTİRME (TOGGLE) ---
// Kullanıcı başlığa tıkladığında, mevcut sıralamanın tersini yapmak istiyoruz.
// Şu an ASC ise bir sonraki tıklamada DESC olsun.
$new_dir = ($dir == 'ASC') ? 'DESC' : 'ASC';

// --- D. VERİTABANI SORGUSU ---
// Hazırladığımız güvenli değişkenleri sorguya yerleştiriyoruz.
$sql = "SELECT * FROM users ORDER BY $sort $dir";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();


// =======================================================
//  3. YARDIMCI FONKSİYON (GÖRSEL)
// =======================================================

// Bu fonksiyon, tablo başlıklarının yanına küçük ok işaretleri (⬆⬇) koyar.
function sortIcon($column, $current_sort, $current_dir) {
    // Eğer şu an sıralanan sütun buysa:
    if ($column == $current_sort) {
        // Yöne göre yukarı veya aşağı ok göster.
        return $current_dir == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
    }
    // Değilse, pasif (gri) bir sıralama ikonu göster.
    return '<i class="fas fa-sort" style="color:#ccc; opacity:0.5;"></i>'; 
}
?>

<h1>Kullanıcı Yönetimi</h1>
<p style="color:#666; margin-bottom:20px;">
    Sisteme kayıtlı üyeler. Başlıklara tıklayarak sıralayabilirsiniz.
</p>

<div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                
                <th style="padding:12px; text-align:left;">
                    <a href="?sort=id&dir=<?php echo $new_dir; ?>" class="sort-link">
                        ID <?php echo sortIcon('id', $sort, $dir); ?>
                    </a>
                </th>
                
                <th style="padding:12px; text-align:left;">
                    <a href="?sort=username&dir=<?php echo $new_dir; ?>" class="sort-link">
                        Kullanıcı Adı <?php echo sortIcon('username', $sort, $dir); ?>
                    </a>
                </th>
                
                <th style="padding:12px; text-align:left;">
                    <a href="?sort=email&dir=<?php echo $new_dir; ?>" class="sort-link">
                        E-posta <?php echo sortIcon('email', $sort, $dir); ?>
                    </a>
                </th>
                
                <th style="padding:12px; text-align:left;">
                    <a href="?sort=role&dir=<?php echo $new_dir; ?>" class="sort-link">
                        Rol <?php echo sortIcon('role', $sort, $dir); ?>
                    </a>
                </th>
                
                <th style="padding:12px; text-align:left;">
                    <a href="?sort=created_at&dir=<?php echo $new_dir; ?>" class="sort-link">
                        Kayıt Tarihi <?php echo sortIcon('created_at', $sort, $dir); ?>
                    </a>
                </th>
                
                <th style="padding:12px; text-align:left;">İşlem</th>
            </tr>
        </thead>
        <tbody>
            
            <?php foreach($users as $user): ?>
            <tr style="border-bottom:1px solid #eee;">
                
                <td style="padding:12px;">#<?php echo $user['id']; ?></td>
                
                <td style="padding:12px;">
                    <strong><?php echo $user['username']; ?></strong>
                    
                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                        <span style="color:#2ecc71; font-size:0.8rem;">(Sen)</span>
                    <?php endif; ?>
                </td>
                
                <td style="padding:12px; color:#555;"><?php echo $user['email']; ?></td>
                
                <td style="padding:12px;">
                    <?php if($user['role'] == 'admin'): ?>
                        <span style="background:#e74c3c; color:white; padding:3px 8px; border-radius:15px; font-size:0.8rem; font-weight:bold;">Admin</span>
                    <?php else: ?>
                        <span style="background:#3498db; color:white; padding:3px 8px; border-radius:15px; font-size:0.8rem; font-weight:bold;">Üye</span>
                    <?php endif; ?>
                </td>
                
                <td style="padding:12px; color:#666; font-size:0.9rem;">
                    <?php echo date("d.m.Y H:i", strtotime($user['created_at'])); ?>
                </td>
                
                <td style="padding:12px;">
                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                        <a href="delete-user.php?id=<?php echo $user['id']; ?>" 
                           onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');"
                           style="color:#ff4757; text-decoration:none;">
                           <i class="fas fa-trash"></i>
                        </a>
                    <?php else: ?>
                        <span style="color:#ccc;" title="Kendinizi silemezsiniz"><i class="fas fa-lock"></i></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    .sort-link {
        text-decoration: none;
        color: #555;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: 0.2s;
    }
    .sort-link:hover {
        color: #1e90ff;
    }
    .sort-link i {
        font-size: 0.8rem;
    }
</style>

<?php include 'footer.php'; ?>