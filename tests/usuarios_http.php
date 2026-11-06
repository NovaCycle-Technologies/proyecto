<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';
require_once __DIR__ . '/cuentas_prueba.php';

function solicitarUsuarios(string $accion, string $pestana, string $sesion, array $datos = []): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Usuarios/api_usuarios.php?accion=' . $accion;
    $cabeceras = 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $sesion . "\r\n"
        . 'X-NovaCycle-Tab: ' . $pestana . "\r\n"
        . "Content-Type: application/x-www-form-urlencoded\r\n";
    $contexto = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => $cabeceras,
        'content' => http_build_query($datos),
        'ignore_errors' => true,
        'timeout' => 5
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo conectar con XAMPP.');
    }
    return json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR);
}

function consultarRuta(string $pestana, string $sesion): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Operaciones/api_operaciones.php?accion=mi_ruta';
    $contexto = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $sesion . "\r\n"
            . 'X-NovaCycle-Tab: ' . $pestana . "\r\n",
        'ignore_errors' => true,
        'timeout' => 5
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo consultar la API de operaciones.');
    }
    return json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR);
}

$sesiones = [];
$idIncidenciaTemporal = null;
[$conexion, $cuentas] = crearCuentasPrueba(['admin_municipal', 'peon']);
do {
    $ciPrueba = 'T' . substr(bin2hex(random_bytes(4)), 0, 7);
    $buscar = $conexion->prepare('SELECT 1 FROM usuarios WHERE CI = ?');
    $buscar->execute([$ciPrueba]);
} while ($buscar->fetchColumn() !== false);

