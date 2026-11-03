<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../.github/workflows/conexion.php';

$ci = trim($argv[1] ?? '');
if ($ci === '' || strlen($ci) > 8) {
    fwrite(STDERR, "Uso: php scripts/crear_admin.php CI_DE_CUENTA_REGISTRADA\n");
    exit(1);
}

$conexion = null;
try {
    $conexion = conectarBaseDeDatos();
    $conexion->beginTransaction();

    if ((int) $conexion->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        throw new RuntimeException('Ya existe una cuenta administradora.');
    }

    $buscar = $conexion->prepare('SELECT estado_cuenta FROM usuarios WHERE CI = ? FOR UPDATE');
    $buscar->execute([$ci]);
    if ($buscar->fetchColumn() !== 'pendiente') {
        throw new RuntimeException('Registrá primero una cuenta nueva con esa CI desde la página de registro.');
    }

    $aprobar = $conexion->prepare("UPDATE usuarios SET estado_cuenta = 'aprobado' WHERE CI = ?");
    $aprobar->execute([$ci]);
    $asignar = $conexion->prepare('INSERT INTO admins (ci) VALUES (?)');
    $asignar->execute([$ci]);
    $conexion->commit();

    echo "Cuenta administradora habilitada. Iniciá sesión con el correo y contraseña registrados.\n";
} catch (Throwable $error) {
    if ($conexion instanceof PDO && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
