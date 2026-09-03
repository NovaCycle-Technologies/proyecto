USE novacycle;

CREATE TABLE IF NOT EXISTS incidencias (
  id_incidencia INT AUTO_INCREMENT PRIMARY KEY,
  ci_reportante VARCHAR(20) NOT NULL,
  ubicacion VARCHAR(150) NOT NULL,
  tipo VARCHAR(80) NOT NULL,
  detalle TEXT NOT NULL,
  estado ENUM('pendiente', 'revisada') NOT NULL DEFAULT 'pendiente',
  fecha_reporte DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_revision DATETIME NULL
);

CREATE TABLE IF NOT EXISTS ingresos_residuos (
  id_ingreso INT AUTO_INCREMENT PRIMARY KEY,
  id_camion INT NOT NULL,
  ci_operario VARCHAR(20) NOT NULL,
  tipo_residuo VARCHAR(80) NOT NULL,
  peso_kg INT NOT NULL,
  fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ingreso_camion FOREIGN KEY (id_camion) REFERENCES camiones(id_camion)
);

CREATE TABLE IF NOT EXISTS estados_maquinaria (
  id_estado INT AUTO_INCREMENT PRIMARY KEY,
  ci_operario VARCHAR(20) NOT NULL,
  maquinaria VARCHAR(100) NOT NULL,
  estado VARCHAR(80) NOT NULL,
  observacion TEXT NULL,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rutas (
  id_ruta INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  zona VARCHAR(100) NOT NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS paradas_ruta (
  id_parada INT AUTO_INCREMENT PRIMARY KEY,
  id_ruta INT NOT NULL,
  ubicacion VARCHAR(150) NOT NULL,
  descripcion VARCHAR(150) NOT NULL,
  orden INT NOT NULL,
  CONSTRAINT fk_parada_ruta FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS asignaciones_ruta (
  id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
  id_ruta INT NOT NULL,
  id_camion INT NOT NULL,
  ci_conductor VARCHAR(20) NOT NULL,
  ci_peon VARCHAR(20) NOT NULL,
  fecha DATE NOT NULL,
  estado ENUM('asignada','en_curso','completada') NOT NULL DEFAULT 'asignada',
  CONSTRAINT fk_asignacion_ruta FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta),
  CONSTRAINT fk_asignacion_camion FOREIGN KEY (id_camion) REFERENCES camiones(id_camion)
);

CREATE TABLE IF NOT EXISTS recolecciones (
  id_recoleccion INT AUTO_INCREMENT PRIMARY KEY,
  id_asignacion INT NOT NULL,
  id_parada INT NOT NULL,
  ci_trabajador VARCHAR(20) NOT NULL,
  fecha_recoleccion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unica_recoleccion (id_asignacion, id_parada),
  CONSTRAINT fk_recoleccion_asignacion FOREIGN KEY (id_asignacion) REFERENCES asignaciones_ruta(id_asignacion),
  CONSTRAINT fk_recoleccion_parada FOREIGN KEY (id_parada) REFERENCES paradas_ruta(id_parada)
);
