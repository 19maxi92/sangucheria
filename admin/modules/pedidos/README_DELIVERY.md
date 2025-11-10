# 🚚 Sistema de Delivery con Mapa Interactivo

## OpenStreetMap + Leaflet.js

Sistema completo de gestión de entregas con mapa interactivo, geocodificación automática y optimización de rutas para Santa Catalina Sanguchería.

---

## 🎯 Características

### ✅ Implementadas (FASE 1)

- **Mapa Interactivo** con OpenStreetMap + Leaflet.js
- **Geocodificación Automática** de direcciones usando Nominatim (OSM)
- **Marcadores Dinámicos** con colores por estado del pedido
- **Panel Lateral** con lista de pedidos en tiempo real
- **Filtros Avanzados** por estado, ubicación y fecha
- **Popups Personalizados** con información completa del pedido
- **MarkerCluster** para agrupar pedidos cercanos
- **Integración WhatsApp** directa desde el mapa
- **Cache de Geocodificación** para optimizar requests
- **API RESTful** para gestionar pedidos
- **Responsive Design** adaptado a móviles

### 🔜 Por Implementar (Próximas Fases)

- **FASE 2:** Optimización de rutas con OSRM
- **FASE 3:** Asignación de repartidores
- **FASE 4:** Seguimiento en tiempo real (GPS tracking)

---

## 📦 Instalación

### 1. Ejecutar Script de Instalación

Visita en tu navegador:

```
https://tu-dominio.com/admin/modules/pedidos/instalar_delivery.php
```

Este script:
- ✅ Agrega columnas de geocodificación a la tabla `pedidos`
- ✅ Crea tabla `repartidores`
- ✅ Crea tabla `rutas_delivery`
- ✅ Crea tabla `geocoding_cache`
- ✅ Agrega índices de optimización

### 2. Verificar Instalación

El instalador mostrará:
- Total de pedidos
- Pedidos de delivery
- Pedidos geocodificados
- Repartidores registrados

### 3. Acceder al Mapa

Desde el módulo **Ver Pedidos**, haz clic en el botón:

```
🗺️ Mapa Delivery
```

---

## 🗺️ Uso del Sistema

### Panel Principal

#### Estadísticas
- **Total Delivery:** Pedidos de entrega del día
- **Geocodificados:** Pedidos con coordenadas GPS
- **Pendientes, Preparando, Listos:** Estados actuales
- **Asignados:** Pedidos con repartidor asignado

#### Mapa Interactivo
- **Marcadores de colores:**
  - 🟡 **Amarillo:** Pendiente
  - 🔵 **Azul:** Preparando
  - 🟢 **Verde:** Listo
  - ⚫ **Gris:** Entregado

#### Panel Lateral
- Lista de todos los pedidos delivery
- Click en un pedido para:
  - Ver en el mapa
  - Centrar la vista
  - Abrir detalles completos

---

## 🔧 Funcionalidades

### 1. Geocodificar Direcciones

**Automático:**
```
Botón: "Geocodificar Pendientes"
```
Procesa hasta 20 pedidos sin geocodificar.

**Individual:**
Desde el panel lateral o popup del mapa:
```
Botón: "Geocodificar" (en pedidos sin coordenadas)
```

**Cómo funciona:**
- Usa **Nominatim** de OpenStreetMap (100% gratis)
- Límite: 1 request/segundo (respeta rate limit)
- Cache local para evitar requests duplicados

### 2. Ver Detalles de Pedido

Click en un marcador del mapa o en la lista lateral:
- 📋 Información del cliente
- 🛍️ Detalles del pedido
- 📍 Dirección y estado de geocodificación
- 💬 WhatsApp directo
- ✏️ Cambiar estado

### 3. Cambiar Estado

Desde el modal de detalles:
```
Estados disponibles:
- Pendiente
- Preparando
- Listo
- Entregado
```

El marcador cambia de color automáticamente.

### 4. Filtros

**Por Estado:**
- Todos
- Pendiente
- Preparando
- Listo
- Entregado

**Por Ubicación:**
- Todas
- 🏪 Local 1
- 🏭 Fábrica

**Por Fecha:**
Selector de fecha (default: hoy)

### 5. Centrar Mapa

```
Botón: "Centrar Mapa"
```
Ajusta la vista para ver todos los pedidos.

### 6. Auto-Refresh

El mapa se actualiza automáticamente cada 2 minutos.

**Refresh manual:**
```
Botón: "Actualizar"
o
Presiona F5
```

---

## 🔑 Atajos de Teclado

- **ESC:** Cerrar modal
- **F5:** Actualizar pedidos

---

## 🌐 API Endpoints

### GET `/api_delivery.php`

#### Obtener Pedidos
```
?action=get_pedidos&fecha=2025-01-10&estado=Pendiente&ubicacion=Local 1
```

**Response:**
```json
{
  "success": true,
  "total": 15,
  "pedidos": [
    {
      "id": 123,
      "cliente": {
        "nombre": "Juan Pérez",
        "telefono": "3512345678",
        "direccion": "Av. Colón 1234"
      },
      "producto": "Plancha x8 Completo",
      "precio": 15000,
      "estado": "Pendiente",
      "coordenadas": {
        "lat": -31.4201,
        "lng": -64.1888
      },
      "geocodificado": true
    }
  ]
}
```

