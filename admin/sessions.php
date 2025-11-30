<?php 
include 'header.php'; 

// Seans Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $film_id = $_POST['film_id'];
    $hall_id = $_POST['hall_id'];
    $start_time = $_POST['start_time'];
    $price = $_POST['price'];

    // Basit çakışma kontrolü yapılabilir ama şimdilik direkt ekliyoruz
    $sql = "INSERT INTO sessions (film_id, hall_id, start_time, price) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$film_id, $hall_id, $start_time, $price]);
    
    header("Location: sessions.php");
    exit;
}

// Verileri Çekme (Dropdownlar için)
$films = $pdo->query("SELECT * FROM films WHERE is_active = 1")->fetchAll();
$halls = $pdo->query("SELECT * FROM halls")->fetchAll();

// Mevcut Seansları Listeleme (JOIN işlemi ile film ve salon adlarını alıyoruz)
// sessions tablosundaki film_id'yi films tablosuyla eşleştirip adını getiriyoruz.
$sql = "SELECT sessions.*, films.title as film_name, halls.name as hall_name 
        FROM sessions 
        JOIN films ON sessions.film_id = films.id 
        JOIN halls ON sessions.hall_id = halls.id 
        ORDER BY sessions.start_time DESC";
$sessions = $pdo->query($sql)->fetchAll();
?>

    <h1>Seans Yönetimi</h1>

    <div style="background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 5px;">
        <h3>Yeni Seans Oluştur</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Film Seç</label>
                <select name="film_id" required style="width: 100%; padding: 10px;">
                    <option value="">Film Seçiniz...</option>
                    <?php foreach ($films as $film): ?>
                        <option value="<?php echo $film['id']; ?>"><?php echo $film['title']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Salon Seç</label>
                <select name="hall_id" required style="width: 100%; padding: 10px;">
                    <?php foreach ($halls as $hall): ?>
                        <option value="<?php echo $hall['id']; ?>"><?php echo $hall['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Tarih ve Saat</label>
                <input type="datetime-local" name="start_time" required>
            </div>

            <div class="form-group">
                <label>Bilet Ücreti (TL)</label>
                <input type="number" name="price" step="0.50" value="150" required>
            </div>

            <button type="submit" class="btn btn-sm">Seans Oluştur</button>
        </form>
    </div>

    <hr>

    <h3>Planlanan Seanslar</h3>
    <table>
        <thead>
            <tr>
                <th>Tarih/Saat</th>
                <th>Film</th>
                <th>Salon</th>
                <th>Ücret</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $session): ?>
            <tr>
                <td><?php echo date("d.m.Y H:i", strtotime($session['start_time'])); ?></td>
                <td><?php echo $session['film_name']; ?></td>
                <td><?php echo $session['hall_name']; ?></td>
                <td><?php echo $session['price']; ?> TL</td>
                <td>
                    <a href="seans-sil.php?id=<?php echo $session['id']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Silmek istiyor musunuz?')">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
</body>
</html>