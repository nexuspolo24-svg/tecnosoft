# TecnoSoft Solutions

Tienda y administración para una demostración académica de tecnología y juegos para PC. PHP 8.1+, MariaDB 10.4+ de XAMPP o MySQL 8. Moneda: **córdoba nicaragüense (NIO / C$)**. Incluye la biblioteca PHPMailer; no requiere Composer ni npm.

## Abrir el proyecto que ya está instalado

1. Enciende **MySQL** desde XAMPP.
2. Abre **TecnoSoft.code-workspace** en Visual Studio Code.
3. Ejecuta **Terminal → Ejecutar tarea → TecnoSoft: iniciar web**, o **Ctrl+Shift+B**.
4. Entra a [la tienda local](http://127.0.0.1:8080/) y accede con tu cuenta.
5. Una cuenta administradora verá **Administración** en la cabecera. También puede entrar a [el panel](http://127.0.0.1:8080/admin.php).

Para este servidor PHP no hace falta iniciar Apache. Para cerrar: **Ctrl+C** en la terminal del servidor y **Stop** en MySQL de XAMPP. Los registros permanecen guardados.

## Actualizar una instalación anterior

**No vuelvas a importar la instalación nueva sobre una base existente.** Primero guarda una exportación SQL de la base y una copia de tus archivos privados de configuración.

Con MySQL encendido, ejecuta la tarea **TecnoSoft: actualizar base de datos**, o desde la carpeta del proyecto:

    C:/xampp/php/php.exe tools/migrate.php

La migración agrega columnas y tablas para administración, correo, costos, auditoría y recuperación de contraseña. Conserva usuarios, productos y pedidos. Se puede repetir: comprueba las columnas existentes y registra la versión. Los cambios de estructura de MySQL no se pueden deshacer como una sola transacción; conserva el respaldo.

Después de actualizar recarga el navegador con **Ctrl+F5**.

## Instalar en otra computadora

1. Descomprime la carpeta completa.
2. Inicia MySQL de XAMPP.
3. Ejecuta **TecnoSoft: preparar configuración local**. Se crea app/config.local.php si no existía.
4. Revisa servidor, puerto, base, usuario y contraseña en ese archivo.
5. Para una instalación vacía con la base tecnosoft, ejecuta **TecnoSoft: importar base nueva**.
6. Ejecuta **TecnoSoft: iniciar web**.

Como alternativa importa **database/tecnosoft-completo.sql** en phpMyAdmin, en una instalación nueva. El archivo contiene las 31 tablas y los datos de demostración. No incluye cuentas personales, pedidos anteriores ni contraseñas.

Si necesitas trasladar tus registros, exporta la base original con phpMyAdmin e impórtala en el equipo de destino en vez de importar el catálogo nuevo. Los archivos de la página y los registros de MySQL se comparten por separado.

## Activar al administrador

Primero crea una cuenta desde la tienda con el correo **nexuspolo24@gmail.com** y tu propia contraseña. Ejecuta **TecnoSoft: activar administrador**, o:

    C:/xampp/php/php.exe tools/admin.php nexuspolo24@gmail.com

El comando solo concede el rol a una cuenta existente y activa. No crea contraseñas y no existe una clave predeterminada. Para otro administrador usa su correo como argumento. La activación solo está disponible por terminal, fuera de la web. Recarga la tienda después.

## Funciones implementadas

- Catálogo adaptable a móvil, categorías, juegos, búsqueda, presupuesto, orden y detalles.
- Cuenta de cliente, registro, acceso, salida, recuperación de contraseña por correo y seguimiento de sus pedidos y servicios.
- Carrito guardado en el navegador, precios calculados en servidor y reintentos sin duplicar pedidos.
- Efectivo o transferencia; domicilio, retiro acordado o entrega digital de carritos digitales.
- Correo confirmado obligatorio antes de pedir por transferencia; pago recibido registrado por el administrador después de revisarlo.
- Enlaces de WhatsApp con número de pedido, productos y total, o con el texto de la consulta. El cliente decide abrir y enviar el mensaje.
- Productos nuevos, edición de precio, costo, imagen HTTPS, stock y visibilidad. Los productos sin stock no permiten agregar unidades.
- Pedidos con estados e historial; cancelación de pedidos sin cobrar devuelve stock una sola vez.
- Ventas presenciales cobradas en efectivo y entregadas.
- Clientes, consultas, diagnóstico, cotización y seguimiento de servicios.
- Reportes por fecha de pago en horario de Nicaragua, ventas por día, productos más vendidos, margen bruto conocido, bajo stock y exportación CSV.
- Configuración de atención, costo de envío y correo SMTP; movimientos de inventario y auditoría administrativa.
- Actualización automática del panel cada 15 segundos en resumen, pedidos, clientes, productos y actividad. Se pausa al editar, abrir un diálogo o trabajar en ventas, servicios o configuración.

El administrador conserva las facultades de superusuario del demo. El registro público siempre crea clientes; nunca concede permisos de administración. Las cuentas administradoras gestionan la tienda: no tienen carrito ni pueden hacer pedidos web; sí pueden registrar ventas presenciales a clientes desde el panel.

## Correo real SMTP

Entra a **Administración → Configuración → Correo real · SMTP**.

Para Gmail: servidor **smtp.gmail.com**, puerto **587**, seguridad **TLS**, usuario y remitente de tu cuenta. Introduce una **contraseña de aplicación**, guarda y pulsa **Enviar prueba a mi correo**. Google requiere verificación en dos pasos para crear contraseñas de aplicación: [instrucciones oficiales](https://support.google.com/mail/answer/185833?hl=es). No uses tu contraseña habitual de Gmail.

La dirección base local es **http://127.0.0.1:8080**. Debe corresponder al servidor que abre el enlace. Un enlace local se utiliza en esa misma computadora; al publicar se cambia por la dirección HTTPS de la tienda.

Las credenciales se guardan en **app/mail.local.php**, fuera de public, excluido del ZIP y de Git. La API no devuelve la contraseña guardada. El campo vacío la conserva. PHPMailer verifica los certificados del servidor SMTP.

Para probar el flujo completo:

1. Inicia sesión como cliente y entra a **Mi cuenta**.
2. Pulsa **Enviar confirmación de correo**.
3. Abre tu correo, sigue el enlace y pulsa **Confirmar mi correo**.
4. Vuelve a Mi cuenta y pulsa **Actualizar confirmación**.
5. Ya puedes elegir transferencia. El pedido seguirá pendiente de pago hasta la revisión del administrador.

El enlace vence a los 30 minutos, se utiliza una vez y la base guarda su hash. Se admite un envío por minuto y hasta diez al día por usuario. Si SMTP no está configurado o falla, la aplicación informa el error; no simula correos enviados.

## Si olvidaste tu contraseña

1. En la tienda, pulsa **Iniciar sesión → Olvidé mi contraseña**.
2. Escribe el correo de tu cuenta y pulsa **Enviar enlace de recuperación**.
3. Revisa tu correo y spam. Abre el enlace en la misma computadora si trabajas con localhost.
4. Escribe dos veces la nueva contraseña: mínimo 10 caracteres, máximo 72 bytes, sin espacios.
5. Pulsa **Guardar nueva contraseña** e inicia sesión de nuevo.

El enlace vence en 30 minutos y funciona una vez. Solicitar otro invalida el anterior. Se cierra el acceso de las sesiones anteriores al restablecer la contraseña. El correo debe coincidir con una cuenta activa y SMTP debe estar configurado; la respuesta pública no revela si una cuenta existe. Se espera un minuto entre solicitudes, hasta diez diarias por correo y veinte por hora desde una conexión.

Después de instalar esta ampliación tendrás que iniciar sesión otra vez. No se cambia tu contraseña por actualizar la base. Consulta docs/recuperacion-contrasena.md para las reglas y las pruebas.

## Base y modelo

- database/schema.sql: estructura nueva de **31 tablas y 34 claves externas**.
- database/seed.sql: 24 productos, cuatro servicios, categorías y roles de ejemplo.
- database/recuperacion.sql: enlaces de recuperación y límites de envío; tools/migrate.php agrega también users.password_version.
- database/administracion.sql: tablas y valores complementarios de la actualización; para bases existentes se ejecuta mediante tools/migrate.php.
- docs/modelo-relacional.html: visor de todas las columnas, claves, índices y relaciones.
- docs/modelo-er.svg y modelo-er.png: mapa de relaciones.
- docs/modelo-er.mmd: atributos y cardinalidades en Mermaid.
- docs/diccionario-datos.md: tipos y restricciones.
- docs/reglas-administracion.md: reglas de funcionamiento y límites.
- docs/verificacion-administracion.md: resultados de las pruebas de esta versión.

## Alcance académico

Precios y existencias iniciales son ficticios. No hay cobros bancarios automáticos, facturación fiscal, reembolsos ni descarga automática de juegos o licencias. Las entregas digitales se coordinan después del pago. Los pedidos pendientes reservan stock hasta que el administrador los atiende o cancela; no hay vencimiento automático de reservas.

El costo histórico de las ventas anteriores es desconocido y permanece vacío. El reporte muestra las unidades sin costo y calcula margen solo para unidades con costo registrado. El margen no equivale a utilidad neta: no resta gastos, impuestos ni costos del transporte. No se aplica un impuesto adicional en este demo.

Los listados administrativos muestran los últimos 200 pedidos, clientes, consultas y servicios, hasta 1,000 productos y las últimas 50 acciones o movimientos. Los reportes consultan todos los pagos del período seleccionado, hasta 366 días.

Las fotos del catálogo requieren Internet y tienen una imagen local de respaldo. El servidor de desarrollo escucha solo en 127.0.0.1. Si usas Apache, su DocumentRoot debe ser public; app y database deben quedar fuera de la raíz publicada.

Esta versión local se prepara por separado del sitio de InfinityFree y del repositorio de GitHub. Compartir el ZIP no actualiza automáticamente ninguno de ellos.

## Correcciones del 26 de octubre de 2026

- El registro requiere contraseñas de al menos 10 caracteres, hasta 72 bytes y ningún espacio o separador. Se rechazan, no se eliminan automáticamente.
- El administrador no ve el carrito ni botones para comprar. Checkout también rechaza su cuenta, incluso si conserva el rol customer.
- Se conserva el acceso con contraseñas previamente creadas. Esta ampliación sí requiere migrar la base para agregar password_version y password_resets.
- Recarga con Ctrl+F5 después de iniciar la web para cargar los archivos nuevos.

Los detalles del producto se cierran con un clic en el fondo exterior, con la X o con Escape. Pulsar dentro o agregar al carrito conserva el panel abierto. Los formularios de cuenta y compra mantienen su cierre explícito.
