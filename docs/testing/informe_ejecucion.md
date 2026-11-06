# Informe de ejecución y validación - NovaCycle

**ID:** IE-NC-2026-01

**Fecha:** 06/11/2026

**Versión examinada:** base Git `42a65e7` de `segundo_avance`, con los cambios de testing y corrección descritos en este informe.

**Estado general:** verificación automatizada aprobada; validación con usuarios pendiente.

## 1. Ambiente y método

Se construyó un proyecto Docker independiente llamado `novacycle_qa_extra_20261106`, con web en puerto 18080 y volumen MariaDB propio. Se ejecutaron las pruebas de forma secuencial dentro del contenedor `web`, PHP 8.2.35 y MariaDB 11.4 (imagen declarada en `compose.yaml`). La base se inicializó con `database/datos_demo.sql`. El proyecto Docker habitual `proyecto` no se usó para estas pruebas. Los tests HTTP insertan datos temporales, verifican las respuestas y, cuando corresponde, SQL; usan bloques `finally` para limpiar.

Cada script aprobado terminó con código de salida 0. Un script de integración debe ejecutarse solo con `NOVACYCLE_TEST_ISOLATED=1` y una base separada. No se midió cobertura porcentual de líneas/ramas ni rendimiento; no se informa un porcentaje ficticio.

## 2. Resultado de las pruebas automáticas

| Script | Nivel | Resultado observado |
| --- | --- | --- |
| `operaciones_unitarias.php` | Unitaria | Aprobó: validaciones de rutas, paradas e incidencias. |
| `usuarios_unitarias.php` | Unitaria | Aprobó: registro, login por cuatro roles y validaciones CRUD. |
| `crud_unitarias.php` | Unitaria | Aprobó: alta, edición, baja y bloqueo de camiones/contenedores en uso. |
| `sesiones.php` | Integración/componente | Aprobó: alternancia directa de dos sesiones asociadas a cuentas QA. |
| `sesiones_http.php` | Integración/API | Aprobó: dos sesiones independientes y permisos. |
| `usuarios_http.php` | Integración/API | Aprobó: permisos, CRUD y persistencia de usuarios. |
| `autenticacion_http.php` | Integración/API | Aprobó: registro real, aprobación, login y cookie para peón/conductor/operario. |
| `contenedores_ruta_http.php` | Integración/API | Aprobó: bloqueo y liberación de contenedores vinculados. |
| `contenedores_crud_http.php` | Integración/API | Aprobó: CRUD real, validaciones, permisos y persistencia de contenedores. |
| `camiones_db_http.php` | Integración/API | Aprobó: edición, baja, reactivación y matrícula duplicada. |
| `camion_asignado_http.php` | Integración/API | Aprobó: bloqueo de camión en uso, liberación y persistencia. |
| `rutas_recoleccion_http.php` | Integración/API | Aprobó: ruta, parada, asignación, recolección, permisos y SQL. |
| `incidencias_http.php` | Integración/API | Aprobó: registro, seguimiento, mapa y cierre. |
| `conductor_operario_http.php` | Integración/API | Aprobó: permisos, camión propio/ajeno, ingreso, maquinaria, resumen y errores `400`. |
| `dashboard_alertas_http.php` | Integración/API | Aprobó: deltas de métricas, datos de origen de alertas y mapa público. |

**Resumen verificable:** 15 scripts ejecutados, 15 aprobados, 0 fallidos (3 unitarios y 12 de integración/componente/API). Cada script contiene varias aserciones; 15 no es el número de aserciones ni un porcentaje de cobertura. Los datos del dashboard se comprobaron mediante incrementos respecto del estado inicial, no con números fijos que dependan del respaldo.

## 3. Defectos encontrados y regresión

Se detectaron y corrigieron dos defectos antes del cierre de la ejecución: `DEF-001` (respuestas HTTP `201/200` en operaciones rechazadas) y `DEF-002` (conductor podía modificar un camión no asignado). Los casos negativos nuevos y el resto de la suite pasaron después de las correcciones. Detalle y reproducción en `defectos.md`.

No se declara que todo el sistema está libre de errores: los tests automáticos no observan el DOM, el trazado visual del mapa ni la facilidad de uso.

## 4. Validación e interfaz: estado honesto

