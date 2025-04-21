<?php
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (isset($_GET['id'], $_GET['cantidad'])) {
    $id = intval($_GET['id']);
    $cantidad = intval($_GET['cantidad']);
    $cantidad = max(1, $cantidad);

    $resultado = $conexion->query("SELECT nombre, pedido FROM clientes_fijos WHERE id = $id");

    if ($resultado && $fila = $resultado->fetch_assoc()) {
        for ($i = 0; $i < $cantidad; $i++) {
            $nombre = $conexion->real_escape_string($fila['nombre']);
            $pedido = $conexion->real_escape_string($fila['pedido']);
            $fecha = date('Y-m-d H:i:s');

            $conexion->query("
                INSERT INTO pedidos (fecha, nombre, apellido, cantidad, planchas, contacto, direccion, modalidad, observaciones)
                VALUES ('$fecha', '$nombre', '', 1, '', '', '', 'Cliente Fijo', '$pedido')
            ");
        }
    }
}

$conexion->close();
header("Location: clientes_fijos.php");
exit;
?>
