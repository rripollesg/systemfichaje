<!-- registro.html -->
<?php
// registro.php
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];
    
    try {
        $conn = new PDO("mysql:host=localhost;dbname=systemfichaje", "root", "usbw");
        $stmt = $conn->prepare("INSERT INTO users (usuario, password, rol) VALUES (?, ?, ?)");
        $stmt->execute([$usuario, $password, $rol]);
        
        echo "Usuario registrado exitosamente!";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
<form action="registro.php" method="POST">
    <h2>Registro</h2>
    <label>Usuario:</label>
    <input type="text" name="usuario" required><br>
    
    <label>Contraseña:</label>
    <input type="password" name="password" required><br>
    
    <label>Tipo de usuario:</label>
    <select name="rol">
        <option value="usuario">Usuario</option>
        <option value="admin">Administrador</option>
    </select><br>
    
    <button type="submit">Registrar</button>
</form>