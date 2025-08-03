<?php
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (isset($_GET['id'], $_GET['cantidad'])) {
    $id = intval($_GET['id']);
    $cantidad_pedidos = intval($_GET['cantidad']);
    $cantidad_pedidos = max(1, $cantidad_pedidos);

    $resultado = $conexion->query("SELECT * FROM clientes_fijos WHERE id = $id");

    if ($resultado && $cliente = $resultado->fetch_assoc()) {
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
        $cantidad_sandwiches = (int)explode(' ', $producto)[0]; // Extraer cantidad del nombre del producto
        
        // Crear múltiples pedidos
        for ($i = 0; $i < $cantidad_pedidos; $i++) {
            $nombre = $conexion->real_escape_string($cliente['nombre']);
            $apellido = $conexion->real_escape_string($cliente['apellido']);
            $contacto = $conexion->real_escape_string($cliente['contacto']);
            $direccion = $conexion->real_escape_string($cliente['direccion']);
            $modalidad = $conexion->real_escape_string($cliente['modalidad']);
            $observacion = $conexion->real_escape_string($cliente['observacion']);
            $fecha = date('Y-m-d H:i:s');
            $planchas = round($cantidad_sandwiches / 24, 2);
            $total = $precio_unitario;
            
            // Crear JSON del producto
            $productos_json = json_encode([
                [
                    'producto' => $producto,
                    'precio' => $precio_unitario,
                    'cantidad' => $cantidad_sandwiches,
                    'sabores' => $observacion,
                    'id' => time() + $i
                ]
            ]);
            
            $productos_json_escaped = $conexion->real_escape_string($productos_json);

            $sql = "INSERT INTO pedidos (
                        fecha, nombre, apellido, cantidad, planchas, contacto, direccion, 
                        modalidad, observaciones, productos, total, pago, estado
                    ) VALUES (
                        '$fecha', '$nombre', '$apellido', $cantidad_sandwiches, $planchas, 
                        '$contacto', '$direccion', '$modalidad', '$observacion', 
                        '$productos_json_escaped', $total, 'Efectivo', 'Pendiente'
                    )";
            
            $conexion->query($sql);
        }
        
        // Mensaje de éxito
        $mensaje = $cantidad_pedidos > 1 ? 
            "$cantidad_pedidos pedidos creados para {$cliente['nombre']} {$cliente['apellido']}" :
            "Pedido creado para {$cliente['nombre']} {$cliente['apellido']}";
            
        $_SESSION['mensaje'] = $mensaje;
    }
}

$conexion->close();
header("Location: clientes_fijos.php");
exit;
?>