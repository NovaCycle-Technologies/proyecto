USE novacycle;

-- Ejecutar una vez. Conserva las incidencias anteriores sin contenedor.
ALTER TABLE incidencias
  ADD COLUMN id_contenedor INT NULL AFTER id_incidencia,
  ADD CONSTRAINT fk_incidencia_contenedor FOREIGN KEY (id_contenedor) REFERENCES contenedores(id_contenedor);
