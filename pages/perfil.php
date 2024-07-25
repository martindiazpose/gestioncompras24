
<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/auth.php';
verificarSesion(['usuario', 'administrador' , 'compras' , 'aprobador<']);
include $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/header.php';

//datos del usuario de la base de datos
$user_id = $_SESSION['user_id'];
$sql = "SELECT nombre_usuario, correo_electronico, perfil FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $sql_update = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $new_password, $user_id);
    $stmt_update->execute();

    echo "Contraseña actualizada con éxito.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_other.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/header.php'; ?>
    <div class="container mt-5">
        <h2>Perfil</h2>
        <form method="post" action="perfil.php">
            <div class="form-group">
                <label>Nombre de Usuario:</label>
                <input type="text" class="form-control" value="<?php echo $user['nombre_usuario']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Correo Electrónico:</label>
                <input type="email" class="form-control" value="<?php echo $user['correo_electronico']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Perfil:</label>
                <input type="text" class="form-control" value="<?php echo $user['perfil']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Nueva Contraseña:</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
<?php include '../php/config/footer.php'; ?>
</html>
