<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/auth.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function enviarNotificacion($solicitud, $estado) {
    $mail = new PHPMailer(true);
    $correo_aprobador = obtenerCorreoAprobador($solicitud['id']);
    $correo_compras = obtenerCorreoCompras();

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gestioncomprasetarey@gmail.com';
        $mail->Password = 'iukl vhlo cvge qmjy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Configuración del remitente
        $mail->setFrom('gestioncomprasetarey@gmail.com', 'Gestion Compras Etarey');

        // Añadir destinatarios
        $mail->addAddress($correo_aprobador);
        $mail->addAddress($correo_compras);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = "Aviso de Solicitud $estado";
        $mail->Body = "
        <p>Le informamos que la solicitud con ID {$solicitud['id']} está en estado $estado.</p>
        <ul>
        <li><strong>Unidad de Trabajo:</strong> {$solicitud['unidad_trabajo']}</li>
        <li><strong>Artículo:</strong> {$solicitud['articulo']}</li>
        <li><strong>Cantidad:</strong> {$solicitud['cantidad']}</li>
        <li><strong>Prioridad:</strong> {$solicitud['prioridad']}</li>
        <li><strong>Comentarios:</strong> {$solicitud['comentarios']}</li>
        </ul>
        <p>Equipo de Gestión de Compras Etarey</p>";

        $mail->send();
    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }
}

function obtenerCorreoAprobador($solicitud_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT u.correo_electronico FROM usuarios u JOIN solicitudes s ON u.id = s.user_id WHERE s.id = ?");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    return $usuario['correo_electronico'];
}

function obtenerCorreoCompras() {
    // Aquí puedes definir los correos electrónicos de los usuarios de compras
    return 'correo_compras@example.com';
}

// Consultar solicitudes pendientes
$sql_pendientes = "SELECT * FROM solicitudes WHERE estado = 'Pendiente'";
$result_pendientes = $conn->query($sql_pendientes);
while ($solicitud = $result_pendientes->fetch_assoc()) {
    enviarNotificacion($solicitud, 'Pendiente');
}

// Consultar solicitudes aprobadas
$sql_aprobadas = "SELECT * FROM solicitudes WHERE estado = 'Aprobado'";
$result_aprobadas = $conn->query($sql_aprobadas);
while ($solicitud = $result_aprobadas->fetch_assoc()) {
    enviarNotificacion($solicitud, 'Aprobado');
}

$conn->close();
?>
