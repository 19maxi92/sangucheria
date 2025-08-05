<?php
require_once '/var/www/html/sangucheria/config.php';

try {
    $conexion = getConnection();
    
    // Manejar operaciones AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        
        // Validar token CSRF para operaciones POST
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido']);
        }
        
        if (isset($_POST['accion'])) {
            switch ($_POST['accion']) {
                case 'agregar':
                    // Validar y sanitizar datos
                    $required_fields = ['nombre', 'apellido', 'contacto', 'direccion', 'modalidad', 'producto', 'observacion'];
                    foreach ($required_fields as $field) {
                        if (empty($_POST[$field])) {
                            jsonResponse(['success' => false, 'message' => "El campo $field es obligatorio"]);
                        }
                    }
                    
                    $nombre = sanitizeInput($_POST['nombre']);
                    $apellido = sanitizeInput($_POST['apellido']);
                    $contacto = sanitizeInput($_POST['contacto']);
                    $direccion = sanitizeInput($_POST['direccion']);
                    $modalidad = sanitizeInput($_POST['modalidad']);
                    $producto = sanitizeInput($_POST['producto']);
                    $observacion = sanitizeInput($_POST['observacion']);
                    
                    // Validar valores específicos
                    $modalidades_validas = ['Retira', 'Envío'];
                    $productos_validos = [
                        '24 Jamón y Queso', '24 Surtidos', '24 Surtidos Premium',
                        '48 Jamón y Queso', '48 Surtidos Clásicos', '48 Surtidos Especiales', '48 Surtidos Premium'
                    ];
                    
                    if (!in_array($modalidad, $modalidades_validas)) {
                        jsonResponse(['success' => false, 'message' => 'Modalidad no válida']);
                    }
                    
                    if (!in_array($producto, $productos_validos)) {
                        jsonResponse(['success' => false, 'message' => 'Producto no válido']);
                    }
                    
                    // Verificar si el cliente ya existe
                    $stmt_check = $conexion->prepare("SELECT id FROM clientes_fijos WHERE nombre = ? AND apellido = ? AND contacto = ?");
                    $stmt_check->bind_param("sss", $nombre, $apellido, $contacto);
                    $stmt_check->execute();
                    
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $stmt_check->close();
                        jsonResponse(['success' => false, 'message' => 'Cliente ya existe']);
                    }
                    $stmt_check->close();
                    
                    // Insertar cliente
                    $stmt = $conexion->prepare("INSERT INTO clientes_fijos (nombre, apellido, contacto, direccion, modalidad, producto, observacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $nombre, $apellido, $contacto, $direccion, $modalidad, $producto, $observacion);
                    
                    if ($stmt->execute()) {
                        $stmt->close();
                        jsonResponse(['success' => true, 'message' => 'Cliente agregado correctamente']);
                    } else {
                        $stmt->close();
                        jsonResponse(['success' => false, 'message' => 'Error al agregar cliente']);
                    }
                    break;
                    
                case 'eliminar':
                    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
                    if ($id === false || $id <= 0) {
                        jsonResponse(['success' => false, 'message' => 'ID inválido']);
                    }
                    
                    $stmt = $conexion->prepare("DELETE FROM clientes_fijos WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $stmt->close();
                        jsonResponse(['success' => true, 'message' => 'Cliente eliminado']);
                    } else {
                        $stmt->close();
                        jsonResponse(['success' => false, 'message' => 'Error al eliminar o cliente no encontrado']);
                    }
                    break;
                    
                case 'hacer_pedido':
                    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
                    if ($id === false || $id <= 0) {
                        jsonResponse(['success' => false, 'message' => 'ID inválido']);
                    }
                    
                    $stmt = $conexion->prepare("SELECT * FROM clientes_fijos WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    
                    if ($cliente = $resultado->fetch_assoc()) {
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
                        $cantidad = (int)explode(' ', $producto)[0];
                        $planchas = round($cantidad / 24, 2);
                        
                        // Crear producto JSON
                        $productos_json = json_encode([
                            [
                                'producto' => $producto,
                                'precio' => $precio,
                                'cantidad' => $cantidad,
                                'sabores' => $cliente['observacion'],
                                'id' => time()
                            ]
                        ], JSON_UNESCAPED_UNICODE);
                        
                        // Insertar pedido
                        $stmt_pedido = $conexion->prepare("INSERT INTO pedidos 
                            (nombre, apellido, cantidad, planchas, contacto, direccion, modalidad, observaciones, productos, total, pago, estado, fecha) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Efectivo', 'Pendiente', NOW())");
                        
                        $stmt_pedido->bind_param("sssdsssss", 
                            $cliente['nombre'], $cliente['apellido'], $cantidad, $planchas,
                            $cliente['contacto'], $cliente['direccion'], $cliente['modalidad'],
                            $cliente['observacion'], $productos_json, $precio
                        );
                        
                        if ($stmt_pedido->execute()) {
                            $pedido_id = $conexion->insert_id;
                            $stmt_pedido->close();
                            jsonResponse([
                                'success' => true, 
                                'message' => "Pedido creado para {$cliente['nombre']} {$cliente['apellido']}",
                                'pedido_id' => $pedido_id
                            ]);
                        } else {
                            $stmt_pedido->close();
                            jsonResponse(['success' => false, 'message' => 'Error al crear pedido']);
                        }
                    } else {
                        jsonResponse(['success' => false, 'message' => 'Cliente no encontrado']);
                    }
                    
                    $stmt->close();
                    break;
                    
                default:
                    jsonResponse(['success' => false, 'message' => 'Acción no válida']);
            }
        }
        
        exit;
    }
    
    // Si es GET, mostrar la página
    $resultado = $conexion->query("SELECT * FROM clientes_fijos ORDER BY nombre, apellido");
    $csrf_token = generateCSRFToken();
    
} catch (Exception $e) {
    error_log("Error en clientes_fijos.php: " . $e->getMessage());
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'message' => 'Error interno del servidor']);
    }
    $error_message = 'Error interno del servidor';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes Fijos - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-people-fill"></i> Clientes Fijos</h2>
            <small class="text-muted"><?= APP_NAME ?> - <i class="bi bi-telephone"></i> <?= APP_PHONE ?></small>
        </div>
        <div>
            <button class="btn btn-success me-2" onclick="mostrarFormulario()">
                <i class="bi bi-plus-circle"></i> Agregar Cliente Fijo
            </button>
            <a href="index.php" class="btn btn-secondary">
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

    <!-- Lista de clientes fijos -->
    <div id="listaClientes" class="mb-4">
        <?php if (isset($resultado) && $resultado->num_rows > 0): ?>
            <?php while ($cliente = $resultado->fetch_assoc()): ?>
                <div class="card mb-3 card-hover">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <h5 class="mb-1">
                                    <?= htmlspecialchars($cliente['nombre']) ?> <?= htmlspecialchars($cliente['apellido']) ?>
                                </h5>
                                <small class="text-muted">
                                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($cliente['contacto']) ?>
                                </small><br>
                                <small class="text-muted">
                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($cliente['direccion']) ?>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <strong>Producto habitual:</strong><br>
                                <span class="badge bg-primary"><?= htmlspecialchars($cliente['producto']) ?></span><br>
                                <small class="text-muted">
                                    Modalidad: <?= htmlspecialchars($cliente['modalidad']) ?>
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="realizarPedido(<?= $cliente['id'] ?>)">
                                        <i class="bi bi-clipboard-plus"></i> Hacer Pedido
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="cargarPedidoMultiple(<?= $cliente['id'] ?>)">
                                        <i class="bi bi-stack"></i> Multiple
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="eliminarCliente(<?= $cliente['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small><strong>Observaciones:</strong> <?= htmlspecialchars($cliente['observacion']) ?></small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i> No hay clientes fijos registrados.
            </div>
        <?php endif; ?>
    </div>

    <!-- Formulario para agregar cliente -->
    <div id="formularioCliente" class="card" style="display:none;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nuevo Cliente Fijo</h5>
        </div>
        <div class="card-body">
            <form id="formNuevoCliente">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre:</label>
                        <input type="text" class="form-control" name="nombre" required maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apellido:</label>
                        <input type="text" class="form-control" name="apellido" required maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">N° de Contacto:</label>
                        <input type="tel" class="form-control" name="contacto" required maxlength="20">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dirección:</label>
                        <input type="text" class="form-control" name="direccion" required maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modalidad:</label>
                        <select class="form-select" name="modalidad" required>
                            <option value="">Seleccionar...</option>
                            <option value="Retira"><i class="bi bi-shop"></i> Retira en Local</option>
                            <option value="Envío"><i class="bi bi-truck"></i> Envío a Domicilio</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Producto Habitual:</label>
                        <select class="form-select" name="producto" required>
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
                        <textarea class="form-control" name="observacion" rows="3" required maxlength="500"
                                placeholder="Ej: 8 jamón crudo, 8 roquefort, 8 palmito..."></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Guardar Cliente
                    </button>
                    <button type="button" class="btn btn-secondary ms-2" onclick="cerrarFormulario()">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para pedidos múltiples -->
