<?php
declare(strict_types=1);

class ModeloCamiones
{
    private PDO $conexion;

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
            'SELECT * FROM camiones WHERE activo = 1 ORDER BY id_camion DESC'
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $camion): int
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO camiones (matricula, modelo, capacidad_kg, estado)
             VALUES (?, ?, ?, ?)'
        );
        $consulta->execute([
            $camion['matricula'],
            $camion['modelo'],
            $camion['capacidad_kg'],
            $camion['estado']
        ]);
        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(int $id, array $camion): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE camiones
             SET matricula = ?, modelo = ?, capacidad_kg = ?, estado = ?
             WHERE id_camion = ? AND activo = 1'
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
            'UPDATE camiones SET activo = 0 WHERE id_camion = ? AND activo = 1'
        );
        $consulta->execute([$id]);
        return $consulta->rowCount() === 1;
    }
}