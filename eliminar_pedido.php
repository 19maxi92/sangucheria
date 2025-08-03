<?php
   require_once '/var/www/html/sangucheria/config.php';
   $conexion = getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conexion->prepare("DELETE FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ver_pedidos.php");
        exit;
    } else {
        echo "Error al eliminar: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "ID inválido.";
}

$conexion->close();
?>
