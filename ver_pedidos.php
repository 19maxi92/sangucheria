<?php
$conexion = new mysqli("localhost", "root", "", "fabrica_sandwiches");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Manejar cambio de estado via AJAX
if (isset($_POST['cambiar_estado'])) {
    $id = (int)$_POST['id'];
    $estado = $conexion->real_escape_string($_POST['estado']);
    
    $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
    
    $stmt->close();
    $conexion->close();
    exit;
}

$busqueda = isset($_GET['buscar']) ? $conexion->real_escape_string($_GET['buscar']) : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha';
$dir = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';

$orden_permitidos = ['fecha', 'nombre', 'apellido', 'total', 'estado'];
$dir_permitidos = ['ASC', 'DESC'];

if (!in_array($orden, $orden_permitidos)) $orden = 'fecha';
if (!in_array($dir, $dir_permitidos)) $dir = 'DESC';

$sql = "SELECT * FROM pedidos";
if ($busqueda != '') {
    $sql .= " WHERE nombre LIKE '%$busqueda%' OR apellido LIKE '%$busqueda%' OR contacto LIKE '%$busqueda%'";
}
$sql .= " ORDER BY $orden $dir";

$resultado = $conexion->query($sql);

// Calcular estadísticas
$estadisticas = $conexion->query("
    SELECT 
        COUNT(*) as total_pedidos,
        SUM(cantidad) as total_sandwiches,
        SUM(total) as total_ventas,
        COUNT(CASE WHEN DATE(fecha) = CURDATE() THEN 1 END) as pedidos_hoy,
        SUM(CASE WHEN DATE(fecha) = CURDATE() THEN cantidad ELSE 0 END) as sandwiches_hoy,
        SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total ELSE 0 END) as ventas_hoy
    FROM pedidos
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Pedidos - Sandwicheria Santa Catalina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .estado-pendiente { background-color: #fff3cd; }
        .estado-preparacion { background-color: #cff4fc; }
        .estado-listo { background-color: #d1e7dd; }
        .estado-entregado { background-color: #f8f9fa; }
        .badge-efectivo { background-color: #28a745; }
        .badge-transferencia { background-color: #007bff; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>📊 Listado de Pedidos</h2>
            <small class="text-muted">Sandwicheria Santa Catalina - 📲 1159813546</small>
        </div>
        <div>
            <a href="exportar_pedidos.php" class="btn btn-success me-2">📥 Exportar Excel</a>
            <a href="index.php" class="btn btn-primary">← Volver al Sistema</a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-primary"><?= $estadisticas['pedidos_hoy'] ?></h4>
                    <small>Pedidos Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-success"><?= $estadisticas['sandwiches_hoy'] ?></h4>
                    <small>Sándwiches Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-warning">$<?= number_format($estadisticas['ventas_hoy'], 0, ',', '.') ?></h4>
                    <small>Ventas Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-info"><?= $estadisticas['total_pedidos'] ?></h4>
                    <small>Total Pedidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-secondary"><?= $estadisticas['total_sandwiches'] ?></h4>
                    <small>Total Sándwiches</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h4 class="text-dark">$<?= number_format($estadisticas['total_ventas'], 0, ',', '.') ?></h4>
                    <small>Total Ventas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="row mb-3">
        <div class="col-md-6">
            <form class="d-flex" method="GET" action="ver_pedidos.php">
                <input type="text" class="form-control me-2" name="buscar" placeholder="🔍 Buscar cliente o teléfono..." value="<?= htmlspecialchars($busqueda) ?>">
                <button type="submit" class="btn btn-info">Buscar</button>
                <?php if ($busqueda): ?>
                    <a href="ver_pedidos.php" class="btn btn-secondary ms-2">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-md-6">
            <div class="btn-group">
                <a href="?orden=fecha&dir=DESC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">📅 Más Recientes</a>
                <a href="?orden=fecha&dir=ASC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">📅 Más Antiguos</a>
                <a href="?orden=total&dir=DESC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">💰 Mayor Valor</a>
                <a href="?orden=nombre&dir=ASC<?= $busqueda ? "&buscar=" . urlencode($busqueda) : '' ?>" class="btn btn-sm btn-outline-secondary">👤 A-Z</a>
            </div>
        </div>
    </div>

    <!-- Tabla de pedidos -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>📅 Fecha</th>
                    <th>👤 Cliente</th>
                    <th>🥪 Productos</th>
                    <th>📊 Cantidad</th>
                    <th>🍞 Planchas</th>
                    <th>💰 Total</th>
                    <th>📱 Contacto</th>
                    <th>🚚 Modalidad</th>
                    <th>💳 Pago</th>
                    <th>⚡ Estado</th>
                    <th>🔧 Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            if ($resultado->num_rows > 0) {
                while ($pedido = $resultado->fetch_assoc()) { 
                    $productos = json_decode($pedido['productos'], true) ?: [];
                    $productos_texto = '';
                    
                    if (!empty($productos)) {
                        foreach ($productos as $producto) {
                            $productos_texto .= $producto['producto'] ?? 'Producto';
                            if (!empty($producto['sabores'])) {
                                $productos_texto .= ' (' . $producto['sabores'] . ')';
                            }
                            $productos_texto .= '<br>';
                        }
                    } else {
                        $productos_texto = 'Pedido Personalizado';
                    }
                    
                    $estado_class = '';
                    switch($pedido['estado']) {
                        case 'Pendiente': $estado_class = 'estado-pendiente'; break;
                        case 'En Preparación': $estado_class = 'estado-preparacion'; break;
                        case 'Listo': $estado_class = 'estado-listo'; break;
                        case 'Entregado': $estado_class = 'estado-entregado'; break;
                    }
            ?>
                <tr class="<?= $estado_class ?>">
                    <td><?= date("d/m/Y H:i", strtotime($pedido['fecha'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($pedido['nombre']) ?> <?= htmlspecialchars($pedido['apellido']) ?></strong>
                    </td>
                    <td><?= $productos_texto ?></td>
                    <td class="text-center">
                        <span class="badge bg-secondary"><?= $pedido['cantidad'] ?></span>
                    </td>
                    <td class="text-center"><?= $pedido['planchas'] ?></td>
                    <td class="text-end">
                        <strong>$<?= number_format($pedido['total'], 0, ',', '.') ?></strong>
                    </td>
                    <td><?= htmlspecialchars($pedido['contacto']) ?></td>
                    <td>
                        <span class="badge <?= $pedido['modalidad'] === 'Envío' ? 'bg-info' : 'bg-secondary' ?>">
                            <?= $pedido['modalidad'] === 'Envío' ? '🚚' : '🏪' ?> <?= htmlspecialchars($pedido['modalidad']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $pedido['pago'] === 'Efectivo' ? 'badge-efectivo' : 'badge-transferencia' ?>">
                            <?= $pedido['pago'] === 'Efectivo' ? '💰' : '💳' ?> <?= htmlspecialchars($pedido['pago']) ?>
                        </span>
                    </td>
                    <td>
                        <select class="form-select form-select-sm" onchange="cambiarEstado(<?= $pedido['id'] ?>, this.value)">
                            <option value="Pendiente" <?= $pedido['estado'] === 'Pendiente' ? 'selected' : '' ?>>⏳ Pendiente</option>
                            <option value="En Preparación" <?= $pedido['estado'] === 'En Preparación' ? 'selected' : '' ?>>👨‍🍳 En Preparación</option>
                            <option value="Listo" <?= $pedido['estado'] === 'Listo' ? 'selected' : '' ?>>✅ Listo</option>
                            <option value="Entregado" <?= $pedido['estado'] === 'Entregado' ? 'selected' : '' ?>>📦 Entregado</option>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" onclick="verDetalles(<?= $pedido['id'] ?>)" title="Ver detalles">👁️</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarPedido(<?= $pedido['id'] ?>)" title="Eliminar">🗑️</button>
                    </td>
                </tr>
            <?php 
                } 
            } else {
                echo '<tr><td colspan="11" class="text-center">No hay pedidos registrados</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para ver detalles -->
<div class="modal fade" id="modalDetalles" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">📋 Detalle del Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDetalles">
                <!-- Contenido se carga aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Cambiar estado del pedido
function cambiarEstado(id, estado) {
    fetch('ver_pedidos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cambiar_estado=1&id=${id}&estado=${encodeURIComponent(estado)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cambiar clase de la fila
            const fila = event.target.closest('tr');
            fila.className = '';
            switch(estado) {
                case 'Pendiente': fila.className = 'estado-pendiente'; break;
                case 'En Preparación': fila.className = 'estado-preparacion'; break;
                case 'Listo': fila.className = 'estado-listo'; break;
                case 'Entregado': fila.className = 'estado-entregado'; break;
            }
        } else {
            alert('Error al actualizar el estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    });
}

// Ver detalles del pedido
function verDetalles(id) {
    fetch(`obtener_pedido.php?id=${id}`)
        .then(response => response.json())
        .then(pedido => {
            const contenido = document.getElementById('contenidoDetalles');
            
            let productosHtml = '';
            if (pedido.productos) {
                const productos = JSON.parse(pedido.productos);
                productos.forEach(producto => {
                    productosHtml += `
                        <div class="border rounded p-2 mb-2">
                            <strong>${producto.producto}</strong>
                            <div class="row">
                                <div class="col-6">Cantidad: ${producto.cantidad}</div>
                                <div class="col-6 text-end">$${producto.precio?.toLocaleString() || '0'}</div>
                            </div>
                            ${producto.sabores ? `<small class="text-success">Sabores: ${producto.sabores}</small>` : ''}
                        </div>
                    `;
                });
            }
            
            contenido.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>📞 Información del Cliente</h6>
                        <p><strong>Nombre:</strong> ${pedido.nombre} ${pedido.apellido}</p>
                        <p><strong>Contacto:</strong> ${pedido.contacto}</p>
                        <p><strong>Dirección:</strong> ${pedido.direccion}</p>
                        <p><strong>Modalidad:</strong> 
                            <span class="badge ${pedido.modalidad === 'Envío' ? 'bg-info' : 'bg-secondary'}">
                                ${pedido.modalidad === 'Envío' ? '🚚' : '🏪'} ${pedido.modalidad}
                            </span>
                        </p>
                        <p><strong>Forma de Pago:</strong> 
                            <span class="badge ${pedido.pago === 'Efectivo' ? 'bg-success' : 'bg-primary'}">
                                ${pedido.pago === 'Efectivo' ? '💰' : '💳'} ${pedido.pago}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>📊 Información del Pedido</h6>
                        <p><strong>Fecha:</strong> ${new Date(pedido.fecha).toLocaleString('es-AR')}</p>
                        <p><strong>Cantidad Total:</strong> ${pedido.cantidad} sándwiches</p>
                        <p><strong>Planchas:</strong> ${pedido.planchas}</p>
                        <p><strong>Total:</strong> <span class="h5 text-success">$${pedido.total?.toLocaleString() || '0'}</span></p>
                        <p><strong>Estado:</strong> 
                            <span class="badge bg-warning">${pedido.estado}</span>
                        </p>
                    </div>
                </div>
                
                <hr>
                <h6>🥪 Productos del Pedido</h6>
                ${productosHtml}
                
                ${pedido.observaciones ? `
                    <hr>
                    <h6>📝 Observaciones</h6>
                    <div class="alert alert-light">${pedido.observaciones}</div>
                ` : ''}
            `;
            
            new bootstrap.Modal(document.getElementById('modalDetalles')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles');
        });
}

// Eliminar pedido
function eliminarPedido(id) {
    if (confirm('¿Está seguro que desea eliminar este pedido?')) {
        window.location.href = `eliminar_pedido.php?id=${id}`;
    }
}

// Filtro dinámico en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const buscarInput = document.querySelector('input[name="buscar"]');
    if (buscarInput) {
        buscarInput.addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    }
});

// Actualizar automáticamente cada 30 segundos
setInterval(function() {
    location.reload();
}, 30000);
</script>

</body>
</html>

<?php $conexion->close(); ?>