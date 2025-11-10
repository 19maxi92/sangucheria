<?php
// admin/modules/pedidos/instalar_delivery.php
// Script de instalación del sistema de delivery

require_once '../../config.php';
requireLogin();

$pdo = getConnection();
$errores = [];
$exitos = [];

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalación Sistema Delivery</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gray-100'>
    <div class='container mx-auto px-4 py-8 max-w-4xl'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6'>
                <i class='fas fa-truck text-blue-500 mr-2'></i>
                Instalación Sistema de Delivery
            </h1>";

try {
    // 1. Agregar columnas de geocodificación a pedidos
    echo "<div class='mb-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded'>
            <h3 class='font-bold text-blue-800 mb-2'>
                <i class='fas fa-database mr-2'></i>1. Agregando columnas de geocodificación...
            </h3>";

    // Verificar si ya existen las columnas
    $check = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'latitud'")->fetch();

    if (!$check) {
        $pdo->exec("ALTER TABLE pedidos
            ADD COLUMN latitud DECIMAL(10, 8) DEFAULT NULL COMMENT 'Latitud de la dirección',
            ADD COLUMN longitud DECIMAL(11, 8) DEFAULT NULL COMMENT 'Longitud de la dirección',
            ADD COLUMN geocodificado TINYINT(1) DEFAULT 0 COMMENT 'Si fue geocodificada',
            ADD COLUMN geocoding_error TEXT DEFAULT NULL COMMENT 'Error de geocodificación',
            ADD COLUMN geocoding_date TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de geocodificación',
            ADD COLUMN costo_envio DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo del envío'
        ");
        echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Columnas agregadas exitosamente</p>";
        $exitos[] = "Columnas de geocodificación agregadas";
    } else {
        echo "<p class='text-yellow-700'><i class='fas fa-info-circle mr-2'></i>Columnas ya existían, saltando...</p>";
        $exitos[] = "Columnas ya existían";
    }

    echo "</div>";

    // 2. Crear tabla repartidores
    echo "<div class='mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded'>
            <h3 class='font-bold text-green-800 mb-2'>
                <i class='fas fa-users mr-2'></i>2. Creando tabla de repartidores...
            </h3>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS repartidores (
      id INT PRIMARY KEY AUTO_INCREMENT,
      nombre VARCHAR(100) NOT NULL,
      apellido VARCHAR(100) NOT NULL,
      telefono VARCHAR(20),
      activo TINYINT(1) DEFAULT 1,
      zona_asignada VARCHAR(100) DEFAULT NULL COMMENT 'Zona geográfica asignada',
      vehiculo VARCHAR(50) DEFAULT NULL COMMENT 'Tipo de vehículo',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Tabla repartidores creada</p>";
    $exitos[] = "Tabla repartidores creada";

    echo "</div>";

    // 3. Crear tabla rutas_delivery
    echo "<div class='mb-4 p-4 bg-purple-50 border-l-4 border-purple-500 rounded'>
            <h3 class='font-bold text-purple-800 mb-2'>
                <i class='fas fa-route mr-2'></i>3. Creando tabla de rutas...
            </h3>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS rutas_delivery (
      id INT PRIMARY KEY AUTO_INCREMENT,
      repartidor_id INT DEFAULT NULL,
      fecha_ruta DATE NOT NULL,
      pedidos_ids JSON DEFAULT NULL COMMENT 'Array de IDs de pedidos',
      orden_optimizado JSON DEFAULT NULL COMMENT 'Array ordenado de IDs',
      distancia_total_km DECIMAL(10,2) DEFAULT NULL,
      tiempo_estimado_min INT DEFAULT NULL,
      estado ENUM('pendiente','en_curso','completada','cancelada') DEFAULT 'pendiente',
      notas TEXT DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_fecha (fecha_ruta),
      INDEX idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Tabla rutas_delivery creada</p>";
    $exitos[] = "Tabla rutas_delivery creada";

    echo "</div>";

    // 4. Agregar foreign keys
    echo "<div class='mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded'>
            <h3 class='font-bold text-yellow-800 mb-2'>
                <i class='fas fa-link mr-2'></i>4. Agregando relaciones...
            </h3>";

    // Verificar si ya existe la columna
    $check_repartidor = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'repartidor_id'")->fetch();

    if (!$check_repartidor) {
        $pdo->exec("ALTER TABLE pedidos
            ADD COLUMN repartidor_id INT DEFAULT NULL COMMENT 'ID del repartidor asignado',
            ADD COLUMN ruta_id INT DEFAULT NULL COMMENT 'ID de la ruta asignada'
        ");

        echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Columnas de relación agregadas</p>";
        $exitos[] = "Relaciones agregadas";
    } else {
        echo "<p class='text-yellow-700'><i class='fas fa-info-circle mr-2'></i>Relaciones ya existían</p>";
    }

    echo "</div>";

    // 5. Crear tabla de cache de geocodificación
    echo "<div class='mb-4 p-4 bg-indigo-50 border-l-4 border-indigo-500 rounded'>
            <h3 class='font-bold text-indigo-800 mb-2'>
                <i class='fas fa-database mr-2'></i>5. Creando cache de geocodificación...
            </h3>";

    $pdo->exec("CREATE TABLE IF NOT EXISTS geocoding_cache (
      id INT PRIMARY KEY AUTO_INCREMENT,
      direccion_original VARCHAR(500) NOT NULL,
      direccion_normalizada VARCHAR(500) NOT NULL,
      latitud DECIMAL(10, 8) NOT NULL,
      longitud DECIMAL(11, 8) NOT NULL,
      proveedor VARCHAR(50) DEFAULT 'nominatim' COMMENT 'nominatim, google, etc',
      confianza DECIMAL(3,2) DEFAULT 1.0 COMMENT 'Nivel de confianza 0-1',
      pais VARCHAR(50) DEFAULT 'Argentina',
      ciudad VARCHAR(100) DEFAULT NULL,
      provincia VARCHAR(100) DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      uso_count INT DEFAULT 1 COMMENT 'Veces que se usó este cache',
      UNIQUE KEY idx_direccion (direccion_normalizada),
      INDEX idx_ciudad (ciudad)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Cache de geocodificación creado</p>";
    $exitos[] = "Cache de geocodificación creado";

    echo "</div>";

    // 6. Crear índices
    echo "<div class='mb-4 p-4 bg-pink-50 border-l-4 border-pink-500 rounded'>
            <h3 class='font-bold text-pink-800 mb-2'>
                <i class='fas fa-bolt mr-2'></i>6. Creando índices de optimización...
            </h3>";

    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_modalidad ON pedidos(modalidad)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_geocodificado ON pedidos(geocodificado)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_fecha_entrega ON pedidos(fecha_entrega)");
        echo "<p class='text-green-700'><i class='fas fa-check-circle mr-2'></i>Índices creados</p>";
        $exitos[] = "Índices de optimización creados";
    } catch (Exception $e) {
        echo "<p class='text-yellow-700'><i class='fas fa-info-circle mr-2'></i>Algunos índices ya existían</p>";
    }

    echo "</div>";

    // 7. Estadísticas finales
    echo "<div class='mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded'>
            <h3 class='font-bold text-emerald-800 mb-2'>
                <i class='fas fa-chart-bar mr-2'></i>7. Estadísticas actuales...
            </h3>";

    $stats = $pdo->query("
        SELECT
            COUNT(*) as total_pedidos,
            SUM(CASE WHEN modalidad = 'Delivery' THEN 1 ELSE 0 END) as total_delivery,
            SUM(CASE WHEN modalidad = 'Delivery' AND geocodificado = 1 THEN 1 ELSE 0 END) as geocodificados
        FROM pedidos
    ")->fetch();

    $total_repartidores = $pdo->query("SELECT COUNT(*) as total FROM repartidores")->fetchColumn();

    echo "<div class='grid grid-cols-2 gap-4 mt-3'>
            <div class='text-center'>
                <div class='text-3xl font-bold text-gray-700'>{$stats['total_pedidos']}</div>
                <div class='text-sm text-gray-500'>Total Pedidos</div>
            </div>
            <div class='text-center'>
                <div class='text-3xl font-bold text-blue-700'>{$stats['total_delivery']}</div>
                <div class='text-sm text-blue-500'>Pedidos Delivery</div>
            </div>
            <div class='text-center'>
                <div class='text-3xl font-bold text-green-700'>{$stats['geocodificados']}</div>
                <div class='text-sm text-green-500'>Geocodificados</div>
            </div>
            <div class='text-center'>
                <div class='text-3xl font-bold text-purple-700'>{$total_repartidores}</div>
                <div class='text-sm text-purple-500'>Repartidores</div>
            </div>
          </div>";

    echo "</div>";

    // ÉXITO TOTAL
    echo "<div class='mt-8 p-6 bg-green-100 border border-green-400 rounded-lg text-center'>
            <i class='fas fa-check-circle text-6xl text-green-500 mb-4'></i>
            <h2 class='text-2xl font-bold text-green-800 mb-2'>¡Instalación Completada Exitosamente!</h2>
            <p class='text-green-700 mb-4'>El sistema de delivery con mapa OSM está listo para usar.</p>
            <div class='flex justify-center gap-4 mt-6'>
                <a href='delivery_simple.php' class='bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg'>
                    <i class='fas fa-map-marked-alt mr-2'></i>Ir al Mapa de Delivery
                </a>
                <a href='ver_pedidos.php' class='bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg'>
                    <i class='fas fa-list mr-2'></i>Ver Pedidos
                </a>
            </div>
          </div>";

} catch (Exception $e) {
    echo "<div class='mt-4 p-4 bg-red-100 border border-red-400 rounded-lg'>
            <h3 class='font-bold text-red-800 mb-2'>
                <i class='fas fa-exclamation-triangle mr-2'></i>Error durante la instalación
            </h3>
            <p class='text-red-700'>" . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
    $errores[] = $e->getMessage();
}

// Resumen
echo "<div class='mt-8 p-4 bg-gray-100 rounded-lg'>
        <h3 class='font-bold text-gray-800 mb-3'>Resumen de Instalación:</h3>
        <ul class='space-y-1'>";

foreach ($exitos as $exito) {
    echo "<li class='text-green-700'><i class='fas fa-check mr-2'></i>$exito</li>";
}

foreach ($errores as $error) {
    echo "<li class='text-red-700'><i class='fas fa-times mr-2'></i>$error</li>";
}

echo "</ul>
      </div>";

echo "
        </div>
    </div>
</body>
</html>";
?>
