-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-09-2026 a las 22:57:41
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
-- Base de datos: `novacycle`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admins`
--

CREATE TABLE `admins` (
  `ci` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admins`
--

INSERT INTO `admins` (`ci`) VALUES
('57736800');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_ruta`
--

CREATE TABLE `asignaciones_ruta` (
  `id_asignacion` int(11) NOT NULL,
  `id_ruta` int(11) NOT NULL,
  `id_camion` int(11) NOT NULL,
  `ci_conductor` varchar(20) NOT NULL,
  `ci_peon` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('asignada','en_curso','completada') NOT NULL DEFAULT 'asignada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camiones`
--

CREATE TABLE `camiones` (
  `id_camion` int(11) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `capacidad_kg` int(11) NOT NULL,
  `estado` enum('disponible','en_mantenimiento','fuera_de_servicio') NOT NULL DEFAULT 'disponible',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `camiones`
--

INSERT INTO `camiones` (`id_camion`, `matricula`, `modelo`, `capacidad_kg`, `estado`, `activo`, `fecha_alta`) VALUES
(1, 'SAA 123', 'Iveco Tector', 2000, 'en_mantenimiento', 1, '2026-08-10 16:19:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conductores`
--

CREATE TABLE `conductores` (
  `ci` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `conductores`
--

INSERT INTO `conductores` (`ci`) VALUES
('55678095'),
('57736802'),
('57736808');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedores`
--

CREATE TABLE `contenedores` (
  `id_contenedor` int(11) NOT NULL,
  `calle` varchar(100) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `estado` enum('funcional','roto','desbordado') NOT NULL DEFAULT 'funcional',
  `tipo_residuo` varchar(50) NOT NULL,
  `capacidad_litros` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contenedores`
--

INSERT INTO `contenedores` (`id_contenedor`, `calle`, `numero`, `estado`, `tipo_residuo`, `capacidad_litros`, `activo`, `fecha_alta`) VALUES
(1, 'Bv.Artigas', '1', 'funcional', 'Reciclaje', 47, 0, '2026-07-22 21:21:08'),
(2, 'Bv.Artigas', '1', 'funcional', 'Reciclaje', 10, 0, '2026-07-23 16:51:59'),
(3, 'Bv.Artigas', '3010', 'funcional', 'Reciclaje', 50, 0, '2026-07-23 17:23:04'),
(4, 'Bv.Artigas', '3126', 'funcional', 'Reciclaje', 10, 0, '2026-07-23 17:53:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_maquinaria`
--

CREATE TABLE `estados_maquinaria` (
  `id_estado` int(11) NOT NULL,
  `ci_operario` varchar(20) NOT NULL,
  `maquinaria` varchar(100) NOT NULL,
  `estado` varchar(80) NOT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

CREATE TABLE `incidencias` (
  `id_incidencia` int(11) NOT NULL,
  `ci_reportante` varchar(20) NOT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `tipo` varchar(80) NOT NULL,
  `detalle` text NOT NULL,
  `estado` enum('pendiente','revisada') NOT NULL DEFAULT 'pendiente',
  `fecha_reporte` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_revision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos_residuos`
--

CREATE TABLE `ingresos_residuos` (
  `id_ingreso` int(11) NOT NULL,
  `id_camion` int(11) NOT NULL,
  `ci_operario` varchar(20) NOT NULL,
  `tipo_residuo` varchar(80) NOT NULL,
  `peso_kg` int(11) NOT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operarios`
--

CREATE TABLE `operarios` (
  `ci` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `operarios`
--

INSERT INTO `operarios` (`ci`) VALUES
('12345678'),
('54368908'),
('55678095'),
('57736801'),
('57736802'),
('57736803'),
('57736808'),
('57736820'),
('57736830');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paradas_ruta`
--

CREATE TABLE `paradas_ruta` (
  `id_parada` int(11) NOT NULL,
  `id_ruta` int(11) NOT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `orden` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peones`
--

CREATE TABLE `peones` (
  `ci` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `peones`
--

INSERT INTO `peones` (`ci`) VALUES
('12345678'),
('57736801'),
('57736803'),
('57736820');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recolecciones`
--

CREATE TABLE `recolecciones` (
  `id_recoleccion` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `id_parada` int(11) NOT NULL,
  `ci_trabajador` varchar(20) NOT NULL,
  `fecha_recoleccion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutas`
--

CREATE TABLE `rutas` (
  `id_ruta` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `zona` varchar(100) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rutas`
--

INSERT INTO `rutas` (`id_ruta`, `nombre`, `zona`, `activa`, `fecha_alta`) VALUES
(1, 'Ruta Centro 01', 'Centro', 1, '2026-08-27 17:04:46'),
(2, 'Ruta Centro 01', 'Centro', 1, '2026-08-27 17:04:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `Nombre` varchar(30) NOT NULL,
  `Apellido` varchar(30) NOT NULL,
  `CI` varchar(8) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estado_cuenta` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `rol_solicitado` enum('peon','conductor') NOT NULL DEFAULT 'peon'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`Nombre`, `Apellido`, `CI`, `Email`, `password`, `estado_cuenta`, `rol_solicitado`) VALUES
('Martin', 'Caceres', '12345678', 'martin@gmail.com', '$2y$10$DMZrmYmHVSxFKtKp6eMH2O6KyXADMbQ3al8ulk77dDIRH3I4o05Pi', 'aprobado', 'peon'),
('lautaro', 'silva', '54368908', 'lau@gmail.com', '$2y$10$2fe3WnlaFKaeP367DBUjGuHn2n2Rephtsv5WDmJSyxDz/ellBp33.', 'aprobado', ''),
('unai', 'simon', '55678095', 'unai@gmail.com', '$2y$10$hB.4cM4tCknh9DIKezeLAOZKs47ytLIBQ0vcVmFMsPRfdPGFLo5eK', 'aprobado', 'conductor'),
('Joaquin', 'Pereira', '57736800', 'joaquinnotre2008@gmail.com', '$2y$10$gxhEX7/NoLtUZ1tbGONRle9stdkoM7MxkfYY1cafGnWsVKB6.9tWi', 'aprobado', 'peon'),
('federico', 'DelValle', '57736801', 'fede@gmail.com', '$2y$10$cf5XuixRh8o7OWJsLPthzeeIJIbeyYexSM02SbG.tDPRTSdTfQpA6', 'aprobado', 'peon'),
('Bruno', 'Acosta', '57736802', 'bruno@gmail.com', '$2y$10$zl0W.XuyyAdTNC0JZThF3OhoppPXXI5sR7wITZXnyh04h0rCbegVC', 'aprobado', 'conductor'),
('ana', 'frank', '57736803', 'ana@gmail.com', '$2y$10$dz6frfiGsbPNM6hBf2hx.e6CNnuU7ynqeRg/Sixl4/bcGx/VEs1NK', 'aprobado', 'peon'),
('jorge', 'jorge', '57736808', 'jorge@gmail.com', '$2y$10$NcPD.UVQbHnY.mIYcq5y/exrko.V3JDzmjIUNw.PUUdF.wFBB0sT6', 'aprobado', 'peon'),
('Peron', 'Pereira', '57736820', 'pe@gmail.com', '$2y$10$P/WIVyj4trx7dpmBpufWmuiEfB0oZLxnCCyozBTRvfIirhkl6uuSq', 'aprobado', 'peon'),
('alejandro', 'DelValle', '57736830', 'ale@gmail.com', '$2y$10$e5U2YiIlOd4mkmGzjUPD6eeIcGxRI8Hy7ks91gFwYC1Awb.skMIOK', 'aprobado', 'peon');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`ci`);

--
-- Indices de la tabla `asignaciones_ruta`
--
ALTER TABLE `asignaciones_ruta`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `fk_asignacion_ruta` (`id_ruta`),
  ADD KEY `fk_asignacion_camion` (`id_camion`);

--
-- Indices de la tabla `camiones`
--
ALTER TABLE `camiones`
  ADD PRIMARY KEY (`id_camion`),
  ADD UNIQUE KEY `matricula` (`matricula`);

--
-- Indices de la tabla `conductores`
--
ALTER TABLE `conductores`
  ADD PRIMARY KEY (`ci`);

--
-- Indices de la tabla `contenedores`
--
ALTER TABLE `contenedores`
  ADD PRIMARY KEY (`id_contenedor`);

--
-- Indices de la tabla `estados_maquinaria`
--
ALTER TABLE `estados_maquinaria`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id_incidencia`);

--
-- Indices de la tabla `ingresos_residuos`
--
ALTER TABLE `ingresos_residuos`
  ADD PRIMARY KEY (`id_ingreso`),
  ADD KEY `fk_ingreso_camion` (`id_camion`);

--
-- Indices de la tabla `operarios`
--
ALTER TABLE `operarios`
  ADD PRIMARY KEY (`ci`);

--
-- Indices de la tabla `paradas_ruta`
--
ALTER TABLE `paradas_ruta`
  ADD PRIMARY KEY (`id_parada`),
  ADD KEY `fk_parada_ruta` (`id_ruta`);

--
-- Indices de la tabla `peones`
--
ALTER TABLE `peones`
  ADD PRIMARY KEY (`ci`);

--
-- Indices de la tabla `recolecciones`
--
ALTER TABLE `recolecciones`
  ADD PRIMARY KEY (`id_recoleccion`),
  ADD UNIQUE KEY `unica_recoleccion` (`id_asignacion`,`id_parada`),
  ADD KEY `fk_recoleccion_parada` (`id_parada`);

--
-- Indices de la tabla `rutas`
--
ALTER TABLE `rutas`
  ADD PRIMARY KEY (`id_ruta`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`CI`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaciones_ruta`
--
ALTER TABLE `asignaciones_ruta`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `camiones`
--
ALTER TABLE `camiones`
  MODIFY `id_camion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `contenedores`
--
ALTER TABLE `contenedores`
  MODIFY `id_contenedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estados_maquinaria`
--
ALTER TABLE `estados_maquinaria`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id_incidencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ingresos_residuos`
--
ALTER TABLE `ingresos_residuos`
  MODIFY `id_ingreso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paradas_ruta`
--
ALTER TABLE `paradas_ruta`
  MODIFY `id_parada` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recolecciones`
--
ALTER TABLE `recolecciones`
  MODIFY `id_recoleccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rutas`
--
ALTER TABLE `rutas`
  MODIFY `id_ruta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `fk_admins_usuario` FOREIGN KEY (`ci`) REFERENCES `usuarios` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignaciones_ruta`
--
ALTER TABLE `asignaciones_ruta`
  ADD CONSTRAINT `fk_asignacion_camion` FOREIGN KEY (`id_camion`) REFERENCES `camiones` (`id_camion`),
  ADD CONSTRAINT `fk_asignacion_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `rutas` (`id_ruta`);

--
-- Filtros para la tabla `conductores`
--
ALTER TABLE `conductores`
  ADD CONSTRAINT `fk_conductores_operario` FOREIGN KEY (`ci`) REFERENCES `operarios` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `ingresos_residuos`
--
ALTER TABLE `ingresos_residuos`
  ADD CONSTRAINT `fk_ingreso_camion` FOREIGN KEY (`id_camion`) REFERENCES `camiones` (`id_camion`);

--
-- Filtros para la tabla `operarios`
--
ALTER TABLE `operarios`
  ADD CONSTRAINT `fk_operarios_usuario` FOREIGN KEY (`ci`) REFERENCES `usuarios` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `paradas_ruta`
--
ALTER TABLE `paradas_ruta`
  ADD CONSTRAINT `fk_parada_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `rutas` (`id_ruta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `peones`
--
ALTER TABLE `peones`
  ADD CONSTRAINT `fk_peones_operario` FOREIGN KEY (`ci`) REFERENCES `operarios` (`ci`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `recolecciones`
--
ALTER TABLE `recolecciones`
  ADD CONSTRAINT `fk_recoleccion_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignaciones_ruta` (`id_asignacion`),
  ADD CONSTRAINT `fk_recoleccion_parada` FOREIGN KEY (`id_parada`) REFERENCES `paradas_ruta` (`id_parada`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
