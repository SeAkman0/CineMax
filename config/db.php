<?php
$host = 'localhost';
$dbname = 'cinemax_db';
$username = 'root'; // XAMPP/MAMP varsayılanı genelde root'tur
$password = '';     // XAMPP'te boş, MAMP'te 'root' olabilir

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Hata modunu aktifleştir (Hataları görebilmek için)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Session başlatma işlemini de burada yapabiliriz, her sayfada tekrar yazmamak için
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>