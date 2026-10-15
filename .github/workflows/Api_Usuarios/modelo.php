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
        $tablas = ['admin_municipal' => 'admins', 'peon' => 'peones', 'conductor' => 'conductores', 'operario' => 'operarios'];
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

    public function listarUsuarios(): array
    {
        $consulta = $this->conexion->query(
            "SELECT u.CI AS ci, u.Nombre AS nombre, u.Apellido AS apellido,
                    u.Email AS email, u.estado_cuenta, u.rol_solicitado,
                    CASE WHEN a.ci IS NOT NULL THEN 'admin_municipal'
                         WHEN p.ci IS NOT NULL THEN 'peon'
                         WHEN c.ci IS NOT NULL THEN 'conductor'
                         WHEN o.ci IS NOT NULL THEN 'operario'
                         ELSE NULL END AS rol
             FROM usuarios u
             LEFT JOIN admins a ON a.ci = u.CI
             LEFT JOIN peones p ON p.ci = u.CI
             LEFT JOIN conductores c ON c.ci = u.CI
             LEFT JOIN operarios o ON o.ci = u.CI
             ORDER BY u.Nombre, u.Apellido, u.CI"
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    private function tieneActividad(string $ci): bool
    {
        $referencias = [
            ['asignaciones_ruta', 'ci_conductor'],
            ['asignaciones_ruta', 'ci_peon'],
            ['recolecciones', 'ci_trabajador'],
            ['incidencias', 'ci_reportante'],
            ['ingresos_residuos', 'ci_operario'],
            ['estados_maquinaria', 'ci_operario']
        ];
        foreach ($referencias as [$tabla, $columna]) {
            $consulta = $this->conexion->prepare("SELECT 1 FROM {$tabla} WHERE {$columna} = ? LIMIT 1");
            $consulta->execute([$ci]);
            if ($consulta->fetchColumn() !== false) {
                return true;
            }
        }
        return false;
    }

    public function modificarUsuario(string $ci, string $nombre, string $apellido, string $email, string $rol): array
    {
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare('SELECT estado_cuenta FROM usuarios WHERE CI = ? FOR UPDATE');
            $consulta->execute([$ci]);
            $estado = $consulta->fetchColumn();
            if ($estado === false) {
                $this->conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'El usuario ya no existe.'];
            }

            $rolActual = $this->obtenerRol($ci);
            if ($rolActual === 'admin_municipal') {
                $this->conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'Las cuentas administradoras no se modifican desde este panel.'];
            }
            $duplicado = $this->conexion->prepare('SELECT 1 FROM usuarios WHERE Email = ? AND CI <> ? LIMIT 1');
            $duplicado->execute([$email, $ci]);
            if ($duplicado->fetchColumn() !== false) {
                $this->conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'Ese correo ya pertenece a otro usuario.'];
            }
            if ($estado === 'aprobado' && $rolActual !== $rol && $this->tieneActividad($ci)) {
                $this->conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'No se puede cambiar el rol: el usuario tiene rutas, incidencias o registros asociados.'];
            }

            $actualizar = $this->conexion->prepare(
                'UPDATE usuarios SET Nombre = ?, Apellido = ?, Email = ?, rol_solicitado = ? WHERE CI = ?'
            );
            $actualizar->execute([$nombre, $apellido, $email, $rol, $ci]);

            if ($estado === 'aprobado' && $rolActual !== $rol) {
                foreach (['peones', 'conductores', 'operarios'] as $tabla) {
                    $eliminar = $this->conexion->prepare("DELETE FROM {$tabla} WHERE ci = ?");
                    $eliminar->execute([$ci]);
                }
                $tabla = ['peon' => 'peones', 'conductor' => 'conductores', 'operario' => 'operarios'][$rol];
                $agregar = $this->conexion->prepare("INSERT INTO {$tabla} (ci) VALUES (?)");
                $agregar->execute([$ci]);
            }

            $this->conexion->commit();
            return ['ok' => true, 'mensaje' => 'Usuario modificado correctamente.'];
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            return ['ok' => false, 'mensaje' => 'No se pudo modificar el usuario.'];
        }
    }

    public function eliminarUsuario(string $ci): array
    {
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare('SELECT CI FROM usuarios WHERE CI = ? FOR UPDATE');
            $consulta->execute([$ci]);
            if ($consulta->fetchColumn() === false) {
                $this->conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'El usuario ya no existe.'];
            }
            if ($this->obtenerRol($ci) === 'admin_municipal') {
                $this->conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'No se pueden eliminar cuentas administradoras desde este panel.'];
            }
            if ($this->tieneActividad($ci)) {
                $this->conexion->rollBack();
                return ['ok' => false, 'mensaje' => 'No se puede eliminar: el usuario tiene rutas, incidencias o registros asociados.'];
            }

            $eliminar = $this->conexion->prepare('DELETE FROM usuarios WHERE CI = ?');
            $eliminar->execute([$ci]);
            $this->conexion->commit();
            return ['ok' => true, 'mensaje' => 'Usuario eliminado correctamente.'];
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            return ['ok' => false, 'mensaje' => 'No se pudo eliminar el usuario.'];
        }
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
            $tabla = ['peon' => 'peones', 'conductor' => 'conductores', 'operario' => 'operarios'][$rol];
            $agregar = $this->conexion->prepare("INSERT INTO {$tabla} (ci) VALUES (?)");
            $agregar->execute([$ci]);
            $this->conexion->commit();
            return true;
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }
}
