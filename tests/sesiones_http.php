<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';
require_once __DIR__ . '/cuentas_prueba.php';

function solicitar(string $accion, string $pestana, string $sesion, string $cookies = '', string $rol = ''): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Operaciones/api_operaciones.php?accion=' . $accion;
    $cookies = $cookies !== '' ? $cookies : nombreSesionPestana($pestana) . '=' . $sesion;
    $cabeceras = 'Cookie: ' . $cookies . "\r\n";
    if ($pestana !== '') {
        $cabeceras .= 'X-NovaCycle-Tab: ' . $pestana . "\r\n";
    }
    if ($rol !== '') {
        $cabeceras .= 'X-NovaCycle-Rol: ' . $rol . "\r\n";
    }
    $cabeceras .= "Accept: application/json\r\n";
    $contexto = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => $cabeceras,
        'ignore_errors' => true,
        'timeout' => 5,
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo conectar con XAMPP.');
    }
    return json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR);
}

$sesiones = [];
$sesionesAnteriores = [];
$correcto = false;
[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'peon']);
try {
    foreach (['admin_municipal', 'peon'] as $rol) {
        $pestana = crearSesionUsuario(['ci' => $cuentas[$rol], 'rol' => $rol]);
        $sesiones[$rol] = [$pestana, session_id()];
    }

    [$adminTab, $adminSesion] = $sesiones['admin_municipal'];
    [$peonTab, $peonSesion] = $sesiones['peon'];

    if (!(solicitar('dashboard_admin', $adminTab, $adminSesion)['ok'] ?? false)) {
        throw new RuntimeException('El administrador perdió su sesión.');
    }
    if (!(solicitar('mi_ruta', $peonTab, $peonSesion)['ok'] ?? false)) {
        throw new RuntimeException('El peón no pudo usar su sesión.');
    }
    if (!(solicitar('dashboard_admin', $adminTab, $adminSesion)['ok'] ?? false)) {
        throw new RuntimeException('La sesión del peón reemplazó a la del administrador.');
    }

    session_id('');
    session_name('PHPSESSID');
    session_start();
    $_SESSION['usuario'] = ['ci' => $cuentas['admin_municipal'], 'rol' => 'admin_municipal'];
    session_write_close();
    $sesionAnterior = session_id();
    $sesionesAnteriores[] = $sesionAnterior;

    $cookies = 'PHPSESSID=' . $sesionAnterior . '; ' . nombreSesionPestana($peonTab) . '=' . $peonSesion;
    if (!(solicitar('dashboard_admin', '', '', $cookies)['ok'] ?? false)) {
        throw new RuntimeException('La sesión antigua del administrador tomó la cookie del peón.');
    }
    if (!(solicitar('mi_ruta', '', '', $cookies, 'peon')['ok'] ?? false)) {
        throw new RuntimeException('La pestaña del peón tomó la sesión antigua del administrador.');
    }
    if (!(solicitar('dashboard_admin', '', '', $cookies, 'admin_municipal')['ok'] ?? false)) {
        throw new RuntimeException('El administrador perdió permisos al volver del peón.');
    }

    session_id('');
    session_name('PHPSESSID');
    session_start();
    $_SESSION['usuario'] = ['ci' => $cuentas['peon'], 'rol' => 'peon'];
    session_write_close();
    $sesionPeonAnterior = session_id();
    $sesionesAnteriores[] = $sesionPeonAnterior;

    $cookies = 'PHPSESSID=' . $sesionPeonAnterior . '; ' . nombreSesionPestana($adminTab) . '=' . $adminSesion;
    if (!(solicitar('mi_ruta', '', '', $cookies, 'peon')['ok'] ?? false)) {
        throw new RuntimeException('El peón antiguo no pudo usar su sesión.');
    }
    if (!(solicitar('dashboard_admin', '', '', $cookies, 'admin_municipal')['ok'] ?? false)) {
        throw new RuntimeException('El administrador tomó la sesión antigua del peón.');
    }
    if (!(solicitar('mi_ruta', '', '', $cookies, 'peon')['ok'] ?? false)) {
        throw new RuntimeException('El peón perdió permisos al volver del administrador.');
    }

    $correcto = true;
} finally {
    foreach ($sesiones as [$pestana, $sesion]) {
        session_name(nombreSesionPestana($pestana));
        session_id($sesion);
        session_start();
        session_destroy();
        session_write_close();
    }
    foreach ($sesionesAnteriores as $sesionAnterior) {
        session_name('PHPSESSID');
        session_id($sesionAnterior);
        session_start();
        session_destroy();
        session_write_close();
    }
    eliminarCuentasPrueba($conexion, $cuentas);
}

if ($correcto) {
    echo "Dos sesiones HTTP independientes: correcto\n";
}
