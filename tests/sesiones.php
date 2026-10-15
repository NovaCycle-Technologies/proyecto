<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';
require_once __DIR__ . '/cuentas_prueba.php';

function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'peon']);
$sesiones = [];
try {
    $pestanaAdmin = crearSesionUsuario(['ci' => $cuentas['admin_municipal'], 'rol' => 'admin_municipal']);
    $sesionAdmin = session_id();
    $sesiones[] = [$pestanaAdmin, $sesionAdmin];
    $pestanaPeon = crearSesionUsuario(['ci' => $cuentas['peon'], 'rol' => 'peon']);
    $sesionPeon = session_id();
    $sesiones[] = [$pestanaPeon, $sesionPeon];

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

} finally {
    foreach ($sesiones as [$pestana, $sesion]) {
        session_name(nombreSesionPestana($pestana));
        session_id($sesion);
        session_start();
        session_destroy();
        session_write_close();
    }
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Sesiones independientes: correcto\n";
