-- ===================================================================
-- StockiaPro · Esquema y datos iniciales
-- Base de datos: stockiapro  (MySQL 8 / MariaDB 10.4+)
--
-- Importar:
--   mysql -u root -p < database/stockiapro.sql
--   o desde phpMyAdmin > Importar
--
-- Credenciales de prueba (cambiar en producción):
--   Usuario: demo        (también demo@stockiapro.local)
--   Contraseña: Demo1234!
--   Rol: Administrador Demo · Empresa: Demo StockiaPro
-- ===================================================================
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: stockiapro
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
-- Current Database: `stockiapro`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `stockiapro` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `stockiapro`;

--
-- Table structure for table `almacenes`
--

DROP TABLE IF EXISTS `almacenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `almacenes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `sucursal_id` int(10) unsigned NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_almacen_empresa_codigo` (`empresa_id`,`codigo`),
  KEY `idx_almacen_sucursal` (`sucursal_id`),
  CONSTRAINT `fk_almacen_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_almacen_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `almacenes`
--

LOCK TABLES `almacenes` WRITE;
/*!40000 ALTER TABLE `almacenes` DISABLE KEYS */;
INSERT INTO `almacenes` VALUES (1,1,1,'Almacén Principal','ALM-01','activo','2026-11-06 22:34:17','2026-11-06 22:34:17',NULL);
/*!40000 ALTER TABLE `almacenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_empresa`
--

DROP TABLE IF EXISTS `configuracion_empresa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion_empresa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `identificacion_fiscal` varchar(50) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `favicon_url` varchar(255) DEFAULT NULL,
  `portada_url` varchar(255) DEFAULT NULL,
  `descripcion_comercial` text DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `email_contacto` varchar(150) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(80) DEFAULT NULL,
  `pais` varchar(80) DEFAULT NULL,
  `sitio_web` varchar(150) DEFAULT NULL,
  `moneda_principal` varchar(50) NOT NULL DEFAULT 'Dólar estadounidense',
  `codigo_moneda` char(3) NOT NULL DEFAULT 'USD',
  `simbolo_moneda` varchar(5) NOT NULL DEFAULT '$',
  `decimales` tinyint(4) NOT NULL DEFAULT 2,
  `separador_decimal` char(1) NOT NULL DEFAULT '.',
  `separador_miles` char(1) NOT NULL DEFAULT ',',
  `impuesto_nombre` varchar(50) DEFAULT 'IVA',
  `impuesto_porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `precios_con_impuesto` tinyint(1) NOT NULL DEFAULT 0,
  `comprobante_tipo` varchar(50) DEFAULT NULL,
  `datos_facturacion` text DEFAULT NULL,
  `tienda_nombre` varchar(150) DEFAULT NULL,
  `tienda_slug` varchar(100) DEFAULT NULL,
  `tienda_logo_url` varchar(255) DEFAULT NULL,
  `tienda_color_primario` varchar(7) DEFAULT '#0d6efd',
  `tienda_color_secundario` varchar(7) DEFAULT '#6c757d',
  `tienda_descripcion` text DEFAULT NULL,
  `tienda_contacto` varchar(255) DEFAULT NULL,
  `tienda_publicada` tinyint(1) NOT NULL DEFAULT 0,
  `zona_horaria` varchar(60) NOT NULL DEFAULT 'America/Mexico_City',
  `idioma` varchar(10) NOT NULL DEFAULT 'es',
  `formato_fecha` varchar(20) NOT NULL DEFAULT 'd/m/Y',
  `formato_hora` varchar(20) NOT NULL DEFAULT 'H:i',
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_empresa` (`empresa_id`),
  UNIQUE KEY `uk_tienda_slug` (`tienda_slug`),
  CONSTRAINT `fk_config_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_empresa`
--

LOCK TABLES `configuracion_empresa` WRITE;
/*!40000 ALTER TABLE `configuracion_empresa` DISABLE KEYS */;
INSERT INTO `configuracion_empresa` VALUES (1,1,'Demo StockiaPro',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'demo@stockiapro.local',NULL,NULL,NULL,NULL,'Dólar estadounidense','USD','$',2,'.',',','IVA',0.00,0,NULL,NULL,NULL,NULL,NULL,'#0d6efd','#6c757d',NULL,NULL,0,'America/Mexico_City','es','d/m/Y','H:i','activa','2026-11-06 22:34:17','2026-11-06 22:34:17');
/*!40000 ALTER TABLE `configuracion_empresa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuraciones_sistema`
--

DROP TABLE IF EXISTS `configuraciones_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuraciones_sistema` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('string','int','bool','json','decimal') NOT NULL DEFAULT 'string',
  `descripcion` varchar(255) DEFAULT NULL,
  `es_publica` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_clave` (`clave`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuraciones_sistema`
--

LOCK TABLES `configuraciones_sistema` WRITE;
/*!40000 ALTER TABLE `configuraciones_sistema` DISABLE KEYS */;
INSERT INTO `configuraciones_sistema` VALUES (1,'sesion_duracion_horas','4','int','Duración máxima de sesión en horas',0,'2026-11-06 22:34:17'),(2,'login_max_intentos','5','int','Intentos fallidos antes de bloqueo',0,'2026-11-06 22:34:17'),(3,'login_bloqueo_minutos','15','int','Minutos de bloqueo temporal',0,'2026-11-06 22:34:17'),(4,'app_nombre','StockiaPro','string','Nombre del SaaS',1,'2026-11-06 22:34:17');
/*!40000 ALTER TABLE `configuraciones_sistema` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empresas`
--

DROP TABLE IF EXISTS `empresas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empresas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_comercial` varchar(150) NOT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `identificacion_fiscal` varchar(50) DEFAULT NULL,
  `email_contacto` varchar(150) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('activa','suspendida','cancelada','prueba') NOT NULL DEFAULT 'prueba',
  `tipo_licencia` enum('suscripcion','licencia_unica') NOT NULL DEFAULT 'suscripcion',
  `fecha_activacion` datetime DEFAULT NULL,
  `fecha_vencimiento_licencia` datetime DEFAULT NULL COMMENT 'NULL si licencia perpetua',
  `es_saas_owner` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = empresa dueña del SaaS',
  `creado_por` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresa_email` (`email_contacto`),
  KEY `idx_empresa_estado` (`estado`),
  KEY `idx_empresa_licencia` (`tipo_licencia`,`fecha_vencimiento_licencia`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empresas`
--

LOCK TABLES `empresas` WRITE;
/*!40000 ALTER TABLE `empresas` DISABLE KEYS */;
INSERT INTO `empresas` VALUES (1,'Demo StockiaPro',NULL,NULL,'demo@stockiapro.local',NULL,'activa','suscripcion','2026-11-06 22:34:17',NULL,0,NULL,'2026-11-06 22:34:17','2026-11-06 22:34:17',NULL);
/*!40000 ALTER TABLE `empresas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intentos_login`
--

DROP TABLE IF EXISTS `intentos_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intentos_login` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `identificador` varchar(150) NOT NULL COMMENT 'usuario o email intentado',
  `empresa_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `resultado` enum('exitoso','fallido','bloqueado') NOT NULL,
  `motivo_rechazo` varchar(150) DEFAULT NULL COMMENT 'password_invalido, usuario_inactivo, empresa_suspendida, suscripcion_vencida, bloqueado_temporal',
  `fecha_intento` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_intento_identificador_ip` (`identificador`,`ip_address`,`fecha_intento`),
  KEY `idx_intento_fecha` (`fecha_intento`),
  KEY `idx_intento_resultado` (`resultado`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intentos_login`
--

LOCK TABLES `intentos_login` WRITE;
/*!40000 ALTER TABLE `intentos_login` DISABLE KEYS */;
/*!40000 ALTER TABLE `intentos_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `suscripcion_id` int(10) unsigned DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'USD',
  `impuesto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','tarjeta','pasarela','otro') NOT NULL DEFAULT 'otro',
  `referencia_pasarela` varchar(150) DEFAULT NULL,
  `comprobante_url` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado','anulado','reembolsado') NOT NULL DEFAULT 'pendiente',
  `fecha_pago` datetime DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `creado_por` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pago_empresa_estado` (`empresa_id`,`estado`),
  KEY `fk_pago_suscripcion` (`suscripcion_id`),
  CONSTRAINT `fk_pago_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pago_suscripcion` FOREIGN KEY (`suscripcion_id`) REFERENCES `suscripciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permisos`
--

DROP TABLE IF EXISTS `permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permisos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL COMMENT 'ej: usuarios.crear',
  `modulo` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permiso_clave` (`clave`),
  KEY `idx_permiso_modulo` (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permisos`
--

LOCK TABLES `permisos` WRITE;
/*!40000 ALTER TABLE `permisos` DISABLE KEYS */;
/*!40000 ALTER TABLE `permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planes`
--

DROP TABLE IF EXISTS `planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('suscripcion','licencia_unica') NOT NULL DEFAULT 'suscripcion',
  `periodicidad` enum('mensual','anual','unico') NOT NULL DEFAULT 'mensual',
  `precio` decimal(12,2) NOT NULL DEFAULT 0.00,
  `moneda` char(3) NOT NULL DEFAULT 'USD',
  `limite_usuarios` int(11) NOT NULL DEFAULT 1 COMMENT '-1 = ilimitado',
  `limite_productos` int(11) NOT NULL DEFAULT 100 COMMENT '-1 = ilimitado',
  `limite_sucursales` int(11) NOT NULL DEFAULT 1 COMMENT '-1 = ilimitado',
  `limite_almacenes` int(11) NOT NULL DEFAULT 1 COMMENT '-1 = ilimitado',
  `es_licencia_perpetua` tinyint(1) NOT NULL DEFAULT 0,
  `caracteristicas_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`caracteristicas_json`)),
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `orden` smallint(6) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_slug` (`slug`),
  KEY `idx_plan_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planes`
--

LOCK TABLES `planes` WRITE;
/*!40000 ALTER TABLE `planes` DISABLE KEYS */;
INSERT INTO `planes` VALUES (1,'Inicio','inicio',NULL,'suscripcion','mensual',9.99,'USD',3,200,1,1,0,'{\"tienda_basica\":true,\"reportes_basicos\":true}','activo',0,'2026-11-06 22:34:17','2026-11-06 22:34:17'),(2,'Profesional','profesional',NULL,'suscripcion','mensual',29.99,'USD',10,2000,3,5,0,'{\"tienda_personalizada\":true,\"compras\":true}','activo',0,'2026-11-06 22:34:17','2026-11-06 22:34:17'),(3,'Empresarial','empresarial',NULL,'suscripcion','anual',299.99,'USD',50,10000,-1,-1,0,'{\"multi_sucursal\":true,\"reportes_avanzados\":true}','activo',0,'2026-11-06 22:34:17','2026-11-06 22:34:17'),(4,'Licencia Perpetua','licencia-perpetua',NULL,'licencia_unica','unico',999.00,'USD',-1,-1,-1,-1,1,'{\"todo_incluido\":true}','activo',0,'2026-11-06 22:34:17','2026-11-06 22:34:17');
/*!40000 ALTER TABLE `planes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rol_permisos`
--

DROP TABLE IF EXISTS `rol_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol_permisos` (
  `rol_id` int(10) unsigned NOT NULL,
  `permiso_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`rol_id`,`permiso_id`),
  KEY `idx_rp_permiso` (`permiso_id`),
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rol_permisos`
--

LOCK TABLES `rol_permisos` WRITE;
/*!40000 ALTER TABLE `rol_permisos` DISABLE KEYS */;
/*!40000 ALTER TABLE `rol_permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = rol global del sistema',
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `es_sistema` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = rol base no editable',
  `nivel` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Mayor = más privilegios',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rol_empresa_slug` (`empresa_id`,`slug`),
  KEY `idx_rol_empresa` (`empresa_id`),
  CONSTRAINT `fk_rol_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,NULL,'Superadministrador','superadmin',NULL,1,100,'activo','2026-11-06 22:34:17','2026-11-06 22:34:17'),(2,NULL,'Administrador','admin',NULL,1,90,'activo','2026-11-06 22:34:17','2026-11-06 22:34:17'),(3,1,'Administrador Demo','admin-demo',NULL,0,90,'activo','2026-11-06 22:34:17','2026-11-06 22:34:17');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones_usuarios`
--

DROP TABLE IF EXISTS `sesiones_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sesiones_usuarios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `empresa_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 del token; nunca el token plano',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `fecha_inicio` datetime NOT NULL DEFAULT current_timestamp(),
  `ultima_actividad` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` datetime NOT NULL COMMENT 'Inicio + 4h (absoluta)',
  `revocada` tinyint(1) NOT NULL DEFAULT 0,
  `motivo_revocacion` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sesion_token` (`token_hash`),
  KEY `idx_sesion_usuario` (`usuario_id`),
  KEY `idx_sesion_expiracion` (`fecha_expiracion`),
  KEY `fk_sesion_empresa` (`empresa_id`),
  CONSTRAINT `fk_sesion_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones_usuarios`
--

LOCK TABLES `sesiones_usuarios` WRITE;
/*!40000 ALTER TABLE `sesiones_usuarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `sesiones_usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sucursales`
--

DROP TABLE IF EXISTS `sucursales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sucursales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sucursal_empresa_codigo` (`empresa_id`,`codigo`),
  KEY `idx_sucursal_empresa` (`empresa_id`),
  CONSTRAINT `fk_sucursal_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sucursales`
--

LOCK TABLES `sucursales` WRITE;
/*!40000 ALTER TABLE `sucursales` DISABLE KEYS */;
INSERT INTO `sucursales` VALUES (1,1,'Sucursal Central','SUC-01',NULL,NULL,'activa','2026-11-06 22:34:17','2026-11-06 22:34:17',NULL);
/*!40000 ALTER TABLE `sucursales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suscripciones`
--

DROP TABLE IF EXISTS `suscripciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suscripciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `plan_id` int(10) unsigned NOT NULL,
  `tipo_plan` enum('suscripcion','licencia_unica') NOT NULL DEFAULT 'suscripcion',
  `estado` enum('pendiente','activa','vencida','suspendida','cancelada','prueba') NOT NULL DEFAULT 'pendiente',
  `fecha_inicio` datetime NOT NULL,
  `fecha_vencimiento` datetime DEFAULT NULL COMMENT 'NULL = perpetua',
  `dias_gracia` smallint(6) NOT NULL DEFAULT 0,
  `renovacion_automatica` tinyint(1) NOT NULL DEFAULT 0,
  `referencia_externa` varchar(150) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `creado_por` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sus_empresa_estado` (`empresa_id`,`estado`),
  KEY `idx_sus_vencimiento` (`fecha_vencimiento`),
  KEY `fk_sus_plan` (`plan_id`),
  CONSTRAINT `fk_sus_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sus_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suscripciones`
--

LOCK TABLES `suscripciones` WRITE;
/*!40000 ALTER TABLE `suscripciones` DISABLE KEYS */;
INSERT INTO `suscripciones` VALUES (1,1,2,'suscripcion','activa','2026-11-06 22:34:17','2027-11-06 22:34:17',3,0,NULL,NULL,NULL,'2026-11-06 22:34:17','2026-11-06 22:34:17');
/*!40000 ALTER TABLE `suscripciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `empresa_id` int(10) unsigned NOT NULL,
  `sucursal_id` int(10) unsigned DEFAULT NULL,
  `rol_id` int(10) unsigned NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `usuario` varchar(60) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'password_hash() BCRYPT/ARGON2ID',
  `token_sesion_hash` char(64) DEFAULT NULL COMMENT 'SHA-256 del token actual',
  `token_expira_at` datetime DEFAULT NULL COMMENT 'Expiración absoluta 4h',
  `requiere_cambio_password` tinyint(1) NOT NULL DEFAULT 0,
  `intentos_fallidos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bloqueado_hasta` datetime DEFAULT NULL,
  `email_verificado_at` datetime DEFAULT NULL,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_habilitado` tinyint(1) NOT NULL DEFAULT 0,
  `estado` enum('activo','inactivo','bloqueado') NOT NULL DEFAULT 'activo',
  `ultimo_acceso` datetime DEFAULT NULL,
  `ultimo_acceso_ip` varchar(45) DEFAULT NULL COMMENT 'IPv6 ready',
  `creado_por` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_empresa_usuario` (`empresa_id`,`usuario`),
  UNIQUE KEY `uk_usuario_empresa_email` (`empresa_id`,`email`),
  KEY `idx_usuario_rol` (`rol_id`),
  KEY `idx_usuario_sucursal` (`sucursal_id`),
  KEY `idx_usuario_estado` (`estado`),
  KEY `idx_usuario_token` (`token_sesion_hash`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,1,1,3,'Usuario Demo','demo','demo@stockiapro.local','$argon2id$v=19$m=65536,t=4,p=1$MFlKSk1TTGVKL0o5RXcxRg$+TEWHoWVIbT0609sxmLKYJXX6PJsrYsYeSo8MKjW8Jw',NULL,NULL,0,0,NULL,NULL,NULL,0,'activo',NULL,NULL,NULL,'2026-11-06 22:34:17','2026-11-06 23:05:44',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'stockiapro'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_suscripcion_activa` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE FUNCTION `fn_suscripcion_activa`(p_empresa_id INT UNSIGNED) RETURNS tinyint(1)
    READS SQL DATA
    DETERMINISTIC
BEGIN
  DECLARE v_activa TINYINT(1) DEFAULT 0;
  DECLARE v_estado_empresa VARCHAR(20);
  DECLARE v_estado_sus VARCHAR(20);
  DECLARE v_fecha_venc DATETIME;
  DECLARE v_gracia SMALLINT DEFAULT 0;

  SELECT estado INTO v_estado_empresa FROM empresas WHERE id = p_empresa_id;
  IF v_estado_empresa IS NULL OR v_estado_empresa IN ('suspendida','cancelada') THEN
    RETURN 0;
  END IF;

  -- Licencia perpetua activa
  SELECT tipo_plan, estado, fecha_vencimiento, dias_gracia
    INTO v_estado_sus, v_estado_sus, v_fecha_venc, v_gracia
  FROM suscripciones
  WHERE empresa_id = p_empresa_id
  ORDER BY id DESC LIMIT 1;

  -- Re-consulta explícita (más claro)
  SELECT estado, fecha_vencimiento, dias_gracia
    INTO v_estado_sus, v_fecha_venc, v_gracia
  FROM suscripciones
  WHERE empresa_id = p_empresa_id
  ORDER BY id DESC LIMIT 1;

  IF v_estado_sus IS NULL THEN
    RETURN 0;
  END IF;

  IF v_estado_sus IN ('cancelada','suspendida','pendiente') THEN
    RETURN 0;
  END IF;

  IF v_fecha_venc IS NULL THEN
    -- Perpetua o sin vencimiento → válida si estado activo/prueba
    RETURN IF(v_estado_sus IN ('activa','prueba'), 1, 0);
  END IF;

  IF NOW() <= DATE_ADD(v_fecha_venc, INTERVAL v_gracia DAY) THEN
    RETURN 1;
  END IF;

  RETURN 0;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-06 23:05:44
