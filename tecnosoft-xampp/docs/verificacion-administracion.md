# Verificación administrativa · 23 de octubre de 2026

PHP 8.1.25, MariaDB 10.4.32 y PHPMailer 7.1.1. Pruebas de negocio realizadas en una base aislada en el puerto 3317, con dos servidores PHP en 8173 y 8174 para los casos simultáneos.

## Resultados

- **49 comprobaciones funcionales aprobadas.**
- 83 casos de formato en PHP y los mismos 83 en JavaScript, con resultados coincidentes.
- Sintaxis PHP y JavaScript revisada.
- Instalación nueva: 30 tablas, 33 claves externas, 24 productos y cuatro servicios iniciales.
- Migración sobre una copia de la base anterior ejecutada dos veces sin perder registros; después aplicada a la base local de XAMPP con respaldo.
- Acceso web a app/config.local.php, app/mail.local.php, database/schema.sql y tools/admin.php: 404; acceso administrativo anónimo: 403.
- CHECK TABLE aprobado en las 30 tablas locales. Cuenta principal activada mediante la herramienta CLI, sin crear contraseñas automáticas.
- Antes y después de migrar se conservaron 2 usuarios, 24 productos, 1 pedido, 1 línea de pedido y 1 pago. La cuenta administradora se registró después de esa comparación.
- Navegador: acceso administrativo, alta de producto con stock cero, botón de compra deshabilitado, formulario de domicilio, costo de envío, compra en efectivo, historial y enlace de WhatsApp con productos. Sin errores de consola observados.
- Revisión de escritorio y de pantalla de 390 px. Sin desbordamiento horizontal del documento; tablas y navegación admiten desplazamiento dentro de sus contenedores.
- Carrito: al ocultarse un producto, recargar elimina la línea que dejó de estar disponible, sin dejar el pedido bloqueado.
- Instalación nueva y migración comparadas: las 173 columnas coinciden en nombre, tipo y nulabilidad.
- Modelo SVG/PNG inspeccionado visualmente; visor HTML, diccionario y Mermaid regenerados desde el esquema vigente.

## Casos funcionales

1. Catálogo público y servicios disponibles
2. El catálogo no expone costos
3. Administrador bloqueado sin sesión
4. Registro público no permite asignarse administrador
5. Permisos del administrador consultados en la base
6. SMTP oculta la contraseña
7. Alta de producto con costo, precio y stock
8. Imagen rechaza protocolos inseguros
9. Transferencia requiere correo confirmado
10. Entrega a domicilio exige dirección
11. Total se calcula en servidor con costo de envío
12. Reintento conserva un solo pedido
13. El reintento no descuenta stock dos veces
14. Cliente no accede a pedidos de otro usuario
15. WhatsApp incluye los productos del pedido
16. Se bloquea la compra por encima del stock
17. Un producto físico no admite entrega digital
18. Venta sin cobrar no puede completarse
19. Cancelar devuelve las existencias
20. Una segunda cancelación no duplica existencias
21. Edición antigua detecta conflictos de inventario
22. PHPMailer entrega por SMTP al destinatario correcto
23. La base guarda solo el hash del enlace
24. Reenvío de correo tiene límite de frecuencia
25. Confirmación funciona y el enlace es de un solo uso
26. Confirmar transferencia exige referencia
27. Pago recibido impide cancelación simple
28. Cliente ve el pago y estado actualizados
29. Venta presencial queda cobrada y completada
30. Cada venta conserva su costo original
31. Cambiar precio y costo no altera ventas anteriores
32. Reporte calcula margen con costos históricos
33. Consulta guardada y protegida contra reenvío inmediato
34. Servicios siguen el flujo de diagnóstico, cotización y entrega
35. Reporte rechaza fechas inexistentes
36. Mutaciones protegidas por CSRF
37. GET no ejecuta ventas
38. Revocar permisos surte efecto en la sesión existente
39. Dos servidores concurrentes no venden la última unidad dos veces
40. Stock nunca negativo después de la compra simultánea
41. Catálogo devuelve agotado después de la venta
42. La idempotencia se mantiene con sesiones y servidores simultáneos
43. Reintento simultáneo solo reserva una unidad
44. Carrito digital permite entrega digital
45. Enlace vencido se rechaza
46. Configuración SMTP exige cifrado TLS o SSL
47. Cuenta desactivada pierde acceso incluso con una sesión iniciada
48. SMTP desconectado produce error verificable
49. El enlace de un correo no enviado queda invalidado

## Correo real y límites de la verificación

PHPMailer entregó el mensaje de confirmación a un receptor SMTP de prueba local y se comprobó el enlace completo, su hash, el uso único, el vencimiento, los límites de envío y el fallo de transporte. No se desactivó la comprobación TLS de servidores reales.

Gmail real: el usuario completó la verificación en dos pasos, generó e introdujo personalmente una contraseña de aplicación y confirmó que recibió el correo de prueba. El envío SMTP real quedó comprobado. Después se envió el enlace de confirmación a su correo real, el usuario lo abrió y confirmó la dirección. Al actualizar Mi cuenta, la interfaz mostró «Correo confirmado». Flujo completo verificado.

Los mensajes de WhatsApp no se enviaron. No se hicieron transferencias, cobros reales, reembolsos ni publicaciones en el alojamiento. Las cuentas y pedidos sintéticos permanecen únicamente en la base de pruebas y no forman parte del SQL o del ZIP.

## Respaldo local

Se creó una exportación SQL con mysqldump --single-transaction, rutinas y triggers antes de aplicar la migración. El respaldo y su SHA-256 están en la carpeta privada de trabajo, fuera del ZIP compartible.


## Actualización automática del panel

Comprobación adicional en el entorno aislado: un cambio en el nombre de un cliente sintético apareció en Clientes sin recargar la página al transcurrir el intervalo de 15 segundos. El cambio se revirtió después de la prueba. También se comprobó la pausa con un diálogo de producto abierto, la conservación del borrador sin guardarlo y la reanudación después de cerrar el diálogo.
