<?php
declare(strict_types=1);
require_once __DIR__.'/validation.php';
ini_set('display_errors','0');
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: same-origin');
session_name('tecnosoft_session');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','path'=>'/']);
ini_set('session.use_strict_mode','1');$sessionPath=__DIR__.'/runtime/sessions';
if(!is_dir($sessionPath)&&!mkdir($sessionPath,0700,true)&&!is_dir($sessionPath))throw new RuntimeException('No se pudo crear el directorio privado.');
session_save_path($sessionPath);session_start();$_SESSION['csrf']??=bin2hex(random_bytes(32));
function respond(array $data,int $status=200): never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
function fail(string $message,int $status=422): never {if(isset($GLOBALS['db'])&&$GLOBALS['db']->inTransaction())$GLOBALS['db']->rollBack();respond(['error'=>$message],$status);}
function run(PDO $db,string $sql,array $args=[]): PDOStatement {$q=$db->prepare($sql);$q->execute($args);return $q;}
function field(array $d,string $name,int $max,bool $required=true): string {$s=$d[$name]??'';if(!is_string($s))fail('Formato inválido: '.$name);if($e=tsTextError($s))fail($e);$s=trim($s);if(($required&&$s==='')||mb_strlen($s)>$max)fail('Revisa el campo '.$name.'.');return $s;}
function emailField(array $d): string {$s=mb_strtolower(field($d,'email',190));if($e=tsEmailError($s))fail($e);return $s;}
function nameField(array $d): string {$s=field($d,'name',100);if($e=tsNameError($s))fail($e);return $s;}
function phoneField(array $d): string {$s=field($d,'phone',11);if($e=tsPhoneError($s))fail($e);return $s;}
function intField(array $d,string $key,int $min=1,int $max=2147483647): int {$v=$d[$key]??null;if(!(is_int($v)||(is_string($v)&&preg_match('/^[0-9]+$/D',$v)))||$v<$min||$v>$max)fail('Revisa el campo '.$key.'.');return(int)$v;}
function cents(mixed $v): int {if(!(is_string($v)||is_int($v))||!preg_match('/^[0-9]{1,9}(?:\\.[0-9]{1,2})?$/D',(string)$v))fail('Importe inválido: usa hasta dos decimales y valores positivos.');return(int)round((float)$v*100);}
function decimal(int $c): string {return number_format($c/100,2,'.','');}
function db(): PDO {$p=__DIR__.'/config.local.php';if(!is_file($p))throw new RuntimeException('Falta config.local.php');$c=require $p;$db=new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);$db->exec("SET time_zone='+00:00'");return $db;}
function currentUser(PDO $db): ?array {$id=$_SESSION['user']['id']??null;if(!$id)return null;$u=run($db,"SELECT id,name,email,phone,email_verified_at,EXISTS(SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=users.id AND r.name='admin') is_admin FROM users WHERE id=? AND active=1",[$id])->fetch();if(!$u){unset($_SESSION['user']);return null;}$u['is_admin']=(bool)$u['is_admin'];$_SESSION['user']=$u;return $u;}
function requireUser(): int {if(empty($_SESSION['user']['id']))fail('Inicia sesión para continuar.',401);return(int)$_SESSION['user']['id'];}
function requireAdmin(): int {$id=requireUser();if(empty($_SESSION['user']['is_admin']))fail('Esta operación requiere una cuenta administradora.',403);return $id;}
function settings(PDO $db): array {return array_column($db->query('SELECT name,value FROM settings')->fetchAll(),'value','name');}
function audit(PDO $db,int $actor,string $action,string $entity,int $id,array $detail=[]): void {run($db,'INSERT INTO audit_log(actor_id,action,entity,entity_id,details) VALUES (?,?,?,?,?)',[$actor,$action,$entity,$id,json_encode($detail,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);}
function whatsapp(array $s,string $text): string {return 'https://wa.me/'.($s['whatsapp']??'50587266171').'?text='.rawurlencode($text);}

function orderWhatsapp(PDO $db,array $s,int $id): string {
 $o=run($db,'SELECT total,delivery_method FROM orders WHERE id=?',[$id])->fetch();
 $lines=run($db,'SELECT product_name,quantity FROM order_items WHERE order_id=?',[$id])->fetchAll();
 $types=['pickup'=>'Retiro','delivery'=>'Domicilio','digital'=>'Digital'];
 $text="Hola, quiero coordinar el pedido #".$id." de TecnoSoft.\n";
 foreach($lines as $i)$text.=$i['quantity']." × ".$i['product_name']."\n";
 $text.="Total: C$ ".$o['total'].". Entrega: ".$types[$o['delivery_method']].".";
 return whatsapp($s,$text);
}
