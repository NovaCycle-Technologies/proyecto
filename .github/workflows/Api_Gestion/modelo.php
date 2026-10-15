<?php
declare(strict_types=1);

class ModeloContenedores
{
    private PDO $conexion;

    private const RUTA_ACTIVA = 'SELECT 1 FROM paradas_ruta p
        INNER JOIN rutas r ON r.id_ruta = p.id_ruta
        WHERE p.id_contenedor = c.id_contenedor AND r.activa = 1';

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
            'SELECT c.*, EXISTS (' . self::RUTA_ACTIVA . ') AS en_uso
             FROM contenedores c WHERE c.activo = 1 ORDER BY c.id_contenedor DESC'
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT c.*, EXISTS (' . self::RUTA_ACTIVA . ') AS en_uso
             FROM contenedores c WHERE c.id_contenedor = ? AND c.activo = 1'
        );
        $consulta->execute([$id]);
        return $consulta->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function estaEnUso(int $id): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT EXISTS (SELECT 1 FROM contenedores c WHERE c.id_contenedor = ?
             AND EXISTS (' . self::RUTA_ACTIVA . '))'
        );
        $consulta->execute([$id]);
        return (bool) $consulta->fetchColumn();
    }

    public function crear(array $contenedor): int
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO contenedores (calle, numero, estado, tipo_residuo, capacidad_litros, latitud, longitud)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $consulta->execute([
            $contenedor['calle'],
            $contenedor['numero'],
            $contenedor['estado'],
            $contenedor['tipo_residuo'],
            $contenedor['capacidad_litros'],
            $contenedor['latitud'],
            $contenedor['longitud']
        ]);
        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(int $id, array $contenedor): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE contenedores c
             SET calle = ?, numero = ?, estado = ?, tipo_residuo = ?, capacidad_litros = ?, latitud = ?, longitud = ?
             WHERE c.id_contenedor = ? AND c.activo = 1
               AND NOT EXISTS (' . self::RUTA_ACTIVA . ')'
        );
        $consulta->execute([
            $contenedor['calle'],
            $contenedor['numero'],
            $contenedor['estado'],
            $contenedor['tipo_residuo'],
            $contenedor['capacidad_litros'],
            $contenedor['latitud'],
            $contenedor['longitud'],
            $id
        ]);
        return $consulta->rowCount() === 1;
    }

    public function darDeBaja(int $id): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE contenedores c SET activo = 0
             WHERE c.id_contenedor = ? AND c.activo = 1
               AND NOT EXISTS (' . self::RUTA_ACTIVA . ')'
        );
        $consulta->execute([$id]);
        return $consulta->rowCount() === 1;
    }
}
