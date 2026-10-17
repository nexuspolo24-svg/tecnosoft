# Verificación · 10 de octubre de 2026

Probado con los binarios PHP 8.1.25 y MariaDB 10.4.32 de XAMPP en una instancia aislada dentro de la carpeta de trabajo. No se modificó la base de datos habitual de XAMPP.

## Resultados

- Importación correcta de la creación y los datos de ejemplo: 27 tablas, 31 restricciones de clave externa y 24 productos.
- Sintaxis de todos los archivos PHP y JavaScript: correcta.
- 21 comprobaciones HTTP: catálogo, acceso sin sesión, CSRF, registro, correo duplicado, contraseña incorrecta, inicio de sesión, rechazo de exceso de stock, rollback de stock, pedido persistido, total calculado en servidor, descuento de stock, idempotencia entre sesiones, reintento sin doble descuento, historial, servicio persistido, servicio ligado al usuario, límite de repetición, consulta persistida, aislamiento de pedidos y cierre de sesión.
- Revisión de interfaz: filtro Software, agregar al carrito, login real y pedido completo hasta el historial de cuenta.
- Revisión visual de escritorio y móvil a 390 px; sin desbordamiento horizontal. Se corrigió el recorte del logo en móvil.
- Consola del navegador sin errores al finalizar la revisión.
- Visor del modelo: 31 conexiones, selección de orders resaltó sus 6 relaciones.
- Logo rasterizado desde el SVG con fondo transparente; modelo SVG/PNG inspeccionado visualmente.

## Límites

La prueba usa datos sintéticos y no ejecuta pagos ni envía mensajes. No se verificó un despliegue público ni una configuración Apache de producción. Las funciones modeladas para administración, envíos, pagos externos y favoritos requieren implementación posterior. Las imágenes son ilustrativas y los precios son de ejemplo en NIO.

Las cuentas y pedidos de prueba no se incluyen en el SQL entregado. El ZIP excluye la configuración local de pruebas y los archivos de sesión.
