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
| Agregar | `INSERT INTO [ControlTiempos].[RegistroDiario] (...) OUTPUT INSERTED.[IDRegistroDiario] VALUES (...)` con empresa, ejercicio, usuario, precio, horas, subtotal, descripción y fecha (las mismas columnas que `RegistroDiario_Ins`); `FechaCreacion`, `Baja` e `IDSeccion` los pone la base por defecto. |
| Editar | `UPDATE ... SET IDActividad, IDCliente, HoraLaborada, Descripcion, FechaRegistro, SubTotal = ROUND(Precio × horas, 1)` — se conserva el precio de la captura original. |
| Dar de baja | `UPDATE ... SET [Baja] = 1` (igual que ManagerControl). |

Cada UPDATE lleva `WHERE IDRegistroDiario = ? AND IDEmpresa = ? AND IDEjercicio = ? AND IDEmpleado = ? AND Baja = 0`: no se puede tocar
un registro de otra persona, de otra empresa o ya dado de baja; si no cambia exactamente 1 fila, se revierte la transacción.
Además la conexión de escritura **solo** acepta un INSERT o UPDATE sobre esa tabla (nunca DELETE, DDL, EXEC ni varias sentencias).

Validaciones: fecha válida, cliente y actividad del catálogo activo, tiempo de 0:06 a 24 h (como 2:30 o 2.5), descripción hasta 2000 caracteres.
Cada alta, edición o baja queda en la bitácora del CRM (`managercontrol_audits`: usuario, acción, registro, colaborador y datos).

### Quién puede
- **Administrador**: cualquier colaborador (pantalla ManagerControl, eligiendo a la persona).
- **Demás usuarios** (Manager Control): solo **sus** actividades (su asignación o la coincidencia de nombre).

### Sincronización
No hay copias: cada pantalla, tabla y PDF lee directo de ManagerControl, así que lo que se agrega, edita o da de baja aparece al momento
en el administrador, en Manager Control, en ambos PDF y en la propia app de escritorio (y viceversa).
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

## 4. Convivencia con la app de escritorio (revisado 12/11/2026)

- **Cómo guarda el escritorio**: no escribe directo en la tabla; usa procedimientos almacenados
  `ControlTiempos.RegistroDiario_Ins`, `RegistroDiario_Upd`, `RegistroDiario_Sel`, `RegistroDiario_Dia`
  (Enterprise Library descubre sus parámetros al ejecutarse). La cuenta `crm_lectura` no puede ver su código.
- **Código revisado (12/11/2026)**: `RegistroDiario_Ins` solo hace un INSERT de empresa, ejercicio, usuario, empleado, actividad, cliente,
  horas, precio, subtotal, descripción, alimentos, transporte y fecha (el SubTotal lo calcula la app, no la base);
  `RegistroDiario_Upd` hace un UPDATE por `IDRegistroDiario` de esos mismos campos y `Baja`. No hay lógica extra ni otras tablas.
- **Sin triggers, llaves foráneas ni objetos** que dependan de `RegistroDiario`; ninguna otra tabla guarda `IDRegistroDiario`.
  `Liquidaciones` trabaja por rango de fechas y usuario (no por registro): una alta o edición dentro de un periodo
  ya liquidado cambia el total de ese periodo, igual que si se hiciera desde el escritorio.
- **Bloqueos**: la base usa READ COMMITTED sin snapshot. Cada escritura del CRM es una sola sentencia en una transacción
  corta (milisegundos); el escritorio solo espera ese instante. El escritorio no ve lo nuevo hasta que recarga su lista.
- **Tiempo y SubTotal**: el escritorio captura horas:minutos; guarda `HoraLaborada` en décimas y calcula
  `SubTotal = Precio × horas a 2 decimales` (2:13 → 2.22 h). El CRM ahora hace exactamente lo mismo.
- **Edición al mismo tiempo**: al editar desde el CRM, el UPDATE exige que el registro siga igual que cuando se abrió el
  formulario (cliente, actividad, horas, fecha y descripción). Si alguien lo cambió o lo dio de baja desde el escritorio,
  no se sobrescribe y se muestra un aviso. (El escritorio no tiene esta protección: si después edita un registro que el CRM
  cambió, gana el último que guarda.)

## 5. Formulario de captura (igual que «Registro Diario» del escritorio, sin comida ni transporte)

- **Campos**: fecha, tiempo (h:mm), cliente, **Cuenta/Concepto**, actividad y descripción. No se capturan `PrecioAlimentos` ni `PrecioTransporte`.
- **Cuenta/Concepto** es la sección (`Catalogos.Secciones`) de la que cuelgan las actividades (`Actividades.IDSeccion`). Solo sirve para
  filtrar la lista de actividades (y al elegir una actividad se ajusta su cuenta); el registro no la guarda (`IDSeccion` queda en 0, como en el escritorio).
- **Recuerda lo que más usa**: al abrir «Agregar actividad» ya vienen elegidos el cliente, la cuenta y la actividad de la combinación más
  repetida del colaborador en los últimos 90 días (si no hay, en toda su historia; a igualdad, la más reciente). Se lee de la base, así cuenta
  también lo capturado en el escritorio. La fecha va de hoy y el tiempo y la descripción en blanco.
- **Protecciones antes de escribir** (todo error sale en una ventana emergente con el diseño del panel; nunca falla en silencio):
  fecha válida, no futura y no más de 400 días atrás (`MANAGERCONTROL_WRITE_MAX_PAST_DAYS`); tiempo de 0:06 a 24:00; cliente y actividad del catálogo
  activo; el total del día del colaborador no puede pasar de 24 h; una actividad idéntica del mismo día (doble clic) no se duplica; al editar se
  exige que el registro siga igual que al abrirlo; cada UPDATE solo toca un registro activo de ese colaborador, empresa y ejercicio.
- **Dar de baja** pide confirmación en una ventana del panel (con fecha, cliente, actividad y tiempo) y solo al confirmar escribe `Baja = 1`.
- **Calendario**: los días en que el colaborador ya tiene actividades salen en verde; al elegirlos aparece la lista de ese día con «Editar».

## 6. Pendiente de confirmar antes de activar
- Código de `ControlTiempos.RegistroDiario_Ins` y `_Upd` (por ejemplo `EXEC sp_helptext 'ControlTiempos.RegistroDiario_Ins'`,
  o `GRANT VIEW DEFINITION ON ... TO crm_lectura`), para confirmar que no hacen nada extra que el CRM deba repetir.
- Si ManagerControl limita la captura a fechas del ejercicio o a días pasados (el CRM hoy permite cualquier fecha válida).
- Si el precio debe recalcularse al editar (hoy se conserva el de la captura original).
