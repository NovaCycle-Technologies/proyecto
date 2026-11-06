<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/cuentas_prueba.php';
require_once __DIR__ . '/operaciones_http_helper.php';

function comprobarDashboard(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function contieneId(array $elementos, string $campo, int $id): bool
{
    foreach ($elementos as $elemento) {
        if ((int) ($elemento[$campo] ?? 0) === $id) {
            return true;
        }
    }
    return false;
}

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'peon', 'conductor', 'operario']);
$sesiones = [];
$camion = null;
$ruta = null;
$asignacion = null;
$incidencia = null;
$ingreso = null;
$recoleccion = null;
$contenedores = [];

try {
    foreach ($cuentas as $rol => $ci) {
        $sesiones[$rol] = sesionDePrueba($ci, $rol);
    }

    [$estado, $respuesta] = solicitarOperacion('dashboard_admin', 'GET', $sesiones['peon']);
    comprobarDashboard($estado === 403 && !($respuesta['ok'] ?? true), 'El peón pudo ver el dashboard administrativo.');

    [$estado, $respuesta] = solicitarOperacion('dashboard_admin', 'GET', $sesiones['admin_municipal']);
    comprobarDashboard($estado === 200 && ($respuesta['ok'] ?? false), 'No se pudo leer el dashboard inicial.');
    $antes = $respuesta['dashboard'];

    $crearCamion = $conexion->prepare(
        "INSERT INTO camiones (matricula, modelo, capacidad_kg, estado)
         VALUES (?, 'Prueba dashboard', 1000, 'disponible')"
    );
    $crearCamion->execute(['T' . strtoupper(bin2hex(random_bytes(4)))]);
    $camion = (int) $conexion->lastInsertId();

    $crearContenedor = $conexion->prepare(
        "INSERT INTO contenedores (calle, numero, estado, tipo_residuo, capacidad_litros, latitud, longitud)
         VALUES ('Prueba dashboard', ?, ?, 'Reciclaje', 800, -34.9000, -56.2000)"
    );
    foreach (['funcional', 'roto', 'desbordado'] as $indice => $estadoContenedor) {
        $crearContenedor->execute([(string) ($indice + 1), $estadoContenedor]);
        $contenedores[$estadoContenedor] = (int) $conexion->lastInsertId();
    }

    $crearRuta = $conexion->prepare("INSERT INTO rutas (nombre, zona) VALUES (?, 'Prueba')");
    $crearRuta->execute(['Ruta dashboard ' . bin2hex(random_bytes(4))]);
    $ruta = (int) $conexion->lastInsertId();
    $crearParada = $conexion->prepare(
        "INSERT INTO paradas_ruta (id_ruta, id_contenedor, ubicacion, descripcion, orden, latitud, longitud)
         VALUES (?, ?, 'Prueba dashboard 3', 'Reciclaje', 1, -34.9000, -56.2000)"
    );
    $crearParada->execute([$ruta, $contenedores['desbordado']]);
    $parada = (int) $conexion->lastInsertId();

    $crearAsignacion = $conexion->prepare(
        "INSERT INTO asignaciones_ruta (id_ruta, id_camion, ci_conductor, ci_peon, fecha)
         VALUES (?, ?, ?, ?, CURDATE())"
    );
    $crearAsignacion->execute([$ruta, $camion, $cuentas['conductor'], $cuentas['peon']]);
    $asignacion = (int) $conexion->lastInsertId();

    $crearRecoleccion = $conexion->prepare(
        'INSERT INTO recolecciones (id_asignacion, id_parada, ci_trabajador) VALUES (?, ?, ?)'
    );
    $crearRecoleccion->execute([$asignacion, $parada, $cuentas['peon']]);
    $recoleccion = (int) $conexion->lastInsertId();

    $crearIngreso = $conexion->prepare(
        "INSERT INTO ingresos_residuos (id_camion, ci_operario, tipo_residuo, peso_kg)
         VALUES (?, ?, 'Reciclaje', 500)"
    );
    $crearIngreso->execute([$camion, $cuentas['operario']]);
    $ingreso = (int) $conexion->lastInsertId();

    $crearIncidencia = $conexion->prepare(
        "INSERT INTO incidencias (id_contenedor, ci_reportante, ubicacion, tipo, detalle)
         VALUES (?, ?, 'Prueba dashboard 3', 'Desborde', 'Prueba temporal')"
    );
    $crearIncidencia->execute([$contenedores['desbordado'], $cuentas['peon']]);
    $incidencia = (int) $conexion->lastInsertId();

    [$estado, $respuesta] = solicitarOperacion('dashboard_admin', 'GET', $sesiones['admin_municipal']);
    comprobarDashboard($estado === 200 && ($respuesta['ok'] ?? false), 'No se pudo leer el dashboard actualizado.');
    $despues = $respuesta['dashboard'];

    foreach (['rutas_hoy' => 1, 'recolecciones_hoy' => 1, 'incidencias_pendientes' => 1, 'ingresos_hoy' => 500] as $campo => $aumento) {
        comprobarDashboard(
            (int) $despues['jornada'][$campo] === (int) $antes['jornada'][$campo] + $aumento,
            "La métrica {$campo} del dashboard no refleja el dato temporal."
        );
    }
    foreach (['funcional', 'roto', 'desbordado'] as $estadoContenedor) {
        comprobarDashboard(
            (int) $despues['contenedores'][$estadoContenedor] === (int) $antes['contenedores'][$estadoContenedor] + 1,
            "El dashboard no contó el contenedor {$estadoContenedor}."
        );
    }
    comprobarDashboard(
        (int) $despues['camiones']['disponible'] === (int) $antes['camiones']['disponible'] + 1,
        'El dashboard no contó el camión disponible.'
    );

    // La interfaz construye sus tres alertas con estos contadores; aquí se prueban los datos de origen.
    comprobarDashboard(
        (int) $despues['jornada']['incidencias_pendientes'] > 0
            && (int) $despues['contenedores']['roto'] > 0
            && (int) $despues['contenedores']['desbordado'] > 0,
        'Faltan contadores para las alertas del administrador.'
    );

    [$estado, $mapa] = solicitarOperacion('mapa_publico');
    comprobarDashboard($estado === 200 && ($mapa['ok'] ?? false), 'El mapa público no respondió.');
    comprobarDashboard(
        contieneId($mapa['rutas'], 'id_ruta', $ruta)
            && contieneId($mapa['contenedores'], 'id_contenedor', $contenedores['desbordado'])
            && contieneId($mapa['incidencias'], 'id_incidencia', $incidencia),
        'El mapa público no muestra la ruta, el contenedor y la incidencia abiertos.'
    );

    $cerrarIncidencia = $conexion->prepare("UPDATE incidencias SET estado = 'cerrada', fecha_cierre = NOW() WHERE id_incidencia = ?");
    $cerrarIncidencia->execute([$incidencia]);

    [$estado, $respuesta] = solicitarOperacion('dashboard_admin', 'GET', $sesiones['admin_municipal']);
    comprobarDashboard(
        $estado === 200 && (int) $respuesta['dashboard']['jornada']['incidencias_pendientes']
            === (int) $antes['jornada']['incidencias_pendientes'],
        'La alerta de incidencia siguió contando un caso cerrado.'
    );
    [, $mapa] = solicitarOperacion('mapa_publico');
    comprobarDashboard(
        !contieneId($mapa['incidencias'], 'id_incidencia', $incidencia),
        'El mapa público siguió mostrando una incidencia cerrada.'
    );
} finally {
    if ($recoleccion !== null) {
        $conexion->prepare('DELETE FROM recolecciones WHERE id_recoleccion = ?')->execute([$recoleccion]);
    }
    if ($ingreso !== null) {
        $conexion->prepare('DELETE FROM ingresos_residuos WHERE id_ingreso = ?')->execute([$ingreso]);
    }
    if ($incidencia !== null) {
        $conexion->prepare('DELETE FROM incidencias WHERE id_incidencia = ?')->execute([$incidencia]);
    }
    if ($asignacion !== null) {
        $conexion->prepare('DELETE FROM asignaciones_ruta WHERE id_asignacion = ?')->execute([$asignacion]);
    }
    if ($ruta !== null) {
        $conexion->prepare('DELETE FROM rutas WHERE id_ruta = ?')->execute([$ruta]);
    }
    foreach ($contenedores as $idContenedor) {
        $conexion->prepare('DELETE FROM contenedores WHERE id_contenedor = ?')->execute([$idContenedor]);
    }
    if ($camion !== null) {
        $conexion->prepare('DELETE FROM camiones WHERE id_camion = ?')->execute([$camion]);
    }
    cerrarSesionesDePrueba($sesiones);
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Dashboard HTTP: métricas, alertas de origen y mapa público correctos\n";
