-- ========================================
-- UPGRADE SQL PARA SISTEMA DELIVERY OSM
-- Santa Catalina - Sistema de Pedidos
-- ========================================

-- 1. AGREGAR COLUMNAS DE GEOCODIFICACIÓN A PEDIDOS
ALTER TABLE pedidos
ADD COLUMN IF NOT EXISTS latitud DECIMAL(10, 8) DEFAULT NULL COMMENT 'Latitud de la dirección (geocodificada)',
ADD COLUMN IF NOT EXISTS longitud DECIMAL(11, 8) DEFAULT NULL COMMENT 'Longitud de la dirección (geocodificada)',
ADD COLUMN IF NOT EXISTS geocodificado TINYINT(1) DEFAULT 0 COMMENT 'Si la dirección fue geocodificada (0=no, 1=si)',
ADD COLUMN IF NOT EXISTS geocoding_error TEXT DEFAULT NULL COMMENT 'Error de geocodificación si hubo',
ADD COLUMN IF NOT EXISTS geocoding_date TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha de última geocodificación',
ADD COLUMN IF NOT EXISTS costo_envio DECIMAL(10,2) DEFAULT 0 COMMENT 'Costo del envío calculado';

-- 2. CREAR TABLA DE REPARTIDORES
CREATE TABLE IF NOT EXISTS repartidores (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CREAR TABLA DE RUTAS DE DELIVERY
CREATE TABLE IF NOT EXISTS rutas_delivery (
  id INT PRIMARY KEY AUTO_INCREMENT,
  repartidor_id INT DEFAULT NULL,
  fecha_ruta DATE NOT NULL,
  pedidos_ids JSON DEFAULT NULL COMMENT 'Array de IDs de pedidos [1,5,8,12]',
  orden_optimizado JSON DEFAULT NULL COMMENT 'Array ordenado de IDs optimizado por distancia',
  distancia_total_km DECIMAL(10,2) DEFAULT NULL,
  tiempo_estimado_min INT DEFAULT NULL,
  estado ENUM('pendiente','en_curso','completada','cancelada') DEFAULT 'pendiente',
  notas TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (repartidor_id) REFERENCES repartidores(id) ON DELETE SET NULL,
  INDEX idx_fecha (fecha_ruta),
  INDEX idx_estado (estado),
  INDEX idx_repartidor (repartidor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. AGREGAR COLUMNA DE REPARTIDOR ASIGNADO A PEDIDOS
ALTER TABLE pedidos
ADD COLUMN IF NOT EXISTS repartidor_id INT DEFAULT NULL COMMENT 'ID del repartidor asignado',
ADD COLUMN IF NOT EXISTS ruta_id INT DEFAULT NULL COMMENT 'ID de la ruta asignada',
ADD CONSTRAINT fk_pedidos_repartidor FOREIGN KEY (repartidor_id)
    REFERENCES repartidores(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_pedidos_ruta FOREIGN KEY (ruta_id)
    REFERENCES rutas_delivery(id) ON DELETE SET NULL;

-- 5. CREAR ÍNDICES PARA OPTIMIZAR BÚSQUEDAS
CREATE INDEX IF NOT EXISTS idx_modalidad ON pedidos(modalidad);
CREATE INDEX IF NOT EXISTS idx_geocodificado ON pedidos(geocodificado);
CREATE INDEX IF NOT EXISTS idx_fecha_entrega ON pedidos(fecha_entrega);
CREATE INDEX IF NOT EXISTS idx_estado_modalidad ON pedidos(estado, modalidad);

-- 6. CREAR TABLA DE CACHE DE GEOCODIFICACIÓN (para evitar llamadas repetidas)
CREATE TABLE IF NOT EXISTS geocoding_cache (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. INSERTAR REPARTIDORES DE EJEMPLO (opcional - comentar si no se necesita)
-- INSERT INTO repartidores (nombre, apellido, telefono, activo, zona_asignada, vehiculo) VALUES
-- ('Juan', 'Pérez', '3512345678', 1, 'Centro', 'Moto'),
-- ('María', 'González', '3519876543', 1, 'Nueva Córdoba', 'Bicicleta'),
-- ('Carlos', 'Rodríguez', '3511122334', 1, 'General Paz', 'Auto');

-- ========================================
-- FIN DEL UPGRADE
-- ========================================

-- Verificar resultados
SELECT 'Upgrade completado exitosamente' AS status;
SELECT COUNT(*) as total_pedidos FROM pedidos;
SELECT COUNT(*) as pedidos_delivery FROM pedidos WHERE modalidad = 'Delivery';
SELECT COUNT(*) as pedidos_geocodificados FROM pedidos WHERE geocodificado = 1;
SELECT COUNT(*) as total_repartidores FROM repartidores;
