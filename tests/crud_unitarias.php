<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Camiones/controlador.php';
require_once __DIR__ . '/../.github/workflows/Api_Gestion/controlador.php';

class ModeloCamionesFalso extends ModeloCamiones
{
    public array $camiones = [['id_camion' => 15, 'matricula' => 'ABC123']];
    public array $creados = [];
    public array $actualizados = [];
    public array $bajas = [];
    public array $resultadoCrear = ['ok' => true, 'id_camion' => 15, 'reactivado' => false];
    public bool $enUso = false;
    public bool $resultadoActualizar = true;
    public bool $resultadoBaja = true;

    public function __construct()
    {
        // Se evita abrir una conexion de base de datos.
    }

    public function listar(): array
    {
        return $this->camiones;
    }

    public function estaEnUso(int $id): bool
    {
        return $this->enUso;
    }

    public function crear(array $camion): array
    {
        $this->creados[] = $camion;
        return $this->resultadoCrear;
    }

    public function actualizar(int $id, array $camion): bool
    {
        $this->actualizados[] = [$id, $camion];
        return $this->resultadoActualizar;
    }

    public function darDeBaja(int $id): bool
    {
        $this->bajas[] = $id;
        return $this->resultadoBaja;
    }
}

class ModeloContenedoresFalso extends ModeloContenedores
{
    public array $contenedores = [['id_contenedor' => 23, 'calle' => 'Ejemplo']];
    public array $creados = [];
    public array $actualizados = [];
    public array $bajas = [];
    public bool $enUso = false;
    public bool $resultadoActualizar = true;
    public bool $resultadoBaja = true;

    public function __construct()
    {
        // Se evita abrir una conexion de base de datos.
    }

    public function listar(): array
    {
        return $this->contenedores;
    }

    public function buscarPorId(int $id): ?array
    {
        return $id === 23 ? $this->contenedores[0] : null;
    }

    public function estaEnUso(int $id): bool
    {
        return $this->enUso;
    }

    public function crear(array $contenedor): int
    {
        $this->creados[] = $contenedor;
        return 23;
    }

    public function actualizar(int $id, array $contenedor): bool
    {
        $this->actualizados[] = [$id, $contenedor];
        return $this->resultadoActualizar;
    }

    public function darDeBaja(int $id): bool
    {
        $this->bajas[] = $id;
        return $this->resultadoBaja;
    }
}

