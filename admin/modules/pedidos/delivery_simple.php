<?php
// admin/modules/pedidos/delivery_simple.php
// Sistema de Delivery con Mapa Interactivo (OpenStreetMap + Leaflet.js)

require_once '../../config.php';
requireLogin();

$pdo = getConnection();

// Estadísticas rápidas
$fecha_hoy = date('Y-m-d');
$stats_sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN geocodificado = 1 THEN 1 ELSE 0 END) as geocodificados,
    SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'Preparando' THEN 1 ELSE 0 END) as preparando,
    SUM(CASE WHEN estado = 'Listo' THEN 1 ELSE 0 END) as listos,
    SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END) as entregados,
    SUM(CASE WHEN repartidor_id IS NOT NULL THEN 1 ELSE 0 END) as asignados
    FROM pedidos
    WHERE modalidad = 'Delivery'
      AND DATE(fecha_entrega) = :fecha";

$stmt = $pdo->prepare($stats_sql);
$stmt->execute(['fecha' => $fecha_hoy]);
$stats = $stmt->fetch();

// Obtener repartidores activos
$repartidores = $pdo->query("
    SELECT id, nombre, apellido, zona_asignada, vehiculo
    FROM repartidores
    WHERE activo = 1
    ORDER BY nombre ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery - Mapa Interactivo | Santa Catalina</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <style>
        /* Mapa */
        #mapa-delivery {
            height: 70vh;
            min-height: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Panel lateral */
        #panel-pedidos {
            height: 70vh;
            overflow-y: auto;
        }

        /* Scrollbar custom */
        #panel-pedidos::-webkit-scrollbar {
            width: 8px;
        }

        #panel-pedidos::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #panel-pedidos::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        #panel-pedidos::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Animaciones */
        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        .pedido-card:hover {
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }

        .pedido-card.selected {
            border-left: 4px solid #3b82f6;
            background-color: #eff6ff;
        }

        /* Popup personalizado */
        .leaflet-popup-content {
            margin: 0;
            width: 300px !important;
        }

        .custom-popup {
            padding: 0;
            border-radius: 8px;
        }

        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="bg-white rounded-lg p-6 shadow-xl text-center">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <p class="text-lg font-semibold">Cargando pedidos...</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-map-marked-alt text-blue-500 mr-2"></i>
                    Delivery - Mapa Interactivo
                </h1>
                <p class="text-gray-600 mt-1">
                    <i class="fas fa-calendar mr-1"></i><?= date('d/m/Y') ?>
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="gestionar_repartidores.php"
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow">
                    <i class="fas fa-users mr-2"></i>Repartidores
                </a>
                <button onclick="procesarGeocodificacion()"
                        class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg shadow">
                    <i class="fas fa-map-pin mr-2"></i>Geocodificar Pendientes
                </button>
                <a href="ver_pedidos.php"
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg shadow">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-gray-700"><?= $stats['total'] ?></div>
                <div class="text-sm text-gray-500">Total Delivery</div>
            </div>
            <div class="bg-green-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-green-700"><?= $stats['geocodificados'] ?></div>
                <div class="text-sm text-green-600">Geocodificados</div>
            </div>
            <div class="bg-yellow-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-yellow-700"><?= $stats['pendientes'] ?></div>
                <div class="text-sm text-yellow-600">Pendientes</div>
            </div>
            <div class="bg-blue-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-blue-700"><?= $stats['preparando'] ?></div>
                <div class="text-sm text-blue-600">Preparando</div>
            </div>
            <div class="bg-indigo-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-indigo-700"><?= $stats['listos'] ?></div>
                <div class="text-sm text-indigo-600">Listos</div>
            </div>
            <div class="bg-purple-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-purple-700"><?= $stats['asignados'] ?></div>
                <div class="text-sm text-purple-600">Asignados</div>
            </div>
        </div>

        <!-- Filtros Rápidos -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <div class="flex flex-wrap gap-3 items-center">
                <label class="font-semibold text-gray-700">Filtros:</label>

                <select id="filtroEstado" onchange="cargarPedidos()"
                        class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Preparando">Preparando</option>
                    <option value="Listo">Listo</option>
                    <option value="Entregado">Entregado</option>
                </select>

                <select id="filtroUbicacion" onchange="cargarPedidos()"
                        class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="todas">Todas las ubicaciones</option>
                    <option value="Local 1">🏪 Local 1</option>
                    <option value="Fábrica">🏭 Fábrica</option>
                </select>

                <input type="date" id="filtroFecha" value="<?= $fecha_hoy ?>" onchange="cargarPedidos()"
                       class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">

                <button onclick="centrarMapa()"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-crosshairs mr-2"></i>Centrar Mapa
                </button>

                <button onclick="cargarPedidos()"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-sync mr-2"></i>Actualizar
                </button>
            </div>
        </div>

        <!-- Contenedor Principal: Mapa + Panel Lateral -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- MAPA -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-map mr-2 text-blue-500"></i>
                            Mapa de Entregas
                        </h2>
                        <div class="text-sm text-gray-600">
                            <span id="pedidosEnMapa">0</span> pedidos visibles
                        </div>
                    </div>
                    <div id="mapa-delivery"></div>
                </div>
            </div>

            <!-- PANEL LATERAL -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list mr-2 text-blue-500"></i>
                        Lista de Pedidos
                    </h2>
                    <div id="panel-pedidos">
                        <!-- Se llena dinámicamente con JavaScript -->
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                            <p>Cargando pedidos...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal: Detalles del Pedido -->
    <div id="modalDetalle" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Detalles del Pedido
                </h3>
                <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="modalContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <!-- Leaflet.js JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <!-- Leaflet MarkerCluster JavaScript -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script>
        // ========================================
        // VARIABLES GLOBALES
        // ========================================
        let mapa;
        let pedidosData = [];
        let markers = {};
        let markerClusterGroup;
        const CORDOBA_CENTER = [-31.4201, -64.1888]; // Córdoba, Argentina

        // Iconos personalizados por estado
        const iconos = {
            'Pendiente': L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            }),
            'Preparando': L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            }),
            'Listo': L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            }),
            'Entregado': L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        };

        // ========================================
        // INICIALIZACIÓN
        // ========================================
        document.addEventListener('DOMContentLoaded', function() {
            inicializarMapa();
            cargarPedidos();

            console.log('🗺️ Sistema de Delivery con OSM + Leaflet.js');
            console.log('📍 Powered by OpenStreetMap');
        });

        // ========================================
        // MAPA: INICIALIZACIÓN
        // ========================================
        function inicializarMapa() {
            // Crear mapa centrado en Córdoba
            mapa = L.map('mapa-delivery').setView(CORDOBA_CENTER, 13);

            // Agregar tiles de OpenStreetMap
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> | Santa Catalina'
            }).addTo(mapa);

            // Inicializar MarkerClusterGroup
            markerClusterGroup = L.markerClusterGroup({
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                spiderfyOnMaxZoom: true,
                maxClusterRadius: 50
            });

            mapa.addLayer(markerClusterGroup);

            console.log('✅ Mapa inicializado');
        }

        // ========================================
        // PEDIDOS: CARGAR DESDE API
        // ========================================
        async function cargarPedidos() {
            showLoading(true);

            const estado = document.getElementById('filtroEstado').value;
            const ubicacion = document.getElementById('filtroUbicacion').value;
            const fecha = document.getElementById('filtroFecha').value;

            try {
                const params = new URLSearchParams({
                    action: 'get_pedidos',
                    estado: estado || 'todos',
                    ubicacion: ubicacion || 'todas',
                    fecha: fecha
                });

                const response = await fetch(`api_delivery.php?${params}`);
                const data = await response.json();

                if (data.success) {
                    pedidosData = data.pedidos;
                    mostrarPedidosEnMapa();
                    mostrarPedidosEnPanel();

                    console.log(`✅ ${data.total} pedidos cargados`);
                } else {
                    console.error('❌ Error:', data.error);
                    alert('Error al cargar pedidos: ' + data.error);
                }

            } catch (error) {
                console.error('❌ Error de red:', error);
                alert('Error de conexión. Por favor, recarga la página.');
            } finally {
                showLoading(false);
            }
        }

        // ========================================
        // MAPA: MOSTRAR PEDIDOS
        // ========================================
        function mostrarPedidosEnMapa() {
            // Limpiar marcadores existentes
            markerClusterGroup.clearLayers();
            markers = {};

            let pedidosConCoordenadas = 0;

            pedidosData.forEach(pedido => {
                if (pedido.coordenadas.lat && pedido.coordenadas.lng) {
                    const icono = iconos[pedido.estado] || iconos['Pendiente'];

                    const marker = L.marker(
                        [pedido.coordenadas.lat, pedido.coordenadas.lng],
                        { icon: icono }
                    );

                    // Popup personalizado
                    const popupContent = crearPopupContent(pedido);
                    marker.bindPopup(popupContent, {
                        className: 'custom-popup',
                        maxWidth: 300
                    });

                    // Click en marcador
                    marker.on('click', () => seleccionarPedido(pedido.id));

                    markerClusterGroup.addLayer(marker);
                    markers[pedido.id] = marker;

                    pedidosConCoordenadas++;
                }
            });

            document.getElementById('pedidosEnMapa').textContent = pedidosConCoordenadas;

            // Ajustar zoom para ver todos los marcadores
            if (pedidosConCoordenadas > 0) {
                setTimeout(() => {
                    mapa.fitBounds(markerClusterGroup.getBounds(), {
                        padding: [50, 50],
                        maxZoom: 15
                    });
                }, 300);
            }
        }

        // ========================================
        // POPUP: CREAR CONTENIDO
        // ========================================
        function crearPopupContent(pedido) {
            const estadoColors = {
                'Pendiente': 'yellow',
                'Preparando': 'blue',
                'Listo': 'green',
                'Entregado': 'gray'
            };

            const color = estadoColors[pedido.estado] || 'gray';

            return `
                <div class="p-3">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-bold text-lg">Pedido #${pedido.id}</h4>
                        <span class="bg-${color}-100 text-${color}-800 px-2 py-1 rounded-full text-xs font-semibold">
                            ${pedido.estado}
                        </span>
                    </div>

                    <div class="space-y-1 text-sm mb-3">
                        <p><i class="fas fa-user text-gray-600 mr-2"></i><strong>${pedido.cliente.nombre}</strong></p>
                        <p><i class="fas fa-phone text-gray-600 mr-2"></i>${pedido.cliente.telefono}</p>
                        <p><i class="fas fa-map-marker-alt text-gray-600 mr-2"></i>${pedido.cliente.direccion}</p>
                        <p><i class="fas fa-clock text-gray-600 mr-2"></i>${pedido.hora_entrega || 'Sin hora'}</p>
                        <p><i class="fas fa-shopping-bag text-gray-600 mr-2"></i>${pedido.producto}</p>
                        <p><i class="fas fa-dollar-sign text-gray-600 mr-2"></i>$${pedido.precio.toLocaleString()}</p>
                    </div>

                    <div class="flex gap-2">
                        <button onclick="verDetallesPedido(${pedido.id})"
                                class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs">
                            <i class="fas fa-eye mr-1"></i>Ver Detalles
                        </button>
                        <button onclick="abrirWhatsApp('${pedido.cliente.telefono}', '${pedido.cliente.nombre}', ${pedido.id})"
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs">
                            <i class="fab fa-whatsapp mr-1"></i>WhatsApp
                        </button>
                    </div>
                </div>
            `;
        }

        // ========================================
        // PANEL: MOSTRAR LISTA DE PEDIDOS
        // ========================================
        function mostrarPedidosEnPanel() {
            const panel = document.getElementById('panel-pedidos');

            if (pedidosData.length === 0) {
                panel.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No hay pedidos de delivery para mostrar</p>
                    </div>
                `;
                return;
            }

            let html = '';

            pedidosData.forEach(pedido => {
                const estadoColors = {
                    'Pendiente': 'yellow',
                    'Preparando': 'blue',
                    'Listo': 'green',
                    'Entregado': 'gray'
                };

                const color = estadoColors[pedido.estado] || 'gray';
                const sinGeocodificar = !pedido.geocodificado ?
                    '<span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">Sin geocodificar</span>' : '';

                html += `
                    <div id="pedido-card-${pedido.id}"
                         class="pedido-card bg-white border border-gray-200 rounded-lg p-3 mb-3 cursor-pointer hover:shadow-md"
                         onclick="seleccionarPedido(${pedido.id})">

                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="font-bold text-lg">#${pedido.id} - ${pedido.cliente.nombre}</div>
                                <div class="text-xs text-gray-600">
                                    <i class="fas fa-phone mr-1"></i>${pedido.cliente.telefono}
                                </div>
                            </div>
                            <span class="bg-${color}-100 text-${color}-800 px-2 py-1 rounded-full text-xs font-semibold">
                                ${pedido.estado}
                            </span>
                        </div>

                        <div class="text-sm text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt text-gray-500 mr-1"></i>
                            ${pedido.cliente.direccion}
                        </div>

                        <div class="flex justify-between items-center text-xs text-gray-600 mb-2">
                            <span><i class="fas fa-clock mr-1"></i>${pedido.hora_entrega || 'Sin hora'}</span>
                            <span><i class="fas fa-shopping-bag mr-1"></i>${pedido.producto}</span>
                        </div>

                        <div class="flex justify-between items-center pt-2 border-t">
                            <div class="text-lg font-bold text-gray-800">
                                $${pedido.precio.toLocaleString()}
                            </div>
                            <div class="flex gap-2">
                                ${sinGeocodificar}
                                ${pedido.geocodificado ?
                                    '<button onclick="event.stopPropagation(); centrarEnPedido(' + pedido.id + ')" class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs hover:bg-blue-200"><i class="fas fa-crosshairs mr-1"></i>Ver</button>' :
                                    '<button onclick="event.stopPropagation(); geocodificarPedido(' + pedido.id + ')" class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs hover:bg-purple-200"><i class="fas fa-map-pin mr-1"></i>Geocodificar</button>'
                                }
                            </div>
                        </div>
                    </div>
                `;
            });

            panel.innerHTML = html;
        }

        // ========================================
        // PEDIDO: SELECCIONAR
        // ========================================
        function seleccionarPedido(pedidoId) {
            // Remover selección previa
            document.querySelectorAll('.pedido-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Marcar como seleccionado
            const card = document.getElementById(`pedido-card-${pedidoId}`);
            if (card) {
                card.classList.add('selected');
                card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Centrar en el marcador
            centrarEnPedido(pedidoId);
        }

        // ========================================
        // MAPA: CENTRAR EN PEDIDO
        // ========================================
        function centrarEnPedido(pedidoId) {
            const marker = markers[pedidoId];
            if (marker) {
                mapa.setView(marker.getLatLng(), 16, {
                    animate: true,
                    duration: 1
                });

                setTimeout(() => {
                    marker.openPopup();
                }, 500);
            }
        }

        // ========================================
        // MAPA: CENTRAR EN CÓRDOBA
        // ========================================
        function centrarMapa() {
            if (Object.keys(markers).length > 0) {
                mapa.fitBounds(markerClusterGroup.getBounds(), {
                    padding: [50, 50],
                    maxZoom: 15
                });
            } else {
                mapa.setView(CORDOBA_CENTER, 13);
            }
        }

        // ========================================
        // PEDIDO: VER DETALLES EN MODAL
        // ========================================
        function verDetallesPedido(pedidoId) {
            const pedido = pedidosData.find(p => p.id === pedidoId);
            if (!pedido) return;

            const modalContent = document.getElementById('modalContent');

            modalContent.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Cliente -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-bold text-lg text-blue-800 mb-3">
                            <i class="fas fa-user mr-2"></i>CLIENTE
                        </h4>
                        <div class="space-y-2 text-sm">
                            <p><strong>Nombre:</strong> ${pedido.cliente.nombre}</p>
                            <p><strong>Teléfono:</strong> ${pedido.cliente.telefono}</p>
                            <p><strong>Dirección:</strong> ${pedido.cliente.direccion}</p>
                        </div>
                    </div>

                    <!-- Pedido -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="font-bold text-lg text-green-800 mb-3">
                            <i class="fas fa-shopping-bag mr-2"></i>PEDIDO
                        </h4>
                        <div class="space-y-2 text-sm">
                            <p><strong>ID:</strong> #${pedido.id}</p>
                            <p><strong>Producto:</strong> ${pedido.producto}</p>
                            <p><strong>Cantidad:</strong> ${pedido.cantidad}</p>
                            <p><strong>Precio:</strong> $${pedido.precio.toLocaleString()}</p>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-bold text-lg text-yellow-800 mb-3">
                            <i class="fas fa-info-circle mr-2"></i>ESTADO
                        </h4>
                        <div class="space-y-2 text-sm">
                            <p><strong>Estado:</strong> <span class="px-2 py-1 rounded bg-yellow-200">${pedido.estado}</span></p>
                            <p><strong>Ubicación:</strong> ${pedido.ubicacion}</p>
                            <p><strong>Hora entrega:</strong> ${pedido.hora_entrega || 'Sin especificar'}</p>
                            <p><strong>Geocodificado:</strong> ${pedido.geocodificado ? '✅ Sí' : '❌ No'}</p>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <h4 class="font-bold text-lg text-purple-800 mb-3">
                            <i class="fas fa-sticky-note mr-2"></i>OBSERVACIONES
                        </h4>
                        <div class="text-sm">
                            <p>${pedido.observaciones || 'Sin observaciones'}</p>
                            <p class="mt-2"><strong>Forma de pago:</strong> ${pedido.forma_pago}</p>
                        </div>
                    </div>

                </div>

                <!-- Acciones -->
                <div class="mt-6 flex justify-center gap-3 flex-wrap">
                    <button onclick="cambiarEstadoPedido(${pedido.id})"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-edit mr-2"></i>Cambiar Estado
                    </button>

                    <button onclick="abrirWhatsApp('${pedido.cliente.telefono}', '${pedido.cliente.nombre}', ${pedido.id})"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">
                        <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                    </button>

                    ${!pedido.geocodificado ?
                        `<button onclick="geocodificarPedido(${pedido.id})"
                                class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-map-pin mr-2"></i>Geocodificar
                        </button>` : ''
                    }

                    <button onclick="cerrarModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Cerrar
                    </button>
                </div>
            `;

            document.getElementById('modalDetalle').classList.remove('hidden');
        }

        // ========================================
        // MODAL: CERRAR
        // ========================================
        function cerrarModal() {
            document.getElementById('modalDetalle').classList.add('hidden');
        }

        // ========================================
        // GEOCODIFICACIÓN: PROCESAR PENDIENTES
        // ========================================
        async function procesarGeocodificacion() {
            if (!confirm('¿Geocodificar todos los pedidos pendientes?\n\nEsto puede tardar varios minutos.')) {
                return;
            }

            showLoading(true);

            try {
                const response = await fetch('geocoding_service.php?action=procesar_pendientes&limit=20', {
                    method: 'GET'
                });

                const data = await response.json();

                if (data.success) {
                    const stats = data.stats;
                    alert(`Geocodificación completada:\n\n` +
                          `✅ Exitosos: ${stats.exitosos}\n` +
                          `❌ Fallidos: ${stats.fallidos}\n` +
                          `📊 Total: ${stats.total}`);

                    // Recargar pedidos
                    await cargarPedidos();
                } else {
                    alert('Error en geocodificación');
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            } finally {
                showLoading(false);
            }
        }

        // ========================================
        // GEOCODIFICACIÓN: PEDIDO INDIVIDUAL
        // ========================================
        async function geocodificarPedido(pedidoId) {
            showLoading(true);

            try {
                const formData = new FormData();
                formData.append('action', 'geocodificar_pendiente');
                formData.append('id', pedidoId);

                const response = await fetch('api_delivery.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Dirección geocodificada correctamente');
                    await cargarPedidos();
                    cerrarModal();
                } else {
                    alert('❌ Error: ' + data.error);
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            } finally {
                showLoading(false);
            }
        }

        // ========================================
        // ESTADO: CAMBIAR
        // ========================================
        async function cambiarEstadoPedido(pedidoId) {
            const nuevoEstado = prompt('Nuevo estado:\n\n1. Pendiente\n2. Preparando\n3. Listo\n4. Entregado\n\nIngresa el nombre:');

            if (!nuevoEstado) return;

            const estadosValidos = ['Pendiente', 'Preparando', 'Listo', 'Entregado'];
            const estadoNormalizado = estadosValidos.find(e => e.toLowerCase() === nuevoEstado.toLowerCase());

            if (!estadoNormalizado) {
                alert('Estado no válido');
                return;
            }

            showLoading(true);

            try {
                const formData = new FormData();
                formData.append('action', 'actualizar_estado');
                formData.append('id', pedidoId);
                formData.append('estado', estadoNormalizado);

                const response = await fetch('api_delivery.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Estado actualizado');
                    await cargarPedidos();
                    cerrarModal();
                } else {
                    alert('❌ Error: ' + data.error);
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            } finally {
                showLoading(false);
            }
        }

        // ========================================
        // WHATSAPP: ABRIR CHAT
        // ========================================
        function abrirWhatsApp(telefono, nombre, pedidoId) {
            const telefonoLimpio = telefono.replace(/[^0-9]/g, '');
            const mensaje = `Hola ${nombre}, tu pedido #${pedidoId} está siendo preparado. ¡Gracias por elegirnos! 🥪`;
            const url = `https://wa.me/${telefonoLimpio}?text=${encodeURIComponent(mensaje)}`;
            window.open(url, '_blank');
        }

        // ========================================
        // LOADING: MOSTRAR/OCULTAR
        // ========================================
        function showLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            if (show) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }

        // ========================================
        // KEYBOARD SHORTCUTS
        // ========================================
        document.addEventListener('keydown', function(e) {
            // ESC para cerrar modal
            if (e.key === 'Escape') {
                cerrarModal();
            }

            // F5 para recargar pedidos
            if (e.key === 'F5') {
                e.preventDefault();
                cargarPedidos();
            }
        });

        // Auto-refresh cada 2 minutos
        setInterval(() => {
            console.log('🔄 Auto-refresh...');
            cargarPedidos();
        }, 120000); // 2 minutos

    </script>
</body>
</html>
