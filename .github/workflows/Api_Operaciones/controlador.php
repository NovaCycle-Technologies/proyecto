<?php
declare(strict_types=1);
require_once __DIR__ . '/modelo.php';

class ControladorOperaciones
{
    private ModeloOperaciones $modelo;
    public function __construct() { $this->modelo = new ModeloOperaciones(); }
    public function camiones(): array { return ['ok' => true, 'camiones' => $this->modelo->camiones()]; }
    public function incidencias(): array { return ['ok' => true, 'incidencias' => $this->modelo->incidencias()]; }
    public function resumen(): array { return ['ok' => true, 'resumen' => $this->modelo->resumen()]; }
    public function crearIncidencia(array $datos, string $ci): array {
        foreach (['ubicacion', 'tipo', 'detalle'] as $campo) if (trim((string)($datos[$campo] ?? '')) === '') return ['ok' => false, 'mensaje' => 'Completá todos los datos de la incidencia.'];
        $this->modelo->crearIncidencia(['ubicacion' => trim($datos['ubicacion']), 'tipo' => trim($datos['tipo']), 'detalle' => trim($datos['detalle'])], $ci);
        return ['ok' => true, 'mensaje' => 'Incidencia enviada correctamente.'];
    }
    public function revisarIncidencia(array $datos): array {
        return $this->modelo->revisarIncidencia((int)($datos['id'] ?? 0)) ? ['ok' => true, 'mensaje' => 'Incidencia marcada como revisada.'] : ['ok' => false, 'mensaje' => 'No se pudo actualizar la incidencia.'];
    }
    public function estadoCamion(array $datos): array {
        $estado = $datos['estado'] ?? ''; $id = (int)($datos['id_camion'] ?? 0);
        if (!in_array($estado, ['disponible', 'en_mantenimiento', 'fuera_de_servicio'], true)) return ['ok' => false, 'mensaje' => 'Estado de camión no válido.'];
        return $this->modelo->actualizarCamion($id, $estado) ? ['ok' => true, 'mensaje' => 'Estado del camión actualizado.'] : ['ok' => false, 'mensaje' => 'No se pudo actualizar el camión.'];
    }
    public function ingreso(array $datos, string $ci): array {
        $peso = (int)($datos['peso_kg'] ?? 0); if ((int)($datos['id_camion'] ?? 0) <= 0 || $peso <= 0 || trim((string)($datos['tipo_residuo'] ?? '')) === '') return ['ok' => false, 'mensaje' => 'Completá correctamente los datos del ingreso.'];
        $this->modelo->registrarIngreso((int)$datos['id_camion'], $ci, trim($datos['tipo_residuo']), $peso);
        return ['ok' => true, 'mensaje' => 'Ingreso de residuos registrado correctamente.'];
    }
    public function maquinaria(array $datos, string $ci): array {
        if (trim((string)($datos['maquinaria'] ?? '')) === '' || trim((string)($datos['estado'] ?? '')) === '') return ['ok' => false, 'mensaje' => 'Seleccioná equipo y estado.'];
        $this->modelo->registrarMaquinaria($ci, trim($datos['maquinaria']), trim($datos['estado']), trim((string)($datos['observacion'] ?? '')));
        return ['ok' => true, 'mensaje' => 'Estado de la maquinaria actualizado correctamente.'];
    }
    public function rutas(): array { return ['ok' => true, 'rutas' => $this->modelo->rutas()]; }
    public function contenedores(): array { return ['ok' => true, 'contenedores' => $this->modelo->contenedores()]; }
    public function trabajadores(string $rol): array { return ['ok' => true, 'trabajadores' => $this->modelo->trabajadores($rol)]; }
    public function crearRuta(array $datos): array { if (trim((string)($datos['nombre'] ?? '')) === '' || trim((string)($datos['zona'] ?? '')) === '') return ['ok' => false, 'mensaje' => 'Completá nombre y zona.']; return ['ok' => true, 'id_ruta' => $this->modelo->crearRuta(trim($datos['nombre']), trim($datos['zona'])), 'mensaje' => 'Ruta creada correctamente.']; }
    public function actualizarRuta(array $datos): array { $ruta = (int)($datos['id_ruta'] ?? 0); $nombre = trim((string)($datos['nombre'] ?? '')); $zona = trim((string)($datos['zona'] ?? '')); if ($ruta <= 0 || $nombre === '' || $zona === '') return ['ok' => false, 'mensaje' => 'Completá ruta, nombre y zona.']; return $this->modelo->actualizarRuta($ruta, $nombre, $zona) ? ['ok' => true, 'mensaje' => 'Ruta actualizada correctamente.'] : ['ok' => false, 'mensaje' => 'No se pudo actualizar la ruta.']; }
    public function eliminarRuta(array $datos): array { return $this->modelo->eliminarRuta((int)($datos['id_ruta'] ?? 0)) ? ['ok' => true, 'mensaje' => 'Ruta eliminada correctamente.'] : ['ok' => false, 'mensaje' => 'No se pudo eliminar la ruta.']; }
    public function crearParada(array $datos): array {
        $ruta = (int)($datos['id_ruta'] ?? 0); $contenedor = (int)($datos['id_contenedor'] ?? 0);
        if ($ruta <= 0 || $contenedor <= 0) return ['ok' => false, 'mensaje' => 'Seleccioná una ruta y un contenedor.'];
        if (!$this->modelo->crearParada($ruta, $contenedor, max(1, (int)($datos['orden'] ?? 1)))) return ['ok' => false, 'mensaje' => 'El contenedor ya está en esta ruta o no tiene coordenadas válidas.'];
        return ['ok' => true, 'mensaje' => 'Contenedor agregado a la ruta correctamente.'];
    }
    public function asignarRuta(array $datos): array { foreach (['id_ruta','id_camion','ci_conductor','ci_peon','fecha'] as $campo) if (trim((string)($datos[$campo] ?? '')) === '') return ['ok' => false, 'mensaje' => 'Completá todos los datos de asignación.']; $this->modelo->asignarRuta($datos); return ['ok' => true, 'mensaje' => 'Ruta asignada correctamente.']; }
    public function miRuta(string $ci): array { return ['ok' => true, 'asignacion' => $this->modelo->miRuta($ci)]; }
    public function completarParada(array $datos, string $ci): array { return $this->modelo->completarParada((int)($datos['id_asignacion'] ?? 0), (int)($datos['id_parada'] ?? 0), $ci) ? ['ok' => true, 'mensaje' => 'Parada registrada como completada.'] : ['ok' => false, 'mensaje' => 'La parada ya estaba completada.']; }
    public function reporteAdmin(): array { return ['ok' => true, 'reporte' => $this->modelo->reporteAdmin()]; }
    public function dashboardAdmin(): array { return ['ok' => true, 'dashboard' => $this->modelo->dashboardAdmin()]; }
    public function rutasPublicas(): array { return ['ok' => true, 'rutas' => $this->modelo->rutasPublicas()]; }
}
