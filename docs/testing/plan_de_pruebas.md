# Plan de pruebas y validación - NovaCycle

**ID:** PP-NC-2026-01

**Versión:** 1.0 - 06/11/2026

**Estado:** en ejecución; la aceptación de usuarios queda pendiente de realizar y firmar.

**Responsables:** equipo de desarrollo NovaCycle. El docente o un compañero que represente cada perfil participa en la validación.

## 1. Propósito

Verificar que el sistema cumple los requisitos funcionales de gestión de residuos y comprobar, con usuarios, que los flujos son comprensibles y útiles. La verificación compara resultados observados con resultados esperados; la validación requiere que alguien use el producto y confirme que resuelve su necesidad. Una prueba automática que pasa no reemplaza esa aceptación.

Este plan toma como alcance la tercera entrega de Programación Full Stack y el apartado de testing de Ingeniería de Software del proyecto. Las diapositivas y el video proporcionados por el curso se usan como guía metodológica, no como requisitos funcionales de NovaCycle. El video se pudo identificar y consultar por su descripción pública, pero no se obtuvo una transcripción para atribuirle pasos específicos.

## 2. Alcance

### Se probará

- Registro, aprobación, inicio/cierre de sesión y separación de permisos entre administrador, peón, conductor y operario.
- Altas, consultas, modificaciones y bajas de usuarios, camiones y contenedores, incluidos duplicados y recursos en uso.
- Creación, modificación, baja y asignación de rutas; vinculación de contenedores como paradas y registro de recolección.
- Registro, revisión y cierre de incidencias; listados, alertas y marcadores del mapa.
- Ingresos de residuos, estado de camiones, estado de maquinaria y resúmenes operativos.
- Estadísticas del administrador, acceso público al mapa e instalación básica con Docker.
- Mensajes de error, estado HTTP, persistencia y navegación por rol.

### No se afirmará como probado en esta entrega

- Rendimiento bajo carga, estrés, recuperación ante caída del servidor o auditoría de seguridad completa: requieren ambientes, objetivos medibles y herramientas adicionales.
- Exactitud de rutas calculadas por servicios cartográficos externos ni disponibilidad de las teselas de terceros.
- Funcionamiento en todos los navegadores/dispositivos y despliegue en la infraestructura de la institución, hasta ejecutar esos casos allí.
- Satisfacción de usuarios reales, hasta completar las sesiones de aceptación documentadas.

Estos puntos no se consideran aprobados por omisión; quedan como riesgos o pruebas pendientes.

**Brechas frente a la consigna detectadas durante el análisis:** no se halló CRUD de centros de acopio ni inventario CRUD de maquinaria (solo registro de estados), y el Docker usa MariaDB 11.4 aunque la consigna menciona MySQL 8. Se registran en el informe; no se convierten en casos "aprobados" por falta de implementación o ambiente.

## 3. Estrategia y justificación

| Nivel/técnica | Qué comprueba | Evidencia |
| --- | --- | --- |
| Caja blanca, unitaria | Ramas de validación, normalización, permisos de negocio y respuesta del controlador con modelo falso, sin abrir la base. | `tests/*_unitarias.php` y salida CLI. |
| Caja negra, integración/API | Entradas y salidas HTTP (JSON y códigos 2xx/4xx), acciones de los cuatro roles y efectos en MariaDB. | `tests/*_http.php`, consultas de comprobación y salida CLI. |
| Prueba de interfaz | Formularios, mensajes, redirección, visualización de mapa/alertas y diseño en navegador. | Guion manual y capturas con fecha. |
| Validación/aceptación | Un representante de cada perfil completa tareas reales sin ayuda del desarrollador y opina si el flujo satisface su necesidad. | Acta de sesión, observaciones y conformidad. |
| Instalación | La aplicación arranca en una instalación nueva con Docker y se puede acceder a web/base. | Guion manual, comandos y capturas. |

La caja blanca se justifica porque las reglas internas (campos obligatorios, rol, normalización y vínculo de contenedor con ruta) pueden probarse con entradas límite y modelos falsos. La caja negra se justifica porque el usuario y el frontend dependen de las respuestas reales de las API, no de cómo estén programadas. Las pruebas de interfaz y aceptación detectan problemas que un script HTTP no ve.

**Diseño de casos:** clases válidas/inválidas, valores límite (vacío, cero, duplicado), transiciones de estado (pendiente/revisada/cerrada; activo/baja) y matriz de roles. La suite se repite tras cambios como prueba de regresión. No se informa porcentaje de cobertura de código porque no hay medición instrumentada.

