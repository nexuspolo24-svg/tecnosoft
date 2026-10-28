# Recuperación de contraseña y comodidad del catálogo

Actualización del 26 de octubre de 2026. La base vigente contiene **31 tablas y 34 claves externas**. Los informes anteriores describen sus fechas respectivas.

## Uso

Iniciar sesión → Olvidé mi contraseña → escribir correo → Enviar enlace de recuperación. Abrir el mensaje y elegir una contraseña nueva, confirmarla e iniciar sesión. Con localhost se abre el enlace en esta misma computadora. La dirección configurada en SMTP debe ser la dirección real de la tienda. El SMTP existente se conserva y no hay que crear otra contraseña de aplicación.

El enlace vence a los treinta minutos y se utiliza una vez. Otro envío invalida los anteriores. Nueva contraseña: mínimo diez caracteres, máximo 72 bytes y ningún espacio o separador. No se envía la contraseña por correo ni se muestra la anterior.

Para cerrar los detalles de un producto pulsa en el fondo fuera del panel, la X o Escape. Agregar sigue conservando los productos en el carrito.

## Estructura y actualización

- app/password_reset.php: solicitud y cambio protegido por token.
- public/reset-password.php y assets/reset-password.js: formulario independiente.
- database/recuperacion.sql: nueva tabla password_resets para token, vencimiento y límites.
- users.password_version: invalida sesiones anteriores al cambio.
- tools/migrate.php: actualización repetible de bases existentes. Primero se exporta la base; no se importa la instalación nueva sobre tus registros.
- Credenciales privadas de MySQL y SMTP conservadas fuera de public y fuera de los paquetes.

La base local original se respaldó y actualizó. Se verificaron cantidades de usuarios, productos, pedidos, líneas y pagos, además de los hashes de contraseñas: sin cambios. La migración se probó dos veces sobre una base sintética anterior conservando los registros.

## Verificación

Se ejecutaron 32 comprobaciones HTTP, MySQL y SMTP en una base separada y un buzón SMTP local de pruebas, sin enviar mensajes reales ni cambiar contraseñas personales:

- SMTP entrega un enlace de recuperaciÃ³n al destinatario correcto.
- Solicitar recuperaciÃ³n no cambia la contraseÃ±a.
- Solo se guarda el hash del token y vence en treinta minutos.
- Una cuenta desconocida recibe la misma respuesta sin enviar correo.
- El correo debe tener un formato vÃ¡lido.
- La solicitud de recuperaciÃ³n requiere CSRF.
- GET no envÃ­a correos ni modifica contraseÃ±as.
- La segunda solicitud inmediata tiene lÃ­mite de frecuencia.
- La contraseÃ±a nueva rechaza espacios tambiÃ©n en recuperaciÃ³n.
- Se exige confirmar la misma contraseÃ±a.
- La contraseÃ±a nueva conserva los lÃ­mites de longitud.
- Un token aleatorio no permite cambiar una cuenta.
- Cambiar la contraseÃ±a exige CSRF.
- Un enlace vigente permite restablecer la contraseÃ±a.
- Se cambia el hash y la versiÃ³n sin alterar la confirmaciÃ³n del correo.
- Se invalidan ambas sesiones anteriores del cliente.
- El enlace utilizado no se puede repetir.
- La contraseÃ±a antigua falla y la nueva permite acceder.
- Un enlace vencido se rechaza.
- Un enlace nuevo invalida el enlace anterior.
- El enlace mÃ¡s reciente sigue funcionando.
- Dos servidores no pueden consumir el mismo enlace dos veces.
- Se cierra la sesiÃ³n administrativa anterior.
- La recuperaciÃ³n conserva el rol y las funciones administrativas.
- El administrador recuperado sigue sin permiso para comprar.
- Una cuenta desactivada no recibe enlace ni revela su estado.
- SMTP sin configurar informa la misma indisponibilidad para cualquier correo.
- El fallo de entrega no revela la existencia de la cuenta.
- Un correo no entregado deja su enlace invalidado.
- Se aplica el mÃ¡ximo de diez solicitudes diarias por correo.
- Se aplica el mÃ¡ximo de veinte solicitudes por hora desde una conexiÃ³n.
- El esquema nuevo contiene treinta y una tablas y treinta y cuatro claves externas.

Comprobación visual: el enlace Olvidé mi contraseña abre el formulario con el correo escrito; el envío muestra la respuesta genérica; volver a iniciar sesión funciona. En el catálogo, agregar conserva el panel y el carrito, y un clic exterior cierra los detalles.

Los límites y el tratamiento de cuentas se documentan en reglas-administracion.md. Esta actualización local no publica el sitio ni sube cambios a GitHub.
