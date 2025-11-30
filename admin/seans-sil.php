<?php
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Yetkisiz erişim.");
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: sessions.php");
exit;
?>