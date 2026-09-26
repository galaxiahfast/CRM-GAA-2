# Generador de reportes de respaldos

Aplicación de escritorio para registrar el respaldo de cada servidor y generar un reporte profesional en Word y PDF.

## Primera instalación

1. Instala Python 3.10 o posterior y activa la opción **Add Python to PATH**.
2. Ejecuta `instalar.bat` una sola vez.
3. Abre `iniciar.bat` cada vez que quieras crear un reporte.

## Uso

1. Completa el título, fecha y responsable.
2. Agrega una sola fila por servidor e indica cuándo se realizó.
3. Solo si hubo fallos, pulsa **Indicar no realizados** y escribe esos nombres.
4. Si todo salió bien, deja ese campo vacío; no se enumerarán los archivos exitosos.
5. Selecciona Word, PDF o ambos y pulsa **Generar reporte**.

Puedes guardar la captura como JSON para continuarla más tarde. También admite CSV con los encabezados `servidor`, `archivo` o `perfil`, `ultimo_respaldo`, `estado` y `observaciones`.

Los estados reconocidos son: Realizado, Falló, Advertencia y Pendiente. El resumen general se calcula automáticamente.
