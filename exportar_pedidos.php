<?php
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="pedidos_' . date("Y-m-d") . '.xls"');

echo "Fecha\tNombre\tApellido\tCantidad\tPlanchas\tContacto\tDirección\tModalidad\tObservaciones\n";

$resultado = $conexion->query("SELECT * FROM pedidos ORDER BY fecha DESC");

while ($pedido = $resultado->fetch_assoc()) {
    echo date("d/m/Y H:i", strtotime($pedido['fecha'])) . "\t" .
         $pedido['nombre'] . "\t" .
         $pedido['apellido'] . "\t" .
         $pedido['cantidad'] . "\t" .
         $pedido['planchas'] . "\t" .
         $pedido['contacto'] . "\t" .
         $pedido['direccion'] . "\t" .
         $pedido['modalidad'] . "\t" .
         $pedido['observaciones'] . "\n";
}

$conexion->close();
?>
