# Preparación de alojamiento · 26 de octubre de 2026

**La actualización administrativa y la recuperación de contraseña no se publicaron.** La publicación se canceló antes de reemplazar la aplicación o ejecutar la migración de la base pública. Subir estos archivos a GitHub no actualiza InfinityFree.

El proyecto local vigente y sus instrucciones están en ../tecnosoft-xampp. Esta carpeta adapta sus rutas al directorio htdocs del alojamiento.

## Archivos

- htdocs/: código preparado con módulos privados en _private y acceso directo bloqueado.
- actualizar-base-existente.sql: migración preparada para una instalación anterior.
- importar-en-base-vacia.sql: 31 tablas y catálogo de demostración para una base nueva.
- PUBLICACION-INICIAL.md: historial de la versión pública anterior; no describe la nueva administración.

## Antes de una futura publicación

1. Obtener y comprobar un respaldo restaurable de la base y los archivos existentes.
2. Ensayar la migración en una copia aislada de ese respaldo. La migración del alojamiento permanece pendiente de esa prueba.
3. Seleccionar en phpMyAdmin la base de destino. Usar actualizar-base-existente.sql para conservar registros; importar-en-base-vacia.sql es solamente para una base vacía.
4. Subir el contenido de htdocs conservando los archivos privados de configuración del servidor y sus sesiones.
5. Mantener _private/.htaccess, configurar SMTP con la URL base pública HTTPS y comprobar los permisos de administración por un medio privado.
6. Verificar registro, confirmación, permisos, pedidos, existencias y reportes antes de compartir la actualización.

No se incluyen contraseñas de MySQL o Gmail, sesiones, respaldos ni registros personales. Los datos de XAMPP y del alojamiento siguen siendo independientes.

Preparación del 26 de octubre: incluye recuperación por correo, invalidación de sesiones al cambiar contraseña y cierre exterior de los detalles de producto. La migración pública agrega users.password_version y password_resets. Sigue pendiente probar esta migración sobre un respaldo del alojamiento antes de publicar. Los ensayos locales no cambian esa base pública.
