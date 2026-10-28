<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$c=require __DIR__.'/../app/config.local.php';
$db=new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(!(int)$db->query("SELECT GET_LOCK('tecnosoft_migration',10)")->fetchColumn())throw new RuntimeException('Otra actualización está ejecutándose.');
try{
$db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(80) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
$changes=[['users','password_version','INT UNSIGNED NOT NULL DEFAULT 1'],['users','email_verified_at','DATETIME NULL'],['products','cost_price','DECIMAL(12,2) NULL CHECK(cost_price>=0)'],['products','revision','INT UNSIGNED NOT NULL DEFAULT 1'],['order_items','unit_cost','DECIMAL(12,2) NULL CHECK(unit_cost>=0)'],['orders','sales_channel',"ENUM('web','store') NOT NULL DEFAULT 'web'"],['orders','customer_name','VARCHAR(100) NULL'],['orders','customer_phone','VARCHAR(25) NULL']];
foreach($changes as [$t,$col,$definition]){$q=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$q->execute([$t,$col]);if(!$q->fetchColumn())$db->exec("ALTER TABLE `$t` ADD COLUMN `$col` $definition");}
$db->exec("ALTER TABLE orders MODIFY delivery_method ENUM('pickup','delivery','digital') NOT NULL DEFAULT 'pickup'");
$db->exec(file_get_contents(__DIR__.'/../database/administracion.sql'));
$db->exec("INSERT IGNORE INTO schema_migrations(version) VALUES ('2026-10-administracion')");
$db->exec(file_get_contents(__DIR__.'/../database/recuperacion.sql'));
$db->exec("INSERT IGNORE INTO schema_migrations(version) VALUES ('2026-10-recuperacion')");
echo "Actualización completada: recuperación por correo disponible; se conservaron usuarios, productos y pedidos.\n";
}finally{$db->query("SELECT RELEASE_LOCK('tecnosoft_migration')");}
