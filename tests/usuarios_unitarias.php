<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Usuarios/Controlador.php';

class ModeloUsuariosFalso extends ModeloUsuarios
{
    public ?array $usuarioPorCI = null;
    public ?array $usuarioPorEmail = null;
    public ?string $rol = null;
    public array $guardados = [];
    public array $modificados = [];
    public array $eliminados = [];
    public array $aprobados = [];
    public bool $aprobarDevuelve = true;
    public string $emailBuscado = '';

    public function __construct()
    {
        // El modelo falso no abre una conexion con la base de datos.
    }

    public function buscarPorCI(string $ci): ?array
    {
        return $this->usuarioPorCI;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $this->emailBuscado = $email;
        return $this->usuarioPorEmail;
    }

    public function guardarUsuario(array $usuario): void
    {
        $this->guardados[] = $usuario;
    }

    public function obtenerRol(string $ci): ?string
    {
        return $this->rol;
    }

    public function listarPendientes(): array
    {
        return [['ci' => '123', 'rol_solicitado' => 'peon']];
    }

    public function listarUsuarios(): array
    {
        return [['ci' => '123', 'rol' => 'peon']];
    }

    public function aprobarYAsignarRol(string $ci, string $rol): bool
    {
        $this->aprobados[] = [$ci, $rol];
        return $this->aprobarDevuelve;
    }

    public function modificarUsuario(string $ci, string $nombre, string $apellido, string $email, string $rol): array
    {
        $this->modificados[] = [$ci, $nombre, $apellido, $email, $rol];
        return ['ok' => true, 'mensaje' => 'Usuario modificado correctamente.'];
    }

    public function eliminarUsuario(string $ci): array
    {
        $this->eliminados[] = $ci;
        return ['ok' => true, 'mensaje' => 'Usuario eliminado correctamente.'];
    }
}

