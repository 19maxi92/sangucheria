<?php
// admin/modules/pedidos/geocoding_service.php
// Servicio de Geocodificación usando OpenStreetMap Nominatim

require_once '../../config.php';

class GeocodingService {

    private $pdo;
    private $nominatim_url = 'https://nominatim.openstreetmap.org/search';
    private $user_agent = 'SantaCatalina/1.0 (Sangucheria Delivery System)';
    private $rate_limit_delay = 1; // Segundos entre requests (Nominatim requiere 1 req/seg)

    public function __construct() {
        $this->pdo = getConnection();
    }

    /**
     * Geocodificar una dirección
     * @param string $direccion Dirección a geocodificar
     * @param string $ciudad Ciudad (default: Córdoba)
     * @param string $pais País (default: Argentina)
     * @return array|false Array con lat/lng o false si falla
     */
    public function geocodificar($direccion, $ciudad = 'Córdoba', $pais = 'Argentina') {

        // 1. Normalizar dirección
        $direccion_normalizada = $this->normalizarDireccion($direccion, $ciudad, $pais);

        // 2. Buscar en cache
        $cache = $this->buscarEnCache($direccion_normalizada);
        if ($cache) {
            return $cache;
        }

        // 3. Llamar a Nominatim
        sleep($this->rate_limit_delay); // Respetar rate limit

        $params = [
            'q' => $direccion_normalizada,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
            'countrycodes' => 'ar' // Solo Argentina
        ];

        $url = $this->nominatim_url . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desarrollo

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            error_log("Geocoding error: HTTP $http_code - $url");
            return false;
        }

        $data = json_decode($response, true);

        if (empty($data) || !isset($data[0])) {
            error_log("Geocoding: No results for '$direccion_normalizada'");
            return false;
        }

        // 4. Extraer coordenadas
        $result = $data[0];
        $lat = (float) $result['lat'];
        $lng = (float) $result['lon'];
        $display_name = $result['display_name'] ?? $direccion_normalizada;

        // 5. Guardar en cache
        $this->guardarEnCache($direccion, $direccion_normalizada, $lat, $lng, $display_name);

