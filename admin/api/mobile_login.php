<?php
include '../../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

// Sadece POST isteği al
$input = json_decode(file_get_contents("php://input"), true);
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Alanları doldurun.']);
    exit;
}

// Kullanıcıyı bul
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    // Giriş Başarılı
    echo json_encode([
        'status' => 'success',
        'message' => 'Giriş Başarılı',
        'user_id' => $user['id'],
        'username' => $user['username']
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Hatalı kullanıcı adı veya şifre!']);
}
?>