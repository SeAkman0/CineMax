<?php
include 'config/db.php';
session_start();

// 1. Giriş Kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. POST ile Bilet ID gelmiş mi?
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_id'])) {
    
    $ticket_id = $_POST['ticket_id'];
    $user_id = $_SESSION['user_id'];

    // 3. Güvenlik ve Silme İşlemi
    // Sadece ID'si eşleşen VE sahibi şu anki kullanıcı olan bileti sil
    $sql = "DELETE FROM tickets WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$ticket_id, $user_id]);

    if ($result && $stmt->rowCount() > 0) {
        // Başarıyla silindi
        header("Location: biletlerim.php?msg=deleted");
    } else {
        // Silinemedi
        header("Location: biletlerim.php?msg=error");
    }

} else {
    // Doğrudan bu sayfaya girmeye çalışırsa at
    header("Location: biletlerim.php");
}
?>