        return [
            'latitud' => $lat,
            'longitud' => $lng,
            'direccion_completa' => $display_name,
            'confianza' => 1.0,
            'proveedor' => 'nominatim'
        ];
    }

    /**
     * Geocodificar todos los pedidos delivery sin geocodificar
     * @param int $limit Límite de pedidos a procesar (default: 20)
     * @return array Estadísticas del proceso
     */
    public function geocodificarPedidosPendientes($limit = 20) {

        $sql = "SELECT id, direccion, modalidad
                FROM pedidos
                WHERE modalidad = 'Delivery'
                  AND (geocodificado = 0 OR geocodificado IS NULL)
                  AND direccion IS NOT NULL
                  AND direccion != ''
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $pedidos = $stmt->fetchAll();

        $stats = [
            'total' => count($pedidos),
            'exitosos' => 0,
            'fallidos' => 0,
            'errores' => []
        ];

        foreach ($pedidos as $pedido) {
            $result = $this->geocodificar($pedido['direccion']);

            if ($result) {
                // Actualizar pedido con coordenadas
                $update = $this->pdo->prepare("
                    UPDATE pedidos
                    SET latitud = :lat,
                        longitud = :lng,
                        geocodificado = 1,
                        geocoding_date = NOW(),
                        geocoding_error = NULL
                    WHERE id = :id
                ");

                $update->execute([
                    'lat' => $result['latitud'],
                    'lng' => $result['longitud'],
                    'id' => $pedido['id']
                ]);

                $stats['exitosos']++;

            } else {
                // Marcar error
                $update = $this->pdo->prepare("
                    UPDATE pedidos
                    SET geocoding_error = 'No se pudo geocodificar la dirección',
                        geocoding_date = NOW()
                    WHERE id = :id
                ");
                $update->execute(['id' => $pedido['id']]);

                $stats['fallidos']++;
                $stats['errores'][] = [
                    'pedido_id' => $pedido['id'],
                    'direccion' => $pedido['direccion']
                ];
            }

            // Respetar rate limit
            sleep($this->rate_limit_delay);
        }

        return $stats;
    }

    /**
     * Normalizar dirección para búsqueda
     */
    private function normalizarDireccion($direccion, $ciudad, $pais) {
        $direccion = trim($direccion);

        // Si ya incluye la ciudad, no agregar
        if (stripos($direccion, $ciudad) === false) {
            $direccion .= ", $ciudad";
        }

        // Agregar país si no está
        if (stripos($direccion, $pais) === false) {
            $direccion .= ", $pais";
        }

        return $direccion;
    }

    /**
     * Buscar en cache de geocodificación
     */
    private function buscarEnCache($direccion_normalizada) {
        $stmt = $this->pdo->prepare("
            SELECT latitud, longitud, direccion_normalizada as direccion_completa,
                   confianza, proveedor
            FROM geocoding_cache
            WHERE direccion_normalizada = :dir
            LIMIT 1
        ");

        $stmt->execute(['dir' => $direccion_normalizada]);
        $result = $stmt->fetch();

        if ($result) {
            // Actualizar contador de uso
            $this->pdo->prepare("
                UPDATE geocoding_cache
                SET uso_count = uso_count + 1,
                    last_used = NOW()
                WHERE direccion_normalizada = :dir
            ")->execute(['dir' => $direccion_normalizada]);

            return $result;
        }

        return false;
    }

    /**
     * Guardar en cache de geocodificación
     */
    private function guardarEnCache($direccion_original, $direccion_normalizada, $lat, $lng, $display_name) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO geocoding_cache
                (direccion_original, direccion_normalizada, latitud, longitud, proveedor, confianza)
                VALUES (:orig, :norm, :lat, :lng, 'nominatim', 1.0)
                ON DUPLICATE KEY UPDATE
                    latitud = :lat2,
                    longitud = :lng2,
                    last_used = NOW(),
                    uso_count = uso_count + 1
            ");

            $stmt->execute([
                'orig' => $direccion_original,
                'norm' => $direccion_normalizada,
                'lat' => $lat,
                'lng' => $lng,
                'lat2' => $lat,
                'lng2' => $lng
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Error guardando cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular distancia entre dos puntos (Haversine)
     * @return float Distancia en kilómetros
     */
    public static function calcularDistancia($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371; // Radio de la Tierra en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earth_radius * $c;

        return round($distance, 2);
    }
}

// ========================================
// API ENDPOINTS
// ========================================

// Si se llama directamente como API
if (basename($_SERVER['PHP_SELF']) === 'geocoding_service.php') {

    header('Content-Type: application/json');

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $service = new GeocodingService();

    switch ($action) {

        case 'geocodificar':
            // Geocodificar una dirección
            $direccion = $_GET['direccion'] ?? $_POST['direccion'] ?? '';
            $ciudad = $_GET['ciudad'] ?? 'Córdoba';
            $pais = $_GET['pais'] ?? 'Argentina';

            if (empty($direccion)) {
                echo json_encode(['error' => 'Dirección requerida']);
                exit;
            }

            $result = $service->geocodificar($direccion, $ciudad, $pais);

            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo geocodificar']);
            }
            break;

        case 'procesar_pendientes':
            // Geocodificar pedidos pendientes
            $limit = (int) ($_GET['limit'] ?? 20);
            $stats = $service->geocodificarPedidosPendientes($limit);
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'distancia':
            // Calcular distancia entre dos puntos
            $lat1 = (float) ($_GET['lat1'] ?? 0);
            $lng1 = (float) ($_GET['lng1'] ?? 0);
            $lat2 = (float) ($_GET['lat2'] ?? 0);
            $lng2 = (float) ($_GET['lng2'] ?? 0);

            $distancia = GeocodingService::calcularDistancia($lat1, $lng1, $lat2, $lng2);
            echo json_encode(['success' => true, 'distancia_km' => $distancia]);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
    }
}
?>
