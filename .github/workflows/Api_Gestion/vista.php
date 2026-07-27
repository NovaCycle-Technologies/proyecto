<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/Controlador.php';

header('Content-Type: application/json; charset=utf-8');

function responder(array $resultado, int $codigo = 200): never
{
    http_response_code($codigo);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

function esAdminMunicipal(): bool
{
    return ($_SESSION['usuario']['rol'] ?? '') === 'admin_municipal';
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';
$controlador = new ControladorContenedores();
$datos = $_POST;

if (in_array($metodo, ['PUT', 'DELETE'], true)) {
    $cuerpo = json_decode(file_get_contents('php://input'), true);
    $datos = is_array($cuerpo) ? $cuerpo : [];
}

if ($metodo === 'GET' && $accion === 'listar') {
    responder($controlador->listar());
}

if ($metodo === 'GET' && $accion === 'obtener') {
    $resultado = $controlador->obtener((int) ($_GET['id'] ?? 0));
    responder($resultado, $resultado['ok'] ? 200 : 404);
}

if (!esAdminMunicipal()) {
    responder(['ok' => false, 'mensaje' => 'Solo el administrador municipal puede modificar contenedores.'], 403);
}

if ($metodo === 'POST' && $accion === 'crear') {
    $resultado = $controlador->crear($datos);
    responder($resultado, $resultado['ok'] ? 201 : 400);
}

if ($metodo === 'PUT' && $accion === 'actualizar') {
    $resultado = $controlador->actualizar((int) ($_GET['id'] ?? 0), $datos);
    responder($resultado, $resultado['ok'] ? 200 : 400);
}

if ($metodo === 'DELETE' && $accion === 'baja') {
    $resultado = $controlador->darDeBaja((int) ($_GET['id'] ?? 0));
    responder($resultado, $resultado['ok'] ? 200 : 404);
}

responder(['ok' => false, 'mensaje' => 'Ruta o método no válido.'], 404);