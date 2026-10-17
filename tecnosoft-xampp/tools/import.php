<?php
// Solo CLI: php tools/import.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$path = __DIR__ . '/../app/config.local.php';
if (!is_file($path)) { fwrite(STDERR, "Copia app/config.example.php a app/config.local.php y configura MySQL.\n"); exit(1); }
$c = require $path;
try {
    $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};charset=utf8mb4", $c['user'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $exists = $pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='tecnosoft'")->fetchColumn();
    if ($exists) { throw new RuntimeException('La base tecnosoft ya existe. No se modificó. Importa manualmente solo si conoces su contenido.'); }
    $pdo->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
    $pdo->exec(file_get_contents(__DIR__.'/../database/seed.sql'));
    echo "Base tecnosoft creada con datos de ejemplo.\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage()."\n"); exit(1); }
