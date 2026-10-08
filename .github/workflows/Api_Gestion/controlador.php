<?php
declare(strict_types=1);

require_once __DIR__ . '/modelo.php';

class ControladorContenedores
{
    private ModeloContenedores $modelo;
    private const ESTADOS = ['funcional', 'roto', 'desbordado'];

    public function __construct()
    {
        $this->modelo = new ModeloContenedores();
    }

    public function listar(): array
    {
        return ['ok' => true, 'contenedores' => $this->modelo->listar()];
    }

    public function obtener(int $id): array
    {
        $contenedor = $this->modelo->buscarPorId($id);
        if ($contenedor === null) {
            return ['ok' => false, 'mensaje' => 'Contenedor no encontrado.'];
        }
        return ['ok' => true, 'contenedor' => $contenedor];
    }

    public function crear(array $datos): array
    {
        $validacion = $this->validar($datos);
        if (!$validacion['ok']) {
            return $validacion;
        }
        $id = $this->modelo->crear($validacion['contenedor']);
        return ['ok' => true, 'mensaje' => 'Contenedor creado correctamente.', 'id_contenedor' => $id];
    }

    public function actualizar(int $id, array $datos): array
    {
        $validacion = $this->validar($datos);
        if (!$validacion['ok']) {
            return $validacion;
        }
        if (!$this->modelo->actualizar($id, $validacion['contenedor'])) {
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el contenedor.'];
        }
        return ['ok' => true, 'mensaje' => 'Contenedor actualizado correctamente.'];
    }

    public function darDeBaja(int $id): array
    {
        if (!$this->modelo->darDeBaja($id)) {
            return ['ok' => false, 'mensaje' => 'No se pudo dar de baja el contenedor.'];
        }
        return ['ok' => true, 'mensaje' => 'Contenedor dado de baja correctamente.'];
    }

    private function validar(array $datos): array
    {
        $calle = trim($datos['calle'] ?? '');
        $numero = trim($datos['numero'] ?? '');
        $estado = $datos['estado'] ?? '';
        $tipoResiduo = trim($datos['tipo_residuo'] ?? '');
        $capacidad = (int) ($datos['capacidad_litros'] ?? 0);
        $latitud = filter_var($datos['latitud'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitud = filter_var($datos['longitud'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($calle === '' || $numero === '' || $tipoResiduo === '' || $capacidad <= 0 || $latitud === false || $longitud === false) {
            return ['ok' => false, 'mensaje' => 'Completá todos los campos correctamente.'];
        }
        if ($latitud < -35.15 || $latitud > -34.65 || $longitud < -56.45 || $longitud > -55.85) {
            return ['ok' => false, 'mensaje' => 'Las coordenadas deben corresponder a una ubicación de Montevideo.'];
        }
        if (!in_array($estado, self::ESTADOS, true)) {
            return ['ok' => false, 'mensaje' => 'El estado no es válido.'];
        }

        return ['ok' => true, 'contenedor' => [
            'calle' => $calle,
            'numero' => $numero,
            'estado' => $estado,
            'tipo_residuo' => $tipoResiduo,
            'capacidad_litros' => $capacidad,
            'latitud' => $latitud,
            'longitud' => $longitud
        ]];
    }
}
