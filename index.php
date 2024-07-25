
<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: pages/mis_solicitudes.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'php/auth.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/style_index.css">
    <title>GCE</title>
</head>
<body class="indexbody">
    <h1>Gestión compras Etarey</h1>
    <main>
        <div class="contenedor__todo">
            <div class="caja__trasera">
                <form class="formulario__login" id="login-form" method="post" action="index.php">
                    <h2>Iniciar Sesión</h2>
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" id="username" name="username" placeholder="Nombre de Usuario" required />
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required />
                    <button type="submit" class="btn btn-encuadrado">Ingresar</button>
                </form>
            </div>
            <div class="caja__trasera">
                <div class="caja__trasera-register">
                    <h3>¿Aún no tienes una cuenta?</h3>
                    <p>Solicitar a IT Creación de cuenta</p>
                    <a href="http://192.168.20.12/glpi/front/helpdesk.public.php?create_ticket=1" class="btn btn-encuadrado">Crear Ticket a IT</a>
                </div>
            </div>
        </div>
    </main>
    <?php include 'php/config/footer.php'; ?>
</body>
</html>
