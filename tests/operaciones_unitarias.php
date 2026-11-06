<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/Api_Operaciones/controlador.php';

class ModeloOperacionesFalso extends ModeloOperaciones
{
    public array $rutasCreadas = [];
    public array $paradasCreadas = [];
    public array $incidenciasCreadas = [];
    public array $asignacionesCreadas = [];

    public function __construct()
    {
        // Esta prueba no abre una conexión a MySQL.
    }

    public function crearRuta(string $nombre, string $zona): int
    {
        $this->rutasCreadas[] = [$nombre, $zona];
        return 42;
    }

    public function crearParada(int $ruta, int $contenedor, int $orden): bool
    {
        $this->paradasCreadas[] = [$ruta, $contenedor, $orden];
        return true;
    }

    public function asignarRuta(array $datos): void
    {
        $this->asignacionesCreadas[] = $datos;
    }

    public function contenedorEnRuta(int $id, string $ci): ?array
    {
        return $id === 5 && $ci === 'P1' ? ['ubicacion' => 'Calle de prueba 1'] : null;
    }

    public function crearIncidencia(array $datos, string $ci): int
    {
        $this->incidenciasCreadas[] = [$datos, $ci];
        return 7;
    }
}

function verificar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$modelo = new ModeloOperacionesFalso();
$controlador = new ControladorOperaciones($modelo);

verificar(!$controlador->crearRuta(['nombre' => ' ', 'zona' => 'Centro'])['ok'], 'Se aceptó una ruta sin nombre.');
verificar(count($modelo->rutasCreadas) === 0, 'Una ruta inválida llegó al modelo.');
$resultado = $controlador->crearRuta(['nombre' => ' Ruta 1 ', 'zona' => ' Centro ']);
verificar($resultado['ok'] && $resultado['id_ruta'] === 42, 'No se creó una ruta válida.');
verificar($modelo->rutasCreadas === [['Ruta 1', 'Centro']], 'No se limpiaron los datos de la ruta.');

verificar(!$controlador->crearParada(['id_ruta' => 0, 'id_contenedor' => 5])['ok'], 'Se aceptó una parada sin ruta.');
verificar(count($modelo->paradasCreadas) === 0, 'Una parada inválida llegó al modelo.');
verificar($controlador->crearParada(['id_ruta' => 42, 'id_contenedor' => 5, 'orden' => 0])['ok'], 'No se aceptó la parada válida.');
verificar($modelo->paradasCreadas === [[42, 5, 1]], 'El orden de la parada no se corrigió a uno.');

verificar(!$controlador->asignarRuta(['id_ruta' => 42])['ok'], 'Se aceptó una asignación incompleta.');
verificar(count($modelo->asignacionesCreadas) === 0, 'Una asignación inválida llegó al modelo.');

verificar(!$controlador->crearIncidencia(['tipo' => 'Desborde', 'detalle' => ' '], 'P1')['ok'], 'Se aceptó una incidencia sin detalle.');
verificar(!$controlador->crearIncidencia(['tipo' => 'Rotura', 'detalle' => 'Problema'], 'P1')['ok'], 'Se aceptó una incidencia sin ubicación.');
verificar(!$controlador->crearIncidencia([
    'id_contenedor' => 6, 'tipo' => 'Desborde', 'detalle' => 'Problema'
], 'P1')['ok'], 'Se aceptó un contenedor fuera de la ruta.');
verificar(count($modelo->incidenciasCreadas) === 0, 'Una incidencia inválida llegó al modelo.');

$resultado = $controlador->crearIncidencia([
    'id_contenedor' => 5, 'tipo' => ' Desborde ', 'detalle' => ' Está lleno '
], 'P1');
verificar($resultado['ok'], 'No se aceptó una incidencia en la ruta.');
verificar($modelo->incidenciasCreadas[0] === [[
    'ubicacion' => 'Calle de prueba 1', 'tipo' => 'Desborde',
    'detalle' => 'Está lleno', 'id_contenedor' => 5
], 'P1'], 'La incidencia vinculada no usó la ubicación del contenedor.');

$resultado = $controlador->crearIncidencia([
    'ubicacion' => ' Otra calle ', 'tipo' => ' Rotura ', 'detalle' => ' Problema '
], 'P1');
verificar($resultado['ok'], 'No se aceptó la incidencia en otro lugar.');
verificar($modelo->incidenciasCreadas[1][0] === [
    'ubicacion' => 'Otra calle', 'tipo' => 'Rotura',
    'detalle' => 'Problema', 'id_contenedor' => null
], 'La incidencia de otro lugar no se preparó correctamente.');

echo "Operaciones unitarias: validaciones de rutas, paradas e incidencias correctas\n";
