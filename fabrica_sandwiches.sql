-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-08-2025 a las 05:08:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `fabrica_sandwiches`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `planchas` float NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `modalidad` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha` datetime DEFAULT current_timestamp(),
  `fijo` tinyint(1) DEFAULT 0,
  `productos` text DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `pago` varchar(50) DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `nombre`, `apellido`, `cantidad`, `planchas`, `contacto`, `direccion`, `modalidad`, `observaciones`, `fecha_pedido`, `fecha`, `fijo`, `productos`, `total`, `pago`, `estado`) VALUES
(1, 'maxi', 'burgos', 12, 0, '22116267575', '10 n°1564', 'Retira', 'qweqwe', '2025-04-15 14:32:58', '2025-04-15 11:50:15', 0, NULL, NULL, NULL, 'Pendiente'),
(2, 'maxi', 'burgos', 24, 1, '22116267575', '10 n°1564', 'retira', '123', '2025-04-15 14:40:31', '2025-04-15 11:50:15', 0, NULL, NULL, NULL, 'Pendiente'),
(3, 'maxi', 'burgos', 24, 1, '22116267575', '10 n°1564', 'Retiro', 'qe', '2025-04-15 14:43:11', '2025-04-15 11:50:15', 0, NULL, NULL, NULL, 'Pendiente'),
(4, 'juan', 'bosanic', 48, 2, '2214123', '12312', 'Envío', 'asdasd', '2025-04-15 14:54:03', '2025-04-15 11:54:03', 0, NULL, NULL, NULL, 'Pendiente'),
(5, 'enrique', 'jaurez', 60, 2.5, '123123', '1231', 'Retira', '123123', '2025-04-15 14:57:33', '2025-04-15 11:57:33', 0, NULL, NULL, NULL, 'Pendiente'),
(6, 'Juan', 'Perez', 12, 0.5, '111234567', 'Av. Siempre Viva 123', 'Retira', 'Sin observaciones', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(7, 'Ana', 'Lopez', 24, 1, '112233445', 'Calle Falsa 456', 'Envío', 'Urgente', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(8, 'Carlos', 'Gomez', 48, 2, '113344556', 'Calle Real 789', 'Retira', 'Con extra mayonesa', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(9, 'Maria', 'Martinez', 12, 0.5, '114455667', 'Av. Libertador 1010', 'Envío', 'Pedido con cambio de pan', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(10, 'Luis', 'Rodriguez', 24, 1, '115566778', 'Calle de la Luna 202', 'Retira', 'Sin cebolla', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(11, 'Marta', 'Fernandez', 48, 2, '116677889', 'Calle del Sol 303', 'Envío', 'Con extra queso', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(12, 'Pedro', 'Lopez', 12, 0.5, '117788990', 'Av. Constitución 404', 'Retira', 'Sin tomate', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(13, 'Lucia', 'Garcia', 24, 1, '118899001', 'Calle del Río 505', 'Envío', 'Con extra salsa', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(14, 'Fernando', 'Hernandez', 48, 2, '119900112', 'Calle del Norte 606', 'Retira', 'Sin mayonesa', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(15, 'Sofia', 'Gonzalez', 12, 0.5, '120011223', 'Av. San Martin 707', 'Envío', 'Sin lechuga', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(16, 'Ruben', 'Diaz', 24, 1, '121122334', 'Calle de la Paz 808', 'Retira', 'Sin aceite de oliva', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(17, 'Laura', 'Perez', 48, 2, '122233445', 'Calle de los Pinos 909', 'Envío', 'Pedido para dieta', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(18, 'Juan', 'Gonzalez', 12, 0.5, '123344556', 'Calle de la Villa 101', 'Retira', 'Con extra pepino', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(19, 'Elena', 'Martinez', 24, 1, '124455667', 'Av. de Mayo 202', 'Envío', 'Con extra tomate', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(20, 'Javier', 'Rodriguez', 48, 2, '125566778', 'Calle del Viento 303', 'Retira', 'Con extra pollo', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(21, 'Adriana', 'Lopez', 12, 0.5, '126677889', 'Av. del Libertador 404', 'Envío', 'Con extra bacon', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(22, 'Oscar', 'Fernandez', 24, 1, '127788990', 'Calle del Sol 505', 'Retira', 'Con extra jamón', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(23, 'Susana', 'Gomez', 48, 2, '128899001', 'Calle de la Esperanza 606', 'Envío', 'Sin mayonesa', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(24, 'Carlos', 'Hernandez', 12, 0.5, '129900112', 'Calle Nueva 707', 'Retira', 'Con cebolla extra', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(25, 'Nadia', 'Gonzalez', 24, 1, '130011223', 'Calle del Lago 808', 'Envío', 'Con extra queso', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(26, 'Hector', 'Diaz', 48, 2, '131122334', 'Calle del Bosque 909', 'Retira', 'Sin pan integral', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(27, 'Mariana', 'Rodriguez', 12, 0.5, '132233445', 'Av. Rivadavia 101', 'Envío', 'Sin salsa de mostaza', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(28, 'Felipe', 'Lopez', 24, 1, '133344556', 'Calle de la Luna 202', 'Retira', 'Con mayonesa', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(29, 'Rosa', 'Martinez', 48, 2, '134455667', 'Calle de la Esperanza 303', 'Envío', 'Con pan de centeno', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(30, 'Gabriel', 'Gonzalez', 12, 0.5, '135566778', 'Av. Constitución 404', 'Retira', 'Sin queso', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(31, 'Beatriz', 'Fernandez', 24, 1, '136677889', 'Calle de los Árboles 505', 'Envío', 'Sin cebolla', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(32, 'Ricardo', 'Lopez', 48, 2, '137788990', 'Calle Real 606', 'Retira', 'Con extra zanahoria', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(33, 'Patricia', 'Diaz', 12, 0.5, '138899001', 'Calle del Sol 707', 'Envío', 'Sin pimienta', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(34, 'Vicente', 'Rodriguez', 24, 1, '139900112', 'Av. del Parque 808', 'Retira', 'Con salsa picante', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(35, 'Angela', 'Gomez', 48, 2, '140011223', 'Calle del Río 909', 'Envío', 'Con pan integral', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(36, 'Santiago', 'Hernandez', 12, 0.5, '141122334', 'Calle de la Paz 101', 'Retira', 'Sin tomates', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(37, 'Nancy', 'Gonzalez', 24, 1, '142233445', 'Calle de la Luna 202', 'Envío', 'Con cebolla extra', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(38, 'Julian', 'Lopez', 48, 2, '143344556', 'Av. de la Libertad 303', 'Retira', 'Con mayonesa extra', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(39, 'Esteban', 'Martinez', 12, 0.5, '144455667', 'Calle Real 404', 'Envío', 'Sin lechuga extra', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(40, 'Miriam', 'Rodriguez', 24, 1, '145566778', 'Calle de la Plaza 505', 'Retira', 'Sin mayonesa', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(41, 'Leonardo', 'Gomez', 48, 2, '146677889', 'Av. de la Costa 606', 'Envío', 'Con extra jamón', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(43, 'Ricardo', 'Fernandez', 24, 1, '148899001', 'Av. San Martin 808', 'Envío', 'Con extra cebolla', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(44, 'Alberto', 'Diaz', 48, 2, '149900112', 'Calle del Sur 909', 'Retira', 'Sin mostaza', '2025-04-15 15:01:52', '2025-04-15 12:01:52', 0, NULL, NULL, NULL, 'Pendiente'),
(46, 'enrique', 'osvaldo', 24, 1, '22116267575', '10 n°1564', 'Envío', 'sadas', '2025-04-15 18:40:02', '2025-04-15 15:40:02', 0, NULL, NULL, NULL, 'Pendiente'),
(49, 'maxi', 'osvaldo', 48, 2, '2214123', '10 n°1564', 'Cliente Fijo', 'sin cebolla', '2025-04-21 16:23:53', '2025-04-21 13:23:53', 0, NULL, NULL, NULL, 'Pendiente'),
(50, 'juan ', 'ignacioooo', 24, 1, '123123', '12123', 'Cliente Fijo', '1231', '2025-04-21 18:14:38', '2025-04-21 15:14:38', 0, NULL, NULL, NULL, 'Pendiente');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
