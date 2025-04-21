<?php
// Conectar a la base de datos
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");

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
$modalidad     = $conexion->real_escape_string($_POST['modalidad']);
$observaciones = $conexion->real_escape_string($_POST['observaciones']);

// Insertar datos en la tabla
$sql = "INSERT INTO pedidos (nombre, apellido, cantidad, planchas, contacto, direccion, modalidad, observaciones)
        VALUES ('$nombre', '$apellido', $cantidad, $planchas, '$contacto', '$direccion', '$modalidad', '$observaciones')";

if ($conexion->query($sql) === TRUE) {
    echo "<div class='container mt-5'>
            <div class='alert alert-success'>✅ Pedido guardado correctamente.</div>
            <a href='index.php' class='btn btn-primary'>Volver</a>
          </div>";
} else {
    echo "<div class='container mt-5'>
            <div class='alert alert-danger'>❌ Error al guardar: " . $conexion->error . "</div>
            <a href='index.php' class='btn btn-secondary'>Volver</a>
          </div>";
}

$conexion->close();
?>
