<?php
include '../config/db.php';

// Güvenlik: Admin mi?
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM films WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: films.php");
exit;
?>