# Casos de prueba y trazabilidad - NovaCycle

**Versión:** 1.0 - 06/11/2026. Los identificadores permiten relacionar requisito, script, resultado y defecto. Los datos `T...` y direcciones `example.test` son sintéticos.

## Casos automatizados

### U01 - Registro y login por rol (unitaria, caja blanca)

**Precondición:** PHP CLI; sin servidor ni base. **Datos:** formulario vacío, email inválido, clave corta, rol administrador no permitido en registro público, CI repetida, cuenta pendiente/rechazada/aprobada y cuatro roles. **Pasos:** ejecutar `tests/usuarios_unitarias.php`. **Esperado:** entradas inválidas no llegan al modelo; los datos válidos se normalizan y la clave se guarda como hash; pendiente/rechazada/sin rol no ingresan; admin va a `admin.html`, peón y conductor a `cuadrilla.html`, operario a `operario.html`. **Aserción:** el script termina con código 0; cada comprobación lanza error al fallar.

### U02 - CRUD y permisos de usuarios (integración, caja negra/gris)

**Precondición:** web y DB QA; cuentas temporales admin y peón. **Datos:** CI y correo sintéticos. **Pasos:** ejecutar `tests/usuarios_http.php`; listar con ambos roles; registrar/aprobar; modificar rol; intentar baja propia y baja con incidencia; eliminar cuenta temporal. **Esperado:** solo admin administra, el listado no revela contraseñas, cambios y baja se reflejan en SQL, una sesión con rol anterior pierde acceso, recursos relacionados impiden la baja. **Aserción:** respuesta `ok`, lectura SQL y salida 0.

### U03 - Registro, aprobación y login HTTP real (integración)

**Precondición:** web y DB QA; admin temporal. **Datos:** cuentas sintéticas de peón, conductor y operario, contraseña correcta e incorrecta. **Pasos:** ejecutar `tests/autenticacion_http.php`; registrar cada rol por API, intentar login pendiente, aprobar con admin, probar clave incorrecta y luego iniciar sesión con clave válida; consultar una API permitida con la cookie real. **Esperado:** pendiente sin fila en tablas de roles y sin acceso; aprobada con un único rol correcto; contraseña errónea `400`; login válido devuelve cookie, rol y destino correctos; la sesión puede consultar su API. **Aserción:** HTTP, JSON, cookie y SQL.

### C01 - Camiones: alta, modificación, baja y reactivación (integración)

**Precondición:** web y DB QA; admin temporal. **Datos:** matrícula aleatoria, capacidad 1000/1500/2000 kg. **Pasos:** ejecutar `tests/camiones_db_http.php`; crear, modificar, dar de baja y volver a registrar la misma matrícula. **Esperado:** alta `201`, cambio y baja `200`; la baja no aparece en listado; la matrícula dada de baja reactiva el mismo ID sin duplicar registro; una matrícula activa duplicada se rechaza con `400`. **Aserción:** estado HTTP, JSON y SQL.

### C02 - Camión asignado: bloqueo y liberación (integración)

**Precondición:** web y DB QA; admin, conductor y peón temporales. **Datos:** dos camiones, uno asignado a ruta de hoy y otro libre. **Pasos:** ejecutar `tests/camion_asignado_http.php`; consultar `en_uso`; intentar editar/dar de baja el asignado, editar/dar de baja el libre, completar asignación y volver a editar el antes asignado. **Esperado:** en uso `409` sin cambios SQL; peón `403`; libre editable y dado de baja; ruta completada libera el camión. **Aserción:** HTTP, JSON y SQL.

### CT01 - Contenedor vinculado a ruta (integración)

**Precondición:** web y DB QA; admin temporal. **Datos:** contenedor temporal con coordenadas y ruta temporal. **Pasos:** ejecutar `tests/contenedores_ruta_http.php`; consultar contenedor libre, crear vínculo de ruta, intentar modificar/dar de baja, quitar vínculo y repetir. **Esperado:** `en_uso` cambia de 0 a 1; edición/baja mientras está en uso se rechazan con `409`; fuera de ruta vuelve a ser editable. **Aserción:** HTTP, JSON y SQL.

### CT02 - CRUD real de contenedores por API (integración)

**Precondición:** web y DB QA; admin y peón temporales. **Datos:** dirección aleatoria, capacidad 800 L, coordenadas válidas/inválidas. **Pasos:** ejecutar `tests/contenedores_crud_http.php`; intentar altas inválidas, crear, obtener/listar, editar válido/inválido y dar de baja dos veces. **Esperado:** alta `201`, consultas/edición/baja válidas; inválidos `400`, rol peón `403`, recurso dado de baja `404`; altas/ediciones/baja comprobadas en SQL. **Aserción:** HTTP, JSON y SQL. No se afirma reactivación por dirección: esa política no existe para contenedores.

