
<?php
session_start();
include '../php/config/config.php';
include '../php/auth.php';
verificarSesion(['administrador', 'aprobador', 'compras']);

$sql = "SELECT id, 
               TIMESTAMPDIFF(HOUR, fecha_pendiente, fecha_aprobado) AS tiempo_pendiente_aprobado,
               TIMESTAMPDIFF(HOUR, fecha_aprobado, fecha_cerrado) AS tiempo_aprobado_cerrado 
        FROM solicitudes 
        WHERE fecha_aprobado IS NOT NULL AND fecha_cerrado IS NOT NULL";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>
