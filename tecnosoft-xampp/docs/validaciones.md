# Validaciones de la versión local

Actualización del 17 de octubre de 2026. Aplicada al proyecto local; esta actualización no se publicó en InfinityFree.

## Reglas

| Campo | Acepta | Rechaza |
| --- | --- | --- |
| Correo | `ana@gmail.com`, `ana.lopez+tienda@outlook.com`, `ana@empresa.com.ni` | `ana@gmail`, `ana@@gmail.com`, espacios, puntos consecutivos en la parte local, dominios incompletos |
| Teléfono | `88887777`, `22556677`, `50588887777` | Letras, signos, espacios, longitudes distintas de 8 u 11 y prefijos de 11 dígitos distintos de `505` |
| Nombre | `José María`, `María-José`, `O'Connor`, `D’Ávila` | Números, emojis, etiquetas, símbolos no indicados, separadores al inicio o al final, espacios consecutivos y menos de dos letras |
| Mensaje y notas | Texto, puntuación, tildes, tabulaciones y saltos de línea | Caracteres nulos y otros controles invisibles |
| Contraseña | Letras, números y símbolos, sin modificar el texto | En registro menos de 10 caracteres, más de 72 bytes y caracteres nulos |

El formato del teléfono se eligió para Nicaragua. No se comprueba que el número esté asignado a una línea real. Se conserva como cadena para no perder ceros iniciales.

El correo admite dominios públicos, empresariales y educativos. Esta función comprueba el formato, sin consultar DNS ni enviar mensajes. Confirmar la propiedad de un correo requiere implementar un flujo de verificación por enlace.

## Archivos

- `app/validation.php`: reglas del servidor, reutilizables sin conectarse a MySQL.
- `public/assets/validation.js`: reglas del navegador.
- `public/assets/app.js`: mensajes por campo, revisión antes del envío, limpieza de errores y manejo del campo de nombre al cambiar de registro a inicio de sesión.
- `public/index.php`: restricciones HTML, ejemplos y teclado numérico en teléfonos.
- `public/api.php`: aplica validaciones del servidor antes de insertar registros o procesar pedidos.

Las contraseñas no se normalizan. Los demás campos se recortan en los extremos, y los correos se guardan en minúsculas como en la versión anterior.

## Verificación

- 83 casos de formato comprobados en PHP y los mismos 83 en JavaScript, con resultados coincidentes.
- 21 comprobaciones previas de registro, acceso, CSRF, catálogo, pedidos, stock, transacciones, reintentos e historial pasaron en una base de prueba aislada.
- 22 comprobaciones adicionales de API pasaron: correos y nombres inválidos, teléfonos con letras/signos, teléfono JSON numérico, contraseña larga en bytes, caracteres nulos y datos válidos con tildes.
- Navegador: errores de nombre y correo visibles, corrección de errores al introducir valores válidos, teléfono rechazado/aceptado y campo de nombre desactivado al volver a inicio de sesión. Sin errores de consola observados.

Las pruebas utilizaron una copia del proyecto y una base de datos aislada en el puerto 3317; no modificaron la base local de uso ni la base publicada.

## Abrir la actualización

Abre `TecnoSoft.code-workspace`, enciende MySQL en XAMPP y ejecuta la tarea `TecnoSoft: iniciar web`. Visita `http://127.0.0.1:8080` y recarga con Ctrl+F5. Si el servidor ya estaba iniciado, basta con recargar.

No vuelvas a importar el SQL: las tablas existentes sirven para esta actualización. El ZIP conserva la estructura del proyecto y excluye la configuración privada y las sesiones.
