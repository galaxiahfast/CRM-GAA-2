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

Las sedes no tienen pantallas ni lógica duplicada. Para agregar otra, basta con incorporarla al arreglo `sites` de `config/backup-storage.php`; aparecerá automáticamente en el selector y utilizará el mismo flujo de carga, historial y descarga.

## Puesta en marcha

```bash
php artisan migrate --force
php artisan queue:work backup_database --queue=backup-uploads --tries=3 --timeout=7200 --sleep=1
```

Este worker es adicional al de la cola `default` de la aplicación. `php artisan schedule:work` no procesa cargas ni sustituye ninguno de los workers.

El worker debe ejecutarse como servicio permanente (Supervisor, systemd o el administrador de procesos usado en el servidor). Reinícialo después de cada despliegue:

```bash
php artisan queue:restart
```

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

El `retry_after` de esta conexión está separado del de las colas normales y debe ser mayor que el timeout del worker. Así un ZIP grande no se procesa dos veces mientras el primer worker sigue ocupado.

`BACKUP_MAX_ACTIVE_UPLOADS` limita las transferencias simultáneas. Las solicitudes adicionales quedan en estado `waiting` y el navegador las inicia automáticamente cuando se libera un espacio.

La extensión PHP `zip` es la opción recomendada. Si no está habilitada, el servidor utiliza `tar`/bsdtar como alternativa para leer el ZIP.
