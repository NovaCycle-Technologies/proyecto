<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';
require_once __DIR__ . '/cuentas_prueba.php';

function solicitarCamion(string $accion, string $metodo, string $pestana, string $sesion, int $id = 0, array $datos = []): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Camiones/api_camiones.php?accion=' . $accion;
    if ($id > 0) {
        $url .= '&id=' . $id;
    }
    $cabeceras = 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $sesion . "\r\n"
        . 'X-NovaCycle-Tab: ' . $pestana . "\r\n";
    $contenido = '';
    if ($metodo === 'PUT') {
        $cabeceras .= "Content-Type: application/json\r\n";
        $contenido = json_encode($datos, JSON_THROW_ON_ERROR);
    } elseif ($metodo === 'POST') {
        $cabeceras .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $contenido = http_build_query($datos);
    }
    $contexto = stream_context_create(['http' => [
        'method' => $metodo,
        'header' => $cabeceras,
        'content' => $contenido,
        'ignore_errors' => true,
        'timeout' => 5
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo conectar con la API de camiones.');
    }
    $estado = (int) substr($http_response_header[0] ?? '', 9, 3);
    return [$estado, json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR)];
}

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal']);
$pestana = crearSesionUsuario(['ci' => $cuentas['admin_municipal'], 'rol' => 'admin_municipal']);
$sesion = session_id();
$idCamion = null;
$matricula = 'T' . strtoupper(bin2hex(random_bytes(4)));

try {
    [$estado, $resultado] = solicitarCamion('crear', 'POST', $pestana, $sesion, 0, [
        'matricula' => $matricula,
        'modelo' => 'Prueba temporal',
        'capacidad_kg' => 1000,
        'estado' => 'disponible'
    ]);
    if ($estado !== 201 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo crear el camión de prueba.');
    }
    $idCamion = (int) $resultado['id_camion'];

    [$estado, $resultado] = solicitarCamion('actualizar', 'PUT', $pestana, $sesion, $idCamion, [
        'matricula' => $matricula,
        'modelo' => 'Modelo editado',
        'capacidad_kg' => 1500,
        'estado' => 'en_mantenimiento'
    ]);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo editar el camión temporal.');
    }
    $consultarCamion = $conexion->prepare('SELECT modelo, capacidad_kg, estado, activo FROM camiones WHERE id_camion = ?');
    $consultarCamion->execute([$idCamion]);
    $guardado = $consultarCamion->fetch(PDO::FETCH_ASSOC);
    if ($guardado['modelo'] !== 'Modelo editado' || (int) $guardado['capacidad_kg'] !== 1500
        || $guardado['estado'] !== 'en_mantenimiento' || (int) $guardado['activo'] !== 1) {
        throw new RuntimeException('La edición del camión no se guardó en MySQL.');
    }

    [$estado, $resultado] = solicitarCamion('baja', 'DELETE', $pestana, $sesion, $idCamion);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo dar de baja el camión temporal.');
    }
    $consultarCamion->execute([$idCamion]);
    $guardado = $consultarCamion->fetch(PDO::FETCH_ASSOC);
    if ((int) $guardado['activo'] !== 0) {
        throw new RuntimeException('La baja lógica del camión no se guardó en MySQL.');
    }
    [$estado, $listado] = solicitarCamion('listar', 'GET', $pestana, $sesion);
    foreach ($listado['camiones'] as $camion) {
        if ((int) $camion['id_camion'] === $idCamion) {
            throw new RuntimeException('El camión dado de baja sigue apareciendo en la API.');
        }
    }

    [$estado, $resultado] = solicitarCamion('crear', 'POST', $pestana, $sesion, 0, [
        'matricula' => $matricula,
        'modelo' => 'Modelo reactivado',
        'capacidad_kg' => 2000,
        'estado' => 'disponible'
    ]);
    if ($estado !== 200 || !($resultado['ok'] ?? false) || !($resultado['reactivado'] ?? false)
        || (int) $resultado['id_camion'] !== $idCamion) {
        throw new RuntimeException('No se reactivó el mismo camión al registrar de nuevo su matrícula.');
    }
    $consultarCamion->execute([$idCamion]);
    $guardado = $consultarCamion->fetch(PDO::FETCH_ASSOC);
    if ($guardado['modelo'] !== 'Modelo reactivado' || (int) $guardado['capacidad_kg'] !== 2000
        || $guardado['estado'] !== 'disponible' || (int) $guardado['activo'] !== 1) {
        throw new RuntimeException('La reactivación no actualizó los datos del camión en MySQL.');
    }
    $contarMatricula = $conexion->prepare('SELECT COUNT(*) FROM camiones WHERE matricula = ?');
    $contarMatricula->execute([$matricula]);
    if ((int) $contarMatricula->fetchColumn() !== 1) {
        throw new RuntimeException('La reactivación creó un camión duplicado.');
    }
    [$estado, $listado] = solicitarCamion('listar', 'GET', $pestana, $sesion);
    $visibles = array_filter($listado['camiones'], fn (array $camion): bool => (int) $camion['id_camion'] === $idCamion);
    if ($estado !== 200 || count($visibles) !== 1) {
        throw new RuntimeException('El camión reactivado no aparece en la API.');
    }

    [$estado, $resultado] = solicitarCamion('crear', 'POST', $pestana, $sesion, 0, [
        'matricula' => $matricula,
        'modelo' => 'Duplicado',
        'capacidad_kg' => 3000,
        'estado' => 'disponible'
    ]);
    if ($estado !== 400 || ($resultado['ok'] ?? true)) {
        throw new RuntimeException('Se permitió duplicar una matrícula activa.');
    }
    $consultarCamion->execute([$idCamion]);
    if ($consultarCamion->fetch(PDO::FETCH_ASSOC)['modelo'] !== 'Modelo reactivado') {
        throw new RuntimeException('El intento de duplicado modificó el camión activo.');
    }
} finally {
    if ($idCamion !== null) {
        $eliminarCamion = $conexion->prepare('DELETE FROM camiones WHERE id_camion = ?');
        $eliminarCamion->execute([$idCamion]);
    }
    session_name(nombreSesionPestana($pestana));
    session_id($sesion);
    session_start();
    session_destroy();
    session_write_close();
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Camiones HTTP: edición, baja y reactivación verificadas en MySQL\n";
