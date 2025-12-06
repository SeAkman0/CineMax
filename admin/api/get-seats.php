<?php
// =======================================================
//  1. TEMİZLİK VE BAŞLIK AYARLARI (JSON İÇİN ŞART)
// =======================================================

// Bu komut, bu satırdan önce oluşmuş olabilecek tüm çıktıları (boşluk, enter, hata yazısı vb.) siler.
// Eğer silmezsek, JSON verisinin başına bir boşluk bile gelse JavaScript bunu okuyamaz ve hata verir.
ob_clean(); 

// Tarayıcıya diyoruz ki: "Sana HTML (yazı) değil, JSON (veri) gönderiyorum."
// Bu sayede JavaScript gelen veriyi nesne (object) olarak tanır.
header('Content-Type: application/json; charset=utf-8');

// Hataları ekrana basma, gizle.
// Çünkü ekrana bir PHP hatası basılırsa JSON formatı bozulur ve sistem çöker.
error_reporting(0); 


// =======================================================
//  2. VERİTABANI BAĞLANTISI (AKILLI YOL BULUCU)
// =======================================================

// Bu dosya hem 'admin' klasöründe hem de 'admin/api' klasöründe olabilir.
// Dosya yolunu dinamik olarak bulmak için 'file_exists' ile kontrol ediyoruz.

if (file_exists('../../config/database.php')) {
    include '../../config/database.php'; // Eğer 'admin/api/' içindeysek 2 klasör geri çık
} elseif (file_exists('../config/database.php')) {
    include '../config/database.php'; // Eğer 'admin/' içindeysek 1 klasör geri çık
} elseif (file_exists('../config/db.php')) {
    include '../config/db.php'; // Eski dosya adı kontrolü
} else {
    // Eğer veritabanı dosyası bulunamazsa JSON formatında hata döndür ve durdur.
    echo json_encode(['error' => 'Veritabanı dosyası bulunamadı!']);
    exit;
}


// =======================================================
//  3. VERİ ÇEKME İŞLEMİ (KOLTUKLARI GETİR)
// =======================================================

// URL'den 'session_id' (Seans ID) gelmiş mi kontrol et.
// Örn: get-seats.php?session_id=5
if (isset($_GET['session_id'])) {
    
    $session_id = $_GET['session_id'];

    try {
        // --- A. SALON BİLGİLERİNİ ÇEK ---
        // Bu seans hangi salonda? O salon kaç sıra ve kaç sütundan oluşuyor?
        // Sessions tablosunu Halls tablosuyla birleştiriyoruz (JOIN).
        $sqlHall = "SELECT h.total_rows, h.total_cols 
                    FROM sessions s 
                    JOIN halls h ON s.hall_id = h.id 
                    WHERE s.id = ?";
        
        $stmt = $pdo->prepare($sqlHall);
        $stmt->execute([$session_id]);
        $hall = $stmt->fetch(PDO::FETCH_ASSOC);

        // Eğer salon bulunamazsa (veya seans yoksa) hata döndür.
        if (!$hall) {
            echo json_encode(['error' => 'Salon bilgisi bulunamadı']);
            exit;
        }

        // --- B. DOLU KOLTUKLARI ÇEK ---
        // Bu seans için daha önce satılmış biletleri (tickets) bul.
        // Bize sadece koltuk numaraları lazım (Örn: A-1, B-5).
        $sqlTickets = "SELECT seat_number FROM tickets WHERE session_id = ?";
        $stmtTicket = $pdo->prepare($sqlTickets);
        $stmtTicket->execute([$session_id]);
        
        // FETCH_COLUMN: Bize karmaşık bir yapı değil, sadece koltuk numaralarından oluşan düz bir liste ver.
        // Örn: ['A-1', 'A-2', 'C-5']
        $sold_seats = $stmtTicket->fetchAll(PDO::FETCH_COLUMN);

        // --- C. CEVABI JSON OLARAK DÖNDÜR ---
        // JavaScript'e 3 parça bilgi gönderiyoruz:
        // 1. rows: Kaç satır var? (Döngü kurmak için)
        // 2. cols: Kaç sütun var? (Döngü kurmak için)
        // 3. sold: Hangi koltuklar dolu? (Kırmızı yapmak için)
        echo json_encode([
            'rows' => $hall['total_rows'],
            'cols' => $hall['total_cols'],
            'sold' => $sold_seats
        ]);

    } catch (Exception $e) {
        // Veritabanı hatası olursa JSON olarak bildir.
        echo json_encode(['error' => 'Sorgu hatası oluştu']);
    }

} else {
    // Eğer ID gönderilmediyse hata döndür.
    echo json_encode(['error' => 'Seans ID (session_id) gönderilmedi']);
}
?>