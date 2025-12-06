<?php
// =======================================================
//  1. AYARLAR VE GEREKLİLİKLER
// =======================================================

// Çıktı tamponunu başlatıyoruz. Bu, "header already sent" hatasını önler.
// Sayfa yönlendirmeleri (header location) HTML kodlarından önce yapılmalıdır.
ob_start();

// Veritabanı bağlantı dosyamızı çağırıyoruz ($pdo değişkeni buradan gelir).
include 'config/database.php'; 

// Sayfanın üst kısmını (Menü, CSS dosyaları, Session başlatma) dahil ediyoruz.
// Bu sayede her sayfada tekrar tekrar HTML head kodları yazmamıza gerek kalmaz.
include 'includes/header.php';

// Hata veya bilgi mesajlarını tutacağımız değişken.
$message = "";

// =======================================================
//  2. GİRİŞ YAPILMIŞSA YÖNLENDİRME (BONUS KONTROL)
// =======================================================
// Eğer kullanıcı zaten giriş yapmışsa, tekrar giriş sayfasına girmesine gerek yok.
// Onu direkt ana sayfaya atıyoruz.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// =======================================================
//  3. FORM GÖNDERİLDİ Mİ? (POST İŞLEMİ)
// =======================================================

// Kullanıcı "Giriş Yap" butonuna bastığında veriler POST yöntemiyle gelir.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Formdan gelen verileri alıyoruz.
    // trim(): Kullanıcı yanlışlıkla boşluk bırakmışsa (örn: " ahmet ") temizler.
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // --- A. BOŞ ALAN KONTROLÜ ---
    if (empty($username) || empty($password)) {
        $message = "Lütfen tüm alanları doldurun.";
    } else {
        
        // --- B. VERİTABANI SORGUSU (GÜVENLİK) ---
        // Kullanıcı adını veritabanında arıyoruz.
        // DİKKAT: Burada SQL Injection saldırısını önlemek için 'prepare' kullanıyoruz.
        // Asla değişkeni direkt sorguya yazma! (Örn: "SELECT * FROM users WHERE username = '$username'" YANLIŞTIR)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(); // Eşleşen kullanıcıyı getir.

        // --- C. ŞİFRE DOĞRULAMA (HASH KONTROLÜ) ---
        // 1. $user: Böyle bir kullanıcı var mı?
        // 2. password_verify: Girilen şifre (123456) ile veritabanındaki şifreli halini ($2y$10$...) kıyaslar.
        if ($user && password_verify($password, $user['password'])) {
            
            // --- D. OTURUM (SESSION) BAŞLATMA ---
            // Giriş başarılı! Tarayıcı kapanana kadar bu bilgileri hafızada tut.
            $_SESSION['user_id'] = $user['id'];       // Kullanıcının ID'si (Bilet alırken lazım)
            $_SESSION['username'] = $user['username']; // Ekranda adını yazmak için
            $_SESSION['role'] = $user['role'];         // Yetkisi (admin mi, user mı?)

            // --- E. LOGLAMA (GÜVENLİK KAYDI) ---
            // Kimin, hangi IP'den girdiğini kaydediyoruz. Güvenlik ve denetim için şarttır.
            $user_ip = $_SERVER['REMOTE_ADDR']; // Kullanıcının IP adresi
            $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
            $logStmt->execute([$user['id'], $user_ip]);

            // --- F. YÖNLENDİRME ---
            // Kullanıcı Admin ise Yönetim Paneline, değilse Ana Sayfaya gönder.
            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit; // Yönlendirmeden sonra kodun devam etmemesi için exit şarttır.

        } else {
            // Kullanıcı yoksa veya şifre yanlışsa genel bir hata veriyoruz.
            // Güvenlik ipucu: "Şifre yanlış" veya "Kullanıcı yok" diye ayrı ayrı söyleme.
            // Hackerlara ipucu vermemek için "Kullanıcı adı veya şifre hatalı" de.
            $message = "Kullanıcı adı veya şifre hatalı.";
        }
    }
}
?>

<div class="container">
    <div class="form-container">
        
        <h2>Giriş Yap</h2>
        
        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" required placeholder="Kullanıcı adınız">
            </div>
            
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" required placeholder="Şifreniz">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width:100%;">Giriş Yap</button>
        
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:0.9rem;">
            Hesabın yok mu? <a href="register.php">Hemen Kayıt Ol</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>