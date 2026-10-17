<?php
declare(strict_types=1);
require_once __DIR__.'/../app/validation.php';
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
session_name('tecnosoft_session');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','path'=>'/']);
ini_set('session.use_strict_mode','1');
$sessionPath=__DIR__.'/../app/runtime/sessions';
if(!is_dir($sessionPath) && !mkdir($sessionPath,0700,true) && !is_dir($sessionPath)) { http_response_code(503); echo '{"error":"No se pudo crear el directorio privado de sesiones."}'; exit; }
session_save_path($sessionPath);
if(!session_start()) { http_response_code(503); echo '{"error":"No se pudo abrir la sesión. Revisa los permisos de app/runtime."}'; exit; }
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
function respond(array $data, int $status=200): never { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); exit; }
function fail(string $message, int $status=422): never { respond(['error'=>$message],$status); }
function field(array $data,string $name,int $max,bool $required=true): string {
    $v=$data[$name]??''; if(!is_string($v)) fail('Formato inválido: '.$name);
    if($error=tsTextError($v)) fail($error);
    $v=trim($v); if(($required && $v==='') || mb_strlen($v)>$max) fail('Revisa el campo '.$name.'.'); return $v;
}
function emailField(array $d): string { $v=mb_strtolower(field($d,'email',190)); if($error=tsEmailError($v)) fail($error); return $v; }
function nameField(array $d): string { $v=field($d,'name',100); if($error=tsNameError($v)) fail($error); return $v; }
function phoneField(array $d): string { $v=field($d,'phone',11); if($error=tsPhoneError($v)) fail($error); return $v; }
function requireUser(): int { if(empty($_SESSION['user']['id'])) fail('Inicia sesión para continuar.',401); return (int)$_SESSION['user']['id']; }
function run(PDO $db,string $sql,array $args=[]): PDOStatement { $q=$db->prepare($sql); $q->execute($args); return $q; }
$action=$_GET['action']??'catalog';
$method=$_SERVER['REQUEST_METHOD'];
$read=['session','catalog','orders'];
if(in_array($action,$read,true) && $method!=='GET') fail('Método no permitido.',405);
if(!in_array($action,$read,true) && $method!=='POST') fail('Método no permitido.',405);
$data=[];
if($method==='POST') {
    if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) fail('La sesión expiró. Recarga la página.',403);
    if((int)($_SERVER['CONTENT_LENGTH']??0)>32000) fail('Solicitud demasiado grande.',413);
    try { $data=json_decode(file_get_contents('php://input'),true,16,JSON_THROW_ON_ERROR); } catch(Throwable $e) { fail('JSON inválido.'); }
    if(!is_array($data)) fail('Solicitud inválida.');
}
if($action==='session') respond(['csrf'=>$_SESSION['csrf'],'user'=>$_SESSION['user']??null]);
if($action==='logout') { $_SESSION=[]; session_regenerate_id(true); $_SESSION['csrf']=bin2hex(random_bytes(32)); respond(['csrf'=>$_SESSION['csrf'],'user'=>null]); }
$config=__DIR__.'/../app/config.local.php';
if(!is_file($config)) fail('Falta configurar MySQL. Consulta README.md para instalar la base de datos.',503);
try {
    $c=require $config;
    $db=new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $db->exec("SET time_zone='+00:00'");
    if($action==='catalog') {
        $products=$db->query("SELECT p.id,p.name,p.description,p.price,p.stock,p.kind,c.name category,c.slug,(SELECT url FROM product_images i WHERE i.product_id=p.id ORDER BY sort_order LIMIT 1) image FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.id")->fetchAll();
        respond(['products'=>$products,'services'=>$db->query('SELECT id,name,description FROM services WHERE active=1 ORDER BY id')->fetchAll(),'currency'=>'NIO','demo_prices'=>true]);
    }
    if($action==='register'||$action==='login') {
        $email=emailField($data); $password=$data['password']??'';
        if(!is_string($password)) fail('Formato inválido: contraseña.');
        if($error=tsPasswordError($password,$action==='register')) fail($error);
        $name=$action==='register'?nameField($data):null;
        $ip=substr($_SERVER['REMOTE_ADDR']??'local',0,45);
        $attempts=run($db,'SELECT COUNT(*) FROM login_attempts WHERE (email=? OR ip=?) AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)',[$email,$ip])->fetchColumn();
        if((int)$attempts>=10) fail('Demasiados intentos. Espera 15 minutos.',429);
        run($db,'INSERT INTO login_attempts(email,ip) VALUES (?,?)',[$email,$ip]);
        if($action==='register') {
            $db->beginTransaction();
            try { run($db,'INSERT INTO users(name,email,password_hash) VALUES (?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]); }
            catch(PDOException $e) { if($e->getCode()==='23000') fail('Este correo ya está registrado.',409); throw $e; }
            $id=(int)$db->lastInsertId();
            run($db,"INSERT INTO user_roles(user_id,role_id) SELECT ?,id FROM roles WHERE name='customer'",[$id]);
            $db->commit(); $user=['id'=>$id,'name'=>$name,'email'=>$email];
        } else {
            $user=run($db,'SELECT id,name,email,password_hash,active FROM users WHERE email=?',[$email])->fetch();
            $dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
            if(!password_verify($password,$user['password_hash']??$dummy)||!$user||!$user['active']) fail('Correo o contraseña incorrectos.',401);
            unset($user['password_hash'],$user['active']);
        }
        session_regenerate_id(true); $_SESSION['user']=$user; $_SESSION['csrf']=bin2hex(random_bytes(32));
        respond(['user'=>$user,'csrf'=>$_SESSION['csrf']]);
    }
    if($action==='orders') {
        $uid=requireUser();
        $orders=run($db,'SELECT id,status,currency,total,created_at FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 100',[$uid])->fetchAll();
        foreach($orders as &$order) $order['items']=run($db,'SELECT product_name,quantity,unit_price,line_total FROM order_items WHERE order_id=?',[$order['id']])->fetchAll();
        respond(['orders'=>$orders,'requests'=>run($db,'SELECT r.id,s.name service,r.status,r.created_at FROM service_requests r JOIN services s ON s.id=r.service_id WHERE r.user_id=? ORDER BY r.id DESC LIMIT 100',[$uid])->fetchAll()]);
    }
    if($action==='checkout') {
        $uid=requireUser();
        $key=field($data,'request_key',36); if(!preg_match('/^[a-f0-9-]{36}$/D',$key)) fail('Clave de pedido inválida.');
        $existing=run($db,'SELECT id,total FROM orders WHERE user_id=? AND request_key=?',[$uid,$key])->fetch();
        if($existing) respond(['order'=>$existing,'repeated'=>true]);
        $phone=phoneField($data); $notes=field($data,'notes',1000,false);
        $items=$data['items']??null; if(!is_array($items)||count($items)<1||count($items)>50) fail('El carrito debe contener entre 1 y 50 productos.');
        $quantities=[];
        foreach($items as $item) {
            if(!is_array($item)||!is_int($item['id']??null)||!is_int($item['quantity']??null)||$item['id']<1||$item['quantity']<1||$item['quantity']>99||isset($quantities[$item['id']])) fail('Revisa las cantidades del carrito.');
            $quantities[$item['id']]=$item['quantity'];
        }
        ksort($quantities); $db->beginTransaction(); $lines=[]; $total=0;
        foreach($quantities as $id=>$quantity) {
            $p=run($db,'SELECT id,sku,name,price,stock,active FROM products WHERE id=? FOR UPDATE',[$id])->fetch();
            if(!$p||!$p['active']||(int)$p['stock']<$quantity) { $db->rollBack(); fail('Un producto no está disponible en la cantidad solicitada.'); }
            $cents=(int)round((float)$p['price']*100); $total+=$cents*$quantity;
            $lines[]=[$p,$quantity,$cents];
        }
        $amount=number_format($total/100,2,'.','');
        run($db,'INSERT INTO orders(user_id,request_key,subtotal,total,notes) VALUES (?,?,?,?,?)',[$uid,$key,$amount,$amount,$notes]);
        $orderId=(int)$db->lastInsertId();
        foreach($lines as [$p,$quantity,$cents]) {
            run($db,'INSERT INTO order_items(order_id,product_id,product_name,sku,quantity,unit_price,line_total) VALUES (?,?,?,?,?,?,?)',[$orderId,$p['id'],$p['name'],$p['sku'],$quantity,$p['price'],number_format($cents*$quantity/100,2,'.','')]);
            run($db,'UPDATE products SET stock=stock-? WHERE id=?',[$quantity,$p['id']]);
            run($db,'INSERT INTO inventory_movements(product_id,actor_id,quantity,reason) VALUES (?,?,?,?)',[$p['id'],$uid,-$quantity,'Pedido #'.$orderId]);
        }
        run($db,"INSERT INTO order_status_history(order_id,actor_id,status,note) VALUES (?,?,'pending','Retiro y pago en efectivo por confirmar')",[$orderId,$uid]);
        run($db,"INSERT INTO payments(order_id,method,amount) VALUES (?,'cash',?)",[$orderId,$amount]);
        run($db,'UPDATE users SET phone=? WHERE id=?',[$phone,$uid]);
        $db->commit(); respond(['order'=>['id'=>$orderId,'total'=>$amount]],201);
    }
    if($action==='contact') {
        if(field($data,'website',100,false)!=='') fail('Solicitud inválida.');
        if(time()-(int)($_SESSION['last_contact']??0)<30) fail('Espera unos segundos antes de enviar otra solicitud.',429);
        $name=nameField($data); $email=emailField($data); $phone=phoneField($data); $message=field($data,'message',5000);
        $uid=$_SESSION['user']['id']??null;
        if(!empty($data['service_id'])) {
            $service=filter_var($data['service_id'],FILTER_VALIDATE_INT); if(!$service||!run($db,'SELECT id FROM services WHERE id=? AND active=1',[$service])->fetchColumn()) fail('Servicio inválido.');
            $modality=field($data,'modality',10); if(!in_array($modality,['shop','remote','home'],true)) fail('Modalidad inválida.');
            $db->beginTransaction();
            run($db,'INSERT INTO service_requests(user_id,service_id,name,email,phone,description,modality) VALUES (?,?,?,?,?,?,?)',[$uid,$service,$name,$email,$phone,$message,$modality]);
            $id=(int)$db->lastInsertId();
            run($db,"INSERT INTO service_request_history(request_id,actor_id,status) VALUES (?,?,'new')",[$id,$uid]);
            $db->commit(); $type='Servicio';
        } else {
            run($db,"INSERT INTO contact_messages(user_id,name,email,phone,subject,message) VALUES (?,?,?,?,'Consulta de producto',?)",[$uid,$name,$email,$phone,$message]);
            $id=(int)$db->lastInsertId(); $type='Consulta';
        }
        $_SESSION['last_contact']=time(); respond(['id'=>$id,'type'=>$type],201);
    }
    fail('Recurso no encontrado.',404);
} catch(Throwable $e) {
    if(isset($db)&&$db->inTransaction()) $db->rollBack();
    error_log('TecnoSoft: '.$e->getMessage());
    fail('No se pudo completar la operación. Comprueba MySQL o inténtalo de nuevo.',503);
}
