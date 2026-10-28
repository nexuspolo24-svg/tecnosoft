<?php
declare(strict_types=1);
if(!defined('TECNOSOFT_INTERNAL')){http_response_code(404);exit;}

function requestPasswordReset(PDO $db,array $d): void {
 $email=emailField($d);$cfg=mailConfig();
 if(empty($cfg['host'])||empty($cfg['from_email'])||empty($cfg['password'])||empty($cfg['base_url']))fail('El servicio de correo no está disponible. Contacta con la tienda.',503);
 $emailHash=hash('sha256',$email);$ipHash=hash('sha256',substr($_SERVER['REMOTE_ADDR']??'local',0,45));
 $message='Si existe una cuenta activa con ese correo, recibirás un enlace para restablecer la contraseña. Revisa también spam.';
 $limited=false;$token=null;$recipient=null;$requestId=null;
 // Serializar solo las comprobaciones y escrituras de límites, nunca la conexión SMTP.
 if(!(int)run($db,"SELECT GET_LOCK('tecnosoft_password_reset_request',5)")->fetchColumn())fail('No se pudo procesar la solicitud. Inténtalo de nuevo en unos segundos.',503);
 try{
  $db->beginTransaction();
  $day=(int)run($db,'SELECT COUNT(*) FROM password_resets WHERE email_hash=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY)',[$emailHash])->fetchColumn();
  $recent=(int)run($db,'SELECT COUNT(*) FROM password_resets WHERE email_hash=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 60 SECOND)',[$emailHash])->fetchColumn();
  $ipCount=(int)run($db,'SELECT COUNT(*) FROM password_resets WHERE ip_hash=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR)',[$ipHash])->fetchColumn();
  $limited=$day>=10||$recent>0||$ipCount>=20;
  if(!$limited){
   $recipient=run($db,'SELECT id,email,name FROM users WHERE email=? AND active=1 FOR UPDATE',[$email])->fetch()?:null;
   if($recipient){
    run($db,'UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL',[$recipient['id']]);
    $token=bin2hex(random_bytes(32));
   }
   run($db,'INSERT INTO password_resets(user_id,email_hash,ip_hash,token_hash,expires_at) VALUES (?,?,?,?,?)',[$recipient['id']??null,$emailHash,$ipHash,$token?hash('sha256',$token):null,$token?gmdate('Y-m-d H:i:s',time()+1800):null]);
   $requestId=(int)$db->lastInsertId();
  }
  $db->commit();
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 finally{run($db,"SELECT RELEASE_LOCK('tecnosoft_password_reset_request')");}
 if($limited)fail('Espera un minuto entre solicitudes. Se permiten diez al día por correo y veinte por hora desde esta conexión.',429);
 if($recipient&&$token){
  // El fragmento evita guardar el token en el registro HTTP del servidor.
  $url=rtrim($cfg['base_url'],'/').'/reset-password.php#token='.$token;
  try{
   sendMail($recipient['email'],'Recupera tu contraseña · TecnoSoft Solutions',"Hola ".$recipient['name'].",\n\nPara elegir una contraseña nueva, abre este enlace:\n".$url."\n\nEl enlace vence en 30 minutos y se utiliza una sola vez. Tu contraseña no cambia hasta que confirmes la nueva.\nSi no solicitaste este mensaje, ignóralo.\n\nTecnoSoft Solutions");
  }catch(Throwable $e){
   run($db,'UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE id=?',[$requestId]);
   error_log('TecnoSoft: no se pudo entregar un mensaje de recuperación de contraseña.');
   // Misma respuesta pública para no revelar si una cuenta existe.
  }
 }
 respond(['message'=>$message]);
}

function resetPassword(PDO $db,array $d): void {
 $token=field($d,'token',64);
 if(!preg_match('/^[a-f0-9]{64}$/D',$token))fail('El enlace no es válido. Solicita uno nuevo desde Olvidé mi contraseña.');
 $password=$d['password']??null;$confirm=$d['password_confirm']??null;
 if(!is_string($password)||!is_string($confirm))fail('Escribe y confirma tu nueva contraseña.');
 if($error=tsPasswordError($password,true))fail($error);
 if(!hash_equals($password,$confirm))fail('Las contraseñas no coinciden.');
 $hash=hash('sha256',$token);
 $candidate=run($db,'SELECT user_id FROM password_resets WHERE token_hash=?',[$hash])->fetchColumn();
 if(!$candidate)fail('El enlace venció o ya fue utilizado. Solicita uno nuevo desde Olvidé mi contraseña.');
 $db->beginTransaction();
 // Bloquear siempre primero el usuario, igual que al crear un enlace nuevo.
 $account=run($db,'SELECT id,active FROM users WHERE id=? FOR UPDATE',[$candidate])->fetch();
 $request=run($db,'SELECT id,expires_at,used_at FROM password_resets WHERE token_hash=? AND user_id=? FOR UPDATE',[$hash,$candidate])->fetch();
 if(!$account||!$account['active']||!$request||$request['used_at']||strtotime($request['expires_at'].' UTC')<=time())fail('El enlace venció o ya fue utilizado. Solicita uno nuevo desde Olvidé mi contraseña.');
 run($db,'UPDATE users SET password_hash=?,password_version=password_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$candidate]);
 run($db,'UPDATE password_resets SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL',[$candidate]);
 run($db,'DELETE FROM login_attempts WHERE email=(SELECT email FROM users WHERE id=?)',[$candidate]);
 $db->commit();
 // La versión de contraseña invalida también las demás sesiones al usarlas.
 $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));
 respond(['message'=>'Contraseña actualizada. Las sesiones anteriores se cerraron. Inicia sesión con tu nueva contraseña.','csrf'=>$_SESSION['csrf']]);
}
