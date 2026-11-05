# NovaCycle

Proyecto PHP, JavaScript y MariaDB para gestionar recolección, rutas, camiones, contenedores e incidencias. No usa React ni dependencias JavaScript instaladas.

## Ejecutar con Docker

1. Instalá Docker Desktop y abrilo.
2. En esta carpeta, copiá `.env.example` a `.env` y cambiá las dos contraseñas de ejemplo. En PowerShell: `Copy-Item .env.example .env`.
3. Ejecutá `docker compose up --build -d`.
4. Abrí `http://localhost:8080/Proyecto/proyecto/.github/workflows/Frontend/index.html` (o cambiá `8080` por `APP_PORT`).

En una instalación nueva, Docker carga automáticamente `database/datos_demo.sql`, con los usuarios, camiones, contenedores, rutas e incidencias de prueba. Podés iniciar sesión con las credenciales que usabas en XAMPP. El archivo `.env` sigue siendo local y contiene las contraseñas de la base de datos, no las de los usuarios.

La carga automática ocurre **solo la primera vez**, cuando el volumen de MariaDB está vacío. Actualizar el archivo SQL no reemplaza una base de Docker que ya tiene datos. Docker conserva los datos al detener los contenedores: usá `docker compose down` y no `docker compose down -v` si querés conservarlos. Las asignaciones de ruta mantienen las fechas del respaldo; para mostrar una ruta en el mapa durante la defensa, asignala para ese día.

### Administrador

La base de prueba ya incluye un administrador. Iniciá sesión con el correo y la contraseña de esa cuenta. Si creás otra base sin administrador, registrá una cuenta y ejecutá `docker compose exec web php scripts/crear_admin.php TU_CI` con su CI. Ese comando sólo funciona cuando aún no existe un administrador.

No hay una contraseña de administrador en texto claro en el repositorio. Guardá `.env` fuera de Git; está ignorado automáticamente.

### Probar dentro de Docker

Con los contenedores en marcha, ejecutá:

```text
docker compose exec -u www-data web php tests/sesiones_http.php
docker compose exec -u www-data web php tests/usuarios_http.php
docker compose exec -u www-data web php tests/contenedores_ruta_http.php
docker compose exec -u www-data web php tests/camiones_db_http.php
docker compose exec -u www-data web php tests/rutas_recoleccion_http.php
docker compose exec -u www-data web php tests/incidencias_http.php
docker compose exec web php tests/operaciones_unitarias.php
```

La colección `postman/NovaCycle_API.postman_collection.json` sirve para probar las API manualmente. Para Docker, usá el puerto indicado en `APP_PORT`.

## Seguir usando XAMPP

XAMPP continúa funcionando como antes: sin variables de entorno, la aplicación conecta a MySQL en `localhost`, base `novacycle`, usuario `root` y contraseña vacía. La configuración Docker no modifica la base existente.

Para una instalación nueva en XAMPP, importá **sólo** `database/esquema_inicial.sql` en phpMyAdmin. Ese archivo ya incluye las actualizaciones anteriores; no vuelvas a ejecutar los archivos `actualizacion_*.sql` sobre una base nueva. Después registrá tu cuenta y ejecutá `C:\xampp\php\php.exe scripts\crear_admin.php TU_CI` desde esta carpeta.

El mapa utiliza Leaflet y teselas externas, por lo que necesita conexión a internet para mostrar el fondo cartográfico.
