# Correcciones de registro y permisos del administrador

Fecha: 26 de octubre de 2026. Pruebas realizadas en la copia aislada del proyecto, puerto web 8176 y base tecnosoft_reglas_20261026 de MariaDB en 3317. No se modificó la base de uso de XAMPP ni se enviaron correos.

## Comportamiento

- Registro: contraseñas nuevas sin espacios, tabulaciones, saltos de línea ni separadores Unicode. Se rechazan con un mensaje por campo y con respuesta 422 en la API. No se eliminan caracteres ni se recorta la contraseña.
- Acceso: se conserva la comparación exacta de las claves ya registradas, incluidas las antiguas que tenían espacios; no se reescriben hashes.
- Administrador: no se muestra carrito ni controles para agregar productos. El catálogo y los detalles ofrecen Gestionar. Se cierra el carrito si se entra con un rol administrativo.
- Servidor: requireCustomer bloquea checkout con 403 para cuentas admin, incluso si conservan también el rol customer o envían un rol falso desde el navegador.
- La venta presencial a clientes permanece disponible como una operación de gestión; no es una compra personal del administrador.
- No se requieren columnas nuevas ni migración. La adaptación de alojamiento está actualizada como preparación, sin desplegar.

## Resultados

- 20 grupos de comprobaciones de validadores, API, permisos y conservación de datos aprobados.
- 28 casos adicionales de contraseña en PHP y los mismos 28 en JavaScript; resultados y mensajes coincidentes.
- 22 intentos de registro con espacios o separadores rechazados sin insertar cuentas.
- Suite previa de 83 casos de formato aprobada en PHP y en JavaScript.
- Administrador bloqueado antes de crear pedidos, líneas, pagos, movimientos o descuentos de stock.
- Cliente con compra válida, pago pendiente y descuento de una unidad.
- Venta presencial del administrador a un cliente guardada y completada.
- Cambio de rol detectado en una sesión ya iniciada; retiro del rol devuelve los permisos de cliente.
- Cuenta anterior con espacios inicia sesión usando su clave exacta. Quitar el espacio no permite entrar.

## Revisión del navegador

Se comprobó el error junto al campo de contraseña y su limpieza al introducir una clave válida. Cambiar entre registro y acceso ajusta también la restricción HTML. Se inició sesión con una cuenta sintética administradora: cabecera sin carrito, catálogo sin botones de compra y detalle con enlace Gestionar. La restricción persistió después de recargar.

Se probó el cambio de visitante con carrito a administrador y después a cliente: el carrito queda inaccesible para el administrador y vuelve a estar disponible para el cliente. Sin errores ni advertencias de consola durante la comprobación.

## Actualizar la aplicación

Inicia el proyecto con la tarea habitual y recarga el navegador con Ctrl+F5. No vuelvas a importar SQL para estas correcciones.
