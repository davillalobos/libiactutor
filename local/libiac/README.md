# local_libiac

Guarda la entrevista inicial y el último contexto de conversación de cada usuario de Moodle.

## Instalación
1. Copiar `local/libiac/` a `<moodle>/local/libiac/`.
2. Administración del sitio > Notificaciones > actualizar la base de datos.

## Rollback
- **Antes de actualizar la BD:** borrar la carpeta `<moodle>/local/libiac/`.
- **Ya instalado:** Administración del sitio > Plugins > Resumen de plugins > Libiac > Desinstalar (elimina la tabla `local_libiac_user_ctx`), luego borrar la carpeta. Alternativa por CLI: `php admin/cli/uninstall_plugins.php --plugins=local_libiac --run`.
- **Código:** `git checkout master && git branch -D feat/moodle-local-libiac-base`.
- Hacer backup de la BD antes de actualizar.
