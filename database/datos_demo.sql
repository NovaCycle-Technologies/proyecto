-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: novacycle
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `novacycle`
--

/*!40000 DROP DATABASE IF EXISTS `novacycle`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `novacycle` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `novacycle`;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `ci` varchar(8) NOT NULL,
  PRIMARY KEY (`ci`),
  CONSTRAINT `fk_admins_usuario` FOREIGN KEY (`ci`) REFERENCES `usuarios` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES ('57736800');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignaciones_ruta`
--

DROP TABLE IF EXISTS `asignaciones_ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asignaciones_ruta` (
  `id_asignacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_ruta` int(11) NOT NULL,
  `id_camion` int(11) NOT NULL,
  `ci_conductor` varchar(20) NOT NULL,
  `ci_peon` varchar(20) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('asignada','en_curso','completada') NOT NULL DEFAULT 'asignada',
  PRIMARY KEY (`id_asignacion`),
  KEY `fk_asignacion_ruta` (`id_ruta`),
  KEY `fk_asignacion_camion` (`id_camion`),
  CONSTRAINT `fk_asignacion_camion` FOREIGN KEY (`id_camion`) REFERENCES `camiones` (`id_camion`),
  CONSTRAINT `fk_asignacion_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `rutas` (`id_ruta`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaciones_ruta`
--

LOCK TABLES `asignaciones_ruta` WRITE;
/*!40000 ALTER TABLE `asignaciones_ruta` DISABLE KEYS */;
INSERT INTO `asignaciones_ruta` VALUES (1,3,3,'57736802','57736830','2026-09-17','asignada'),(2,4,4,'55678095','1878982','2026-09-17','asignada'),(3,6,4,'57736802','57736830','2026-09-28','asignada'),(4,6,4,'57736802','57736830','2026-10-08','asignada'),(5,7,4,'57736802','57736830','2026-10-15','asignada'),(6,6,4,'55678095','57736801','2026-10-15','asignada'),(7,8,4,'57736802','57736830','2025-09-15','asignada');
/*!40000 ALTER TABLE `asignaciones_ruta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `camiones`
--

DROP TABLE IF EXISTS `camiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `camiones` (
  `id_camion` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(20) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `capacidad_kg` int(11) NOT NULL,
  `estado` enum('disponible','en_mantenimiento','fuera_de_servicio') NOT NULL DEFAULT 'disponible',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_camion`),
  UNIQUE KEY `matricula` (`matricula`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `camiones`
--

LOCK TABLES `camiones` WRITE;
/*!40000 ALTER TABLE `camiones` DISABLE KEYS */;
INSERT INTO `camiones` VALUES (1,'SAA 123','Iveco Tector',2000,'en_mantenimiento',0,'2026-08-10 16:19:25'),(3,'SAA 127','Iveco Tector',8000,'disponible',0,'2026-09-17 16:03:56'),(4,'SAA 122','Iveco Tector',8000,'en_mantenimiento',0,'2026-09-17 16:58:04'),(10,'SAA 128','Iveco Tector',7500,'disponible',0,'2026-10-27 14:03:20');
/*!40000 ALTER TABLE `camiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conductores`
--

DROP TABLE IF EXISTS `conductores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conductores` (
  `ci` varchar(8) NOT NULL,
  PRIMARY KEY (`ci`),
  CONSTRAINT `fk_conductores_usuario` FOREIGN KEY (`ci`) REFERENCES `usuarios` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conductores`
--

LOCK TABLES `conductores` WRITE;
/*!40000 ALTER TABLE `conductores` DISABLE KEYS */;
INSERT INTO `conductores` VALUES ('55678095'),('57736802');
/*!40000 ALTER TABLE `conductores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contenedores`
--

DROP TABLE IF EXISTS `contenedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contenedores` (
  `id_contenedor` int(11) NOT NULL AUTO_INCREMENT,
  `calle` varchar(100) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `estado` enum('funcional','roto','desbordado') NOT NULL DEFAULT 'funcional',
  `tipo_residuo` varchar(50) NOT NULL,
  `capacidad_litros` int(11) NOT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_contenedor`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contenedores`
--

LOCK TABLES `contenedores` WRITE;
/*!40000 ALTER TABLE `contenedores` DISABLE KEYS */;
INSERT INTO `contenedores` VALUES (1,'Bv.Artigas','1','funcional','Reciclaje',47,NULL,NULL,0,'2026-07-22 21:21:08'),(2,'Bv.Artigas','1','funcional','Reciclaje',10,NULL,NULL,0,'2026-07-23 16:51:59'),(3,'Bv.Artigas','3010','funcional','Reciclaje',50,NULL,NULL,0,'2026-07-23 17:23:04'),(4,'Bv.Artigas','3126','funcional','Reciclaje',10,NULL,NULL,0,'2026-07-23 17:53:19'),(5,'Av.Uruguay','1','funcional','Residuos comunes',1000,NULL,NULL,0,'2026-09-03 16:07:18'),(6,'Av.Uruguay','2','funcional','Reciclaje',1000,NULL,NULL,0,'2026-09-03 16:08:30'),(7,'Ejido','1467','roto','Residuos comunes',3000,NULL,NULL,0,'2026-09-17 16:00:34'),(8,'Ejido','1542','funcional','Residuos comunes',3000,-34.9019000,-56.1872000,1,'2026-09-17 16:01:25'),(9,'La Paz','danu','roto','Residuos comunes',1000,-34.8949000,-56.1921000,1,'2026-10-15 16:10:16');
/*!40000 ALTER TABLE `contenedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estados_maquinaria`
--

DROP TABLE IF EXISTS `estados_maquinaria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estados_maquinaria` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `ci_operario` varchar(20) NOT NULL,
  `maquinaria` varchar(100) NOT NULL,
  `estado` varchar(80) NOT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estados_maquinaria`
--

LOCK TABLES `estados_maquinaria` WRITE;
/*!40000 ALTER TABLE `estados_maquinaria` DISABLE KEYS */;
/*!40000 ALTER TABLE `estados_maquinaria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incidencias`
--

DROP TABLE IF EXISTS `incidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidencias` (
  `id_incidencia` int(11) NOT NULL AUTO_INCREMENT,
  `id_contenedor` int(11) DEFAULT NULL,
  `ci_reportante` varchar(20) NOT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `tipo` varchar(80) NOT NULL,
  `detalle` text NOT NULL,
  `estado` enum('pendiente','revisada','cerrada') NOT NULL DEFAULT 'pendiente',
  `fecha_reporte` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_revision` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  PRIMARY KEY (`id_incidencia`),
  KEY `fk_incidencia_contenedor` (`id_contenedor`),
  CONSTRAINT `fk_incidencia_contenedor` FOREIGN KEY (`id_contenedor`) REFERENCES `contenedores` (`id_contenedor`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incidencias`
--

LOCK TABLES `incidencias` WRITE;
/*!40000 ALTER TABLE `incidencias` DISABLE KEYS */;
INSERT INTO `incidencias` VALUES (1,NULL,'57736802','Av.Uruguay','Desborde','no se pudo recojer','cerrada','2026-09-03 16:40:04','2026-10-08 15:44:48','2026-10-08 15:44:51'),(6,8,'57736802','Ejido 1542','Desborde','tapa del contenedor rota','cerrada','2026-10-08 15:00:41','2026-10-08 15:44:39','2026-10-08 15:44:43'),(7,NULL,'57736830','Ejido 1542','No se pudo recolectar','Contenedor incendiado','pendiente','2026-10-11 09:53:49',NULL,NULL),(8,NULL,'57736830','Ejido 1542','Contenedor roto','roto','pendiente','2026-10-11 10:17:45',NULL,NULL),(9,NULL,'57736830','Av.Uruguay','Contenedor roto','AS','pendiente','2026-10-11 10:27:43',NULL,NULL);
/*!40000 ALTER TABLE `incidencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos_residuos`
--

DROP TABLE IF EXISTS `ingresos_residuos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ingresos_residuos` (
  `id_ingreso` int(11) NOT NULL AUTO_INCREMENT,
  `id_camion` int(11) NOT NULL,
  `ci_operario` varchar(20) NOT NULL,
  `tipo_residuo` varchar(80) NOT NULL,
  `peso_kg` int(11) NOT NULL,
  `fecha_ingreso` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_ingreso`),
  KEY `fk_ingreso_camion` (`id_camion`),
  CONSTRAINT `fk_ingreso_camion` FOREIGN KEY (`id_camion`) REFERENCES `camiones` (`id_camion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos_residuos`
--

LOCK TABLES `ingresos_residuos` WRITE;
/*!40000 ALTER TABLE `ingresos_residuos` DISABLE KEYS */;
/*!40000 ALTER TABLE `ingresos_residuos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operarios`
--

DROP TABLE IF EXISTS `operarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operarios` (
  `ci` varchar(8) NOT NULL,
  PRIMARY KEY (`ci`),
  CONSTRAINT `fk_operarios_usuario` FOREIGN KEY (`ci`) REFERENCES `usuarios` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operarios`
--

LOCK TABLES `operarios` WRITE;
/*!40000 ALTER TABLE `operarios` DISABLE KEYS */;
INSERT INTO `operarios` VALUES ('54368908');
/*!40000 ALTER TABLE `operarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paradas_ruta`
--

DROP TABLE IF EXISTS `paradas_ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paradas_ruta` (
  `id_parada` int(11) NOT NULL AUTO_INCREMENT,
  `id_ruta` int(11) NOT NULL,
  `id_contenedor` int(11) DEFAULT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `descripcion` varchar(150) NOT NULL,
  `orden` int(11) NOT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`id_parada`),
  UNIQUE KEY `unica_parada_contenedor` (`id_ruta`,`id_contenedor`),
  KEY `fk_parada_contenedor` (`id_contenedor`),
  CONSTRAINT `fk_parada_contenedor` FOREIGN KEY (`id_contenedor`) REFERENCES `contenedores` (`id_contenedor`),
  CONSTRAINT `fk_parada_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `rutas` (`id_ruta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paradas_ruta`
--

LOCK TABLES `paradas_ruta` WRITE;
/*!40000 ALTER TABLE `paradas_ruta` DISABLE KEYS */;
INSERT INTO `paradas_ruta` VALUES (2,3,NULL,'Ejido 1450','Contenedores',1,-34.9037000,-56.1869000),(3,3,8,'Ejido 1542','Contenedores',1,-34.9019000,-56.1872000),(4,4,NULL,'Mercedes 1202','Contenedores',1,-34.9039000,-56.1900000),(5,4,NULL,'Mercedes 1250','Contenedores',1,-34.9038000,-56.1896000),(6,6,8,'Ejido 1542','Residuos comunes · 3000 L',1,-34.9019000,-56.1872000),(7,7,8,'Ejido 1542','Residuos comunes · 3000 L',1,-34.9019000,-56.1872000),(8,7,9,'La Paz danu','Residuos comunes · 1000 L',2,-34.8949000,-56.1921000),(9,8,8,'Ejido 1542','Residuos comunes · 3000 L',1,-34.9019000,-56.1872000);
/*!40000 ALTER TABLE `paradas_ruta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `peones`
--

DROP TABLE IF EXISTS `peones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `peones` (
  `ci` varchar(8) NOT NULL,
  PRIMARY KEY (`ci`),
  CONSTRAINT `fk_peones_usuario` FOREIGN KEY (`ci`) REFERENCES `usuarios` (`CI`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `peones`
--

LOCK TABLES `peones` WRITE;
/*!40000 ALTER TABLE `peones` DISABLE KEYS */;
INSERT INTO `peones` VALUES ('12345678'),('1878982'),('57736801'),('57736803'),('57736808'),('57736830');
/*!40000 ALTER TABLE `peones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recolecciones`
--

DROP TABLE IF EXISTS `recolecciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recolecciones` (
  `id_recoleccion` int(11) NOT NULL AUTO_INCREMENT,
  `id_asignacion` int(11) NOT NULL,
  `id_parada` int(11) NOT NULL,
  `ci_trabajador` varchar(20) NOT NULL,
  `fecha_recoleccion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_recoleccion`),
  UNIQUE KEY `unica_recoleccion` (`id_asignacion`,`id_parada`),
  KEY `fk_recoleccion_parada` (`id_parada`),
  CONSTRAINT `fk_recoleccion_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignaciones_ruta` (`id_asignacion`),
  CONSTRAINT `fk_recoleccion_parada` FOREIGN KEY (`id_parada`) REFERENCES `paradas_ruta` (`id_parada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recolecciones`
--

LOCK TABLES `recolecciones` WRITE;
/*!40000 ALTER TABLE `recolecciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `recolecciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rutas`
--

DROP TABLE IF EXISTS `rutas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rutas` (
  `id_ruta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `zona` varchar(100) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_ruta`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rutas`
--

LOCK TABLES `rutas` WRITE;
/*!40000 ALTER TABLE `rutas` DISABLE KEYS */;
INSERT INTO `rutas` VALUES (1,'Ruta Centro 01','Centro',0,'2026-08-27 17:04:46'),(2,'Ruta Centro 01','Centro',0,'2026-08-27 17:04:50'),(3,'Ruta Centro 02','Centro',0,'2026-09-17 15:51:37'),(4,'Ruta Jorge','Centro',0,'2026-09-17 16:58:26'),(6,'Ruta Centro 01','Centro',1,'2026-09-28 16:44:49'),(7,'Ruta Jorge','Centro',1,'2026-10-15 16:10:47'),(8,'ruta jorge2','Centro',1,'2026-10-15 16:13:37');
/*!40000 ALTER TABLE `rutas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `Nombre` varchar(30) NOT NULL,
  `Apellido` varchar(30) NOT NULL,
  `CI` varchar(8) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estado_cuenta` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `rol_solicitado` enum('peon','conductor','operario') NOT NULL DEFAULT 'peon',
  PRIMARY KEY (`CI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES ('Martin','Caceres','12345678','martin@gmail.com','$2y$10$DMZrmYmHVSxFKtKp6eMH2O6KyXADMbQ3al8ulk77dDIRH3I4o05Pi','aprobado','peon'),('Jorge','Alvarez','1878982','jorge@gmail.com','$2y$10$wSNst4k8E7z4eQmL1cfF8ecWBoi/V8WKoRJqfOpWuBCtBBV.h/S5C','aprobado','peon'),('lautaro','silva','54368908','lau@gmail.com','$2y$10$2fe3WnlaFKaeP367DBUjGuHn2n2Rephtsv5WDmJSyxDz/ellBp33.','aprobado','operario'),('unai','simon','55678095','unai@gmail.com','$2y$10$hB.4cM4tCknh9DIKezeLAOZKs47ytLIBQ0vcVmFMsPRfdPGFLo5eK','aprobado','conductor'),('Joaquin','Pereira','57736800','joaquinnotre2008@gmail.com','$2y$10$gxhEX7/NoLtUZ1tbGONRle9stdkoM7MxkfYY1cafGnWsVKB6.9tWi','aprobado','peon'),('federico','DelValle','57736801','fede@gmail.com','$2y$10$cf5XuixRh8o7OWJsLPthzeeIJIbeyYexSM02SbG.tDPRTSdTfQpA6','aprobado','peon'),('Bruno','Acosta','57736802','bruno@gmail.com','$2y$10$zl0W.XuyyAdTNC0JZThF3OhoppPXXI5sR7wITZXnyh04h0rCbegVC','aprobado','conductor'),('ana','frank','57736803','ana@gmail.com','$2y$10$dz6frfiGsbPNM6hBf2hx.e6CNnuU7ynqeRg/Sixl4/bcGx/VEs1NK','aprobado','peon'),('jorge','jorge','57736808','jorge@gmail.com','$2y$10$NcPD.UVQbHnY.mIYcq5y/exrko.V3JDzmjIUNw.PUUdF.wFBB0sT6','aprobado','peon'),('alejandro','DelValle','57736830','ale@gmail.com','$2y$10$e5U2YiIlOd4mkmGzjUPD6eeIcGxRI8Hy7ks91gFwYC1Awb.skMIOK','aprobado','peon');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'novacycle'
--

--
-- Dumping routines for database 'novacycle'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 14:43:53
