<?php
declare(strict_types=1);

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/sesion.php';

function solicitarOperacion(string $accion, string $metodo = 'GET', ?array $sesion = null, array $datos = []): array
{
    $url = 'http://localhost/Proyecto/proyecto/.github/workflows/Api_Operaciones/api_operaciones.php?accion=' . $accion;
    $cabeceras = "Accept: application/json\r\n";
    if ($sesion !== null) {
        [$pestana, $idSesion] = $sesion;
        $cabeceras .= 'Cookie: ' . nombreSesionPestana($pestana) . '=' . $idSesion . "\r\n";
        $cabeceras .= 'X-NovaCycle-Tab: ' . $pestana . "\r\n";
    }
    if ($metodo === 'POST') {
        $cabeceras .= "Content-Type: application/json\r\n";
    }

    $contexto = stream_context_create(['http' => [
        'method' => $metodo,
        'header' => $cabeceras,
        'content' => $metodo === 'POST' ? json_encode($datos, JSON_THROW_ON_ERROR) : '',
        'ignore_errors' => true,
        'timeout' => 5
    ]]);
    $cuerpo = file_get_contents($url, false, $contexto);
    if ($cuerpo === false) {
        throw new RuntimeException('No se pudo consultar la API de operaciones.');
    }
    $estado = (int) substr($http_response_header[0] ?? '', 9, 3);
    $resultado = json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($resultado)) {
        throw new RuntimeException('La API de operaciones no devolvió un objeto JSON.');
    }
    return [$estado, $resultado];
}

function sesionDePrueba(string $ci, string $rol): array
{
    $pestana = crearSesionUsuario(['ci' => $ci, 'rol' => $rol]);
    return [$pestana, session_id()];
}

function cerrarSesionesDePrueba(array $sesiones): void
{
    foreach ($sesiones as [$pestana, $idSesion]) {
        session_name(nombreSesionPestana($pestana));
        session_id($idSesion);
        session_start();
        session_destroy();
        session_write_close();
    }
}
