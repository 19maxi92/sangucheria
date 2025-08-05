<?php
require_once '/var/www/html/sangucheria/config.php';

try {
    $conexion = getConnection();
    
    // Validar método de request
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener ID del pedido
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
    } else {
        // Para requests POST (desde AJAX)
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
            } else {
                throw new Exception('Token de seguridad inválido');
            }
        }
        $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
    }
    
    if ($id === false || $id <= 0) {
        $error_msg = 'ID de pedido inválido';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['error'] = $error_msg;
            header("Location: ver_pedidos.php");
            exit;
        }
    }
    
    // Verificar que el pedido existe antes de eliminarlo
    $stmt_check = $conexion->prepare("SELECT id, nombre, apellido FROM pedidos WHERE id = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();
    
    if ($resultado->num_rows === 0) {
        $stmt_check->close();
        $error_msg = 'Pedido no encontrado';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['error'] = $error_msg;
            header("Location: ver_pedidos.php");
            exit;
        }
    }
    
    $pedido_info = $resultado->fetch_assoc();
    $stmt_check->close();
    
    // Eliminar el pedido
    $stmt = $conexion->prepare("DELETE FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        $success_msg = "Pedido #{$id} de {$pedido_info['nombre']} {$pedido_info['apellido']} eliminado correctamente";
        
        if (isAjaxRequest()) {
            jsonResponse(['success' => true, 'message' => $success_msg]);
        } else {
            $_SESSION['mensaje'] = $success_msg;
            header("Location: ver_pedidos.php");
            exit;
        }
    } else {
        $stmt->close();
        $error_msg = 'Error al eliminar el pedido';
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['error'] = $error_msg;
            header("Location: ver_pedidos.php");
            exit;
        }
    }
    
} catch (Exception $e) {
    error_log("Error en eliminar_pedido.php: " . $e->getMessage());
    $error_msg = 'Error interno del servidor';
    
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => $error_msg]);
    } else {
        $_SESSION['error'] = $error_msg;
        header("Location: ver_pedidos.php");
        exit;
    }
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}
?>