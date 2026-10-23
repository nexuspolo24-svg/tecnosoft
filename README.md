# TecnoSoft Solutions

## Versión actual: PHP y XAMPP · 23 de octubre de 2026

El proyecto académico actual está en **[tecnosoft-xampp](tecnosoft-xampp/)**. Incluye tienda de tecnología y juegos, cuentas de cliente y administración, inventario, pedidos, ventas presenciales y reportes en córdobas nicaragüenses.

- [Cómo abrir, instalar o actualizar la aplicación](tecnosoft-xampp/README.md).
- [Área de trabajo de Visual Studio Code](tecnosoft-xampp/TecnoSoft.code-workspace).
- [Reglas administrativas y límites del demo](tecnosoft-xampp/docs/reglas-administracion.md).
- [Validaciones de correos, teléfonos, nombres y formularios](tecnosoft-xampp/docs/validaciones.md).
- [Base de datos: 30 tablas, columnas y relaciones](tecnosoft-xampp/docs/modelo-relacional.html).
- [Diagrama de relaciones](tecnosoft-xampp/docs/modelo-er.png).
- [Resultados de las pruebas](tecnosoft-xampp/docs/verificacion-administracion.md).
- [Preparación de InfinityFree, pendiente de publicar](tecnosoft-infinityfree/PUBLICACION.md).

### Avances de esta versión

- Administración de productos, precios, costos, existencias y visibilidad.
- Pedidos en efectivo o transferencia; domicilio, retiro o coordinación digital.
- Confirmación de correo por SMTP real antes de pedir por transferencia.
- Gestión de pagos y estados, cancelación sin doble devolución de stock y ventas presenciales.
- Clientes, consultas, servicios con diagnóstico, cotización y seguimiento.
- Reportes por fecha de pago, margen bruto conocido, exportación CSV y auditoría.
- Actualización del panel cada 15 segundos, con pausa durante la edición.
- Validaciones en navegador y servidor, permisos por rol, sesiones, CSRF y contraseñas protegidas mediante hash.

### Abrir en tu computadora

Enciende MySQL en XAMPP y abre tecnosoft-xampp/TecnoSoft.code-workspace. Usa **TecnoSoft: iniciar web** y entra a http://127.0.0.1:8080/. Sigue el README de esa carpeta para una instalación nueva o para configurar tu propia cuenta administradora.

**Si ya tienes una base con registros**, respáldala y usa **TecnoSoft: actualizar base de datos**. No importes la instalación vacía encima.

GitHub contiene código, modelo y catálogo de demostración. Las contraseñas de base y SMTP, sesiones, respaldos y registros personales no se incluyen; cada instalación genera su configuración privada. Esta subida no despliega la actualización en el alojamiento ni sincroniza la base de XAMPP.

---

## Documentación de la versión anterior con .NET y SQL Server


Prototipo web de TecnoSoft Solutions basado en la versión 14 del Figma Make compartido. La portada conserva la paleta crema, verde oscuro y verde lima, y la pantalla de acceso sigue el diseño bicolor del prototipo.

## Avance funcional

- Registro de usuarios en SQL Server con correo único.
- Inicio y cierre de sesión mediante cookie HTTP-only de ASP.NET Core.
- Contraseñas con sal aleatoria y PBKDF2-SHA256 (310 000 iteraciones); no se guardan contraseñas en texto claro.
- Consultas SQL parametrizadas, validación del lado del servidor y protección antifalsificación en los formularios.
- Portada adaptable con muestra visual de catálogo, servicios y contacto. El carrito y la compra aún no tienen lógica comercial.

## Requisitos

- .NET SDK 9.
- SQL Server local o remoto y permisos para crear la base de datos y consultar `dbo.Users`.
- Visual Studio Code con extensión C# para depuración; las tareas de compilación y ejecución también funcionan desde la terminal integrada.

## Iniciar en Visual Studio Code

1. Abre Visual Studio Code tú mismo desde el menú Inicio de Windows y usa **Archivo → Abrir carpeta** para abrir esta carpeta `TecnoSoftSolutions`. Así las tareas se ejecutan con tu cuenta Windows, que puede tener acceso a SQL Server.
2. En el menú superior elige **Terminal → Run Task → Primera ejecución (base de datos + sitio)**. Esa tarea crea la base de datos y arranca la aplicación.
3. Abre `http://localhost:5219` en tu navegador y selecciona **Iniciar sesión → Regístrate gratis** para crear el primer usuario.

Si ya creaste la base de datos, usa **Terminal → Run Task → Ejecutar TecnoSoft** las siguientes veces. Para detener el sitio, haz clic en la terminal de VS Code y pulsa `Ctrl+C`.

La configuración inicial de `ConnectionStrings:TecnoSoft` en `appsettings.json` apunta a `localhost`, a la base `TecnoSoftSolutions` y usa autenticación integrada de Windows. Para otra instancia o autenticación SQL, configura la variable de entorno `ConnectionStrings__TecnoSoft` sin guardar contraseñas en el repositorio.

## Estructura principal

- `Controllers/AccountController.cs`: registro, acceso y salida.
- `Data/UserRepository.cs`: acceso parametrizado a SQL Server.
- `Security/PasswordService.cs`: derivación y comprobación de contraseñas.
- `database/01-create-database.sql`: esquema idempotente de la base de datos.
- `Views/Account/`: formularios de acceso y registro.

La creación de la base de datos requiere ejecutarse con un usuario SQL Server autorizado. Si la conexión falla, los formularios muestran un error controlado y el servidor registra el detalle técnico.
