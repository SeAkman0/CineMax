<?php 
include 'header.php'; 

// --- 1. DURUM GÜNCELLEME İŞLEMİ (YENİ) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $film_id = $_POST['film_id'];
    $new_status = $_POST['new_status']; // 1: Aktif, 0: Pasif

    $sql = "UPDATE films SET is_active = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $film_id]);
    
    echo "<script>window.location.href='films.php?msg=updated';</script>";
    exit;
}

// --- 2. FİLM EKLEME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_film'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $poster = $_POST['poster_url'];
    $duration = $_POST['duration'];

    if (!empty($title) && !empty($duration)) {
        // Yeni eklenen film varsayılan olarak Aktif (1) olsun
        $sql = "INSERT INTO films (title, description, poster_url, duration, is_active) VALUES (?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $desc, $poster, $duration]);
        
        echo "<script>window.location.href='films.php';</script>";
        exit;
    }
}

// --- 3. SIRALAMA VE LİSTELEME ---
$sortable_columns = [
    'id' => 'id',
    'title' => 'title',
    'duration' => 'duration',
    'status' => 'is_active' // Yeni sıralama kriteri
];

$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

$sql = "SELECT * FROM films ORDER BY " . $sortable_columns[$sort] . " " . $dir;
$stmt = $pdo->query($sql);
$films = $stmt->fetchAll();

// --- YARDIMCI FONKSİYON ---
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

<h1>Film Yönetimi</h1>
<p style="color:#666; margin-bottom:20px;">Sistemdeki filmleri buradan ekleyebilir, silebilir veya vizyon durumunu değiştirebilirsiniz.</p>

<?php if(isset($_GET['msg']) && $_GET['msg']=='updated'): ?>
    <div style="background:#3498db; color:white; padding:10px; border-radius:5px; margin-bottom:20px;">
        <i class="fas fa-check-circle"></i> Film durumu güncellendi.
    </div>
<?php endif; ?>

<div style="display: flex; gap: 30px; flex-wrap: wrap;">

    <div style="flex: 1; min-width: 300px;">
        <div class="table-box" style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">
                <i class="fas fa-plus-circle" style="color:#2ecc71;"></i> Yeni Film Ekle
            </h3>
            
            <form method="POST" action="">
                <input type="hidden" name="add_film" value="1">

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Film Adı</label>
                    <input type="text" name="title" required 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Afiş URL</label>
                    <input type="text" name="poster_url" placeholder="https://..." required 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Süre (Dakika)</label>
                    <input type="number" name="duration" required 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Açıklama</label>
                    <textarea name="description" rows="3" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"></textarea>
                </div>

                <button type="submit" class="btn btn-sm" style="background:#2ecc71; color:white; border:none; padding:10px 20px; font-size:1rem; cursor:pointer; width:100%; margin-top:10px;">
                    Kaydet
                </button>
            </form>
        </div>
    </div>

    <div style="flex: 2; min-width: 500px;"> <div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Mevcut Filmler</h3>
            
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                        <th style="padding:12px;"><?php echo createSortLink('id', 'ID', $sort, $dir); ?></th>
                        <th style="padding:12px;">Afiş</th>
                        <th style="padding:12px;"><?php echo createSortLink('title', 'Film Adı', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('duration', 'Süre', $sort, $dir); ?></th>
                        <th style="padding:12px;"><?php echo createSortLink('status', 'Durum', $sort, $dir); ?></th> <th style="padding:12px; text-align:left;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($films as $film): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;">#<?php echo $film['id']; ?></td>
                        
                        <td style="padding:12px;">
                            <img src="<?php echo $film['poster_url']; ?>" width="40" height="60" style="object-fit:cover; border-radius:4px;">
                        </td>
                        
                        <td style="padding:12px;"><strong><?php echo $film['title']; ?></strong></td>
                        
                        <td style="padding:12px; color:#555;">
                            <i class="fas fa-clock" style="color:#1e90ff;"></i> <?php echo $film['duration']; ?> dk
                        </td>
                        
                        <td style="padding:12px;">
                            <form method="POST" action="">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="film_id" value="<?php echo $film['id']; ?>">
                                
                                <?php 
                                    $is_active = $film['is_active'] == 1;
                                    $style = $is_active 
                                        ? "background:#eafaf1; color:#2ecc71; border:1px solid #2ecc71;" 
                                        : "background:#fbeaea; color:#e74c3c; border:1px solid #e74c3c;";
                                ?>

                                <select name="new_status" onchange="this.form.submit()" 
                                        style="padding:5px; border-radius:5px; font-weight:bold; cursor:pointer; <?php echo $style; ?>">
                                    <option value="1" <?php echo $is_active ? 'selected' : ''; ?>>Yayında</option>
                                    <option value="0" <?php echo !$is_active ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </form>
                        </td>
                        
                        <td style="padding:12px;">
                            <a href="film-sil.php?id=<?php echo $film['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               style="background:#ff4757; color:white; text-decoration:none; padding:5px 10px; border-radius:5px;"
                               onclick="return confirm('Bu filmi silmek istediğinize emin misiniz? Tüm seansları da silinecektir.')">
                               <i class="fas fa-trash"></i> Sil
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(count($films) == 0): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">Henüz film eklenmemiş.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</div> </body>
</html>