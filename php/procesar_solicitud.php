
<?php
session_start();
include 'config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Función para enviar correo de notificación
function enviarCorreoNotificacion($unidad_trabajo, $articulo, $cantidad, $material, $color, $dimensiones, $prioridad, $proveedor_sugerido, $precio_estimado, $centro_costo, $comentarios, $user_id, $estado) {
    global $conn;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gestioncomprasetarey@gmail.com';
        $mail->Password = 'npzw zkhl euqb xoso';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('gestioncomprasetarey@gmail.com', 'Gestion Compras Etarey');

        // Obtener correos de aprobadores y el correo del usuario que creó la solicitud
        $correosAprobadores = obtenerCorreosPorPerfil('aprobador');
        $correoUsuario = obtenerCorreoUsuario($user_id);

        foreach ($correosAprobadores as $correo) {
            $mail->addAddress($correo);
        }
        $mail->addAddress($correoUsuario);

        // Convertir arrays a cadenas
        $articulo = implode(', ', (array)$articulo);
        $cantidad = implode(', ', (array)$cantidad);
        $material = implode(', ', (array)$material);
        $color = implode(', ', (array)$color);
        $dimensiones = implode(', ', (array)$dimensiones);
        $prioridad = implode(', ', (array)$prioridad);
        $proveedor_sugerido = implode(', ', (array)$proveedor_sugerido);
        $precio_estimado = implode(', ', (array)$precio_estimado);
        $centro_costo = implode(', ', (array)$centro_costo);
        $comentarios = implode(', ', (array)$comentarios);

        $mail->isHTML(true);
        $mail->Subject = "Estado de la solicitud en $unidad_trabajo ha cambiado a $estado";
        $mail->Body = "
        <p>Le informamos que el estado de la solicitud en el sistema de Gestión de Compras Etarey ha cambiado.</p>
        <h3>Detalles de la solicitud:</h3>
        <ul>
        <li><strong>Unidad de Trabajo:</strong> $unidad_trabajo</li>
        <li><strong>Articulo:</strong> $articulo</li>
        <li><strong>Cantidad:</strong> $cantidad</li>
        <li><strong>Material:</strong> $material</li>
        <li><strong>Color:</strong> $color</li>
        <li><strong>Dimensiones:</strong> $dimensiones</li>
        <li><strong>Prioridad:</strong> $prioridad</li>
        <li><strong>Proveedor Sugerido:</strong> $proveedor_sugerido</li>
        <li><strong>Precio Estimado:</strong> $precio_estimado</li>
        <li><strong>Centro de Costo:</strong> $centro_costo</li>
        <li><strong>Comentarios:</strong> $comentarios</li>
        <li><strong>Estado:</strong> $estado</li>
        </ul>
        <h4>Puede verificar la solicitud ingresando en <a href='http://localhost/GESTIONCOMPRAS/index.php'>http://localhost/GESTIONCOMPRAS/index.php</a> ingresando en Mis Solicitudes.</h4>
        <h5> Este correo se generó de manera automática, no lo responda.</h5>
        <p>Equipo de Gestión de Compras Etarey</p>";

        $mail->send();
        registrarCorreo($correoUsuario, $mail->Subject, $mail->Body, 'Enviado');
        foreach ($correosAprobadores as $correo) {
            registrarCorreo($correo, $mail->Subject, $mail->Body, 'Enviado');
        }
        echo 'Correo de notificación enviado.';
    } catch (Exception $e) {
        $error = $mail->ErrorInfo;
        registrarCorreo($correoUsuario, $mail->Subject, $mail->Body, 'Error', $error);
        foreach ($correosAprobadores as $correo) {
            registrarCorreo($correo, $mail->Subject, $mail->Body, 'Error', $error);
        }
        echo "Error al enviar el correo de notificación: {$mail->ErrorInfo}";
    }
}

