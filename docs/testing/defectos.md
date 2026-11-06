# Registro de defectos - NovaCycle

**Versión de revisión:** 06/11/2026. Se registran solo problemas observados en código y comprobados con pruebas; no se agregan defectos hipotéticos como si hubieran ocurrido.

## DEF-001 - Una operación rechazada respondía como exitosa

- **Severidad:** media (contrato HTTP incorrecto para el frontend y clientes de la API).
- **Área:** `Api_Operaciones/api_operaciones.php`.
- **Precondición:** usuario con rol permitido.
- **Reproducción:** enviar `POST ingreso` con `peso_kg=0`, o `POST crear_ruta` sin zona, `crear_parada` sin IDs, `asignar_ruta` sin fecha.
- **Esperado:** JSON `ok:false`, HTTP `400`, ningún registro nuevo.
- **Observado antes de corregir:** por revisión del código, esas ramas pasaban `201` fijo a `responder(...)` aunque el controlador devolvía `ok:false`. No se conserva una captura de ejecución anterior al cambio; esta observación se basa en el código previo.
- **Causa:** código de éxito fijado en la capa HTTP, sin consultar el resultado del controlador. Otras acciones fallidas también respondían `200`.
- **Corrección:** todas las acciones mutantes de operaciones usan `400` cuando `ok` es falso; las creaciones válidas conservan `201` y las modificaciones válidas `200`.
- **Reprueba:** `R02` en `tests/conductor_operario_http.php` comprueba los cuatro casos y ausencia de inserciones; `R01` comprueba errores de recolección. Resultado: aprobado en Docker QA aislado.
- **Estado:** corregido y repruebado.

## DEF-002 - Conductor podía cambiar un camión ajeno

- **Severidad:** alta (permiso por rol insuficiente para proteger recursos de otra cuadrilla).
- **Área:** `estado_camion` y `ModeloOperaciones::actualizarCamion`.
- **Precondición:** sesión de conductor y un camión activo que no esté asignado a ese conductor hoy.
- **Reproducción:** enviar `POST estado_camion` con el ID del camión ajeno y un estado válido.
- **Esperado:** `ok:false`, HTTP `400`; el estado del camión ajeno permanece intacto.
- **Observado antes de corregir:** la API comprobaba el rol `conductor`, pero el `UPDATE` filtraba solamente por ID y `activo=1`; cualquier conductor podía afectar otro camión. Observación por revisión del código, sin afirmar una ejecución previa del ataque.
- **Causa:** faltaba asociar la actualización a la asignación del día y a la CI autenticada.
- **Corrección:** el `UPDATE` exige una asignación de hoy cuyo `ci_conductor` coincide con la sesión; el controlador pasa la CI y la API devuelve `400` si se rechaza.
- **Reprueba y estado:** `O01` en `tests/conductor_operario_http.php` usa dos camiones: el asignado cambia, el ajeno no; comprueba respuesta y SQL. Aprobado en Docker QA aislado; corregido y repruebado.

## Pendientes de observación

No se detectaron fallas en los 15 scripts ejecutados después de las correcciones. Esto **no prueba ausencia de defectos**. Todavía falta ejecutar los casos de interfaz y aceptación M01-M05, especialmente trazado vial real, tarjetas visuales de alerta, legibilidad móvil y experiencia de cada perfil.

## Plantilla para nuevos defectos

- **ID y título:**
- **Fecha, responsable y versión/commit:**
- **Severidad:** crítica / alta / media / baja.
- **Módulo y ambiente:**
- **Precondiciones y datos de prueba:**
- **Pasos exactos para reproducir:**
- **Resultado esperado:**
- **Resultado observado:**
- **Evidencia:** captura, respuesta HTTP o registro de consola sin secretos.
- **Estado:** abierto / en corrección / corregido / repruebado / cerrado.
- **Caso de regresión asociado:**
