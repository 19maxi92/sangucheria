<?php
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$busqueda = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';

$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha';
$dir = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';

$orden_permitidos = ['fecha', 'nombre', 'apellido'];
$dir_permitidos = ['ASC', 'DESC'];

if (!in_array($orden, $orden_permitidos)) $orden = 'fecha';
if (!in_array($dir, $dir_permitidos)) $dir = 'DESC';

$sql = "SELECT * FROM pedidos";
if ($busqueda != '') {
    $sql .= " WHERE nombre LIKE '%$busqueda%' OR apellido LIKE '%$busqueda%'";
}
$sql .= " ORDER BY $orden $dir";

$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-3">Pedidos Guardados</h2>
    <a href="exportar_pedidos.php" class="btn btn-outline-success mb-3">📥 Exportar a Excel</a>
    <a href="index.php" class="btn btn-outline-primary mb-3">← Volver</a>

    <form class="mb-3 d-flex" method="GET" action="ver_pedidos.php">
        <input type="text" class="form-control me-2" name="buscar" id="searchInput" placeholder="Buscar cliente..." value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit" class="btn btn-info">🔍 Buscar</button>
    </form>

    <div class="mb-2">
        <strong>Ordenar por:</strong>
        <a href="?orden=fecha&dir=ASC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">Fecha ↑</a>
        <a href="?orden=fecha&dir=DESC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">Fecha ↓</a>
        <a href="?orden=nombre&dir=ASC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">Nombre A-Z</a>
        <a href="?orden=nombre&dir=DESC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">Nombre Z-A</a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Cantidad</th>
                <th>Planchas</th>
                <th>Contacto</th>
                <th>Dirección</th>
                <th>Modalidad</th>
                <th>Observaciones</th>
                <th>Eliminar</th>
            </tr>
        </thead>
        <tbody id="pedidosTable">
        <?php while ($pedido = $resultado->fetch_assoc()) { ?>
            <tr>
                <td><?= date("d/m/Y H:i", strtotime($pedido['fecha'])) ?></td>
                <td><?= htmlspecialchars($pedido['nombre']) ?></td>
                <td><?= htmlspecialchars($pedido['apellido']) ?></td>
                <td><?= $pedido['cantidad'] ?></td>
                <td><?= $pedido['planchas'] ?></td>
                <td><?= htmlspecialchars($pedido['contacto']) ?></td>
                <td><?= htmlspecialchars($pedido['direccion']) ?></td>
                <td><?= htmlspecialchars($pedido['modalidad']) ?></td>
                <td><?= htmlspecialchars($pedido['observaciones']) ?></td>
                <td>
                    <a href="eliminar_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que querés eliminar este pedido?');">🗑️</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    $("#searchInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#pedidosTable tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>

<script>
function eliminarPedido(id, boton) {
    if (confirm("¿Seguro que quieres eliminar este pedido?")) {
        fetch('eliminar_pedido.php?id=' + id)
            .then(response => {
                if (response.ok) {
                    // Eliminar la fila visualmente
                    boton.closest('tr').remove();
                } else {
                    alert("Error al eliminar el pedido.");
                }
            })
            .catch(error => {
                alert("Ocurrió un error: " + error);
            });
    }
}
</script>

</body>
</html>

<?php $conexion->close(); ?>
