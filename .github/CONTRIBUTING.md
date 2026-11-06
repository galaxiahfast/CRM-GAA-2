# Convenciones de cambios

## Commits

Escribe títulos breves en español con el formato `tipo(área): descripción`. Usa minúsculas y un verbo que describa el cambio.

Tipos recomendados: `feat` (función), `fix` (corrección), `docs` (documentación), `style` (presentación), `refactor` (reorganización interna), `test` (pruebas) y `chore` (mantenimiento).

Ejemplos:

- `feat(reportes): agrega reporte de instalación de software`
- `fix(roles): permite reasignar usuarios antes de eliminar un rol`
- `docs(versiones): registra la versión inicial`

Esta convención aplica a los commits nuevos. Los commits ya publicados conservan sus identificadores para que las instalaciones existentes puedan seguir usando `git pull`.

## Versiones

Usa etiquetas anotadas `vMAJOR.MINOR.PATCH`. Incrementa `PATCH` para correcciones compatibles, `MINOR` para funciones compatibles y `MAJOR` para cambios incompatibles. Antes de publicar una etiqueta, actualiza `CHANGELOG.md` con la fecha y los cambios incluidos.

Los PDF finales de reportes forman parte del repositorio. Las imágenes temporales usadas para revisar PDF no son entregables y deben permanecer fuera de los commits nuevos.
