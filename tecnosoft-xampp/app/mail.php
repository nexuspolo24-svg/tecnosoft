<?php
require_once __DIR__.'/vendor/PHPMailer/Exception.php';
require_once __DIR__.'/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__.'/vendor/PHPMailer/SMTP.php';
function mailConfig(): array {return is_file(__DIR__.'/mail.local.php')?require __DIR__.'/mail.local.php':[];}
function mailPublicConfig(): array {$c=mailConfig();unset($c['password'],$c['allow_test_transport']);$c['password_saved']=!empty(mailConfig()['password']);return $c;}
function sendMail(string $to,string $subject,string $body): void {
 $c=mailConfig();if(empty($c['host'])||empty($c['from_email'])||empty($c['password']))throw new RuntimeException('SMTP pendiente de configuración.');
 $m=new PHPMailer\PHPMailer\PHPMailer(true);$m->isSMTP();$m->CharSet='UTF-8';$m->Host=$c['host'];$m->Port=(int)$c['port'];$m->SMTPAuth=true;$m->Username=$c['username'];$m->Password=$c['password'];$m->SMTPSecure=$c['security'];$m->Timeout=15;$m->Timelimit=20;
 // Solo los tests privados pueden usar un receptor SMTP local sin TLS/autenticación.
 if(!empty($c['allow_test_transport'])&&in_array($c['host'],['127.0.0.1','localhost'],true)){$m->SMTPAuth=false;$m->SMTPSecure='';$m->SMTPAutoTLS=false;}
 $m->setFrom($c['from_email'],$c['from_name']??'TecnoSoft Solutions');$m->addAddress($to);$m->Subject=$subject;$m->Body=$body;$m->send();
}
function saveMailConfig(array $d): void {
 $old=mailConfig();$host=field($d,'host',190);if(!preg_match('/^(?=.{1,190}$)[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/D',$host))fail('Servidor SMTP inválido.');
 $security=field($d,'security',10);$port=intField($d,'port',1,65535);if(!in_array($security,['tls','ssl'],true))fail('SMTP requiere TLS o SSL.');
 $from=emailField(['email'=>$d['from_email']??'']);$username=field($d,'username',190);$name=field($d,'from_name',100);
 $password=$d['password']??'';if(!is_string($password)||strlen($password)>500||strpbrk($password,"\r\n\0")!==false)fail('Contraseña SMTP inválida.');
 if($password==='')$password=$old['password']??'';if($password==='')fail('Introduce la contraseña de aplicación del correo.');
 $url=rtrim(field($d,'base_url',500),'/');$u=parse_url($url);
 if(!$u||isset($u['query'])||isset($u['fragment'])||isset($u['user'])||!isset($u['host'])||!(($u['scheme']??'')==='https'||(($u['scheme']??'')==='http'&&in_array($u['host'],['localhost','127.0.0.1'],true))))fail('Usa una URL HTTPS; en local se permite http://127.0.0.1:8080.');
 $config=compact('host','port','security','username','password');$config+=['from_email'=>$from,'from_name'=>$name,'base_url'=>$url];
 $file=__DIR__.'/mail.local.php';$tmp=tempnam(__DIR__,'mail-');if($tmp===false)throw new RuntimeException('No se pudo guardar SMTP.');
 try{if(file_put_contents($tmp,"<?php\nreturn ".var_export($config,true).";\n",LOCK_EX)===false||!rename($tmp,$file))throw new RuntimeException('No se pudo guardar SMTP.');}finally{if(is_file($tmp))unlink($tmp);}
}
function requestVerification(PDO $db): void {
 $uid=requireUser();if($_SESSION['user']['email_verified_at'])respond(['message'=>'Tu correo ya está confirmado.']);
 $cfg=mailConfig();if(empty($cfg['password']))fail('El administrador debe configurar SMTP antes de enviar correos.',503);
 $db->beginTransaction();run($db,'SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
 $count=run($db,'SELECT COUNT(*) FROM email_verifications WHERE user_id=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY)',[$uid])->fetchColumn();
 $recent=run($db,'SELECT COUNT(*) FROM email_verifications WHERE user_id=? AND created_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 60 SECOND)',[$uid])->fetchColumn();
 if($count>=10||$recent)fail('Espera un minuto entre envíos. Máximo diez correos al día.',429);
 run($db,'UPDATE email_verifications SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL',[$uid]);
 $token=bin2hex(random_bytes(32));run($db,'INSERT INTO email_verifications(user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE))',[$uid,hash('sha256',$token)]);$id=(int)$db->lastInsertId();$db->commit();
 $link=rtrim($cfg['base_url'],'/').'/verify.php?token='.$token;
 try{sendMail($_SESSION['user']['email'],'Confirma tu correo · TecnoSoft Solutions',"Hola ".$_SESSION['user']['name'].",\n\nConfirma tu correo abriendo este enlace y pulsando Confirmar:\n".$link."\n\nEl enlace vence en 30 minutos y solo se utiliza una vez. Si no solicitaste este mensaje, ignóralo.\nTecnoSoft Solutions");}
 catch(Throwable $e){run($db,'UPDATE email_verifications SET used_at=UTC_TIMESTAMP() WHERE id=?',[$id]);fail('No se pudo enviar el correo. Revisa la configuración SMTP con el administrador.',503);}
 respond(['message'=>'Te enviamos un enlace de confirmación. Revisa también la carpeta de spam.']);
}
function verifyEmail(PDO $db,array $d): void {
 $token=field($d,'token',64);if(!preg_match('/^[a-f0-9]{64}$/D',$token))fail('Enlace inválido.');
 $db->beginTransaction();$row=run($db,'SELECT * FROM email_verifications WHERE token_hash=? FOR UPDATE',[hash('sha256',$token)])->fetch();
 if(!$row||$row['used_at']||strtotime($row['expires_at'].' UTC')<=time())fail('El enlace venció o ya fue utilizado. Solicita otro desde Mi cuenta.');
 run($db,'UPDATE users SET email_verified_at=COALESCE(email_verified_at,UTC_TIMESTAMP()) WHERE id=?',[$row['user_id']]);
 run($db,'UPDATE email_verifications SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL',[$row['user_id']]);$db->commit();respond(['message'=>'Correo confirmado. Ya puedes elegir transferencia al comprar.']);
}
