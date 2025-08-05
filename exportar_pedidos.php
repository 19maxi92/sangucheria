<?php
   require_once '/var/www/html/sangucheria/config.php';
   $conexion = getConnection();
?>

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="pedidos_sandwicheria_' . date("Y-m-d_H-i") . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM para UTF-8 en Excel
echo "\xEF\xBB\xBF";

// Encabezados de la tabla
echo "Fecha\tHora\tCliente\tContacto\tDirección\tProductos\tCantidad\tPlanchas\tTotal\tModalidad\tPago\tEstado\tObservaciones\n";

// Consultar pedidos ordenados por fecha
$resultado = $conexion->query("SELECT * FROM pedidos ORDER BY fecha DESC");

while ($pedido = $resultado->fetch_assoc()) {
    // Procesar productos
    $productos_info = '';
    if (!empty($pedido['productos'])) {
        $productos = json_decode($pedido['productos'], true);
        if ($productos) {
            foreach ($productos as $producto) {
                $productos_info .= $producto['producto'] ?? 'Producto';
                if (!empty($producto['sabores'])) {
                    $productos_info .= ' (' . $producto['sabores'] . ')';
                }
                $productos_info .= '; ';
            }
            $productos_info = rtrim($productos_info, '; ');
        }
    } else {
        $productos_info = 'Pedido Personalizado';
    }
    
    // Formatear fecha y hora
    $fecha_obj = new DateTime($pedido['fecha']);
    $fecha = $fecha_obj->format('d/m/Y');
    $hora = $fecha_obj->format('H:i');
    
    // Limpiar datos para Excel (quitar caracteres problemáticos)
    $cliente = trim($pedido['nombre'] . ' ' . $pedido['apellido']);
    $contacto = $pedido['contacto'];
    $direccion = str_replace(["\n", "\r", "\t"], ' ', $pedido['direccion']);
    $productos_info = str_replace(["\n", "\r", "\t"], ' ', $productos_info);
    $cantidad = $pedido['cantidad'];
    $planchas = $pedido['planchas'];
    $total = number_format($pedido['total'], 0, ',', '.');
    $modalidad = $pedido['modalidad'];
    $pago = $pedido['pago'] ?: 'No especificado';
    $estado = $pedido['estado'] ?: 'Pendiente';
    $observaciones = str_replace(["\n", "\r", "\t"], ' ', $pedido['observaciones']);
    
    // Escribir fila
    echo "$fecha\t$hora\t$cliente\t$contacto\t$direccion\t$productos_info\t$cantidad\t$planchas\t$$total\t$modalidad\t$pago\t$estado\t$observaciones\n";
}

$conexion->close();

?>
