<?php
if(!defined('TECNOSOFT_INTERNAL')){http_response_code(404);exit;}
function createOrder(PDO $db,array $d,bool $store=false): array {
 $uid=$store?requireAdmin():requireUser();$key=field($d,'request_key',36);
 if(!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/D',$key))fail('Clave de pedido inválida.');
 $db->beginTransaction();
 // El bloqueo por comprador también serializa el reintento de una misma clave.
 $buyer=run($db,'SELECT * FROM users WHERE id=? AND active=1 FOR UPDATE',[$uid])->fetch();if(!$buyer)fail('Cuenta no disponible.',401);
 $old=run($db,'SELECT id,total FROM orders WHERE user_id=? AND request_key=?',[$uid,$key])->fetch();
 if($old){$db->commit();return ['order'=>$old,'repeated'=>true];}
 $phone=phoneField($d);$name=$store?nameField($d):$buyer['name'];$notes=field($d,'notes',1000,false);
 $method=$store?'cash':field($d,'payment_method',20);
 if(!in_array($method,['cash','transfer'],true))fail('Selecciona efectivo o transferencia.');
 if($method==='transfer'&&!$buyer['email_verified_at'])fail('Confirma tu correo desde Mi cuenta antes de elegir transferencia.');
 $delivery=$store?'pickup':field($d,'delivery_method',20);
 if(!in_array($delivery,['pickup','delivery','digital'],true))fail('Selecciona la forma de entrega.');
 $address=null;
 if($delivery==='delivery')$address=[field($d,'department',80),field($d,'city',80),field($d,'address_line',500)];
 $items=$d['items']??null;if(!is_array($items)||count($items)<1||count($items)>50)fail('El carrito debe contener entre 1 y 50 productos.');
 $quantities=[];
 foreach($items as $i){if(!is_array($i))fail('Producto inválido.');$id=intField($i,'id');$n=intField($i,'quantity',1,99);if(isset($quantities[$id]))fail('Producto repetido.');$quantities[$id]=$n;}
 ksort($quantities);$lines=[];$subtotal=0;$allDigital=true;
 foreach($quantities as $id=>$n){$p=run($db,'SELECT * FROM products WHERE id=? FOR UPDATE',[$id])->fetch();if(!$p||!$p['active']||(int)$p['stock']<$n)fail('Un producto no está disponible en la cantidad solicitada.');$subtotal+=cents($p['price'])*$n;$lines[]=[$p,$n];$allDigital=$allDigital&&$p['kind']==='digital';}
 if($delivery==='digital'&&!$allDigital)fail('La entrega digital solo está disponible para productos digitales.');
 $s=settings($db);$fee=$delivery==='delivery'?cents($s['delivery_fee']??'0.00'):0;$total=$subtotal+$fee;
 if($total>999999999999)fail('El pedido supera el importe permitido.');
 $status=$store?'completed':'pending';
 run($db,'INSERT INTO orders(user_id,request_key,status,subtotal,shipping_total,total,delivery_method,notes,sales_channel,customer_name,customer_phone) VALUES (?,?,?,?,?,?,?,?,?,?,?)',[$uid,$key,$status,decimal($subtotal),decimal($fee),decimal($total),$delivery,$notes,$store?'store':'web',$name,$phone]);
 $oid=(int)$db->lastInsertId();
 foreach($lines as [$p,$n]){
  run($db,'INSERT INTO order_items(order_id,product_id,product_name,sku,quantity,unit_price,unit_cost,line_total) VALUES (?,?,?,?,?,?,?,?)',[$oid,$p['id'],$p['name'],$p['sku'],$n,$p['price'],$p['cost_price'],decimal(cents($p['price'])*$n)]);
  run($db,'UPDATE products SET stock=stock-?,revision=revision+1 WHERE id=?',[$n,$p['id']]);
  run($db,'INSERT INTO inventory_movements(product_id,actor_id,quantity,reason) VALUES (?,?,?,?)',[$p['id'],$uid,-$n,'Venta #'.$oid]);
 }
 if($address)run($db,"INSERT INTO order_addresses(order_id,type,recipient,phone,department,city,address_line) VALUES (?,'shipping',?,?,?,?,?)",[$oid,$name,$phone,...$address]);
 run($db,'INSERT INTO order_status_history(order_id,actor_id,status,note) VALUES (?,?,?,?)',[$oid,$uid,$status,$store?'Venta presencial en efectivo':'Pedido web; pago pendiente de revisión']);
 run($db,'INSERT INTO payments(order_id,method,status,amount,paid_at) VALUES (?,?,?,?,?)',[$oid,$method,$store?'paid':'pending',decimal($total),$store?gmdate('Y-m-d H:i:s'):null]);
 if(!$store)run($db,'UPDATE users SET phone=? WHERE id=?',[$phone,$uid]);
 if($store)audit($db,$uid,'sale.create','order',$oid);
 $db->commit();
 return ['order'=>['id'=>$oid,'total'=>decimal($total)],'whatsapp_url'=>orderWhatsapp($db,$s,$oid)];
}
function changeOrder(PDO $db,array $d): void {
 $actor=requireAdmin();$id=intField($d,'id');$operation=field($d,'operation',30);$note=field($d,'note',500,false);
 $db->beginTransaction();$o=run($db,'SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();if(!$o)fail('Pedido no encontrado.',404);
 $pay=run($db,'SELECT * FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1 FOR UPDATE',[$id])->fetch();if(!$pay)fail('Este pedido no tiene un pago registrado.');
 if($o['status']==='cancelled')fail('El pedido ya está cancelado.');
 if($operation==='pay'){
  if($pay['status']!=='pending')fail('El pago ya fue revisado.');
  $ref=field($d,'reference',190,$pay['method']==='transfer');
  if($pay['method']==='transfer'&&!run($db,'SELECT email_verified_at FROM users WHERE id=?',[$o['user_id']])->fetchColumn())fail('El comprador todavía no confirmó su correo.');
  run($db,"UPDATE payments SET status='paid',provider_reference=?,paid_at=UTC_TIMESTAMP() WHERE id=?",[$ref?:null,$pay['id']]);
  audit($db,$actor,'payment.confirm','order',$id,['method'=>$pay['method'],'reference'=>$ref]);
 }elseif($operation==='cancel'){
  if($o['status']==='completed')fail('Una venta completada no se cancela desde este flujo.');
  if($pay['status']==='paid')fail('Un pedido pagado requiere registrar primero su devolución con un administrador; este demo no procesa reembolsos.');
  $lines=run($db,'SELECT product_id,quantity FROM order_items WHERE order_id=? ORDER BY product_id',[$id])->fetchAll();
  foreach($lines as $line){run($db,'UPDATE products SET stock=stock+?,revision=revision+1 WHERE id=?',[$line['quantity'],$line['product_id']]);run($db,'INSERT INTO inventory_movements(product_id,actor_id,quantity,reason) VALUES (?,?,?,?)',[$line['product_id'],$actor,$line['quantity'],'Cancelación #'.$id]);}
  run($db,"UPDATE orders SET status='cancelled' WHERE id=?",[$id]);run($db,"UPDATE payments SET status='failed' WHERE id=?",[$pay['id']]);
  run($db,"INSERT INTO order_status_history(order_id,actor_id,status,note) VALUES (?,?,'cancelled',?)",[$id,$actor,$note]);
  audit($db,$actor,'order.cancel','order',$id);
 }elseif($operation==='status'){
  $next=field($d,'status',20);$flow=['pending'=>['confirmed'],'confirmed'=>['preparing','completed'],'preparing'=>['shipped','completed'],'shipped'=>['completed'],'completed'=>[]];
  if(!in_array($next,$flow[$o['status']]??[],true))fail('Cambio de estado no permitido.');
  if(in_array($next,['shipped','completed'],true)&&$pay['status']!=='paid')fail('Registra el pago recibido antes de enviar o completar la venta.');
  if($next==='shipped'&&$o['delivery_method']!=='delivery')fail('El estado enviado corresponde a entregas a domicilio.');
  run($db,'UPDATE orders SET status=? WHERE id=?',[$next,$id]);
  run($db,'INSERT INTO order_status_history(order_id,actor_id,status,note) VALUES (?,?,?,?)',[$id,$actor,$next,$note]);audit($db,$actor,'order.status','order',$id,['status'=>$next]);
 }else fail('Operación inválida.');
 $db->commit();
}
