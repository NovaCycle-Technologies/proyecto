<?php
declare(strict_types=1);

if (getenv('NOVACYCLE_TEST_ISOLATED') !== '1') {
    fwrite(STDERR, "Usá una base de pruebas aislada y definí NOVACYCLE_TEST_ISOLATED=1.\n");
    exit(1);
}

require_once __DIR__ . '/../.github/workflows/conexion.php';

function crearCuentasPrueba(array $roles): array
{
    $conexion = conectarBaseDeDatos();
    $cuentas = [];
    $tablas = [
        'admin_municipal' => 'admins',
        'peon' => 'peones',
        'conductor' => 'conductores',
        'operario' => 'operarios'
    ];

    try {
        foreach ($roles as $rol) {
            do {
                $ci = 'T' . substr(bin2hex(random_bytes(4)), 0, 7);
                $buscar = $conexion->prepare('SELECT 1 FROM usuarios WHERE CI = ?');
                $buscar->execute([$ci]);
            } while ($buscar->fetchColumn() !== false);

            $insertar = $conexion->prepare(
                "INSERT INTO usuarios (CI, Nombre, Apellido, Email, password, estado_cuenta, rol_solicitado)
                 VALUES (?, 'Prueba', 'Temporal', ?, ?, 'aprobado', ?)"
            );
            $insertar->execute([
                $ci, strtolower($ci) . '@test.local', password_hash('ClavePrueba123', PASSWORD_DEFAULT),
                $rol === 'admin_municipal' ? 'peon' : $rol
            ]);
            $cuentas[$rol] = $ci;
            $asignar = $conexion->prepare("INSERT INTO {$tablas[$rol]} (ci) VALUES (?)");
            $asignar->execute([$ci]);
        }
        return [$conexion, $cuentas];
    } catch (Throwable $error) {
        eliminarCuentasPrueba($conexion, $cuentas);
        throw $error;
    }
}

function eliminarCuentasPrueba(PDO $conexion, array $cuentas): void
{
    $eliminar = $conexion->prepare('DELETE FROM usuarios WHERE CI = ?');
    foreach ($cuentas as $ci) {
        $eliminar->execute([$ci]);
    }
}
