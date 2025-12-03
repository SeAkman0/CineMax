<?php
include '../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Yetki Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

// Silme İşlemi
if (isset($_GET['id'])) {
    $hall_id = $_GET['id'];

    try {
        $pdo->beginTransaction(); // İşlemi başlat

        // 1. ADIM: Bu salona ait Seans ID'lerini bul
        $stmt = $pdo->prepare("SELECT id FROM sessions WHERE hall_id = ?");
        $stmt->execute([$hall_id]);
        $session_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Eğer bu salonda seanslar varsa temizlik başlasın
        if (!empty($session_ids)) {
            
            // 2. ADIM: Bu seanslara ait BİLETLERİ sil
            // ID listesini virgüllü hale getiriyoruz (?,?,?)
            $inQuery = implode(',', array_fill(0, count($session_ids), '?'));
            
            $stmtTickets = $pdo->prepare("DELETE FROM tickets WHERE session_id IN ($inQuery)");
            $stmtTickets->execute($session_ids);

            // 3. ADIM: SEANSLARI sil
            $stmtSessions = $pdo->prepare("DELETE FROM sessions WHERE hall_id = ?");
            $stmtSessions->execute([$hall_id]);
        }

        // 4. ADIM: Artık SALONU silebiliriz
        $stmtHall = $pdo->prepare("DELETE FROM halls WHERE id = ?");
        $stmtHall->execute([$hall_id]);

        $pdo->commit(); // Hata yoksa işlemi onayla

    } catch (Exception $e) {
        $pdo->rollBack(); // Hata varsa işlemi iptal et
        die("Silme işlemi sırasında hata oluştu: " . $e->getMessage());
    }
}

// Geri Dön
header("Location: halls.php");
exit;
?>