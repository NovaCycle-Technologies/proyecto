<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/cuentas_prueba.php';
require_once __DIR__ . '/operaciones_http_helper.php';

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'conductor', 'peon', 'operario']);
$sesiones = [];
$camion = null;
$ruta = null;
$asignacion = null;
$contenedores = [];

try {
    foreach ($cuentas as $rol => $ci) {
        $sesiones[$rol] = sesionDePrueba($ci, $rol);
    }

    $crearCamion = $conexion->prepare(
        "INSERT INTO camiones (matricula, modelo, capacidad_kg, estado)
         VALUES (?, 'Prueba temporal', 1000, 'disponible')"
    );
    $crearCamion->execute(['T' . strtoupper(bin2hex(random_bytes(4)))]);
    $camion = (int) $conexion->lastInsertId();

    $crearContenedor = $conexion->prepare(
        "INSERT INTO contenedores (calle, numero, estado, tipo_residuo, capacidad_litros, latitud, longitud)
         VALUES ('Prueba incidencia', ?, 'funcional', 'Reciclaje', 800, -34.9000, -56.2000)"
    );
    foreach (['1', '2'] as $numero) {
        $crearContenedor->execute([$numero]);
        $contenedores[] = (int) $conexion->lastInsertId();
    }

    [$estado, $resultado] = solicitarOperacion('crear_ruta', 'POST', $sesiones['admin_municipal'], [
        'nombre' => 'Ruta incidencias ' . substr(bin2hex(random_bytes(4)), 0, 8),
        'zona' => 'Prueba'
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo crear la ruta de prueba.');
    }
    $ruta = (int) $resultado['id_ruta'];
    [$estado, $resultado] = solicitarOperacion('crear_parada', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => $ruta, 'id_contenedor' => $contenedores[0], 'orden' => 1
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo vincular el contenedor a la ruta.');
    }
    [$estado, $resultado] = solicitarOperacion('asignar_ruta', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => $ruta, 'id_camion' => $camion,
        'ci_conductor' => $cuentas['conductor'], 'ci_peon' => $cuentas['peon'],
        'fecha' => $conexion->query('SELECT CURDATE()')->fetchColumn()
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo asignar la cuadrilla de prueba.');
    }
    $buscarAsignacion = $conexion->prepare('SELECT id_asignacion FROM asignaciones_ruta WHERE id_ruta = ?');
    $buscarAsignacion->execute([$ruta]);
    $asignacion = (int) $buscarAsignacion->fetchColumn();

    [$estado, $resultado] = solicitarOperacion('incidencia', 'POST', $sesiones['peon'], [
        'id_contenedor' => $contenedores[0], 'tipo' => 'Desborde', 'detalle' => ''
    ]);
    if ($estado !== 400 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('Se permitió una incidencia sin detalle.');
    }
    [$estado, $resultado] = solicitarOperacion('incidencia', 'POST', $sesiones['peon'], [
        'id_contenedor' => $contenedores[1], 'tipo' => 'Desborde', 'detalle' => 'Contenedor ajeno'
    ]);
    if ($estado !== 400 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('Se permitió reportar un contenedor ajeno a la ruta.');
    }
    [$estado, $resultado] = solicitarOperacion('incidencia', 'POST', $sesiones['operario'], [
        'ubicacion' => 'Prueba', 'tipo' => 'Rotura', 'detalle' => 'Sin permiso'
    ]);
    if ($estado !== 403 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('El operario pudo crear una incidencia de cuadrilla.');
    }

    [$estado, $resultado] = solicitarOperacion('incidencia', 'POST', $sesiones['peon'], [
        'id_contenedor' => $contenedores[0], 'tipo' => 'Desborde', 'detalle' => 'Prueba temporal'
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('El peón no pudo registrar la incidencia.');
    }
    $buscarIncidencia = $conexion->prepare(
        'SELECT id_incidencia, ubicacion, estado, fecha_revision, fecha_cierre
         FROM incidencias WHERE ci_reportante = ? AND id_contenedor = ? ORDER BY id_incidencia DESC LIMIT 1'
    );
    $buscarIncidencia->execute([$cuentas['peon'], $contenedores[0]]);
    $incidencia = $buscarIncidencia->fetch(PDO::FETCH_ASSOC);
    if (!$incidencia || $incidencia['estado'] !== 'pendiente' || $incidencia['ubicacion'] !== 'Prueba incidencia 1') {
        throw new RuntimeException('La incidencia no se guardó como pendiente y vinculada al contenedor.');
    }
    $idIncidencia = (int) $incidencia['id_incidencia'];

    [, $resultado] = solicitarOperacion('mis_incidencias', 'GET', $sesiones['peon']);
    $propias = array_filter($resultado['incidencias'], fn (array $item): bool => (int) $item['id_incidencia'] === $idIncidencia);
    if (count($propias) !== 1) {
        throw new RuntimeException('El peón no ve su incidencia.');
    }
    [, $resultado] = solicitarOperacion('mis_incidencias', 'GET', $sesiones['conductor']);
    foreach ($resultado['incidencias'] as $item) {
        if ((int) $item['id_incidencia'] === $idIncidencia) {
            throw new RuntimeException('El conductor ve una incidencia ajena.');
        }
    }
    [, $resultado] = solicitarOperacion('incidencias&estado=abiertas', 'GET', $sesiones['operario']);
    $abiertas = array_filter($resultado['incidencias'], fn (array $item): bool => (int) $item['id_incidencia'] === $idIncidencia);
    if (count($abiertas) !== 1) {
        throw new RuntimeException('El operario no ve la incidencia abierta.');
    }
    [, $mapa] = solicitarOperacion('mapa_publico');
    $marcadores = array_filter($mapa['incidencias'], fn (array $item): bool => (int) $item['id_incidencia'] === $idIncidencia);
    if (count($marcadores) !== 1) {
        throw new RuntimeException('La incidencia abierta no aparece en el mapa.');
    }

    [$estado, $resultado] = solicitarOperacion('revisar_incidencia', 'POST', $sesiones['peon'], ['id' => $idIncidencia]);
    if ($estado !== 403 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('Un peón pudo revisar una incidencia.');
    }
    [$estado, $resultado] = solicitarOperacion('revisar_incidencia', 'POST', $sesiones['operario'], ['id' => $idIncidencia]);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('El operario no pudo marcar la incidencia como revisada.');
    }
    $buscarIncidencia->execute([$cuentas['peon'], $contenedores[0]]);
    $incidencia = $buscarIncidencia->fetch(PDO::FETCH_ASSOC);
    if ($incidencia['estado'] !== 'revisada' || $incidencia['fecha_revision'] === null) {
        throw new RuntimeException('La revisión no quedó guardada en MySQL.');
    }

    [$estado, $resultado] = solicitarOperacion('cerrar_incidencia', 'POST', $sesiones['operario'], ['id' => $idIncidencia]);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('El operario no pudo cerrar la incidencia.');
    }
    $buscarIncidencia->execute([$cuentas['peon'], $contenedores[0]]);
    $incidencia = $buscarIncidencia->fetch(PDO::FETCH_ASSOC);
    if ($incidencia['estado'] !== 'cerrada' || $incidencia['fecha_cierre'] === null) {
        throw new RuntimeException('El cierre no quedó guardado en MySQL.');
    }
    [, $resultado] = solicitarOperacion('incidencias&estado=cerradas', 'GET', $sesiones['operario']);
    $cerradas = array_filter($resultado['incidencias'], fn (array $item): bool => (int) $item['id_incidencia'] === $idIncidencia);
    if (count($cerradas) !== 1) {
        throw new RuntimeException('La incidencia cerrada no aparece en su listado.');
    }
    [, $mapa] = solicitarOperacion('mapa_publico');
    foreach ($mapa['incidencias'] as $item) {
        if ((int) $item['id_incidencia'] === $idIncidencia) {
            throw new RuntimeException('La incidencia cerrada sigue apareciendo en el mapa.');
        }
    }
    [, $resultado] = solicitarOperacion('cerrar_incidencia', 'POST', $sesiones['operario'], ['id' => $idIncidencia]);
    if ($resultado['ok'] ?? true) {
        throw new RuntimeException('Se pudo cerrar dos veces la misma incidencia.');
    }

    [$estado, $resultado] = solicitarOperacion('incidencia', 'POST', $sesiones['conductor'], [
        'ubicacion' => 'Otro lugar de prueba', 'tipo' => 'Rotura', 'detalle' => 'Sin contenedor'
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo registrar una incidencia en otro lugar.');
    }
    $buscarLibre = $conexion->prepare('SELECT id_contenedor FROM incidencias WHERE ci_reportante = ? ORDER BY id_incidencia DESC LIMIT 1');
    $buscarLibre->execute([$cuentas['conductor']]);
    if ($buscarLibre->fetchColumn() !== null) {
        throw new RuntimeException('La incidencia en otro lugar quedó vinculada a un contenedor.');
    }
} finally {
    $eliminarIncidencias = $conexion->prepare('DELETE FROM incidencias WHERE ci_reportante = ?');
    foreach ([$cuentas['peon'], $cuentas['conductor']] as $ci) {
        $eliminarIncidencias->execute([$ci]);
    }
    if ($asignacion !== null) {
        $eliminar = $conexion->prepare('DELETE FROM asignaciones_ruta WHERE id_asignacion = ?');
        $eliminar->execute([$asignacion]);
    }
    if ($ruta !== null) {
        $eliminar = $conexion->prepare('DELETE FROM rutas WHERE id_ruta = ?');
        $eliminar->execute([$ruta]);
    }
    $eliminarContenedor = $conexion->prepare('DELETE FROM contenedores WHERE id_contenedor = ?');
    foreach ($contenedores as $idContenedor) {
        $eliminarContenedor->execute([$idContenedor]);
    }
    if ($camion !== null) {
        $eliminar = $conexion->prepare('DELETE FROM camiones WHERE id_camion = ?');
        $eliminar->execute([$camion]);
    }
    cerrarSesionesDePrueba($sesiones);
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Incidencias HTTP: registro, permisos, seguimiento, mapa y cierre correctos\n";
