<?php
include 'config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basit doğrulama
    if (empty($username) || empty($email) || empty($password)) {
        $message = "Lütfen tüm alanları doldurun.";
    } else {
        // E-posta veya kullanıcı adı daha önce alınmış mı?
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Bu kullanıcı adı veya e-posta zaten kayıtlı.";
        } else {
            // Şifreyi güvenli hale getir
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Veritabanına ekle
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                // Kayıt başarılı ise login sayfasına yönlendir
                header("Location: login.php");
                exit;
            } else {
                $message = "Bir hata oluştu.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol - Sinema Bilet Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Kayıt Ol</h2>
        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Kayıt Ol</button>
        </form>
        <p style="text-align:center; margin-top:10px;">
            Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
    </div>
</body>
</html>