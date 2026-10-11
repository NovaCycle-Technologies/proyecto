<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';

function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$pestanaAdmin = crearSesionUsuario(['ci' => 'admin-prueba', 'rol' => 'admin_municipal']);
$sesionAdmin = session_id();
$pestanaPeon = crearSesionUsuario(['ci' => 'peon-prueba', 'rol' => 'peon']);
$sesionPeon = session_id();

$_COOKIE[nombreSesionPestana($pestanaAdmin)] = $sesionAdmin;
$_COOKIE[nombreSesionPestana($pestanaPeon)] = $sesionPeon;

foreach ([
    [$pestanaAdmin, 'admin_municipal'],
    [$pestanaPeon, 'peon'],
    [$pestanaAdmin, 'admin_municipal'],
] as [$pestana, $rolEsperado]) {
    session_id('');
    $_SERVER['HTTP_X_NOVACYCLE_TAB'] = $pestana;
    iniciarSesionActual();
    $rolActual = $_SESSION['usuario']['rol'] ?? '';
    comprobar(
        $rolActual === $rolEsperado,
        "Las pestañas compartieron el rol: esperado {$rolEsperado}, obtenido {$rolActual}.",
    );
    session_write_close();
}

foreach ([[$pestanaAdmin, $sesionAdmin], [$pestanaPeon, $sesionPeon]] as [$pestana, $sesion]) {
    session_name(nombreSesionPestana($pestana));
    session_id($sesion);
    session_start();
    session_destroy();
    session_write_close();
}

echo "Sesiones independientes: correcto\n";