function comprobarCrud(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

function controladorConModeloFalso(string $clase, object $modelo): object
{
    $reflexion = new ReflectionClass($clase);
    $controlador = $reflexion->newInstanceWithoutConstructor();
    $reflexion->getProperty('modelo')->setValue($controlador, $modelo);
    return $controlador;
}

$modeloCamiones = new ModeloCamionesFalso();
/** @var ControladorCamiones $camiones */
$camiones = controladorConModeloFalso(ControladorCamiones::class, $modeloCamiones);

comprobarCrud(count($camiones->listar()['camiones']) === 1, 'No se listo el camion del modelo.');
$camionValido = [
    'matricula' => ' abc123 ', 'modelo' => ' Modelo de prueba ',
    'capacidad_kg' => 1000, 'estado' => 'disponible'
];
comprobarCrud(!$camiones->crear([])['ok'], 'Se acepto un camion sin datos.');
comprobarCrud(!$camiones->crear(array_replace($camionValido, ['capacidad_kg' => 0]))['ok'], 'Se acepto capacidad cero.');
comprobarCrud(!$camiones->crear(array_replace($camionValido, ['estado' => 'desconocido']))['ok'], 'Se acepto un estado invalido.');
comprobarCrud($modeloCamiones->creados === [], 'Un camion invalido llego al modelo.');

$respuesta = $camiones->crear($camionValido);
comprobarCrud($respuesta['ok'] && $respuesta['id_camion'] === 15 && !$respuesta['reactivado'], 'No se creo el camion valido.');
comprobarCrud($modeloCamiones->creados === [[
    'matricula' => 'ABC123', 'modelo' => 'Modelo de prueba',
    'capacidad_kg' => 1000, 'estado' => 'disponible'
]], 'No se normalizaron los datos del camion.');
$modeloCamiones->resultadoCrear = ['ok' => true, 'id_camion' => 15, 'reactivado' => true];
comprobarCrud(str_contains($camiones->crear($camionValido)['mensaje'], 'reactivado'), 'No se informo la reactivacion.');
$modeloCamiones->resultadoCrear = ['ok' => false, 'mensaje' => 'Matricula repetida'];
comprobarCrud(!$camiones->crear($camionValido)['ok'], 'No se propago el rechazo del modelo.');

comprobarCrud(!$camiones->actualizar(0, $camionValido)['ok'], 'Se acepto editar un camion sin ID.');
$modeloCamiones->enUso = true;
comprobarCrud($camiones->actualizar(15, $camionValido)['en_uso'] ?? false, 'Se permitio editar un camion asignado.');
comprobarCrud($camiones->darDeBaja(15)['en_uso'] ?? false, 'Se permitio dar de baja un camion asignado.');
comprobarCrud($modeloCamiones->actualizados === [] && $modeloCamiones->bajas === [], 'La operacion bloqueada llego al modelo.');
$modeloCamiones->enUso = false;
comprobarCrud($camiones->actualizar(15, $camionValido)['ok'], 'No se actualizo un camion libre.');
comprobarCrud($modeloCamiones->actualizados[0][0] === 15, 'La edicion envio un ID incorrecto.');
$modeloCamiones->resultadoActualizar = false;
comprobarCrud(!$camiones->actualizar(15, $camionValido)['ok'], 'No se propago una edicion fallida.');
comprobarCrud(!$camiones->darDeBaja(0)['ok'], 'Se acepto dar de baja un camion sin ID.');
comprobarCrud($camiones->darDeBaja(15)['ok'] && $modeloCamiones->bajas === [15], 'No se dio de baja un camion libre.');
$modeloCamiones->resultadoBaja = false;
comprobarCrud(!$camiones->darDeBaja(15)['ok'], 'No se propago una baja fallida.');

$modeloContenedores = new ModeloContenedoresFalso();
/** @var ControladorContenedores $contenedores */
$contenedores = controladorConModeloFalso(ControladorContenedores::class, $modeloContenedores);

comprobarCrud(count($contenedores->listar()['contenedores']) === 1, 'No se listo el contenedor del modelo.');
comprobarCrud(!$contenedores->obtener(99)['ok'], 'Se obtuvo un contenedor inexistente.');
comprobarCrud($contenedores->obtener(23)['contenedor']['id_contenedor'] === 23, 'No se obtuvo el contenedor existente.');
$contenedorValido = [
    'calle' => ' Calle de prueba ', 'numero' => ' 12 ', 'estado' => 'funcional',
    'tipo_residuo' => ' Reciclaje ', 'capacidad_litros' => 800,
    'latitud' => -34.9, 'longitud' => -56.2
];
comprobarCrud(!$contenedores->crear([])['ok'], 'Se acepto un contenedor sin datos.');
comprobarCrud(!$contenedores->crear(array_replace($contenedorValido, ['capacidad_litros' => 0]))['ok'], 'Se acepto capacidad cero.');
comprobarCrud(!$contenedores->crear(array_replace($contenedorValido, ['latitud' => -34.5]))['ok'], 'Se acepto una latitud fuera de Montevideo.');
comprobarCrud(!$contenedores->crear(array_replace($contenedorValido, ['longitud' => -56.5]))['ok'], 'Se acepto una longitud fuera de Montevideo.');
comprobarCrud(!$contenedores->crear(array_replace($contenedorValido, ['estado' => 'desconocido']))['ok'], 'Se acepto un estado invalido.');
comprobarCrud($modeloContenedores->creados === [], 'Un contenedor invalido llego al modelo.');

$respuesta = $contenedores->crear($contenedorValido);
comprobarCrud($respuesta['ok'] && $respuesta['id_contenedor'] === 23, 'No se creo el contenedor valido.');
comprobarCrud($modeloContenedores->creados === [[
    'calle' => 'Calle de prueba', 'numero' => '12', 'estado' => 'funcional',
    'tipo_residuo' => 'Reciclaje', 'capacidad_litros' => 800,
    'latitud' => -34.9, 'longitud' => -56.2
]], 'No se normalizaron los datos del contenedor.');
comprobarCrud($contenedores->crear(array_replace($contenedorValido, ['latitud' => -35.15, 'longitud' => -56.45]))['ok'], 'Se rechazo el limite inferior de coordenadas.');
comprobarCrud($contenedores->crear(array_replace($contenedorValido, ['latitud' => -34.65, 'longitud' => -55.85]))['ok'], 'Se rechazo el limite superior de coordenadas.');

comprobarCrud(!$contenedores->actualizar(0, $contenedorValido)['ok'], 'Se acepto editar un contenedor sin ID.');
$modeloContenedores->enUso = true;
comprobarCrud($contenedores->actualizar(23, $contenedorValido)['en_uso'] ?? false, 'Se permitio editar un contenedor en ruta.');
comprobarCrud($contenedores->darDeBaja(23)['en_uso'] ?? false, 'Se permitio dar de baja un contenedor en ruta.');
comprobarCrud($modeloContenedores->actualizados === [] && $modeloContenedores->bajas === [], 'La operacion bloqueada llego al modelo.');
$modeloContenedores->enUso = false;
comprobarCrud($contenedores->actualizar(23, $contenedorValido)['ok'], 'No se actualizo un contenedor libre.');
comprobarCrud($modeloContenedores->actualizados[0][0] === 23, 'La edicion envio un ID incorrecto.');
$modeloContenedores->resultadoActualizar = false;
comprobarCrud(!$contenedores->actualizar(23, $contenedorValido)['ok'], 'No se propago una edicion fallida.');
comprobarCrud(!$contenedores->darDeBaja(0)['ok'], 'Se acepto dar de baja un contenedor sin ID.');
comprobarCrud($contenedores->darDeBaja(23)['ok'] && $modeloContenedores->bajas === [23], 'No se dio de baja un contenedor libre.');
$modeloContenedores->resultadoBaja = false;
comprobarCrud(!$contenedores->darDeBaja(23)['ok'], 'No se propago una baja fallida.');

echo "CRUD unitarias: camiones y contenedores correctos\n";
