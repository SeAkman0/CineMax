<?php
include '../config/database.php';

if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];

    // 1. Salon Bilgilerini Çek (Kaç sıra, kaç sütun?)
    $sqlHall = "SELECT h.total_rows, h.total_cols 
                FROM sessions s 
                JOIN halls h ON s.hall_id = h.id 
                WHERE s.id = ?";
    $stmt = $pdo->prepare($sqlHall);
    $stmt->execute([$session_id]);
    $hall = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Dolu Koltukları Çek
    $sqlTickets = "SELECT seat_number FROM tickets WHERE session_id = ?";
    $stmtTicket = $pdo->prepare($sqlTickets);
    $stmtTicket->execute([$session_id]);
    $sold_seats = $stmtTicket->fetchAll(PDO::FETCH_COLUMN);

    // 3. JSON Olarak Döndür
    echo json_encode([
        'rows' => $hall['total_rows'],
        'cols' => $hall['total_cols'],
        'sold' => $sold_seats
    ]);
}
?>