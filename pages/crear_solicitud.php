
<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/auth.php';
verificarSesion(['usuario', 'administrador', 'compras', 'aprobador']);
include $_SERVER['DOCUMENT_ROOT'] . '/gestioncompras/php/config/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Solicitud</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style_crear.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Crear Solicitud</h2>
        <form action="/gestioncompras/php/procesar_solicitud.php" method="post" enctype="multipart/form-data">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="unidad_trabajo" id="mantenimiento" value="Mantenimiento">
                <label class="form-check-label" for="mantenimiento">Mantenimiento</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="unidad_trabajo" id="Servicio" value="Servicio" checked>
                <label class="form-check-label" for="Servicio">Servicio</label>
            </div>
            <hr>

            <div id="articulos-container">
                <div class="articulo">
                    <div class="row">
                        <div class="col-md-6">
                            <h2>Material*</h2>
                            <input class="form-control" type="text" name="material[]" required>
                        </div>
                        <div class="col-md-6">
                            <h2>Cantidad*</h2>
                            <input class="form-control" type="number" name="cantidad[]" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h2>Articulo</h2>
                            <input class="form-control" type="text" name="articulo[]">
                        </div>
                        <div class="col-md-6">
                            <h2>Color</h2>
                            <input class="form-control" type="text" name="color[]">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h2>Dimensiones</h2>
                            <input class="form-control" type="text" name="dimensiones[]">
                        </div>
                        <div class="col-md-6">
                            <h2>Precio estimado*</h2>
                            <input class="form-control" type="text" name="precio_estimado[]" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h2>Centro de costo*</h2>
                            <select class="form-control" name="centro_costo[]" required>
                                <option disabled selected>Seleccionar...</option>
                                <option value="Mantenimiento">Mantenimiento</option>
                                <option value="Lavanderia">Lavanderia</option>
                                <option value="PDR">PDR</option>
                                <option value="TFP">TFP</option>
                                <option value="LNN">LNN</option>
                                <option value="Limpieza">Limpieza</option>
                                <option value="Alimentacion">Alimentacion</option>
                                <option value="Gastos generales">Gastos generales</option>
                                <option value="Economato">Economato</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <h2>Adjuntar Imagen</h2>
                            <input class="form-control" type="file" name="imagen[]">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h2>Comentarios/detalles del articulo</h2>
                            <textarea class="form-control" name="comentarios_articulo[]" cols="50" rows="3"></textarea>
                        </div>
                    </div>
                    <hr>
                </div>
            </div>

            <button type="button" class="btn btn-secondary mt-3" id="agregar-articulo">Agregar artículo</button>
            <hr>

            <div class="row">
                <div class="col-md-6">
                    <h2>Proveedor sugerido</h2>
                    <input class="form-control" type="text" name="proveedor_sugerido">
                </div>
                <div class="col-md-6">
                    <h2>Prioridad*</h2>
                    <select class="form-control" name="prioridad" required>
                        <option disabled selected>Seleccionar...</option>
                        <option value="Baja">Baja</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>
            </div>
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="notificar" value="1" id="flexCheckIndeterminate">
                <label class="form-check-label" for="flexCheckIndeterminate">Notificar por correo electrónico</label>
            </div>
            <button class="btn btn-warning mt-3" type="submit">Enviar solicitud</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2"
        crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
        crossorigin="anonymous"></script>

    <script>
        $(document).ready(function() {
            $('#agregar-articulo').click(function() {
                var articuloClone = $('.articulo:first').clone();
                articuloClone.find('input').val(''); // Clear the cloned inputs
                articuloClone.find('textarea').val(''); // Clear the cloned textareas
                articuloClone.find('select').val(''); // Clear the cloned selects
                $('#articulos-container').append(articuloClone);
            });
        });
    </script>
</body>
<?php include '../php/config/footer.php'; ?>
</html>
