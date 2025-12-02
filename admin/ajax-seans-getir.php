<?php
include '../config/db.php';

if (isset($_GET['film_id'])) {
    $film_id = $_GET['film_id'];

    // Sadece gelecekteki seansları getir
    $sql = "SELECT s.id, s.start_time, s.price, h.name as hall_name 
            FROM sessions s
            JOIN halls h ON s.hall_id = h.id
            WHERE s.film_id = ? AND s.start_time > NOW()
            ORDER BY s.start_time ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$film_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tarih formatını güzelleştirip gönderelim
    foreach ($sessions as &$session) {
        $session['formatted_time'] = date("d.m.Y H:i", strtotime($session['start_time']));
    }

    echo json_encode($sessions);
}
?>