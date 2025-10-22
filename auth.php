<?php
// Configurar parámetros de sesión ANTES de session_start()
ini_set('session.cookie_lifetime', 31536000); // 1 año en segundos
ini_set('session.gc_maxlifetime', 31536000);
session_start();

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// Validar campos vacíos
if (empty($_POST['usuario']) || empty($_POST['password'])) {
    header("Location: login.php?error=1");
    exit;
}

// Sanitizar y obtener datos
$usuario = trim($_POST['usuario']);
$password = $_POST['password'];

try {
    // Configurar conexión PDO
    $conn = new PDO(
        "mysql:host=localhost;dbname=systemfichaje;charset=utf8mb4",
        "root",
        "usbw"
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Buscar usuario en la base de datos
    $stmt = $conn->prepare("
        SELECT id, usuario, password, rol 
        FROM users 
        WHERE usuario = :usuario
        LIMIT 1
    ");
    $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar credenciales
    if ($user && password_verify($password, $user['password'])) {
        // Regenerar ID de sesión para prevenir fijación
        session_regenerate_id(true);
        
        // Establecer datos de sesión
        $_SESSION = [
            'user_id' => $user['id'],
            'usuario' => $user['usuario'],
            'rol' => $user['rol'],
            'loggedin' => true,
            'ip' => $_SERVER['REMOTE_ADDR'], // Para verificación de seguridad
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];

        // Redirección según rol
        if ($user['rol'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit;
        
    } else {
        // Credenciales inválidas
        header("Location: login.php?error=1");
        exit;
    }
    
} catch (PDOException $e) {
    // Registrar error en logs
    error_log("Error de autenticación: " . $e->getMessage());
    
    // Redirección genérica para evitar revelar información
    header("Location: login.php?error=1");
    exit;
    
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        $conn = null;
    }
}