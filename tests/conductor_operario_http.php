<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (getenv('NOVACYCLE_TEST_ISOLATED') !== '1') {
    fwrite(STDERR, "Ejecutá esta prueba en una base aislada con NOVACYCLE_TEST_ISOLATED=1.\n");
    exit(1);
}

require_once __DIR__ . '/cuentas_prueba.php';
require_once __DIR__ . '/operaciones_http_helper.php';

function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'conductor', 'peon', 'operario']);
$sesiones = [];
$camion = null;
$camionAjeno = null;
$ruta = null;
$asignacion = null;

try {
    foreach ($cuentas as $rol => $ci) {
        $sesiones[$rol] = sesionDePrueba($ci, $rol);
    }

    $insertarCamion = $conexion->prepare(
        "INSERT INTO camiones (matricula, modelo, capacidad_kg, estado)
         VALUES (?, 'Prueba temporal', 1000, 'disponible')"
    );
    $insertarCamion->execute(['T' . strtoupper(bin2hex(random_bytes(4)))]);
    $camion = (int) $conexion->lastInsertId();
    $insertarCamion->execute(['T' . strtoupper(bin2hex(random_bytes(4)))]);
    $camionAjeno = (int) $conexion->lastInsertId();

    $insertarRuta = $conexion->prepare("INSERT INTO rutas (nombre, zona) VALUES (?, 'Prueba')");
    $insertarRuta->execute(['Ruta operario ' . bin2hex(random_bytes(4))]);
    $ruta = (int) $conexion->lastInsertId();

    $insertarAsignacion = $conexion->prepare(
        'INSERT INTO asignaciones_ruta (id_ruta, id_camion, ci_conductor, ci_peon, fecha)
         VALUES (?, ?, ?, ?, CURDATE())'
    );
    $insertarAsignacion->execute([$ruta, $camion, $cuentas['conductor'], $cuentas['peon']]);
    $asignacion = (int) $conexion->lastInsertId();

    $buscarCamion = $conexion->prepare('SELECT estado FROM camiones WHERE id_camion = ?');
    $buscarCamion->execute([$camion]);
    comprobar($buscarCamion->fetchColumn() === 'disponible', 'El camión de prueba no empezó disponible.');

    [$estado, $respuesta] = solicitarOperacion('estado_camion', 'POST', $sesiones['peon'], [
        'id_camion' => $camion, 'estado' => 'en_mantenimiento'
    ]);
    comprobar($estado === 403 && !($respuesta['ok'] ?? true), 'Un peón pudo cambiar el estado del camión.');

    [$estado, $respuesta] = solicitarOperacion('estado_camion', 'POST', $sesiones['conductor'], [
        'id_camion' => $camion, 'estado' => 'estado_inexistente'
    ]);
    comprobar($estado === 400 && !($respuesta['ok'] ?? true), 'Se aceptó un estado de camión inválido.');
    $buscarCamion->execute([$camion]);
    comprobar($buscarCamion->fetchColumn() === 'disponible', 'El intento inválido modificó el camión.');

    [$estado, $respuesta] = solicitarOperacion('estado_camion', 'POST', $sesiones['conductor'], [
        'id_camion' => $camionAjeno, 'estado' => 'en_mantenimiento'
    ]);
    comprobar($estado === 400 && !($respuesta['ok'] ?? true), 'Un conductor pudo modificar un camión ajeno a su ruta de hoy.');
    $buscarCamion->execute([$camionAjeno]);
    comprobar($buscarCamion->fetchColumn() === 'disponible', 'El camión ajeno cambió de estado.');

    [$estado, $respuesta] = solicitarOperacion('estado_camion', 'POST', $sesiones['conductor'], [
        'id_camion' => $camion, 'estado' => 'en_mantenimiento'
    ]);
    comprobar($estado === 200 && ($respuesta['ok'] ?? false), 'El conductor no pudo cambiar el estado del camión.');
    $buscarCamion->execute([$camion]);
    comprobar($buscarCamion->fetchColumn() === 'en_mantenimiento', 'El estado del camión no se guardó.');

    [$estado, $respuesta] = solicitarOperacion('estado_camion', 'POST', $sesiones['conductor'], [
        'id_camion' => $camion, 'estado' => 'disponible'
    ]);
    comprobar($estado === 200 && ($respuesta['ok'] ?? false), 'No se pudo devolver el camión al estado disponible.');

    [$estado, $respuesta] = solicitarOperacion('resumen', 'GET', $sesiones['peon']);
    comprobar($estado === 403 && !($respuesta['ok'] ?? true), 'Un peón pudo consultar el resumen del operario.');

    [$estado, $respuesta] = solicitarOperacion('resumen', 'GET', $sesiones['operario']);
    comprobar($estado === 200 && ($respuesta['ok'] ?? false), 'El operario no pudo consultar el resumen.');
    $resumenInicial = $respuesta['resumen'] ?? [];
    foreach (['ingresos_hoy', 'camiones_hoy', 'incidencias_pendientes'] as $campo) {
        comprobar(isset($resumenInicial[$campo]), "Falta {$campo} en el resumen.");
    }

    [$estado, $respuesta] = solicitarOperacion('ingreso', 'POST', $sesiones['peon'], [
        'id_camion' => $camion, 'tipo_residuo' => 'Reciclaje', 'peso_kg' => 175
    ]);
    comprobar($estado === 403 && !($respuesta['ok'] ?? true), 'Un peón pudo registrar un ingreso de residuos.');

    [$estado, $respuesta] = solicitarOperacion('ingreso', 'POST', $sesiones['operario'], [
        'id_camion' => $camion, 'tipo_residuo' => 'Reciclaje', 'peso_kg' => 175
    ]);
    comprobar($estado === 201 && ($respuesta['ok'] ?? false), 'El operario no pudo registrar el ingreso.');

    $buscarIngreso = $conexion->prepare(
        'SELECT id_camion, tipo_residuo, peso_kg FROM ingresos_residuos WHERE ci_operario = ?'
    );
    $buscarIngreso->execute([$cuentas['operario']]);
    $ingresos = $buscarIngreso->fetchAll(PDO::FETCH_ASSOC);
    comprobar(count($ingresos) === 1, 'El ingreso no quedó guardado exactamente una vez.');
    comprobar(
        (int) $ingresos[0]['id_camion'] === $camion &&
        $ingresos[0]['tipo_residuo'] === 'Reciclaje' &&
        (int) $ingresos[0]['peso_kg'] === 175,
        'El ingreso guardado no coincide con los datos enviados.'
    );

    [$estado, $respuesta] = solicitarOperacion('resumen', 'GET', $sesiones['operario']);
    comprobar($estado === 200 && ($respuesta['ok'] ?? false), 'No se pudo actualizar el resumen.');
    $resumenFinal = $respuesta['resumen'] ?? [];
    comprobar(
        (int) ($resumenFinal['ingresos_hoy'] ?? -1) === (int) $resumenInicial['ingresos_hoy'] + 175,
        'El resumen no sumó el peso ingresado hoy.'
    );
    comprobar(
        (int) ($resumenFinal['camiones_hoy'] ?? -1) === (int) $resumenInicial['camiones_hoy'] + 1,
        'El resumen no contó el camión ingresado hoy.'
    );
    comprobar(
        (int) ($resumenFinal['incidencias_pendientes'] ?? -1) === (int) $resumenInicial['incidencias_pendientes'],
        'El ingreso cambió el total de incidencias pendientes.'
    );

    $datosMaquinaria = [
        'maquinaria' => 'Compactadora de prueba',
        'estado' => 'operativa',
        'observacion' => 'Registro temporal'
    ];
    [$estado, $respuesta] = solicitarOperacion('maquinaria', 'POST', $sesiones['conductor'], $datosMaquinaria);
    comprobar($estado === 403 && !($respuesta['ok'] ?? true), 'Un conductor pudo registrar maquinaria.');

    [$estado, $respuesta] = solicitarOperacion('maquinaria', 'POST', $sesiones['operario'], [
        'maquinaria' => '', 'estado' => 'operativa'
    ]);
    comprobar($estado === 400 && !($respuesta['ok'] ?? true), 'Se aceptó maquinaria sin nombre.');

    [$estado, $respuesta] = solicitarOperacion('maquinaria', 'POST', $sesiones['operario'], $datosMaquinaria);
    comprobar($estado === 200 && ($respuesta['ok'] ?? false), 'El operario no pudo registrar maquinaria.');
    $buscarMaquinaria = $conexion->prepare(
        'SELECT maquinaria, estado, observacion FROM estados_maquinaria WHERE ci_operario = ?'
    );
    $buscarMaquinaria->execute([$cuentas['operario']]);
    $maquinas = $buscarMaquinaria->fetchAll(PDO::FETCH_ASSOC);
    comprobar(count($maquinas) === 1, 'La maquinaria no quedó guardada exactamente una vez.');
    comprobar(
        $maquinas[0]['maquinaria'] === $datosMaquinaria['maquinaria'] &&
        $maquinas[0]['estado'] === $datosMaquinaria['estado'] &&
        $maquinas[0]['observacion'] === $datosMaquinaria['observacion'],
        'El registro de maquinaria no coincide con los datos enviados.'
    );

    [$estado, $respuesta] = solicitarOperacion('ingreso', 'POST', $sesiones['operario'], [
        'id_camion' => $camion, 'tipo_residuo' => 'Reciclaje', 'peso_kg' => 0
    ]);
    comprobar($estado === 400 && !($respuesta['ok'] ?? true), 'Se aceptó un ingreso con peso cero.');
    $buscarIngreso->execute([$cuentas['operario']]);
    comprobar(count($buscarIngreso->fetchAll(PDO::FETCH_ASSOC)) === 1, 'El ingreso inválido llegó a la base.');

    $totalesIniciales = [];
    foreach (['rutas', 'paradas_ruta', 'asignaciones_ruta'] as $tabla) {
        $totalesIniciales[$tabla] = (int) $conexion->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
    }
    [$estado, $respuesta] = solicitarOperacion('crear_ruta', 'POST', $sesiones['admin_municipal'], [
        'nombre' => 'Ruta incompleta', 'zona' => ''
    ]);
    comprobar($estado === 400 && !($respuesta['ok'] ?? true), 'Se aceptó una ruta sin zona.');

    [$estado, $respuesta] = solicitarOperacion('crear_parada', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => 0, 'id_contenedor' => 0
    ]);
    comprobar($estado === 400 && !($respuesta['ok'] ?? true), 'Se aceptó una parada sin ruta ni contenedor.');

    [$estado, $respuesta] = solicitarOperacion('asignar_ruta', 'POST', $sesiones['admin_municipal'], [
        'id_ruta' => $ruta, 'id_camion' => $camion,
        'ci_conductor' => $cuentas['conductor'], 'ci_peon' => $cuentas['peon'], 'fecha' => ''
    ]);
    comprobar($estado === 400 && !($respuesta['ok'] ?? true), 'Se aceptó una asignación sin fecha.');
    foreach ($totalesIniciales as $tabla => $total) {
        comprobar(
            (int) $conexion->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn() === $total,
            "La validación inválida creó datos en {$tabla}."
        );
    }
} finally {
    $eliminar = $conexion->prepare('DELETE FROM ingresos_residuos WHERE ci_operario = ?');
    $eliminar->execute([$cuentas['operario']]);
    $eliminar = $conexion->prepare('DELETE FROM estados_maquinaria WHERE ci_operario = ?');
    $eliminar->execute([$cuentas['operario']]);
    if ($asignacion !== null) {
        $eliminar = $conexion->prepare('DELETE FROM asignaciones_ruta WHERE id_asignacion = ?');
        $eliminar->execute([$asignacion]);
    }
    if ($ruta !== null) {
        $eliminar = $conexion->prepare('DELETE FROM rutas WHERE id_ruta = ?');
        $eliminar->execute([$ruta]);
    }
    if ($camion !== null) {
        $eliminar = $conexion->prepare('DELETE FROM camiones WHERE id_camion = ?');
        $eliminar->execute([$camion]);
    }
    if ($camionAjeno !== null) {
        $eliminar = $conexion->prepare('DELETE FROM camiones WHERE id_camion = ?');
        $eliminar->execute([$camionAjeno]);
    }
    cerrarSesionesDePrueba($sesiones);
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Conductor y operario HTTP: permisos, estado del camión, ingresos, maquinaria y resumen correctos\n";
