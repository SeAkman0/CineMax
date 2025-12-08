<?php 
// =======================================================
//  1. AYARLAR VE GEREKLİLİKLER
// =======================================================

// Admin paneli menüsünü ve veritabanı bağlantısını dahil ediyoruz.
// Bu dosyanın içinde session kontrolü ve admin yetki kontrolü zaten var.
include 'header.php'; 

// =======================================================
//  2. BİLET DURUMU GÜNCELLEME (POST İŞLEMİ)
// =======================================================

// Tablodaki "Durum" açılır menüsü (select box) değiştirildiğinde burası çalışır.
// Amacı: Admin'in manuel olarak bileti "Kullanıldı" veya "Aktif" yapmasını sağlamak.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    
    $ticket_id = $_POST['ticket_id']; // Hangi bilet?
    $new_status = $_POST['new_status']; // 0: Aktif, 1: Kullanıldı

    if ($new_status == 1) {
        // Eğer "Kullanıldı" seçildiyse: is_used=1 yap ve şu anki saati kaydet.
        $sql = "UPDATE tickets SET is_used = 1, used_at = NOW() WHERE id = ?";
    } else {
        // Eğer "Aktif" seçildiyse: is_used=0 yap ve giriş saatini temizle (NULL).
        $sql = "UPDATE tickets SET is_used = 0, used_at = NULL WHERE id = ?";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ticket_id]);
    
    // İşlem bitince sayfayı yenile ve başarılı mesajı göster.
    echo "<script>window.location.href='tickets.php?msg=updated';</script>";
    exit;
}


// =======================================================
//  3. SİLME İŞLEMİ (GET İŞLEMİ)
// =======================================================

// URL'de ?delete_id=5 varsa, o ID'li bileti sil.
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->execute([$del_id]);
    
    // İşlem bitince sayfayı yenile ve silindi mesajı göster.
    echo "<script>window.location.href='tickets.php?msg=deleted';</script>";
    exit;
}


// =======================================================
//  4. ARAMA VE SIRALAMA AYARLARI
// =======================================================

// Arama kutusuna bir şey yazılmış mı? (Örn: ?q=ahmet)
$search = isset($_GET['q']) ? $_GET['q'] : '';

// İzin verilen sıralama sütunları (SQL Injection koruması - Whitelist)
// URL'den gelen 'sort' parametresini bu listeyle eşleştiriyoruz.
$sortable_columns = [
    'id' => 't.id',
    'username' => 'u.username',
    'film' => 'f.title',
    'seat' => 't.seat_number',
    'status' => 't.is_used',
    'price' => 's.price',
    'date' => 't.purchase_time'
];

// URL'den sıralama parametrelerini al (Varsayılan: ID'ye göre)
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'id';
// URL'den yön parametresini al (Varsayılan: DESC - Azalan)
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';


// =======================================================
//  5. VERİTABANI SORGUSU (BİLETLERİ ÇEKME)
// =======================================================

// Tüm biletleri listelemek için Tickets tablosunu; 
// Users, Sessions, Films ve Halls tablolarıyla birleştiriyoruz (JOIN).
// Ayrıca filmin vizyon durumunu (f.is_active) da çekiyoruz.
$sql = "SELECT t.*, u.username, u.email, f.title, f.is_active, s.start_time, s.price, h.name as hall_name 
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        JOIN sessions s ON t.session_id = s.id
        JOIN films f ON s.film_id = f.id
        JOIN halls h ON s.hall_id = h.id";

// Eğer arama yapılmışsa WHERE şartı ekle
if ($search) {
    // Kullanıcı adı, Film başlığı veya Bilet kodunda arama yap
    $sql .= " WHERE u.username LIKE :q OR f.title LIKE :q OR t.verification_code LIKE :q";
}

// Sıralama komutunu ekle
$sql .= " ORDER BY " . $sortable_columns[$sort] . " " . $dir;

// Sorguyu hazırla ve çalıştır
$stmt = $pdo->prepare($sql);

