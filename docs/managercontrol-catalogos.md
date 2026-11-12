# Catálogos del CRM en ManagerControl

El CRM usa `customers`, `services` y `sub_services`. ManagerControl usa `Catalogos.Clientes`, `Catalogos.Secciones` y `Catalogos.Actividades`. Los IDs son distintos; nunca se copia un ID del CRM a `RegistroDiario`.

## Origen y coincidencias

- Los registros ya existentes se conservan. Se vinculan por RFC válido o por nombre normalizado cuando no hay conflicto.
- Los nuevos llevan `NombreUsuario = CRM-GAA`. El formulario muestra **CRM** o **ManagerControl** junto al nombre de cada opción.
- `managercontrol_catalog_links` guarda los pares de IDs por empresa y ejercicio. Una nueva ejecución reutiliza los vínculos y no vuelve a insertar los mismos registros.
- Coincidencias ambiguas, RFC distintos con el mismo nombre y datos inválidos quedan para revisión. El comando nunca modifica un registro original.
- Si la vista previa muestra clientes pendientes de revisión, `--apply` importará los demás y volverá a mostrar esos IDs para resolverlos después.
- Las cuentas se asocian al departamento aprobado en `config/managercontrol.php`. La cuenta `Contabilidad` existente en el departamento Jurídico se conserva y se creará otra en Contabilidad.

## Permisos mínimos

La cuenta `crm_captura` solo tiene permiso sobre `ControlTiempos.RegistroDiario` y debe permanecer así. Un administrador de SQL Server debe crear una cuenta distinta. Sustituya la contraseña de ejemplo antes de ejecutar este script; no la guarde en Git:

```sql
USE [master];
CREATE LOGIN [crm_catalogos] WITH PASSWORD = N'REEMPLAZAR_POR_UNA_CLAVE_SEGURA', CHECK_POLICY = ON;
USE [ControlTiempos_Contabilidad];
CREATE USER [crm_catalogos] FOR LOGIN [crm_catalogos];
GRANT SELECT, INSERT ON [Catalogos].[Clientes] TO [crm_catalogos];
GRANT SELECT, INSERT ON [Catalogos].[Secciones] TO [crm_catalogos];
GRANT SELECT, INSERT ON [Catalogos].[Actividades] TO [crm_catalogos];
```

No se necesitan permisos `UPDATE`, `DELETE`, `ALTER` ni `db_owner`. La cuenta de lectura `crm_lectura` continúa separada.

En el `.env` del CRM:

```dotenv
MANAGERCONTROL_CATALOG_WRITE_ENABLED=true
MANAGERCONTROL_CATALOG_WRITE_USERNAME=crm_catalogos
MANAGERCONTROL_CATALOG_WRITE_PASSWORD="la_clave_real"
```

## Ejecución

```powershell
php artisan migrate --force
php artisan config:clear
php artisan managercontrol:sync-catalogs
php artisan managercontrol:sync-catalogs --apply
```

El primer comando `sync-catalogs` solo muestra la vista previa. `--apply` comprueba los tres permisos antes de insertar. Si la conexión falla a mitad del proceso, se puede repetir: los registros insertados se reconocen por RFC/nombre y los vínculos ya guardados se reutilizan. Después de importar, el comando limpia la caché de los tres catálogos.

Los clientes sin país, estado, ciudad o domicilio usan el marcador `.` que ya emplea la base de ManagerControl. Se copia el domicilio, teléfono y correo disponibles en el CRM. `TipoPersona` se determina por la longitud del RFC: 12 caracteres para moral y 13 para física; un RFC con otra longitud se deja para revisión.

## Revisión del 2026-11-12

- Respaldo previo (solo lectura) de `Catalogos.Clientes`, `Catalogos.Secciones`, `Catalogos.Actividades` y `Catalogos.Departamentos` en `storage/app/managercontrol-backups/2026-11-12_203719` (JSON y CSV, con `resumen.json` y huellas SHA-256). La carpeta no se sube a Git.
- El RFC genérico del SAT (`XAXX010101000`) y sus variantes mal capturadas (`XXAX01010100`, `XXX010101000`) ya no cuentan como "RFC distinto": el cliente se reconoce por su nombre.
- `catalog_manual_links.customers` en `config/managercontrol.php` vincula 64 clientes que ya existían en ManagerControl con otro nombre (sin "SA DE CV", errores de captura o apellidos en otro orden), confirmados con las iniciales del RFC. Si el cliente está repetido en ManagerControl se usa el que tiene más horas registradas.
- `catalog_hold.customers` deja 5 clientes sin importar hasta que alguien confirme si ya existen.
- Resultado de la vista previa: 130 clientes vinculados a registros existentes, 9 clientes nuevos, 13 cuentas y 11 actividades por importar, 5 pendientes.

### Usar la cuenta de captura existente

Si no se define `MANAGERCONTROL_CATALOG_WRITE_USERNAME`, la importación usa la cuenta de captura (`MANAGERCONTROL_WRITE_USERNAME`). Un administrador de SQL Server debe otorgarle, una sola vez:

```sql
USE [ControlTiempos_Contabilidad];
GRANT SELECT, INSERT ON [Catalogos].[Clientes] TO [crm_captura];
GRANT SELECT, INSERT ON [Catalogos].[Secciones] TO [crm_captura];
GRANT SELECT, INSERT ON [Catalogos].[Actividades] TO [crm_captura];
```

No se necesita `UPDATE`, `DELETE` ni `ALTER`. Después basta con `MANAGERCONTROL_CATALOG_WRITE_ENABLED=true` en el `.env`.
