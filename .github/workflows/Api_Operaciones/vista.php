<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/controlador.php';
header('Content-Type: application/json; charset=utf-8');
function responder(array $resultado, int $codigo = 200): never { http_response_code($codigo); echo json_encode($resultado, JSON_UNESCAPED_UNICODE); exit; }
function permitir(array $roles): void { if (!in_array($_SESSION['usuario']['rol'] ?? '', $roles, true)) responder(['ok' => false, 'mensaje' => 'No tenés permisos para esta acción.'], 403); }
$metodo = $_SERVER['REQUEST_METHOD']; $accion = $_GET['accion'] ?? ''; $datos = json_decode(file_get_contents('php://input'), true); $datos = is_array($datos) ? $datos : $_POST; $controlador = new ControladorOperaciones(); $rol = $_SESSION['usuario']['rol'] ?? ''; $ci = (string)($_SESSION['usuario']['ci'] ?? '');
try {
  if ($metodo === 'GET' && $accion === 'rutas') { permitir(['admin_municipal']); responder($controlador->rutas()); }
  if ($metodo === 'GET' && $accion === 'trabajadores') { permitir(['admin_municipal']); responder($controlador->trabajadores($_GET['rol'] ?? '')); }
  if ($metodo === 'GET' && $accion === 'mi_ruta') { permitir(['peon', 'conductor']); responder($controlador->miRuta($ci)); }
  if ($metodo === 'GET' && $accion === 'reporte_admin') { permitir(['admin_municipal']); responder($controlador->reporteAdmin()); }
  if ($metodo === 'GET' && $accion === 'camiones') { permitir(['admin_municipal', 'peon', 'conductor', 'operario']); responder($controlador->camiones()); }
  if ($metodo === 'GET' && $accion === 'incidencias') { permitir(['operario']); responder($controlador->incidencias()); }
  if ($metodo === 'GET' && $accion === 'resumen') { permitir(['operario']); responder($controlador->resumen()); }
  if ($metodo === 'POST' && $accion === 'incidencia') { permitir(['peon', 'conductor']); responder($controlador->crearIncidencia($datos, $ci), 201); }
  if ($metodo === 'POST' && $accion === 'revisar_incidencia') { permitir(['operario']); responder($controlador->revisarIncidencia($datos)); }
  if ($metodo === 'POST' && $accion === 'estado_camion') { permitir(['conductor']); responder($controlador->estadoCamion($datos)); }
  if ($metodo === 'POST' && $accion === 'ingreso') { permitir(['operario']); responder($controlador->ingreso($datos, $ci), 201); }
  if ($metodo === 'POST' && $accion === 'maquinaria') { permitir(['operario']); responder($controlador->maquinaria($datos, $ci)); }
  if ($metodo === 'POST' && $accion === 'crear_ruta') { permitir(['admin_municipal']); responder($controlador->crearRuta($datos), 201); }
  if ($metodo === 'POST' && $accion === 'crear_parada') { permitir(['admin_municipal']); responder($controlador->crearParada($datos), 201); }
  if ($metodo === 'POST' && $accion === 'asignar_ruta') { permitir(['admin_municipal']); responder($controlador->asignarRuta($datos), 201); }
  if ($metodo === 'POST' && $accion === 'completar_parada') { permitir(['peon', 'conductor']); responder($controlador->completarParada($datos, $ci)); }
  responder(['ok' => false, 'mensaje' => 'Ruta no encontrada.'], 404);
} catch (Throwable $error) { responder(['ok' => false, 'mensaje' => 'No se pudo completar la operación.'], 500); }
