# TecnoSoft Solutions

Primera versión local basada en el prototipo de Figma facilitado. PHP 8.1+, MariaDB 10.4+ (XAMPP) o MySQL 8. Moneda: **córdoba nicaragüense, NIO / C$**. Sin Composer ni npm.

## Incluye

- Diseño adaptable a móvil, catálogo, búsqueda, filtros, juegos, detalles y carrito.
- Registro, inicio y cierre de sesión reales con contraseñas almacenadas mediante hash con `password_hash`.
- Pedidos con cálculo en servidor, transacciones, control de stock, historial e idempotencia.
- Formulario de consultas y servicios con persistencia en MySQL; seguimiento desde la cuenta para solicitudes enviadas con sesión iniciada.
- Logo SVG/PNG e isotipo conservados del diseño de Figma; el logo usa tipografía de sistema para ser editable.
- 27 tablas, 31 relaciones, SQL de creación, datos de ejemplo, diagrama Mermaid, mapa SVG/PNG y diccionario de datos.

## Instalación en XAMPP

1. Descomprime la carpeta `tecnosoft` en una ubicación de tu elección. No es necesario copiarla a `htdocs` para usar la opción recomendada.
2. Abre XAMPP y pulsa **Start** en **MySQL**.
3. Copia `app/config.example.php` como `app/config.local.php`. Ajusta usuario, contraseña y puerto a tu instalación. Los valores vacíos de contraseña son solo para un entorno local de XAMPP.
4. Abre PowerShell en la carpeta `tecnosoft` y ejecuta:

```powershell
& 'C:\xampp\php\php.exe' .\tools\import.php
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t .\public
```

5. Abre **http://127.0.0.1:8080**. Crea una cuenta desde **Iniciar sesión → Crea una cuenta**. No hay usuarios ni contraseñas predeterminados.

La importación crea una base nueva llamada `tecnosoft`; si ya existe, se detiene sin alterarla. Si la creación falla a mitad de camino, revisa el error y las tablas creadas antes de volver a importar. Los DDL de MySQL no son una sola transacción.

Alternativa: inicia Apache y MySQL, abre phpMyAdmin y ejecuta `database/schema.sql` y luego `database/seed.sql`, en ese orden. Configura un VirtualHost de Apache cuyo **DocumentRoot sea la carpeta `public`**; las credenciales y el SQL deben permanecer fuera de la raíz pública. El servidor PHP anterior evita necesitar ese VirtualHost para comenzar.

## Abrir y trabajar en Visual Studio Code

1. Abre **Archivo → Abrir área de trabajo desde archivo** y selecciona `TecnoSoft.code-workspace`, dentro de la carpeta `tecnosoft`. Conserva el archivo junto al resto del proyecto.
2. En **Terminal → Ejecutar tarea**, selecciona **TecnoSoft: preparar configuración local**. Revisa `app/config.local.php` y enciende MySQL en XAMPP.
3. Para la primera instalación, ejecuta la tarea **TecnoSoft: importar base nueva**. Si ya la importaste, omite este paso; el importador no sobrescribe una base existente.
4. Ejecuta **TecnoSoft: iniciar web**, o pulsa **Ctrl+Shift+B**. Abre **http://127.0.0.1:8080**. Para detener el servidor, usa **Ctrl+C** en su terminal.

El área de trabajo abre todos los archivos editables de PHP, HTML, CSS, JavaScript, SQL y documentación. Usa PHP de `C:/xampp/php/php.exe`; si instalaste XAMPP en otra carpeta, ajusta esa ruta en el archivo de área de trabajo. No requiere extensiones adicionales. Las tareas de preparación conservan tu configuración local si ya existe.

## Validación de formularios

La versión local incluye validaciones en HTML y JavaScript, repetidas en PHP antes de guardar los datos. Se aplican al registro, inicio de sesión, consultas, solicitudes de servicio y teléfono de los pedidos.

