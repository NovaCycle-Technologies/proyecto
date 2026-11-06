<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';
require_once __DIR__ . '/cuentas_prueba.php';

function solicitarContenedor(string $accion, string $metodo, string $pestana, string $sesion, int $id = 0, array $datos = []): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Gestion/api_gestion.php?accion=' . $accion;
    if ($id > 0) {
        $url .= '&id=' . $id;
    }
    $contexto = stream_context_create(['http' => [
        'method' => $metodo,
        'header' => 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $sesion . "\r\n"
            . 'X-NovaCycle-Tab: ' . $pestana . "\r\n"
            . "Content-Type: application/json\r\n",
        'content' => $metodo === 'PUT' ? json_encode($datos, JSON_THROW_ON_ERROR) : '',
        'ignore_errors' => true,
        'timeout' => 5
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo conectar con la API de contenedores.');
    }
    $estado = (int) substr($http_response_header[0] ?? '', 9, 3);
    return [$estado, json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR)];
}

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal']);
$pestana = crearSesionUsuario(['ci' => $cuentas['admin_municipal'], 'rol' => 'admin_municipal']);
$sesion = session_id();
$contenedor = null;
$ruta = null;

try {
    $agregarContenedor = $conexion->prepare(
        "INSERT INTO contenedores (calle, numero, estado, tipo_residuo, capacidad_litros, latitud, longitud)
         VALUES ('Prueba temporal', '1', 'funcional', 'Reciclaje', 800, -34.9000, -56.2000)"
    );
    $agregarContenedor->execute();
    $contenedor = (int) $conexion->lastInsertId();

    [$estado, $listado] = solicitarContenedor('listar', 'GET', $pestana, $sesion);
    $elementos = array_values(array_filter($listado['contenedores'], fn (array $item): bool => (int) $item['id_contenedor'] === $contenedor));
    if ($estado !== 200 || count($elementos) !== 1 || (int) $elementos[0]['en_uso'] !== 0) {
        throw new RuntimeException('El contenedor libre no se mostró correctamente.');
    }

    $conexion->exec("INSERT INTO rutas (nombre, zona) VALUES ('Ruta prueba temporal', 'Prueba')");
    $ruta = (int) $conexion->lastInsertId();
    $agregarParada = $conexion->prepare(
        "INSERT INTO paradas_ruta (id_ruta, id_contenedor, ubicacion, descripcion, orden, latitud, longitud)
         VALUES (?, ?, 'Prueba temporal 1', 'Reciclaje', 1, -34.9000, -56.2000)"
    );
    $agregarParada->execute([$ruta, $contenedor]);

    [$estado, $listado] = solicitarContenedor('listar', 'GET', $pestana, $sesion);
    $elementos = array_values(array_filter($listado['contenedores'], fn (array $item): bool => (int) $item['id_contenedor'] === $contenedor));
    if ($estado !== 200 || count($elementos) !== 1 || (int) $elementos[0]['en_uso'] !== 1) {
        throw new RuntimeException('El listado no marcó el contenedor como incluido en una ruta.');
    }

    $cambios = [
        'calle' => 'Calle modificada', 'numero' => '2', 'estado' => 'funcional',
        'tipo_residuo' => 'Reciclaje', 'capacidad_litros' => 800,
        'latitud' => -34.9000, 'longitud' => -56.2000
    ];
    [$estado, $resultado] = solicitarContenedor('actualizar', 'PUT', $pestana, $sesion, $contenedor, $cambios);
    if ($estado !== 409 || !($resultado['en_uso'] ?? false)) {
        throw new RuntimeException('La API permitió editar un contenedor en una ruta activa.');
    }
    [$estado, $resultado] = solicitarContenedor('baja', 'DELETE', $pestana, $sesion, $contenedor);
    if ($estado !== 409 || !($resultado['en_uso'] ?? false)) {
        throw new RuntimeException('La API permitió dar de baja un contenedor en una ruta activa.');
    }

    $desactivarRuta = $conexion->prepare('UPDATE rutas SET activa = 0 WHERE id_ruta = ?');
    $desactivarRuta->execute([$ruta]);
    [$estado, $resultado] = solicitarContenedor('actualizar', 'PUT', $pestana, $sesion, $contenedor, $cambios);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo editar el contenedor después de desactivar la ruta.');
    }
    $consultarContenedor = $conexion->prepare('SELECT calle, numero, activo FROM contenedores WHERE id_contenedor = ?');
    $consultarContenedor->execute([$contenedor]);
    $guardado = $consultarContenedor->fetch(PDO::FETCH_ASSOC);
    if ($guardado['calle'] !== 'Calle modificada' || $guardado['numero'] !== '2' || (int) $guardado['activo'] !== 1) {
        throw new RuntimeException('La edición del contenedor no se guardó en MySQL.');
    }
    [$estado, $resultado] = solicitarContenedor('baja', 'DELETE', $pestana, $sesion, $contenedor);
    if ($estado !== 200 || !($resultado['ok'] ?? false)) {
        throw new RuntimeException('No se pudo dar de baja el contenedor después de desactivar la ruta.');
    }
    $consultarContenedor->execute([$contenedor]);
    $guardado = $consultarContenedor->fetch(PDO::FETCH_ASSOC);
    if ((int) $guardado['activo'] !== 0) {
        throw new RuntimeException('La baja lógica del contenedor no se guardó en MySQL.');
    }
    [$estado, $listado] = solicitarContenedor('listar', 'GET', $pestana, $sesion);
    foreach ($listado['contenedores'] as $item) {
        if ((int) $item['id_contenedor'] === $contenedor) {
            throw new RuntimeException('El contenedor dado de baja sigue apareciendo en la API.');
        }
    }
} finally {
    if ($ruta !== null) {
        $eliminarRuta = $conexion->prepare('DELETE FROM rutas WHERE id_ruta = ?');
        $eliminarRuta->execute([$ruta]);
    }
    if ($contenedor !== null) {
        $eliminarContenedor = $conexion->prepare('DELETE FROM contenedores WHERE id_contenedor = ?');
        $eliminarContenedor->execute([$contenedor]);
    }
    session_name(nombreSesionPestana($pestana));
    session_id($sesion);
    session_start();
    session_destroy();
    session_write_close();
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Contenedores en rutas activas: bloqueo y liberación correctos\n";