### R01 - Ruta, asignación y recolección (integración)

**Precondición:** web y DB QA; admin, peón y conductor temporales. **Datos:** ruta, contenedor, camión y fecha actual generados por el test. **Pasos:** ejecutar `tests/rutas_recoleccion_http.php`; crear ruta y parada, asignarla, consultar `mi_ruta`, completar parada, consultar mapa y dar de baja ruta. **Esperado:** la cuadrilla correcta ve su asignación, la parada se completa una vez, la recolección queda en SQL, una parada ajena no se completa y la ruta dada de baja desaparece de listados/mapa. **Aserción:** HTTP, JSON y SQL.

### I01 - Incidencias y seguimiento (integración)

**Precondición:** web y DB QA; ruta asignada y perfiles temporales. **Datos:** desborde en contenedor de ruta, contenedor ajeno, detalle vacío y aviso en otro lugar. **Pasos:** ejecutar `tests/incidencias_http.php`; registrar, consultar listados/mapa, revisar y cerrar con operario. **Esperado:** dato incompleto/ajeno `400`, rol incorrecto `403`, registro válido `201`; estado `pendiente` -> `revisada` -> `cerrada` en SQL; cerrada sale del mapa y entra al listado de cerradas. **Aserción:** HTTP, JSON y SQL.

### S01 - Sesiones simultáneas (integración)

**Precondición:** web y DB QA; admin y peón temporales. **Pasos:** ejecutar `tests/sesiones.php` para alternar directamente las sesiones y `tests/sesiones_http.php` para repetir por API, probar cookies antiguas y cerrar solo una. **Esperado:** cada pestaña mantiene sus permisos; cerrar peón no cierra admin. **Aserción:** rol de sesión, permisos vía API y salida 0.

### O01 - Operario y conductor (integración)

**Precondición:** web y DB QA; perfiles y dos camiones temporales. **Datos:** peso 0 y 175 kg, tipo de residuo, equipo/estado, cambio de estado de camión asignado y ajeno. **Pasos:** ejecutar `tests/conductor_operario_http.php`. **Esperado:** solo conductor cambia el camión asignado hoy; el camión ajeno queda intacto; solo operario registra ingreso/maquinaria y consulta resumen; ingreso inválido `400` sin guardar; ingreso válido `201` y aumenta `ingresos_hoy` en 175 kg; los registros quedan vinculados a las cuentas de prueba. **Aserción:** HTTP, JSON y SQL.

### R02 - Contrato HTTP de errores de creación (integración)

**Precondición:** web y DB QA; admin/operario temporales. **Datos:** ruta sin zona, parada sin ruta, asignación sin fecha, ingreso con peso 0. **Pasos:** ejecutar `tests/conductor_operario_http.php`. **Esperado:** las cuatro acciones devuelven `400` y `ok:false`, nunca `201`, y no insertan registros. **Aserción:** estado HTTP, JSON y conteos SQL.

### D01 - Dashboard y alertas del administrador (integración)

**Precondición:** web y DB QA; admin temporal. **Datos:** recursos e incidencia temporales. **Pasos:** ejecutar `tests/dashboard_alertas_http.php`; consultar dashboard antes/después de crear datos, comprobar incrementos y acceso al mapa público. **Esperado:** rutas y recolecciones del día +1, ingresos +500 kg, contenedores por estado +1, camión disponible +1, incidencias pendientes +1 y luego -1 al cerrar; la incidencia abierta aparece en mapa y la cerrada no; rol no admin recibe `403`. **Aserción:** HTTP y JSON comparados con datos insertados en SQL. La creación visual de tarjetas de alerta en el DOM requiere M02.

### B01 - Reglas de operaciones y CRUD (unitarias, caja blanca)

**Precondición:** PHP CLI; sin servidor ni base. **Datos:** nombres vacíos/con espacios, IDs cero, contenedor ajeno, incidencia en ruta y en otro lugar. **Pasos:** ejecutar `tests/operaciones_unitarias.php`, `tests/usuarios_unitarias.php` y `tests/crud_unitarias.php`. **Esperado:** las entradas inválidas no pasan al modelo; las válidas se normalizan; altas, modificaciones y bajas de camiones/contenedores respetan el bloqueo de recursos en uso. **Aserción:** modelos falsos y salida 0.

## Casos manuales de interfaz y validación

Cada caso manual debe registrar **fecha, persona/perfil, navegador, versión/commit, resultado real, captura y estado** (`aprobado`, `falló`, `bloqueado` o `pendiente`). No se considera aprobado solo por estar escrito.