#### Obtener Repartidores
```
?action=get_repartidores
```

#### Estadísticas
```
?action=estadisticas&fecha=2025-01-10
```

### POST `/api_delivery.php`

#### Actualizar Estado
```
action=actualizar_estado
id=123
estado=Listo
```

#### Asignar Repartidor
```
action=asignar_repartidor
id=123
repartidor_id=5
```

#### Geocodificar Pedido
```
action=geocodificar_pendiente
id=123
```

---

## 📊 Base de Datos

### Nuevas Tablas

#### `repartidores`
```sql
id, nombre, apellido, telefono, activo, zona_asignada, vehiculo
```

#### `rutas_delivery`
```sql
id, repartidor_id, fecha_ruta, pedidos_ids, orden_optimizado,
distancia_total_km, tiempo_estimado_min, estado
```

#### `geocoding_cache`
```sql
id, direccion_original, direccion_normalizada, latitud, longitud,
proveedor, confianza, ciudad, provincia
```

### Nuevas Columnas en `pedidos`

```sql
latitud DECIMAL(10, 8)
longitud DECIMAL(11, 8)
geocodificado TINYINT(1)
geocoding_error TEXT
geocoding_date TIMESTAMP
costo_envio DECIMAL(10,2)
repartidor_id INT
ruta_id INT
```

---

## 🛠️ Servicios Utilizados

### OpenStreetMap (OSM)
- **Tiles del mapa:** `https://tile.openstreetmap.org/`
- **Licencia:** Open Data Commons Open Database License (ODbL)
- **Costo:** $0 USD (gratis)

### Nominatim (Geocodificación)
- **API:** `https://nominatim.openstreetmap.org/search`
- **Rate Limit:** 1 request/segundo
- **Licencia:** Open Database License
- **Costo:** $0 USD (gratis)

### Leaflet.js
- **Versión:** 1.9.4
- **CDN:** `unpkg.com/leaflet@1.9.4/`
- **Licencia:** BSD-2-Clause
- **Costo:** $0 USD (gratis)

### Leaflet.markercluster
- **Versión:** 1.5.3
- **CDN:** `unpkg.com/leaflet.markercluster@1.5.3/`
- **Licencia:** MIT
- **Costo:** $0 USD (gratis)

**TOTAL DE COSTOS:** $0 USD 🎉

---

## ⚠️ Limitaciones Actuales

### Nominatim Rate Limit
- **Límite:** 1 request por segundo
- **Solución:** Cache local implementado
- **Alternativa:** Self-host Nominatim si se necesita más velocidad

### Sin Tracking en Tiempo Real
- Implementado en FASE 4 (futuro)
- Requiere GPS del repartidor + WebSockets

### Sin Optimización de Rutas
- Implementado en FASE 2 (próximamente)
- Usará OSRM o GraphHopper

---

## 🚀 Próximas Funcionalidades

### FASE 2: Optimización de Rutas (2-3 semanas)
- [ ] Integración con OSRM para cálculo de rutas
- [ ] Algoritmo de optimización (TSP - Travelling Salesman)
- [ ] Generación automática de ruta óptima del día
- [ ] Exportar ruta a Google Maps / Waze

### FASE 3: Gestión de Repartidores (3-4 semanas)
- [ ] ABM completo de repartidores
- [ ] Asignación automática por zona
- [ ] Dashboard del repartidor (mobile-friendly)
- [ ] Historial de entregas por repartidor

### FASE 4: Tracking en Tiempo Real (4-6 semanas)
- [ ] Ubicación GPS del repartidor en vivo
- [ ] Notificaciones al cliente (SMS/WhatsApp)
- [ ] Tiempo estimado de llegada
- [ ] Foto de comprobante de entrega

---

## 📞 Soporte

Para reportar bugs o solicitar funcionalidades:
1. Contactar al administrador del sistema
2. Verificar logs en `error_log` del servidor

---

## 🎓 Recursos

### Documentación Oficial
- [OpenStreetMap Wiki](https://wiki.openstreetmap.org/)
- [Leaflet.js Docs](https://leafletjs.com/reference.html)
- [Nominatim API](https://nominatim.org/release-docs/latest/api/Overview/)

### Tutoriales
- [Leaflet Quick Start](https://leafletjs.com/examples/quick-start/)
- [OSM Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/)

---

## 📜 Licencia

Sistema propietario de Santa Catalina Sanguchería.

Componentes de terceros bajo sus respectivas licencias:
- OpenStreetMap: ODbL
- Leaflet.js: BSD-2-Clause
- Nominatim: ODbL

---

## 👨‍💻 Desarrollado por

**Sistema creado:** Enero 2025
**Stack:** PHP 7.4+ | MySQL 5.7+ | OpenStreetMap | Leaflet.js
**Filosofía:** 100% Open Source

---

¡Felices entregas! 🚚🥪
