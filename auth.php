<?php
// Configurar parámetros de sesión ANTES de session_start()
ini_set('session.cookie_lifetime', 31536000); // 1 año en segundos
ini_set('session.gc_maxlifetime', 31536000);
session_start();

// --- Obtener Variables de Entorno del Hosting (Render) ---
// La plataforma de hosting (Render) proporciona estas variables
$db_host = getenv('DB_HOST') ?: 'localhost'; // Usa getenv() o un fallback
$db_name = getenv('DB_DATABASE') ?: 'systemfichaje';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: 'usbw';

// Detectar el driver (MySQL o PostgreSQL)
// Render usa PostgreSQL; si usas un servicio externo puede ser MySQL
$db_driver = getenv('DB_DRIVER') ?: 'pgsql'; // 'pgsql' para Render, 'mysql' para la mayoría de los hostings
$db_charset = 'utf8mb4'; // Típico para MySQL

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
    // --- Configurar DSN (Data Source Name) basado en el driver ---
    if ($db_driver === 'mysql') {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
    } elseif ($db_driver === 'pgsql') {
        // Formato DSN para PostgreSQL
        $dsn = "pgsql:host=$db_host;dbname=$db_name;user=$db_user;password=$db_pass";
        $db_user = null; // PDO para pgsql lo maneja en el DSN
        $db_pass = null;
    } else {
        throw new Exception("Driver de Base de Datos no soportado.");
    }

    // Configurar conexión PDO
    $conn = new PDO(
        $dsn,
        $db_user, // $db_user y $db_pass pueden ser null si ya están en el DSN
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Buscar usuario en la base de datos
    // NOTA: Esta consulta funciona perfectamente en PDO.
    $stmt = $conn->prepare("
        SELECT id, usuario, password, rol 
        FROM users 
        WHERE usuario = :usuario
        LIMIT 1
    ");
    $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
    $stmt->execute();
    
    $user = $stmt->fetch(); // No necesitas PDO::FETCH_ASSOC, ya se puso como DEFAULT en las opciones

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
    // Manejo de errores específicos de DB
    error_log("Error de autenticación (DB): " . $e->getMessage());
    header("Location: login.php?error=1");
    exit;
} catch (Exception $e) {
    // Manejo de errores generales (ej. driver no soportado)
    error_log("Error de autenticación (General): " . $e->getMessage());
    header("Location: login.php?error=1");
    exit;
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        $conn = null;
    }
}
