<?php
require_once '/var/www/html/sangucheria/config.php';

try {
    $conexion = getConnection();
    
    // Manejar cambio de estado via AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
        header('Content-Type: application/json; charset=utf-8');
        
        // Validar token CSRF
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
        }
        
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $estado = sanitizeInput($_POST['estado']);
        
        if ($id === false || $id <= 0) {
            jsonResponse(['success' => false, 'message' => 'ID inválido']);
        }
        
        // Validar estados permitidos
        $estados_validos = ['Pendiente', 'En Preparación', 'Listo', 'Entregado'];
        if (!in_array($estado, $estados_validos)) {
            jsonResponse(['success' => false, 'message' => 'Estado no válido']);
        }
        
        $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            jsonResponse(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            $stmt->close();
            jsonResponse(['success' => false, 'message' => 'Error al actualizar o pedido no encontrado']);
        }
    }
    
    // Procesar parámetros de búsqueda y filtrado
    $busqueda = isset($_GET['buscar']) ? sanitizeInput($_GET['buscar']) : '';
    $orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha';
    $dir = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
    $estado_filtro = isset($_GET['estado']) ? sanitizeInput($_GET['estado']) : '';
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
    
    // Validar parámetros de ordenamiento
    $orden_permitidos = ['fecha', 'nombre', 'apellido', 'total', 'estado', 'cantidad'];
    $dir_permitidos = ['ASC', 'DESC'];
    
    if (!in_array($orden, $orden_permitidos)) $orden = 'fecha';
    if (!in_array($dir, $dir_permitidos)) $dir = 'DESC';
    
    // Construir consulta con filtros
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($busqueda !== '') {
        $busqueda_param = "%{$busqueda}%";
        $where_conditions[] = "(nombre LIKE ? OR apellido LIKE ? OR contacto LIKE ?)";
        $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param]);
        $types .= 'sss';
    }
    
    if ($estado_filtro !== '') {
        $where_conditions[] = "estado = ?";
        $params[] = $estado_filtro;
        $types .= 's';
    }
    
    if ($fecha_desde !== '' && $fecha_hasta !== '') {
        // Validar fechas
        if (DateTime::createFromFormat('Y-m-d', $fecha_desde) && DateTime::createFromFormat('Y-m-d', $fecha_hasta)) {
            $where_conditions[] = "DATE(fecha) BETWEEN ? AND ?";
            $params[] = $fecha_desde;
            $params[] = $fecha_hasta;
            $types .= 'ss';
        }
    }
    
    // Construir consulta SQL
    $sql = "SELECT * FROM pedidos";
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    $sql .= " ORDER BY {$orden} {$dir}";
    
    // Ejecutar consulta
    if (!empty($params)) {
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $resultado = $conexion->query($sql);
    }
    
    // Calcular estadísticas
    $sql_stats = "SELECT 
        COUNT(*) as total_pedidos,
        COALESCE(SUM(cantidad), 0) as total_sandwiches,
        COALESCE(SUM(total), 0) as total_ventas,
        COUNT(CASE WHEN DATE(fecha) = CURDATE() THEN 1 END) as pedidos_hoy,
        COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN cantidad ELSE 0 END), 0) as sandwiches_hoy,
        COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total ELSE 0 END), 0) as ventas_hoy,
        COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN estado = 'En Preparación' THEN 1 END) as en_preparacion,
        COUNT(CASE WHEN estado = 'Listo' THEN 1 END) as listos,
        COUNT(CASE WHEN estado = 'Entregado' THEN 1 END) as entregados
    FROM pedidos";
    
    if (!empty($where_conditions)) {
        $sql_stats .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    if (!empty($params)) {
        $stmt_stats = $conexion->prepare($sql_stats);
        $stmt_stats->bind_param($types, ...$params);
        $stmt_stats->execute();
        $estadisticas = $stmt_stats->get_result()->fetch_assoc();
        $stmt_stats->close();
    } else {
        $estadisticas = $conexion->query($sql_stats)->fetch_assoc();
    }
    
    $csrf_token = generateCSRFToken();
    
} catch (Exception $e) {
    error_log("Error en ver_pedidos.php: " . $e->getMessage());
    if (isset($_POST['cambiar_estado']) && isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Error interno del servidor']);
    }
    $error_message = 'Error al cargar los pedidos';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Pedidos - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .estado-pendiente { background-color: #fff3cd; }
        .estado-preparacion { background-color: #cff4fc; }
        .estado-listo { background-color: #d1e7dd; }
        .estado-entregado { background-color: #f8f9fa; }
        .table-hover tbody tr:hover { background-color: #f5f5f5; }
        .badge-efectivo { background-color: #28a745; }
        .badge-transferencia { background-color: #007bff; }
        .stats-card { transition: transform 0.2s; }
        .stats-card:hover { transform: translateY(-2px); }
        .sticky-header { position: sticky; top: 0; z-index: 1020; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-bar-chart-line"></i> Listado de Pedidos</h2>
            <small class="text-muted"><?= APP_NAME ?> - <i class="bi bi-telephone"></i> <?= APP_PHONE ?></small>
        </div>
        <div>
            <div class="btn-group">
                <a href="exportar_pedidos.php?formato=excel" class="btn btn-success">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                </a>
                <a href="exportar_pedidos.php?formato=csv" class="btn btn-outline-success">
                    <i class="bi bi-filetype-csv"></i> CSV
                </a>
            </div>
            <a href="index.php" class="btn btn-primary ms-2">
                <i class="bi bi-arrow-left"></i> Volver al Sistema
            </a>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Estadísticas -->
    <?php if (isset($estadisticas)): ?>
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center stats-card">
                <div class="card-body p-3">
                    <h4 class="text-primary mb-1"><?= number_format($estadisticas['pedidos_hoy']) ?></h4>
                    <small class="text-muted">Pedidos Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center stats-card">
                <div class="card-body p-3">
                    <h4 class="text-success mb-1"><?= number_format($estadisticas['sandwiches_hoy']) ?></h4>
                    <small class="text-muted">Sándwiches Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center stats-card">
                <div class="card-body p-3">
                    <h4 class="text-warning mb-1">$<?= number_format($estadisticas['ventas_hoy'], 0, ',', '.') ?></h4>
                    <small class="text-muted">Ventas Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center stats-card">
                <div class="card-body p-3">
                    <h4 class="text-info mb-1"><?= number_format($estadisticas['total_pedidos']) ?></h4>
                    <small class="text-muted">Total Pedidos</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center stats-card">
                <div class="card-body p-3">
                    <h4 class="text-secondary mb-1"><?= number_format($estadisticas['total_sandwiches']) ?></h4>
                    <small class="text-muted">Total Sándwiches</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center stats-card">
                <div class="card-body p-3">
                    <h4 class="text-dark mb-1">$<?= number_format($estadisticas['total_ventas'], 0, ',', '.') ?></h4>
                    <small class="text-muted">Total Ventas</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estados rápidos -->
    <?php if (isset($estadisticas)): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-group" role="group">
                <a href="?estado=" class="btn btn-outline-secondary <?= $estado_filtro === '' ? 'active' : '' ?>">
                    Todos (<?= $estadisticas['total_pedidos'] ?>)
                </a>
                <a href="?estado=Pendiente" class="btn btn-outline-warning <?= $estado_filtro === 'Pendiente' ? 'active' : '' ?>">
                    Pendientes (<?= $estadisticas['pendientes'] ?>)
                </a>
                <a href="?estado=En Preparación" class="btn btn-outline-info <?= $estado_filtro === 'En Preparación' ? 'active' : '' ?>">
                    En Preparación (<?= $estadisticas['en_preparacion'] ?>)
                </a>
                <a href="?estado=Listo" class="btn btn-outline-success <?= $estado_filtro === 'Listo' ? 'active' : '' ?>">
                    Listos (<?= $estadisticas['listos'] ?>)
                </a>
                <a href="?estado=Entregado" class="btn btn-outline-dark <?= $estado_filtro === 'Entregado' ? 'active' : '' ?>">
                    Entregados (<?= $estadisticas['entregados'] ?>)
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros y búsqueda -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="ver_pedidos.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar:</label>
                    <input type="text" class="form-control" name="buscar" 
                           placeholder="Nombre, apellido o teléfono..." 
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado:</label>
                    <select class="form-select" name="estado">
                        <option value="">Todos</option>
                        <option value="Pendiente" <?= $estado_filtro === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="En Preparación" <?= $estado_filtro === 'En Preparación' ? 'selected' : '' ?>>En Preparación</option>
                        <option value="Listo" <?= $estado_filtro === 'Listo' ? 'selected' : '' ?>>Listo</option>
                        <option value="Entregado" <?= $estado_filtro === 'Entregado' ? 'selected' : '' ?>>Entregado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde:</label>
                    <input type="date" class="form-control" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta:</label>
                    <input type="date" class="form-control" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ordenar por:</label>
                    <select class="form-select" name="orden">
                        <option value="fecha" <?= $orden === 'fecha' ? 'selected' : '' ?>>Fecha</option>
                        <option value="nombre" <?= $orden === 'nombre' ? 'selected' : '' ?>>Nombre</option>
                        <option value="total" <?= $orden === 'total' ? 'selected' : '' ?>>Total</option>
                        <option value="cantidad" <?= $orden === 'cantidad' ? 'selected' : '' ?>>Cantidad</option>
                        <option value="estado" <?= $orden === 'estado' ? 'selected' : '' ?>>Estado</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Orden:</label>
                    <select class="form-select" name="dir">
                        <option value="DESC" <?= $dir === 'DESC' ? 'selected' : '' ?>>Desc</option>
                        <option value="ASC" <?= $dir === 'ASC' ? 'selected' : '' ?>>Asc</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="ver_pedidos.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de pedidos -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark sticky-header">
                <tr>
                    <th><i class="bi bi-calendar"></i> Fecha</th>
                    <th><i class="bi bi-person"></i> Cliente</th>
                    <th><i class="bi bi-basket"></i> Productos</th>
                    <th><i class="bi bi-bar-chart"></i> Cantidad</th>
                    <th><i class="bi bi-stack"></i> Planchas</th>
                    <th><i class="bi bi-currency-dollar"></i> Total</th>
                    <th><i class="bi bi-telephone"></i> Contacto</th>
                    <th><i class="bi bi-truck"></i> Modalidad</th>
                    <th><i class="bi bi-credit-card"></i> Pago</th>
                    <th><i class="bi bi-hourglass"></i> Estado</th>
                    <th><i class="bi bi-gear"></i> Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            if (isset($resultado) && $resultado->num_rows > 0) {
                while ($pedido = $resultado->fetch_assoc()) { 
                    $productos = json_decode($pedido['productos'], true) ?: [];
                    $productos_texto = '';
                    
                    if (!empty($productos)) {
                        $productos_array = [];
                        foreach ($productos as $producto) {
                            $producto_texto = htmlspecialchars($producto['producto'] ?? 'Producto');
                            if (!empty($producto['sabores'])) {
                                $producto_texto .= ' <small class="text-muted">(' . htmlspecialchars($producto['sabores']) . ')</small>';
                            }
                            $productos_array[] = $producto_texto;
                        }
                        $productos_texto = implode('<br>', $productos_array);
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
                <tr class="<?= $estado_class ?>" data-id="<?= $pedido['id'] ?>">
                    <td>
                        <small><?= date("d/m/Y", strtotime($pedido['fecha'])) ?></small><br>
                        <small class="text-muted"><?= date("H:i", strtotime($pedido['fecha'])) ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($pedido['nombre']) ?> <?= htmlspecialchars($pedido['apellido']) ?></strong>
                    </td>
                    <td><small><?= $productos_texto ?></small></td>
                    <td class="text-center">
                        <span class="badge bg-secondary"><?= number_format($pedido['cantidad']) ?></span>
                    </td>
                    <td class="text-center"><?= number_format($pedido['planchas'], 1) ?></td>
                    <td class="text-end">
                        <strong>$<?= number_format($pedido['total'], 0, ',', '.') ?></strong>
                    </td>
                    <td>
                        <small>
                            <i class="bi bi-telephone"></i> 
                            <a href="tel:<?= htmlspecialchars($pedido['contacto']) ?>"><?= htmlspecialchars($pedido['contacto']) ?></a>
                        </small>
                    </td>
                    <td>
                        <span class="badge <?= $pedido['modalidad'] === 'Envío' ? 'bg-info' : 'bg-secondary' ?>">
                            <i class="bi bi-<?= $pedido['modalidad'] === 'Envío' ? 'truck' : 'shop' ?>"></i>
                            <?= htmlspecialchars($pedido['modalidad']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $pedido['pago'] === 'Efectivo' ? 'badge-efectivo' : 'badge-transferencia' ?>">
                            <i class="bi bi-<?= $pedido['pago'] === 'Efectivo' ? 'cash' : 'credit-card' ?>"></i>
                            <?= htmlspecialchars($pedido['pago']) ?>
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
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-info" onclick="verDetalles(<?= $pedido['id'] ?>)" title="Ver detalles">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarPedido(<?= $pedido['id'] ?>)" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
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
                <h5 class="modal-title"><i class="bi bi-clipboard-data"></i> Detalle del Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDetalles">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = '<?= $csrf_token ?>';

// Cambiar estado del pedido
function cambiarEstado(id, estado) {
    const selectElement = event.target;
    const originalValue = selectElement.dataset.originalValue || selectElement.value;
    
    // Guardar valor original
    selectElement.dataset.originalValue = originalValue;
    
    // Deshabilitar select durante la actualización
    selectElement.disabled = true;
    
    fetch('ver_pedidos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cambiar_estado=1&id=${id}&estado=${encodeURIComponent(estado)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cambiar clase de la fila
            const fila = selectElement.closest('tr');
            fila.className = '';
            switch(estado) {
                case 'Pendiente': fila.className = 'estado-pendiente'; break;
                case 'En Preparación': fila.className = 'estado-preparacion'; break;
                case 'Entregado': fila.className = 'estado-entregado'; break;
            }
            
            // Actualizar valor original
            selectElement.dataset.originalValue = estado;
            showAlert('success', data.message);
        } else {
            // Revertir al valor original
            selectElement.value = originalValue;
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.value = originalValue;
        showAlert('danger', 'Error de conexión');
    })
    .finally(() => {
        selectElement.disabled = false;
    });
}

// Ver detalles del pedido
function verDetalles(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
    const contenido = document.getElementById('contenidoDetalles');
    
    // Mostrar spinner
    contenido.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`obtener_pedido.php?id=${id}`)
        .then(response => response.json())
        .then(pedido => {
            if (pedido.error) {
                throw new Error(pedido.error);
            }
            
            let productosHtml = '';
            if (pedido.productos && pedido.productos !== '[]') {
                try {
                    const productos = JSON.parse(pedido.productos);
                    productos.forEach(producto => {
                        productosHtml += `
                            <div class="border rounded p-3 mb-2 bg-light">
                                <div class="row">
                                    <div class="col-8">
                                        <strong>${producto.producto || 'Producto'}</strong>
                                        <br><small class="text-muted">Cantidad: ${producto.cantidad || 0}</small>
                                        ${producto.sabores ? `<br><small class="text-success"><i class="bi bi-star"></i> ${producto.sabores}</small>` : ''}
                                    </div>
                                    <div class="col-4 text-end">
                                        <h6 class="mb-0">${(producto.precio || 0).toLocaleString()}</h6>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } catch (e) {
                    productosHtml = '<div class="alert alert-warning">Error al procesar productos</div>';
                }
            } else {
                productosHtml = '<div class="alert alert-info">Pedido personalizado sin detalles de productos</div>';
            }
            
            // Determinar color del badge de estado
            let estadoBadgeClass = 'bg-secondary';
            switch(pedido.estado) {
                case 'Pendiente': estadoBadgeClass = 'bg-warning'; break;
                case 'En Preparación': estadoBadgeClass = 'bg-info'; break;
                case 'Listo': estadoBadgeClass = 'bg-success'; break;
                case 'Entregado': estadoBadgeClass = 'bg-dark'; break;
            }
            
            contenido.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-person-circle"></i> Información del Cliente</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Nombre:</strong></td><td>${pedido.nombre} ${pedido.apellido}</td></tr>
                            <tr><td><strong>Contacto:</strong></td><td><a href="tel:${pedido.contacto}"><i class="bi bi-telephone"></i> ${pedido.contacto}</a></td></tr>
                            <tr><td><strong>Dirección:</strong></td><td><i class="bi bi-geo-alt"></i> ${pedido.direccion}</td></tr>
                            <tr>
                                <td><strong>Modalidad:</strong></td>
                                <td>
                                    <span class="badge ${pedido.modalidad === 'Envío' ? 'bg-info' : 'bg-secondary'}">
                                        <i class="bi bi-${pedido.modalidad === 'Envío' ? 'truck' : 'shop'}"></i> ${pedido.modalidad}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Forma de Pago:</strong></td>
                                <td>
                                    <span class="badge ${pedido.pago === 'Efectivo' ? 'bg-success' : 'bg-primary'}">
                                        <i class="bi bi-${pedido.pago === 'Efectivo' ? 'cash' : 'credit-card'}"></i> ${pedido.pago}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-clipboard-data"></i> Información del Pedido</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ID:</strong></td><td>#${pedido.id}</td></tr>
                            <tr><td><strong>Fecha:</strong></td><td><i class="bi bi-calendar"></i> ${pedido.fecha_formateada || new Date(pedido.fecha).toLocaleString('es-AR')}</td></tr>
                            <tr><td><strong>Cantidad Total:</strong></td><td><span class="badge bg-secondary">${pedido.cantidad}</span> sándwiches</td></tr>
                            <tr><td><strong>Planchas:</strong></td><td>${pedido.planchas}</td></tr>
                            <tr><td><strong>Total:</strong></td><td><span class="h5 text-success">${(pedido.total || 0).toLocaleString()}</span></td></tr>
                            <tr>
                                <td><strong>Estado:</strong></td>
                                <td><span class="badge ${estadoBadgeClass}">${pedido.estado}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                <h6><i class="bi bi-basket"></i> Productos del Pedido</h6>
                ${productosHtml}
                
                ${pedido.observaciones ? `
                    <hr>
                    <h6><i class="bi bi-chat-square-text"></i> Observaciones</h6>
                    <div class="alert alert-light">
                        <i class="bi bi-info-circle"></i> ${pedido.observaciones}
                    </div>
                ` : ''}
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            contenido.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error al cargar los detalles: ${error.message}
                </div>
            `;
        });
}

// Eliminar pedido
function eliminarPedido(id) {
    if (!confirm('¿Está seguro que desea eliminar este pedido?\nEsta acción no se puede deshacer.')) {
        return;
    }
    
    // Crear formulario para envío POST con CSRF
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'eliminar_pedido.php';
    
    const inputId = document.createElement('input');
    inputId.type = 'hidden';
    inputId.name = 'id';
    inputId.value = id;
    
    const inputToken = document.createElement('input');
    inputToken.type = 'hidden';
    inputToken.name = 'csrf_token';
    inputToken.value = csrfToken;
    
    form.appendChild(inputId);
    form.appendChild(inputToken);
    document.body.appendChild(form);
    form.submit();
}

// Función para mostrar alertas
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Filtro dinámico en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const buscarInput = document.querySelector('input[name="buscar"]');
    if (buscarInput) {
        let timeoutId;
        buscarInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                const filtro = this.value.toLowerCase();
                const filas = document.querySelectorAll('tbody tr[data-id]');
                
                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = texto.includes(filtro) ? '' : 'none';
                });
            }, 300);
        });
    }
    
    // Auto-refresh cada 30 segundos solo si no hay filtros activos
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilters = urlParams.has('buscar') || urlParams.has('estado') || urlParams.has('fecha_desde');
    
    if (!hasFilters) {
        setInterval(function() {
            // Solo refrescar si la página está visible
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 30000);
    }
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl + F para buscar
    if (e.ctrlKey && e.key === 'f') {
        e.preventDefault();
        document.querySelector('input[name="buscar"]').focus();
    }
    
    // Escape para cerrar modal
    if (e.key === 'Escape') {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetalles'));
        if (modal) {
            modal.hide();
        }
    }
});
</script>

</body>
</html>

<?php
if (isset($conexion)) {
    $conexion->close();
}
if (isset($stmt)) {
    $stmt->close();
}
?>