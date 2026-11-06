<?php
declare(strict_types=1);

function conectarBaseDeDatos(): PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $nombre = getenv('DB_NAME') ?: 'novacycle';
    $usuario = getenv('DB_USER') ?: 'root';
    $clave = getenv('DB_PASSWORD');
    $clave = $clave === false ? '' : $clave;

    return new PDO(
        "mysql:host={$host};dbname={$nombre};charset=utf8mb4",
        $usuario,
        $clave,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}
