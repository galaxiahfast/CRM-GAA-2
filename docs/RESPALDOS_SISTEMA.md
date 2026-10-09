# Gestión de Respaldos

El módulo guarda archivos privados en `storage/app/private/backups`, separados por sede, cliente y tipo (`index` o `bak`). Las cargas parciales permanecen en `storage/app/private/backup-chunks` hasta que el worker termina de ensamblarlas.

## Puesta en marcha

```bash
php artisan migrate --force
php artisan queue:work --queue=backup-uploads,default --tries=3 --timeout=7200
```

El worker debe ejecutarse como servicio permanente (Supervisor, systemd o el administrador de procesos usado en el servidor). Reinícialo después de cada despliegue:

```bash
php artisan queue:restart
```

## Variables opcionales

```dotenv
BACKUP_STORAGE_DISK=local
BACKUP_CHUNK_SIZE=5242880
BACKUP_MAX_FILE_SIZE=536870912000
BACKUP_MAX_ACTIVE_UPLOADS=2
BACKUP_UPLOAD_STALE_MINUTES=15
BACKUP_UPLOAD_QUEUE=backup-uploads
```

`BACKUP_MAX_ACTIVE_UPLOADS` limita las transferencias simultáneas. Las solicitudes adicionales quedan en estado `waiting` y el navegador las inicia automáticamente cuando se libera un espacio.