try {
    foreach (['admin_municipal', 'peon'] as $rol) {
        $pestana = crearSesionUsuario(['ci' => $cuentas[$rol], 'rol' => $rol]);
        $sesiones[$rol] = [$pestana, session_id()];
    }
    [$adminTab, $adminSesion] = $sesiones['admin_municipal'];
    [$peonTab, $peonSesion] = $sesiones['peon'];

    $listado = solicitarUsuarios('listar_usuarios', $adminTab, $adminSesion);
    if (!($listado['ok'] ?? false) || !isset($listado['usuarios'])) {
        throw new RuntimeException('El administrador no pudo consultar usuarios.');
    }
    foreach ($listado['usuarios'] as $usuario) {
        if (array_key_exists('password', $usuario)) {
            throw new RuntimeException('La API expuso contraseñas en el listado.');
        }
    }
    if (solicitarUsuarios('listar_usuarios', $peonTab, $peonSesion)['ok'] ?? true) {
        throw new RuntimeException('Un peón pudo consultar el listado administrativo.');
    }
    if (solicitarUsuarios('eliminar_usuario', $adminTab, $adminSesion, ['ci' => $cuentas['admin_municipal']])['ok'] ?? true) {
        throw new RuntimeException('El administrador pudo eliminar su propia cuenta.');
    }
    if (solicitarUsuarios('modificar_usuario', $adminTab, $adminSesion, [
        'ci' => 'usuario-que-no-existe', 'nombre' => 'Prueba', 'apellido' => 'Prueba',
        'email' => 'prueba@example.com', 'rol' => 'peon'
    ])['ok'] ?? true) {
        throw new RuntimeException('Se pudo modificar una cuenta inexistente.');
    }

    $correoPrueba = strtolower($ciPrueba) . '@example.test';
    if (!(solicitarUsuarios('registrar', $adminTab, $adminSesion, [
        'CI' => $ciPrueba, 'nombre' => 'Prueba', 'apellido' => 'Temporal',
        'email' => $correoPrueba, 'password' => 'ClavePrueba123', 'rol_solicitado' => 'peon'
    ])['ok'] ?? false)) {
        throw new RuntimeException('No se pudo registrar el usuario temporal.');
    }
    if (!(solicitarUsuarios('aprobar_usuario', $adminTab, $adminSesion, ['ci' => $ciPrueba, 'rol' => 'peon'])['ok'] ?? false)) {
        throw new RuntimeException('No se pudo aprobar el usuario temporal.');
    }
    $pestanaPeonTemporal = crearSesionUsuario(['ci' => $ciPrueba, 'rol' => 'peon']);
    $sesionPeonTemporal = session_id();
    $sesiones['peon_temporal'] = [$pestanaPeonTemporal, $sesionPeonTemporal];
    if (!(consultarRuta($pestanaPeonTemporal, $sesionPeonTemporal)['ok'] ?? false)) {
        throw new RuntimeException('La sesión nueva del peón temporal no funcionó.');
    }

    $insertarIncidencia = $conexion->prepare(
        "INSERT INTO incidencias (ci_reportante, ubicacion, tipo, detalle)
         VALUES (?, 'Prueba temporal', 'Prueba', 'Verificación automática')"
    );
    $insertarIncidencia->execute([$ciPrueba]);
    $idIncidenciaTemporal = (int) $conexion->lastInsertId();
    if (solicitarUsuarios('eliminar_usuario', $adminTab, $adminSesion, ['ci' => $ciPrueba])['ok'] ?? true) {
        throw new RuntimeException('Se eliminó un usuario con incidencias asociadas.');
    }
    if (solicitarUsuarios('modificar_usuario', $adminTab, $adminSesion, [
        'ci' => $ciPrueba, 'nombre' => 'Prueba', 'apellido' => 'Temporal',
        'email' => $correoPrueba, 'rol' => 'conductor'
    ])['ok'] ?? true) {
        throw new RuntimeException('Se cambió el rol de un usuario con incidencias asociadas.');
    }
    $quitarIncidencia = $conexion->prepare('DELETE FROM incidencias WHERE id_incidencia = ?');
    $quitarIncidencia->execute([$idIncidenciaTemporal]);
    $idIncidenciaTemporal = null;

    if (!(solicitarUsuarios('modificar_usuario', $adminTab, $adminSesion, [
        'ci' => $ciPrueba, 'nombre' => 'Nombre editado', 'apellido' => 'Temporal',
        'email' => $correoPrueba, 'rol' => 'conductor'
    ])['ok'] ?? false)) {
        throw new RuntimeException('No se pudo editar el usuario temporal.');
    }
    if (consultarRuta($pestanaPeonTemporal, $sesionPeonTemporal)['ok'] ?? true) {
        throw new RuntimeException('La sesión del rol anterior siguió activa.');
    }
    $usuarios = solicitarUsuarios('listar_usuarios', $adminTab, $adminSesion)['usuarios'];
    $editado = array_values(array_filter($usuarios, fn (array $usuario): bool => $usuario['ci'] === $ciPrueba));
    if (count($editado) !== 1 || $editado[0]['nombre'] !== 'Nombre editado' || $editado[0]['rol'] !== 'conductor') {
        throw new RuntimeException('La edición de datos o rol no quedó guardada.');
    }
    $consultarUsuario = $conexion->prepare('SELECT Nombre, Email, rol_solicitado FROM usuarios WHERE CI = ?');
    $consultarUsuario->execute([$ciPrueba]);
    $guardado = $consultarUsuario->fetch(PDO::FETCH_ASSOC);
    $consultarConductor = $conexion->prepare('SELECT 1 FROM conductores WHERE ci = ?');
    $consultarConductor->execute([$ciPrueba]);
    $consultarPeon = $conexion->prepare('SELECT 1 FROM peones WHERE ci = ?');
    $consultarPeon->execute([$ciPrueba]);
    if ($guardado['Nombre'] !== 'Nombre editado' || $guardado['Email'] !== $correoPrueba
        || $guardado['rol_solicitado'] !== 'conductor' || !$consultarConductor->fetchColumn()
        || $consultarPeon->fetchColumn()) {
        throw new RuntimeException('La edición del usuario o de su rol no se guardó en MySQL.');
    }
    $pestanaConductorTemporal = crearSesionUsuario(['ci' => $ciPrueba, 'rol' => 'conductor']);
    $sesionConductorTemporal = session_id();
    $sesiones['conductor_temporal'] = [$pestanaConductorTemporal, $sesionConductorTemporal];
    if (!(consultarRuta($pestanaConductorTemporal, $sesionConductorTemporal)['ok'] ?? false)) {
        throw new RuntimeException('La sesión nueva del conductor temporal no funcionó.');
    }
    if (!(solicitarUsuarios('eliminar_usuario', $adminTab, $adminSesion, ['ci' => $ciPrueba])['ok'] ?? false)) {
        throw new RuntimeException('No se pudo eliminar el usuario temporal.');
    }
    $consultarUsuario->execute([$ciPrueba]);
    $consultarConductor->execute([$ciPrueba]);
    if ($consultarUsuario->fetchColumn() !== false || $consultarConductor->fetchColumn() !== false) {
        throw new RuntimeException('La baja del usuario no se aplicó en MySQL.');
    }
    if (consultarRuta($pestanaConductorTemporal, $sesionConductorTemporal)['ok'] ?? true) {
        throw new RuntimeException('La cuenta eliminada conservó acceso con una sesión abierta.');
    }
} finally {
    if ($idIncidenciaTemporal !== null) {
        $quitarIncidencia = $conexion->prepare('DELETE FROM incidencias WHERE id_incidencia = ?');
        $quitarIncidencia->execute([$idIncidenciaTemporal]);
    }
    $eliminarPrueba = $conexion->prepare('DELETE FROM usuarios WHERE CI = ?');
    $eliminarPrueba->execute([$ciPrueba]);
    foreach ($sesiones as [$pestana, $sesion]) {
        session_name(nombreSesionPestana($pestana));
        session_id($sesion);
        session_start();
        session_destroy();
        session_write_close();
    }
    eliminarCuentasPrueba($conexion, $cuentas);
}

echo "Usuarios HTTP: permisos y CRUD correctos\n";
