# NovaCycle

Proyecto PHP, JavaScript y MariaDB para gestionar recolección, rutas, camiones, contenedores e incidencias. No usa React ni dependencias JavaScript instaladas.

## Ejecutar con Docker

1. Instalá Docker Desktop y abrilo.
2. En esta carpeta, copiá `.env.example` a `.env` y cambiá las dos contraseñas de ejemplo. En PowerShell: `Copy-Item .env.example .env`.
3. Ejecutá `docker compose up --build -d`.
4. Abrí `http://localhost:8080/Proyecto/proyecto/.github/workflows/Frontend/index.html` (o cambiá `8080` por `APP_PORT`).

La base se crea vacía a partir de `database/esquema_inicial.sql`. No se copian datos de XAMPP ni usuarios reales. Docker guarda los datos en un volumen para conservarlos al detener los contenedores.

### Crear el primer administrador

1. Abrí la página de registro y creá una cuenta con tus propios datos y contraseña. El puesto solicitado puede ser cualquiera; todavía no tendrá permisos.
2. Ejecutá `docker compose exec web php scripts/crear_admin.php TU_CI` con la CI que registraste. Este comando sólo funciona si aún no existe un administrador.
3. Iniciá sesión con el correo y la contraseña que elegiste. Desde el panel podrás aprobar las demás cuentas.

No hay una contraseña de administrador predefinida en el repositorio. Guardá `.env` fuera de Git; está ignorado automáticamente.

### Probar dentro de Docker

Con los contenedores en marcha, ejecutá:

```text
docker compose exec -u www-data web php tests/sesiones_http.php
docker compose exec -u www-data web php tests/usuarios_http.php
docker compose exec -u www-data web php tests/contenedores_ruta_http.php
docker compose exec -u www-data web php tests/camiones_db_http.php
```

La colección `postman/NovaCycle_API.postman_collection.json` sirve para probar las API manualmente. Para Docker, usá el puerto indicado en `APP_PORT`.

## Seguir usando XAMPP

XAMPP continúa funcionando como antes: sin variables de entorno, la aplicación conecta a MySQL en `localhost`, base `novacycle`, usuario `root` y contraseña vacía. La configuración Docker no modifica la base existente.

Para una instalación nueva en XAMPP, importá **sólo** `database/esquema_inicial.sql` en phpMyAdmin. Ese archivo ya incluye las actualizaciones anteriores; no vuelvas a ejecutar los archivos `actualizacion_*.sql` sobre una base nueva. Después registrá tu cuenta y ejecutá `C:\xampp\php\php.exe scripts\crear_admin.php TU_CI` desde esta carpeta.

El mapa utiliza Leaflet y teselas externas, por lo que necesita conexión a internet para mostrar el fondo cartográfico.
