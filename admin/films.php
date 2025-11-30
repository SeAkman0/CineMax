<?php 
include 'header.php'; 

// Film Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $poster = $_POST['poster_url'];
    $duration = $_POST['duration'];

    $sql = "INSERT INTO films (title, description, poster_url, duration) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $desc, $poster, $duration]);
    
    // Sayfayı yenile ki liste güncellensin
    header("Location: films.php");
    exit;
}

// Filmleri Listeleme Sorgusu
$films = $pdo->query("SELECT * FROM films ORDER BY id DESC")->fetchAll();
?>

    <h1>Film Yönetimi</h1>

    <div style="background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 5px;">
        <h3>Yeni Film Ekle</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Film Adı</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Afiş URL (Resim Linki)</label>
                <input type="text" name="poster_url" placeholder="https://ornek.com/resim.jpg" required>
            </div>
            <div class="form-group">
                <label>Süre (Dakika)</label>
                <input type="number" name="duration" required>
            </div>
            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="description" rows="3" style="width:100%; border:1px solid #ddd;"></textarea>
            </div>
            <button type="submit" class="btn btn-sm">Kaydet</button>
        </form>
    </div>

    <hr>

    <h3>Mevcut Filmler</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Afiş</th>
                <th>Film Adı</th>
                <th>Süre</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($films as $film): ?>
            <tr>
                <td><?php echo $film['id']; ?></td>
                <td><img src="<?php echo $film['poster_url']; ?>" width="50"></td>
                <td><?php echo $film['title']; ?></td>
                <td><?php echo $film['duration']; ?> dk</td>
                <td>
                    <a href="film-sil.php?id=<?php echo $film['id']; ?>" 
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Bu filmi silmek istediğinize emin misiniz?')">Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
</body>
</html>