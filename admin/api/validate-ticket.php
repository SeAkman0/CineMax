<?php
// Veritabanı bağlantısı
require_once '../../config/database.php';

// JSON cevabı vereceğiz
header('Content-Type: application/json');

// Sadece POST isteği kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek']);
    exit;
}

// Gelen JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? trim($input['code']) : '';

if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Kod boş olamaz']);
    exit;
}

// 1. Kodu, Film Durumunu, Seans Saatini ve KULLANICI ADINI Sorgula
$sql = "SELECT t.*, f.title, f.is_active, s.start_time, u.username 
        FROM tickets t
        JOIN sessions s ON t.session_id = s.id
        JOIN films f ON s.film_id = f.id
        JOIN users u ON t.user_id = u.id 
        WHERE t.verification_code = ?";
        
$stmt = $pdo->prepare($sql);
$stmt->execute([$code]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if ($ticket) {
    
    $simdi = time();
    $seans_zamani = strtotime($ticket['start_time']);

    // A. Film Vizyonda mı?
    if ($ticket['is_active'] == 0) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'GİRİŞ YASAK! Film vizyondan kaldırılmış.',
            'detail' => $ticket['title']
        ]);
    }
    // B. Bilet Zaten Kullanılmış mı?
    elseif ($ticket['is_used'] == 1) {
        echo json_encode([
            'status' => 'warning', 
            'message' => 'BU BİLET ZATEN KULLANILDI!',
            // DÜZELTME 1: \n yerine <br> kullanıldı
            'detail' => 'Kullanıcı: ' . $ticket['username'] . '<br>Giriş Saati: ' . date("H:i d.m.Y", strtotime($ticket['used_at']))
        ]);
    }
    // C. Seans Süresi Doldu mu?
    elseif ($seans_zamani < $simdi) {
        echo json_encode([
            'status' => 'error',
            'message' => 'GİRİŞ YASAK! Seans süresi doldu.',
            'detail' => 'Seans Tarihi: ' . date("d.m.Y H:i", $seans_zamani)
        ]);
    }
    // D. GİRİŞ BAŞARILI (Hoşgeldin Mesajı)
    else {
        $update = $pdo->prepare("UPDATE tickets SET is_used = 1, used_at = NOW() WHERE id = ?");
        $update->execute([$ticket['id']]);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'GİRİŞ ONAYLANDI',
            // DÜZELTME 2: Kullanıcı adından sonra <br> eklendi ve film bilgisiyle ayrıldı
            'detail' => 'Hoşgeldin: ' . strtoupper($ticket['username']) . '<br>' . $ticket['title'] . ' - Koltuk: ' . $ticket['seat_number']
        ]);
    }
} else {
    // E. Kod Bulunamadı
    echo json_encode(['status' => 'error', 'message' => 'GEÇERSİZ BİLET KODU!']);
}
?>