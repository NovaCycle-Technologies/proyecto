<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/cuentas_prueba.php';
require_once __DIR__ . '/operaciones_http_helper.php';

function pedirUsuarios(string $accion, array $datos, ?array $sesion = null): array
{
    $cabeceras = "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n";
    if ($sesion !== null) {
        [$pestana, $idSesion] = $sesion;
        $cabeceras .= 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $idSesion . "\r\n";
        $cabeceras .= 'X-NovaCycle-Tab: ' . $pestana . "\r\n";
    }
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Usuarios/api_usuarios.php?accion=' . $accion;
    $contexto = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => $cabeceras,
        'content' => http_build_query($datos),
        'ignore_errors' => true,
        'timeout' => 5
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo consultar la API de usuarios.');
    }
    $estado = (int) substr($http_response_header[0] ?? '', 9, 3);
    $respuesta = json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($respuesta)) {
        throw new RuntimeException('La API de usuarios no devolvio un objeto JSON.');
    }
    return [$estado, $respuesta, $http_response_header];
}

function comprobarAutenticacion(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function extraerSesion(array $respuesta, array $cabeceras): array
{
    $pestana = $respuesta['pestana'] ?? '';
    comprobarAutenticacion(is_string($pestana) && preg_match('/^[a-f0-9]{32}$/', $pestana) === 1,
        'El login no devolvio una pestana valida.');
    $nombre = nombreSesionPestana($pestana);
    $idSesion = null;
    foreach ($cabeceras as $cabecera) {
        if (preg_match('/^Set-Cookie:\s*' . preg_quote($nombre, '/') . '=([^;]+)/i', $cabecera, $coincidencia)) {
            $idSesion = $coincidencia[1];
        }
    }
    if ($idSesion === null) {
        throw new RuntimeException('El login no devolvio la cookie de sesion.');
    }
    return [$pestana, $idSesion];
}

[$conexion, $administradores] = crearCuentasPrueba(['admin_municipal']);
$cuentasTemporales = [];
$sesiones = [];

try {
    $sesionAdmin = sesionDePrueba($administradores['admin_municipal'], 'admin_municipal');
    $sesiones[] = $sesionAdmin;

    foreach (['peon' => 'cuadrilla.html', 'conductor' => 'cuadrilla.html', 'operario' => 'operario.html'] as $rol => $destino) {
        do {
            $ci = 'T' . substr(bin2hex(random_bytes(4)), 0, 7);
            $buscar = $conexion->prepare('SELECT 1 FROM usuarios WHERE CI = ?');
            $buscar->execute([$ci]);
        } while ($buscar->fetchColumn() !== false);

        $correo = strtolower($ci) . '@example.test';
        $clave = 'ClavePrueba123';
        $datos = [
            'CI' => $ci, 'nombre' => 'Prueba', 'apellido' => 'Temporal',
            'email' => $correo, 'password' => $clave, 'rol_solicitado' => $rol
        ];
        [$estado, $respuesta] = pedirUsuarios('registrar', $datos);
        comprobarAutenticacion($estado === 200 && ($respuesta['ok'] ?? false), "No se registro {$rol}.");
        $cuentasTemporales[] = $ci;

        $consultar = $conexion->prepare('SELECT estado_cuenta, rol_solicitado, password FROM usuarios WHERE CI = ?');
        $consultar->execute([$ci]);
        $guardado = $consultar->fetch(PDO::FETCH_ASSOC);
        comprobarAutenticacion($guardado !== false && $guardado['estado_cuenta'] === 'pendiente'
            && $guardado['rol_solicitado'] === $rol && password_verify($clave, $guardado['password']),
            "El registro pendiente de {$rol} no quedo guardado correctamente.");

        foreach (['peones' => 'peon', 'conductores' => 'conductor', 'operarios' => 'operario'] as $tabla => $rolTabla) {
            $consultaRol = $conexion->prepare("SELECT 1 FROM {$tabla} WHERE ci = ?");
            $consultaRol->execute([$ci]);
            comprobarAutenticacion($consultaRol->fetchColumn() === false,
                "La cuenta pendiente de {$rol} ya aparece en {$tabla}.");
        }

        [$estado, $respuesta] = pedirUsuarios('iniciar_sesion', ['email' => $correo, 'password' => $clave]);
        comprobarAutenticacion($estado === 400 && !($respuesta['ok'] ?? true) && !isset($respuesta['pestana']),
            "La cuenta pendiente de {$rol} pudo iniciar sesion.");

        [$estado, $respuesta] = pedirUsuarios('aprobar_usuario', ['ci' => $ci, 'rol' => $rol], $sesionAdmin);
        comprobarAutenticacion($estado === 200 && ($respuesta['ok'] ?? false), "No se aprobo {$rol}.");
        $consultar->execute([$ci]);
        $guardado = $consultar->fetch(PDO::FETCH_ASSOC);
        comprobarAutenticacion($guardado['estado_cuenta'] === 'aprobado', "La aprobacion de {$rol} no se guardo.");

        foreach (['peones' => 'peon', 'conductores' => 'conductor', 'operarios' => 'operario'] as $tabla => $rolTabla) {
            $consultaRol = $conexion->prepare("SELECT 1 FROM {$tabla} WHERE ci = ?");
            $consultaRol->execute([$ci]);
            comprobarAutenticacion(($consultaRol->fetchColumn() !== false) === ($rol === $rolTabla),
                "La cuenta de {$rol} tiene un rol incorrecto en {$tabla}.");
        }

        [$estado, $respuesta] = pedirUsuarios('iniciar_sesion', ['email' => $correo, 'password' => 'incorrecta']);
        comprobarAutenticacion($estado === 400 && !($respuesta['ok'] ?? true) && !isset($respuesta['pestana']),
            "La cuenta de {$rol} acepto una clave incorrecta.");

        [$estado, $respuesta, $cabeceras] = pedirUsuarios('iniciar_sesion', ['email' => $correo, 'password' => $clave]);
        comprobarAutenticacion($estado === 200 && ($respuesta['ok'] ?? false)
            && ($respuesta['usuario']['ci'] ?? '') === $ci
            && ($respuesta['usuario']['rol'] ?? '') === $rol
            && ($respuesta['destino'] ?? '') === $destino,
            "El login de {$rol} no devolvio el destino o rol esperado.");
        $sesionReal = extraerSesion($respuesta, $cabeceras);
        $sesiones[] = $sesionReal;

        $accionPermitida = $rol === 'operario' ? 'resumen' : 'mi_ruta';
        [$estado, $respuesta] = solicitarOperacion($accionPermitida, 'GET', $sesionReal);
        comprobarAutenticacion($estado === 200 && ($respuesta['ok'] ?? false),
            "La sesion real de {$rol} no pudo acceder a {$accionPermitida}.");
    }
} finally {
    cerrarSesionesDePrueba($sesiones);
    $eliminar = $conexion->prepare('DELETE FROM usuarios WHERE CI = ?');
    foreach ($cuentasTemporales as $ci) {
        $eliminar->execute([$ci]);
    }
    eliminarCuentasPrueba($conexion, $administradores);
}

echo "Autenticacion HTTP: registro, aprobacion y login real por rol correctos\n";