if ($search) {
    $stmt->execute(['q' => "%$search%"]);
} else {
    $stmt->execute();
}

$tickets = $stmt->fetchAll();


// --- YARDIMCI FONKSİYON: TIKLANABİLİR BAŞLIK ---
// Tablo başlıklarına tıklandığında sıralama yönünü değiştiren link üretir.
function createSortLink($column, $label, $currentSort, $currentDir, $searchQuery) {
    $newDir = ($currentSort == $column && $currentDir == 'ASC') ? 'DESC' : 'ASC';
    
    $icon = '<i class="fas fa-sort" style="color:#ccc; opacity:0.3; font-size:0.8rem;"></i>';
    if ($currentSort == $column) {
        $icon = ($currentDir == 'ASC') 
            ? '<i class="fas fa-sort-up" style="color:#333;"></i>' 
            : '<i class="fas fa-sort-down" style="color:#333;"></i>';
    }

    return '<a href="?sort='.$column.'&dir='.$newDir.'&q='.htmlspecialchars($searchQuery).'" style="text-decoration:none; color:#555; display:flex; align-items:center; gap:5px;">
                '.$label.' '.$icon.'
            </a>';
}
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h1>Bilet Yönetimi</h1>
    
    <div style="display:flex; gap:10px; align-items:center;">
        
        <a href="create-ticket.php" class="btn" style="background:#2ecc71; color:white; padding:10px 15px; text-decoration:none; border-radius:5px; font-weight:bold; font-size:0.9rem;">
            <i class="fas fa-plus-circle"></i> Bilet Oluştur
        </a>

        <form method="GET" style="display:flex; gap:5px; margin:0;">
            <input type="hidden" name="sort" value="<?php echo $sort; ?>">
            <input type="hidden" name="dir" value="<?php echo $dir; ?>">
            
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Kod, Film veya Kullanıcı..." 
                   style="padding:10px; border:1px solid #ddd; border-radius:5px; width:200px;">
            
            <button type="submit" class="btn btn-primary" style="background:#3498db; color:white; border:none; padding:0 15px; border-radius:5px; cursor:pointer;">
                <i class="fas fa-search"></i>
            </button>
            
            <?php if($search): ?>
                <a href="tickets.php" class="btn" style="background:#95a5a6; color:white; padding:10px; text-decoration:none; border-radius:5px;">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg']=='deleted'): ?>
        <div style="background:#e74c3c; color:white; padding:10px; border-radius:5px; margin-bottom:20px;">
            <i class="fas fa-trash"></i> Bilet başarıyla silindi.
        </div>
    <?php elseif($_GET['msg']=='updated'): ?>
        <div style="background:#3498db; color:white; padding:10px; border-radius:5px; margin-bottom:20px;">
            <i class="fas fa-sync-alt"></i> Bilet durumu güncellendi.
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                <th style="padding:12px;"><?php echo createSortLink('id', 'ID', $sort, $dir, $search); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('username', 'Kullanıcı', $sort, $dir, $search); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('film', 'Film Bilgisi', $sort, $dir, $search); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('seat', 'Koltuk', $sort, $dir, $search); ?></th>
                <th style="padding:12px;">Kod / QR</th>
                <th style="padding:12px;"><?php echo createSortLink('status', 'Durum', $sort, $dir, $search); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('price', 'Tutar', $sort, $dir, $search); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('date', 'Satış Tarihi', $sort, $dir, $search); ?></th>
                <th style="padding:12px;">İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($tickets as $ticket): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:12px;">#<?php echo $ticket['id']; ?></td>
                
                <td style="padding:12px;">
                    <strong><?php echo $ticket['username']; ?></strong><br>
                    <span style="font-size:0.8rem; color:#888;"><?php echo $ticket['email']; ?></span>
                </td>
                
                <td style="padding:12px;">
                    <div style="font-weight:bold; color:#333;"><?php echo $ticket['title']; ?></div>
                    <div style="font-size:0.85rem; color:#666;">
                        <?php echo $ticket['hall_name']; ?> - <?php echo date("d.m H:i", strtotime($ticket['start_time'])); ?>
                    </div>
                </td>
                
                <td style="padding:12px;">
                    <span style="background:#3498db; color:white; padding:3px 8px; border-radius:4px; font-weight:bold;">
                        <?php echo $ticket['seat_number']; ?>
                    </span>
                </td>
                
                <td style="padding:12px;">
                    <div style="display:flex; align-items:center; gap:5px;">
                        <code style="background:#f1f2f6; color:#e74c3c; padding:2px 5px; border-radius:3px; font-size:0.85rem;">
                            <?php echo $ticket['verification_code']; ?>
                        </code>
                        <a href="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $ticket['verification_code']; ?>" target="_blank" title="QR Göster">
                            <i class="fas fa-qrcode" style="color:#333;"></i>
                        </a>
                    </div>
                </td>

                <td style="padding:12px;">
                    <?php 
                        $simdi = time();
                        $seans_zamani = strtotime($ticket['start_time']);
                        $durum = 0; // Varsayılan: Aktif

                        // Öncelik Sırasına Göre Kontrol
                        if ($ticket['is_active'] == 0) $durum = 2; // Vizyondan Kalktı (Kırmızı)
                        elseif ($ticket['is_used'] == 1) $durum = 1; // Kullanıldı (Gri)
                        elseif ($seans_zamani < $simdi) $durum = 3; // Süresi Doldu (Turuncu)
                        else $durum = 0; // Aktif (Yeşil)
                    ?>

                    <?php if($durum == 2): ?>
                        <span style="background:#e74c3c; color:white; padding:5px 10px; border-radius:5px; font-size:0.8rem; display:block; text-align:center;">
                            Vizyondan Kalktı
                        </span>
                    
                    <?php elseif($durum == 3): ?> 
                        <span style="background:#d35400; color:white; padding:5px 10px; border-radius:5px; font-size:0.8rem; display:block; text-align:center;">
                            <i class="fas fa-clock"></i> Süresi Doldu
                        </span>

                    <?php elseif($durum == 1): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <input type="hidden" name="update_status" value="1"> <select name="new_status" onchange="this.form.submit()" style="background:#ecf0f1; color:#7f8c8d; border:1px solid #bdc3c7; padding:5px; border-radius:5px; width:100%; cursor:pointer;">
                                <option value="1" selected>⚫ Kullanıldı</option>
                                <option value="0">🟢 Aktif Yap</option>
                            </select>
                        </form>
                        <div style="font-size:0.7rem; color:#999; margin-top:3px; text-align:center;">
                            <?php echo date("H:i", strtotime($ticket['used_at'])); ?>
                        </div>

                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="new_status" onchange="this.form.submit()" style="background:#eafaf1; color:#2ecc71; border:1px solid #2ecc71; padding:5px; border-radius:5px; width:100%; cursor:pointer;">
                                <option value="0" selected>🟢 Aktif</option>
                                <option value="1">⚫ Kullanıldı Yap</option>
                            </select>
                        </form>
                    <?php endif; ?>
                </td>
                
                <td style="padding:12px; font-weight:bold; color:#2ecc71;">
                    <?php echo $ticket['price']; ?> ₺
                </td>
                
                <td style="padding:12px; font-size:0.9rem; color:#666;">
                    <?php echo date("d.m.Y H:i", strtotime($ticket['purchase_time'])); ?>
                </td>
                
                <td style="padding:12px;">
                    <a href="tickets.php?delete_id=<?php echo $ticket['id']; ?>" 
                       onclick="return confirm('Bu bileti silmek istediğinize emin misiniz?');"
                       style="color:#ff4757; font-size:1.1rem;" title="Bileti Sil">
                       <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($tickets) == 0): ?>
                <tr><td colspan="9" style="text-align:center; padding:20px; color:#999;">Kayıt bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

