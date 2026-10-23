<?php
if(!defined('TECNOSOFT_INTERNAL')){http_response_code(404);exit;}
$actor=requireAdmin();
if($action==='admin_data'){
 $today=new DateTimeImmutable('now',new DateTimeZone('America/Managua'));$from=$_GET['from']??$today->format('Y-m-01');$to=$_GET['to']??$today->format('Y-m-d');
 $tz=new DateTimeZone('America/Managua');
 $a=is_string($from)?DateTimeImmutable::createFromFormat('!Y-m-d',$from,$tz):false;$b=is_string($to)?DateTimeImmutable::createFromFormat('!Y-m-d',$to,$tz):false;
 if(!$a||!$b||$a->format('Y-m-d')!==$from||$b->format('Y-m-d')!==$to||$a>$b||$a->diff($b)->days>366)fail('Usa un rango válido de hasta 366 días.');
 $start=$a->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$end=$b->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
 $condition=" FROM orders o JOIN payments p ON p.order_id=o.id WHERE p.status='paid' AND o.status<>'cancelled' AND p.paid_at>=? AND p.paid_at<?";
 $report=run($db,"SELECT COUNT(*) sales,COALESCE(SUM(o.total),0) revenue,COALESCE(SUM(o.shipping_total),0) shipping".$condition,[$start,$end])->fetch();
 $cost=run($db,"SELECT COALESCE(SUM(CASE WHEN i.unit_cost IS NOT NULL THEN (i.unit_price-i.unit_cost)*i.quantity ELSE 0 END),0) known_margin,COALESCE(SUM(CASE WHEN i.unit_cost IS NULL THEN i.quantity ELSE 0 END),0) units_without_cost FROM order_items i JOIN orders o ON o.id=i.order_id JOIN payments p ON p.order_id=o.id WHERE p.status='paid' AND o.status<>'cancelled' AND p.paid_at>=? AND p.paid_at<?",[$start,$end])->fetch();
 $top=run($db,"SELECT i.product_name,SUM(i.quantity) quantity,SUM(i.line_total) revenue FROM order_items i JOIN orders o ON o.id=i.order_id JOIN payments p ON p.order_id=o.id WHERE p.status='paid' AND o.status<>'cancelled' AND p.paid_at>=? AND p.paid_at<? GROUP BY i.product_id,i.product_name ORDER BY quantity DESC LIMIT 10",[$start,$end])->fetchAll();
 $daily=run($db,"SELECT DATE(DATE_SUB(p.paid_at,INTERVAL 6 HOUR)) day,COUNT(*) sales,SUM(o.total) revenue".$condition." GROUP BY day ORDER BY day",[$start,$end])->fetchAll();
 $orders=$db->query("SELECT o.*,u.email,p.method payment_method,p.status payment_status,p.provider_reference,p.paid_at FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN payments p ON p.id=(SELECT MAX(p2.id) FROM payments p2 WHERE p2.order_id=o.id) ORDER BY o.id DESC LIMIT 200")->fetchAll();
 foreach($orders as &$o){$o['items']=run($db,'SELECT product_name,quantity,unit_price,line_total FROM order_items WHERE order_id=?',[$o['id']])->fetchAll();$o['address']=run($db,"SELECT recipient,phone,department,city,address_line FROM order_addresses WHERE order_id=? AND type='shipping'",[$o['id']])->fetch()?:null;}
 unset($o);
 respond(['report'=>$report+$cost,'top'=>$top,'daily'=>$daily,'orders'=>$orders,
 'products'=>$db->query("SELECT p.*,c.name category,(SELECT url FROM product_images i WHERE i.product_id=p.id ORDER BY sort_order LIMIT 1) image FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC LIMIT 1000")->fetchAll(),
 'categories'=>$db->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(),
 'customers'=>$db->query("SELECT u.id,u.name,u.email,u.phone,u.active,u.email_verified_at,u.created_at,(SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id AND o.sales_channel='web') orders FROM users u ORDER BY u.id DESC LIMIT 200")->fetchAll(),
 'requests'=>$db->query('SELECT r.*,s.name service FROM service_requests r JOIN services s ON s.id=r.service_id ORDER BY r.id DESC LIMIT 200')->fetchAll(),
 'messages'=>$db->query('SELECT id,name,email,phone,subject,message,status,created_at FROM contact_messages ORDER BY id DESC LIMIT 200')->fetchAll(),
 'movements'=>$db->query('SELECT m.*,p.name product FROM inventory_movements m JOIN products p ON p.id=m.product_id ORDER BY m.id DESC LIMIT 50')->fetchAll(),
 'audit'=>$db->query('SELECT a.id,a.action,a.entity,a.entity_id,a.created_at,u.email actor FROM audit_log a JOIN users u ON u.id=a.actor_id ORDER BY a.id DESC LIMIT 50')->fetchAll(),
 'settings'=>settings($db),'smtp'=>mailPublicConfig()]);
}
if($action==='admin_product'){
 $id=intField($data,'id',0);$name=field($data,'name',160);$sku=field($data,'sku',60);$desc=field($data,'description',5000);
 if(!preg_match('/^[a-zA-Z0-9_-]+$/D',$sku))fail('El código SKU admite letras, números, guiones y guion bajo.');
 $cat=intField($data,'category_id');if(!run($db,'SELECT id FROM categories WHERE id=?',[$cat])->fetchColumn())fail('Categoría inválida.');
 $kind=field($data,'kind',20);if(!in_array($kind,['physical','digital'],true))fail('Tipo inválido.');
 $price=decimal(cents($data['price']??null));$cost=decimal(cents($data['cost_price']??null));$stock=intField($data,'stock',0,1000000);$active=intField($data,'active',0,1);$image=field($data,'image',700,false);
 if($image!==''&&$image!=='assets/producto.svg'&&(!filter_var($image,FILTER_VALIDATE_URL)||strtolower(parse_url($image,PHP_URL_SCHEME)??'')!=='https'))fail('La imagen debe ser una URL HTTPS o dejarse vacía.');
 $db->beginTransaction();$old=null;
 if($id){$revision=intField($data,'revision');$old=run($db,'SELECT * FROM products WHERE id=? FOR UPDATE',[$id])->fetch();if(!$old)fail('Producto no encontrado.',404);if((int)$old['revision']!==$revision)fail('El producto cambió mientras lo editabas. Actualiza la lista y vuelve a abrirlo.',409);
 run($db,'UPDATE products SET name=?,sku=?,description=?,category_id=?,kind=?,price=?,cost_price=?,stock=?,active=?,revision=revision+1 WHERE id=?',[$name,$sku,$desc,$cat,$kind,$price,$cost,$stock,$active,$id]);}
 else{run($db,'INSERT INTO products(name,sku,description,category_id,kind,price,cost_price,stock,active) VALUES (?,?,?,?,?,?,?,?,?)',[$name,$sku,$desc,$cat,$kind,$price,$cost,$stock,$active]);$id=(int)$db->lastInsertId();}
 $delta=$stock-(int)($old['stock']??0);if($delta)run($db,'INSERT INTO inventory_movements(product_id,actor_id,quantity,reason) VALUES (?,?,?,?)',[$id,$actor,$delta,$old?'Ajuste administrativo':'Existencia inicial']);
 run($db,'DELETE FROM product_images WHERE product_id=? AND sort_order=0',[$id]);
 if($image!=='')run($db,'INSERT INTO product_images(product_id,url,alt,sort_order) VALUES (?,?,?,0)',[$id,$image,$name]);
 audit($db,$actor,$old?'product.update':'product.create','product',$id,['stock_before'=>$old['stock']??0,'stock_after'=>$stock,'price'=>$price,'cost'=>$cost,'active'=>$active]);$db->commit();respond(['message'=>'Producto guardado.','id'=>$id]);
}
if($action==='admin_order'){changeOrder($db,$data);respond(['message'=>'Pedido actualizado.']);}
if($action==='admin_sale')respond(createOrder($db,$data,true),201);
if($action==='admin_settings'){
 $wa=field($data,'whatsapp',15);if(!preg_match('/^505[0-9]{8}$/D',$wa))fail('WhatsApp: escribe 505 y los ocho dígitos.');
 $s=['whatsapp'=>$wa,'pickup_point'=>field($data,'pickup_point',300),'delivery_fee'=>decimal(cents($data['delivery_fee']??null)),'transfer_instructions'=>field($data,'transfer_instructions',1500)];
 $db->beginTransaction();foreach($s as $key=>$value)run($db,'INSERT INTO settings(name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$key,$value]);audit($db,$actor,'settings.update','settings',0);$db->commit();respond(['message'=>'Configuración guardada.']);
}
if($action==='admin_smtp'){saveMailConfig($data);audit($db,$actor,'smtp.configure','settings',0);respond(['message'=>'SMTP guardado. Envía una prueba para comprobar la conexión.']);}
if($action==='admin_smtp_test'){
 if(time()-(int)($_SESSION['last_mail_test']??0)<60)fail('Espera un minuto entre pruebas.',429);$_SESSION['last_mail_test']=time();
 try{sendMail($_SESSION['user']['email'],'Prueba SMTP · TecnoSoft Solutions','El envío SMTP está funcionando. Este mensaje no confirma tu correo: utiliza Mi cuenta para confirmarlo.');}
 catch(Throwable $e){fail('Falló la prueba. Revisa servidor, puerto, seguridad y contraseña de aplicación.',503);}
 respond(['message'=>'Prueba enviada a tu correo de administrador. Revisa la bandeja de entrada.']);
}
if($action==='admin_request'){
 $id=intField($data,'id');$status=field($data,'status',30);$note=field($data,'note',500,false);$quoted=field($data,'quoted_total',20,false);$cost=$quoted===''?null:decimal(cents($quoted));
 $flow=['new'=>['diagnosing','cancelled'],'diagnosing'=>['quoted','cancelled'],'quoted'=>['approved','cancelled'],'approved'=>['in_progress','cancelled'],'in_progress'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]];
 $db->beginTransaction();$old=run($db,'SELECT status FROM service_requests WHERE id=? FOR UPDATE',[$id])->fetchColumn();if(!$old)fail('Solicitud no encontrada.',404);if(!in_array($status,$flow[$old],true))fail('Cambio de estado no permitido.');if($status==='quoted'&&$cost===null)fail('Indica el importe cotizado.');
 run($db,'UPDATE service_requests SET status=?,quoted_total=COALESCE(?,quoted_total) WHERE id=?',[$status,$cost,$id]);run($db,'INSERT INTO service_request_history(request_id,actor_id,status,note) VALUES (?,?,?,?)',[$id,$actor,$status,$note]);audit($db,$actor,'service.status','service_request',$id,['status'=>$status]);$db->commit();respond(['message'=>'Servicio actualizado.']);
}
if($action==='admin_message'){
 $id=intField($data,'id');$status=field($data,'status',20);if(!in_array($status,['read','resolved'],true))fail('Estado inválido.');
 run($db,'UPDATE contact_messages SET status=? WHERE id=?',[$status,$id]);audit($db,$actor,'message.status','message',$id,['status'=>$status]);respond(['message'=>'Consulta actualizada.']);
}
fail('Recurso no encontrado.',404);
