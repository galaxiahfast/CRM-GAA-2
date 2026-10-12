# Módulo de Gestión de Respaldos

El módulo está disponible en `/actividades/respaldos` para administradores y usuarios con rol de contador.

## Preparación del servidor

```bash
php artisan migrate --force
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Los assets de producción se generan con nombres versionados por Vite, por lo que cada compilación invalida automáticamente la versión anterior en el navegador.

## Procesamiento en segundo plano

Debe existir un worker permanente y exclusivo para la cola de respaldos:

```bash
php artisan queue:work backup_database --queue=backup-uploads --tries=3 --timeout=7200 --sleep=1
```

En producción debe administrarse como servicio con Supervisor, systemd, NSSM o el administrador de procesos disponible, para que se inicie con el servidor y se reinicie después de un corte. `schedule:work` ejecuta tareas programadas, pero no sustituye este worker.

Si otras funciones de la aplicación usan la cola normal, conserva además su worker independiente:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=90 --sleep=1
```

Para desarrollo local basta un solo comando; inicia Laravel, Vite, el worker, el programador y los logs:

```bash
composer run dev
```

## Datos visuales de prueba

El siguiente comando permite crear datos visuales de prueba de forma idempotente directamente en la sede Mérida:

```bash
php artisan db:seed --class=BackupDemoSeeder --force
```

No se incluye en `DatabaseSeeder` para evitar insertar registros ficticios accidentalmente durante una carga normal de producción.

## Carga y administración

- La zona admite selección múltiple, carpetas y arrastrar y soltar paquetes ZIP.
- La vista inicia en la zona de carga; cada archivo muestra progreso, velocidad, tiempo restante y una cancelación que purga sus fragmentos del servidor.
- Las cargas se fragmentan, conservan su avance local y se reanudan después de recuperar la conexión.
- El historial permite descargar el paquete o sus archivos internos, editar metadatos, eliminar archivos individuales y reconstruir el ZIP restante.
- **Re-subir** solicita un nuevo paquete y conserva la versión actual hasta que el reemplazo se procese correctamente.
- El árbol inicia plegado, mantiene abiertas las carpetas sin cierres temporizados y limita la consulta simultánea a tres clientes; al abrir un cuarto se pliega el más antiguo.

## Variables opcionales

```dotenv
BACKUP_STORAGE_DISK=local
BACKUP_CHUNK_SIZE=16777216
BACKUP_MAX_FILE_SIZE=536870912000
BACKUP_MAX_EXTRACTED_SIZE=536870912000
BACKUP_MAX_ACTIVE_UPLOADS=2
BACKUP_UPLOAD_STALE_MINUTES=15
BACKUP_UPLOAD_QUEUE=backup-uploads
BACKUP_QUEUE_CONNECTION=backup_database
BACKUP_QUEUE_RETRY_AFTER=7500
BACKUP_WORKER_TIMEOUT=7200
```

`BACKUP_QUEUE_RETRY_AFTER` debe ser mayor que `BACKUP_WORKER_TIMEOUT` para impedir que dos workers procesen el mismo ZIP grande. El límite `post_max_size` de PHP debe ser mayor que `BACKUP_CHUNK_SIZE`; con fragmentos de 16 MB se recomienda al menos 40 MB.

Los ZIP deben conservar la estructura `Cliente/Index/*.index` y `Cliente/Bak/*.bak`.
