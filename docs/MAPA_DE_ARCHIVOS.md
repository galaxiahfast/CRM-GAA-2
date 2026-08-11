# Mapa de archivos del proyecto

Esta guía permite localizar rápidamente los componentes principales del CRM. Los nombres describen la función de cada pantalla; las rutas públicas y el funcionamiento del sistema no cambiaron.

## Administración

| Función | Componente Livewire | Vista Blade |
| --- | --- | --- |
| Panel principal, organigrama y catálogos | `app/Livewire/Administracion/PanelAdministracion.php` | `resources/views/livewire/administracion/panel-administracion.blade.php` |
| Gestión de usuarios | `app/Livewire/Administracion/Users/GestionUsuarios.php` | `resources/views/livewire/administracion/users/gestion-usuarios.blade.php` |
| Gestión de auxiliares | `app/Livewire/Administracion/Interns/GestionAuxiliares.php` | `resources/views/livewire/administracion/interns/gestion-auxiliares.blade.php` |
| Relaciones jerárquicas | `app/Livewire/Administracion/Relationship/GestionRelacionesJerarquicas.php` | `resources/views/livewire/administracion/relationship/gestion-relaciones-jerarquicas.blade.php` |
| Gestión de roles | `app/Livewire/Administracion/Roles/GestionRoles.php` | `resources/views/livewire/administracion/roles/gestion-roles.blade.php` |

## Clientes

| Función | Componente Livewire | Vista Blade |
| --- | --- | --- |
| Listado y gestión de clientes | `app/Livewire/Customer/GestionClientes.php` | `resources/views/livewire/customer/gestion-clientes.blade.php` |
| Crear cliente | `app/Livewire/Customer/CrearCliente.php` | `resources/views/livewire/customer/crear-cliente.blade.php` |
| Editar cliente | `app/Livewire/Customer/EditarCliente.php` | `resources/views/livewire/customer/editar-cliente.blade.php` |
| Detalle de cliente | `app/Livewire/Customer/DetalleCliente.php` | `resources/views/livewire/customer/detalle-cliente.blade.php` |

## Control de horas

| Función | Componente o controlador | Vista Blade |
| --- | --- | --- |
| Registro operativo de actividades | `app/Livewire/TimeControl/RegistroActividades.php` | `resources/views/livewire/time-control/registro-actividades.blade.php` |
| Informe general de supervisión | `app/Livewire/TimeControl/Admin/InformeGeneralHoras.php` | `resources/views/livewire/time-control/admin/informe-general-horas.blade.php` |
| Contenido del informe general | Incluido por la vista anterior | `resources/views/livewire/time-control/admin/contenido-informe-general.blade.php` |
| Informe de productividad | `app/Http/Controllers/DashboardController.php` | `resources/views/time-dashboard/informe-productividad.blade.php` |

## Nombres que se conservan por convención

- Los métodos `index`, `store`, `update` y `destroy` de controladores siguen las convenciones de Laravel y no son archivos ambiguos.
- Las migraciones conservan sus nombres originales porque Laravel registra su nombre exacto en la base de datos.
- Los modelos conservan nombres de clase en inglés para mantener relaciones, tablas y contratos existentes.
- `resources/views/api/index.blade.php` pertenece a la estructura de Jetstream y se mantiene para evitar romper su integración.
- Los archivos internos de Fortify y Jetstream no se renombran.

## Regla para archivos nuevos

Los componentes y vistas propios deben utilizar nombres descriptivos, por ejemplo `GestionProveedores`, `InformeMensual` o `EditarContrato`. Evita nombres aislados como `Index`, `Form`, `View` o `Dashboard` cuando la carpeta no haga evidente su propósito.
