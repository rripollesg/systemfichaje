<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'systemfichaje';
$username = 'root';
$password = 'usbw';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener los datos del cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = isset($input['userId']) ? $input['userId'] : null;
    $type = isset($input['type']) ? $input['type'] : null;
    $timestamp = isset($input['timestamp']) ? $input['timestamp'] : date('Y-m-d H:i:s'); // Usar la hora actual si no se proporciona
    $latitude = isset($input['latitude']) ? $input['latitude'] : null;
    $longitude = isset($input['longitude']) ? $input['longitude'] : null;

    if (!$userId || !$type) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    // Asegurarse de que la fecha sea válida
    try {
        $dateTime = new DateTime($timestamp); // Parsear la fecha
        $formattedTimestamp = $dateTime->format('Y-m-d H:i:s'); // Formatear para MySQL
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Fecha inválida: ' . $e->getMessage()]);
        exit;
    }

    // Insertar el registro en la base de datos
    $stmt = $pdo->prepare("INSERT INTO access_log (user_id, type, timestamp, latitude, longitude) VALUES (:userId, :type, :timestamp, :latitude, :longitude)");
    $stmt->execute([
        'userId' => $userId,
        'type' => $type,
        'timestamp' => $formattedTimestamp,
        'latitude' => $latitude,
        'longitude' => $longitude
    ]);


    echo json_encode(['success' => true, 'message' => 'Registro guardado correctamente']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Error al procesar la fecha: ' . $e->getMessage()]);
}
?>