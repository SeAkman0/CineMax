<?php
include 'config/database.php';
header("Content-Type: application/json; charset=UTF-8");

// Sadece ID ve Başlığı çekiyoruz
$sql = "SELECT id, title FROM films WHERE is_active = 1";
$stmt = $pdo->query($sql);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>