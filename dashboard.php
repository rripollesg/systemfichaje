<?php
session_start();
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h2>
    
    <?php if($_SESSION['rol'] === 'admin'): ?>
        <h3>Panel de Administración</h3>
        <a href="admin.php">Administrar usuarios</a><br>
        <a href="configuracion.php">Configuración del sistema</a><br>
    <?php else: ?>
        <h3>Panel de usuario</h3>
        <a href="perfil.php">Mi perfil</a><br>
        <a href="documentos.php">Mis documentos</a><br>
    <?php endif; ?>
    
    <a href="logout.php">Cerrar Sesión</a>
</body>
</html>