**Orden sugerido:** M02 crea y asigna una ruta con dos contenedores para hoy; M03 reporta la incidencia; volver a M02 para observar la alerta; M01 comprueba el mapa; M04 cierra la incidencia; volver a M01/M02 para comprobar que desapareció de abiertos y bajó el contador. M05 se hace en la máquina destino.

### M01 - Landing y mapa público

**Precondición:** M02 dejó asignada para hoy una ruta con dos contenedores geolocalizados; M03 creó una incidencia abierta para uno de ellos. **Pasos:** abrir la landing sin sesión; identificar ruta, contenedores e incidencia; acercar el mapa y revisar el trayecto entre ambas paradas. Repetir tras M04. **Esperado:** no exige login; marcadores corresponden a registros activos; la incidencia cerrada deja de verse; la traza sigue calles transitables y no cruza agua; si falla el servicio externo, se informa sin romper la página. **Validación:** preguntar al usuario si entiende qué muestran los símbolos y si puede ubicar su zona.

### M02 - Flujo del administrador

**Pasos:** entrar como admin; aprobar usuario; crear camión, dos contenedores y ruta; vincular ambos contenedores como paradas y asignar cuadrilla para hoy; intentar editar/borrar camión y contenedor en uso; consultar dashboard. Tras M03, comprobar alerta activa; tras M04, comprobar el cambio de contador. **Esperado:** acciones y mensajes coherentes, bloqueo visible para recursos en uso, datos actualizados al volver a abrir cada sección. **Validación:** el usuario puede encontrar las secciones y completar una asignación sin ayuda.

### M03 - Flujo de cuadrilla

**Pasos:** entrar como peón/conductor; ver ruta de hoy; completar una parada; reportar incidencia en su contenedor y otra ubicación; intentar acción exclusiva de admin/operario. **Esperado:** tareas y contador coinciden con la ruta asignada; mensaje de incidencia enviada; permisos negados donde corresponde. **Validación:** la cuadrilla sabe qué parada le toca y cómo reportar un problema.

### M04 - Flujo del operario

**Pasos:** entrar como operario; consultar incidencias; revisar/cerrar una; registrar peso de residuos y estado de maquinaria; comprobar resumen. **Esperado:** datos actualizados, estados claros, entradas inválidas con mensaje útil. **Validación:** el operario puede distinguir pendiente de revisada/cerrada y entender el resumen.

### M05 - Instalación y compatibilidad mínima

**Pasos:** en equipo distinto o entorno nuevo, configurar `.env`, iniciar `docker compose up --build -d`, abrir landing, login y phpMyAdmin; repetir M01-M04 en al menos dos tamaños de pantalla y dos navegadores. **Esperado:** servicios saludables, sin datos/contraseñas expuestos, interfaz legible y acciones funcionales. **Estado:** pendiente hasta ejecutarse realmente en ese equipo.

## Matriz de trazabilidad

Los requisitos provienen de `Proyecto_ESI_2026.pdf`, secciones 9.1 y 9.4 de la tercera entrega y de las funciones implementadas. `Auto` no significa que la validación con usuarios esté hecha.

| ID requisito | Requisito | Casos relacionados |
| --- | --- | --- |
| RF01 | Registro, sesión, roles y CRUD de usuarios | U01, U02, U03, S01, M02, M03, M04 |
| RF02 | CRUD de camiones y protección de camión asignado | C01, C02, B01, M02 |
| RF03 | CRUD de contenedores y vínculo con ruta | CT01, CT02, B01, M02 |
| RF04 | API de recolección, rutas y paradas | R01, R02, B01, M02, M03 |
| RF05 | Incidencias: registro, seguimiento y listados | I01, D01, M03, M04 |
| RF06 | Mapa interactivo con contenedores e incidencias | I01, R01, D01, M01 |
| RF07 | Informes y panel de estadísticas | D01, O01, M02, M04 |
| RF08 | Sistema de alertas | D01, M02 |
| RF09 | Operario: ingresos y maquinaria; conductor: estado de camión | O01, M03, M04 |
| RF10 | CRUD de centros de acopio y maquinaria básica (segunda entrega) | Sin implementación completa; brecha registrada en el informe |
| RNF01 | Seguridad básica: permisos y sesiones | U01, U02, U03, C02, CT02, I01, S01, O01 |
| RNF02 | Instalación con Docker | M05 |
| RV01 | Utilidad/usabilidad para perfiles reales | M01, M02, M03, M04, M05 |

**Brechas a revisar:** la presentación del bloqueo de camión asignado y la visualización/claridad de alertas requieren observación manual además de API; el trazado vial externo no está cubierto por los tests HTTP; la conformidad de usuarios sigue pendiente hasta que se ejecute el guion.
