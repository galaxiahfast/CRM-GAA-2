# ManagerControl — agregar, editar y dar de baja actividades desde el CRM

**Estado actual: SOLO LECTURA.** Todo está construido pero apagado (`MANAGERCONTROL_WRITE_ENABLED=false`).
Mientras siga así, el CRM no puede escribir nada en ManagerControl: los botones aparecen desactivados con la leyenda
«Solo lectura…» y el servicio rechaza cualquier intento (`WriteDisabled`), sin abrir conexión.

## 1. Lo que se revisó en la base (solo con SELECT, 11/11/2026)

### Tabla `ControlTiempos.RegistroDiario` (una fila = una actividad capturada)

| Columna | Tipo | Cómo la llena ManagerControl (confirmado con los datos de 2026) |
|---|---|---|
| `IDRegistroDiario` | int, **identidad** | La genera SQL Server. |
| `IDEmpresa`, `IDEjercicio` | int, NOT NULL | Empresa y ejercicio con que se entra a la app. **Forman la llave junto con IDRegistroDiario.** |
| `NombreUsuario` | varchar(50) | Usuario de ManagerControl del colaborador (98 % de los casos lo captura el mismo colaborador). |
| `IDEmpleado`, `IDCliente`, `IDActividad` | int, NOT NULL | Colaborador, cliente y actividad (catálogos de la misma empresa/ejercicio). |
| `HoraLaborada` | decimal(18,1) | Horas en **décimas** (0.1 = 6 min). Los valores más comunes: 2.0, 1.0, 0.5, 0.3, 3.0, 0.1. |
| `Descripcion` | varchar(max), NOT NULL | Texto libre (puede ir vacío). |
| `FechaRegistro` | datetime | Solo la **fecha** (siempre a las 00:00). |
| `FechaCreacion` | datetime | Momento real de la captura (77 % se captura días después). |
| `Baja` | bit, NOT NULL | **Baja lógica**: «eliminar» pone `Baja = 1` (hay 5,046 filas así). Nunca se borra la fila. |
| `Precio` | decimal(18,1) | Precio por hora del colaborador (`Empleados.Precio`) al momento de capturar (98 %). |
| `SubTotal` | decimal(18,1) | `Precio × HoraLaborada`, redondeado a 1 decimal. |
| `IDSeccion` | int, NOT NULL | Siempre 0 en 2026. |
| `PrecioAlimentos`, `PrecioTransporte` | float, NULL | Casi sin uso (49 y 4 filas en 2026); el CRM no los toca. |

- No se ven triggers ni procedimientos sobre la tabla (la cuenta de lectura no tiene VIEW DEFINITION; confirmarlo con el responsable de la base).
- Llave primaria: `PK_RegistroDiario (IDEmpresa, IDEjercicio, IDRegistroDiario)`.

### Empresas y ejercicios
La base guarda varias empresas. La que usa la oficina es **IDEmpresa 1 «GONZÁLEZ ALONZO Y ASOCIADOS S.C.P.-CONTABILIDAD», IDEjercicio 3
«ENERO 2010- DICIEMBRE 2010»** (todos los registros de 2026 son de ahí). Antes la lista de colaboradores mezclaba 14 de «AUDITORIA»;
ahora **todas** las consultas (y escrituras) se limitan a `MANAGERCONTROL_COMPANY_ID` / `MANAGERCONTROL_EXERCISE_ID` (por defecto 1 y 3).

## 2. Qué hace el CRM al escribir (ya programado)

| Acción | Sentencia (siempre parametrizada) |
|---|---|
| Agregar | `INSERT INTO [ControlTiempos].[RegistroDiario] (...) OUTPUT INSERTED.[IDRegistroDiario] VALUES (...)` con empresa, ejercicio, usuario y precio del colaborador, `FechaCreacion = GETDATE()`, `Baja = 0`, `IDSeccion = 0`. |
| Editar | `UPDATE ... SET IDActividad, IDCliente, HoraLaborada, Descripcion, FechaRegistro, SubTotal = ROUND(Precio × horas, 1)` — se conserva el precio de la captura original. |
| Dar de baja | `UPDATE ... SET [Baja] = 1` (igual que ManagerControl). |

Cada UPDATE lleva `WHERE IDRegistroDiario = ? AND IDEmpresa = ? AND IDEjercicio = ? AND IDEmpleado = ? AND Baja = 0`: no se puede tocar
un registro de otra persona, de otra empresa o ya dado de baja; si no cambia exactamente 1 fila, se revierte la transacción.
Además la conexión de escritura **solo** acepta un INSERT o UPDATE sobre esa tabla (nunca DELETE, DDL, EXEC ni varias sentencias).

Validaciones: fecha válida, cliente y actividad del catálogo activo, horas de 0.1 a 24 (en décimas), descripción hasta 2000 caracteres.
Cada alta, edición o baja queda en la bitácora del CRM (`managercontrol_audits`: usuario, acción, registro, colaborador y datos).

### Quién puede
- **Administrador**: cualquier colaborador (pantalla ManagerControl, eligiendo a la persona).
- **Demás usuarios** (Control Gerencial): solo **sus** actividades (su asignación o la coincidencia de nombre).

### Sincronización
No hay copias: cada pantalla, tabla y PDF lee directo de ManagerControl, así que lo que se agrega, edita o da de baja aparece al momento
en el administrador, en Control Gerencial, en ambos PDF y en la propia app de escritorio (y viceversa).
Solo la lista de colaboradores y los catálogos se recuerdan 10 minutos (`php artisan cache:clear` para verlos al instante).

## 3. Para activarlo (cuando se den los permisos)

1. El responsable de SQL Server crea una cuenta **solo para captura** (no usar `sa`):
   ```sql
   USE [master];
   CREATE LOGIN [crm_captura] WITH PASSWORD = N'CambiaEstaClave#2026', CHECK_POLICY = ON;
   USE [ControlTiempos_Contabilidad];
   CREATE USER [crm_captura] FOR LOGIN [crm_captura];
   GRANT SELECT, INSERT, UPDATE ON [ControlTiempos].[RegistroDiario] TO [crm_captura];
   ```
   (sin DELETE: la baja es lógica). La cuenta de lectura `crm_lectura` se queda igual.
2. En el `.env` del servidor:
   ```
   MANAGERCONTROL_COMPANY_ID=1
   MANAGERCONTROL_EXERCISE_ID=3
   MANAGERCONTROL_WRITE_USERNAME=crm_captura
   MANAGERCONTROL_WRITE_PASSWORD="la contraseña"
   MANAGERCONTROL_WRITE_ENABLED=true
   ```
3. `php artisan config:clear` y `php artisan optimize`.
4. Probar primero con una actividad de prueba y darla de baja; revisar `managercontrol_audits`.

Para volver a solo lectura en cualquier momento: `MANAGERCONTROL_WRITE_ENABLED=false` y `php artisan optimize`.

## 4. Pendiente de confirmar antes de activar
- Que no haya triggers o procesos de ManagerControl que dependan de otra tabla al capturar (p. ej. liquidaciones).
- Si ManagerControl limita la captura a fechas del ejercicio o a días pasados (el CRM hoy permite cualquier fecha válida).
- Si el precio debe recalcularse al editar (hoy se conserva el de la captura original).
