<?php
session_start();
include '../php/config/config.php';
include '../php/auth.php';
verificarSesion(['administrador']);

$user_role = $_SESSION['perfil'];

// Configuración de la paginación
$correos_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $correos_por_pagina;

// Preparar la consulta para contar el total de registros de correos
$sql_count = "SELECT COUNT(*) AS total FROM registro_correos";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_correos = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_correos / $correos_por_pagina);

// Ejecutar la consulta para obtener los registros de correos con límite
$sql = "SELECT * FROM registro_correos ORDER BY fecha DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $correos_por_pagina, $offset);
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
    <title>Registro de Correos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_other.css">
</head>
<body>
<?php include '../php/config/header.php'; ?>
    <div class="container mt-5">
        <h2>Registro de Correos</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Destinatario</th>
                    <th>Asunto</th>
                    <th>Mensaje</th>
                    <th>Estado</th>
                    <th>Error</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody id="correos-body">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['destinatario']; ?></td>
                        <td><?php echo $row['asunto']; ?></td>
                        <td><?php echo htmlspecialchars(substr($row['mensaje'], 0, 50)) . '...'; ?></td>
                        <td><?php echo $row['estado']; ?></td>
                        <td><?php echo $row['error'] ? $row['error'] : 'N/A'; ?></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($row['fecha'])); ?></td>
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
