<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'systemfichaje';
$username = 'root';
$password = 'usbw';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Incluir log_id y NIE en la consulta
    $stmt = $pdo->query("
        SELECT 
            l.id AS log_id,
            u.usuario,
            u.nie,
            l.type,
            l.timestamp,
            l.latitude,
            l.longitude 
        FROM access_log l
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.timestamp DESC
    ");
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($logs);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>