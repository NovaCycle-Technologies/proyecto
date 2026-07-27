<?php
declare(strict_types=1);

class ModeloContenedores
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
            'SELECT * FROM contenedores WHERE activo = 1 ORDER BY id_contenedor DESC'
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT * FROM contenedores WHERE id_contenedor = ? AND activo = 1'
        );
        $consulta->execute([$id]);
        return $consulta->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function crear(array $contenedor): int
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO contenedores (calle, numero, estado, tipo_residuo, capacidad_litros)
             VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $contenedor['calle'],
            $contenedor['numero'],
            $contenedor['estado'],
            $contenedor['tipo_residuo'],
            $contenedor['capacidad_litros']
        ]);
        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(int $id, array $contenedor): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE contenedores
             SET calle = ?, numero = ?, estado = ?, tipo_residuo = ?, capacidad_litros = ?
             WHERE id_contenedor = ? AND activo = 1'
        );
        $consulta->execute([
            $contenedor['calle'],
            $contenedor['numero'],
            $contenedor['estado'],
            $contenedor['tipo_residuo'],
            $contenedor['capacidad_litros'],
            $id
        ]);
        return $consulta->rowCount() === 1;
    }

    public function darDeBaja(int $id): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE contenedores SET activo = 0 WHERE id_contenedor = ? AND activo = 1'
        );
        $consulta->execute([$id]);
        return $consulta->rowCount() === 1;
    }
}