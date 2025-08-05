<?php
require_once '/var/www/html/sangucheria/config.php';

try {
    $conexion = getConnection();
    
    // Validar rango de fechas si se proporcionan
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;
    $formato = isset($_GET['formato']) ? $_GET['formato'] : 'excel';
    
    // Construir consulta con filtros
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($fecha_desde && $fecha_hasta) {
        // Validar fechas
        if (!DateTime::createFromFormat('Y-m-d', $fecha_desde) || !DateTime::createFromFormat('Y-m-d', $fecha_hasta)) {
            throw new Exception('Formato de fecha inválido');
        }
        
        $where_conditions[] = "DATE(fecha) BETWEEN ? AND ?";
        $params[] = $fecha_desde;
        $params[] = $fecha_hasta;
        $types .= 'ss';
    }
    
    $sql = "SELECT * FROM pedidos";
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    $sql .= " ORDER BY fecha DESC";
    
    // Preparar y ejecutar consulta
    if (!empty($params)) {
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $resultado = $conexion->query($sql);
    }
    
    if (!$resultado) {
        throw new Exception('Error al consultar pedidos');
    }
    
    // Configurar headers para descarga
    $fecha_actual = date("Y-m-d_H-i");
    $filename = "pedidos_sandwicheria_{$fecha_actual}";
    
    if ($formato === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM para UTF-8
        echo "\xEF\xBB\xBF";
        
        // Encabezados CSV
        $headers = [
            'ID', 'Fecha', 'Hora', 'Cliente', 'Contacto', 'Dirección', 
            'Productos', 'Cantidad', 'Planchas', 'Total', 'Modalidad', 
            'Pago', 'Estado', 'Observaciones'
        ];
        
        echo implode(',', array_map(function($header) {
            return '"' . str_replace('"', '""', $header) . '"';
        }, $headers)) . "\n";
        
    } else {
        // Formato Excel (TSV)
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // BOM para UTF-8 en Excel
        echo "\xEF\xBB\xBF";
        
        // Encabezados Excel
        echo "ID\tFecha\tHora\tCliente\tContacto\tDirección\tProductos\tCantidad\tPlanchas\tTotal\tModalidad\tPago\tEstado\tObservaciones\n";
    }
    
    $total_registros = 0;
    $total_ventas = 0;
    
    // Procesar cada pedido
    while ($pedido = $resultado->fetch_assoc()) {
        $total_registros++;
        $total_ventas += $pedido['total'];
        
        // Procesar productos
        $productos_info = '';
        if (!empty($pedido['productos'])) {
            $productos = json_decode($pedido['productos'], true);
            if ($productos && is_array($productos)) {
                $productos_array = [];
                foreach ($productos as $producto) {
                    $producto_texto = $producto['producto'] ?? 'Producto';
                    if (!empty($producto['sabores'])) {
                        $producto_texto .= ' (' . $producto['sabores'] . ')';
                    }
                    $productos_array[] = $producto_texto;
                }
                $productos_info = implode('; ', $productos_array);
            }
        } else {
            $productos_info = 'Pedido Personalizado';
        }
        
        // Formatear fecha y hora
        try {
            $fecha_obj = new DateTime($pedido['fecha']);
            $fecha = $fecha_obj->format('d/m/Y');
            $hora = $fecha_obj->format('H:i');
        } catch (Exception $e) {
            $fecha = 'Fecha inválida';
            $hora = '';
        }
        
        // Limpiar datos para exportación
        $cliente = trim(($pedido['nombre'] ?? '') . ' ' . ($pedido['apellido'] ?? ''));
        $contacto = $pedido['contacto'] ?? '';
        $direccion = preg_replace('/[\n\r\t]/', ' ', $pedido['direccion'] ?? '');
        $productos_info = preg_replace('/[\n\r\t]/', ' ', $productos_info);
        $cantidad = $pedido['cantidad'] ?? 0;
        $planchas = $pedido['planchas'] ?? 0;
        $total = number_format($pedido['total'] ?? 0, 0, ',', '.');
        $modalidad = $pedido['modalidad'] ?? '';
        $pago = $pedido['pago'] ?? 'No especificado';
        $estado = $pedido['estado'] ?? 'Pendiente';
        $observaciones = preg_replace('/[\n\r\t]/', ' ', $pedido['observaciones'] ?? '');
        
        if ($formato === 'csv') {
            // Formato CSV
            $row = [
                $pedido['id'],
                $fecha,
                $hora,
                $cliente,
                $contacto,
                $direccion,
                $productos_info,
                $cantidad,
                $planchas,
                '$' . $total,
                $modalidad,
                $pago,
                $estado,
                $observaciones
            ];
            
            echo implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
            
        } else {
            // Formato Excel (TSV)
            echo "{$pedido['id']}\t{$fecha}\t{$hora}\t{$cliente}\t{$contacto}\t{$direccion}\t{$productos_info}\t{$cantidad}\t{$planchas}\t\${$total}\t{$modalidad}\t{$pago}\t{$estado}\t{$observaciones}\n";
        }
    }
    
    // Agregar fila de totales
    if ($formato === 'csv') {
        echo "\n\"TOTALES\",\"\",\"\",\"\",\"\",\"\",\"\",\"{$total_registros} pedidos\",\"\",\"\$" . number_format($total_ventas, 0, ',', '.') . "\",\"\",\"\",\"\",\"\"\n";
    } else {
        echo "\nTOTALES\t\t\t\t\t\t\t{$total_registros} pedidos\t\t\$" . number_format($total_ventas, 0, ',', '.') . "\t\t\t\t\n";
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Error en exportar_pedidos.php: " . $e->getMessage());
    
    // Si ya se enviaron headers, no podemos redirigir
    if (!headers_sent()) {
        $_SESSION['error'] = 'Error al exportar pedidos: ' . $e->getMessage();
        header("Location: ver_pedidos.php");
        exit;
    } else {
        echo "Error al exportar pedidos";
    }
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}
?>