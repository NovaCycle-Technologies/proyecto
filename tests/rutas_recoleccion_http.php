<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/cuentas_prueba.php';
require_once __DIR__ . '/operaciones_http_helper.php';

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'conductor', 'peon']);
$cuentasAjenas = [];
$sesiones = [];
$camion = null;
$contenedor = null;
$ruta = null;
$rutaAjena = null;
$asignacion = null;

try {
    [, $cuentasAjenas] = crearCuentasPrueba(['peon']);
    foreach ($cuentas as $rol => $ci) {
        $sesiones[$rol] = sesionDePrueba($ci, $rol);
    }
    $sesiones['peon_ajeno'] = sesionDePrueba($cuentasAjenas['peon'], 'peon');

    $matricula = 'T' . strtoupper(bin2hex(random_bytes(4)));
    $crearCamion = $conexion->prepare(
        "INSERT INTO camiones (matricula, modelo, capacidad_kg, estado)
         VALUES (?, 'Prueba temporal', 1000, 'disponible')"
    );
    $crearCamion->execute([$matricula]);
    $camion = (int) $conexion->lastInsertId();

    $conexion->exec(
        "INSERT INTO contenedores (calle, numero, estado, tipo_residuo, capacidad_litros, latitud, longitud)
         VALUES ('Prueba temporal', '1', 'funcional', 'Reciclaje', 800, -34.9000, -56.2000)"
    );
    $contenedor = (int) $conexion->lastInsertId();

    [$estado, $resultado] = solicitarOperacion('crear_ruta', 'POST', $sesiones['peon'], [
        'nombre' => 'Ruta prohibida', 'zona' => 'Prueba'
    ]);
    if ($estado !== 403 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('Un peón pudo crear rutas.');
    }

    $nombre = 'Ruta prueba ' . substr(bin2hex(random_bytes(4)), 0, 8);
    [$estado, $resultado] = solicitarOperacion('crear_ruta', 'POST', $sesiones['admin_municipal'], [
        'nombre' => $nombre, 'zona' => 'Zona prueba'
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('El administrador no pudo crear la ruta.');
    }
    $ruta = (int) $resultado['id_ruta'];
    $buscarRuta = $conexion->prepare('SELECT nombre, zona, activa FROM rutas WHERE id_ruta = ?');
    $buscarRuta->execute([$ruta]);
    $guardada = $buscarRuta->fetch(PDO::FETCH_ASSOC);
    if ($guardada['nombre'] !== $nombre || $guardada['zona'] !== 'Zona prueba' || (int) $guardada['activa'] !== 1) {
        throw new RuntimeException('La ruta nueva no quedó guardada en MySQL.');
    }

    [$estado, $resultado] = solicitarOperacion('actualizar_ruta', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => $ruta, 'nombre' => $nombre . ' editada', 'zona' => 'Zona editada'
    ]);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo editar la ruta.');
    }
    $buscarRuta->execute([$ruta]);
    $guardada = $buscarRuta->fetch(PDO::FETCH_ASSOC);
    if ($guardada['nombre'] !== $nombre . ' editada' || $guardada['zona'] !== 'Zona editada') {
        throw new RuntimeException('La edición de la ruta no quedó guardada en MySQL.');
    }
    [$estado, $resultado] = solicitarOperacion('rutas', 'GET', $sesiones['admin_municipal']);
    $rutasVisibles = array_filter($resultado['rutas'], fn (array $item): bool => (int) $item['id_ruta'] === $ruta);
    if ($estado !== 200 || count($rutasVisibles) !== 1) {
        throw new RuntimeException('La ruta activa no aparece en el listado administrativo.');
    }

    [$estado, $resultado] = solicitarOperacion('crear_parada', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => $ruta, 'id_contenedor' => $contenedor, 'orden' => 1
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo agregar el contenedor a la ruta.');
    }
    $buscarParada = $conexion->prepare('SELECT id_parada, latitud, longitud FROM paradas_ruta WHERE id_ruta = ? AND id_contenedor = ?');
    $buscarParada->execute([$ruta, $contenedor]);
    $parada = $buscarParada->fetch(PDO::FETCH_ASSOC);
    if (!$parada || $parada['latitud'] === null || $parada['longitud'] === null) {
        throw new RuntimeException('La parada no quedó vinculada al contenedor con coordenadas.');
    }
    $idParada = (int) $parada['id_parada'];
    [$estado, $resultado] = solicitarOperacion('rutas', 'GET', $sesiones['admin_municipal']);
    $rutaConParadas = array_values(array_filter(
        $resultado['rutas'],
        fn (array $item): bool => (int) $item['id_ruta'] === $ruta
    ));
    if ($estado !== 200 || (int) ($rutaConParadas[0]['paradas'][0]['id_parada'] ?? 0) !== $idParada) {
        throw new RuntimeException('El listado administrativo no muestra las paradas de la ruta.');
    }

    [$estado, $resultado] = solicitarOperacion('crear_ruta', 'POST', $sesiones['admin_municipal'], [
        'nombre' => $nombre . ' ajena', 'zona' => 'Zona prueba'
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo crear la ruta auxiliar de prueba.');
    }
    $rutaAjena = (int) $resultado['id_ruta'];
    [$estado, $resultado] = solicitarOperacion('crear_parada', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => $rutaAjena, 'id_contenedor' => $contenedor, 'orden' => 1
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo crear la parada auxiliar de prueba.');
    }
    $buscarParada->execute([$rutaAjena, $contenedor]);
    $idParadaAjena = (int) $buscarParada->fetch(PDO::FETCH_ASSOC)['id_parada'];

    $hoy = $conexion->query('SELECT CURDATE()')->fetchColumn();
    [$estado, $resultado] = solicitarOperacion('asignar_ruta', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => $ruta, 'id_camion' => $camion,
        'ci_conductor' => $cuentas['conductor'], 'ci_peon' => $cuentas['peon'], 'fecha' => $hoy
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo asignar la ruta a la cuadrilla.');
    }
    $buscarAsignacion = $conexion->prepare('SELECT id_asignacion FROM asignaciones_ruta WHERE id_ruta = ? AND id_camion = ?');
    $buscarAsignacion->execute([$ruta, $camion]);
    $asignacion = (int) $buscarAsignacion->fetchColumn();
    if ($asignacion <= 0) {
        throw new RuntimeException('La asignación no quedó guardada en MySQL.');
    }

    foreach (['peon', 'conductor'] as $rol) {
        [$estado, $resultado] = solicitarOperacion('mi_ruta', 'GET', $sesiones[$rol]);
        if ($estado !== 200 || (int) ($resultado['asignacion']['id_asignacion'] ?? 0) !== $asignacion
            || (int) ($resultado['asignacion']['paradas'][0]['id_parada'] ?? 0) !== $idParada) {
            throw new RuntimeException('La ruta no llegó correctamente a la cuadrilla.');
        }
    }
    [$estado, $mapa] = solicitarOperacion('mapa_publico');
    $visibles = array_filter($mapa['rutas'], fn (array $item): bool => (int) $item['id_ruta'] === $ruta);
    if ($estado !== 200 || count($visibles) !== 1) {
        throw new RuntimeException('La ruta asignada no aparece en el mapa público.');
    }

    [$estado, $resultado] = solicitarOperacion('completar_parada', 'POST', $sesiones['peon_ajeno'], [
        'id_asignacion' => $asignacion, 'id_parada' => $idParada
    ]);
    if ($estado !== 400 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('Un peón ajeno pudo completar una parada de otra cuadrilla.');
    }
    [$estado, $resultado] = solicitarOperacion('completar_parada', 'POST', $sesiones['peon'], [
        'id_asignacion' => $asignacion, 'id_parada' => $idParadaAjena
    ]);
    if ($estado !== 400 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('Se pudo completar una parada que no pertenece a la ruta asignada.');
    }
    [$estado, $resultado] = solicitarOperacion('completar_parada', 'POST', $sesiones['peon'], [
        'id_asignacion' => $asignacion, 'id_parada' => $idParada
    ]);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('El peón asignado no pudo completar su parada.');
    }
    $contarRecolecciones = $conexion->prepare('SELECT COUNT(*) FROM recolecciones WHERE id_asignacion = ?');
    $contarRecolecciones->execute([$asignacion]);
    if ((int) $contarRecolecciones->fetchColumn() !== 1) {
        throw new RuntimeException('La recolección no se guardó una sola vez en MySQL.');
    }
    [, $resultado] = solicitarOperacion('completar_parada', 'POST', $sesiones['conductor'], [
        'id_asignacion' => $asignacion, 'id_parada' => $idParada
    ]);
    if ($resultado['ok'] ?? true) {
        throw new RuntimeException('La misma parada se pudo completar dos veces.');
    }
    [, $resultado] = solicitarOperacion('mi_ruta', 'GET', $sesiones['conductor']);
    if ((int) ($resultado['asignacion']['paradas'][0]['completada'] ?? 0) !== 1) {
        throw new RuntimeException('El recorrido no muestra la parada completada.');
    }

    [$estado, $resultado] = solicitarOperacion('eliminar_ruta', 'POST', $sesiones['admin_municipal'], ['id_ruta' => $ruta]);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo dar de baja la ruta.');
    }
    $buscarRuta->execute([$ruta]);
    if ((int) $buscarRuta->fetch(PDO::FETCH_ASSOC)['activa'] !== 0) {
        throw new RuntimeException('La baja de la ruta no quedó guardada en MySQL.');
    }
    [, $resultado] = solicitarOperacion('rutas', 'GET', $sesiones['admin_municipal']);
    foreach ($resultado['rutas'] as $item) {
        if ((int) $item['id_ruta'] === $ruta) {
            throw new RuntimeException('La ruta dada de baja sigue disponible para nuevas asignaciones.');
        }
    }
    [, $mapa] = solicitarOperacion('mapa_publico');
    foreach ($mapa['rutas'] as $item) {
        if ((int) $item['id_ruta'] === $ruta) {
            throw new RuntimeException('La ruta dada de baja sigue apareciendo en el mapa.');
        }
    }
} finally {
    if ($asignacion !== null) {
        $eliminarRecolecciones = $conexion->prepare('DELETE FROM recolecciones WHERE id_asignacion = ?');
        $eliminarRecolecciones->execute([$asignacion]);
        $eliminarAsignacion = $conexion->prepare('DELETE FROM asignaciones_ruta WHERE id_asignacion = ?');
        $eliminarAsignacion->execute([$asignacion]);
    }
    $eliminarRuta = $conexion->prepare('DELETE FROM rutas WHERE id_ruta = ?');
    foreach ([$rutaAjena, $ruta] as $idRuta) {
        if ($idRuta !== null) {
            $eliminarRuta->execute([$idRuta]);
        }
    }
    if ($contenedor !== null) {
        $eliminar = $conexion->prepare('DELETE FROM contenedores WHERE id_contenedor = ?');
        $eliminar->execute([$contenedor]);
    }
    if ($camion !== null) {
        $eliminar = $conexion->prepare('DELETE FROM camiones WHERE id_camion = ?');
        $eliminar->execute([$camion]);
    }
    cerrarSesionesDePrueba($sesiones);
    eliminarCuentasPrueba($conexion, $cuentasAjenas);
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Rutas y recolección HTTP: CRUD, asignación, permisos y persistencia correctos\n";
