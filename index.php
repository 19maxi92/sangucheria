<?php
   require_once '/var/www/html/sangucheria/config.php';
   $conexion = getConnection();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandwicheria Santa Catalina - Sistema de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .menu-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .menu-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .precio-original {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9em;
        }
        .precio-efectivo {
            color: #28a745;
            font-weight: bold;
        }
        .badge-descuento {
            background-color: #dc3545;
            font-size: 0.8em;
        }
        .nav-tabs .nav-link.active {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        .ingredientes {
            font-size: 0.85em;
            color: #6c757d;
        }
        .contador-sabor {
            width: 60px;
            text-align: center;
        }
        .btn-contador {
            width: 30px;
            height: 30px;
            padding: 0;
            font-size: 14px;
            line-height: 1;
        }
        .sabor-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .personalizados-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .cantidad-input {
            width: 80px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-3">
    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="text-success">🥪 Sandwicheria Santa Catalina</h1>
        <p class="text-muted">📲 1159813546</p>
    </div>

    <!-- Navegación por pestañas -->
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="menu-tab" data-bs-toggle="tab" data-bs-target="#menu" role="tab">📋 Menú</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="personalizados-tab" data-bs-toggle="tab" data-bs-target="#personalizados" role="tab">🎨 Personalizados</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="planchas-tab" data-bs-toggle="tab" data-bs-target="#planchas" role="tab">🍞 Por Planchas</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pedido-tab" data-bs-toggle="tab" data-bs-target="#pedido" role="tab">🛒 Nuevo Pedido</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#clientes" role="tab">👥 Clientes Fijos</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" role="tab">📊 Ver Pedidos</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        
        <!-- PESTAÑA MENÚ -->
        <div class="tab-pane fade show active" id="menu" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <h3 class="text-center mb-4">🥪 Promo de Triples</h3>
                    
                    <!-- Paquetes x48 -->
                    <h4 class="text-primary mb-3">📦 Paquetes x48 Sándwiches</h4>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card menu-card h-100" onclick="agregarAlPedido('48 Jamón y Queso', 22000, 48)">
                                <div class="card-body text-center">
                                    <h5 class="card-title">48 Jamón y Queso</h5>
                                    <div class="precio-original">$24.000</div>
                                    <div class="precio-efectivo">$22.000</div>
                                    <span class="badge badge-descuento">-$2.000 efectivo</span>
                                    <p class="ingredientes mt-2">Jamón y queso clásico</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card menu-card h-100" onclick="agregarAlPedido('48 Surtidos Clásicos', 20000, 48)">
                                <div class="card-body text-center">
                                    <h5 class="card-title">48 Surtidos Clásicos</h5>
                                    <div class="precio-original">$22.000</div>
                                    <div class="precio-efectivo">$20.000</div>
                                    <span class="badge badge-descuento">-$2.000 efectivo</span>
                                    <p class="ingredientes mt-2">Jamón y queso, lechuga, tomate, huevo</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card menu-card h-100" onclick="agregarAlPedido('48 Surtidos Especiales', 22000, 48)">
                                <div class="card-body text-center">
                                    <h5 class="card-title">48 Surtidos Especiales</h5>
                                    <div class="precio-original">$24.000</div>
                                    <div class="precio-efectivo">$22.000</div>
                                    <span class="badge badge-descuento">-$2.000 efectivo</span>
                                    <p class="ingredientes mt-2">Jamón y queso, lechuga, tomate, huevo, choclo, aceitunas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card menu-card h-100" onclick="agregarAlPedido('48 Surtidos Premium', 42000, 48)">
                                <div class="card-body text-center">
                                    <h5 class="card-title">48 Surtidos Premium</h5>
                                    <div class="precio-original">$44.000</div>
                                    <div class="precio-efectivo">$42.000</div>
                                    <span class="badge badge-descuento">-$2.000 efectivo</span>
                                    <p class="ingredientes mt-2">Con sumadores de sabores</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paquetes x24 -->
                    <h4 class="text-primary mb-3">📦 Paquetes x24 Sándwiches</h4>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card menu-card h-100" onclick="agregarAlPedido('24 Jamón y Queso', 11000, 24)">
                                <div class="card-body text-center">
                                    <h5 class="card-title">24 Jamón y Queso</h5>
                                    <div class="precio-original">$12.000</div>
                                    <div class="precio-efectivo">$11.000</div>
                                    <span class="badge badge-descuento">-$1.000 efectivo</span>
                                    <p class="ingredientes mt-2">Jamón y queso clásico</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card menu-card h-100" onclick="agregarAlPedido('24 Surtidos', 11000, 24)">
                                <div class="card-body text-center">
                                    <h5 class="card-title">24 Surtidos</h5>
                                    <div class="precio-original">$12.000</div>
                                    <div class="precio-efectivo">$11.000</div>
                                    <span class="badge badge-descuento">-$1.000 efectivo</span>
                                    <p class="ingredientes mt-2">Jamón y queso, lechuga, tomate, huevo, choclo, aceitunas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card menu-card h-100" onclick="agregarAlPedido('24 Surtidos Premium', 21000, 24)">
                                <div class="card-body text-center">
                                    <h5 class="card-title">24 Surtidos Premium</h5>
                                    <div class="precio-original">$22.000</div>
                                    <div class="precio-efectivo">$21.000</div>
                                    <span class="badge badge-descuento">-$1.000 efectivo</span>
                                    <p class="ingredientes mt-2">Con sumadores de sabores</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ingredientes Premium -->
                    <div class="card bg-info text-white mb-4">
                        <div class="card-body">
                            <h5>🌟 Ingredientes Premium Disponibles:</h5>
                            <p class="mb-0">Ananá, Atún, Berenjena, Durazno, Jamón Crudo, Morrón, Palmito, Panceta, Pollo, Roquefort, Salame</p>
                        </div>
                    </div>

                    <!-- Carrito de compras -->
                    <div id="carritoResumen" class="card border-success" style="display: none;">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">🛒 Carrito de Compras</h5>
                        </div>
                        <div class="card-body">
                            <div id="itemsCarrito"></div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total: $<span id="totalCarrito">0</span></strong>
                                <div>
                                    <button class="btn btn-warning btn-sm" onclick="limpiarCarrito()">Limpiar</button>
                                    <button class="btn btn-success" onclick="pasarAPedido()">Continuar Pedido</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA SÁNDWICHES POR PLANCHAS -->
        <div class="tab-pane fade" id="planchas" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card" style="background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%); color: white;">
                        <div class="card-body text-center">
                            <h3>🍞 Pedido por Planchas</h3>
                            <p class="mb-0">1 Plancha = 8 Sándwiches = 1 Sabor | Precio por plancha: $4.000</p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">🍞 Configurar Pedido por Planchas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label class="form-label"><strong>Cantidad de planchas:</strong></label>
                                        <div class="d-flex align-items-center">
                                            <button type="button" class="btn btn-outline-danger" onclick="cambiarCantidadPlanchas(-1)">-</button>
                                            <input type="number" id="cantidadPlanchas" class="form-control mx-3" style="width: 100px;" min="1" value="1" readonly>
                                            <button type="button" class="btn btn-outline-success" onclick="cambiarCantidadPlanchas(1)">+</button>
                                            <span class="ms-3 text-muted">= <span id="totalSandwichesPlanchas">8</span> sándwiches</span>
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <strong>Sabores a seleccionar: <span id="saboresASeleccionar">1</span></strong><br>
                                        <small>Cada plancha necesita 1 sabor. Puedes repetir el mismo sabor en varias planchas.</small>
                                    </div>

                                    <h6>Seleccionar Sabores por Plancha:</h6>
                                    <div class="row" id="saboresPorPlanchas">
                                        <!-- Sabores se cargan aquí -->
                                    </div>

                                    <div class="text-center mt-4">
                                        <button class="btn btn-warning btn-lg" onclick="agregarPedidoPlanchas()">
                                            Agregar al Carrito - $<span id="precioPorPlanchas">4000</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">📋 Resumen del Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <div id="resumenPlanchas">
                                        <p><strong>Planchas:</strong> <span id="resumenCantidadPlanchas">1</span></p>
                                        <p><strong>Sándwiches:</strong> <span id="resumenTotalSandwiches">8</span></p>
                                        <p><strong>Precio por plancha:</strong> $4.000</p>
                                        <p><strong>Total:</strong> $<span id="resumenTotalPlanchas">4000</span></p>
                                        <hr>
                                        <div id="resumenSaboresPlanchas">
                                            <p class="text-muted">Selecciona sabores...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="personalizados" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card personalizados-card mb-4">
                        <div class="card-body text-center">
                            <h3>🎨 Crear Sándwich Personalizado</h3>
                            <p class="mb-0">Selecciona los sabores que quieras, sin límites. Precio por unidad: $500</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">🥪 Configurar Sándwich</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad de sándwiches:</label>
                                        <input type="number" id="cantidadPersonalizada" class="form-control cantidad-input" min="1" value="1" onchange="actualizarPrecioPersonalizado()">
                                    </div>

                                    <h6>Seleccionar Sabores:</h6>
                                    <div class="row" id="saboresPersonalizados">
                                        <!-- Sabores se cargan aquí -->
                                    </div>

                                    <div class="text-center mt-4">
                                        <button class="btn btn-success btn-lg" onclick="agregarPersonalizado()">
                                            Agregar al Carrito - $<span id="precioPersonalizado">500</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">📋 Resumen</h5>
                                </div>
                                <div class="card-body">
                                    <div id="resumenPersonalizado">
                                        <p>Cantidad: <span id="resumenCantidad">1</span> sándwiches</p>
                                        <p>Precio unitario: $500</p>
                                        <p><strong>Total: $<span id="resumenTotal">500</span></strong></p>
                                        <hr>
                                        <div id="resumenSabores">
                                            <p class="text-muted">Selecciona sabores...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA NUEVO PEDIDO -->
        <div class="tab-pane fade" id="pedido" role="tabpanel">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">📝 Nuevo Pedido</h5>
                        </div>
                        <div class="card-body">
                            <form id="pedidoForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nombre" class="form-label">Nombre:</label>
                                        <input type="text" class="form-control" name="nombre" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="apellido" class="form-label">Apellido:</label>
                                        <input type="text" class="form-control" name="apellido" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contacto" class="form-label">Número de Contacto:</label>
                                        <input type="text" class="form-control" name="contacto" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="direccion" class="form-label">Dirección:</label>
                                        <input type="text" class="form-control" name="direccion" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Modalidad:</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="modalidad" id="retiro" value="Retira" required>
                                            <label class="form-check-label" for="retiro">Retira en Local</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="modalidad" id="envio" value="Envío" required>
                                            <label class="form-check-label" for="envio">Envío a Domicilio</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Forma de Pago:</label><br>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="pago" id="efectivo" value="Efectivo" required onchange="actualizarPreciosPago()">
                                            <label class="form-check-label" for="efectivo">💰 Efectivo (Con descuento)</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="pago" id="transferencia" value="Transferencia" required onchange="actualizarPreciosPago()">
                                            <label class="form-check-label" for="transferencia">💳 Transferencia</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="observaciones" class="form-label">Observaciones Adicionales:</label>
                                        <textarea class="form-control" name="observaciones" rows="3" placeholder="Observaciones generales del pedido..."></textarea>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success btn-lg">💾 Guardar Pedido</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Resumen del pedido -->
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">📋 Resumen del Pedido</h5>
                        </div>
                        <div class="card-body">
                            <div id="resumenPedido">
                                <p class="text-muted">Selecciona productos del menú para agregarlos aquí</p>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total: $<span id="totalPedido">0</span></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA CLIENTES FIJOS -->
        <div class="tab-pane fade" id="clientes" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>👥 Clientes Fijos</h3>
                <button class="btn btn-success" onclick="mostrarFormularioCliente()">➕ Agregar Cliente Fijo</button>
            </div>

            <div id="listaClientesFijos" class="mb-4"></div>

            <!-- Formulario para nuevo cliente fijo -->
            <div id="formularioClienteFijo" class="card" style="display:none;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">➕ Nuevo Cliente Fijo</h5>
                </div>
                <div class="card-body">
                    <form id="formClienteFijo">
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
                                <label class="form-label">Contacto:</label>
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
                                    <option value="Retira">Retira en Local</option>
                                    <option value="Envío">Envío a Domicilio</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Producto Habitual:</label>
                                <select class="form-control" name="producto" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="24 Jamón y Queso">24 Jamón y Queso</option>
                                    <option value="24 Surtidos">24 Surtidos</option>
                                    <option value="24 Surtidos Premium">24 Surtidos Premium</option>
                                    <option value="48 Jamón y Queso">48 Jamón y Queso</option>
                                    <option value="48 Surtidos Clásicos">48 Surtidos Clásicos</option>
                                    <option value="48 Surtidos Especiales">48 Surtidos Especiales</option>
                                    <option value="48 Surtidos Premium">48 Surtidos Premium</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observaciones:</label>
                                <textarea class="form-control" name="observacion" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">💾 Guardar Cliente</button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="cerrarFormularioCliente()">❌ Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- PESTAÑA HISTORIAL -->
        <div class="tab-pane fade" id="historial" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>📊 Historial de Pedidos</h3>
                <button class="btn btn-outline-success" onclick="exportarPedidos()">📥 Exportar Excel</button>
            </div>

            <div class="mb-3">
                <input type="text" id="buscarPedidos" class="form-control" placeholder="🔍 Buscar por nombre, apellido o teléfono..." onkeyup="filtrarPedidos()">
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Planchas</th>
                            <th>Total</th>
                            <th>Contacto</th>
                            <th>Modalidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaPedidos">
                        <!-- Pedidos se cargan aquí -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para seleccionar sabores premium con sumadores -->
<div class="modal fade" id="modalSaboresPremium" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">🌟 Configurar Sabores Premium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Selecciona <span id="totalSandwichesPremium">6 sabores</span></strong><br>
                    <small>Puedes repetir sabores. Cada contador representa cuántas veces quieres ese sabor.</small>
                </div>
                <div class="row" id="listaSaboresPremium">
                    <!-- Sabores con sumadores se cargan aquí -->
                </div>
                <div class="mt-3 text-center">
                    <strong>Sabores seleccionados: <span id="saboresAsignados">0</span> / <span id="maxSandwiches">6</span></strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmarSaboresPremium()">Confirmar Sabores</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Variables globales
let carrito = [];
let pedidos = JSON.parse(localStorage.getItem('pedidos')) || [];
let clientesFijos = JSON.parse(localStorage.getItem('clientesFijos')) || [];
let saboresPremium = ['Jamón y Queso', 'Ananá', 'Atún', 'Berenjena', 'Durazno', 'Jamón Crudo', 'Morrón', 'Palmito', 'Panceta', 'Pollo', 'Roquefort', 'Salame'];
let productoTemporal = null;
let saboresPersonalizadosSeleccionados = {};
let saboresPorPlanchasSeleccionados = {};

// Precios de productos
const precios = {
    '48 Jamón y Queso': { efectivo: 22000, transferencia: 24000 },
    '48 Surtidos Clásicos': { efectivo: 20000, transferencia: 22000 },
    '48 Surtidos Especiales': { efectivo: 22000, transferencia: 24000 },
    '48 Surtidos Premium': { efectivo: 42000, transferencia: 44000 },
    '24 Jamón y Queso': { efectivo: 11000, transferencia: 12000 },
    '24 Surtidos': { efectivo: 11000, transferencia: 12000 },
    '24 Surtidos Premium': { efectivo: 21000, transferencia: 22000 }
};

// Inicializar sabores por planchas
function inicializarSaboresPorPlanchas() {
    const contenedor = document.getElementById('saboresPorPlanchas');
    saboresPremium.forEach(sabor => {
        saboresPorPlanchasSeleccionados[sabor] = 0;
        
        const div = document.createElement('div');
        div.className = 'col-md-6 mb-3';
        div.innerHTML = `
            <div class="sabor-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span><strong>${sabor}</strong></span>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-danger btn-contador" onclick="cambiarCantidadSaborPlancha('${sabor}', -1)">-</button>
                        <input type="number" class="form-control contador-sabor mx-2" id="plancha_${sabor}" value="0" min="0" readonly>
                        <button type="button" class="btn btn-outline-success btn-contador" onclick="cambiarCantidadSaborPlancha('${sabor}', 1)">+</button>
                    </div>
                </div>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

// Cambiar cantidad de planchas
function cambiarCantidadPlanchas(cambio) {
    const cantidadActual = parseInt(document.getElementById('cantidadPlanchas').value);
    const nuevaCantidad = Math.max(1, cantidadActual + cambio);
    
    document.getElementById('cantidadPlanchas').value = nuevaCantidad;
    document.getElementById('totalSandwichesPlanchas').textContent = nuevaCantidad * 8;
    document.getElementById('saboresASeleccionar').textContent = nuevaCantidad;
    document.getElementById('precioPorPlanchas').textContent = (nuevaCantidad * 4000).toLocaleString();
    
    // Actualizar resumen
    document.getElementById('resumenCantidadPlanchas').textContent = nuevaCantidad;
    document.getElementById('resumenTotalSandwiches').textContent = nuevaCantidad * 8;
    document.getElementById('resumenTotalPlanchas').textContent = (nuevaCantidad * 4000).toLocaleString();
    
    // Resetear sabores si se reduce la cantidad
    const totalSaboresSeleccionados = Object.values(saboresPorPlanchasSeleccionados).reduce((a, b) => a + b, 0);
    if (totalSaboresSeleccionados > nuevaCantidad) {
        // Resetear todos los sabores
        Object.keys(saboresPorPlanchasSeleccionados).forEach(sabor => {
            saboresPorPlanchasSeleccionados[sabor] = 0;
            document.getElementById(`plancha_${sabor}`).value = 0;
        });
        actualizarResumenPlanchas();
    }
}

// Cambiar cantidad de sabor por plancha
function cambiarCantidadSaborPlancha(sabor, cambio) {
    const cantidadPlanchas = parseInt(document.getElementById('cantidadPlanchas').value);
    const totalSaboresActual = Object.values(saboresPorPlanchasSeleccionados).reduce((a, b) => a + b, 0);
    
    let nuevaCantidad = saboresPorPlanchasSeleccionados[sabor] + cambio;
    
    // Validar límites
    if (nuevaCantidad < 0) nuevaCantidad = 0;
    if (cambio > 0 && totalSaboresActual >= cantidadPlanchas) {
        alert(`❌ Ya tienes ${cantidadPlanchas} sabores seleccionados (1 por plancha).`);
        return;
    }
    
    saboresPorPlanchasSeleccionados[sabor] = nuevaCantidad;
    document.getElementById(`plancha_${sabor}`).value = nuevaCantidad;
    actualizarResumenPlanchas();
}

// Actualizar resumen de planchas
function actualizarResumenPlanchas() {
    const resumenSabores = document.getElementById('resumenSaboresPlanchas');
    const saboresSeleccionados = Object.entries(saboresPorPlanchasSeleccionados)
        .filter(([sabor, cantidad]) => cantidad > 0)
        .map(([sabor, cantidad]) => `${sabor}: ${cantidad} plancha${cantidad > 1 ? 's' : ''}`)
        .join('<br>');
    
    if (saboresSeleccionados) {
        resumenSabores.innerHTML = `<strong>Sabores:</strong><br>${saboresSeleccionados}`;
    } else {
        resumenSabores.innerHTML = '<p class="text-muted">Selecciona sabores...</p>';
    }
}

// Agregar pedido por planchas al carrito
function agregarPedidoPlanchas() {
    const cantidadPlanchas = parseInt(document.getElementById('cantidadPlanchas').value);
    const totalSaboresSeleccionados = Object.values(saboresPorPlanchasSeleccionados).reduce((a, b) => a + b, 0);
    
    if (totalSaboresSeleccionados !== cantidadPlanchas) {
        alert(`❌ Debes seleccionar exactamente ${cantidadPlanchas} sabores (1 por plancha). Tienes ${totalSaboresSeleccionados} seleccionados.`);
        return;
    }
    
    const saboresSeleccionados = Object.entries(saboresPorPlanchasSeleccionados)
        .filter(([sabor, cant]) => cant > 0)
        .map(([sabor, cant]) => `${sabor} (${cant} plancha${cant > 1 ? 's' : ''})`)
        .join(', ');
    
    const item = {
        producto: `Pedido por Planchas (${cantidadPlanchas} plancha${cantidadPlanchas > 1 ? 's' : ''})`,
        precio: cantidadPlanchas * 4000,
        cantidad: cantidadPlanchas * 8, // 8 sándwiches por plancha
        sabores: saboresSeleccionados,
        id: Date.now(),
        porPlanchas: true,
        planchas: cantidadPlanchas
    };
    
    carrito.push(item);
    actualizarCarrito();
    mostrarCarrito();
    
    // Limpiar selección
    Object.keys(saboresPorPlanchasSeleccionados).forEach(sabor => {
        saboresPorPlanchasSeleccionados[sabor] = 0;
        document.getElementById(`plancha_${sabor}`).value = 0;
    });
    document.getElementById('cantidadPlanchas').value = 1;
    cambiarCantidadPlanchas(0); // Para actualizar los valores
    actualizarResumenPlanchas();
    
    alert('✅ Pedido por planchas agregado al carrito');
}

// Inicializar sabores personalizados
function inicializarSaboresPersonalizados() {
    const contenedor = document.getElementById('saboresPersonalizados');
    saboresPremium.forEach(sabor => {
        saboresPersonalizadosSeleccionados[sabor] = 0;
        
        const div = document.createElement('div');
        div.className = 'col-md-6 mb-3';
        div.innerHTML = `
            <div class="sabor-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span><strong>${sabor}</strong></span>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-danger btn-contador" onclick="cambiarCantidadPersonalizado('${sabor}', -1)">-</button>
                        <input type="number" class="form-control contador-sabor mx-2" id="personalizado_${sabor}" value="0" min="0" readonly>
                        <button type="button" class="btn btn-outline-success btn-contador" onclick="cambiarCantidadPersonalizado('${sabor}', 1)">+</button>
                    </div>
                </div>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

// Cambiar cantidad de sabor personalizado
function cambiarCantidadPersonalizado(sabor, cambio) {
    const nuevaCantidad = Math.max(0, saboresPersonalizadosSeleccionados[sabor] + cambio);
    saboresPersonalizadosSeleccionados[sabor] = nuevaCantidad;
    document.getElementById(`personalizado_${sabor}`).value = nuevaCantidad;
    actualizarResumenPersonalizado();
}

// Actualizar precio personalizado
function actualizarPrecioPersonalizado() {
    const cantidad = parseInt(document.getElementById('cantidadPersonalizada').value) || 1;
    const precio = cantidad * 500;
    document.getElementById('precioPersonalizado').textContent = precio.toLocaleString();
    document.getElementById('resumenCantidad').textContent = cantidad;
    document.getElementById('resumenTotal').textContent = precio.toLocaleString();
}

// Actualizar resumen personalizado
function actualizarResumenPersonalizado() {
    const resumenSabores = document.getElementById('resumenSabores');
    const saboresSeleccionados = Object.entries(saboresPersonalizadosSeleccionados)
        .filter(([sabor, cantidad]) => cantidad > 0)
        .map(([sabor, cantidad]) => `${sabor}: ${cantidad}`)
        .join('<br>');
    
    if (saboresSeleccionados) {
        resumenSabores.innerHTML = `<strong>Sabores:</strong><br>${saboresSeleccionados}`;
    } else {
        resumenSabores.innerHTML = '<p class="text-muted">Selecciona sabores...</p>';
    }
}

// Agregar personalizado al carrito
function agregarPersonalizado() {
    const cantidad = parseInt(document.getElementById('cantidadPersonalizada').value) || 1;
    const saboresSeleccionados = Object.entries(saboresPersonalizadosSeleccionados)
        .filter(([sabor, cant]) => cant > 0)
        .map(([sabor, cant]) => `${sabor} (${cant})`);
    
    if (saboresSeleccionados.length === 0) {
        alert('❌ Debes seleccionar al menos un sabor');
        return;
    }
    
    const item = {
        producto: `Sándwich Personalizado x${cantidad}`,
        precio: cantidad * 500,
        cantidad: cantidad,
        sabores: saboresSeleccionados.join(', '),
        id: Date.now(),
        personalizado: true
    };
    
    carrito.push(item);
    actualizarCarrito();
    mostrarCarrito();
    
    // Limpiar selección
    Object.keys(saboresPersonalizadosSeleccionados).forEach(sabor => {
        saboresPersonalizadosSeleccionados[sabor] = 0;
        document.getElementById(`personalizado_${sabor}`).value = 0;
    });
    document.getElementById('cantidadPersonalizada').value = 1;
    actualizarPrecioPersonalizado();
    actualizarResumenPersonalizado();
    
    alert('✅ Sándwich personalizado agregado al carrito');
}

// Agregar producto al carrito
function agregarAlPedido(producto, precio, cantidad) {
    if (producto.includes('Premium')) {
        productoTemporal = { producto, precio, cantidad };
        mostrarModalSaboresPremium(producto);
    } else {
        const item = {
            producto: producto,
            precio: precio,
            cantidad: cantidad,
            sabores: '',
            id: Date.now()
        };
        carrito.push(item);
        actualizarCarrito();
        mostrarCarrito();
    }
}

// Mostrar modal de sabores premium con sumadores
function mostrarModalSaboresPremium(producto) {
    const cantidadSabores = producto.includes('48') ? 6 : 3; // 6 sabores para 48, 3 para 24
    document.getElementById('totalSandwichesPremium').textContent = `${cantidadSabores} sabores`;
    document.getElementById('maxSandwiches').textContent = cantidadSabores;
    
    const listaSabores = document.getElementById('listaSaboresPremium');
    listaSabores.innerHTML = '';
    
    const saboresTemporales = {};
    
    saboresPremium.forEach(sabor => {
        saboresTemporales[sabor] = 0;
        
        const div = document.createElement('div');
        div.className = 'col-md-6 mb-3';
        div.innerHTML = `
            <div class="sabor-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span><strong>${sabor}</strong></span>
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-outline-danger btn-contador" onclick="cambiarCantidadPremium('${sabor}', -1)">-</button>
                        <input type="number" class="form-control contador-sabor mx-2" id="premium_${sabor}" value="0" min="0" readonly>
                        <button type="button" class="btn btn-outline-success btn-contador" onclick="cambiarCantidadPremium('${sabor}', 1)">+</button>
                    </div>
                </div>
            </div>
        `;
        listaSabores.appendChild(div);
    });
    
    // Guardar referencia temporal
    window.saboresTemporalesPremium = saboresTemporales;
    window.maxSaboresPremium = cantidadSabores;
    document.getElementById('saboresAsignados').textContent = '0';
    
    new bootstrap.Modal(document.getElementById('modalSaboresPremium')).show();
}

// Cambiar cantidad de sabor premium
function cambiarCantidadPremium(sabor, cambio) {
    const maxSabores = window.maxSaboresPremium;
    const totalActual = Object.values(window.saboresTemporalesPremium).reduce((a, b) => a + b, 0);
    
    let nuevaCantidad = window.saboresTemporalesPremium[sabor] + cambio;
    
    // Validar límites
    if (nuevaCantidad < 0) nuevaCantidad = 0;
    if (cambio > 0 && totalActual >= maxSabores) {
        alert(`❌ No puedes agregar más sabores. Máximo ${maxSabores} sabores.`);
        return;
    }
    
    window.saboresTemporalesPremium[sabor] = nuevaCantidad;
    document.getElementById(`premium_${sabor}`).value = nuevaCantidad;
    
    // Actualizar contador
    const totalAsignados = Object.values(window.saboresTemporalesPremium).reduce((a, b) => a + b, 0);
    document.getElementById('saboresAsignados').textContent = totalAsignados;
}

// Confirmar sabores premium
function confirmarSaboresPremium() {
    const maxSabores = window.maxSaboresPremium;
    const totalAsignados = Object.values(window.saboresTemporalesPremium).reduce((a, b) => a + b, 0);
    
    if (totalAsignados !== maxSabores) {
        alert(`❌ Debes seleccionar exactamente ${maxSabores} sabores. Actualmente tienes ${totalAsignados}.`);
        return;
    }
    
    // Crear array de sabores (pueden repetirse)
    const listaSabores = [];
    Object.entries(window.saboresTemporalesPremium).forEach(([sabor, cantidad]) => {
        for (let i = 0; i < cantidad; i++) {
            listaSabores.push(sabor);
        }
    });
    
    const item = {
        producto: productoTemporal.producto,
        precio: productoTemporal.precio,
        cantidad: productoTemporal.cantidad,
        sabores: listaSabores.join(', '),
        id: Date.now()
    };
    
    carrito.push(item);
    actualizarCarrito();
    mostrarCarrito();
    bootstrap.Modal.getInstance(document.getElementById('modalSaboresPremium')).hide();
    productoTemporal = null;
    window.saboresTemporalesPremium = null;
    window.maxSaboresPremium = null;
}

// Actualizar precios según forma de pago
function actualizarPreciosPago() {
    const formaPago = document.querySelector('input[name="pago"]:checked')?.value;
    if (!formaPago) return;
    
    carrito.forEach(item => {
        if (precios[item.producto]) {
            item.precio = precios[item.producto][formaPago.toLowerCase()];
        }
        // No cambiar precios de personalizados
    });
    
    actualizarCarrito();
}

// Mostrar carrito
function mostrarCarrito() {
    document.getElementById('carritoResumen').style.display = 'block';
}

// Actualizar visualización del carrito
function actualizarCarrito() {
    const contenedor = document.getElementById('itemsCarrito');
    const total = carrito.reduce((sum, item) => sum + item.precio, 0);
    
    contenedor.innerHTML = '';
    carrito.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'border-bottom pb-2 mb-2';
        
        let detallesProducto = '';
        if (item.personalizado) {
            detallesProducto = `<small>${item.cantidad} sándwiches</small>`;
        } else if (item.porPlanchas) {
            detallesProducto = `<small>${item.planchas} plancha${item.planchas > 1 ? 's' : ''} - ${item.cantidad} sándwiches</small>`;
        } else {
            detallesProducto = `<small>${item.cantidad} sándwiches - ${(item.cantidad/24).toFixed(2)} planchas</small>`;
        }
        
        div.innerHTML = `
            <div class="d-flex justify-content-between">
                <div>
                    <strong>${item.producto}</strong>
                    <br>${detallesProducto}
                    ${item.sabores ? `<br><small class="text-success">Sabores: ${item.sabores}</small>` : ''}
                </div>
                <div class="text-end">
                    <div>${item.precio.toLocaleString()}</div>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarDelCarrito(${index})">🗑️</button>
                </div>
            </div>
        `;
        contenedor.appendChild(div);
    });
    
    document.getElementById('totalCarrito').textContent = total.toLocaleString();
    document.getElementById('totalPedido').textContent = total.toLocaleString();
}

// Eliminar del carrito
function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    actualizarCarrito();
    if (carrito.length === 0) {
        document.getElementById('carritoResumen').style.display = 'none';
    }
}

// Limpiar carrito
function limpiarCarrito() {
    carrito = [];
    document.getElementById('carritoResumen').style.display = 'none';
    actualizarCarrito();
}

// Pasar a la pestaña de pedido
function pasarAPedido() {
    document.getElementById('pedido-tab').click();
    
    // Llenar el resumen en la pestaña de pedido
    const resumen = document.getElementById('resumenPedido');
    resumen.innerHTML = '';
    
    carrito.forEach(item => {
        const div = document.createElement('div');
        div.className = 'border-bottom pb-2 mb-2';
        div.innerHTML = `
            <div>
                <strong>${item.producto}</strong>
                <div class="text-end">${item.precio.toLocaleString()}</div>
                ${item.sabores ? `<small class="text-success">Sabores: ${item.sabores}</small>` : ''}
            </div>
        `;
        resumen.appendChild(div);
    });
}

// Enviar pedido
document.getElementById('pedidoForm').addEventListener('submit', function(event) {
    event.preventDefault();
    
    if (carrito.length === 0) {
        alert('❌ Debe agregar al menos un producto al pedido');
        return;
    }
    
    const datos = new FormData(this);
    const pedido = {
        id: Date.now(),
        fecha: new Date().toISOString(),
        nombre: datos.get('nombre'),
        apellido: datos.get('apellido'),
        contacto: datos.get('contacto'),
        direccion: datos.get('direccion'),
        modalidad: datos.get('modalidad'),
        pago: datos.get('pago'),
        observaciones: datos.get('observaciones'),
        productos: [...carrito],
        total: carrito.reduce((sum, item) => sum + item.precio, 0),
        cantidad: carrito.reduce((sum, item) => sum + item.cantidad, 0),
        planchas: (carrito.reduce((sum, item) => {
            if (item.porPlanchas) {
                return sum + item.planchas;
            } else if (item.personalizado) {
                return sum + (item.cantidad / 8); // Convertir sándwiches personalizados a planchas
            } else {
                return sum + (item.cantidad / 24); // Planchas normales
            }
        }, 0)).toFixed(2),
        estado: 'Pendiente'
    };
    
    pedidos.push(pedido);
    localStorage.setItem('pedidos', JSON.stringify(pedidos));
    
    // Mostrar mensaje de éxito
    const alerta = document.createElement('div');
    alerta.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    alerta.style.zIndex = '9999';
    alerta.innerHTML = `
        ✅ Pedido guardado correctamente
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alerta);
    
    // Limpiar formulario y carrito
    this.reset();
    limpiarCarrito();
    
    // Remover alerta después de 3 segundos
    setTimeout(() => {
        if (alerta.parentNode) {
            alerta.remove();
        }
    }, 3000);
    
    // Actualizar historial
    cargarPedidos();
});

// Cargar clientes fijos
function cargarClientesFijos() {
    const contenedor = document.getElementById('listaClientesFijos');
    contenedor.innerHTML = '';
    
    if (clientesFijos.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-warning">No hay clientes fijos registrados.</div>';
        return;
    }
    
    clientesFijos.forEach((cliente, index) => {
        const div = document.createElement('div');
        div.className = 'card mb-3';
        div.innerHTML = `
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h5 class="mb-1">${cliente.nombre} ${cliente.apellido}</h5>
                        <small class="text-muted">📱 ${cliente.contacto}</small><br>
                        <small class="text-muted">📍 ${cliente.direccion}</small>
                    </div>
                    <div class="col-md-4">
                        <strong>Producto habitual:</strong><br>
                        <span class="badge bg-primary">${cliente.producto}</span><br>
                        <small class="text-muted">Modalidad: ${cliente.modalidad}</small>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-success btn-sm" onclick="realizarPedidoFijo(${index})">📋 Hacer Pedido</button>
                        <button class="btn btn-outline-danger btn-sm" onclick="eliminarClienteFijo(${index})">🗑️</button>
                    </div>
                </div>
                <div class="mt-2">
                    <small><strong>Observaciones:</strong> ${cliente.observacion}</small>
                </div>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

// Realizar pedido para cliente fijo
function realizarPedidoFijo(index) {
    const cliente = clientesFijos[index];
    const producto = cliente.producto;
    const precioInfo = precios[producto];
    
    if (!precioInfo) {
        alert('Error: Producto no encontrado en el menú');
        return;
    }
    
    const pedido = {
        id: Date.now(),
        fecha: new Date().toISOString(),
        nombre: cliente.nombre,
        apellido: cliente.apellido,
        contacto: cliente.contacto,
        direccion: cliente.direccion,
        modalidad: cliente.modalidad,
        pago: 'Efectivo', // Por defecto efectivo para clientes fijos
        observaciones: cliente.observacion,
        productos: [{
            producto: producto,
            precio: precioInfo.efectivo,
            cantidad: parseInt(producto.split(' ')[0]),
            sabores: '',
            id: Date.now()
        }],
        total: precioInfo.efectivo,
        cantidad: parseInt(producto.split(' ')[0]),
        planchas: (parseInt(producto.split(' ')[0]) / 24).toFixed(2),
        estado: 'Pendiente',
        clienteFijo: true
    };
    
    pedidos.push(pedido);
    localStorage.setItem('pedidos', JSON.stringify(pedidos));
    
    alert(`✅ Pedido realizado para ${cliente.nombre} ${cliente.apellido}\n${producto} - ${precioInfo.efectivo.toLocaleString()}`);
    cargarPedidos();
}

// Mostrar formulario de cliente fijo
function mostrarFormularioCliente() {
    document.getElementById('formularioClienteFijo').style.display = 'block';
}

// Cerrar formulario de cliente fijo
function cerrarFormularioCliente() {
    document.getElementById('formularioClienteFijo').style.display = 'none';
    document.getElementById('formClienteFijo').reset();
}

// Guardar cliente fijo
document.getElementById('formClienteFijo').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const datos = new FormData(this);
    const cliente = {
        nombre: datos.get('nombre'),
        apellido: datos.get('apellido'),
        contacto: datos.get('contacto'),
        direccion: datos.get('direccion'),
        modalidad: datos.get('modalidad'),
        producto: datos.get('producto'),
        observacion: datos.get('observacion'),
        id: Date.now()
    };
    
    clientesFijos.push(cliente);
    localStorage.setItem('clientesFijos', JSON.stringify(clientesFijos));
    
    alert('✅ Cliente fijo agregado correctamente');
    cerrarFormularioCliente();
    cargarClientesFijos();
});

// Eliminar cliente fijo
function eliminarClienteFijo(index) {
    if (confirm('¿Está seguro que desea eliminar este cliente fijo?')) {
        clientesFijos.splice(index, 1);
        localStorage.setItem('clientesFijos', JSON.stringify(clientesFijos));
        cargarClientesFijos();
    }
}

// Cargar pedidos en historial
function cargarPedidos() {
    const tabla = document.getElementById('tablaPedidos');
    tabla.innerHTML = '';
    
    if (pedidos.length === 0) {
        tabla.innerHTML = '<tr><td colspan="10" class="text-center">No hay pedidos registrados</td></tr>';
        return;
    }
    
    // Ordenar por fecha más reciente
    const pedidosOrdenados = [...pedidos].sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
    
    pedidosOrdenados.forEach((pedido, index) => {
        const fecha = new Date(pedido.fecha);
        const fechaFormateada = fecha.toLocaleDateString('es-AR') + ' ' + fecha.toLocaleTimeString('es-AR', {hour: '2-digit', minute: '2-digit'});
        
        const productosTexto = pedido.productos.map(p => p.producto).join(', ');
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${fechaFormateada}</td>
            <td>${pedido.nombre} ${pedido.apellido}</td>
            <td>${productosTexto}</td>
            <td>${pedido.cantidad}</td>
            <td>${pedido.planchas}</td>
            <td>${pedido.total.toLocaleString()}</td>
            <td>${pedido.contacto}</td>
            <td>
                <span class="badge ${pedido.modalidad === 'Envío' ? 'bg-info' : 'bg-secondary'}">${pedido.modalidad}</span>
                <span class="badge ${pedido.pago === 'Efectivo' ? 'bg-success' : 'bg-primary'}">${pedido.pago}</span>
            </td>
            <td>
                <select class="form-select form-select-sm" onchange="cambiarEstado(${pedido.id}, this.value)">
                    <option value="Pendiente" ${pedido.estado === 'Pendiente' ? 'selected' : ''}>⏳ Pendiente</option>
                    <option value="En Preparación" ${pedido.estado === 'En Preparación' ? 'selected' : ''}>👨‍🍳 En Preparación</option>
                    <option value="Listo" ${pedido.estado === 'Listo' ? 'selected' : ''}>✅ Listo</option>
                    <option value="Entregado" ${pedido.estado === 'Entregado' ? 'selected' : ''}>📦 Entregado</option>
                </select>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-info" onclick="verDetalles(${pedido.id})" title="Ver detalles">👁️</button>
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarPedido(${pedido.id})" title="Eliminar">🗑️</button>
            </td>
        `;
        tabla.appendChild(tr);
    });
}

// Cambiar estado del pedido
function cambiarEstado(id, nuevoEstado) {
    const index = pedidos.findIndex(p => p.id === id);
    if (index !== -1) {
        pedidos[index].estado = nuevoEstado;
        localStorage.setItem('pedidos', JSON.stringify(pedidos));
    }
}

// Ver detalles del pedido
function verDetalles(id) {
    const pedido = pedidos.find(p => p.id === id);
    if (!pedido) return;
    
    let detalles = `📋 DETALLE DEL PEDIDO\n\n`;
    detalles += `Cliente: ${pedido.nombre} ${pedido.apellido}\n`;
    detalles += `Contacto: ${pedido.contacto}\n`;
    detalles += `Dirección: ${pedido.direccion}\n`;
    detalles += `Modalidad: ${pedido.modalidad}\n`;
    detalles += `Forma de Pago: ${pedido.pago}\n`;
    detalles += `Estado: ${pedido.estado}\n\n`;
    detalles += `PRODUCTOS:\n`;
    
    pedido.productos.forEach(producto => {
        detalles += `• ${producto.producto} - ${producto.precio.toLocaleString()}\n`;
        if (producto.sabores) {
            detalles += `  Sabores: ${producto.sabores}\n`;
        }
    });
    
    detalles += `\nTotal: ${pedido.total.toLocaleString()}`;
    
    if (pedido.observaciones) {
        detalles += `\n\nObservaciones: ${pedido.observaciones}`;
    }
    
    alert(detalles);
}

// Eliminar pedido
function eliminarPedido(id) {
    if (confirm('¿Está seguro que desea eliminar este pedido?')) {
        const index = pedidos.findIndex(p => p.id === id);
        if (index !== -1) {
            pedidos.splice(index, 1);
            localStorage.setItem('pedidos', JSON.stringify(pedidos));
            cargarPedidos();
        }
    }
}

// Filtrar pedidos
function filtrarPedidos() {
    const filtro = document.getElementById('buscarPedidos').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaPedidos tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
}

// Exportar pedidos a Excel
function exportarPedidos() {
    let csv = 'Fecha,Cliente,Producto,Cantidad,Planchas,Total,Contacto,Modalidad,Pago,Estado,Observaciones\n';
    
    pedidos.forEach(pedido => {
        const fecha = new Date(pedido.fecha).toLocaleDateString('es-AR');
        const productos = pedido.productos.map(p => p.producto).join(' + ');
        csv += `"${fecha}","${pedido.nombre} ${pedido.apellido}","${productos}",${pedido.cantidad},${pedido.planchas},${pedido.total},"${pedido.contacto}","${pedido.modalidad}","${pedido.pago}","${pedido.estado}","${pedido.observaciones || ''}"\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `pedidos_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Estadísticas rápidas
function mostrarEstadisticas() {
    const hoy = new Date().toDateString();
    const pedidosHoy = pedidos.filter(p => new Date(p.fecha).toDateString() === hoy);
    const totalHoy = pedidosHoy.reduce((sum, p) => sum + p.total, 0);
    const sandwichesHoy = pedidosHoy.reduce((sum, p) => sum + p.cantidad, 0);
    
    alert(`📊 ESTADÍSTICAS DE HOY\n\nPedidos: ${pedidosHoy.length}\nSándwiches: ${sandwichesHoy}\nVentas: ${totalHoy.toLocaleString()}`);
}

// Cargar datos iniciales
document.addEventListener('DOMContentLoaded', function() {
    inicializarSaboresPersonalizados();
    inicializarSaboresPorPlanchas();
    actualizarPrecioPersonalizado();
    cargarClientesFijos();
    cargarPedidos();
    
    // Agregar botón de estadísticas
    const headerHistorial = document.querySelector('#historial .d-flex');
    if (headerHistorial) {
        const btnEstadisticas = document.createElement('button');
        btnEstadisticas.className = 'btn btn-outline-info me-2';
        btnEstadisticas.innerHTML = '📊 Estadísticas';
        btnEstadisticas.onclick = mostrarEstadisticas;
        headerHistorial.insertBefore(btnEstadisticas, headerHistorial.lastElementChild);
    }
});

// Función para limpiar datos (solo para desarrollo)
function limpiarTodosLosDatos() {
    if (confirm('⚠️ ADVERTENCIA: Esto eliminará TODOS los pedidos y clientes fijos. ¿Está seguro?')) {
        localStorage.removeItem('pedidos');
        localStorage.removeItem('clientesFijos');
        pedidos = [];
        clientesFijos = [];
        cargarClientesFijos();
        cargarPedidos();
        alert('✅ Todos los datos han sido eliminados');
    }
}
</script>

</body>

</html>
