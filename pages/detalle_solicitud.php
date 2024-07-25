<?php
session_start();
include '../php/config/config.php';
include '../php/auth.php';
verificarSesion(['aprobador', 'administrador', 'compras']);

require '../vendor/autoload.php';
$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM solicitudes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$solicitud = $result->fetch_assoc();

if (!$solicitud) {
    echo "Solicitud no encontrada.";
    exit();
}

$articulo_stmt = $conn->prepare("SELECT * FROM articulos WHERE solicitud_id = ?");
$articulo_stmt->bind_param("i", $id);
$articulo_stmt->execute();
$articulos = $articulo_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_estado = $_POST['accion'];
    $timestamp = date('Y-m-d H:i:s');

    if ($nuevo_estado == 'Aprobado') {
        $stmt = $conn->prepare("UPDATE solicitudes SET estado = ?, fecha_aprobado = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nuevo_estado, $timestamp, $id);
    } elseif ($nuevo_estado == 'Rechazado') {
        $stmt = $conn->prepare("UPDATE solicitudes SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $id);
    } elseif ($nuevo_estado == 'En tránsito' && $_SESSION['perfil'] == 'compras') {
        $stmt = $conn->prepare("UPDATE solicitudes SET estado = ?, fecha_en_transito = ?, user_id_asignado = ? WHERE id = ?");
        $stmt->bind_param("ssii", $nuevo_estado, $timestamp, $_SESSION['user_id'], $id);
    } elseif ($nuevo_estado == 'Cerrado' && $_SESSION['perfil'] == 'compras') {
        $numero_oc = $_POST['numero_oc'];  // Cambio de nombre de variable
        $stmt = $conn->prepare("UPDATE solicitudes SET estado = ?, fecha_cerrado = ?, numero_oc = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nuevo_estado, $timestamp, $numero_oc, $id);
    } elseif ($nuevo_estado == 'Cierre Parcial' && $_SESSION['perfil'] == 'compras') {
        $stmt = $conn->prepare("UPDATE solicitudes SET estado = 'Cierre Parcial', fecha_cerrado = ? WHERE id = ?");
        $stmt->bind_param("si", $timestamp, $id);
        if ($stmt->execute()) {
            // Marcar artículos como llegados
            if (!empty($_POST['articulos'])) {
                $articulos_llegados = $_POST['articulos'];
                foreach ($articulos_llegados as $articulo_id) {
                    $update_stmt = $conn->prepare("UPDATE articulos SET estado = 'Llegado' WHERE id = ?");
                    $update_stmt->bind_param("i", $articulo_id);
                    $update_stmt->execute();
                }
            }
            echo "Solicitud cerrada parcialmente con éxito.";
            header("Location: detalle_solicitud.php?id=$id");
            exit();
        } else {
            echo "Error al actualizar la solicitud: " . $stmt->error;
        }
    }

    if ($stmt->execute()) {
        if ($nuevo_estado == 'En tránsito' || $nuevo_estado == 'Cerrado') {
            enviarCorreoEstado($solicitud['user_id'], $nuevo_estado);
        }
        echo "Solicitud $nuevo_estado con éxito.";
        header("Location: detalle_solicitud.php?id=$id");
        exit();
    } else {
        echo "Error al actualizar la solicitud: " . $stmt->error;
    }
}

function enviarCorreoEstado($user_id, $estado)
{
    global $conn;
    $stmt = $conn->prepare("SELECT correo_electronico FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if ($usuario) {
        $correo = $usuario['correo_electronico'];
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gestioncomprasetarey@gmail.com';
            $mail->Password = 'iukl vhlo cvge qmjy';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('gestioncomprasetarey@gmail.com', 'Gestion Compras Etarey');
            $mail->addAddress($correo);
            $mail->isHTML(true);
            $mail->Subject = "Estado de Solicitud Actualizado";
            $mail->Body = "El estado de su solicitud ha sido actualizado a: $estado. Por favor, valide que los artículos hayan llegado.";
            $mail->send();
        } catch (Exception $e) {
            echo "Error al enviar el correo: {$mail->ErrorInfo}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Solicitud</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_other.css">
</head>

<body>
    <?php include '../php/config/header.php'; ?>
    <div class="container mt-5">
        <h2>Detalle de Solicitud</h2>
        <form>
            <div class="form-group">
                <label>Unidad de Trabajo:</label>
                <input type="text" class="form-control" value="<?php echo $solicitud['unidad_trabajo']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Centro de costo:</label>
                <input type="text" class="form-control" value="<?php echo $solicitud['centro_costo']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Prioridad:</label>
                <input type="text" class="form-control" value="<?php echo $solicitud['prioridad']; ?>" readonly>
            </div>
            <div class="form-group">
                <label>Proveedor sugerido:</label>
                <input type="text" class="form-control" value="<?php echo $solicitud['proveedor_sugerido']; ?>" readonly>
            </div>
            <?php if (!empty($solicitud['comentarios'])): ?>
                <div class="form-group">
                    <label>Comentarios:</label>
                    <textarea class="form-control" rows="5" readonly><?php echo $solicitud['comentarios']; ?></textarea>
                </div>
            <?php endif; ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Cantidad</th>
                        <th>Artículo</th>
                        <th>Color</th>
                        <th>Dimensiones</th>
                        <th>Precio estimado</th>
                        <th>Centro de Costo</th>
                        <th>Comentarios</th>
                        <th>Imagen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articulos as $articulo): ?>
                        <tr>
                            <td><?php echo $articulo['material']; ?></td>
                            <td><?php echo $articulo['cantidad']; ?></td>
                            <td><?php echo $articulo['articulo']; ?></td>
                            <td><?php echo $articulo['color']; ?></td>
                            <td><?php echo $articulo['dimensiones']; ?></td>
                            <td><?php echo $articulo['precio_estimado']; ?></td>
                            <td><?php echo $articulo['centro_costo']; ?></td>
                            <td><?php echo $articulo['comentarios_articulo']; ?></td>
                            <td>
                                <?php if ($articulo['imagen']): ?>
                                    <a href="<?php echo $articulo['imagen']; ?>" target="_blank">Ver Imagen</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-group">
                <label>Estado:</label>
                <input type="text" class="form-control" value="<?php echo $solicitud['estado']; ?>" readonly>
            </div>
            <?php if ($solicitud['estado'] == 'Cerrado'): ?>
                <div class="form-group">
                    <label>Número OC:</label>
                    <input type="text" class="form-control" value="<?php echo $solicitud['numero_oc']; ?>" readonly>
                </div>
            <?php endif; ?>
        </form>
        <form method="post" action="../generar_pdf.php">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <button type="submit" class="btn btn-primary">Generar PDF</button>
        </form>
        <?php if ($solicitud['estado'] == 'Pendiente' || $solicitud['estado'] == 'Aprobado'): ?>
            <form method="post" action="" class="mt-3">
                <?php if ($solicitud['estado'] == 'Pendiente'): ?>
                    <button type="submit" name="accion" value="Aprobado" class="btn btn-success">Aprobar</button>
                    <button type="submit" name="accion" value="Rechazado" class="btn btn-danger">Rechazar</button>
                <?php elseif ($solicitud['estado'] == 'Aprobado'): ?>
                    <div class="form-group">
                        <label for="numero_oc">Número OC:</label>
                        <input type="text" class="form-control" name="numero_oc" required>
                    </div>
                    <button type="submit" name="accion" value="Cerrado" class="btn btn-primary">Cerrar</button>
                <?php endif; ?>
            </form>
        <?php elseif ($solicitud['estado'] == 'En tránsito'): ?>
            <form method="post" action="" class="mt-3">
                <div class="form-group">
                    <label>Confirmar llegada de artículos:</label>
                    <div>
                        <?php foreach ($articulos as $articulo): ?>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="articulos[]" value="<?php echo $articulo['id']; ?>">
                                <label class="form-check-label"><?php echo $articulo['articulo']; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" name="accion" value="Cerrado" class="btn btn-primary">Cerrar</button>
                <button type="submit" name="accion" value="Cierre Parcial" class="btn btn-warning">Cerrar Parcial</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>
