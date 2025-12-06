<?php
// =======================================================
//  1. AYARLAR VE OTURUM KONTROLÜ
// =======================================================

// Veritabanı bağlantı dosyasını sayfaya dahil ediyoruz.
include '../config/database.php';

// --- SESSION HATASI ÇÖZÜMÜ ---
// Eğer session zaten başlatılmışsa tekrar başlatmaya çalışma (Hata verir).
// Sadece başlatılmamışsa başlat.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
//  2. GÜVENLİK KONTROLÜ (YETKİ)
// =======================================================

// Giriş yapmamışsa VEYA yetkisi 'admin' değilse işlemi durdur.
// Bu, linki bilen herhangi birinin (veya normal üyenin) admin silmesini engeller.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim. Bu işlemi sadece yöneticiler yapabilir.");
}

// =======================================================
//  3. SİLME İŞLEMİ (TRANSACTION İLE)
// =======================================================

// URL'den silinecek kullanıcının ID'si gelmiş mi? (delete-user.php?id=5)
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // --- KENDİNİ SİLME KORUMASI ---
    // Admin yanlışlıkla kendi hesabını silerse sistemden atılır ve bir daha giremez.
    if ($id == $_SESSION['user_id']) {
        die("Güvenlik Uyarısı: Kendi hesabınızı silemezsiniz!");
    }

    try {
        // --- TRANSACTION BAŞLAT ---
        // Veritabanı işlemlerini bir "paket" haline getiriyoruz.
        // Aşağıdaki adımlardan biri bile başarısız olursa, hiçbiri yapılmamış sayılacak (Rollback).
        $pdo->beginTransaction();

        // --- HATA ÇÖZÜMÜ: İLİŞKİSEL VERİ TEMİZLİĞİ ---
        // Kullanıcıyı silmeden önce ona bağlı olan verileri silmeliyiz.
        // Aksi takdirde "Integrity constraint violation" (Bütünlük ihlali) hatası alırız.

        // 1. ADIM: Kullanıcının BİLETLERİNİ sil
        // Eğer biletleri silmezsek, tickets tablosundaki user_id boşa düşer.
        $stmtTickets = $pdo->prepare("DELETE FROM tickets WHERE user_id = ?");
        $stmtTickets->execute([$id]);

        // 2. ADIM: Kullanıcının LOG KAYITLARINI sil
        // (Eğer sistemde login_logs tablosu varsa bu adım şarttır)
        $stmtLogs = $pdo->prepare("DELETE FROM login_logs WHERE user_id = ?");
        $stmtLogs->execute([$id]);

        // 3. ADIM: Artık KULLANICIYI silebiliriz
        // Alt veriler temizlendiği için artık ana kaydı silmek güvenli.
        $stmtUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmtUser->execute([$id]);

        // --- İŞLEMİ ONAYLA (COMMIT) ---
        // Buraya kadar hata olmadıysa, yapılan değişiklikleri kalıcı olarak kaydet.
        $pdo->commit();

    } catch (Exception $e) {
        // --- HATA VARSA GERİ AL (ROLLBACK) ---
        // Eğer bir sorun çıkarsa (örneğin veritabanı bağlantısı koparsa),
        // yarım yamalak silme yapma, her şeyi eski haline getir.
        $pdo->rollBack();
        die("Silme işlemi sırasında teknik bir hata oluştu: " . $e->getMessage());
    }
}

// =======================================================
//  4. YÖNLENDİRME
// =======================================================
// İşlem bitince Kullanıcı Listesi sayfasına geri dön.
header("Location: users.php");
exit;
?>