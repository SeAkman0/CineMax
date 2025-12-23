<?php
// =======================================================
//  1. BAŞLANGIÇ AYARLARI
// =======================================================

ob_start();
include 'config/database.php';
include 'includes/header.php';

$message = ""; 

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// =======================================================
//  2. FORM GÖNDERME İŞLEMİ (POST)
// =======================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; 

    // --- B. VALIDASYON KONTROLLERİ ---
    
    // 1. Boş alan kontrolü
    if (empty($username) || empty($email) || empty($password)) {
        $message = "Lütfen tüm alanları doldurun.";
    } 
    // 2. ŞİFRE UZUNLUK KONTROLÜ (YENİ EKLENEN KISIM)
    elseif (strlen($password) < 8) {
        $message = "Şifreniz en az 8 karakter olmalıdır.";
    }
    else {
        
        // --- C. MÜKERRER KAYIT KONTROLÜ ---
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Bu kullanıcı adı veya e-posta zaten kullanımda.";
        } else {
            
            // --- D. ŞİFRE GÜVENLİĞİ VE KAYIT ---
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
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
                <input type="password" name="password" placeholder="En az 8 karakter girin" minlength="8" required>
                <small style="color:#666; font-size:12px;">Minimum 8 karakter olmalıdır.</small>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width:100%;">Kayıt Ol</button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:0.9rem;">
            Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>