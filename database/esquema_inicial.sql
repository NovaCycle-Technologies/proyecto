-- Esquema inicial de NovaCycle. No incluye cuentas ni datos personales.
-- Para instalaciones nuevas; no ejecutar las migraciones sobre este esquema.
CREATE DATABASE IF NOT EXISTS novacycle
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE novacycle;

CREATE TABLE IF NOT EXISTS usuarios (
  Nombre VARCHAR(30) NOT NULL,
  Apellido VARCHAR(30) NOT NULL,
  CI VARCHAR(8) NOT NULL,
  Email VARCHAR(30) NOT NULL,
  password VARCHAR(255) NOT NULL,
  estado_cuenta ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente',
  rol_solicitado ENUM('peon', 'conductor', 'operario') NOT NULL DEFAULT 'peon',
  PRIMARY KEY (CI)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
  ci VARCHAR(8) NOT NULL,
  PRIMARY KEY (ci),
  CONSTRAINT fk_admins_usuario FOREIGN KEY (ci) REFERENCES usuarios(CI)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS operarios (
  ci VARCHAR(8) NOT NULL,
  PRIMARY KEY (ci),
  CONSTRAINT fk_operarios_usuario FOREIGN KEY (ci) REFERENCES usuarios(CI)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS peones (
  ci VARCHAR(8) NOT NULL,
  PRIMARY KEY (ci),
  CONSTRAINT fk_peones_usuario FOREIGN KEY (ci) REFERENCES usuarios(CI)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS conductores (
  ci VARCHAR(8) NOT NULL,
  PRIMARY KEY (ci),
  CONSTRAINT fk_conductores_usuario FOREIGN KEY (ci) REFERENCES usuarios(CI)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS camiones (
  id_camion INT NOT NULL AUTO_INCREMENT,
  matricula VARCHAR(20) NOT NULL,
  modelo VARCHAR(100) NOT NULL,
  capacidad_kg INT NOT NULL,
  estado ENUM('disponible', 'en_mantenimiento', 'fuera_de_servicio') NOT NULL DEFAULT 'disponible',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_camion),
  UNIQUE KEY matricula (matricula)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contenedores (
  id_contenedor INT NOT NULL AUTO_INCREMENT,
  calle VARCHAR(100) NOT NULL,
  numero VARCHAR(20) NOT NULL,
  estado ENUM('funcional', 'roto', 'desbordado') NOT NULL DEFAULT 'funcional',
  tipo_residuo VARCHAR(50) NOT NULL,
  capacidad_litros INT NOT NULL,
  latitud DECIMAL(10, 7) DEFAULT NULL,
  longitud DECIMAL(10, 7) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_contenedor)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rutas (
  id_ruta INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  zona VARCHAR(100) NOT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_ruta)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS incidencias (
  id_incidencia INT NOT NULL AUTO_INCREMENT,
  id_contenedor INT DEFAULT NULL,
  ci_reportante VARCHAR(20) NOT NULL,
  ubicacion VARCHAR(150) NOT NULL,
  tipo VARCHAR(80) NOT NULL,
  detalle TEXT NOT NULL,
  estado ENUM('pendiente', 'revisada', 'cerrada') NOT NULL DEFAULT 'pendiente',
  fecha_reporte DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_revision DATETIME DEFAULT NULL,
  fecha_cierre DATETIME DEFAULT NULL,
  PRIMARY KEY (id_incidencia),
  KEY fk_incidencia_contenedor (id_contenedor),
  CONSTRAINT fk_incidencia_contenedor FOREIGN KEY (id_contenedor)
    REFERENCES contenedores(id_contenedor)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ingresos_residuos (
  id_ingreso INT NOT NULL AUTO_INCREMENT,
  id_camion INT NOT NULL,
  ci_operario VARCHAR(20) NOT NULL,
  tipo_residuo VARCHAR(80) NOT NULL,
  peso_kg INT NOT NULL,
  fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_ingreso),
  KEY fk_ingreso_camion (id_camion),
  CONSTRAINT fk_ingreso_camion FOREIGN KEY (id_camion) REFERENCES camiones(id_camion)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estados_maquinaria (
  id_estado INT NOT NULL AUTO_INCREMENT,
  ci_operario VARCHAR(20) NOT NULL,
  maquinaria VARCHAR(100) NOT NULL,
  estado VARCHAR(80) NOT NULL,
  observacion TEXT DEFAULT NULL,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_estado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS paradas_ruta (
  id_parada INT NOT NULL AUTO_INCREMENT,
  id_ruta INT NOT NULL,
  id_contenedor INT DEFAULT NULL,
  ubicacion VARCHAR(150) NOT NULL,
  descripcion VARCHAR(150) NOT NULL,
  orden INT NOT NULL,
  latitud DECIMAL(10, 7) DEFAULT NULL,
  longitud DECIMAL(10, 7) DEFAULT NULL,
  PRIMARY KEY (id_parada),
  UNIQUE KEY unica_parada_contenedor (id_ruta, id_contenedor),
  KEY fk_parada_contenedor (id_contenedor),
  CONSTRAINT fk_parada_contenedor FOREIGN KEY (id_contenedor) REFERENCES contenedores(id_contenedor),
  CONSTRAINT fk_parada_ruta FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS asignaciones_ruta (
  id_asignacion INT NOT NULL AUTO_INCREMENT,
  id_ruta INT NOT NULL,
  id_camion INT NOT NULL,
  ci_conductor VARCHAR(20) NOT NULL,
  ci_peon VARCHAR(20) NOT NULL,
  fecha DATE NOT NULL,
  estado ENUM('asignada', 'en_curso', 'completada') NOT NULL DEFAULT 'asignada',
  PRIMARY KEY (id_asignacion),
  KEY fk_asignacion_ruta (id_ruta),
  KEY fk_asignacion_camion (id_camion),
  CONSTRAINT fk_asignacion_ruta FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta),
  CONSTRAINT fk_asignacion_camion FOREIGN KEY (id_camion) REFERENCES camiones(id_camion)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS recolecciones (
  id_recoleccion INT NOT NULL AUTO_INCREMENT,
  id_asignacion INT NOT NULL,
  id_parada INT NOT NULL,
  ci_trabajador VARCHAR(20) NOT NULL,
  fecha_recoleccion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_recoleccion),
  UNIQUE KEY unica_recoleccion (id_asignacion, id_parada),
  KEY fk_recoleccion_parada (id_parada),
  CONSTRAINT fk_recoleccion_asignacion FOREIGN KEY (id_asignacion)
    REFERENCES asignaciones_ruta(id_asignacion),
  CONSTRAINT fk_recoleccion_parada FOREIGN KEY (id_parada) REFERENCES paradas_ruta(id_parada)
) ENGINE=InnoDB;
