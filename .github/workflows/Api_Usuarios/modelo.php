<?php
declare(strict_types=1);

class ModeloUsuarios
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = new PDO('mysql:host=localhost;dbname=novacycle;charset=utf8mb4', 'root', '');
        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function buscarPorCI(string $ci): ?array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM usuarios WHERE CI = ?');
        $consulta->execute([$ci]);
        return $consulta->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function buscarPorEmail(string $email): ?array
    {
        $consulta = $this->conexion->prepare('SELECT * FROM usuarios WHERE Email = ?');
        $consulta->execute([$email]);
        return $consulta->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function guardarUsuario(array $usuario): void
    {
        $consulta = $this->conexion->prepare(
            "INSERT INTO usuarios (CI, Nombre, Apellido, Email, password, estado_cuenta, rol_solicitado)
             VALUES (?, ?, ?, ?, ?, 'pendiente', ?)"
        );
        $consulta->execute([$usuario['ci'], $usuario['nombre'], $usuario['apellido'], $usuario['email'], $usuario['password'], $usuario['rol_solicitado']]);
    }

    public function obtenerRol(string $ci): ?string
    {
        $tablas = ['admin_municipal' => 'admins', 'operario' => 'operarios', 'peon' => 'peones', 'conductor' => 'conductores'];
        foreach ($tablas as $rol => $tabla) {
            $consulta = $this->conexion->prepare("SELECT ci FROM {$tabla} WHERE ci = ?");
            $consulta->execute([$ci]);
            if ($consulta->fetchColumn() !== false) {
                return $rol;
            }
        }
        return null;
    }

    public function listarPendientes(): array
    {
        $consulta = $this->conexion->query(
            "SELECT CI AS ci, Nombre AS nombre, Apellido AS apellido, Email AS email, rol_solicitado
             FROM usuarios WHERE estado_cuenta = 'pendiente' ORDER BY Nombre, Apellido"
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function aprobarYAsignarRol(string $ci, string $rol): bool
    {
        if (!in_array($rol, ['peon', 'conductor', 'operario'], true)) {
            return false;
        }
        $this->conexion->beginTransaction();
        try {
            $actualizar = $this->conexion->prepare("UPDATE usuarios SET estado_cuenta = 'aprobado' WHERE CI = ? AND estado_cuenta = 'pendiente'");
            $actualizar->execute([$ci]);
            if ($actualizar->rowCount() !== 1) {
                $this->conexion->rollBack();
                return false;
            }
            if ($rol === 'operario') {
                $agregar = $this->conexion->prepare('INSERT INTO operarios (ci) VALUES (?)');
                $agregar->execute([$ci]);
            } else {
                $operario = $this->conexion->prepare('INSERT INTO operarios (ci) VALUES (?)');
                $operario->execute([$ci]);
                $tabla = $rol === 'peon' ? 'peones' : 'conductores';
                $agregar = $this->conexion->prepare("INSERT INTO {$tabla} (ci) VALUES (?)");
                $agregar->execute([$ci]);
            }
            $this->conexion->commit();
            return true;
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }
}