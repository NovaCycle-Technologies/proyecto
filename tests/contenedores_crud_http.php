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

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';
require_once __DIR__ . '/cuentas_prueba.php';

function solicitarCrudContenedor(string $accion, string $metodo, array $sesion, int $id = 0, array $datos = []): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Gestion/api_gestion.php?accion=' . $accion;
    if ($id > 0) {
        $url .= '&id=' . $id;
    }

    [$pestana, $idSesion] = $sesion;
    $cabeceras = 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $idSesion . "\r\n"
        . 'X-NovaCycle-Tab: ' . $pestana . "\r\n";
    $contenido = '';
    if ($metodo === 'POST') {
        $cabeceras .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $contenido = http_build_query($datos);
    } elseif ($metodo === 'PUT') {
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
        throw new RuntimeException('No se pudo consultar la API de contenedores.');
    }
    $estado = (int) substr($http_response_header[0] ?? '', 9, 3);
    return [$estado, json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR)];
}

function comprobarCrudContenedor(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'peon']);
$sesiones = [];
$idContenedor = null;
$calle = 'QA HTTP ' . strtoupper(bin2hex(random_bytes(4)));

try {
    foreach ($cuentas as $rol => $ci) {
        $pestana = crearSesionUsuario(['ci' => $ci, 'rol' => $rol]);
        $sesiones[$rol] = [$pestana, session_id()];
    }

    $datos = [
        'calle' => ' ' . $calle . ' ',
        'numero' => ' 12 ',
        'estado' => 'funcional',
        'tipo_residuo' => ' Reciclaje ',
        'capacidad_litros' => 800,
        'latitud' => -34.9000,
        'longitud' => -56.2000,
    ];
    $buscar = $conexion->prepare('SELECT COUNT(*) FROM contenedores WHERE calle = ?');

    [$estado, $respuesta] = solicitarCrudContenedor('crear', 'POST', $sesiones['peon'], 0, $datos);
    comprobarCrudContenedor($estado === 403 && !($respuesta['ok'] ?? true), 'Un peón pudo crear un contenedor.');

    foreach ([
        ['calle' => ''],
        ['capacidad_litros' => 0],
        ['latitud' => -33.0],
        ['estado' => 'inexistente'],
    ] as $cambioInvalido) {
        [$estado, $respuesta] = solicitarCrudContenedor(
            'crear', 'POST', $sesiones['admin_municipal'], 0, array_replace($datos, $cambioInvalido)
        );
        comprobarCrudContenedor($estado === 400 && !($respuesta['ok'] ?? true), 'La API aceptó un contenedor inválido.');
    }
    $buscar->execute([$calle]);
    comprobarCrudContenedor((int) $buscar->fetchColumn() === 0, 'Una creación inválida llegó a MySQL.');

    [$estado, $respuesta] = solicitarCrudContenedor('crear', 'POST', $sesiones['admin_municipal'], 0, $datos);
    comprobarCrudContenedor($estado === 201 && ($respuesta['ok'] ?? false), 'El administrador no pudo crear el contenedor.');
    $idContenedor = (int) ($respuesta['id_contenedor'] ?? 0);
    comprobarCrudContenedor($idContenedor > 0, 'La API no devolvió el ID creado.');

    $consultar = $conexion->prepare(
        'SELECT calle, numero, estado, tipo_residuo, capacidad_litros, activo FROM contenedores WHERE id_contenedor = ?'
    );
    $consultar->execute([$idContenedor]);
    $guardado = $consultar->fetch(PDO::FETCH_ASSOC);
    comprobarCrudContenedor(
        $guardado && $guardado['calle'] === $calle && $guardado['numero'] === '12'
            && $guardado['tipo_residuo'] === 'Reciclaje' && (int) $guardado['capacidad_litros'] === 800
            && (int) $guardado['activo'] === 1,
        'El contenedor no se guardó correctamente en MySQL.'
    );

    [$estado, $respuesta] = solicitarCrudContenedor('obtener', 'GET', $sesiones['admin_municipal'], $idContenedor);
    comprobarCrudContenedor(
        $estado === 200 && ($respuesta['ok'] ?? false)
            && (int) ($respuesta['contenedor']['id_contenedor'] ?? 0) === $idContenedor,
        'La API no obtuvo el contenedor creado.'
    );
    [$estado, $respuesta] = solicitarCrudContenedor('listar', 'GET', $sesiones['peon']);
    $coincidencias = array_filter(
        $respuesta['contenedores'] ?? [],
        fn (array $item): bool => (int) $item['id_contenedor'] === $idContenedor
    );
    comprobarCrudContenedor($estado === 200 && ($respuesta['ok'] ?? false) && count($coincidencias) === 1,
        'El contenedor creado no aparece en el listado.');

    $editado = array_replace($datos, ['calle' => 'Calle editada', 'numero' => '24', 'estado' => 'roto']);
    [$estado, $respuesta] = solicitarCrudContenedor('actualizar', 'PUT', $sesiones['peon'], $idContenedor, $editado);
    comprobarCrudContenedor($estado === 403 && !($respuesta['ok'] ?? true), 'Un peón pudo editar un contenedor.');
    [$estado, $respuesta] = solicitarCrudContenedor('actualizar', 'PUT', $sesiones['admin_municipal'], $idContenedor,
        array_replace($editado, ['latitud' => 0]));
    comprobarCrudContenedor($estado === 400 && !($respuesta['ok'] ?? true), 'Se aceptó una edición con coordenadas inválidas.');
    [$estado, $respuesta] = solicitarCrudContenedor('actualizar', 'PUT', $sesiones['admin_municipal'], $idContenedor, $editado);
    comprobarCrudContenedor($estado === 200 && ($respuesta['ok'] ?? false), 'No se pudo editar el contenedor.');
    $consultar->execute([$idContenedor]);
    $guardado = $consultar->fetch(PDO::FETCH_ASSOC);
    comprobarCrudContenedor($guardado['calle'] === 'Calle editada' && $guardado['numero'] === '24'
        && $guardado['estado'] === 'roto', 'La edición no quedó guardada en MySQL.');

    [$estado, $respuesta] = solicitarCrudContenedor('baja', 'DELETE', $sesiones['peon'], $idContenedor);
    comprobarCrudContenedor($estado === 403 && !($respuesta['ok'] ?? true), 'Un peón pudo dar de baja un contenedor.');
    [$estado, $respuesta] = solicitarCrudContenedor('baja', 'DELETE', $sesiones['admin_municipal'], $idContenedor);
    comprobarCrudContenedor($estado === 200 && ($respuesta['ok'] ?? false), 'No se pudo dar de baja el contenedor.');
    $consultar->execute([$idContenedor]);
    comprobarCrudContenedor((int) $consultar->fetch(PDO::FETCH_ASSOC)['activo'] === 0,
        'La baja lógica no quedó guardada en MySQL.');
    [$estado, $respuesta] = solicitarCrudContenedor('obtener', 'GET', $sesiones['admin_municipal'], $idContenedor);
    comprobarCrudContenedor($estado === 404 && !($respuesta['ok'] ?? true), 'Un contenedor dado de baja sigue disponible.');
    [$estado, $respuesta] = solicitarCrudContenedor('baja', 'DELETE', $sesiones['admin_municipal'], $idContenedor);
    comprobarCrudContenedor($estado === 404 && !($respuesta['ok'] ?? true), 'Se pudo dar de baja dos veces.');
} finally {
    if ($idContenedor !== null) {
        $conexion->prepare('DELETE FROM contenedores WHERE id_contenedor = ?')->execute([$idContenedor]);
    }
    foreach ($sesiones as [$pestana, $idSesion]) {
        session_name(nombreSesionPestana($pestana));
        session_id($idSesion);
        session_start();
        session_destroy();
        session_write_close();
    }
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Contenedores CRUD HTTP: alta, consulta, edición, baja, validaciones y permisos correctos\n";
