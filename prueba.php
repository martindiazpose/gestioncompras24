<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Compras Etarey</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <table id="example" class="display" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Unidad de Trabajo</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>
    </table>

    <script>
    $(document).ready(function() {
        $('#example').DataTable({
            "ajax": {
                "url": "php/obtener_solicitudes.php",
                "dataSrc": ""
            },
            "columns": [
                { "data": "id" },
                { "data": "unidad_trabajo" },
                { "data": "estado" },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        if (row.estado === 'Pendiente') {
                            return `
                                <button onclick="aprobarSolicitud(${row.id})">Aprobar</button>
                                <button onclick="rechazarSolicitud(${row.id})">Rechazar</button>
                            `;
                        } else {
                            return "No hay acciones disponibles";
                        }
                    }
                }
            ]
        });
    });

    function aprobarSolicitud(solicitud_id) {
        $.ajax({
            url: 'php/aprobar_rechazar_solicitud.php',
            method: 'POST',
            data: { solicitud_id: solicitud_id, accion: 'aprobar' },
            success: function(response) {
                alert(response);
                $('#example').DataTable().ajax.reload();
            }
        });
    }

    function rechazarSolicitud(solicitud_id) {
        $.ajax({
            url: 'php/aprobar_rechazar_solicitud.php',
            method: 'POST',
            data: { solicitud_id: solicitud_id, accion: 'rechazar' },
            success: function(response) {
                alert(response);
                $('#example').DataTable().ajax.reload();
            }
        });
    }
    </script>
</body>
</html>
