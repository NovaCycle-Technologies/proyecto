<?php
declare(strict_types=1);

function nombreSesionPestana(string $pestana): string
{
    return 'NC_' . $pestana;
}

function configurarCookieSesion(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

function iniciarSesionActual(): void
{
    configurarCookieSesion();

    $pestana = $_SERVER['HTTP_X_NOVACYCLE_TAB'] ?? '';
    if (!is_string($pestana) || !preg_match('/^[a-f0-9]{32}$/', $pestana)) {
        // El rol es solo una pista para elegir entre cookies ya autenticadas.
        // Los permisos se comprueban después con el rol guardado en la sesión.
        $rol = $_SERVER['HTTP_X_NOVACYCLE_ROL'] ?? '';
        if (is_string($rol) && in_array($rol, ['admin_municipal', 'operario', 'peon', 'conductor'], true)) {
            $nombres = [];
            if (isset($_COOKIE[session_name()])) {
                $nombres[] = session_name();
            }
            foreach (array_keys($_COOKIE) as $nombre) {
                if (preg_match('/^NC_[a-f0-9]{32}$/', $nombre)) {
                    $nombres[] = $nombre;
                }
            }

            foreach ($nombres as $nombre) {
                $cookie = $_COOKIE[$nombre];
                if (!is_string($cookie) || !preg_match('/^[A-Za-z0-9,-]{16,128}$/', $cookie)) {
                    continue;
                }
                session_name($nombre);
                session_id($cookie);
                session_start();
                if (($_SESSION['usuario']['rol'] ?? null) === $rol) {
                    return;
                }
                session_write_close();
                $_SESSION = [];
                session_id('');
            }
            return;
        }

        // Una pestaña abierta antes del cambio sigue usando PHPSESSID.
        // Debe tener prioridad sobre las cookies nuevas de otras pestañas.
        if (isset($_COOKIE[session_name()])) {
            session_start();
            return;
        }

        $pestanas = [];
        foreach (array_keys($_COOKIE) as $nombre) {
            if (preg_match('/^NC_([a-f0-9]{32})$/', $nombre, $coincidencia)) {
                $pestanas[] = $coincidencia[1];
            }
        }

        if (count($pestanas) === 1) {
            $pestana = $pestanas[0];
        } elseif (count($pestanas) > 1) {
            return;
        } else {
            // Compatibilidad con sesiones anteriores y una sola sesión de Postman.
            session_start();
            return;
        }
    }

    $nombre = nombreSesionPestana($pestana);
    session_name($nombre);
    $cookie = $_COOKIE[$nombre] ?? '';
    if (is_string($cookie) && preg_match('/^[A-Za-z0-9,-]{16,128}$/', $cookie)) {
        session_id($cookie);
    }
    session_start();
}

function crearSesionUsuario(array $usuario): string
{
    configurarCookieSesion();
    $pestana = bin2hex(random_bytes(16));
    session_id('');
    session_name(nombreSesionPestana($pestana));
    session_start();
    session_regenerate_id(true);
    $_SESSION['usuario'] = $usuario;
    session_write_close();

    return $pestana;
}
