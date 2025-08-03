<?php
// Conectar a la base de datos
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y sanitizar los datos del formulario
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $apellido = $conexion->real_escape_string($_POST['apellido']);
    $contacto = $conexion->real_escape_string($_POST['contacto']);
    $direccion = $conexion->real_escape_string($_POST['direccion']);
    $modalidad = $conexion->real_escape_string($_POST['modalidad']);
    $pago = isset($_POST['pago']) ? $conexion->real_escape_string($_POST['pago']) : 'Efectivo';
    $observaciones = $conexion->real_escape_string($_POST['observaciones']);
    
    // Datos del pedido (estos vienen del JavaScript como JSON)
    $productos_json = isset($_POST['productos']) ? $_POST['productos'] : '[]';
    $total = isset($_POST['total']) ? (float)$_POST['total'] : 0;
    $cantidad_total = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
    $planchas = isset($_POST['planchas']) ? (float)$_POST['planchas'] : 0;
    
    // Si no viene información de productos (pedido simple), usar datos tradicionales
    if ($productos_json === '[]' && isset($_POST['cantidad']) && isset($_POST['planchas'])) {
        $cantidad_total = (int)$_POST['cantidad'];
        $planchas = (float)$_POST['planchas'];
        $total = 0; // Se puede calcular después según el producto
        
        // Crear un producto simple
        $productos_json = json_encode([
            [
                'producto' => 'Pedido Personalizado',
                'cantidad' => $cantidad_total,
                'precio' => $total,
                'sabores' => $observaciones
            ]
        ]);
    }
    
    // Validar datos obligatorios
    if (empty($nombre) || empty($apellido) || empty($contacto)) {
        echo json_encode(['success' => false, 'message' => 'Datos obligatorios faltantes']);
        exit;
    }
    
    // Insertar datos en la tabla
    $sql = "INSERT INTO pedidos (
                nombre, apellido, cantidad, planchas, contacto, direccion, 
                modalidad, observaciones, productos, total, pago, estado, fecha
            ) VALUES (
                '$nombre', '$apellido', $cantidad_total, $planchas, '$contacto', '$direccion',
                '$modalidad', '$observaciones', '$productos_json', $total, '$pago', 'Pendiente', NOW()
            )";
    
    if ($conexion->query($sql) === TRUE) {
        $pedido_id = $conexion->insert_id;
        
        // Si es una petición AJAX (desde el nuevo sistema)
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode([
                'success' => true, 
                'message' => 'Pedido guardado correctamente',
                'pedido_id' => $pedido_id
            ]);
        } else {
            // Respuesta HTML tradicional
            echo "<div class='container mt-5'>
                    <div class='alert alert-success'>✅ Pedido guardado correctamente.</div>
                    <a href='index.php' class='btn btn-primary'>Volver</a>
                    <a href='ver_pedidos.php?highlight=$pedido_id' class='btn btn-success'>Ver Pedidos</a>
                  </div>";
        }
    } else {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al guardar: ' . $conexion->error
            ]);
        } else {
            echo "<div class='container mt-5'>
                    <div class='alert alert-danger'>❌ Error al guardar: " . $conexion->error . "</div>
                    <a href='index.php' class='btn btn-secondary'>Volver</a>
                  </div>";
        }
    }
} else {
    // Si no es POST, redirigir
    header("Location: index.php");
    exit;
}

$conexion->close();
?>