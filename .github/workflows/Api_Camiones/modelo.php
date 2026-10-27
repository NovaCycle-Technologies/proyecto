<?php
declare(strict_types=1);

class ModeloCamiones
{
    private PDO $conexion;

    private const RUTA_VIGENTE = "SELECT 1 FROM asignaciones_ruta a
        INNER JOIN rutas r ON r.id_ruta = a.id_ruta
        WHERE a.id_camion = c.id_camion
          AND a.fecha >= CURDATE()
          AND a.estado IN ('asignada', 'en_curso')
          AND r.activa = 1";

    public function __construct()
    {
        $this->conexion = new PDO(
            'mysql:host=localhost;dbname=novacycle;charset=utf8mb4',
            'root',
            ''
        );
        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function listar(): array
    {
        $consulta = $this->conexion->query(
            'SELECT c.*, EXISTS (' . self::RUTA_VIGENTE . ') AS en_uso
             FROM camiones c WHERE c.activo = 1 ORDER BY c.id_camion DESC'
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function estaEnUso(int $id): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT EXISTS (SELECT 1 FROM camiones c WHERE c.id_camion = ?
             AND EXISTS (' . self::RUTA_VIGENTE . '))'
        );
        $consulta->execute([$id]);
        return (bool) $consulta->fetchColumn();
    }

    public function crear(array $camion): array
    {
        $this->conexion->beginTransaction();
        try {
            $buscar = $this->conexion->prepare(
                'SELECT id_camion, activo FROM camiones WHERE matricula = ? FOR UPDATE'
            );
            $buscar->execute([$camion['matricula']]);
            $existente = $buscar->fetch(PDO::FETCH_ASSOC);

            if ($existente !== false) {
                if ((int) $existente['activo'] === 1) {
                    $this->conexion->rollBack();
                    return ['ok' => false, 'mensaje' => 'Ya existe un camión activo con esa matrícula.'];
                }

                $reactivar = $this->conexion->prepare(
                    'UPDATE camiones c
                     SET modelo = ?, capacidad_kg = ?, estado = ?, activo = 1
                     WHERE c.id_camion = ? AND c.activo = 0
                       AND NOT EXISTS (' . self::RUTA_VIGENTE . ')'
                );
                $reactivar->execute([
                    $camion['modelo'], $camion['capacidad_kg'], $camion['estado'], $existente['id_camion']
                ]);
                if ($reactivar->rowCount() !== 1) {
                    $this->conexion->rollBack();
                    return ['ok' => false, 'mensaje' => 'No se puede reactivar un camión asignado a una ruta vigente.'];
                }

                $this->conexion->commit();
                return ['ok' => true, 'id_camion' => (int) $existente['id_camion'], 'reactivado' => true];
            }

            $insertar = $this->conexion->prepare(
                'INSERT INTO camiones (matricula, modelo, capacidad_kg, estado)
                 VALUES (?, ?, ?, ?)'
            );
            $insertar->execute([
                $camion['matricula'], $camion['modelo'], $camion['capacidad_kg'], $camion['estado']
            ]);
            $id = (int) $this->conexion->lastInsertId();
            $this->conexion->commit();
            return ['ok' => true, 'id_camion' => $id, 'reactivado' => false];
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function actualizar(int $id, array $camion): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE camiones c
             SET matricula = ?, modelo = ?, capacidad_kg = ?, estado = ?
             WHERE c.id_camion = ? AND c.activo = 1
               AND NOT EXISTS (' . self::RUTA_VIGENTE . ')'
        );
        $consulta->execute([
            $camion['matricula'],
            $camion['modelo'],
            $camion['capacidad_kg'],
            $camion['estado'],
            $id
        ]);
        return $consulta->rowCount() === 1;
    }

    public function darDeBaja(int $id): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE camiones c SET activo = 0
             WHERE c.id_camion = ? AND c.activo = 1
               AND NOT EXISTS (' . self::RUTA_VIGENTE . ')'
        );
        $consulta->execute([$id]);
        return $consulta->rowCount() === 1;
    }
}
