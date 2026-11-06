USE novacycle;

-- Ejecutar una sola vez en una base de datos creada antes de esta actualización.
ALTER TABLE contenedores
  ADD COLUMN latitud DECIMAL(10, 7) NULL AFTER capacidad_litros,
  ADD COLUMN longitud DECIMAL(10, 7) NULL AFTER latitud;

ALTER TABLE paradas_ruta
  ADD COLUMN id_contenedor INT NULL AFTER id_ruta,
  ADD KEY fk_parada_contenedor (id_contenedor),
  ADD UNIQUE KEY unica_parada_contenedor (id_ruta, id_contenedor),
  ADD CONSTRAINT fk_parada_contenedor FOREIGN KEY (id_contenedor) REFERENCES contenedores(id_contenedor);
