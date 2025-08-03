<?php
   require_once '/var/www/html/sangucheria/config.php';
   $conexion = getConnection();

   
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");
if ($conexion->connect_error) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($pedido = $resultado->fetch_assoc()) {
        // Convertir campos numéricos
        $pedido['id'] = (int)$pedido['id'];
        $pedido['cantidad'] = (int)$pedido['cantidad'];
        $pedido['planchas'] = (float)$pedido['planchas'];
        $pedido['total'] = (float)$pedido['total'];
        
        echo json_encode($pedido);
    } else {
        echo json_encode(['error' => 'Pedido no encontrado']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['error' => 'ID inválido']);
}

$conexion->close();
?>