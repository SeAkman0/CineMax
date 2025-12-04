<?php
include '../config/database.php';

// Session kontrolü (admin klasöründeki diğer dosyalarda olduğu gibi)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $pdo->beginTransaction(); // İşlemi başlat

        // 1. ÖNCE: Bu seansa ait BİLETLERİ sil
        $stmt1 = $pdo->prepare("DELETE FROM tickets WHERE session_id = ?");
        $stmt1->execute([$id]);

        // 2. SONRA: SEANSI sil
        $stmt2 = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt2->execute([$id]);

        $pdo->commit(); // Onayla
    } catch (Exception $e) {
        $pdo->rollBack(); // Hata varsa geri al
        die("Silme hatası: " . $e->getMessage());
    }
}

header("Location: sessions.php");
exit;
?>