function comprobarUsuario(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$modelo = new ModeloUsuariosFalso();
$reflexion = new ReflectionClass(ControladorUsuarios::class);
/** @var ControladorUsuarios $controlador */
$controlador = $reflexion->newInstanceWithoutConstructor();
$reflexion->getProperty('modelo')->setValue($controlador, $modelo);

$registro = [
    'nombre' => ' Ana ', 'apellido' => ' Perez ', 'CI' => ' 123 ',
    'email' => ' ANA@EJEMPLO.COM ', 'password' => 'clave-segura', 'rol_solicitado' => 'peon'
];
comprobarUsuario(!$controlador->registrar([])['ok'], 'Se acepto un registro sin campos.');
comprobarUsuario(!$controlador->registrar(array_replace($registro, ['email' => 'invalido']))['ok'], 'Se acepto un correo invalido.');
comprobarUsuario(!$controlador->registrar(array_replace($registro, ['password' => 'corta']))['ok'], 'Se acepto una clave corta.');
comprobarUsuario(!$controlador->registrar(array_replace($registro, ['rol_solicitado' => 'admin_municipal']))['ok'], 'Se permitio solicitar el rol administrador.');
comprobarUsuario($modelo->guardados === [], 'Un registro invalido llego al modelo.');

$modelo->usuarioPorCI = ['CI' => '123'];
comprobarUsuario(!$controlador->registrar($registro)['ok'], 'Se acepto una CI duplicada.');
$modelo->usuarioPorCI = null;
comprobarUsuario($controlador->registrar($registro)['ok'], 'No se acepto un registro valido.');
$guardado = $modelo->guardados[0] ?? [];
comprobarUsuario(($guardado['ci'] ?? '') === '123' && ($guardado['nombre'] ?? '') === 'Ana'
    && ($guardado['apellido'] ?? '') === 'Perez' && ($guardado['email'] ?? '') === 'ana@ejemplo.com'
    && ($guardado['rol_solicitado'] ?? '') === 'peon', 'El registro no normalizo los datos.');
comprobarUsuario(password_verify('clave-segura', $guardado['password'] ?? ''), 'La clave no fue almacenada como hash valido.');

comprobarUsuario(!$controlador->iniciarSesion(['email' => 'nadie@ejemplo.com', 'password' => 'x'])['ok'], 'Se acepto un usuario inexistente.');
$modelo->usuarioPorEmail = [
    'CI' => '123', 'Nombre' => 'Ana', 'password' => $guardado['password'], 'estado_cuenta' => 'pendiente'
];
comprobarUsuario(!$controlador->iniciarSesion(['email' => ' ANA@EJEMPLO.COM ', 'password' => 'incorrecta'])['ok'], 'Se acepto una clave incorrecta.');
comprobarUsuario(!$controlador->iniciarSesion(['email' => ' ANA@EJEMPLO.COM ', 'password' => 'clave-segura'])['ok'], 'Inicio sesion una cuenta pendiente.');
comprobarUsuario($modelo->emailBuscado === 'ana@ejemplo.com', 'El login no normalizo el correo.');
$modelo->usuarioPorEmail['estado_cuenta'] = 'rechazado';
comprobarUsuario(!$controlador->iniciarSesion(['email' => 'ana@ejemplo.com', 'password' => 'clave-segura'])['ok'], 'Inicio sesion una cuenta rechazada.');
$modelo->usuarioPorEmail['estado_cuenta'] = 'aprobado';
comprobarUsuario(!$controlador->iniciarSesion(['email' => 'ana@ejemplo.com', 'password' => 'clave-segura'])['ok'], 'Inicio sesion una cuenta sin rol.');
foreach ([
    'admin_municipal' => 'admin.html', 'peon' => 'cuadrilla.html',
    'conductor' => 'cuadrilla.html', 'operario' => 'operario.html'
] as $rol => $destino) {
    $modelo->rol = $rol;
    $respuesta = $controlador->iniciarSesion(['email' => 'ana@ejemplo.com', 'password' => 'clave-segura']);
    comprobarUsuario($respuesta['ok'] && $respuesta['destino'] === $destino
        && $respuesta['usuario']['rol'] === $rol, "Destino de login incorrecto para {$rol}.");
}

comprobarUsuario(count($controlador->listarPendientes()['usuarios']) === 1, 'No se listaron solicitudes pendientes.');
comprobarUsuario(count($controlador->listarUsuarios()['usuarios']) === 1, 'No se listaron usuarios.');
comprobarUsuario($controlador->aprobarUsuario(['ci' => ' 123 ', 'rol' => 'peon'])['ok'], 'No se aprobo el usuario.');
comprobarUsuario($modelo->aprobados === [['123', 'peon']], 'La aprobacion no envio la CI normalizada.');
$modelo->aprobarDevuelve = false;
comprobarUsuario(!$controlador->aprobarUsuario(['ci' => '123', 'rol' => 'admin_municipal'])['ok'], 'No se propago el rechazo de aprobacion.');

comprobarUsuario(!$controlador->modificarUsuario(['ci' => '123'])['ok'], 'Se acepto una modificacion incompleta.');
comprobarUsuario(!$controlador->modificarUsuario(array_replace($registro, ['ci' => '123', 'rol' => 'admin_municipal']))['ok'], 'Se acepto un cambio a administrador.');
comprobarUsuario($modelo->modificados === [], 'Una modificacion invalida llego al modelo.');
comprobarUsuario($controlador->modificarUsuario([
    'ci' => ' 123 ', 'nombre' => ' Ana ', 'apellido' => ' Perez ',
    'email' => ' ANA@EJEMPLO.COM ', 'rol' => 'operario'
])['ok'], 'No se modifico un usuario valido.');
comprobarUsuario($modelo->modificados === [['123', 'Ana', 'Perez', 'ana@ejemplo.com', 'operario']], 'La modificacion no normalizo los datos.');

comprobarUsuario(!$controlador->eliminarUsuario([], '999')['ok'], 'Se acepto eliminar sin seleccionar usuario.');
comprobarUsuario(!$controlador->eliminarUsuario(['ci' => '999'], '999')['ok'], 'Se permitio borrar la propia cuenta administradora.');
comprobarUsuario($modelo->eliminados === [], 'Una eliminacion invalida llego al modelo.');
comprobarUsuario($controlador->eliminarUsuario(['ci' => ' 123 '], '999')['ok'], 'No se elimino un usuario valido.');
comprobarUsuario($modelo->eliminados === ['123'], 'La eliminacion no envio la CI normalizada.');

echo "Usuarios unitarias: registro, login por rol y validaciones CRUD correctas\n";
