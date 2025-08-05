<?php
require_once '/var/www/html/sangucheria/config.php';

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = getConnection();
    
    // Validar que es una petición GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonResponse(['error' => 'Método no permitido']);
    }
    
    // Validar y sanitizar ID
    $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
    
    if ($id === false || $id <= 0) {
        jsonResponse(['error' => 'ID inválido']);
    }
    
    // Usar prepared statement para seguridad
    $stmt = $conexion->prepare("SELECT * FROM pedidos WHERE id = ?");
    if (!$stmt) {
        error_log("Error al preparar consulta: " . $conexion->error);
        jsonResponse(['error' => 'Error interno del servidor']);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($pedido = $resultado->fetch_assoc()) {
        // Convertir y validar campos numéricos
        $pedido['id'] = (int)$pedido['id'];
        $pedido['cantidad'] = (int)($pedido['cantidad'] ?? 0);
        $pedido['planchas'] = (float)($pedido['planchas'] ?? 0);
        $pedido['total'] = (float)($pedido['total'] ?? 0);
        
        // Sanitizar campos de texto
        $pedido['nombre'] = htmlspecialchars($pedido['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
        $pedido['apellido'] = htmlspecialchars($pedido['apellido'] ?? '', ENT_QUOTES, 'UTF-8');
        $pedido['contacto'] = htmlspecialchars($pedido['contacto'] ?? '', ENT_QUOTES, 'UTF-8');
        $pedido['direccion'] = htmlspecialchars($pedido['direccion'] ?? '', ENT_QUOTES, 'UTF-8');
        $pedido['modalidad'] = htmlspecialchars($pedido['modalidad'] ?? '', ENT_QUOTES, 'UTF-8');
        $pedido['observaciones'] = htmlspecialchars($pedido['observaciones'] ?? '', ENT_QUOTES, 'UTF-8');
        $pedido['pago'] = htmlspecialchars($pedido['pago'] ?? 'Efectivo', ENT_QUOTES, 'UTF-8');
        $pedido['estado'] = htmlspecialchars($pedido['estado'] ?? 'Pendiente', ENT_QUOTES, 'UTF-8');
        
        // Validar y procesar JSON de productos
        if (!empty($pedido['productos'])) {
            $productos = json_decode($pedido['productos'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($productos)) {
                // Sanitizar productos
                foreach ($productos as &$producto) {
                    if (isset($producto['producto'])) {
                        $producto['producto'] = htmlspecialchars($producto['producto'], ENT_QUOTES, 'UTF-8');
                    }
                    if (isset($producto['sabores'])) {
                        $producto['sabores'] = htmlspecialchars($producto['sabores'], ENT_QUOTES, 'UTF-8');
                    }
                    if (isset($producto['precio'])) {
                        $producto['precio'] = (float)$producto['precio'];
                    }
                    if (isset($producto['cantidad'])) {
                        $producto['cantidad'] = (int)$producto['cantidad'];
                    }
                }
                $pedido['productos'] = json_encode($productos, JSON_UNESCAPED_UNICODE);
            } else {
                // JSON inválido, limpiar
                $pedido['productos'] = '[]';
            }
        } else {
            $pedido['productos'] = '[]';
        }
        
        // Formatear fecha
        if (!empty($pedido['fecha'])) {
            try {
                $fecha_obj = new DateTime($pedido['fecha']);
                $pedido['fecha_formateada'] = $fecha_obj->format('d/m/Y H:i:s');
            } catch (Exception $e) {
                $pedido['fecha_formateada'] = 'Fecha inválida';
            }
        }
        
        $stmt->close();
        jsonResponse($pedido);
        
    } else {
        $stmt->close();
        jsonResponse(['error' => 'Pedido no encontrado']);
    }
    
} catch (Exception $e) {
    error_log("Error en obtener_pedido.php: " . $e->getMessage());
    jsonResponse(['error' => 'Error interno del servidor']);
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}
?>