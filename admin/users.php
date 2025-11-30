<?php 
include 'header.php'; 

// --- 1. SIRALAMA MANTIĞI ---

// İzin verilen sütunlar (Güvenlik için şart!)
$allowed_columns = ['id', 'username', 'email', 'role', 'created_at'];

// URL'den gelen 'sort' verisini al, eğer yoksa veya listede yoksa varsayılan 'id' olsun
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_columns) ? $_GET['sort'] : 'id';

// URL'den gelen 'dir' (yön) verisini al, eğer yoksa veya hatalıysa varsayılan 'DESC' olsun
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

// Bir sonraki tıklamada yön ne olsun? (Şu an ASC ise DESC yap, yoksa ASC yap)
$new_dir = ($dir == 'ASC') ? 'DESC' : 'ASC';

// Sorguyu Hazırla (Değişkenleri güvenli şekilde yerleştirdik)
$sql = "SELECT * FROM users ORDER BY $sort $dir";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

// --- OK YARDIMCI FONKSİYONU ---
// Hangi başlığın sıralandığını göstermek için ikon koyar
function sortIcon($column, $current_sort, $current_dir) {
    if ($column == $current_sort) {
        return $current_dir == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
    }
    return '<i class="fas fa-sort" style="color:#ccc; opacity:0.5;"></i>'; // Pasif ikon
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
                        <a href="user-sil.php?id=<?php echo $user['id']; ?>" 
                           onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?');"
                           style="color:#ff4757; text-decoration:none;">
                           <i class="fas fa-trash"></i>
                        </a>
                    <?php else: ?>
                        <span style="color:#ccc;"><i class="fas fa-lock"></i></span>
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

</div>
</body>
</html>