<?php
include 'config/config.php';
include 'auth.php';
verificarSesion();

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM solicitudes WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$solicitudes = array();
while ($row = $result->fetch_assoc()) {
    $solicitudes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($solicitudes);

$stmt->close();
$conn->close();
?>
