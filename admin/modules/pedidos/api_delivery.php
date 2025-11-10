<?php
// admin/modules/pedidos/api_delivery.php
// API para obtener pedidos de delivery en formato JSON para el mapa

require_once '../../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getConnection();
$action = $_GET['action'] ?? 'get_pedidos';

try {
    switch ($action) {

        case 'get_pedidos':
            // Obtener pedidos de delivery
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $estado = $_GET['estado'] ?? '';
            $ubicacion = $_GET['ubicacion'] ?? '';

            $sql = "SELECT
                        p.id,
                        p.nombre,
                        p.apellido,
                        p.telefono,
                        p.direccion,
                        p.latitud,
                        p.longitud,
                        p.geocodificado,
                        p.producto,
                        p.cantidad,
                        p.precio,
                        p.estado,
                        p.ubicacion,
                        p.hora_entrega,
                        p.fecha_entrega,
                        p.observaciones,
                        p.forma_pago,
                        p.costo_envio,
                        p.created_at,
                        p.repartidor_id,
                        r.nombre as repartidor_nombre,
                        r.apellido as repartidor_apellido,
                        cf.nombre as cliente_fijo_nombre,
                        cf.apellido as cliente_fijo_apellido
                    FROM pedidos p
                    LEFT JOIN repartidores r ON p.repartidor_id = r.id
                    LEFT JOIN clientes_fijos cf ON p.cliente_fijo_id = cf.id
                    WHERE p.modalidad = 'Delivery'";

            $params = [];

            // Filtros
            if ($estado && $estado !== 'todos') {
                $sql .= " AND p.estado = :estado";
                $params['estado'] = $estado;
            } else {
                // Por defecto mostrar solo pendientes, preparando y listos
                $sql .= " AND p.estado IN ('Pendiente', 'Preparando', 'Listo')";
            }

            if ($ubicacion && $ubicacion !== 'todas') {
                $sql .= " AND p.ubicacion = :ubicacion";
                $params['ubicacion'] = $ubicacion;
            }

            if ($fecha) {
                $sql .= " AND DATE(p.fecha_entrega) = :fecha";
                $params['fecha'] = $fecha;
            }

            $sql .= " ORDER BY p.hora_entrega ASC, p.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pedidos = $stmt->fetchAll();

            // Formatear datos para el mapa
            $pedidos_formateados = array_map(function($p) {
                $nombre_cliente = $p['cliente_fijo_nombre']
                    ? $p['cliente_fijo_nombre'] . ' ' . $p['cliente_fijo_apellido']
                    : $p['nombre'] . ' ' . $p['apellido'];

                return [
                    'id' => (int) $p['id'],
                    'cliente' => [
                        'nombre' => $nombre_cliente,
                        'telefono' => $p['telefono'],
                        'direccion' => $p['direccion']
                    ],
                    'producto' => $p['producto'],
                    'cantidad' => (int) $p['cantidad'],
                    'precio' => (float) $p['precio'],
                    'costo_envio' => (float) ($p['costo_envio'] ?? 0),
                    'estado' => $p['estado'],
                    'ubicacion' => $p['ubicacion'],
                    'hora_entrega' => $p['hora_entrega'],
                    'fecha_entrega' => $p['fecha_entrega'],
                    'observaciones' => $p['observaciones'],
                    'forma_pago' => $p['forma_pago'],
                    'created_at' => $p['created_at'],
                    'geocodificado' => (bool) $p['geocodificado'],
                    'coordenadas' => [
                        'lat' => $p['latitud'] ? (float) $p['latitud'] : null,
                        'lng' => $p['longitud'] ? (float) $p['longitud'] : null
                    ],
                    'repartidor' => $p['repartidor_id'] ? [
                        'id' => (int) $p['repartidor_id'],
                        'nombre' => $p['repartidor_nombre'] . ' ' . $p['repartidor_apellido']
                    ] : null
                ];
            }, $pedidos);

            echo json_encode([
                'success' => true,
                'total' => count($pedidos_formateados),
                'pedidos' => $pedidos_formateados
            ]);
            break;

        case 'actualizar_estado':
            // Actualizar estado de un pedido
            $id = (int) ($_POST['id'] ?? 0);
            $nuevo_estado = $_POST['estado'] ?? '';

            if (!$id || !$nuevo_estado) {
                throw new Exception('ID y estado requeridos');
            }

            $stmt = $pdo->prepare("UPDATE pedidos SET estado = :estado WHERE id = :id");
            $stmt->execute(['estado' => $nuevo_estado, 'id' => $id]);

            echo json_encode(['success' => true, 'mensaje' => 'Estado actualizado']);
            break;

        case 'asignar_repartidor':
            // Asignar repartidor a un pedido
            $id = (int) ($_POST['id'] ?? 0);
            $repartidor_id = (int) ($_POST['repartidor_id'] ?? 0);

            if (!$id) {
                throw new Exception('ID de pedido requerido');
            }

            $stmt = $pdo->prepare("UPDATE pedidos SET repartidor_id = :repartidor WHERE id = :id");
            $stmt->execute([
                'repartidor' => $repartidor_id ? $repartidor_id : null,
                'id' => $id
            ]);

            echo json_encode(['success' => true, 'mensaje' => 'Repartidor asignado']);
            break;

        case 'get_repartidores':
            // Obtener lista de repartidores activos
            $stmt = $pdo->query("
                SELECT id, nombre, apellido, telefono, zona_asignada, vehiculo
                FROM repartidores
                WHERE activo = 1
                ORDER BY nombre ASC
            ");

            $repartidores = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'repartidores' => $repartidores
            ]);
            break;

        case 'estadisticas':
            // Obtener estadísticas de delivery
            $fecha = $_GET['fecha'] ?? date('Y-m-d');

            $stats = $pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN geocodificado = 1 THEN 1 ELSE 0 END) as geocodificados,
                    SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'Preparando' THEN 1 ELSE 0 END) as preparando,
                    SUM(CASE WHEN estado = 'Listo' THEN 1 ELSE 0 END) as listos,
                    SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END) as entregados,
                    SUM(CASE WHEN repartidor_id IS NOT NULL THEN 1 ELSE 0 END) as asignados,
                    SUM(precio + COALESCE(costo_envio, 0)) as total_ventas
                FROM pedidos
                WHERE modalidad = 'Delivery'
                  AND DATE(fecha_entrega) = :fecha
            ");

            $stats->execute(['fecha' => $fecha]);
            $data = $stats->fetch();

            echo json_encode([
                'success' => true,
                'fecha' => $fecha,
                'estadisticas' => $data
            ]);
            break;

        case 'geocodificar_pendiente':
            // Geocodificar un pedido específico
            $id = (int) ($_POST['id'] ?? 0);

            if (!$id) {
                throw new Exception('ID requerido');
            }

            // Obtener dirección del pedido
            $stmt = $pdo->prepare("SELECT direccion FROM pedidos WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $pedido = $stmt->fetch();

            if (!$pedido || !$pedido['direccion']) {
                throw new Exception('Pedido no encontrado o sin dirección');
            }

            // Geocodificar
            require_once 'geocoding_service.php';
            $service = new GeocodingService();
            $result = $service->geocodificar($pedido['direccion']);

            if ($result) {
                $update = $pdo->prepare("
                    UPDATE pedidos
                    SET latitud = :lat,
                        longitud = :lng,
                        geocodificado = 1,
                        geocoding_date = NOW()
                    WHERE id = :id
                ");

                $update->execute([
                    'lat' => $result['latitud'],
                    'lng' => $result['longitud'],
                    'id' => $id
                ]);

                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Dirección geocodificada',
                    'coordenadas' => [
                        'lat' => $result['latitud'],
                        'lng' => $result['longitud']
                    ]
                ]);
            } else {
                throw new Exception('No se pudo geocodificar la dirección');
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
