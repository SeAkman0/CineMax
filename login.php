<?php
include 'config/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Lütfen tüm alanları doldurun.";
    } else {
        // Kullanıcıyı veritabanında ara
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Kullanıcı var mı ve şifre doğru mu?
        if ($user && password_verify($password, $user['password'])) {
            // Oturum başlat
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            // Giriş logunu kaydet
            $user_ip = $_SERVER['REMOTE_ADDR'];
            $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address) VALUES (?, ?)");
            $logStmt->execute([$user['id'], $user_ip]);

            // Role göre yönlendirme
            if ($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $message = "Kullanıcı adı veya şifre hatalı.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap - Sinema Bilet Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Giriş Yap</h2>
        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Giriş Yap</button>
        </form>
        <p style="text-align:center; margin-top:10px;">
            Hesabın yok mu? <a href="register.php">Kayıt Ol</a>
        </p>
    </div>
</body>
</html>