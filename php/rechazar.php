<?php
$stmt = $conn->prepare("UPDATE solicitudes SET estado = 'Rechazado' WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
?>