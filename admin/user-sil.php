<?php
include '../config/db.php';
session_start();

// 1. Yetki Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

// 2. ID Geldi mi?
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // 3. KENDİNİ SİLMEYE Mİ ÇALIŞIYOR? (Ekstra güvenlik)
    if ($id == $_SESSION['user_id']) {
        die("Kendinizi silemezsiniz!");
    }

    // 4. Silme İşlemi
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
}

// Listeye geri dön
header("Location: users.php");
exit;
?>