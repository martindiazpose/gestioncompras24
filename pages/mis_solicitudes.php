<?php
session_start();
include '../php/config/config.php';
include '../php/auth.php';
verificarSesion(['usuario', 'administrador', 'compras', 'aprobador']);

$user_role = $_SESSION['perfil'];
$user_id = $_SESSION['user_id'];

// Configuración de la paginación
$solicitudes_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $solicitudes_por_pagina;

// Inicialización de la variable para el total de páginas
$total_paginas = 1; // Valor predeterminado

// Contar el total de solicitudes basándonos en el perfil del usuario
if ($user_role == 'compras') {
    $sql_count = "SELECT COUNT(*) AS total 
                  FROM solicitudes s
                  WHERE s.estado IN ('Aprobado', 'Cerrado')";
    $sql = "SELECT s.*, u.nombre_usuario AS nombre_usuario 
            FROM solicitudes s
            JOIN usuarios u ON s.user_id = u.id
            WHERE s.estado IN ('Aprobado', 'Cerrado') 
            ORDER BY s.fecha_creacion DESC 
            LIMIT ? OFFSET ?";
    $params = [$solicitudes_por_pagina, $offset];
    $types = "ii";
} elseif ($user_role == 'administrador') {
    $sql_count = "SELECT COUNT(*) AS total 
                  FROM solicitudes s";
    $sql = "SELECT s.*, u.nombre_usuario AS nombre_usuario 
            FROM solicitudes s
            JOIN usuarios u ON s.user_id = u.id
            ORDER BY s.fecha_creacion DESC 
            LIMIT ? OFFSET ?";
    $params = [$solicitudes_por_pagina, $offset];
    $types = "ii";
} else {
    $sql_count = "SELECT COUNT(*) AS total 
                  FROM solicitudes s
                  WHERE s.user_id = ?";
    $sql = "SELECT s.*, u.nombre_usuario AS nombre_usuario 
            FROM solicitudes s
            JOIN usuarios u ON s.user_id = u.id
            WHERE s.user_id = ? 
            ORDER BY s.fecha_creacion DESC 
            LIMIT ? OFFSET ?";
    $params = [$user_id, $solicitudes_por_pagina, $offset];
    $types = "iii";
}

// Contar el total de solicitudes
$stmt_count = $conn->prepare($sql_count);
if ($user_role == 'compras' || $user_role == 'administrador') {
    $stmt_count->execute();
} else {
    $stmt_count->bind_param("i", $user_id);
    $stmt_count->execute();
}
$total_solicitudes = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_solicitudes / $solicitudes_por_pagina);

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Solicitudes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_other.css">
</head>
<body>
<?php include '../php/config/header.php'; ?>
    <div class="container mt-5">
        <h2>Mis Solicitudes</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Creado Por:</th> <!-- Nueva columna -->
                    <th>Fecha de Creación</th>
                    <th>Estado</th>
                    <th>Unidad de Trabajo</th>
                    <th>Artículos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="solicitudes-body">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['nombre_usuario']; ?></td> <!-- Mostrar el nombre del usuario -->

                        <td><?php echo date("d/m/Y H:i", strtotime($row['fecha_creacion'])); ?></td>
                        <td><?php echo $row['estado']; ?></td>
                        <td><?php echo $row['unidad_trabajo']; ?></td>
                        <td>
                            <?php
                            $articulo_stmt = $conn->prepare("SELECT COUNT(*) AS cantidad_articulos FROM articulos WHERE solicitud_id = ?");
                            $articulo_stmt->bind_param("i", $row['id']);
                            $articulo_stmt->execute();
                            $articulo_result = $articulo_stmt->get_result()->fetch_assoc();
                            echo $articulo_result['cantidad_articulos'];
                            ?>
                        </td>
                        <td>
                            <a href="detalle_solicitud.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-info">Ver Detalle</a>
                            <?php if ($user_role == 'aprobador'): ?>
                                <form method="post" action="../php/procesar_solicitud.php" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <select name="nuevo_estado" required>
                                        <option value="">Cambiar Estado</option>
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="Aprobado">Aprobado</option>
                                        <option value="Rechazado">Rechazado</option>
                                        <option value="Cerrado">Cerrado</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Actualizar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($pagina_actual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>" aria-label="Siguiente">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
</body>
<?php include '../php/config/footer.php'; ?>
</html>

<?php
$conn->close();
?>

