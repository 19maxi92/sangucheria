<?php
require_once '/var/www/html/sangucheria/config.php';

try {
    $conexion = getConnection();
    
    // Verificar que es una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
        } else {
            throw new Exception('Token de seguridad inválido');
        }
    }
    
    // Validar y sanitizar datos obligatorios
    $required_fields = ['nombre', 'apellido', 'contacto'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error_msg = "El campo $field es obligatorio";
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => $error_msg]);
            } else {
                throw new Exception($error_msg);
            }
        }
    }
    
    // Sanitizar y validar datos de entrada
    $nombre = sanitizeInput($_POST['nombre']);
    $apellido = sanitizeInput($_POST['apellido']);
    $contacto = sanitizeInput($_POST['contacto']);
    $direccion = sanitizeInput($_POST['direccion'] ?? '');
    $modalidad = sanitizeInput($_POST['modalidad'] ?? '');
    $pago = sanitizeInput($_POST['pago'] ?? 'Efectivo');
    $observaciones = sanitizeInput($_POST['observaciones'] ?? '');
    
    // Validar longitudes
    if (strlen($nombre) > 100 || strlen($apellido) > 100) {
        $error_msg = 'Nombre o apellido demasiado largo';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_msg]);
        } else {
            throw new Exception($error_msg);
        }
    }
    
    // Validar valores específicos
    $modalidades_validas = ['Retira', 'Envío', ''];
    $pagos_validos = ['Efectivo', 'Transferencia'];
    
    if (!in_array($modalidad, $modalidades_validas)) {
        $modalidad = '';
    }
    
    if (!in_array($pago, $pagos_validos)) {
        $pago = 'Efectivo';
    }
    
    // Validar formato de contacto (básico)
    if (!preg_match('/^[\d\s\-\+\(\)]+$/', $contacto)) {
        $error_msg = 'Formato de contacto inválido';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_msg]);
        } else {
            throw new Exception($error_msg);
        }
    }
    
    // Procesar datos del pedido
    $productos_json = isset($_POST['productos']) ? $_POST['productos'] : '[]';
    $total = isset($_POST['total']) ? filter_var($_POST['total'], FILTER_VALIDATE_FLOAT) : 0;
    $cantidad_total = isset($_POST['cantidad']) ? filter_var($_POST['cantidad'], FILTER_VALIDATE_INT) : 0;
    $planchas = isset($_POST['planchas']) ? filter_var($_POST['planchas'], FILTER_VALIDATE_FLOAT) : 0;
    
    // Validar JSON de productos
    if ($productos_json !== '[]') {
        $productos_array = json_decode($productos_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_msg = 'Datos de productos inválidos';
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => $error_msg]);
            } else {
                throw new Exception($error_msg);
            }
        }
        
        // Validar que hay productos
        if (empty($productos_array)) {
            $error_msg = 'Debe incluir al menos un producto';
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => $error_msg]);
            } else {
                throw new Exception($error_msg);
            }
        }
        
        // Recodificar JSON para asegurar formato correcto
        $productos_json = json_encode($productos_array, JSON_UNESCAPED_UNICODE);
        
    } else {
        // Si no viene información de productos, crear pedido simple
        if ($cantidad_total <= 0) {
            $error_msg = 'Cantidad inválida';
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => $error_msg]);
            } else {
                throw new Exception($error_msg);
            }
        }
        
        $cantidad_total = max(1, min(1000, $cantidad_total)); // Límite de seguridad
        $planchas = round($cantidad_total / 24, 2);
        
        // Crear un producto simple
        $productos_json = json_encode([
            [
                'producto' => 'Pedido Personalizado',
                'cantidad' => $cantidad_total,
                'precio' => $total,
                'sabores' => $observaciones,
                'id' => time()
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    
    // Validar valores numéricos
    if ($total === false || $total < 0 || $total > 1000000) {
        $total = 0;
    }
    
    if ($cantidad_total === false || $cantidad_total < 0 || $cantidad_total > 1000) {
        $cantidad_total = 0;
    }
    
    if ($planchas === false || $planchas < 0) {
        $planchas = 0;
    }
    
    // Insertar pedido en la base de datos usando prepared statement
    $sql = "INSERT INTO pedidos (
                nombre, apellido, cantidad, planchas, contacto, direccion, 
                modalidad, observaciones, productos, total, pago, estado, fecha
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente', NOW())";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . $conexion->error);
    }
    
    $stmt->bind_param("sssdsssssds", 
        $nombre, $apellido, $cantidad_total, $planchas, $contacto, $direccion,
        $modalidad, $observaciones, $productos_json, $total, $pago
    );
    
    if ($stmt->execute()) {
        $pedido_id = $conexion->insert_id;
        $stmt->close();
        
        $success_msg = 'Pedido guardado correctamente';
        
        // Respuesta según el tipo de request
        if (isAjaxRequest()) {
            jsonResponse([
                'success' => true, 
                'message' => $success_msg,
                'pedido_id' => $pedido_id
            ]);
        } else {
            // Respuesta HTML tradicional
            $_SESSION['mensaje'] = $success_msg;
            header("Location: ver_pedidos.php?highlight=$pedido_id");
            exit;
        }
    } else {
        $stmt->close();
        throw new Exception('Error al guardar pedido: ' . $conexion->error);
    }
    
} catch (Exception $e) {
    error_log("Error en guardar_pedido.php: " . $e->getMessage());
    $error_msg = 'Error al guardar el pedido';
    
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => $error_msg]);
    } else {
        $_SESSION['error'] = $error_msg;
        header("Location: index.php");
        exit;
    }
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}
?>