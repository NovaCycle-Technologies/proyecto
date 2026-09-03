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
        $consulta = $this->conexion->prepare('INSERT INTO incidencias (ci_reportante, ubicacion, tipo, detalle) VALUES (?, ?, ?, ?)');
        $consulta->execute([$ci, $datos['ubicacion'], $datos['tipo'], $datos['detalle']]);
        return (int) $this->conexion->lastInsertId();
    }

    public function incidencias(): array
    {
        return $this->conexion->query("SELECT id_incidencia, ubicacion, tipo, detalle, estado, fecha_reporte FROM incidencias WHERE estado = 'pendiente' ORDER BY fecha_reporte DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function revisarIncidencia(int $id): bool
    {
        $consulta = $this->conexion->prepare("UPDATE incidencias SET estado = 'revisada', fecha_revision = NOW() WHERE id_incidencia = ? AND estado = 'pendiente'");
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
            'incidencias_pendientes' => (int) $this->conexion->query("SELECT COUNT(*) FROM incidencias WHERE estado = 'pendiente'")->fetchColumn()
        ];
    }

    public function rutas(): array { return $this->conexion->query('SELECT id_ruta, nombre, zona FROM rutas WHERE activa = 1 ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC); }
    public function trabajadores(string $rol): array {
        $tabla = $rol === 'conductor' ? 'conductores' : 'peones';
        $consulta = $this->conexion->query("SELECT u.CI AS ci, CONCAT(u.Nombre, ' ', u.Apellido) AS nombre FROM usuarios u INNER JOIN {$tabla} r ON r.ci = u.CI WHERE u.estado_cuenta = 'aprobado' ORDER BY u.Nombre");
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
    public function crearRuta(string $nombre, string $zona): int { $consulta = $this->conexion->prepare('INSERT INTO rutas (nombre, zona) VALUES (?, ?)'); $consulta->execute([$nombre, $zona]); return (int)$this->conexion->lastInsertId(); }
    public function crearParada(int $ruta, string $ubicacion, string $descripcion, int $orden): void { $consulta = $this->conexion->prepare('INSERT INTO paradas_ruta (id_ruta, ubicacion, descripcion, orden) VALUES (?, ?, ?, ?)'); $consulta->execute([$ruta, $ubicacion, $descripcion, $orden]); }
    public function asignarRuta(array $datos): void { $consulta = $this->conexion->prepare('INSERT INTO asignaciones_ruta (id_ruta, id_camion, ci_conductor, ci_peon, fecha) VALUES (?, ?, ?, ?, ?)'); $consulta->execute([$datos['id_ruta'], $datos['id_camion'], $datos['ci_conductor'], $datos['ci_peon'], $datos['fecha']]); }
    public function miRuta(string $ci): ?array {
        $consulta = $this->conexion->prepare("SELECT a.id_asignacion, a.id_camion, r.nombre, r.zona, c.matricula, c.modelo, c.estado FROM asignaciones_ruta a INNER JOIN rutas r ON r.id_ruta=a.id_ruta INNER JOIN camiones c ON c.id_camion=a.id_camion WHERE a.fecha=CURDATE() AND (a.ci_conductor=? OR a.ci_peon=?) ORDER BY a.id_asignacion DESC LIMIT 1");
        $consulta->execute([$ci, $ci]); $asignacion = $consulta->fetch(PDO::FETCH_ASSOC); if (!$asignacion) return null;
        $paradas = $this->conexion->prepare('SELECT p.id_parada, p.ubicacion, p.descripcion, p.orden, IF(rc.id_recoleccion IS NULL, 0, 1) AS completada FROM paradas_ruta p LEFT JOIN recolecciones rc ON rc.id_parada=p.id_parada AND rc.id_asignacion=? WHERE p.id_ruta=(SELECT id_ruta FROM asignaciones_ruta WHERE id_asignacion=?) ORDER BY p.orden');
        $paradas->execute([$asignacion['id_asignacion'], $asignacion['id_asignacion']]); $asignacion['paradas'] = $paradas->fetchAll(PDO::FETCH_ASSOC); return $asignacion;
    }
    public function completarParada(int $asignacion, int $parada, string $ci): bool { $consulta = $this->conexion->prepare('INSERT IGNORE INTO recolecciones (id_asignacion, id_parada, ci_trabajador) VALUES (?, ?, ?)'); $consulta->execute([$asignacion, $parada, $ci]); return $consulta->rowCount() === 1; }
    public function reporteAdmin(): array { return ['rutas_hoy' => (int)$this->conexion->query('SELECT COUNT(*) FROM asignaciones_ruta WHERE fecha=CURDATE()')->fetchColumn(), 'recolecciones_hoy' => (int)$this->conexion->query('SELECT COUNT(*) FROM recolecciones WHERE DATE(fecha_recoleccion)=CURDATE()')->fetchColumn(), 'incidencias_pendientes' => (int)$this->conexion->query("SELECT COUNT(*) FROM incidencias WHERE estado='pendiente'")->fetchColumn(), 'ingresos_hoy' => (int)$this->conexion->query('SELECT COALESCE(SUM(peso_kg),0) FROM ingresos_residuos WHERE DATE(fecha_ingreso)=CURDATE()')->fetchColumn()]; }
}
