<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../vendor/autoload.php';
include '../php/config/config.php';
include '../php/auth.php';
verificarSesion(['usuario', 'administrador', 'aprobador', 'compras']);

$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && isset($_POST['id'])) {
    $nuevo_estado = $_POST['accion'];
    $id_solicitud = $_POST['id'];
    $timestamp = date('Y-m-d H:i:s');

    if ($nuevo_estado == 'Aprobado') {
        $stmt = $conn->prepare("UPDATE solicitudes SET estado = ?, fecha_aprobado = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nuevo_estado, $timestamp, $id_solicitud);
    } elseif ($nuevo_estado == 'Rechazado') {
        $stmt = $conn->prepare("UPDATE solicitudes SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $id_solicitud);
    } elseif ($nuevo_estado == 'En Transito' && isset($_POST['numero_oc'])) {
        $numero_oc = $_POST['numero_oc'];
        
        // Validación del número de OC
        if (empty($numero_oc) || !preg_match("/^[A-Za-z0-9-]+$/", $numero_oc)) {
            $error_message = "Número de OC inválido.";
        } else {
            $stmt = $conn->prepare("UPDATE solicitudes SET estado = ?, numero_oc = ?, fecha_transito = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nuevo_estado, $numero_oc, $timestamp, $id_solicitud);
        }
    }

    if (empty($error_message) && $stmt->execute()) {
        $success_message = "Solicitud $nuevo_estado con éxito.";
        enviarCorreoEstado($id_solicitud, $nuevo_estado);
        header("Location: solicitudes_para_aprobar.php");
        exit();
    } else {
        $error_message = $error_message ?: "Error al actualizar la solicitud: " . $stmt->error;
    }
}

function enviarCorreoEstado($id_solicitud, $estado) {
    global $conn;
    $stmt = $conn->prepare("SELECT u.correo_electronico FROM usuarios u JOIN solicitudes s ON u.id = s.user_id WHERE s.id = ?");
    $stmt->bind_param("i", $id_solicitud);
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
            $mail->Body = "El estado de su solicitud ha sido actualizado a: $estado.";

            $mail->send();
        } catch (Exception $e) {
            echo "Error al enviar el correo: {$mail->ErrorInfo}";
        }
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM solicitudes";
$firstCondition = true;
$params = [];
$types = "";

if (!empty($_GET['fecha'])) {
    $sql .= $firstCondition ? " WHERE" : " AND";
    $sql .= " DATE(fecha_creacion) = ?";
    $params[] = $_GET['fecha'];
    $types .= "s";
    $firstCondition = false;
}

if (!empty($_GET['prioridad'])) {
    $sql .= $firstCondition ? " WHERE" : " AND";
    $sql .= " prioridad = ?";
    $params[] = $_GET['prioridad'];
    $types .= "s";
    $firstCondition = false;
}

if (!empty($_GET['estado'])) {
    $sql .= $firstCondition ? " WHERE" : " AND";
    $sql .= " estado = ?";
    $params[] = $_GET['estado'];
    $types .= "s";
    $firstCondition = false;
}

if (!empty($_GET['unidad_trabajo'])) {
    $sql .= $firstCondition ? " WHERE" : " AND";
    $sql .= " unidad_trabajo = ?";
    $params[] = $_GET['unidad_trabajo'];
    $types .= "s";
}

$sql .= " ORDER BY fecha_creacion DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Para la paginación
$count_sql = "SELECT COUNT(*) as total FROM solicitudes";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_solicitudes = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_solicitudes / $limit);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes para Aprobar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_other.css">
</head>
<body>
<?php include '../php/config/header.php'; ?>
    <div class="container mt-5">
        <h2>Solicitudes para Aprobar</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="filter-section mb-4">
            <form action="" method="get" class="form-inline justify-content-center">
                <div class="form-group mx-2">
                    <label for="fecha" class="mx-1">Fecha:</label>
                    <input type="date" name="fecha" class="form-control" placeholder="Fecha de Creación">
                </div>
                <div class="form-group mx-2">
                    <label for="prioridad" class="mx-1">Prioridad:</label>
                    <select name="prioridad" class="form-control">
                        <option value="">Todas</option>
                        <option value="Baja">Baja</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>
                <div class="form-group mx-2">
                    <label for="estado" class="mx-1">Estado:</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Aprobado">Aprobado</option>
                        <option value="Rechazado">Rechazado</option>
                        <option value="En Transito">En Transito</option>
                        <option value="Cerrado">Cerrado</option>
                        <option value="Culminado">Culminado</option>
                    </select>
                </div>
                <div class="form-group mx-2">
                    <label for="unidad_trabajo" class="mx-1">Unidad de Trabajo:</label>
                    <input type="text" name="unidad_trabajo" class="form-control" placeholder="Unidad de Trabajo">
                </div>
                <button type="submit" class="btn btn-primary mx-2">Filtrar</button>
            </form>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha Creación</th>
                    <th>Estado</th>
                    <th>Unidad de Trabajo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['fecha_creacion']) ?></td>
                        <td><?= htmlspecialchars($row['estado']) ?></td>
                        <td><?= htmlspecialchars($row['unidad_trabajo']) ?></td>
                        <td>
                            <a href="detalle_solicitud.php?id=<?= htmlspecialchars($row['id']) ?>" target="_blank" class="btn btn-info btn-sm">Detalles</a>
                            <form action="" method="post" class="d-inline">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                <?php if ($row['estado'] == 'Pendiente'): ?>
                                    <button type="submit" name="accion" value="Aprobado" class="btn btn-success btn-sm">Aprobar</button>
                                    <button type="submit" name="accion" value="Rechazado" class="btn btn-danger btn-sm">Rechazar</button>
                                <?php elseif ($row['estado'] == 'Aprobado'): ?>
                                    <button type="submit" name="accion" value="En Transito" class="btn btn-warning btn-sm">Marcar en Transito</button>
                                    <input type="text" name="numero_oc" placeholder="Número OC" class="form-control form-control-sm">
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 . buildQueryString() ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i . buildQueryString() ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 . buildQueryString() ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js" integrity="sha384-oBqDVmMz4fnFO9c+fiKnc7ECv5bQ8MBbO8fP9j+5HIQTYd/f8j8fRb3s20Y5pF7h" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz4fnFO9c+fiKnc7ECv5bQ8MBbO8fP9j+5HIQTYd/f8j8fRb3s20Y5pF7h" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-2S1p7F9bGdSG2Y71Nxk6z9cLgT3mW62wHlL5XbTgb1Q9L+yoF6KnG6GzmMDqk31eg" crossorigin="anonymous"></script>
</body>
</html>

<?php
function buildQueryString() {
    $params = ['fecha', 'prioridad', 'estado', 'unidad_trabajo'];
    $query_string = '';
    foreach ($params as $param) {
        if (!empty($_GET[$param])) {
            $query_string .= "&$param=" . urlencode($_GET[$param]);
        }
    }
    return $query_string;
}
?>