// Función para obtener correos por perfil
function obtenerCorreosPorPerfil($perfil) {
    global $conn;
    $stmt = $conn->prepare("SELECT correo_electronico FROM usuarios WHERE perfil = ?");
    $stmt->bind_param("s", $perfil);
    $stmt->execute();
    $result = $stmt->get_result();
    $correos = [];
    while ($row = $result->fetch_assoc()) {
        $correos[] = $row['correo_electronico'];
    }
    $stmt->close();
    return $correos;
}

// Función para obtener el correo de un usuario
function obtenerCorreoUsuario($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT correo_electronico FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($correo);
    $stmt->fetch();
    $stmt->close();
    return $correo;
}

// Función para registrar el envío del correo en la base de datos
function registrarCorreo($destinatario, $asunto, $mensaje, $estado, $error = null) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO registro_correos (destinatario, asunto, mensaje, estado, error) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $destinatario, $asunto, $mensaje, $estado, $error);
    $stmt->execute();
    $stmt->close();
}

// Función para aprobar una solicitud
function aprobarSolicitud($solicitud_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE solicitudes SET estado = 'Aprobado' WHERE id = ?");
    $stmt->bind_param("i", $solicitud_id);
    if ($stmt->execute()) {
        $solicitud = obtenerDetallesSolicitud($solicitud_id);
        $articulos = obtenerArticulosPorSolicitud($solicitud_id);

        enviarCorreoNotificacion(
            $solicitud['unidad_trabajo'],
            array_column($articulos, 'articulo'),
            array_column($articulos, 'cantidad'),
            array_column($articulos, 'material'),
            array_column($articulos, 'color'),
            array_column($articulos, 'dimensiones'),
            $solicitud['prioridad'],
            $solicitud['proveedor_sugerido'],
            array_column($articulos, 'precio_estimado'),
            $solicitud['centro_costo'],
            $solicitud['comentarios'],
            $solicitud['user_id'],
            'Aprobado'
        );
        echo "Solicitud aprobada y correo de notificación enviado.";
    } else {
        echo "Error al aprobar la solicitud.";
    }
    $stmt->close();
}

// Función para rechazar una solicitud
function rechazarSolicitud($solicitud_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE solicitudes SET estado = 'Rechazado' WHERE id = ?");
    $stmt->bind_param("i", $solicitud_id);
    if ($stmt->execute()) {
        $solicitud = obtenerDetallesSolicitud($solicitud_id);
        $articulos = obtenerArticulosPorSolicitud($solicitud_id);

        enviarCorreoNotificacion(
            $solicitud['unidad_trabajo'],
            array_column($articulos, 'articulo'),
            array_column($articulos, 'cantidad'),
            array_column($articulos, 'material'),
            array_column($articulos, 'color'),
            array_column($articulos, 'dimensiones'),
            $solicitud['prioridad'],
            $solicitud['proveedor_sugerido'],
            array_column($articulos, 'precio_estimado'),
            $solicitud['centro_costo'],
            $solicitud['comentarios'],
            $solicitud['user_id'],
            'Rechazado'
        );
        echo "Solicitud rechazada y correo de notificación enviado.";
    } else {
        echo "Error al rechazar la solicitud.";
    }
    $stmt->close();
}

// Función para obtener detalles de una solicitud
function obtenerDetallesSolicitud($solicitud_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM solicitudes WHERE id = ?");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitud = $result->fetch_assoc();
    $stmt->close();
    return $solicitud;
}

// Obtener artículos de una solicitud
function obtenerArticulosPorSolicitud($solicitud_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM articulos WHERE solicitud_id = ?");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $articulos = [];
    while ($row = $result->fetch_assoc()) {
        $articulos[] = $row;
    }
    $stmt->close();
    return $articulos;
}

// Ejemplo de uso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    if (isset($_POST['accion']) && isset($_POST['solicitud_id'])) {
        $accion = $_POST['accion'];
        $solicitud_id = $_POST['solicitud_id'];
    
        if ($accion === 'aprobar') {
            aprobarSolicitud($solicitud_id);
        } elseif ($accion === 'rechazar') {
            rechazarSolicitud($solicitud_id);
        } else {
            echo "Acción no reconocida.";
        }
    } else {
        echo "Faltan parámetros en la solicitud.";
    }
}
?>