<div class="modal fade" id="modalPedidoMultiple" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Pedidos Múltiples</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Cuántos pedidos deseas crear para este cliente?</p>
                <div class="mb-3">
                    <label class="form-label">Cantidad de pedidos:</label>
                    <input type="number" class="form-control" id="cantidadPedidos" min="1" max="10" value="1">
                    <small class="text-muted">Máximo 10 pedidos</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmarPedidoMultiple()">Crear Pedidos</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let clienteIdPedidoMultiple = null;

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
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';
    submitBtn.disabled = true;
    
    const datos = new FormData(this);
    datos.append('accion', 'agregar');
    
    fetch('clientes_fijos.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error de conexión');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
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
    datos.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('clientes_fijos.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (confirm('¿Desea ver el pedido creado?')) {
                window.open('ver_pedidos.php', '_blank');
            }
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error de conexión');
    });
}

// Cargar pedido múltiple
function cargarPedidoMultiple(id) {
    clienteIdPedidoMultiple = id;
    new bootstrap.Modal(document.getElementById('modalPedidoMultiple')).show();
}

// Confirmar pedido múltiple
function confirmarPedidoMultiple() {
    const cantidad = document.getElementById('cantidadPedidos').value;
    if (cantidad < 1 || cantidad > 10) {
        showAlert('warning', 'La cantidad debe estar entre 1 y 10');
        return;
    }
    
    window.location.href = `cargar_pedido_fijo.php?id=${clienteIdPedidoMultiple}&cantidad=${cantidad}`;
}

// Eliminar cliente fijo
function eliminarCliente(id) {
    if (!confirm('¿Está seguro que desea eliminar este cliente fijo?\nEsta acción no se puede deshacer.')) {
        return;
    }
    
    const datos = new FormData();
    datos.append('accion', 'eliminar');
    datos.append('id', id);
    datos.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('clientes_fijos.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error de conexión');
    });
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

// Validación en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formNuevoCliente');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
    
    function validateField(field) {
        field.classList.remove('is-invalid', 'is-valid');
        
        if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('is-invalid');
            return false;
        }
        
        if (field.name === 'contacto' && field.value) {
            const phonePattern = /^[\d\s\-\+\(\)]+$/;
            if (!phonePattern.test(field.value)) {
                field.classList.add('is-invalid');
                return false;
            }
        }
        
        field.classList.add('is-valid');
        return true;
    }
});
</script>

</body>
</html>

<?php
if (isset($conexion)) {
    $conexion->close();
}
?>