## 4. Ambiente, datos y seguridad de la prueba

- Aplicación: PHP 8.2, JavaScript y CSS sin framework; base MariaDB 11.4; servicios definidos en `compose.yaml`.
- Las pruebas HTTP usan la URL interna fija `http://localhost/Proyecto/proyecto/...`; se ejecutan **dentro del contenedor web**.
- Se levanta un proyecto Compose separado (`novacycle_qa`) con puerto distinto y volumen propio. No se ejecutan pruebas que escriben en la base habitual del equipo.
- Los scripts crean cuentas y recursos temporales con identificadores aleatorios, comprueban datos en SQL y los eliminan al finalizar. Si un script falla antes de limpiar, se inspecciona y descarta **solo** el volumen de QA.
- No se publican contraseñas, cookies, dumps con datos de usuarios ni capturas de sesiones activas como evidencia.

## 5. Criterios de entrada, salida y suspensión

**Entrada:** versión identificada en Git; PHP sin errores de sintaxis; servicios web y DB saludables; base QA con esquema/datos de prueba; cuentas de los roles disponibles o creadas por el test.

**Aprobación técnica:** todos los casos automáticos planificados ejecutados y aprobados; ninguna falla crítica/alta abierta; defectos corregidos repetidos y suite de regresión aprobada. Un caso pasa solo si coinciden código HTTP, `ok`, datos retornados y cambios persistidos cuando corresponda.

**Aprobación funcional:** al menos un usuario o representante de cada perfil completa el guion de aceptación, registra observaciones y confirma si el flujo le sirve. Sin esa sesión, el resultado es **pendiente**, no aprobado.

**Suspender** si no arranca el entorno, si hay riesgo de escribir en la base habitual, si falla la limpieza de datos o si un defecto bloqueante impide los demás casos. **Reanudar** al aislar/restaurar el entorno, registrar el defecto y volver a correr el caso más la regresión afectada.

## 6. Secuencia y responsabilidades

1. Equipo de desarrollo: revisar requisitos y preparar entorno QA.
2. Equipo de desarrollo: ejecutar unitarias; corregir fallas.
3. Equipo de desarrollo: ejecutar integración/API; registrar resultados y defectos.
4. Equipo de desarrollo y representantes de perfiles: probar interfaz y aceptación con los casos manuales.
5. Coordinador del equipo: revisar matriz de trazabilidad, pendientes y evidencia; incorporar esta sección al PDF integrador.

La planificación es por etapas y dependencias, sin inventar fechas o firmas de terceros. Si se modifica el sistema después del informe, se actualiza versión, fecha y resultado de las pruebas repetidas.

## 7. Riesgos y mitigación

| Riesgo | Impacto | Mitigación |
| --- | --- | --- |
| Pruebas HTTP sobre datos de uso | Alto | Compose y volumen QA independientes; limpieza `finally`. |
| Servicios externos del mapa sin internet | Medio | Separar validación de datos/markers de comprobación visual del fondo y trazado. |
| Tests unitarios confundidos con pruebas de API | Alto | Distinguir explícitamente nivel, entradas y evidencia de cada archivo. |
| URL HTTP fija en los tests | Medio | Ejecutarlos desde `web`, no desde PowerShell/XAMPP. |
| Validación declarada sin usuarios | Alto | Mantener UAT como pendiente hasta obtener acta y observaciones reales. |
| Cambios posteriores al informe | Medio | Registrar commit y repetir suite y casos manuales afectados. |

## 8. Entregables y fuentes

- `casos_y_trazabilidad.md`: casos, datos, resultados esperados y matriz requisito-prueba.
- `informe_ejecucion.md`: ambiente, comandos, resultados obtenidos, defectos y pendientes.
- `defectos.md`: incidencias reales y plantilla para registrar nuevas.
- `output/pdf/Informe_testing_NovaCycle.pdf`: copia para anexar al documento integrador de la entrega.

Fuentes consultadas: `Proyecto_ESI_2026.pdf` (tercera entrega y rúbrica); `Pruebas_y_su_documentacion.pdf`; `Testing_-_VV_del_Software_diap.pdf`; `Tipos_de_pruebas._niveles_de_prueba_y_plan_de_pruebas.pdf`; `Verificacion_y_validacion_Testing.pdf`; video “¿Cómo hacer un plan de Pruebas? | Tutorial paso a paso” de Full Advanced, https://www.youtube.com/watch?v=apbxGwdXORA (descripción pública, sin transcripción verificada). El plan se adapta a este proyecto estudiantil; cifras y herramientas de los ejemplos didácticos no se trasladan como obligaciones.
