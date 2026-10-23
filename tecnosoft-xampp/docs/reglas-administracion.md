# Reglas de negocio · Versión administrativa

## Usuarios y permisos

El registro exige nombre válido, correo completo y contraseña de 10 caracteres como mínimo, hasta 72 bytes. Se conserva el hash de la contraseña. Correos únicos, sesiones HttpOnly y SameSite=Lax, renovación de sesión al iniciar o salir y tokens CSRF para operaciones de escritura.

Un cliente solo consulta sus pedidos y sus solicitudes asociadas a su cuenta. La API y la página administrativa consultan en MySQL la actividad y el rol de la cuenta. Desactivar una cuenta o retirar su rol afecta la sesión ya iniciada. El superusuario se activa mediante tools/admin.php por terminal.

## Validación

Correo con dominio y extensión: Gmail, Outlook, cuentas empresariales y otros dominios válidos; no se usa una lista exclusiva de proveedores. El formato válido no demuestra la titularidad: esta se verifica con un enlace enviado por SMTP.

Teléfonos de ocho dígitos ASCII o 505 seguido de ocho dígitos. Sin espacios, signos o letras. Nombres personales con letras Unicode y tildes, espacios simples, apóstrofos y guiones entre palabras. Los nombres de productos admiten números y puntuación.

Importes no negativos con hasta dos decimales. Cantidades enteras entre 1 y 99 por producto, hasta 50 productos distintos por pedido. Las imágenes administrativas usan HTTPS o una ilustración local. Los mensajes se muestran escapados como texto.

## Inventario y precios

Se calculan totales con los precios de MySQL, nunca con precios enviados por el navegador. Los productos se bloquean durante la venta para impedir vender dos veces la misma unidad. Los pedidos web reservan existencias al registrarse.

Cada venta conserva nombre, SKU, precio, costo y cantidades en sus líneas. El costo desconocido se conserva como nulo. Editar el producto no modifica las líneas históricas. La edición de producto comprueba una revisión para evitar sobrescribir una venta o un ajuste que ocurrió mientras el formulario estaba abierto.

Ocultar un producto lo retira del catálogo sin borrar su historial. Stock cero significa agotado. Toda reserva, cancelación o ajuste registra un movimiento. Un pedido pendiente no caduca automáticamente: el administrador lo atiende o cancela.

## Compra, entrega y pago

El cliente elige efectivo o transferencia. Para transferencia debe confirmar primero el correo. Confirmar un correo nunca confirma el pago.

Retiro: punto acordado en configuración. Domicilio: departamento, ciudad y dirección obligatorios; el servidor suma la tarifa fija configurada. Entrega digital: solo si todos los productos son digitales, coordinada manualmente. El envío y los impuestos adicionales de un pedido antiguo permanecen iguales aunque cambie la configuración.

El pedido inicia pendiente y con pago pendiente. El administrador revisa el efectivo o la transferencia y registra el pago. Una transferencia requiere referencia única. El importe del pago corresponde al total del pedido.

Estados: pendiente → confirmado → en preparación → enviado → completado. Se permite confirmado → completado y preparación → completado para retiro o entrega directa. Enviado se permite solo para domicilio. Enviar o completar exige pago recibido.

La cancelación está disponible para pedidos sin cobrar y no completados. Cambia el estado, anula el pago pendiente y devuelve cada unidad una sola vez. Los pedidos pagados se bloquean para evitar simular reembolsos que esta demo no procesa.

Una venta presencial es una operación administrativa en efectivo, pagada y completada. Usa los mismos bloqueos, cálculo de precios, snapshots y movimientos del pedido web. El administrador queda como responsable; el nombre y teléfono del comprador se guardan en la venta.

## Correo y WhatsApp

Confirmación por SMTP real con PHPMailer. Enlace aleatorio de 32 bytes, hash SHA-256 almacenado, vencimiento de 30 minutos y uso único. Enviar otro enlace invalida los anteriores. Frecuencia de un minuto y límite de diez al día por cuenta. Un fallo SMTP invalida el enlace del envío fallido.

Las credenciales permanecen en app/mail.local.php, fuera de la raíz pública. La API solo indica si una contraseña está guardada. El botón de prueba envía únicamente al correo de la cuenta administradora en sesión.

WhatsApp inicial: 50587266171. Se generan enlaces con el pedido y sus productos o con la consulta registrada. El usuario abre WhatsApp y pulsa enviar; la aplicación no manda mensajes por su cuenta.

## Servicios y atención

Servicios: nuevo → diagnóstico → cotizado → aprobado → en curso → completado. Cotizar requiere importe. Los estados no terminales admiten cancelación. Cada cambio guarda historial y actor.

Consultas: nueva, leída o resuelta. Se guarda el contacto, el mensaje y la asociación al usuario cuando inició sesión al enviarlo. Servicios y consultas limitan el reenvío inmediato a 30 segundos por sesión.

## Reportes y trazabilidad

Período de hasta 366 días, fechas válidas y horario America/Managua. Los datos de MySQL se guardan en UTC. Se incluyen pagos recibidos en el período y se excluyen pedidos cancelados.

Se muestran cantidad de ventas, total cobrado incluyendo envío, margen bruto conocido, unidades sin costo, ventas por día y productos más vendidos. El margen suma (precio de venta − costo histórico) × cantidad únicamente en líneas con costo conocido. No es utilidad neta.

El CSV identifica período y moneda y neutraliza celdas que podrían interpretarse como fórmulas. Auditoría guarda actor, acción y registro afectado. La interfaz muestra las últimas 50 acciones y movimientos; los registros completos permanecen en MySQL.

## Límites acordados

Demostración académica sin cobros reales automáticos, facturas fiscales, recuperación de contraseña, reembolsos, entrega automática de archivos o reservas con vencimiento automático. La configuración SMTP privada debe completarse para usar correo real. El proceso local no actualiza el alojamiento público.
