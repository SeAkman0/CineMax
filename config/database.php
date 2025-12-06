<?php
// =======================================================
//  1. VERİTABANI AYARLARI
// =======================================================

// Sunucu bilgileri (Localhost'ta genelde standarttır)
$host = 'localhost';      // Sunucu adresi
$dbname = 'cinemax_db';   // Veritabanı adı (Senin oluşturduğun isim)
$username = 'root';       // XAMPP varsayılan kullanıcı adı
$password = '';           // XAMPP varsayılan şifresi (Boş)

// =======================================================
//  2. ZAMAN VE DİL AYARLARI
// =======================================================

// Saat farkı sorununu çözmek için (Seans saati geçti mi kontrolü için kritik)
// Sunucu saati ile Türkiye saati aynı olsun diye:
date_default_timezone_set('Europe/Istanbul');

// Türkçe tarih ve gün isimleri için yerel ayar (Örn: Monday yerine Pazartesi)
setlocale(LC_TIME, 'tr_TR.UTF-8', 'tr_TR', 'tr', 'turkish');

// =======================================================
//  3. BAĞLANTIYI KURMA (PDO)
// =======================================================

try {
    // PDO (PHP Data Objects) ile güvenli bağlantı oluşturuyoruz.
    // "charset=utf8" diyerek Türkçe karakter sorunu yaşamayı engelliyoruz.
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Hata Modunu Açıyoruz:
    // Eğer SQL sorgularında bir hata olursa, PHP bize "Exception" fırlatsın ve hatayı görelim.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // =======================================================
    //  4. OTURUM VE TAMPON YÖNETİMİ
    // =======================================================

    // Session (Oturum) başlatılmamışsa başlat.
    // Bu sayede her sayfada tekrar tekrar session_start() yazmak zorunda kalmayız.
    // Kullanıcı giriş bilgileri burada tutulur.
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        
        // Output Buffering (Çıktı Tamponlama) Başlat:
        // Sayfadaki HTML kodlarını hemen basmak yerine hafızada tutar.
        // Bu, "Headers already sent" hatasını ve sayfa yönlendirme (header location) sorunlarını çözer.
        ob_start();
    }
    
} catch (PDOException $e) {
    // Eğer bağlantı kurulamazsa (Şifre yanlışsa, veritabanı yoksa vb.)
    // İşlemi durdur ve hatayı ekrana bas.
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
// NOT: Bu dosyanın sonuna asla ?> (kapatma etiketi) koymuyoruz.
// Sebebi: Eğer ?> sonrasında yanlışlıkla bir boşluk bırakılırsa,
// bu boşluk tüm sayfalara yansır ve tasarım hatalarına (beyaz çizgi) yol açar.