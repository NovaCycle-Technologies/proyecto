USE novacycle;

-- Ejecutar una vez en instalaciones anteriores. Conserva las incidencias existentes.
ALTER TABLE incidencias
  MODIFY COLUMN estado ENUM('pendiente', 'revisada', 'cerrada') NOT NULL DEFAULT 'pendiente',
  ADD COLUMN fecha_cierre DATETIME NULL AFTER fecha_revision;
