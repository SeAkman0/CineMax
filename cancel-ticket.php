<?php
// ---------------------------------------------------------
//  1. AYARLAR VE OTURUM BAŞLATMA
// ---------------------------------------------------------

// Veritabanı bağlantı dosyasını (PDO) sayfaya dahil ediyoruz.
include 'config/database.php';

// Kullanıcının kim olduğunu bilmek için oturumu (session) başlatıyoruz.
session_start();


// ---------------------------------------------------------
//  2. GÜVENLİK KONTROLÜ: GİRİŞ YAPILMIŞ MI?
// ---------------------------------------------------------

// Eğer session'da 'user_id' yoksa, kişi giriş yapmamış demektir.
// Giriş yapmayan biri bilet silemeyeceği için login sayfasına atıyoruz.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit; // "exit" komutu, kodun geri kalanının çalışmasını durdurur.
}


// ---------------------------------------------------------
//  3. İŞLEM KONTROLÜ: POST METODU VE VERİ VAR MI?
// ---------------------------------------------------------

// Silme işlemleri güvenlik nedeniyle asla GET (link) ile yapılmaz, POST (form) ile yapılır.
// Burada, isteğin POST olup olmadığını ve silinecek ID'nin gelip gelmediğini kontrol ediyoruz.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ticket_id'])) {
    
    // Formdan gelen (gizli inputtaki) bilet ID'si
    $ticket_id = $_POST['ticket_id'];
    
    // Şu an sisteme giriş yapmış olan kullanıcının ID'si
    $user_id = $_SESSION['user_id'];


    // ---------------------------------------------------------
    //  4. VERİTABANI SİLME İŞLEMİ (KRİTİK GÜVENLİK)
    // ---------------------------------------------------------

    // BURASI ÇOK ÖNEMLİ: 
    // SQL sorgusuna "AND user_id = ?" şartını ekliyoruz.
    // Bu sayede; kullanıcı sadece KENDİ biletini silebilir.
    // Eğer bilet ID'si doğru olsa bile sahibi başkasıysa, bu sorgu o satırı silmez.
    $sql = "DELETE FROM tickets WHERE id = ? AND user_id = ?";
    
    // SQL Injection saldırılarını önlemek için 'prepare' kullanıyoruz.
    $stmt = $pdo->prepare($sql);
    
    // Sorguyu çalıştırıyoruz.
    $result = $stmt->execute([$ticket_id, $user_id]);

    // rowCount(): Etkilenen satır sayısını verir.
    // Eğer > 0 ise, veritabanından bir şeyler silinmiş demektir.
    if ($result && $stmt->rowCount() > 0) {
        
        // BAŞARILI: İşlem tamam, kullanıcıyı bilgilendirmek için msg=deleted parametresiyle geri yolla.
        header("Location: my-tickets.php?msg=deleted");
        
    } else {
        
        // BAŞARISIZ: Ya bilet bulunamadı ya da bu bilet bu kullanıcıya ait değil.
        header("Location: my-tickets.php?msg=error");
    }

} else {
    // ---------------------------------------------------------
    //  5. YETKİSİZ ERİŞİM (DOĞRUDAN LİNK)
    // ---------------------------------------------------------
    
    // Eğer birisi bu sayfanın adresini tarayıcıya elle yazıp girmeye çalışırsa (POST değilse),
    // hiçbir işlem yapmadan biletlerim sayfasına geri gönderiyoruz.
    header("Location: my-tickets.php");
}
?>