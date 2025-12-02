<?php 
include 'header.php'; 

// --- SEANS EKLEME İŞLEMİ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $film_id = $_POST['film_id'];
    $hall_id = $_POST['hall_id'];
    $start_time = $_POST['start_time'];
    $price = $_POST['price'];

    $sql = "INSERT INTO sessions (film_id, hall_id, start_time, price) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$film_id, $hall_id, $start_time, $price]);
    
    echo "<script>window.location.href='sessions.php';</script>";
    exit;
}

// Dropdown Verileri
$films = $pdo->query("SELECT * FROM films WHERE is_active = 1 ORDER BY title ASC")->fetchAll();
$halls = $pdo->query("SELECT * FROM halls ORDER BY name ASC")->fetchAll();

// --- SIRALAMA MANTIĞI ---

// Hangi başlığa tıklayınca veritabanında nereye göre sıralasın? (Mapping)
$sortable_columns = [
    'date' => 'sessions.start_time',
    'film' => 'films.title', // Film adına göre sırala
    'hall' => 'halls.name',  // Salon adına göre sırala
    'price' => 'sessions.price'
];

// URL'den gelen veriyi al
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortable_columns) ? $_GET['sort'] : 'date';
$dir = isset($_GET['dir']) && strtoupper($_GET['dir']) == 'ASC' ? 'ASC' : 'DESC';

// Sorguyu Hazırla (ORDER BY kısmını dinamik yaptık)
$sql = "SELECT sessions.*, films.title as film_name, halls.name as hall_name 
        FROM sessions 
        JOIN films ON sessions.film_id = films.id 
        JOIN halls ON sessions.hall_id = halls.id 
        ORDER BY " . $sortable_columns[$sort] . " " . $dir;

$stmt = $pdo->query($sql);
$sessions = $stmt->fetchAll();

// --- YARDIMCI FONKSİYON: BAŞLIK LİNKİ ---
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
                            <a href="seans-sil.php?id=<?php echo $session['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               style="background:#ff4757; color:white; text-decoration:none; padding:5px 10px; border-radius:5px;"
                               onclick="return confirm('Silmek istiyor musunuz?')">
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

</div> </body>
</html>