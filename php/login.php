
<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/crear_solicitud');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include '../php/auth.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_other.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Iniciar Sesión</h2>
        <form method="post" action="login.php">
            <div class="form-group">
                <label>Nombre de Usuario:</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
