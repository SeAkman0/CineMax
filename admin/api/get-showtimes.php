<?php
// =======================================================
//  1. TEMİZLİK VE BAŞLIK AYARLARI
// =======================================================

// Çıktı tamponunu temizle.
// Bu komut, bu dosyadan önce oluşmuş olabilecek boşluk, satır sonu veya hata mesajlarını siler.
// Eğer silmezsek, JSON verisi bozulur ve JavaScript tarafında "SyntaxError" alırsın.
ob_clean(); 

// Tarayıcıya ve JavaScript'e diyoruz ki: "Sana HTML (yazı) değil, JSON (veri) gönderiyorum."
// Ayrıca Türkçe karakter sorunu olmasın diye utf-8 belirtiyoruz.
header('Content-Type: application/json; charset=utf-8');

// PHP hatalarını ekrana basmayı kapatıyoruz.
// Çünkü ekrana basılan bir "Warning" veya "Notice", JSON yapısını bozar ve sistem çalışmaz.
error_reporting(0); 


// =======================================================
//  2. VERİTABANI BAĞLANTISI (AKILLI YOL BULUCU)
// =======================================================

// Bu dosya farklı klasörlerde (admin/ veya admin/api/) olabilir.
// Dosyanın nerede olduğuna bakarak doğru yolu dinamik olarak buluyoruz.

if (file_exists('../../config/database.php')) {
    include '../../config/database.php'; // Eğer 'admin/api/' klasöründeysek 2 geri çık
} elseif (file_exists('../config/database.php')) {
    include '../config/database.php'; // Eğer 'admin/' klasöründeysek 1 geri çık
} else {
    // Eğer dosya bulunamazsa JSON formatında hata döndür ve durdur.
    echo json_encode(['error' => 'Veritabanı dosyası bulunamadı! Yol hatası.']);
    exit;
}


// =======================================================
//  3. VERİ ÇEKME İŞLEMİ (SEANSLARI GETİR)
// =======================================================

// URL'den 'film_id' gelmiş mi kontrol et (Örn: get-showtimes.php?film_id=5)
if (isset($_GET['film_id'])) {
    
    $film_id = $_GET['film_id'];

    try {
        // --- SQL SORGUSU ---
        // 1. Sadece seçilen filme ait (s.film_id = ?) seansları getir.
        // 2. Sadece gelecekteki (s.start_time > NOW()) seansları getir. Geçmiştekilere bilet kesilmez.
        // 3. Salon adını da almak için 'halls' tablosuyla birleştir (JOIN).
        // 4. Tarihe göre sırala (En yakın tarih en üstte).
        $sql = "SELECT s.id, s.start_time, s.price, h.name as hall_name 
                FROM sessions s
                JOIN halls h ON s.hall_id = h.id
                WHERE s.film_id = ? AND s.start_time > NOW()
                ORDER BY s.start_time ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$film_id]);
        
        // Verileri ilişkisel dizi (associative array) olarak al.
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- VERİYİ İŞLEME ---
        // Veritabanından gelen ham tarih (2025-12-01 14:00:00) kullanıcıya göstermek için uygun değil.
        // Onu "01.12.2025 14:00" formatına çevirip yeni bir anahtar ('formatted_time') olarak ekliyoruz.
        foreach ($sessions as &$session) {
            $session['formatted_time'] = date("d.m.Y H:i", strtotime($session['start_time']));
        }

        // Hazırlanan diziyi JSON formatına çevirip ekrana bas.
        echo json_encode($sessions);

    } catch (Exception $e) {
        // Veritabanı hatası olursa bunu JSON olarak bildir.
        echo json_encode(['error' => 'Sorgu Hatası: ' . $e->getMessage()]);
    }

} else {
    // Eğer ID gönderilmediyse boş bir dizi döndür (Hata verme, sadece boş dön).
    echo json_encode([]);
}
?>