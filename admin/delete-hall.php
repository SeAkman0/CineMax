<?php
// =======================================================
//  1. AYARLAR VE GÜVENLİK
// =======================================================

// Veritabanı bağlantı dosyasını çağırıyoruz.
include '../config/database.php';

// Oturum (Session) kontrolü. Eğer başlatılmamışsa başlatıyoruz.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- YETKİ KONTROLÜ ---
// Sadece giriş yapmış ve rolü 'admin' olan kişiler silme işlemi yapabilir.
// Aksi takdirde işlem durdurulur (die).
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

// =======================================================
//  2. SİLME İŞLEMİ (TRANSACTION)
// =======================================================

// URL'den silinecek salonun ID'si gelmiş mi? (delete-hall.php?id=5)
if (isset($_GET['id'])) {
    $hall_id = $_GET['id'];

    try {
        // --- TRANSACTION BAŞLAT ---
        // Transaction (İşlem): "Ya hep ya hiç" kuralıdır.
        // Aşağıdaki işlemlerden (Bilet silme, Seans silme, Salon silme) herhangi biri başarısız olursa,
        // yapılan tüm değişiklikler geri alınır (Rollback). Veri kaybı veya bozulması önlenir.
        $pdo->beginTransaction(); 

        // ---------------------------------------------------
        //  ADIM 1: BU SALONA AİT SEANSLARI BUL
        // ---------------------------------------------------
        // Önce bu salonda hangi seansların olduğunu öğrenmemiz lazım ki,
        // o seanslara ait biletleri silebilelim.
        $stmt = $pdo->prepare("SELECT id FROM sessions WHERE hall_id = ?");
        $stmt->execute([$hall_id]);
        
        // FETCH_COLUMN: Bize sadece ID'lerden oluşan düz bir liste (array) verir. [10, 12, 15...]
        $session_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Eğer bu salonda tanımlı seanslar varsa temizlik başlasın
        if (!empty($session_ids)) {
            
            // ---------------------------------------------------
            //  ADIM 2: BU SEANSLARA AİT BİLETLERİ SİL (En Alt Katman)
            // ---------------------------------------------------
            // SQL'de "WHERE id IN (1, 2, 3)" yapısını kurmak için ID sayısı kadar soru işareti (?) oluşturuyoruz.
            // Örnek: implode ile "?,?,?" stringi oluşturulur.
            $inQuery = implode(',', array_fill(0, count($session_ids), '?'));
            
            $stmtTickets = $pdo->prepare("DELETE FROM tickets WHERE session_id IN ($inQuery)");
            
            // execute() fonksiyonuna dizi olarak ID listesini veriyoruz.
            $stmtTickets->execute($session_ids);

            // ---------------------------------------------------
            //  ADIM 3: SEANSLARI SİL (Orta Katman)
            // ---------------------------------------------------
            // Biletler temizlendiğine göre artık seansları silebiliriz.
            $stmtSessions = $pdo->prepare("DELETE FROM sessions WHERE hall_id = ?");
            $stmtSessions->execute([$hall_id]);
        }

        // ---------------------------------------------------
        //  ADIM 4: SALONU SİL (En Üst Katman - Ana Hedef)
        // ---------------------------------------------------
        // Artık salonun içi boş (Seansı ve bileti yok), güvenle silebiliriz.
        $stmtHall = $pdo->prepare("DELETE FROM halls WHERE id = ?");
        $stmtHall->execute([$hall_id]);

        // --- İŞLEMİ ONAYLA (COMMIT) ---
        // Buraya kadar hata olmadıysa, yapılan tüm silme işlemlerini veritabanına kalıcı olarak işle.
        $pdo->commit(); 

    } catch (Exception $e) {
        // --- HATA VARSA GERİ AL (ROLLBACK) ---
        // Eğer herhangi bir aşamada hata çıkarsa, hiçbir şeyi silme, her şeyi eski haline getir.
        $pdo->rollBack(); 
        die("Silme işlemi sırasında hata oluştu: " . $e->getMessage());
    }
}

// =======================================================
//  3. YÖNLENDİRME
// =======================================================
// İşlem bitince Salon Yönetimi sayfasına geri dön.
header("Location: halls.php");
exit;
?>