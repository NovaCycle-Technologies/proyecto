<?php
declare(strict_types=1);

require_once __DIR__ . '/sesion.php';
require_once __DIR__ . '/Controlador.php';

header('Content-Type: application/json; charset=utf-8');

function responder(array $resultado, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
}

$accion = $_GET['accion'] ?? '';
$controlador = new ControladorUsuarios();
$datos = $_POST;

if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $cuerpo = json_decode(file_get_contents('php://input'), true);
    $datos = is_array($cuerpo) ? $cuerpo : [];
}

if ($accion === 'registrar') {
    $resultado = $controlador->registrar($datos);
} elseif ($accion === 'iniciar_sesion') {
    $resultado = $controlador->iniciarSesion($datos);
    if ($resultado['ok']) {
        $resultado['pestana'] = crearSesionUsuario($resultado['usuario']);
    }
} elseif (in_array($accion, ['listar_pendientes', 'aprobar_usuario', 'listar_usuarios', 'modificar_usuario', 'eliminar_usuario'], true)) {
    iniciarSesionActual();
    if (($_SESSION['usuario']['rol'] ?? '') !== 'admin_municipal') {
        $resultado = ['ok' => false, 'mensaje' => 'Solo el administrador municipal puede realizar esta acción.'];
        responder($resultado, 403);
    }
    $resultado = match ($accion) {
        'listar_pendientes' => $controlador->listarPendientes(),
        'aprobar_usuario' => $controlador->aprobarUsuario($datos),
        'listar_usuarios' => $controlador->listarUsuarios(),
        'modificar_usuario' => $controlador->modificarUsuario($datos),
        'eliminar_usuario' => $controlador->eliminarUsuario($datos, (string) ($_SESSION['usuario']['ci'] ?? ''))
    };
} else {
    responder(['ok' => false, 'mensaje' => 'Acción no encontrada.'], 404);
}

responder($resultado, $resultado['ok'] ? 200 : 400);
