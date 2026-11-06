<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/cuentas_prueba.php';
require_once __DIR__ . '/operaciones_http_helper.php';

function solicitarCamionAsignado(string $accion, string $metodo, array $sesion, int $id = 0, array $datos = []): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Camiones/api_camiones.php?accion=' . $accion;
    if ($id > 0) {
        $url .= '&id=' . $id;
    }
    [$pestana, $idSesion] = $sesion;
    $cabeceras = 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $idSesion . "\r\n"
        . 'X-NovaCycle-Tab: ' . $pestana . "\r\n";
    $contenido = '';
    if ($metodo === 'PUT') {
        $cabeceras .= "Content-Type: application/json\r\n";
        $contenido = json_encode($datos, JSON_THROW_ON_ERROR);
    }

    $contexto = stream_context_create(['http' => [
        'method' => $metodo,
        'header' => $cabeceras,
        'content' => $contenido,
        'ignore_errors' => true,
        'timeout' => 5,
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo consultar la API de camiones.');
    }
    $estado = (int) substr($http_response_header[0] ?? '', 9, 3);
    return [$estado, json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR)];
}

function comprobarCamionAsignado(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'conductor', 'peon']);
$sesiones = [];
$camiones = [];
$ruta = null;
$asignacion = null;

try {
    foreach ($cuentas as $rol => $ci) {
        $sesiones[$rol] = sesionDePrueba($ci, $rol);
    }

    $crearCamion = $conexion->prepare(
        "INSERT INTO camiones (matricula, modelo, capacidad_kg, estado)
         VALUES (?, 'Modelo inicial', 1000, 'disponible')"
    );
    $matriculaAsignado = 'T' . strtoupper(bin2hex(random_bytes(5)));
    $matriculaLibre = 'T' . strtoupper(bin2hex(random_bytes(5)));
    $crearCamion->execute([$matriculaAsignado]);
    $camiones['asignado'] = (int) $conexion->lastInsertId();
    $crearCamion->execute([$matriculaLibre]);
    $camiones['libre'] = (int) $conexion->lastInsertId();

    $crearRuta = $conexion->prepare("INSERT INTO rutas (nombre, zona) VALUES (?, 'Prueba')");
    $crearRuta->execute(['Ruta bloqueo camión ' . bin2hex(random_bytes(4))]);
    $ruta = (int) $conexion->lastInsertId();

    $crearAsignacion = $conexion->prepare(
        'INSERT INTO asignaciones_ruta (id_ruta, id_camion, ci_conductor, ci_peon, fecha, estado)
         VALUES (?, ?, ?, ?, CURDATE(), ?)'
    );
    $crearAsignacion->execute([$ruta, $camiones['asignado'], $cuentas['conductor'], $cuentas['peon'], 'asignada']);
    $asignacion = (int) $conexion->lastInsertId();

    [$estado, $listado] = solicitarCamionAsignado('listar', 'GET', $sesiones['admin_municipal']);
    comprobarCamionAsignado($estado === 200 && ($listado['ok'] ?? false), 'No se pudo listar la flota.');
    $enUso = [];
    foreach ($listado['camiones'] as $camion) {
        $id = (int) $camion['id_camion'];
        if (in_array($id, $camiones, true)) {
            $enUso[$id] = (int) $camion['en_uso'];
        }
    }
    comprobarCamionAsignado(
        ($enUso[$camiones['asignado']] ?? -1) === 1 && ($enUso[$camiones['libre']] ?? -1) === 0,
        'La API no distinguió el camión asignado del libre.'
    );

    $cambioAsignado = [
        'matricula' => $matriculaAsignado, 'modelo' => 'No debe guardarse',
        'capacidad_kg' => 1500, 'estado' => 'en_mantenimiento',
    ];
    [$estado, $resultado] = solicitarCamionAsignado(
        'actualizar', 'PUT', $sesiones['admin_municipal'], $camiones['asignado'], $cambioAsignado
    );
    comprobarCamionAsignado(
        $estado === 409 && !($resultado['ok'] ?? true) && ($resultado['en_uso'] ?? false),
        'El administrador pudo editar un camión asignado a una ruta vigente.'
    );

    [$estado, $resultado] = solicitarCamionAsignado(
        'baja', 'DELETE', $sesiones['admin_municipal'], $camiones['asignado']
    );
    comprobarCamionAsignado(
        $estado === 409 && !($resultado['ok'] ?? true) && ($resultado['en_uso'] ?? false),
        'El administrador pudo dar de baja un camión asignado a una ruta vigente.'
    );

    $buscarCamion = $conexion->prepare('SELECT modelo, capacidad_kg, estado, activo FROM camiones WHERE id_camion = ?');
    $buscarCamion->execute([$camiones['asignado']]);
    $guardado = $buscarCamion->fetch(PDO::FETCH_ASSOC);
    comprobarCamionAsignado(
        $guardado['modelo'] === 'Modelo inicial' && (int) $guardado['capacidad_kg'] === 1000
        && $guardado['estado'] === 'disponible' && (int) $guardado['activo'] === 1,
        'El intento bloqueado modificó el camión asignado en la base.'
    );

    [$estado, $resultado] = solicitarCamionAsignado(
        'actualizar', 'PUT', $sesiones['peon'], $camiones['libre'], [
            'matricula' => $matriculaLibre, 'modelo' => 'Sin permiso',
            'capacidad_kg' => 1200, 'estado' => 'disponible',
        ]
    );
    comprobarCamionAsignado($estado === 403 && !($resultado['ok'] ?? true), 'Un peón pudo editar la flota.');

    [$estado, $resultado] = solicitarCamionAsignado(
        'actualizar', 'PUT', $sesiones['admin_municipal'], $camiones['libre'], [
            'matricula' => $matriculaLibre, 'modelo' => 'Modelo libre editado',
            'capacidad_kg' => 1200, 'estado' => 'en_mantenimiento',
        ]
    );
    comprobarCamionAsignado($estado === 200 && ($resultado['ok'] ?? false), 'No se pudo editar el camión libre.');
    $buscarCamion->execute([$camiones['libre']]);
    $guardado = $buscarCamion->fetch(PDO::FETCH_ASSOC);
    comprobarCamionAsignado(
        $guardado['modelo'] === 'Modelo libre editado' && (int) $guardado['capacidad_kg'] === 1200
        && $guardado['estado'] === 'en_mantenimiento' && (int) $guardado['activo'] === 1,
        'La edición del camión libre no se guardó en la base.'
    );

    [$estado, $resultado] = solicitarCamionAsignado(
        'baja', 'DELETE', $sesiones['admin_municipal'], $camiones['libre']
    );
    comprobarCamionAsignado($estado === 200 && ($resultado['ok'] ?? false), 'No se pudo dar de baja el camión libre.');
    $buscarCamion->execute([$camiones['libre']]);
    comprobarCamionAsignado((int) $buscarCamion->fetch(PDO::FETCH_ASSOC)['activo'] === 0, 'La baja del camión libre no se guardó.');

    $completarAsignacion = $conexion->prepare("UPDATE asignaciones_ruta SET estado = 'completada' WHERE id_asignacion = ?");
    $completarAsignacion->execute([$asignacion]);
    [$estado, $resultado] = solicitarCamionAsignado(
        'actualizar', 'PUT', $sesiones['admin_municipal'], $camiones['asignado'], [
            'matricula' => $matriculaAsignado, 'modelo' => 'Modelo liberado',
            'capacidad_kg' => 1500, 'estado' => 'disponible',
        ]
    );
    comprobarCamionAsignado($estado === 200 && ($resultado['ok'] ?? false), 'La ruta terminada no liberó el camión.');
    $buscarCamion->execute([$camiones['asignado']]);
    comprobarCamionAsignado(
        $buscarCamion->fetch(PDO::FETCH_ASSOC)['modelo'] === 'Modelo liberado',
        'El cambio del camión liberado no se guardó.'
    );
} finally {
    if ($asignacion !== null) {
        $conexion->prepare('DELETE FROM asignaciones_ruta WHERE id_asignacion = ?')->execute([$asignacion]);
    }
    if ($ruta !== null) {
        $conexion->prepare('DELETE FROM rutas WHERE id_ruta = ?')->execute([$ruta]);
    }
    foreach ($camiones as $idCamion) {
        $conexion->prepare('DELETE FROM camiones WHERE id_camion = ?')->execute([$idCamion]);
    }
    cerrarSesionesDePrueba($sesiones);
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Camión asignado HTTP: bloqueo, liberación y persistencia correctos\n";
