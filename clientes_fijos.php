<?php
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Manejar operaciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar':
                $nombre = $conexion->real_escape_string($_POST['nombre']);
                $apellido = $conexion->real_escape_string($_POST['apellido']);
                $contacto = $conexion->real_escape_string($_POST['contacto']);
                $direccion = $conexion->real_escape_string($_POST['direccion']);
                $modalidad = $conexion->real_escape_string($_POST['modalidad']);
                $producto = $conexion->real_escape_string($_POST['producto']);
                $observacion = $conexion->real_escape_string($_POST['observacion']);
                
                $sql = "INSERT INTO clientes_fijos (nombre, apellido, contacto, direccion, modalidad, producto, observacion) 
                        VALUES ('$nombre', '$apellido', '$contacto', '$direccion', '$modalidad', '$producto', '$observacion')";
                
                if ($conexion->query($sql)) {
                    echo json_encode(['success' => true, 'message' => 'Cliente agregado correctamente']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al agregar cliente']);
                }
                break;
                
            case 'eliminar':
                $id = (int)$_POST['id'];
                $stmt = $conexion->prepare("DELETE FROM clientes_fijos WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Cliente eliminado']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
                }
                $stmt->close();
                break;
                
            case 'hacer_pedido':
                $id = (int)$_POST['id'];
                $resultado = $conexion->query("SELECT * FROM clientes_fijos WHERE id = $id");
                
                if ($resultado && $cliente = $resultado->fetch_assoc()) {
                    // Definir precios
                    $precios = [
                        '48 Jamón y Queso' => 22000,
                        '48 Surtidos Clásicos' => 20000,
                        '48 Surtidos Especiales' => 22000,
                        '48 Surtidos Premium' => 42000,
                        '24 Jamón y Queso' => 11000,
                        '24 Surtidos' => 11000,
                        '24 Surtidos Premium' => 21000
                    ];
                    
                    $producto = $cliente['producto'];
                    $precio = isset($precios[$producto]) ? $precios[$producto] : 0;
                    $cantidad = (int)explode(' ', $producto)[0]; // Extraer cantidad del nombre del producto
                    $planchas = round($cantidad / 24, 2);
                    
                    // Crear producto JSON
                    $productos_json = json_encode([
                        [
                            'producto' => $producto,
                            'precio' => $precio,
                            'cantidad' => $cantidad,
                            'sabores' => '',
                            'id' => time()
                        ]
                    ]);
                    
                    // Insertar pedido
                    $sql_pedido = "INSERT INTO pedidos (
                        nombre, apellido, cantidad, planchas, contacto, direccion,
                        modalidad, observaciones, productos, total, pago, estado, fecha
                    ) VALUES (
                        '{$cliente['nombre']}', '{$cliente['apellido']}', $cantidad, $planchas,
                        '{$cliente['contacto']}', '{$cliente['direccion']}', '{$cliente['modalidad']}',
                        '{$cliente['observacion']}', '$productos_json', $precio, 'Efectivo', 'Pendiente', NOW()
                    )";
                    
                    if ($conexion->query($sql_pedido)) {
                        echo json_encode([
                            'success' => true, 
                            'message' => "Pedido creado para {$cliente['nombre']} {$cliente['apellido']}",
                            'pedido_id' => $conexion->insert_id
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error al crear pedido']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
                }
                break;
        }
    }
    
    $conexion->close();
    exit;
}

