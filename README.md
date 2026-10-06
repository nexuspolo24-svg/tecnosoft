# TecnoSoft Solutions

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
