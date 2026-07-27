<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/Controlador.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

$accion = $_GET['accion'] ?? '';
$controlador = new ControladorUsuarios();
$esJson = in_array($accion, ['listar_pendientes', 'aprobar_usuario'], true);

if ($accion === 'registrar') {
    $resultado = $controlador->registrar($_POST);
} elseif ($accion === 'iniciar_sesion') {
    $resultado = $controlador->iniciarSesion($_POST);
    if ($resultado['ok']) {
        $_SESSION['usuario'] = $resultado['usuario'];
        if ($resultado['usuario']['rol'] === 'admin_municipal') {
            header('Location: ../Frontend/admin.html');
            exit;
        }
    }
} elseif ($accion === 'listar_pendientes' || $accion === 'aprobar_usuario') {
    if (($_SESSION['usuario']['rol'] ?? '') !== 'admin_municipal') {
        http_response_code(403);
        $resultado = ['ok' => false, 'mensaje' => 'Solo el administrador municipal puede realizar esta acción.'];
    } elseif ($accion === 'listar_pendientes') {
        $resultado = $controlador->listarPendientes();
    } else {
        $resultado = $controlador->aprobarUsuario($_POST);
    }
} else {
    http_response_code(404);
    exit('Acción no encontrada.');
}

if (!$resultado['ok'] && http_response_code() === 200) {
    http_response_code(400);
}

if ($esJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

$mensaje = htmlspecialchars($resultado['mensaje'], ENT_QUOTES, 'UTF-8');
echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><title>NovaCycle Tech</title></head><body>";
echo "<h1>NovaCycle Tech</h1><p>{$mensaje}</p><p><a href=\"../Frontend/index.html\">Volver</a></p></body></html>";