- Correos: dirección completa con un solo `@`, dominio y extensión, por ejemplo `ana@gmail.com`, `ana@outlook.com` o `ana@empresa.com.ni`. Admite puntos y etiquetas con `+`; no se limita a proveedores de una lista.
- Teléfonos: solo dígitos ASCII, 8 dígitos nacionales o `505` seguido de 8 dígitos. Ejemplos: `88887777` y `50588887777`. No admite `+`, espacios, letras, guiones ni decimales. Se conserva como texto.
- Nombres: de 2 a 100 caracteres, al menos dos letras, con tildes y letras Unicode; admite espacios simples, apóstrofos y guiones entre palabras. Rechaza números, emojis y otros símbolos.
- Mensajes y notas: conservan la puntuación y los saltos de línea; rechazan caracteres de control no permitidos.
- Contraseñas: mantienen símbolos; en el registro se exigen 10 caracteres como mínimo y 72 bytes como máximo. No se recortan ni se modifican.

Los errores aparecen junto al campo. PHP rechaza también solicitudes enviadas directamente a la API. Esto valida el formato del correo; no comprueba que exista ni envía enlaces de confirmación.

Abre el proyecto local como antes y recarga con **Ctrl+F5** para cargar los archivos actualizados. No se requiere una migración ni volver a importar SQL. Detalles y pruebas en `docs/validaciones.md`.

## Base de datos y modelo

- `database/tecnosoft-completo.sql`: creación y datos de ejemplo en un único archivo para importar en phpMyAdmin. Usar en una instalación nueva.
- `database/schema.sql`: creación de las 27 tablas, claves externas, índices y validaciones.
- `database/seed.sql`: 24 productos, 4 servicios, categorías y roles. **Todos los precios y existencias son ficticios**; no son una conversión de los importes de Figma.
- `docs/modelo-relacional.html`: visor navegable con columnas y claves externas. Se abre directamente en el navegador.
- `docs/modelo-er.mmd`: diagrama completo en Mermaid, con atributos y relaciones.
- `docs/modelo-er.svg`: mapa visual de todas las tablas y sus relaciones.
- `docs/diccionario-datos.md`: tipos, claves, reglas de negocio y alcance de esta versión.

## Estructura

```text
app/          Configuración privada
public/       Única raíz de publicación web
  api.php     API PHP con PDO
  assets/     Estilos, JavaScript, logo e ilustración
database/     Creación y datos de ejemplo
docs/         Modelo, diccionario y resultados de verificación
tools/        Importador CLI de instalación nueva
```

## Alcance de esta primera versión

Los pedidos son solicitudes de **retiro en tienda y pago en efectivo pendiente de confirmación**. No se realizan cobros en línea ni se envían correos. En la demostración el envío y los impuestos adicionales son cero; hay que definir precios, política fiscal, entrega y facturación antes de operar.

El modelo incluye pagos, envíos parciales, direcciones, roles, favoritos y carrito persistente para ampliaciones. Sus pantallas administrativas y sus flujos todavía no están implementados. El carrito de esta versión permanece en el navegador y se valida contra MySQL al enviar el pedido. Las licencias digitales no se entregan automáticamente. No incluye recuperación de contraseña ni verificación de correo.

Las fotografías ilustrativas proceden de las mismas referencias Unsplash del prototipo y requieren conexión a internet; existe una imagen SVG local de respaldo. Confirma fotos y modelos exactos antes de vender. Los contactos y horarios se dejaron pendientes para evitar publicar los números de ejemplo de Figma.

La contraseña se guarda como hash; las consultas usan parámetros PDO, las sesiones usan cookies HttpOnly y los formularios usan CSRF. El servicio se enlaza solo a `127.0.0.1` para desarrollo. Para publicar se necesita HTTPS, usuario MySQL con permisos limitados, límites de solicitudes del servidor, copias de seguridad, información comercial definitiva y los flujos pendientes de operación.
