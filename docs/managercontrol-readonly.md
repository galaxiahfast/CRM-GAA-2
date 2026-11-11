# ManagerControl (legacy) — lectura de solo lectura

Pantalla: **Pruebas** (menú lateral, solo Administrador) → `/pruebas/managercontrol`. PDF: `/pruebas/managercontrol/reporte.pdf`.
Ambas rutas usan `can:view-legacy-managercontrol` (403 para cualquier otro rol).

## Qué dice el `ManagerControl.exe.config`
- Motor: **Microsoft SQL Server** (`System.Data.SqlClient`).
- Servidor `SRVIFAC`, instancia `SISTEMAS` (puerto dinámico vía SQL Browser), base `ControlTiempos_Contabilidad`.
- El archivo trae un usuario y contraseña de administrador en texto plano. **La aplicación los descarta y nunca los usa.**
  Conviene avisar a quien administre el servidor que ese archivo expone una credencial privilegiada.

## Configurar `.env` (solo en su equipo; nunca en git)
```
MANAGERCONTROL_HOST=         # vacío = el del .exe.config (SRVIFAC); o la IP real
MANAGERCONTROL_INSTANCE=     # vacío = SISTEMAS (solo se hereda si HOST también está vacío)
MANAGERCONTROL_PORT=         # opcional (1433…)
MANAGERCONTROL_DATABASE=     # vacío = ControlTiempos_Contabilidad
MANAGERCONTROL_SQL_USERNAME= # login de SQL Server de SOLO LECTURA (db_datareader)
MANAGERCONTROL_SQL_PASSWORD=
# MANAGERCONTROL_USERNAME/PASSWORD: se usan solo si no se definen los SQL_*
```
Después: `php artisan config:clear`.

Requisito de PHP: extensión **pdo_sqlsrv** (Microsoft Drivers for PHP for SQL Server + ODBC Driver 17/18). En este equipo **no está instalada**, por lo que la conexión real aún no se ha probado.

## Garantías de solo lectura
1. `ReadOnlyGuard`: solo una sentencia `SELECT`; rechaza `;`, `INTO`, `EXEC`, INSERT/UPDATE/DELETE/DDL, `sp_`/`xp_`, etc. (también tras comentarios).
2. Todas las consultas son fijas, preparadas y con parámetros `?`; los únicos identificadores dinámicos salen de `config/managercontrol.php` y se validan con `^[A-Za-z0-9_]+$`.
3. SQL Server no tiene `SET TRANSACTION READ ONLY`: la sesión se abre con `ApplicationIntent=ReadOnly`, y al conectar se verifica que la cuenta **no** sea `sysadmin`, `db_owner`, `db_datawriter` ni `db_ddladmin`; si lo es, la app se niega a consultar.
4. Timeouts: login 5 s, consulta 15 s, máximo 5000 filas.
5. Los errores se muestran en pantalla (y se registran sin SQL ni parámetros); nada falla en silencio.

## Comandos
```
php artisan managercontrol:test-connection [--config-only]   # motor/servidor/BD/versión y confirmación de solo lectura
php artisan managercontrol:schema Hora                        # tablas/columnas (INFORMATION_SCHEMA) para confirmar el mapa
```

## Mapa de esquema (CONFIRMADO con `managercontrol:schema`, SQL Server 2012, `ControlTiempos_Contabilidad`)
| Concepto | Tabla | Columnas |
|---|---|---|
| Horas laboradas | `ControlTiempos.RegistroDiario` | `IDEmpleado`, `IDCliente`, `IDActividad`, `IDEjercicio`, `FechaRegistro`, `HoraLaborada` (decimal, horas), `Descripcion`, `Baja` (baja lógica) |
| Colaboradores | `Catalogos.Empleados` | `IDEmpleado`, `Nombres` + `ApellidoPaterno` + `ApellidoMaterno`, `Baja` |
| Clientes | `Catalogos.Clientes` | `IDCliente`, `Nombre` |
| Actividades | `Catalogos.Actividades` | `IDActividad`, `Nombre` |

Los nombres de las consultas de abajo usan estas tablas (esquemas incluidos). El login `crm_lectura` solo tiene `db_datareader`.

## Consultas (todas SELECT, parametrizadas)
1. Colaboradores: `SELECT TOP (500) id, nombre FROM Empleado ORDER BY nombre`
2. Un colaborador: `SELECT TOP (1) id, nombre FROM Empleado WHERE id = ?`
3. Horas por colaborador, cliente, actividad y fecha:
```sql
SELECT TOP (5000) h.Fecha, c.Nombre AS customer, a.Nombre AS activity, h.Descripcion, h.HoraLaborada
FROM dbo.ControlHoras h
LEFT JOIN dbo.Cliente c   ON c.IDCliente   = h.IDCliente
LEFT JOIN dbo.Actividad a ON a.IDActividad = h.IDActividad
WHERE h.IDEmpleado = ? AND h.Fecha >= ? AND h.Fecha < DATEADD(day, 1, CAST(? AS date))
  [AND h.IDEjercicio = ?]      -- solo si MANAGERCONTROL_EXERCISE_ID está definido
ORDER BY h.Fecha
```
4. Diagnóstico: `SELECT @@VERSION, DB_NAME(), SUSER_SNAME(), IS_SRVROLEMEMBER('sysadmin'), IS_MEMBER('db_owner'), …`
5. Esquema: `SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME LIKE ?`

Los totales por cliente/actividad/día se calculan en PHP (`HoursSummary`).

## Pendiente antes de conectar a la base real
- Confirmación del usuario del resumen anterior (qué se consulta).
- Instalar `pdo_sqlsrv`, capturar el `.env` y confirmar el mapa con `managercontrol:schema`.
- Muestra/captura del reporte oficial «Total de Horas Laboradas» para ajustar el formato exacto del PDF (el actual es una réplica por descripción).
