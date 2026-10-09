# Gestión de Respaldos

El usuario solamente elige la pestaña de la sede (Mérida o Tulum) y carga un archivo `.zip`. No tiene que elegir cliente ni tipo de respaldo. El ZIP debe contener una o más carpetas con esta estructura:

```text
Cliente 1/
├── Index/
│   └── respaldo.index
└── Bak/
    └── respaldo.bak
```

También se admite una carpeta contenedora adicional antes de los clientes. El worker detecta automáticamente cada cliente, guarda los archivos privados en `storage/app/private/backups` y genera el árbol que aparece en pantalla. El ZIP original queda en `storage/app/private/backup-archives` para auditoría. Las cargas parciales permanecen en `storage/app/private/backup-chunks` hasta que el worker termina de ensamblarlas.

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
BACKUP_MAX_EXTRACTED_SIZE=536870912000
BACKUP_MAX_ACTIVE_UPLOADS=2
BACKUP_UPLOAD_STALE_MINUTES=15
BACKUP_UPLOAD_QUEUE=backup-uploads
```

`BACKUP_MAX_ACTIVE_UPLOADS` limita las transferencias simultáneas. Las solicitudes adicionales quedan en estado `waiting` y el navegador las inicia automáticamente cuando se libera un espacio.

La extensión PHP `zip` es la opción recomendada. Si no está habilitada, el servidor utiliza `tar`/bsdtar como alternativa para leer el ZIP.
