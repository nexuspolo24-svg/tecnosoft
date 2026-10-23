<?php
declare(strict_types=1);
define('TECNOSOFT_INTERNAL',true);
require_once __DIR__.'/_private/bootstrap.php';
$action=$_GET['action']??'catalog';if(!is_string($action))fail('Recurso inválido.',404);
$method=$_SERVER['REQUEST_METHOD'];$read=['session','catalog','orders','admin_data'];
if(in_array($action,$read,true)&&$method!=='GET'||!in_array($action,$read,true)&&$method!=='POST')fail('Método no permitido.',405);
$data=[];
if($method==='POST'){
 if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))fail('La sesión expiró. Recarga la página.',403);
 if((int)($_SERVER['CONTENT_LENGTH']??0)>32000)fail('Solicitud demasiado grande.',413);
 try{$data=json_decode(file_get_contents('php://input'),true,16,JSON_THROW_ON_ERROR);}catch(Throwable $e){fail('JSON inválido.');}
 if(!is_array($data))fail('Solicitud inválida.');
}
if($action==='logout'){$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));respond(['csrf'=>$_SESSION['csrf'],'user'=>null]);}
try{
 $db=db();$user=currentUser($db);
 if($action==='session')respond(['csrf'=>$_SESSION['csrf'],'user'=>$user]);
 require_once __DIR__.'/_private/sales.php';require_once __DIR__.'/_private/mail.php';
 if(str_starts_with($action,'admin_'))require __DIR__.'/_private/admin.php';
 if($action==='catalog'){
  $products=$db->query("SELECT p.id,p.name,p.description,p.price,p.stock,p.kind,c.name category,c.slug,(SELECT url FROM product_images i WHERE i.product_id=p.id ORDER BY sort_order LIMIT 1) image FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.id")->fetchAll();
  $s=settings($db);
  respond(['products'=>$products,'services'=>$db->query('SELECT id,name,description FROM services WHERE active=1 ORDER BY id')->fetchAll(),'currency'=>'NIO','demo_prices'=>true,'settings'=>array_intersect_key($s,array_flip(['whatsapp','pickup_point','delivery_fee','transfer_instructions']))]);
 }
 if($action==='register'||$action==='login'){
  $email=emailField($data);$password=$data['password']??'';if(!is_string($password))fail('Contraseña inválida.');if($error=tsPasswordError($password,$action==='register'))fail($error);
  $name=$action==='register'?nameField($data):null;$ip=substr($_SERVER['REMOTE_ADDR']??'local',0,45);
  $attempts=run($db,'SELECT COUNT(*) FROM login_attempts WHERE (email=? OR ip=?) AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)',[$email,$ip])->fetchColumn();
  if((int)$attempts>=10)fail('Demasiados intentos. Espera 15 minutos.',429);
  run($db,'INSERT INTO login_attempts(email,ip) VALUES (?,?)',[$email,$ip]);
  if($action==='register'){
   $db->beginTransaction();
   try{run($db,'INSERT INTO users(name,email,password_hash) VALUES (?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);}catch(PDOException $e){if($e->getCode()==='23000')fail('Este correo ya está registrado.',409);throw $e;}
   $id=(int)$db->lastInsertId();run($db,"INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='customer'",[$id]);$db->commit();
  }else{
   $row=run($db,'SELECT id,password_hash,active FROM users WHERE email=?',[$email])->fetch();
   $dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
   if(!password_verify($password,$row['password_hash']??$dummy)||!$row||!$row['active'])fail('Correo o contraseña incorrectos.',401);$id=(int)$row['id'];
  }
  session_regenerate_id(true);$_SESSION['user']=['id'=>$id];$_SESSION['csrf']=bin2hex(random_bytes(32));respond(['user'=>currentUser($db),'csrf'=>$_SESSION['csrf']]);
 }
 if($action==='orders'){
  $uid=requireUser();$orders=run($db,"SELECT o.id,o.status,o.currency,o.total,o.shipping_total,o.delivery_method,o.created_at,p.method payment_method,p.status payment_status FROM orders o LEFT JOIN payments p ON p.id=(SELECT MAX(id) FROM payments WHERE order_id=o.id) WHERE o.user_id=? AND o.sales_channel='web' ORDER BY o.id DESC LIMIT 100",[$uid])->fetchAll();
  $s=settings($db);
  foreach($orders as &$order){$order['items']=run($db,'SELECT product_name,quantity,unit_price,line_total FROM order_items WHERE order_id=?',[$order['id']])->fetchAll();$order['whatsapp_url']=orderWhatsapp($db,$s,(int)$order['id']);}
  respond(['orders'=>$orders,'user'=>$user,'requests'=>run($db,'SELECT r.id,s.name service,r.status,r.created_at,r.quoted_total FROM service_requests r JOIN services s ON s.id=r.service_id WHERE r.user_id=? ORDER BY r.id DESC LIMIT 100',[$uid])->fetchAll()]);
 }
 if($action==='checkout')respond(createOrder($db,$data),201);
 if($action==='request_verification')requestVerification($db);
 if($action==='verify_email')verifyEmail($db,$data);
 if($action==='contact'){
  if(field($data,'website',100,false)!=='')fail('Solicitud inválida.');
  if(time()-(int)($_SESSION['last_contact']??0)<30)fail('Espera unos segundos antes de enviar otra solicitud.',429);
  $name=nameField($data);$email=emailField($data);$phone=phoneField($data);$message=field($data,'message',5000);$uid=$user['id']??null;
  if(!empty($data['service_id'])){
   $service=intField($data,'service_id');if(!run($db,'SELECT id FROM services WHERE id=? AND active=1',[$service])->fetchColumn())fail('Servicio inválido.');
   $modality=field($data,'modality',10);if(!in_array($modality,['shop','remote','home'],true))fail('Modalidad inválida.');
   $db->beginTransaction();run($db,'INSERT INTO service_requests(user_id,service_id,name,email,phone,description,modality) VALUES (?,?,?,?,?,?,?)',[$uid,$service,$name,$email,$phone,$message,$modality]);$id=(int)$db->lastInsertId();run($db,"INSERT INTO service_request_history(request_id,actor_id,status) VALUES (?,?,'new')",[$id,$uid]);$db->commit();$type='Servicio';
  }else{run($db,"INSERT INTO contact_messages(user_id,name,email,phone,subject,message) VALUES (?,?,?,?,'Consulta de producto',?)",[$uid,$name,$email,$phone,$message]);$id=(int)$db->lastInsertId();$type='Consulta';}
  $_SESSION['last_contact']=time();respond(['id'=>$id,'type'=>$type,'whatsapp_url'=>whatsapp(settings($db),'Hola, soy '.$name.'. '.$type.' #'.$id.': '.$message)],201);
 }
 fail('Recurso no encontrado.',404);
}catch(Throwable $e){
 if(isset($db)&&$db->inTransaction())$db->rollBack();
 error_log('TecnoSoft: '.$e->getMessage());
 if($e instanceof PDOException&&$e->getCode()==='23000')fail('El código SKU o la referencia de pago ya existe. Revisa los datos.',409);
 fail('No se pudo completar la operación. Comprueba MySQL y ejecuta la tarea de actualizar base de datos si instalaste esta versión.',503);
}
