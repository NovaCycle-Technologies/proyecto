<?php
declare(strict_types=1);

function puede(string $rol, string $permiso): bool
{
    $permisos = [
        'admin_municipal' => ['gestionar_usuarios', 'gestionar_contenedores', 'gestionar_vehiculos', 'asignar_rutas', 'generar_reportes'],
        'operario' => ['ver_cuadrilla', 'ver_rutas_asignadas', 'actualizar_incidencia_asignada', 'registrar_recoleccion'],
        'peon' => ['ver_rutas_asignadas', 'registrar_recoleccion', 'reportar_incidencia'],
        'conductor' => ['ver_rutas_asignadas', 'registrar_recoleccion', 'reportar_incidencia', 'reportar_estado_camion']
    ];
    return in_array($permiso, $permisos[$rol] ?? [], true);
}