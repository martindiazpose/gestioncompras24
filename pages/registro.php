<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/auth.php';
verificarSesion(['administrador', 'usuario' ]);

// Incluir el Header
include $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/header.php';

// Configuración de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'gestioncomprasetarey@gmail.com';
$mail->Password = 'iukl vhlo cvge qmjy';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->setFrom('gestioncomprasetarey@gmail.com', 'Gestion Compras Etarey');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_usuario = $_POST['nombre_usuario'] ?? null;
    $correo_electronico = $_POST['correo_electronico'] ?? null;
    $contrasena = $_POST['contrasena'] ?? null;
    $perfil = $_POST['perfil'] ?? null;

    if ($nombre_usuario && $correo_electronico && $contrasena && $perfil) {
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

        if (isset($_POST['modificar'])) {
            // Modificar usuario existente
            $stmt = $conn->prepare("UPDATE usuarios SET nombre_usuario = ?, correo_electronico = ?, contrasena = ?, perfil = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $nombre_usuario, $correo_electronico, $contrasena_hash, $perfil, $_POST['id']);
            if ($stmt->execute()) {
                // Enviar correo de notificación de modificación.
                $mail->clearAddresses();
                $mail->addAddress($correo_electronico);
                $mail->Subject = 'Modificación de Usuario';
                $mail->Body = '
                <html>
                <head>
                    <title>Modificación de Usuario</title>
                </head>
                <body>
                    <p>Estimado/a ' . $nombre_usuario . ',</p>
                    <p>Le informamos que su cuenta ha sido modificada con los siguientes detalles:</p>
                    <ul>
                        <li><strong>Nombre de usuario:</strong> ' . $nombre_usuario . '</li>
                        <li><strong>Correo electrónico:</strong> ' . $correo_electronico . '</li>
                        <li><strong>Perfil:</strong> ' . $perfil . '</li>
                    </ul>
                    <p>Si no realizó esta acción, por favor contacte al equipo de IT Etarey a la mayor brevedad posible.</p>
                    <p>Saludos cordiales,</p>
                    <p>Equipo de IT Etarey</p>
                </body>
                </html>
                ';
                $mail->isHTML(true);
                $mail->send();
            } else {
                echo "Error al modificar usuario: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // Registrar nuevo usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, correo_electronico, contrasena, perfil) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre_usuario, $correo_electronico, $contrasena_hash, $perfil);
            if ($stmt->execute()) {
                // Enviar correo de notificación de registro
                $mail->clearAddresses();
                $mail->addAddress($correo_electronico);
                $mail->Subject = 'Registro de Usuario';
                $mail->Body = '
                <html>
                <head>
                    <title>Registro de Usuario</title>
                </head>
                <body>
                    <p>Estimado/a ' . $nombre_usuario . ',</p>
                    <p>¡Bienvenido/a a la plataforma de Gestión de Compras Etarey!</p>
                    <p>Su cuenta ha sido creada exitosamente con los siguientes detalles:</p>
                    <ul>
                        <li><strong>Nombre de usuario:</strong> ' . $nombre_usuario . '</li>
                        <li><strong>Correo electrónico:</strong> ' . $correo_electronico . '</li>
                        <li><strong>Contraseña:</strong> ' . $contrasena . ' (Debe cambiarla en su primer inicio de sesión)</li>
                        <li><strong>Perfil:</strong> ' . $perfil . '</li>
                    </ul>
                    <p>Por favor cambie su contraseña en su primer inicio de sesión por motivos de seguridad: <a href="http://localhost/gestioncompras/pages/perfil.php">Cambiar Contraseña</a></p>
                    <p>Saludos cordiales,</p>
                    <p>Equipo de IT Etarey</p>
                </body>
                </html>
                ';
                $mail->isHTML(true);
                $mail->send();
            } else {
                echo "Error al registrar usuario: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Obtener usuarios para mostrar en la tabla
$result = $conn->query("SELECT * FROM usuarios");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuarios</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style_registro.css">
</head>

<body>
    <div class="container mt-5">
        <h1>Registro de Usuarios</h1>
        <form method="post" action="">
            <div class="form-group">
                <label for="nombre_usuario">Nombre de Usuario</label>
                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required>
            </div>
            <div class="form-group">
                <label for="correo_electronico">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" required>
            </div>
            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" required>
            </div>
            <div class="form-group">
                <label for="perfil">Perfil</label>
                <select class="form-control" id="perfil" name="perfil" required>
                    <option value="usuario">Usuario</option>
                    <option value="administrador">Administrador</option>
                    <option value="aprobador">Aprobador</option>
                    <option value="compras">Compras</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>

        <h2 class="mt-5">Usuarios Registrados</h2>
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Usuario</th>
                        <th>Correo Electrónico</th>
                        <th>Perfil</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['nombre_usuario']; ?></td>
                            <td><?php echo $row['correo_electronico']; ?></td>
                            <td><?php echo $row['perfil']; ?></td>
                            <td>
                                <form method="post" action="">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="text" name="nombre_usuario" value="<?php echo $row['nombre_usuario']; ?>" required>
                                    <input type="email" name="correo_electronico" value="<?php echo $row['correo_electronico']; ?>" required>
                                    <input type="password" name="contrasena" placeholder="Nueva contraseña" required>
                                    <select name="perfil" required>
                                        <option value="usuario" <?php if ($row['perfil'] == 'usuario') echo 'selected'; ?>>Usuario</option>
                                        <option value="administrador" <?php if ($row['perfil'] == 'administrador') echo 'selected'; ?>>Administrador</option>
                                        <option value="aprobador" <?php if ($row['perfil'] == 'aprobador') echo 'selected'; ?>>Aprobador</option>
                                        <option value="compras" <?php if ($row['perfil'] == 'compras') echo 'selected'; ?>>Compras</option>
                                    </select>
                                    <button type="submit" name="modificar" class="btn btn-warning">Modificar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay usuarios registrados.</p>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
<?php include '../php/config/footer.php'; ?>

</html>
