<?php
// =======================================================
//  1. AYARLAR VE GÜVENLİK
// =======================================================

// Veritabanı bağlantı dosyasını sayfaya dahil ediyoruz.
include '../config/database.php';

// Oturum (Session) başlatılmamışsa başlatıyoruz.
// Bu, $_SESSION değişkenlerini (giriş yapan kullanıcı bilgisi) okumak için şarttır.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- YETKİ KONTROLÜ ---
// Eğer kullanıcı giriş yapmamışsa VEYA rolü 'admin' değilse işlemi durdur.
// Bu güvenlik önlemi, linki bilen herhangi birinin silme yapmasını engeller.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim. Bu işlemi sadece yöneticiler yapabilir.");
}

// =======================================================
//  2. SİLME İŞLEMİ (TRANSACTION YÖNTEMİ)
// =======================================================

// URL'den silinecek filmin ID'si gelmiş mi? (delete-movie.php?id=5 gibi)
if (isset($_GET['id'])) {
    $film_id = $_GET['id'];

    try {
        // --- TRANSACTION BAŞLAT ---
        // Veritabanı işlemlerini bir "paket" haline getiriyoruz.
        // Eğer aşağıdaki adımlardan herhangi birinde hata çıkarsa, 
        // yapılan tüm silme işlemleri iptal edilecek (Rollback).
        $pdo->beginTransaction();

        // ---------------------------------------------------
        //  ADIM 1: BU FİLME AİT SEANSLARI BUL
        // ---------------------------------------------------
        // Bir filmi silmeden önce, o filmin oynatıldığı seansları bulmalıyız.
        // Çünkü bu seanslara bağlı satılmış biletler olabilir.
        $stmt = $pdo->prepare("SELECT id FROM sessions WHERE film_id = ?");
        $stmt->execute([$film_id]);
        
        // FETCH_COLUMN: Bize sadece ID'lerden oluşan düz bir liste verir.
        // Örn: $session_ids = [10, 12, 15];
        $session_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Eğer bu filme ait tanımlı seanslar varsa temizlik başlasın...
        if (!empty($session_ids)) {
            
            // ---------------------------------------------------
            //  ADIM 2: BU SEANSLARA AİT BİLETLERİ SİL (En Alt Katman)
            // ---------------------------------------------------
            // SQL'de "WHERE id IN (1, 2, 3)" yapısını kullanacağız.
            // ID sayısı kadar soru işareti (?) oluşturuyoruz. (Örn: ?,?,?)
            $inQuery = implode(',', array_fill(0, count($session_ids), '?'));
            
            // "Bu seanslardan herhangi birine ait olan biletleri sil" diyoruz.
            $stmtTickets = $pdo->prepare("DELETE FROM tickets WHERE session_id IN ($inQuery)");
            $stmtTickets->execute($session_ids);

            // ---------------------------------------------------
            //  ADIM 3: SEANSLARI SİL (Orta Katman)
            // ---------------------------------------------------
            // Biletler temizlendiğine göre artık seans kayıtlarını silebiliriz.
            $stmtSessions = $pdo->prepare("DELETE FROM sessions WHERE film_id = ?");
            $stmtSessions->execute([$film_id]);
        }

        // ---------------------------------------------------
        //  ADIM 4: FİLMİ SİL (En Üst Katman - Ana Hedef)
        // ---------------------------------------------------
        // Filmin altındaki tüm veriler (Biletler ve Seanslar) temizlendi.
        // Artık filmin kendisini güvenle silebiliriz.
        $stmtFilm = $pdo->prepare("DELETE FROM films WHERE id = ?");
        $stmtFilm->execute([$film_id]);

        // --- İŞLEMİ ONAYLA (COMMIT) ---
        // Buraya kadar kod kırılmadan geldiyse her şey yolunda demektir.
        // Yapılan değişiklikleri veritabanına kalıcı olarak işle.
        $pdo->commit();

    } catch (Exception $e) {
        // --- HATA VARSA GERİ AL (ROLLBACK) ---
        // Eğer herhangi bir adımda hata oluşursa (Örn: Veritabanı bağlantısı koptu),
        // "pdo->rollBack()" komutu yapılan tüm silme işlemlerini geri alır.
        // Böylece yarım yamalak silinmiş veri kalmaz.
        $pdo->rollBack();
        die("Silme işlemi sırasında hata oluştu: " . $e->getMessage());
    }
}

// =======================================================
//  3. YÖNLENDİRME
// =======================================================
// İşlem bittiğinde Film Yönetimi sayfasına geri dön.
header("Location: movies.php");
exit;
?>