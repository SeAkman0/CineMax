<?php
include '../config/database.php';

// Güvenlik: Admin mi?
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

if (isset($_GET['id'])) {
    $film_id = $_GET['id'];

    try {
        // İşlemi Başlat (Transaction) - Hata olursa geri almak için
        $pdo->beginTransaction();

        // 1. ADIM: Bu filme ait Seans ID'lerini bul
        $stmt = $pdo->prepare("SELECT id FROM sessions WHERE film_id = ?");
        $stmt->execute([$film_id]);
        $session_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Eğer bu filmin seansları varsa...
        if (!empty($session_ids)) {
            
            // 2. ADIM: Bu seanslara ait BİLETLERİ sil
            // ID listesini virgüllü hale getiriyoruz: ?,?,?
            $inQuery = implode(',', array_fill(0, count($session_ids), '?'));
            
            $stmtTickets = $pdo->prepare("DELETE FROM tickets WHERE session_id IN ($inQuery)");
            $stmtTickets->execute($session_ids);

            // 3. ADIM: SEANSLARI sil
            $stmtSessions = $pdo->prepare("DELETE FROM sessions WHERE film_id = ?");
            $stmtSessions->execute([$film_id]);
        }

        // 4. ADIM: Artık FİLMİ silebiliriz
        $stmtFilm = $pdo->prepare("DELETE FROM films WHERE id = ?");
        $stmtFilm->execute([$film_id]);

        // İşlemi Onayla
        $pdo->commit();

    } catch (Exception $e) {
        // Hata olursa işlemi geri al
        $pdo->rollBack();
        die("Silme işlemi sırasında hata oluştu: " . $e->getMessage());
    }
}

header("Location: movies.php");
exit;
?>