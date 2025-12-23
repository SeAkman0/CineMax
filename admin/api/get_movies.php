<?php
include '../../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

// Tüm filmleri çek (En yeni en üstte)
$sql = "SELECT * FROM films ORDER BY id DESC";
$stmt = $pdo->query($sql);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'data' => $movies
]);
?>