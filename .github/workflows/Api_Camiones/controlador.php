<?php
declare(strict_types=1);

require_once __DIR__ . '/modelo.php';

class ControladorCamiones
{
    private ModeloCamiones $modelo;
    private const ESTADOS = ['disponible', 'en_mantenimiento', 'fuera_de_servicio'];

    public function __construct()
    {
        $this->modelo = new ModeloCamiones();
    }

    public function listar(): array
    {
        return ['ok' => true, 'camiones' => $this->modelo->listar()];
    }

    public function crear(array $datos): array
    {
        $validacion = $this->validar($datos);
        if (!$validacion['ok']) {
            return $validacion;
        }

        $id = $this->modelo->crear($validacion['camion']);
        return ['ok' => true, 'mensaje' => 'Camión registrado correctamente.', 'id_camion' => $id];
    }

    public function actualizar(int $id, array $datos): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'mensaje' => 'El camión no es válido.'];
        }

        $validacion = $this->validar($datos);
        if (!$validacion['ok']) {
            return $validacion;
        }

        if (!$this->modelo->actualizar($id, $validacion['camion'])) {
            return ['ok' => false, 'mensaje' => 'No se pudo actualizar el camión.'];
        }
        return ['ok' => true, 'mensaje' => 'Camión actualizado correctamente.'];
    }

    public function darDeBaja(int $id): array
    {
        if (!$this->modelo->darDeBaja($id)) {
            return ['ok' => false, 'mensaje' => 'No se pudo dar de baja el camión.'];
        }
        return ['ok' => true, 'mensaje' => 'Camión dado de baja correctamente.'];
    }

    private function validar(array $datos): array
    {
        $matricula = strtoupper(trim($datos['matricula'] ?? ''));
        $modelo = trim($datos['modelo'] ?? '');
        $capacidad = (int) ($datos['capacidad_kg'] ?? 0);
        $estado = $datos['estado'] ?? '';

        if ($matricula === '' || $modelo === '' || $capacidad <= 0) {
            return ['ok' => false, 'mensaje' => 'Completá todos los campos correctamente.'];
        }
        if (!in_array($estado, self::ESTADOS, true)) {
            return ['ok' => false, 'mensaje' => 'El estado no es válido.'];
        }

        return ['ok' => true, 'camion' => [
            'matricula' => $matricula,
            'modelo' => $modelo,
            'capacidad_kg' => $capacidad,
            'estado' => $estado
        ]];
    }
}