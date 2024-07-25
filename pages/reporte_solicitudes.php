
<?php
session_start();
include '../php/config/config.php';
include '../php/auth.php';
verificarSesion(['administrador']);

date_default_timezone_set('America/Montevideo'); 

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

$estado_counts = [];
$estado_query = "SELECT estado, COUNT(*) AS count FROM solicitudes GROUP BY estado";
if ($estado_result = $conn->query($estado_query)) {
    while ($estado_row = $estado_result->fetch_assoc()) {
        $estado_counts[$estado_row['estado']] = $estado_row['count'];
    }
    $estado_result->free();
}

$month_filter = '';
if ($selected_month) {
    $month_filter = " AND DATE_FORMAT(s.fecha_creacion, '%Y-%m') = '$selected_month'";
}

$sql = "
    SELECT s.id, 
           s.unidad_trabajo,
           a.articulo AS articulo, 
           a.cantidad, 
           s.estado, 
           s.fecha_creacion, 
           s.fecha_aprobado, 
           s.fecha_cerrado, 
           TIMESTAMPDIFF(MINUTE, s.fecha_creacion, s.fecha_aprobado) AS minutos_pendiente,
           TIMESTAMPDIFF(MINUTE, s.fecha_aprobado, s.fecha_cerrado) AS minutos_aprobado,
           TIMESTAMPDIFF(MINUTE, s.fecha_creacion, s.fecha_cerrado) AS minutos_totales
    FROM solicitudes s
    LEFT JOIN articulos a ON s.id = a.solicitud_id
    WHERE s.estado IN ('Aprobado', 'Cerrado', 'Cerrado Parcialmente')
    $month_filter
    ORDER BY s.fecha_creacion DESC
    LIMIT 20
";

$result = $conn->query($sql);

if (!function_exists('formatTime')) {
    function formatTime($minutes)
    {
        if ($minutes === null) {
            return 'N/A';
        }
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return $hours . ' hr ' . $remainingMinutes . ' min';
    }
}

$months_query = "SELECT DISTINCT DATE_FORMAT(fecha_creacion, '%Y-%m') AS month FROM solicitudes ORDER BY month DESC";
$months_result = $conn->query($months_query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiempo de Solicitudes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_other.css">
    <style>
        .rojo {
            background-color: #f8d7da;
        }

        .amarillo {
            background-color: #fff3cd;
        }

        .verde {
            background-color: #d4edda;
        }
    </style>
    <style>
    #estadoChart {
        width: 100% !important;  /* Ajusta el ancho al 50% del contenedor */
        height: 200 !important; /* Ajusta la altura a 250px */
    }
</style>
</head>
<body>
    <?php include '../php/config/header.php'; ?>
    <div class="container mt-5">
        <h2>Reporte de Tiempos</h2>

        <form method="GET" action="" class="mb-3">
            <div class="form-group">
                <label for="month">Seleccionar Mes:</label>
                <select id="month" name="month" class="form-control">
                    <option value="">-- Todos los Meses --</option>
                    <?php while ($month_row = $months_result->fetch_assoc()): ?>
                        <option value="<?php echo $month_row['month']; ?>" <?php echo ($selected_month === $month_row['month']) ? 'selected' : ''; ?>>
                            <?php echo date("F Y", strtotime($month_row['month'] . '-01')); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Unidad de Trabajo</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                    <th>Fecha de Creación</th>
                    <th>Fecha de Aprobación</th>
                    <th>Fecha de Cierre</th>
                    <th>Tiempo Pendiente</th>
                    <th>Tiempo Aprobado</th>
                    <th>Tiempo Total</th>
                    <th>Imagen</th>
                    <th>Seleccionar</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    $clase = '';
                    if ($row['minutos_totales'] > 1440) {
                        $clase = 'rojo';
                    } elseif ($row['minutos_totales'] > 720) {
                        $clase = 'amarillo';
                    } else {
                        $clase = 'verde';
                    }
                ?>
                    <tr class="<?php echo $clase; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['unidad_trabajo']; ?></td>
                        <td><?php echo $row['articulo']; ?></td>
                        <td><?php echo $row['cantidad']; ?></td>
                        <td><?php echo $row['estado']; ?></td>
                        <td><?php echo date("Y-m-d H:i:s", strtotime($row['fecha_creacion'])); ?></td>
                        <td><?php echo $row['fecha_aprobado'] ? date("Y-m-d H:i:s", strtotime($row['fecha_aprobado'])) : 'N/A'; ?></td>
                        <td><?php echo $row['fecha_cerrado'] ? date("Y-m-d H:i:s", strtotime($row['fecha_cerrado'])) : 'N/A'; ?></td>
                        <td><?php echo formatTime($row['minutos_pendiente']); ?></td>
                        <td><?php echo formatTime($row['minutos_aprobado']); ?></td>
                        <td><?php echo formatTime($row['minutos_totales']); ?></td>
                        <td><img src="ruta/a/imagen/<?php echo $row['id']; ?>.jpg" alt="Imagen"></td>
                        <td><input type="checkbox" name="seleccionar[]" value="<?php echo $row['id']; ?>"></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h3 class="mt-5">Distribución de Estados de Solicitud</h3>
        <canvas id="estadoChart"></canvas>

        <div class="mt-4">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('estadoChart').getContext('2d');
        var estadoCounts = <?php echo json_encode($estado_counts); ?>;
        var labels = Object.keys(estadoCounts);
        var data = Object.values(estadoCounts);

        var estadoChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#E7E9ED',
                        '#4BC0C0'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                if (label) {
                                    label += ': ' + context.raw;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
<?php include '../php/config/footer.php'; ?>
</html>

<?php
$conn->close();
?>
