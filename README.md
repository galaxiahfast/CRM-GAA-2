# CRM GAA

Sistema corporativo desarrollado con Laravel 11, Livewire 3, Alpine.js, Tailwind CSS y Vite. Integra administración de usuarios, clientes, control de horas, reloj checador, organigrama, reportes y soporte.

## Tecnologías principales

- PHP 8.2 o superior
- Laravel 11
- Livewire 3
- Node.js y npm
- Vite 6
- MySQL
- Tailwind CSS

## Requisitos previos

Antes de iniciar, comprueba que estén disponibles los siguientes comandos:

```powershell
php --version
composer --version
node --version
npm --version
```

En Windows se recomienda utilizar PHP y MySQL mediante XAMPP, además de instalar Composer y una versión LTS de Node.js.

## Instalación inicial

Ubícate en la carpeta del proyecto e instala las dependencias que todavía no existan:

```powershell
composer install
npm install
```

Configura el archivo `.env` con las credenciales de la base de datos y genera la clave de la aplicación si aún no existe:

```powershell
php artisan key:generate
php artisan migrate
```

> No ejecutes `php artisan key:generate` sobre una instalación existente sin comprobar antes el valor de `APP_KEY`, ya que cambiarlo puede invalidar sesiones y datos cifrados.

## Ejecución en desarrollo local

Para trabajar con todas las funciones del sistema deben mantenerse abiertos tres procesos. Ejecuta cada bloque en una terminal PowerShell independiente desde la raíz del proyecto.

### 1. Servidor Laravel

```powershell
php artisan serve
```

La aplicación estará disponible normalmente en:

```text
http://127.0.0.1:8000
```

### 2. Vite y recursos del frontend

```powershell
npm run dev
```

Este proceso recompila automáticamente los estilos y scripts cuando detecta cambios. Debe permanecer abierto durante el desarrollo.

### 3. Tareas programadas

```powershell
php artisan schedule:work
```

El scheduler ejecuta procesos recurrentes como la sincronización biométrica, el mantenimiento de sesiones y la limpieza programada del chat de soporte.

Para detener cualquiera de estos procesos utiliza `Ctrl+C`. Si PowerShell muestra `¿Desea terminar el trabajo por lotes (S/N)?`, responde `S`.

### Inicio unificado opcional

También es posible iniciar servidor, cola, registros, Vite y scheduler desde una sola terminal:

```powershell
composer run dev
```

El inicio separado en tres terminales resulta más práctico cuando se necesita revisar individualmente la salida de cada proceso.

## Ejecución en un servidor interno o red local

En el servidor Windows, abre PowerShell y entra en la ubicación real del proyecto:

```powershell
cd C:\xampp\htdocs\crm-v1
```

Inicia Laravel escuchando en todas las interfaces de red:

```powershell
php artisan serve --host=0.0.0.0 --port=8001
```

El servidor mostrará una salida similar a:

```text
Starting Laravel development server: http://0.0.0.0:8001
```

Desde otro equipo de la misma red se debe utilizar la dirección IP del servidor, no `0.0.0.0`. Por ejemplo:

```text
http://192.168.2.122:8001
```

Si el puerto `8001` está ocupado, utiliza otro puerto y conserva el mismo número en la URL:

```powershell
php artisan serve --host=0.0.0.0 --port=8002
```

```text
http://192.168.2.122:8002
```

En terminales adicionales también deben permanecer activos los recursos frontend y las tareas programadas:

```powershell
cd C:\xampp\htdocs\crm-v1
npm run dev
```

```powershell
cd C:\xampp\htdocs\crm-v1
php artisan schedule:work
```

> `php artisan serve` es apropiado para desarrollo o una red interna controlada. Para un despliegue público de producción debe configurarse Apache o Nginx, HTTPS y un servicio permanente para el scheduler.

## Compilación para producción

En el servidor, confirma primero que el archivo `.env` contenga:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

No copies a producción los archivos PHP generados dentro de `bootstrap/cache`
en otro equipo. Laravel puede seguir usando una configuración anterior aunque
el `.env` ya se haya corregido.

Después de actualizar el código, ejecuta desde la raíz del proyecto:

```powershell
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize
```

Verifica la configuración que Laravel está usando realmente, no sólo el
contenido visible del `.env`:

```powershell
php artisan about --only=environment
```

La salida del servidor debe indicar `Environment: production` y
`Debug Mode: OFF`. El comando `php artisan optimize` genera las cachés de
configuración, eventos, rutas y vistas; debe ejecutarse otra vez después de
cambiar el `.env` o archivos de configuración.

Para producción utiliza Apache, Nginx o IIS con OPcache habilitado. No dejes la
aplicación atendida por `php artisan serve`, porque es un servidor de desarrollo
y procesa peor la concurrencia.

## Actualización de Browserslist

Si Vite muestra que la información de navegadores está desactualizada, ejecuta:

```powershell
npx update-browserslist-db@latest
```

Este aviso no impide iniciar la aplicación; únicamente recomienda actualizar los datos de compatibilidad utilizados durante la compilación.

