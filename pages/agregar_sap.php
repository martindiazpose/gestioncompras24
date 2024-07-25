
<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/auth.php';
verificarSesion(['compras']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $numero_oc = $_POST['numero_oc'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE solicitudes SET numero_oc = ?, estado = 'En tránsito', user_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $numero_oc, $user_id, $id);
    
    if ($stmt->execute()) {
        echo "Número OC agregado y solicitud actualizada a 'En tránsito' con éxito.";
        header("Location: ../pages/mis_solicitudes.php");
    } else {
        echo "Error al actualizar la solicitud: " . $stmt->error;
    }

    $stmt->close();
}
?>