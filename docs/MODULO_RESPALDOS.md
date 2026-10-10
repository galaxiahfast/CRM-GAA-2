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

Debe existir un worker permanente para la cola de respaldos:

```bash
php artisan queue:work --queue=backup-uploads,default --tries=3 --timeout=7200
```

En producción conviene administrar este comando con Supervisor, systemd o el administrador de procesos disponible en el servidor para que se reinicie después de un corte.

## Datos visuales de prueba

Desde la zona de carga se puede descargar **ZIP de prueba**, un paquete listo para subir con 15 empresas y sus carpetas `Index` y `Bak`. El siguiente comando ofrece una alternativa para crear esos mismos datos de forma idempotente directamente en la sede Mérida:

```bash
php artisan db:seed --class=BackupDemoSeeder --force
```

No se incluye en `DatabaseSeeder` para evitar insertar registros ficticios accidentalmente durante una carga normal de producción.

## Carga y administración

- La zona admite selección múltiple, carpetas y arrastrar y soltar paquetes ZIP.
- Las cargas se fragmentan, conservan su avance local y se reanudan después de recuperar la conexión.
- El historial permite descargar el paquete o sus archivos internos, editar metadatos, eliminar archivos individuales y reconstruir el ZIP restante.
- **Re-subir** solicita un nuevo paquete y conserva la versión actual hasta que el reemplazo se procese correctamente.
- El árbol inicia plegado y muestra las carpetas `Index` y `Bak` únicamente al abrir un cliente.

## Variables opcionales

```dotenv
BACKUP_STORAGE_DISK=local
BACKUP_CHUNK_SIZE=5242880
BACKUP_MAX_FILE_SIZE=536870912000
BACKUP_MAX_EXTRACTED_SIZE=536870912000
BACKUP_MAX_ACTIVE_UPLOADS=2
BACKUP_UPLOAD_STALE_MINUTES=15
BACKUP_UPLOAD_QUEUE=backup-uploads
```

Los ZIP deben conservar la estructura `Cliente/Index/*.index` y `Cliente/Bak/*.bak`.
