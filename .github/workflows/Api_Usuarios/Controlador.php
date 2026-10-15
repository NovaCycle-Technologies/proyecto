<?php
declare(strict_types=1);

require_once __DIR__ . '/modelo.php';

class ControladorUsuarios
{
    private ModeloUsuarios $modelo;

    public function __construct()
    {
        $this->modelo = new ModeloUsuarios();
    }

    public function registrar(array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $apellido = trim($datos['apellido'] ?? '');
        $ci = trim($datos['CI'] ?? '');
        $email = strtolower(trim($datos['email'] ?? ''));
        $password = $datos['password'] ?? '';
        $rol = $datos['rol_solicitado'] ?? '';

        if ($nombre === '' || $apellido === '' || $ci === '' || $email === '' || $password === '' || $rol === '') {
            return ['ok' => false, 'mensaje' => 'Todos los campos son obligatorios.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            return ['ok' => false, 'mensaje' => 'Email o contraseña no válidos.'];
        }
        if (!in_array($rol, ['peon', 'conductor', 'operario'], true)) {
            return ['ok' => false, 'mensaje' => 'El rol solicitado no es válido.'];
        }
        if ($this->modelo->buscarPorCI($ci) !== null) {
            return ['ok' => false, 'mensaje' => 'Ya existe un usuario con esa CI.'];
        }
        $this->modelo->guardarUsuario(['ci' => $ci, 'nombre' => $nombre, 'apellido' => $apellido, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'rol_solicitado' => $rol]);
        return ['ok' => true, 'mensaje' => 'Solicitud enviada. Debe ser aprobada por un administrador municipal.'];
    }

    public function iniciarSesion(array $datos): array
    {
        $usuario = $this->modelo->buscarPorEmail(strtolower(trim($datos['email'] ?? '')));
        if ($usuario === null || !password_verify($datos['password'] ?? '', $usuario['password'] ?? '')) {
            return ['ok' => false, 'mensaje' => 'Email o contraseña incorrectos.'];
        }
        if (($usuario['estado_cuenta'] ?? '') !== 'aprobado') {
            return ['ok' => false, 'mensaje' => 'Tu cuenta todavía no fue aprobada.'];
        }
        $rol = $this->modelo->obtenerRol((string) $usuario['CI']);
        if ($rol === null) {
            return ['ok' => false, 'mensaje' => 'La cuenta aprobada no tiene un rol asignado.'];
        }
        $destinos = [
            'admin_municipal' => 'admin.html',
            'peon' => 'cuadrilla.html',
            'conductor' => 'cuadrilla.html',
            'operario' => 'operario.html'
        ];
        return [
            'ok' => true,
            'mensaje' => 'Sesión iniciada correctamente.',
            'usuario' => ['ci' => $usuario['CI'], 'nombre' => $usuario['Nombre'], 'rol' => $rol],
            'destino' => $destinos[$rol] ?? 'index.html'
        ];
    }

    public function listarPendientes(): array
    {
        return ['ok' => true, 'usuarios' => $this->modelo->listarPendientes()];
    }

    public function aprobarUsuario(array $datos): array
    {
        $ci = trim($datos['ci'] ?? '');
        $rol = $datos['rol'] ?? '';
        if (!$this->modelo->aprobarYAsignarRol($ci, $rol)) {
            return ['ok' => false, 'mensaje' => 'No se pudo aprobar ese usuario.'];
        }
        return ['ok' => true, 'mensaje' => 'Usuario aprobado correctamente.'];
    }

    public function listarUsuarios(): array
    {
        return ['ok' => true, 'usuarios' => $this->modelo->listarUsuarios()];
    }

    public function modificarUsuario(array $datos): array
    {
        $ci = trim((string) ($datos['ci'] ?? ''));
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $apellido = trim((string) ($datos['apellido'] ?? ''));
        $email = strtolower(trim((string) ($datos['email'] ?? '')));
        $rol = (string) ($datos['rol'] ?? '');

        if ($ci === '' || $nombre === '' || $apellido === '' || $email === '') {
            return ['ok' => false, 'mensaje' => 'Completá todos los datos del usuario.'];
        }
        if (mb_strlen($nombre) > 30 || mb_strlen($apellido) > 30 || mb_strlen($email) > 30
            || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'mensaje' => 'Nombre, apellido o correo no válidos.'];
        }
        if (!in_array($rol, ['peon', 'conductor', 'operario'], true)) {
            return ['ok' => false, 'mensaje' => 'El rol no es válido.'];
        }

        return $this->modelo->modificarUsuario($ci, $nombre, $apellido, $email, $rol);
    }

    public function eliminarUsuario(array $datos, string $ciAdmin): array
    {
        $ci = trim((string) ($datos['ci'] ?? ''));
        if ($ci === '') {
            return ['ok' => false, 'mensaje' => 'Seleccioná un usuario.'];
        }
        if ($ci === $ciAdmin) {
            return ['ok' => false, 'mensaje' => 'No podés eliminar tu propia cuenta.'];
        }

        return $this->modelo->eliminarUsuario($ci);
    }
}
