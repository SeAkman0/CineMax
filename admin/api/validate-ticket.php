<?php
// =======================================================
//  1. AYARLAR VE GÜVENLİK
// =======================================================

// Veritabanı bağlantı dosyasını dahil ediyoruz.
// 'require_once' dosyayı sadece bir kere çağırır, hata varsa durdurur.
require_once '../../config/database.php';

// Tarayıcıya veya JavaScript'e diyoruz ki: 
// "Sana HTML sayfası değil, saf veri (JSON) gönderiyorum."
header('Content-Type: application/json');

// --- GÜVENLİK: SADECE POST İSTEĞİ ---
// Bu sayfaya tarayıcı adres çubuğundan (GET) girilmesini engelliyoruz.
// Sadece JavaScript (fetch) üzerinden POST ile veri gelirse çalışır.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek']);
    exit; // Kodun çalışmasını burada durdur.
}

// =======================================================
//  2. GELEN VERİYİ ALMA (RAW JSON)
// =======================================================

// JavaScript 'fetch' ile veriyi JSON formatında gönderdiği için $_POST ile alamayız.
// 'php://input' akışını okuyup, gelen JSON verisini PHP dizisine çeviriyoruz.
$input = json_decode(file_get_contents('php://input'), true);

// Gelen verinin içinden 'code' (QR kod içeriği) değerini alıyoruz.
$code = isset($input['code']) ? trim($input['code']) : '';

// Eğer kod boş geldiyse hata döndür.
if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Kod boş olamaz']);
    exit;
}

// =======================================================
//  3. VERİTABANI SORGUSU (BİLETİ BULMA)
// =======================================================

// Bu sorgu çok önemlidir. Bilet (tickets) tablosundan yola çıkarak;
// - Filmin adını ve aktiflik durumunu (films tablosundan)
// - Seansın saatini (sessions tablosundan)
// - Bileti alan kişinin adını (users tablosundan) çekiyoruz.
// Bunun için 4 tabloyu birbirine JOIN (bağlama) işlemiyle bağlıyoruz.
$sql = "SELECT t.*, f.title, f.is_active, s.start_time, u.username 
        FROM tickets t
        JOIN sessions s ON t.session_id = s.id
        JOIN films f ON s.film_id = f.id
        JOIN users u ON t.user_id = u.id 
        WHERE t.verification_code = ?"; // QR kodumuz bu sütunda saklı
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$code]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

// =======================================================
//  4. MANTIKSAL KONTROLLER (SENARYOLAR)
// =======================================================

// Eğer veritabanında böyle bir bilet bulunduysa:
if ($ticket) {
    
    // Şu anki zamanı ve seansın zamanını alıyoruz.
    $simdi = time();
    $seans_zamani = strtotime($ticket['start_time']);

    // --- SENARYO 1: FİLM VİZYONDAN KALKMIŞ MI? ---
    // Eğer admin filmi 'Pasif' yapmışsa, o filmin biletleri geçersizdir.
    if ($ticket['is_active'] == 0) {
        echo json_encode([
            'status' => 'error', // Kırmızı uyarı
            'message' => 'GİRİŞ YASAK! Film vizyondan kaldırılmış.',
            'detail' => $ticket['title']
        ]);
    }
    
    // --- SENARYO 2: BİLET DAHA ÖNCE KULLANILMIŞ MI? ---
    // Eğer is_used sütunu 1 ise, bu bilet daha önce turnikeden geçmiştir.
    elseif ($ticket['is_used'] == 1) {
        echo json_encode([
            'status' => 'warning', // Sarı uyarı
            'message' => 'BU BİLET ZATEN KULLANILDI!',
            // used_at sütunundan giriş saatini gösteriyoruz. <br> ile alt satıra geçiyoruz.
            'detail' => 'Kullanıcı: ' . $ticket['username'] . '<br>Giriş Saati: ' . date("H:i d.m.Y", strtotime($ticket['used_at']))
        ]);
    }
    
    // --- SENARYO 3: SEANSIN SAATİ GEÇMİŞ Mİ? ---
    // Eğer seans saati şimdiki zamandan küçükse, film başlamış veya bitmiştir.
    elseif ($seans_zamani < $simdi) {
        echo json_encode([
            'status' => 'error', // Kırmızı uyarı
            'message' => 'GİRİŞ YASAK! Seans süresi doldu.',
            'detail' => 'Seans Tarihi: ' . date("d.m.Y H:i", $seans_zamani)
        ]);
    }
    
    // --- SENARYO 4: BAŞARILI GİRİŞ ---
    // Tüm kontrollerden geçtiyse, bilet geçerlidir.
    else {
        // 1. Veritabanını Güncelle: is_used=1 yap ve şu anki saati kaydet.
        $update = $pdo->prepare("UPDATE tickets SET is_used = 1, used_at = NOW() WHERE id = ?");
        $update->execute([$ticket['id']]);
        
        // 2. Başarılı Mesajı Gönder
        echo json_encode([
            'status' => 'success', // Yeşil onay
            'message' => 'GİRİŞ ONAYLANDI',
            // Kullanıcıyı karşıla ve koltuk bilgisini ver.
            // strtoupper: Kullanıcı adını büyük harf yapar.
            'detail' => 'Hoşgeldin: ' . strtoupper($ticket['username']) . '<br>' . $ticket['title'] . ' - Koltuk: ' . $ticket['seat_number']
        ]);
    }

} else {
    // --- SENARYO 5: BİLET BULUNAMADI ---
    // Okunan QR kod veritabanında yoksa sahte veya hatalıdır.
    echo json_encode(['status' => 'error', 'message' => 'GEÇERSİZ BİLET KODU!']);
}
?>