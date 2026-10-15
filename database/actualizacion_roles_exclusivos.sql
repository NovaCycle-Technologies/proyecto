USE novacycle;

-- Ejecutar una sola vez antes de usar la nueva aprobación de usuarios.
-- Cada puesto aprobado queda en una única tabla de rol.
ALTER TABLE usuarios
  MODIFY COLUMN rol_solicitado ENUM('peon', 'conductor', 'operario') NOT NULL DEFAULT 'peon';

-- Antes peones y conductores dependían de operarios. Ahora todos dependen
-- directamente de usuarios, sin cambiar sus CI ni sus asignaciones.
ALTER TABLE peones
  DROP FOREIGN KEY fk_peones_operario,
  ADD CONSTRAINT fk_peones_usuario FOREIGN KEY (ci) REFERENCES usuarios(CI)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE conductores
  DROP FOREIGN KEY fk_conductores_operario,
  ADD CONSTRAINT fk_conductores_usuario FOREIGN KEY (ci) REFERENCES usuarios(CI)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- Algunos operarios reales quedaron con rol_solicitado vacío porque el ENUM
-- anterior no admitía ese valor.
UPDATE usuarios u
INNER JOIN operarios o ON o.ci = u.CI
LEFT JOIN peones p ON p.ci = u.CI
LEFT JOIN conductores c ON c.ci = u.CI
SET u.rol_solicitado = 'operario'
WHERE p.ci IS NULL AND c.ci IS NULL AND u.rol_solicitado = '';

-- Se conservan solo los operarios que no sean peones ni conductores.
DELETE o FROM operarios o
LEFT JOIN peones p ON p.ci = o.ci
LEFT JOIN conductores c ON c.ci = o.ci
WHERE p.ci IS NOT NULL OR c.ci IS NOT NULL;
