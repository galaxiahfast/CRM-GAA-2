# Auditoría de rendimiento

Fecha: 2026-08-10

## Alcance revisado

- Autenticación, cierre de sesión y almacenamiento de sesiones.
- Navegación global, permisos y centro de notificaciones.
- Componentes Livewire de administración, clientes y control de horas.
- Organigrama y presencia de usuarios.
- Reportes de productividad, dashboard y reporte anual de clientes.
- Consultas frecuentes e índices de MySQL.

## Hallazgos corregidos

### Dashboard de productividad

- Existían escrituras de log por cada actividad, intervalo, día y cliente.
- El tiempo efectivo de una misma actividad se recalculaba varias veces.
- La combinación principal cliente-actividad ejecutaba nuevamente la consulta completa del periodo.
- Algunos filtros por actividad usaban `whereHas` aunque la llave foránea estaba disponible directamente.

Resultado: una sola carga del periodo y un solo cálculo por actividad, sin logs de diagnóstico en el flujo normal.

### Reporte anual de clientes

- Ejecutaba consultas dentro de ciclos por mes, servicio y archivo.
- Antes de abrir un solo reporte cargaba también todo el dashboard general.

Resultado: el reporte carga únicamente el cliente solicitado y realiza tres consultas masivas y constantes sobre `customer_files`, independientemente del número de meses o servicios.

### Livewire y navegación global

- El buscador de clientes cargaba el listado en `updatedSearch` y nuevamente durante `render`.
- Las notificaciones consultaban lista, total y no leídas cada 5 segundos en todos los apartados.
- El keep-alive podía iniciar solicitudes superpuestas y competir con el cierre de sesión.

Resultado:

- Una sola consulta del listado por actualización del buscador.
- Polling de notificaciones cada 30 segundos y conteos consolidados en una consulta. La carga estimada pasa de 36 a 4 consultas por minuto por usuario conectado.
- Keep-alive de presencia conservado en 30 segundos, con exclusión mutua y cancelación inmediata al cerrar sesión.

### Permisos y datos de referencia

- Middleware, Gates y navegación podían resolver instancias distintas del servicio de permisos durante la misma petición.
- Catálogos de áreas, puestos, roles, permisos, clientes y actividades se consultaban repetidamente en renders Livewire.
- El organigrama consultaba varias veces la misma tabla de relaciones para construir candidatos y linajes.

Resultado:

- Servicio de permisos compartido únicamente durante la petición actual; no se comparten decisiones entre usuarios.
- Caché de catálogos con invalidación automática al crear, editar o eliminar sus modelos.
- Una sola lectura de relaciones jerárquicas por render administrativo.
- El árbol del organigrama conserva su carga ansiosa existente y no presenta N+1 en usuarios, roles o perfiles.

### Sesiones y concurrencia

- La recolección probabilística de sesiones podía caer sobre una petición interactiva y producir picos de latencia.

Resultado: limpieza programada cada 30 minutos y probabilidad de respaldo reducida a 1/1000. La duración, autenticación y presencia no cambian.

## Índices agregados

- `sessions (user_id, last_activity)`
- `notifications (notifiable_type, notifiable_id, created_at)`
- `notifications (notifiable_type, notifiable_id, read_at)`
- `time_entries (user_id, entry_date)`
- `control_de_horas (employeeID, authDate, authDateTime)`
- `customer_accountants (accountant_id, status, customer_id)`
- `customer_accountants (customer_id, status, accountant_id)`
- `customer_files (customer_id, upload_period, sub_service_id)`
- `customer_files (customer_id, declaration_type, file_type)`

MySQL confirmó mediante `EXPLAIN` el uso de los nuevos índices para periodos de actividades, asistencia y presencia.

## Validación

- Pruebas específicas de caché, invalidación, conteos de notificaciones, índices y ausencia de N+1 en el reporte anual.
- Pruebas existentes de autenticación, permisos, organigrama, clientes, sesiones, control de horas y exportaciones.
- Compilación de Blade, Tailwind y Vite.

## Escalamiento futuro

Si el sistema crece a cientos de usuarios concurrentes, el siguiente paso recomendado es mover caché y sesiones de MySQL a Redis y sustituir polling por eventos WebSocket. No se aplicó ahora porque implica infraestructura nueva y no es necesario para conservar la lógica actual.