## Verificación del sistema

Ejecuta las pruebas automatizadas antes de publicar cambios importantes:

```powershell
php artisan test
```

Para validar la compilación del frontend:

```powershell
npm run build
```

## Importación de hojas de entrega anteriores

El módulo **Soporte > Centro de ayuda > Hoja de entrega** reemplaza la
aplicación local de Python. Para trasladar su historial SQLite sin modificar el
archivo original, ejecuta una sola vez:

```powershell
php artisan delivery-notes:import-legacy "C:\Users\MOISES INFORMATICA\Desktop\Hoja de Entrega\instance\datamid.sqlite3" --uploads="C:\Users\MOISES INFORMATICA\Desktop\Hoja de Entrega\instance\uploads"
```

El importador puede repetirse con seguridad: los folios que ya existen se
omiten y las fotografías se copian al almacenamiento privado del CRM.

## Publicación única del historial de órdenes de servicio

Git publica el código, pero no copia los registros de la base de datos local.
Para trasladar una sola vez el historial actual —incluidas sus evidencias— crea
la instantánea versionable en el equipo local:

```powershell
php artisan delivery-notes:export-history
git add database/seeders/data/historial_ordenes.json
git commit -m "Export historial de órdenes de servicio"
git push
```

Después del despliegue, impórtala una sola vez en producción:

```bash
php artisan migrate --force
php artisan db:seed --class=HistorialOrdenesServicioSeeder --force
```

El seeder busca cada orden por folio y cada movimiento por tipo y fecha, por lo
que puede repetirse sin duplicar información. No está registrado en
`DatabaseSeeder` y, por tanto, no se ejecuta automáticamente durante los
despliegues posteriores.

## Autocompletado gratuito de hojas de entrega

El módulo puede leer etiquetas de equipos y proponer marca, modelo, número de
serie, tipo de equipo y accesorios. Por seguridad y costo, la configuración
predeterminada usa **Tesseract local**: la fotografía no sale del servidor y no
existe cuota ni suscripción.

Instala el ejecutable y los idiomas en el servidor Ubuntu/Debian:

```bash
sudo apt update
sudo apt install -y tesseract-ocr tesseract-ocr-eng tesseract-ocr-spa
tesseract --version
composer install --no-dev --optimize-autoloader
```

Configura el `.env` de producción:

```dotenv
EQUIPMENT_OCR_DRIVER=tesseract
TESSERACT_BINARY=/usr/bin/tesseract
TESSERACT_LANGUAGES=eng+spa
TESSERACT_TIMEOUT=25

# Catálogo UPCitemdb: opcional, sin registro, 100 consultas diarias.
EQUIPMENT_CATALOG_ENABLED=false
EQUIPMENT_CATALOG_TEXT_SEARCH=false
UPCITEMDB_TIMEOUT=8
UPCITEMDB_CACHE_HOURS=168
```

En Windows instala Tesseract 5, agrega su carpeta al `PATH` o define, por
ejemplo, `TESSERACT_BINARY=C:\Program Files\Tesseract-OCR\tesseract.exe`.

Como alternativa opcional, OCR.space ofrece una clave gratuita. Esta opción
envía la fotografía a un servicio externo; actívala únicamente si la política
de privacidad de DataMID lo permite:

```dotenv
EQUIPMENT_OCR_DRIVER=ocr_space
OCR_SPACE_API_KEY=clave_gratuita
OCR_SPACE_TIMEOUT=25
```

La clave se obtiene en `https://ocr.space/ocrapi`. UPCitemdb no necesita clave
en su plan de prueba; se habilita con `EQUIPMENT_CATALOG_ENABLED=true`. La
búsqueda por texto consume una cuota más limitada, por eso permanece separada
en `EQUIPMENT_CATALOG_TEXT_SEARCH`.

Después de modificar el `.env`, aplica la configuración:

```bash
php artisan optimize:clear
php artisan optimize
```

Si Tesseract no está disponible o una API gratuita alcanza su límite, la foto
se conserva y el formulario continúa permitiendo captura y corrección manual.

## Solución rápida de problemas

### Los cambios visuales no aparecen

Comprueba que `npm run dev` siga activo y limpia las vistas compiladas:

```powershell
php artisan view:clear
```

### Las tareas automáticas no se ejecutan

Comprueba que esta terminal permanezca abierta:

```powershell
php artisan schedule:work
```

### El puerto ya está en uso

Selecciona otro puerto disponible:

```powershell
php artisan serve --host=0.0.0.0 --port=8002
```

### Se instalaron o actualizaron dependencias

```powershell
composer install
npm install
php artisan optimize:clear
npm run build
```

## Seguridad

- No publiques el archivo `.env` ni credenciales de acceso.
- No compartas `APP_KEY`.
- Mantén `APP_DEBUG=false` fuera del entorno de desarrollo.
- Respalda la base de datos antes de ejecutar migraciones en producción.
- Utiliza HTTPS y un servidor web configurado para despliegues públicos.
