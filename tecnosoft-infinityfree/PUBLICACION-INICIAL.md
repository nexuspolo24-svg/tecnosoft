# Historial de la publicación inicial (versión anterior)

Enlace para compartir: https://tecnosoft-solutions-ni.great-site.net/

Alojamiento: InfinityFree, cuenta `if0_43142556`.
Base compartida: `if0_43142556_tecnosoft`, servidor `sql104.infinityfree.com`.
Directorio web: `htdocs`.

## Verificación realizada

- Página abierta mediante HTTPS sin advertencias de certificado.
- Catálogo obtenido desde MySQL: 24 productos, 20 en el catálogo general y 4 juegos.
- 27 tablas InnoDB con `utf8mb4_unicode_ci`, confirmadas en phpMyAdmin.
- Registro, cierre e inicio de sesión comprobados con una cuenta sintética denominada «Prueba de publicación», correo del dominio reservado `example.test`. Se dejó la sesión cerrada.
- Acceso HTTP a `_private/config.example.php` y `_private/runtime/` bloqueado con 403.
- Texto del formulario actualizado para referirse a la base de datos de la tienda.
- Sin errores de consola observados en la revisión final.

## Uso y mantenimiento

Tus compañeros pueden entrar al enlace, pulsar «Iniciar sesión» y después «Crea una cuenta». No necesitan XAMPP ni Visual Studio Code. Sus cuentas quedan en la base del alojamiento.

La copia local en XAMPP sigue siendo independiente. Las cuentas creadas antes en tu computadora no se copiaron: la publicación comienza con el catálogo de demostración y nuevas cuentas.

Para consultar los registros: panel InfinityFree → cuenta → MySQL Databases → phpMyAdmin → `users` → Examinar.

La configuración real se guarda únicamente en el servidor, dentro de `htdocs/_private/config.local.php`, protegida por `.htaccess` y una comprobación PHP. Los paquetes locales excluyen contraseñas y sesiones. Para volver a publicar conserva ese archivo; no lo reemplaces por la configuración de XAMPP.

No vuelvas a importar `importar-en-base-vacia.sql` en la base que ya está instalada. Para cambios futuros prepara migraciones y exporta una copia de seguridad desde phpMyAdmin.

Esta versión mantiene precios y existencias ficticios, pedidos de retiro y pago en efectivo pendiente, sin cobros en línea ni envío de correos. No tiene un panel para aprobar registros: las cuentas se crean directamente.
