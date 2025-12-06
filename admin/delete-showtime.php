<?php
// =======================================================
//  1. AYARLAR VE GÜVENLİK
// =======================================================

// Veritabanı bağlantı dosyasını sayfaya dahil ediyoruz.
include '../config/database.php';

// Oturum (Session) kontrolü.
// Eğer oturum daha önce başlatılmamışsa, şimdi başlatıyoruz.
// Bu, $_SESSION değişkenlerini okuyabilmemiz için şarttır.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- YETKİ KONTROLÜ ---
// Güvenlik Duvarı:
// 1. Kullanıcı giriş yapmış mı? (user_id var mı?)
// 2. Kullanıcının yetkisi 'admin' mi?
// Eğer bunlardan biri eksikse işlem durdurulur ve "Yetkisiz erişim" denir.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim. Bu işlemi sadece yöneticiler yapabilir.");
}

// =======================================================
//  2. SİLME İŞLEMİ (TRANSACTION YÖNTEMİ)
// =======================================================

// URL'den silinecek seansın ID'si gelmiş mi? (delete-showtime.php?id=5 gibi)
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // --- TRANSACTION BAŞLAT ---
        // Veritabanı işlemlerini bir "paket" haline getiriyoruz.
        // "Ya hepsi silinsin ya da bir hata çıkarsa hiçbiri silinmesin" mantığıdır.
        // Bu sayede yarım yamalak veri silinmesinin önüne geçeriz.
        $pdo->beginTransaction();

        // ---------------------------------------------------
        //  ADIM 1: ÖNCE BAĞLI BİLETLERİ SİL (Alt Tablo)
        // ---------------------------------------------------
        // Bir seansı silmeden önce, o seansa satılmış biletleri silmemiz gerekir.
        // Aksi takdirde veritabanı "Bu biletlerin seansı nerede?" diye hata verir (Foreign Key Hatası).
        $stmt1 = $pdo->prepare("DELETE FROM tickets WHERE session_id = ?");
        $stmt1->execute([$id]);

        // ---------------------------------------------------
        //  ADIM 2: ARDINDAN SEANSI SİL (Ana Tablo)
        // ---------------------------------------------------
        // Biletler temizlendiğine göre, artık seansın kendisini güvenle silebiliriz.
        $stmt2 = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt2->execute([$id]);

        // --- İŞLEMİ ONAYLA (COMMIT) ---
        // Buraya kadar kod hata vermeden geldiyse, yapılan tüm değişiklikleri kalıcı olarak kaydet.
        $pdo->commit();

    } catch (Exception $e) {
        // --- HATA VARSA GERİ AL (ROLLBACK) ---
        // Eğer biletleri silerken veya seansı silerken bir hata oluşursa,
        // "pdo->rollBack()" komutu yapılan işlemleri geri alır.
        // Böylece seans silinmediyse biletlerin de silinmemesini sağlar (Veri Güvenliği).
        $pdo->rollBack();
        die("Silme hatası: " . $e->getMessage());
    }
}

// =======================================================
//  3. YÖNLENDİRME
// =======================================================
// İşlem bitince Seans Yönetimi sayfasına (showtimes.php) geri dön.
header("Location: showtimes.php");
exit;
?>