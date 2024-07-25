
<?php
require_once __DIR__ . '/config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function verificarSesion($rolesPermitidos = []) {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['perfil'], $rolesPermitidos)) {
        header('Location: /GESTIONCOMPRAS/pages/unauthorized.php');
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, contrasena, perfil FROM usuarios WHERE nombre_usuario = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hash, $perfil);
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['perfil'] = $perfil;

            switch ($perfil) {
                case 'administrador':
                    header('Location: /GESTIONCOMPRAS/pages/crear_solicitud.php');
                    break;
                case 'aprobador':
                    header('Location: /GESTIONCOMPRAS/pages/solicitudes_para_aprobar.php');
                    break;
                case 'usuario':
                    header('Location: /GESTIONCOMPRAS/pages/crear_solicitud.php');
                    break;
                case 'compras':
                    header('Location: /GESTIONCOMPRAS/pages/mis_solicitudes.php');
                    break;
                default:
                    header('Location: /GESTIONCOMPRAS/index.php');
                    break;
            }
            exit();
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Nombre de usuario no encontrado.";
    }
    $stmt->close();
}
?>
