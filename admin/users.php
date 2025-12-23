<?php
// =======================================================
//  1. ROL GÜNCELLEME İŞLEMİ (SAYFANIN EN BAŞI)
// =======================================================
// Veritabanı bağlantısı header.php içinde olduğu için,
// önce veritabanını manuel dahil etmemiz veya header'dan sonra işlem yapmamız gerekir.
// Ancak header.php'de ob_start() olduğu için header'dan sonra da yönlendirme yapabiliriz.

require_once 'header.php'; // Senin attığın admin/header.php dosyasını çağırır.

// NOT: header.php içinde veritabanı bağlantısı ($pdo) zaten yapılmış durumda.

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $target_user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    // GÜVENLİK: Admin kendi rolünü değiştirememeli
    if ($target_user_id == $_SESSION['user_id']) {
        echo "<script>alert('Güvenlik: Kendi rolünüzü değiştiremezsiniz!');</script>";
    } else {
        // Veritabanını güncelle
        $updateSql = "UPDATE users SET role = ? WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $result = $updateStmt->execute([$new_role, $target_user_id]);

        if ($result) {
            // Başarılıysa sayfayı yenile
            echo "<script>window.location.href = 'users.php';</script>";
            exit;
        } else {
             echo "<script>alert('Güncelleme başarısız oldu!');</script>";
        }
    }
}

// =======================================================
//  2. KULLANICI LİSTELEME VE SIRALAMA
// =======================================================

$allowed_columns = ['id', 'username', 'email', 'role', 'created_at'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_columns) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';
$new_dir = ($dir == 'ASC') ? 'DESC' : 'ASC';

$sql = "SELECT * FROM users ORDER BY $sort $dir";
$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

// Yardımcı Fonksiyon: Sıralama Okları
function sortIcon($column, $current_sort, $current_dir) {
    if ($column == $current_sort) {
        return $current_dir == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
    }
    return '<i class="fas fa-sort" style="color:#ccc; opacity:0.5;"></i>'; 
}
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h1>Kullanıcı Yönetimi</h1>
    <span style="background:#e9ecef; padding:5px 10px; border-radius:5px; font-size:0.9rem;">
        Toplam: <strong><?php echo count($users); ?></strong> Üye
    </span>
</div>

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
                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                        <span style="color:#2ecc71; font-size:0.8rem;">(Sen)</span>
                    <?php endif; ?>
                </td>
                
                <td style="padding:12px; color:#555;"><?php echo htmlspecialchars($user['email']); ?></td>
                
                <td style="padding:12px;">
                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                        <span style="background:#e74c3c; color:white; padding:5px 10px; border-radius:4px; font-size:0.8rem; font-weight:bold;">
                            YÖNETİCİ (SİZ)
                        </span>
                    <?php else: ?>
                        <form method="POST" style="display:flex; align-items:center; gap:5px; margin:0;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="update_role" value="1">

                            <select name="role" style="padding:5px; border:1px solid #ddd; border-radius:4px; font-size:0.9rem; outline:none; cursor:pointer;">
                                <option value="member" <?php echo $user['role'] == 'member' ? 'selected' : ''; ?>>Üye</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>

                            <button type="submit" style="background:#2ecc71; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer;" title="Kaydet">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
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
                        <span style="color:#ccc;"><i class="fas fa-lock"></i></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    .sort-link { text-decoration: none; color: #555; display: flex; align-items: center; gap: 5px; transition: 0.2s; }
    .sort-link:hover { color: #1e90ff; }
</style>

</div> 
</body>
</html>