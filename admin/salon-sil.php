<?php
include '../config/db.php';
session_start();

// Yetki Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

// Silme İşlemi
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM halls WHERE id = ?");
    $stmt->execute([$id]);
}

// Geri Dön
header("Location: halls.php");
exit;
?>