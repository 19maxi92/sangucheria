<?php
   require_once '/var/www/html/sangucheria/config.php';
   $conexion = getConnection();

   
// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Recibir y sanitizar los datos del formulario
$nombre        = $conexion->real_escape_string($_POST['nombre']);
$apellido      = $conexion->real_escape_string($_POST['apellido']);
$cantidad      = (int)$_POST['cantidad'];
$planchas      = (float)$_POST['planchas'];
$contacto      = $conexion->real_escape_string($_POST['contacto']);
$direccion     = $conexion->real_escape_string($_POST['direccion']);
$modalidad     = 'Cliente Fijo'; // Siempre indicamos que es un cliente fijo
$observaciones = $conexion->real_escape_string($_POST['observaciones']);

// Insertar datos en la tabla
$sql = "INSERT INTO pedidos (nombre, apellido, cantidad, planchas, contacto, direccion, modalidad, observaciones)
        VALUES ('$nombre', '$apellido', $cantidad, $planchas, '$contacto', '$direccion', '$modalidad', '$observaciones')";

if ($conexion->query($sql) === TRUE) {
    // Redirigir a la página de clientes fijos con mensaje de éxito
    header("Location: clientes_fijos.php?success=1");
} else {
    // Redirigir con mensaje de error
    header("Location: clientes_fijos.php?error=" . urlencode($conexion->error));
}

$conexion->close();
?>