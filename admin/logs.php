<?php 
include 'header.php';

// --- SIRALAMA AYARLARI ---

// İzin verilen sütunlar ve veritabanındaki karşılıkları (Whitelist)
$sortable_columns = [
    'user' => 'u.username',
    'email' => 'u.email',
    'ip' => 'l.ip_address',
    'date' => 'l.login_time'
];

// URL'den gelen veriyi al, yoksa varsayılanı kullan (Varsayılan: Tarih)
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'date';
// Varsayılan yön: DESC (En yeniden eskiye)
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

// Sorguyu Hazırla
$sql = "SELECT l.*, u.username, u.email 
        FROM login_logs l 
        JOIN users u ON l.user_id = u.id 
        ORDER BY " . $sortable_columns[$sort] . " " . $dir . " 
        LIMIT 50";

$logs = $pdo->query($sql)->fetchAll();

// --- YARDIMCI FONKSİYON: BAŞLIK LİNKİ ---
function createSortLink($column, $label, $currentSort, $currentDir) {
    // Yön değiştirme mantığı
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

<h1>Giriş Logları (Son 50)</h1>
<p style="color:#666; margin-bottom:20px;">Sisteme giriş yapan kullanıcıların hareket dökümü. Başlıklara tıklayarak sıralayabilirsiniz.</p>

<div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                <th style="padding:12px;"><?php echo createSortLink('user', 'Kullanıcı', $sort, $dir); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('email', 'E-posta', $sort, $dir); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('ip', 'IP Adresi', $sort, $dir); ?></th>
                <th style="padding:12px;"><?php echo createSortLink('date', 'Tarih & Saat', $sort, $dir); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($logs as $log): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:12px;">
                    <strong style="color:#333;"><?php echo $log['username']; ?></strong>
                    <?php if($log['username'] == 'admin'): ?>
                        <span style="background:#e74c3c; color:white; padding:2px 6px; border-radius:4px; font-size:0.7rem; margin-left:5px;">Admin</span>
                    <?php endif; ?>
                </td>
                <td style="padding:12px; color:#555;"><?php echo $log['email']; ?></td>
                <td style="padding:12px; color:#666; font-family:monospace;"><?php echo $log['ip_address']; ?></td>
                <td style="padding:12px; color:#1e90ff;">
                    <i class="fas fa-clock"></i> <?php echo date("d.m.Y H:i:s", strtotime($log['login_time'])); ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if(count($logs) == 0): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px;">Henüz kayıt bulunmuyor.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div> </body>
</html>