
<?php
session_start();
ob_start(); // Iniciar la captura de la salida

include 'php/config/config.php';
include 'php/auth.php';
verificarSesion(['aprobador', 'administrador', 'compras']);

require 'vendor/autoload.php';
require('fpdf/fpdf.php');

$id = $_POST['id'];
$stmt = $conn->prepare("SELECT * FROM solicitudes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$solicitud = $result->fetch_assoc();

if (!$solicitud) {
    echo "Solicitud no encontrada.";
    ob_end_flush(); // Enviar y terminar la salida
    exit();
}

$articulo_stmt = $conn->prepare("SELECT * FROM articulos WHERE solicitud_id = ?");
$articulo_stmt->bind_param("i", $id);
$articulo_stmt->execute();
$articulos = $articulo_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'Detalle de Solicitud', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }

    function ChapterTitle($title) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        $this->Ln(4);
    }

    function ChapterBody($body) {
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 10, utf8_decode($body));
        $this->Ln();
    }

    function Table($header, $data) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(200, 220, 255); // Color de fondo de la cabecera
        $this->SetTextColor(0); // Color del texto
        $this->SetDrawColor(0, 0, 0); // Color del borde
        $this->SetLineWidth(.3); // Grosor del borde

        // Cabecera
        $w = array(30, 20, 30, 20, 30, 30, 50); // Anchos de las columnas
        for($i=0; $i<count($header); $i++)
            $this->Cell($w[$i], 7, utf8_decode($header[$i]), 1, 0, 'C', true);
        $this->Ln();

        // Datos
        $this->SetFont('Arial', '', 10);
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $fill = false;
        foreach($data as $row) {
            $this->Cell($w[0], 6, utf8_decode($row[0]), 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 6, $row[1], 'LR', 0, 'C', $fill);
            $this->Cell($w[2], 6, utf8_decode($row[2]), 'LR', 0, 'L', $fill);
            $this->Cell($w[3], 6, utf8_decode($row[3]), 'LR', 0, 'C', $fill);
            $this->Cell($w[4], 6, utf8_decode($row[4]), 'LR', 0, 'L', $fill);
            $this->Cell($w[5], 6, $row[5], 'LR', 0, 'R', $fill);
            $this->Cell($w[6], 6, utf8_decode($row[6]), 'LR', 0, 'L', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

$pdf->ChapterTitle('Unidad de Trabajo:');
$pdf->ChapterBody($solicitud['unidad_trabajo']);

$pdf->ChapterTitle('Centro de costo:');
$pdf->ChapterBody($solicitud['centro_costo']);

$pdf->ChapterTitle('Prioridad:');
$pdf->ChapterBody($solicitud['prioridad']);

$pdf->ChapterTitle('Proveedor sugerido:');
$pdf->ChapterBody($solicitud['proveedor_sugerido']);

if (!empty($solicitud['comentarios'])) {
    $pdf->ChapterTitle('Comentarios:');
    $pdf->ChapterBody($solicitud['comentarios']);
}

$pdf->ChapterTitle('Articulos:');
$header = array('Material', 'Cantidad', 'Artículo', 'Color', 'Dimensiones', 'Precio estimado', 'Comentarios');
$data = [];
foreach ($articulos as $articulo) {
    $data[] = [
        $articulo['material'],
        $articulo['cantidad'],
        $articulo['articulo'],
        $articulo['color'],
        $articulo['dimensiones'],
        $articulo['precio_estimado'],
        $articulo['comentarios_articulo']
    ];
}
$pdf->Table($header, $data);

$pdf->ChapterTitle('Estado:');
$pdf->ChapterBody($solicitud['estado']);

if ($solicitud['estado'] == 'Cerrado') {
    $pdf->ChapterTitle('Número SAP:');
    $pdf->ChapterBody($solicitud['numero_sap']);
}

ob_end_clean(); // Limpiar el búfer de salida
$pdf->Output();
?>

