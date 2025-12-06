<?php
// =======================================================
//  1. BAŞLANGIÇ AYARLARI
// =======================================================

// Çıktı tamponlamasını başlatıyoruz. (Sayfa yönlendirmelerinde hata almamak için)
ob_start();

// Veritabanı bağlantı dosyasını çağırıyoruz ($pdo değişkeni buradan gelir)
include 'config/database.php';

// Sayfanın üst kısmını (Menü, CSS, Session) dahil ediyoruz.
// Böylece kayıt sayfasında da menü görünür olur.
include 'includes/header.php';

$message = ""; // Kullanıcıya göstereceğimiz hata/başarı mesajı.

// Eğer kullanıcı zaten giriş yapmışsa, kayıt sayfasına girmesine gerek yok.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// =======================================================
//  2. FORM GÖNDERME İŞLEMİ (POST)
// =======================================================

// Sayfa "Kayıt Ol" butonuna basılarak mı açıldı?
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- A. VERİ TEMİZLİĞİ ---
    // trim(): Kullanıcının yanlışlıkla başta/sonda bıraktığı boşlukları siler.
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Şifredeki boşluklar bazen bilinçli olabilir, trim yapmıyoruz.

    // --- B. BOŞ ALAN KONTROLÜ (VALIDASYON) ---
    if (empty($username) || empty($email) || empty($password)) {
        $message = "Lütfen tüm alanları doldurun.";
    } else {
        
        // --- C. MÜKERRER KAYIT KONTROLÜ ---
        // Bu e-posta veya kullanıcı adı daha önce alınmış mı?
        // SQL Injection'a karşı 'prepare' kullanıyoruz.
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        // rowCount() > 0 ise veritabanında böyle biri var demektir.
        if ($stmt->rowCount() > 0) {
            $message = "Bu kullanıcı adı veya e-posta zaten kullanımda.";
        } else {
            
            // --- D. ŞİFRE GÜVENLİĞİ (HASHLEME) ---
            // Şifreyi asla düz metin (123456) olarak kaydetmiyoruz.
            // password_hash(): Şifreyi kırılması çok zor bir diziye ($2y$10$...) dönüştürür.
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // --- E. VERİTABANINA KAYIT ---
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            // execute(): Sorguyu çalıştırır ve başarılıysa true döner.
            if ($stmt->execute([$username, $email, $hashed_password])) {
                
                // --- F. BAŞARILI KAYIT ---
                // Kayıt tamamlandı, kullanıcıyı giriş yapması için login sayfasına yönlendiriyoruz.
                // İstersen burada "Kayıt Başarılı" mesajı verip bekletebilirsin ama direkt yönlendirme daha hızlıdır.
                echo "<script>
                        alert('Kayıt başarılı! Lütfen giriş yapınız.');
                        window.location.href = 'login.php';
                      </script>";
                exit;
                
            } else {
                $message = "Kayıt sırasında bir veritabanı hatası oluştu.";
            }
        }
    }
}
?>

<div class="container">
    <div class="form-container">
        
        <h2>Kayıt Ol</h2>
        
        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" placeholder="Kullanıcı adı belirleyin" required>
            </div>
            
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="email" placeholder="ornek@email.com" required>
            </div>
            
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" placeholder="Güçlü bir şifre girin" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width:100%;">Kayıt Ol</button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:0.9rem;">
            Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>