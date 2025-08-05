<?php
require_once '/var/www/html/sangucheria/config.php';

try {
    $conexion = getConnection();
    
    // Validar parámetros de entrada
    if (!isset($_GET['id']) || !isset($_GET['cantidad'])) {
        $_SESSION['error'] = 'Parámetros inválidos';
        header("Location: clientes_fijos.php");
        exit;
    }
    
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $cantidad_pedidos = filter_var($_GET['cantidad'], FILTER_VALIDATE_INT);
    
    if ($id === false || $cantidad_pedidos === false || $id <= 0 || $cantidad_pedidos <= 0) {
        $_SESSION['error'] = 'Parámetros inválidos';
        header("Location: clientes_fijos.php");
        exit;
    }
    
    $cantidad_pedidos = max(1, min(10, $cantidad_pedidos)); // Límite de 10 pedidos
    
    // Usar prepared statement para seguridad
    $stmt = $conexion->prepare("SELECT * FROM clientes_fijos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($cliente = $resultado->fetch_assoc()) {
        // Definir precios según el producto
        $precios = [
            '48 Jamón y Queso' => 22000,
            '48 Surtidos Clásicos' => 20000,
            '48 Surtidos Especiales' => 22000,
            '48 Surtidos Premium' => 42000,
            '24 Jamón y Queso' => 11000,
            '24 Surtidos' => 11000,
            '24 Surtidos Premium' => 21000
        ];
        
        $producto = $cliente['producto'];
        $precio_unitario = isset($precios[$producto]) ? $precios[$producto] : 0;
        
        if ($precio_unitario === 0) {
            $_SESSION['error'] = 'Producto no válido';
            header("Location: clientes_fijos.php");
            exit;
        }
        
        // Extraer cantidad del nombre del producto
        $cantidad_sandwiches = (int)explode(' ', $producto)[0];
        $planchas = round($cantidad_sandwiches / 24, 2);
        
        // Preparar statement para insertar pedidos
        $sql_insert = "INSERT INTO pedidos 
                      (fecha, nombre, apellido, cantidad, planchas, contacto, direccion, 
                       modalidad, observaciones, productos, total, pago, estado) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Efectivo', 'Pendiente')";
        
        $stmt_insert = $conexion->prepare($sql_insert);
        
        $pedidos_creados = 0;
        
        // Crear múltiples pedidos
        for ($i = 0; $i < $cantidad_pedidos; $i++) {
            $fecha = date('Y-m-d H:i:s');
            
            // Crear JSON del producto
            $productos_json = json_encode([
                [
                    'producto' => $producto,
                    'precio' => $precio_unitario,
                    'cantidad' => $cantidad_sandwiches,
                    'sabores' => $cliente['observacion'],
                    'id' => time() + $i
                ]
            ], JSON_UNESCAPED_UNICODE);
            
            $stmt_insert->bind_param(
                "sssidsssssid",
                $fecha,
                $cliente['nombre'],
                $cliente['apellido'],
                $cantidad_sandwiches,
                $planchas,
                $cliente['contacto'],
                $cliente['direccion'],
                $cliente['modalidad'],
                $cliente['observacion'],
                $productos_json,
                $precio_unitario
            );
            
            if ($stmt_insert->execute()) {
                $pedidos_creados++;
            }
        }
        
        $stmt_insert->close();
        
        // Mensaje de éxito
        if ($pedidos_creados > 0) {
            $mensaje = $pedidos_creados > 1 ? 
                "$pedidos_creados pedidos creados para {$cliente['nombre']} {$cliente['apellido']}" :
                "Pedido creado para {$cliente['nombre']} {$cliente['apellido']}";
                
            $_SESSION['mensaje'] = $mensaje;
        } else {
            $_SESSION['error'] = 'No se pudieron crear los pedidos';
        }
    } else {
        $_SESSION['error'] = 'Cliente no encontrado';
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error en cargar_pedido_fijo.php: " . $e->getMessage());
    $_SESSION['error'] = 'Error interno del servidor';
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}

header("Location: clientes_fijos.php");
exit;
?>