| Caso | Qué debe demostrar | Estado actual |
| --- | --- | --- |
| M01 | Landing y mapa comprensibles; traza vial razonable. | Pendiente de sesión visual. |
| M02 | Admin completa aprobación, asignación y consulta de alertas sin ayuda. | Pendiente de sesión con usuario. |
| M03 | Peón/conductor entienden la ruta, paradas e incidencia. | Pendiente de sesión con usuario. |
| M04 | Operario distingue estados y comprende ingreso/resumen. | Pendiente de sesión con usuario. |
| M05 | Instalación en otra máquina, dos tamaños y dos navegadores. | Pendiente de ejecución en equipo destino. |

La respuesta JSON del mapa y los contadores que alimentan alertas sí fueron comprobados. La presentación del mapa y las tarjetas de alerta, la compatibilidad y la satisfacción de usuarios no se marcarán como aprobadas hasta observarlas.

### Acta breve para cada sesión de aceptación

1. Registrar fecha, persona (o perfil simulado), navegador/dispositivo y versión.
2. Entregar el objetivo de M01-M04 sin explicar dónde hacer clic.
3. Anotar pasos que pudo completar, tiempo aproximado, dudas, errores y frases textuales breves del usuario.
4. Guardar capturas sin contraseñas ni datos personales y relacionarlas con el ID de caso.
5. Preguntar: “¿Pudiste completar la tarea?” y “¿Qué cambiarías?”. Registrar respuesta y decisión del equipo.
6. Marcar aprobado/falló/bloqueado; si hubo falla, abrir defecto y repetir tras corregir.

## 5. Reproducción en PowerShell

Desde `C:\xampp\htdocs\Proyecto\proyecto`, con Docker Desktop abierto y `.env` configurado:

```powershell
$env:APP_PORT = '18080'
docker compose -p novacycle_qa_local up -d --build web db
docker compose -p novacycle_qa_local ps
$pruebas = @(
  'operaciones_unitarias.php', 'usuarios_unitarias.php', 'crud_unitarias.php',
  'sesiones.php',
  'sesiones_http.php', 'usuarios_http.php', 'autenticacion_http.php',
  'contenedores_ruta_http.php', 'contenedores_crud_http.php',
  'camiones_db_http.php', 'camion_asignado_http.php',
  'rutas_recoleccion_http.php', 'incidencias_http.php',
  'conductor_operario_http.php', 'dashboard_alertas_http.php'
)
foreach ($prueba in $pruebas) {
  docker compose -p novacycle_qa_local exec -T -u www-data `
    -e NOVACYCLE_TEST_ISOLATED=1 web php "tests/$prueba"
  if ($LASTEXITCODE -ne 0) { throw "Falló $prueba" }
}
```

El nombre `novacycle_qa_local` crea un volumen distinto del proyecto habitual. **No uses `-p proyecto` ni ejecutes estos tests HTTP sobre tu base de uso.** Si todo salió bien y no necesitás investigar datos de prueba, eliminá solo la copia QA:

```powershell
docker compose -p novacycle_qa_local down -v
Remove-Item Env:APP_PORT
```

`down -v` se reserva para esa copia QA: sobre el proyecto habitual borraría sus datos. Para la defensa, conservá este informe y un registro de salida/captura de la ejecución más las actas M01-M05 que realmente completes.

## 6. Brechas de requisitos detectadas

- El proyecto pide CRUD de centros de acopio y maquinaria básica en la segunda entrega. En este repositorio el centro solo se menciona en textos del frontend; no se encontró tabla, API ni formulario CRUD. Para maquinaria existe registro de estado, pero no un CRUD de inventario. No hay caso automatizado que pueda aprobar esas funciones ausentes. Confirmar con el docente si siguen siendo obligatorias o implementarlas antes de afirmar cumplimiento total.
- La consigna especifica MySQL 8, mientras `compose.yaml` usa MariaDB 11.4. Las pruebas demuestran funcionamiento en MariaDB; **no** demuestran compatibilidad con el servidor MySQL 8 de la institución. M05 debe ejecutarse en ese ambiente o acordar la sustitución con el docente.

Estos puntos son brechas de alcance/ambiente, no fallos de los 15 scripts ni defectos runtime inventados.

## 7. Decisión de cierre

**Verificación automatizada:** aprobada en el entorno descrito.

**Validación por usuarios:** pendiente, sin firma ni evidencia observacional todavía.
**Aprobación final del producto:** no declarada; requiere completar M01-M05, resolver/aclarar las brechas de requisitos y repetir la regresión si se modifica código.
