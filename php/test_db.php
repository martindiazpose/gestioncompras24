<?php
session_start();
include 'config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Datos de prueba
    $unidad_trabajo = 'Unidad de Ejemplo';
    $solicitud_id = 1; // Valor de prueba

    // Enviar correo
    enviarCorreoNotificacion($unidad_trabajo, $solicitud_id);
}

function enviarCorreoNotificacion($unidad_trabajo, $solicitud_id) {
    global $conn;
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gestioncomprasetarey@gmail.com';
        $mail->Password = 'wfvw ryxc qfef bozn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('gestioncomprasetarey@gmail.com', 'Gestion Compras Etarey');
        $mail->addAddress('martindiazpose@gmail.com'); // Prueba con una dirección de correo válida

        $mail->isHTML(true);
        $mail->Subject = "Nueva solicitud creada en $unidad_trabajo";
        $mail->Body    = "<p>Prueba de envío de correo.</p>";

        $mail->send();
        echo 'Correo de notificación enviado.';
    } catch (Exception $e) {
        echo "Error al enviar el correo de notificación: {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html>
<body>

<form action="test_db.php" method="post">
    <input type="hidden" name="unit_test" value="1">
    <button type="submit">Enviar Correo de Prueba</button>
</form>

</body>
</html>