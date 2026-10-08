<?php
declare(strict_types=1);

class ModeloOperaciones
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = new PDO('mysql:host=localhost;dbname=novacycle;charset=utf8mb4', 'root', '');
        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function camiones(): array
    {
        return $this->conexion->query('SELECT id_camion, matricula, modelo, estado FROM camiones WHERE activo = 1 ORDER BY matricula')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearIncidencia(array $datos, string $ci): int
    {
        $consulta = $this->conexion->prepare('INSERT INTO incidencias (ci_reportante, ubicacion, tipo, detalle, id_contenedor) VALUES (?, ?, ?, ?, ?)');
        $consulta->execute([$ci, $datos['ubicacion'], $datos['tipo'], $datos['detalle'], $datos['id_contenedor']]);
        return (int) $this->conexion->lastInsertId();
    }

    public function contenedorEnRuta(int $id, string $ci): ?array
    {
        $consulta = $this->conexion->prepare("SELECT c.id_contenedor, CONCAT(c.calle, ' ', c.numero) AS ubicacion FROM contenedores c INNER JOIN paradas_ruta p ON p.id_contenedor = c.id_contenedor INNER JOIN asignaciones_ruta a ON a.id_ruta = p.id_ruta WHERE c.id_contenedor = ? AND c.activo = 1 AND a.fecha = CURDATE() AND (a.ci_conductor = ? OR a.ci_peon = ?) LIMIT 1");
        $consulta->execute([$id, $ci, $ci]);
        return $consulta->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function misIncidencias(string $ci): array {
        $consulta = $this->conexion->prepare('SELECT id_incidencia, ubicacion, tipo, detalle, estado, fecha_reporte, fecha_revision, fecha_cierre FROM incidencias WHERE ci_reportante = ? ORDER BY fecha_reporte DESC');
        $consulta->execute([$ci]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
    public function incidencias(string $filtro): array
    {
        $condicion = $filtro === 'cerradas' ? "estado = 'cerrada'" : "estado IN ('pendiente', 'revisada')";
        return $this->conexion->query("SELECT id_incidencia, ubicacion, tipo, detalle, estado, fecha_reporte, fecha_revision, fecha_cierre FROM incidencias WHERE {$condicion} ORDER BY fecha_reporte DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revisarIncidencia(int $id): bool
    {
        $consulta = $this->conexion->prepare("UPDATE incidencias SET estado = 'revisada', fecha_revision = NOW() WHERE id_incidencia = ? AND estado = 'pendiente'");
        $consulta->execute([$id]);
        return $consulta->rowCount() === 1;
    }

    public function cerrarIncidencia(int $id): bool
    {
        $consulta = $this->conexion->prepare("UPDATE incidencias SET estado = 'cerrada', fecha_cierre = NOW() WHERE id_incidencia = ? AND estado IN ('pendiente', 'revisada')");
        $consulta->execute([$id]);
        return $consulta->rowCount() === 1;
    }

    public function actualizarCamion(int $id, string $estado): bool
    {
        $consulta = $this->conexion->prepare('UPDATE camiones SET estado = ? WHERE id_camion = ? AND activo = 1');
        $consulta->execute([$estado, $id]);
        return $consulta->rowCount() === 1;
    }

    public function registrarIngreso(int $camion, string $ci, string $tipo, int $peso): int
    {
        $consulta = $this->conexion->prepare('INSERT INTO ingresos_residuos (id_camion, ci_operario, tipo_residuo, peso_kg) VALUES (?, ?, ?, ?)');
        $consulta->execute([$camion, $ci, $tipo, $peso]);
        return (int) $this->conexion->lastInsertId();
    }

    public function registrarMaquinaria(string $ci, string $maquinaria, string $estado, string $observacion): void
    {
        $consulta = $this->conexion->prepare('INSERT INTO estados_maquinaria (ci_operario, maquinaria, estado, observacion) VALUES (?, ?, ?, ?)');
        $consulta->execute([$ci, $maquinaria, $estado, $observacion]);
    }

    public function resumen(): array
    {
        return [
            'ingresos_hoy' => (int) $this->conexion->query('SELECT COALESCE(SUM(peso_kg), 0) FROM ingresos_residuos WHERE DATE(fecha_ingreso) = CURDATE()')->fetchColumn(),
            'camiones_hoy' => (int) $this->conexion->query('SELECT COUNT(DISTINCT id_camion) FROM ingresos_residuos WHERE DATE(fecha_ingreso) = CURDATE()')->fetchColumn(),
            'incidencias_pendientes' => (int) $this->conexion->query("SELECT COUNT(*) FROM incidencias WHERE estado IN ('pendiente', 'revisada')")->fetchColumn()
        ];
    }

    public function rutas(): array { return $this->conexion->query('SELECT id_ruta, nombre, zona FROM rutas WHERE activa = 1 ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC); }
    public function contenedores(): array { return $this->conexion->query("SELECT id_contenedor, CONCAT(calle, ' ', numero) AS ubicacion, tipo_residuo, capacidad_litros FROM contenedores WHERE activo = 1 AND latitud IS NOT NULL AND longitud IS NOT NULL ORDER BY calle, numero")->fetchAll(PDO::FETCH_ASSOC); }
    public function trabajadores(string $rol): array {
        $tabla = $rol === 'conductor' ? 'conductores' : 'peones';
        $consulta = $this->conexion->query("SELECT u.CI AS ci, CONCAT(u.Nombre, ' ', u.Apellido) AS nombre FROM usuarios u INNER JOIN {$tabla} r ON r.ci = u.CI WHERE u.estado_cuenta = 'aprobado' ORDER BY u.Nombre");
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
    public function crearRuta(string $nombre, string $zona): int { $consulta = $this->conexion->prepare('INSERT INTO rutas (nombre, zona) VALUES (?, ?)'); $consulta->execute([$nombre, $zona]); return (int)$this->conexion->lastInsertId(); }
    public function actualizarRuta(int $ruta, string $nombre, string $zona): bool { $consulta = $this->conexion->prepare('UPDATE rutas SET nombre = ?, zona = ? WHERE id_ruta = ? AND activa = 1'); $consulta->execute([$nombre, $zona, $ruta]); return $consulta->rowCount() === 1; }
    public function eliminarRuta(int $ruta): bool { $consulta = $this->conexion->prepare('UPDATE rutas SET activa = 0 WHERE id_ruta = ? AND activa = 1'); $consulta->execute([$ruta]); return $consulta->rowCount() === 1; }
    public function crearParada(int $ruta, int $contenedor, int $orden): bool {
        $consulta = $this->conexion->prepare("INSERT INTO paradas_ruta (id_ruta, id_contenedor, ubicacion, descripcion, orden, latitud, longitud) SELECT ?, id_contenedor, CONCAT(calle, ' ', numero), CONCAT(tipo_residuo, ' · ', capacidad_litros, ' L'), ?, latitud, longitud FROM contenedores WHERE id_contenedor = ? AND activo = 1 AND latitud IS NOT NULL AND longitud IS NOT NULL");
        $consulta->execute([$ruta, $orden, $contenedor]);
        return $consulta->rowCount() === 1;
    }
    public function asignarRuta(array $datos): void { $consulta = $this->conexion->prepare('INSERT INTO asignaciones_ruta (id_ruta, id_camion, ci_conductor, ci_peon, fecha) VALUES (?, ?, ?, ?, ?)'); $consulta->execute([$datos['id_ruta'], $datos['id_camion'], $datos['ci_conductor'], $datos['ci_peon'], $datos['fecha']]); }
    public function miRuta(string $ci): ?array {
        $consulta = $this->conexion->prepare("SELECT a.id_asignacion, a.id_camion, r.nombre, r.zona, c.matricula, c.modelo, c.estado FROM asignaciones_ruta a INNER JOIN rutas r ON r.id_ruta=a.id_ruta INNER JOIN camiones c ON c.id_camion=a.id_camion WHERE a.fecha=CURDATE() AND (a.ci_conductor=? OR a.ci_peon=?) ORDER BY a.id_asignacion DESC LIMIT 1");
        $consulta->execute([$ci, $ci]); $asignacion = $consulta->fetch(PDO::FETCH_ASSOC); if (!$asignacion) return null;
        $paradas = $this->conexion->prepare('SELECT p.id_parada, p.id_contenedor, p.ubicacion, p.descripcion, p.orden, IF(rc.id_recoleccion IS NULL, 0, 1) AS completada FROM paradas_ruta p LEFT JOIN recolecciones rc ON rc.id_parada=p.id_parada AND rc.id_asignacion=? WHERE p.id_ruta=(SELECT id_ruta FROM asignaciones_ruta WHERE id_asignacion=?) ORDER BY p.orden');
        $paradas->execute([$asignacion['id_asignacion'], $asignacion['id_asignacion']]); $asignacion['paradas'] = $paradas->fetchAll(PDO::FETCH_ASSOC); return $asignacion;
    }
    public function completarParada(int $asignacion, int $parada, string $ci): bool { $consulta = $this->conexion->prepare('INSERT IGNORE INTO recolecciones (id_asignacion, id_parada, ci_trabajador) VALUES (?, ?, ?)'); $consulta->execute([$asignacion, $parada, $ci]); return $consulta->rowCount() === 1; }
    public function reporteAdmin(): array { return ['rutas_hoy' => (int)$this->conexion->query('SELECT COUNT(*) FROM asignaciones_ruta WHERE fecha=CURDATE()')->fetchColumn(), 'recolecciones_hoy' => (int)$this->conexion->query('SELECT COUNT(*) FROM recolecciones WHERE DATE(fecha_recoleccion)=CURDATE()')->fetchColumn(), 'incidencias_pendientes' => (int)$this->conexion->query("SELECT COUNT(*) FROM incidencias WHERE estado IN ('pendiente','revisada')")->fetchColumn(), 'ingresos_hoy' => (int)$this->conexion->query('SELECT COALESCE(SUM(peso_kg),0) FROM ingresos_residuos WHERE DATE(fecha_ingreso)=CURDATE()')->fetchColumn()]; }
    public function dashboardAdmin(): array {
        $contenedores = ['funcional' => 0, 'roto' => 0, 'desbordado' => 0];
        foreach ($this->conexion->query('SELECT estado, COUNT(*) AS cantidad FROM contenedores WHERE activo = 1 GROUP BY estado')->fetchAll(PDO::FETCH_ASSOC) as $fila) $contenedores[$fila['estado']] = (int)$fila['cantidad'];
        $camiones = ['disponible' => 0, 'en_mantenimiento' => 0, 'fuera_de_servicio' => 0];
        foreach ($this->conexion->query('SELECT estado, COUNT(*) AS cantidad FROM camiones WHERE activo = 1 GROUP BY estado')->fetchAll(PDO::FETCH_ASSOC) as $fila) $camiones[$fila['estado']] = (int)$fila['cantidad'];
        return ['jornada' => $this->reporteAdmin(), 'contenedores' => $contenedores, 'camiones' => $camiones];
    }
    public function contenedoresPublicos(): array {
        return $this->conexion->query("SELECT id_contenedor, CONCAT(calle, ' ', numero) AS ubicacion, tipo_residuo, estado, latitud, longitud FROM contenedores WHERE activo = 1 AND latitud IS NOT NULL AND longitud IS NOT NULL ORDER BY id_contenedor")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function incidenciasPublicas(): array {
        return $this->conexion->query("SELECT i.id_incidencia, i.tipo, i.estado, i.ubicacion, c.latitud, c.longitud FROM incidencias i INNER JOIN contenedores c ON c.id_contenedor = i.id_contenedor WHERE i.estado IN ('pendiente', 'revisada') AND c.activo = 1 AND c.latitud IS NOT NULL AND c.longitud IS NOT NULL ORDER BY i.fecha_reporte DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function rutasPublicas(): array {
        $rutas = $this->conexion->query("SELECT DISTINCT r.id_ruta, r.nombre, r.zona, c.matricula FROM rutas r INNER JOIN asignaciones_ruta a ON a.id_ruta = r.id_ruta AND a.fecha = CURDATE() INNER JOIN camiones c ON c.id_camion = a.id_camion WHERE r.activa = 1 ORDER BY r.nombre")->fetchAll(PDO::FETCH_ASSOC);
        $consulta = $this->conexion->prepare('SELECT id_parada, ubicacion, descripcion, orden, latitud, longitud FROM paradas_ruta WHERE id_ruta = ? AND latitud IS NOT NULL AND longitud IS NOT NULL ORDER BY orden');
        foreach ($rutas as &$ruta) { $consulta->execute([$ruta['id_ruta']]); $ruta['paradas'] = $consulta->fetchAll(PDO::FETCH_ASSOC); }
        return $rutas;
    }
}
