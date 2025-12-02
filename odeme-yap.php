<?php 
include 'config/db.php'; 
include 'header.php'; 

// Güvenlik: Giriş yapmamışsa login'e at
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// POST ile veri gelmiş mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['selected_seats'])) {
    
    $session_id = $_POST['session_id'];
    $user_id = $_SESSION['user_id'];
    // Gelen veri "1-5,1-6" şeklinde virgüllü stringdir, bunu diziye çeviriyoruz
    $seats = explode(',', $_POST['selected_seats']); 
    
    $success = true;
    $error = "";

    try {
        // Veritabanı işlemini başlat (Transaction)
        $pdo->beginTransaction(); 

        $sql = "INSERT INTO tickets (user_id, session_id, seat_number, verification_code) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        foreach ($seats as $seat) {
            // Önce bu koltuk satılmış mı diye son bir kontrol yapalım (Çakışma önlemek için)
            $check = $pdo->prepare("SELECT id FROM tickets WHERE session_id = ? AND seat_number = ?");
            $check->execute([$session_id, $seat]);
            $unique_code = "CNM-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            if ($check->rowCount() > 0) {
                throw new Exception("Seçtiğiniz $seat numaralı koltuk az önce başkası tarafından alındı.");
            }

            // Satılmamışsa ekle
            $stmt->execute([$user_id, $session_id, $seat, $unique_code]);
        }

        $pdo->commit(); // İşlemi onayla
    } catch (Exception $e) {
        $pdo->rollBack(); // Hata varsa işlemi geri al (Hiçbirini kaydetme)
        $success = false;
        $error = $e->getMessage();
    }
} else {
    // Veri gelmediyse ana sayfaya at
    echo "<script>window.location.href='index.php';</script>";
    exit;
}
?>

<div class="container" style="padding: 100px 20px; text-align: center;">
    <?php if ($success): ?>
        <div style="background: white; padding: 50px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;">
            <div style="width: 80px; height: 80px; background: #2ecc71; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-check" style="font-size: 40px; color: white;"></i>
            </div>
            
            <h1 style="color: #333; margin-bottom: 15px;">İşlem Başarılı!</h1>
            <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">
                Biletleriniz başarıyla oluşturuldu. İyi seyirler dileriz.
            </p>
            
            <div style="display: flex; justify-content: center; gap: 15px;">
                <a href="biletlerim.php" class="btn btn-primary">Biletlerimi Gör</a>
                <a href="index.php" class="btn btn-secondary" style="color:#1e90ff; border-color:#1e90ff;">Ana Sayfa</a>
            </div>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 50px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;">
             <div style="width: 80px; height: 80px; background: #ff4757; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-times" style="font-size: 40px; color: white;"></i>
            </div>
            <h1 style="color: #333; margin-bottom: 15px;">Bir Sorun Oluştu</h1>
            <p style="color: #666; margin-bottom: 30px;"><?php echo $error; ?></p>
            <a href="index.php" class="btn btn-primary">Tekrar Dene</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>