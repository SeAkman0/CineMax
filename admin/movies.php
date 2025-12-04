<?php 
include 'header.php';

// --- 1. DURUM GÜNCELLEME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $film_id = $_POST['film_id'];
    $new_status = $_POST['new_status']; 
    $sql = "UPDATE films SET is_active = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$new_status, $film_id]);
    echo "<script>window.location.href='movies.php?msg=updated';</script>";
    exit;
}

// --- 2. FİLM EKLEME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_film'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $duration = $_POST['duration'];
    $trailer = $_POST['trailer_url']; // Yeni Eklenen Fragman Linki
    
    // Dosya Yükleme İşlemleri
    $target_dir = "../assets/images/posters/";
    $db_path_dir = "assets/images/posters/";
    
    if (isset($_FILES["poster_file"]) && $_FILES["poster_file"]["error"] == 0) {
        
        $file_extension = pathinfo($_FILES["poster_file"]["name"], PATHINFO_EXTENSION);
        $unique_name = time() . "_" . uniqid() . "." . $file_extension;
        
        $target_file = $target_dir . $unique_name;
        $db_save_path = $db_path_dir . $unique_name;

        if (move_uploaded_file($_FILES["poster_file"]["tmp_name"], $target_file)) {
            
            // Veritabanına Kaydet (trailer_url eklendi)
            $sql = "INSERT INTO films (title, description, poster_url, duration, trailer_url, is_active) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $desc, $db_save_path, $duration, $trailer]);
            
            echo "<script>window.location.href='movies.php';</script>";
            exit;
            
        } else {
            echo "<script>alert('Resim yüklenirken hata oluştu.');</script>";
        }
    } else {
        echo "<script>alert('Lütfen bir resim dosyası seçiniz.');</script>";
    }
}

// --- LİSTELEME ---
$sortable_columns = ['id'=>'id', 'title'=>'title', 'duration'=>'duration', 'status'=>'is_active'];
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'id';
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

$sql = "SELECT * FROM films ORDER BY " . $sortable_columns[$sort] . " " . $dir;
$stmt = $pdo->query($sql);
$films = $stmt->fetchAll();

function createSortLink($column, $label, $currentSort, $currentDir) {
    $newDir = ($currentSort == $column && $currentDir == 'ASC') ? 'DESC' : 'ASC';
    $icon = '<i class="fas fa-sort" style="color:#ccc; opacity:0.3; font-size:0.8rem;"></i>';
    if ($currentSort == $column) {
        $icon = ($currentDir == 'ASC') ? '<i class="fas fa-sort-up" style="color:#333;"></i>' : '<i class="fas fa-sort-down" style="color:#333;"></i>';
    }
    return '<a href="?sort='.$column.'&dir='.$newDir.'" style="text-decoration:none; color:#555; display:flex; align-items:center; gap:5px;">'.$label.' '.$icon.'</a>';
}
?>

<h1>Film Yönetimi</h1>
<p style="color:#666; margin-bottom:20px;">Filmleri yönetin ve fragman linklerini ekleyin.</p>

<div style="display: flex; gap: 30px; flex-wrap: wrap;">

    <div style="flex: 1; min-width: 300px;">
        <div class="table-box" style="background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">
                <i class="fas fa-plus-circle" style="color:#2ecc71;"></i> Yeni Film Ekle
            </h3>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="add_film" value="1">

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Film Adı</label>
                    <input type="text" name="title" required 
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                </div>
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Film Afişi</label>
                    <input type="file" name="poster_file" required accept="image/*"
                           style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; background:#f9f9f9;">
                </div>

                <div style="display:flex; gap:10px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Süre (Dk)</label>
                        <input type="number" name="duration" required 
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
                    <div style="flex:2;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Fragman URL (YouTube)</label>
                        <input type="text" name="trailer_url" placeholder="https://youtube.com/..." 
                               style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
                    </div>
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

    <div style="flex: 2; min-width: 500px;">
        <div class="table-box" style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Mevcut Filmler</h3>
            
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #eee;">
                        <th style="padding:12px;"><?php echo createSortLink('id', 'ID', $sort, $dir); ?></th>
                        <th style="padding:12px;">Afiş</th>
                        <th style="padding:12px;"><?php echo createSortLink('title', 'Film Adı', $sort, $dir); ?></th>
                        <th style="padding:12px;">Fragman</th> <th style="padding:12px;"><?php echo createSortLink('status', 'Durum', $sort, $dir); ?></th>
                        <th style="padding:12px; text-align:left;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($films as $film): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;">#<?php echo $film['id']; ?></td>
                        
                        <td style="padding:12px;">
                            <?php $imgSrc = (strpos($film['poster_url'], 'http') === 0) ? $film['poster_url'] : '../' . $film['poster_url']; ?>
                            <img src="<?php echo $imgSrc; ?>" width="40" height="60" style="object-fit:cover; border-radius:4px;">
                        </td>
                        
                        <td style="padding:12px;">
                            <strong><?php echo $film['title']; ?></strong><br>
                            <span style="font-size:0.8rem; color:#888;"><?php echo $film['duration']; ?> dk</span>
                        </td>

                        <td style="padding:12px; text-align:center;">
                            <?php if(!empty($film['trailer_url'])): ?>
                                <a href="<?php echo $film['trailer_url']; ?>" target="_blank" style="color:#e74c3c; font-size:1.2rem;">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            <?php else: ?>
                                <span style="color:#ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding:12px;">
                            <form method="POST" action="">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="film_id" value="<?php echo $film['id']; ?>">
                                <?php 
                                    $is_active = $film['is_active'] == 1;
                                    $style = $is_active ? "background:#eafaf1; color:#2ecc71; border:1px solid #2ecc71;" : "background:#fbeaea; color:#e74c3c; border:1px solid #e74c3c;";
                                ?>
                                <select name="new_status" onchange="this.form.submit()" style="padding:5px; border-radius:5px; font-weight:bold; cursor:pointer; <?php echo $style; ?>">
                                    <option value="1" <?php echo $is_active ? 'selected' : ''; ?>>Yayında</option>
                                    <option value="0" <?php echo !$is_active ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </form>
                        </td>
                        
                        <td style="padding:12px;">
                            <a href="delete-movie.php?id=<?php echo $film['id']; ?>" class="btn btn-sm btn-danger" style="background:#ff4757; color:white; text-decoration:none; padding:5px 10px; border-radius:5px;" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
</body>
</html>