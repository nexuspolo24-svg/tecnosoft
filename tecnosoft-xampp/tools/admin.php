<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$email=mb_strtolower(trim($argv[1]??'nexuspolo24@gmail.com'));
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Correo inválido.\n");exit(1);}
$c=require __DIR__.'/../app/config.local.php';
$db=new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$q=$db->prepare('SELECT id FROM users WHERE email=? AND active=1');$q->execute([$email]);$id=$q->fetchColumn();
if(!$id){fwrite(STDERR,"Primero crea la cuenta $email en la tienda y vuelve a ejecutar esta tarea. No se creó una contraseña automática.\n");exit(1);}
$q=$db->prepare("INSERT IGNORE INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='admin'");$q->execute([$id]);echo "Administrador activado para $email. Usa tu contraseña habitual.\n";