// Si es GET, mostrar la página
$resultado = $conexion->query("SELECT * FROM clientes_fijos ORDER BY nombre, apellido");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes Fijos - Sandwicheria Santa Catalina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>👥 Clientes Fijos</h2>
            <small class="text-muted">Sandwicheria Santa Catalina - 📲 1159813546</small>
        </div>
        <div>
            <button class="btn btn-success me-2" onclick="mostrarFormulario()">➕ Agregar Cliente Fijo</button>
            <a href="index.php" class="btn btn-secondary">← Volver al Sistema</a>
        </div>
    </div>

    <!-- Lista de clientes fijos -->
    <div id="listaClientes" class="mb-4">
        <?php if ($resultado->num_rows > 0): ?>
            <?php while ($cliente = $resultado->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="mb-1"><?= htmlspecialchars($cliente['nombre']) ?> <?= htmlspecialchars($cliente['apellido']) ?></h5>
                                <small class="text-muted">📱 <?= htmlspecialchars($cliente['contacto']) ?></small><br>
                                <small class="text-muted">📍 <?= htmlspecialchars($cliente['direccion']) ?></small>
                            </div>
                            <div class="col-md-4">
                                <strong>Producto habitual:</strong><br>
                                <span class="badge bg-primary"><?= htmlspecialchars($cliente['producto']) ?></span><br>
                                <small class="text-muted">Modalidad: <?= htmlspecialchars($cliente['modalidad']) ?></small>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-success btn-sm" onclick="realizarPedido(<?= $cliente['id'] ?>)">📋 Hacer Pedido</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="eliminarCliente(<?= $cliente['id'] ?>)">🗑️</button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small><strong>Observaciones:</strong> <?= htmlspecialchars($cliente['observacion']) ?></small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-warning">No hay clientes fijos registrados.</div>
        <?php endif; ?>
    </div>

    <!-- Formulario para agregar cliente -->
    <div id="formularioCliente" class="card" style="display:none;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">➕ Nuevo Cliente Fijo</h5>
        </div>
        <div class="card-body">
            <form id="formNuevoCliente">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre:</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apellido:</label>
                        <input type="text" class="form-control" name="apellido" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">N° de Contacto:</label>
                        <input type="text" class="form-control" name="contacto" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dirección:</label>
                        <input type="text" class="form-control" name="direccion" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modalidad:</label>
                        <select class="form-control" name="modalidad" required>
                            <option value="">Seleccionar...</option>
                            <option value="Retira">🏪 Retira en Local</option>
                            <option value="Envío">🚚 Envío a Domicilio</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Producto Habitual:</label>
                        <select class="form-control" name="producto" required>
                            <option value="">Seleccionar...</option>
                            <optgroup label="📦 Paquetes x24">
                                <option value="24 Jamón y Queso">24 Jamón y Queso - $11.000</option>
                                <option value="24 Surtidos">24 Surtidos - $11.000</option>
                                <option value="24 Surtidos Premium">24 Surtidos Premium - $21.000</option>
                            </optgroup>
                            <optgroup label="📦 Paquetes x48">
                                <option value="48 Jamón y Queso">48 Jamón y Queso - $22.000</option>
                                <option value="48 Surtidos Clásicos">48 Surtidos Clásicos - $20.000</option>
                                <option value="48 Surtidos Especiales">48 Surtidos Especiales - $22.000</option>
                                <option value="48 Surtidos Premium">48 Surtidos Premium - $42.000</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones/Sabores:</label>
                        <textarea class="form-control" name="observacion" rows="3" placeholder="Ej: 8 jamón crudo, 8 roquefort, 8 palmito..." required></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">💾 Guardar Cliente</button>
                    <button type="button" class="btn btn-secondary ms-2" onclick="cerrarFormulario()">❌ Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar formulario
function mostrarFormulario() {
    document.getElementById('formularioCliente').style.display = 'block';
    document.getElementById('formularioCliente').scrollIntoView({ behavior: 'smooth' });
}

// Cerrar formulario
function cerrarFormulario() {
    document.getElementById('formularioCliente').style.display = 'none';
    document.getElementById('formNuevoCliente').reset();
}

// Guardar nuevo cliente
document.getElementById('formNuevoCliente').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const datos = new FormData(this);
    datos.append('accion', 'agregar');
    
    fetch('clientes_fijos.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión');
    });
});

// Realizar pedido para cliente fijo
function realizarPedido(id) {
    if (!confirm('¿Confirma realizar el pedido habitual para este cliente?')) {
        return;
    }
    
    const datos = new FormData();
    datos.append('accion', 'hacer_pedido');
    datos.append('id', id);
    
    fetch('clientes_fijos.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            // Opcional: redirigir a ver pedidos
            if (confirm('¿Desea ver el pedido creado?')) {
                window.open('ver_pedidos.php', '_blank');
            }
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión');
    });
}

// Eliminar cliente fijo
function eliminarCliente(id) {
    if (!confirm('¿Está seguro que desea eliminar este cliente fijo?')) {
        return;
    }
    
    const datos = new FormData();
    datos.append('accion', 'eliminar');
    datos.append('id', id);
    
    fetch('clientes_fijos.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión');
    });
}
</script>

</body>
</html>

<?php $conexion->close(); ?>