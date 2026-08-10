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

Para generar los recursos optimizados del frontend:

```powershell
npm run build
```

Después de actualizar el código en el servidor, limpia y reconstruye las cachés necesarias:

```powershell
php artisan optimize:clear
php artisan migrate --force
npm run build
```

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
