<?php
// Solo CLI. Prepara la configuración sin sustituir un archivo existente.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$target = __DIR__ . '/../app/config.local.php';
if (is_file($target)) {
    echo "La configuración local ya existe; se conservó.\n";
    exit;
}
if (!copy(__DIR__ . '/../app/config.example.php', $target)) {
    fwrite(STDERR, "No se pudo crear app/config.local.php. Revisa los permisos.\n");
    exit(1);
}
echo "Configuración creada. Revisa usuario, contraseña y puerto en app/config.local.php antes de importar.\n";
