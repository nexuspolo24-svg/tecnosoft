<?php
if(!defined('TECNOSOFT_INTERNAL')){http_response_code(404);exit;}
// Copiar como mail.local.php o completar desde Administración > Configuración.
// Este archivo y las credenciales reales deben permanecer FUERA de public/.
return [
 'host'=>'smtp.gmail.com','port'=>587,'security'=>'tls',
 'username'=>'','password'=>'','from_email'=>'','from_name'=>'TecnoSoft Solutions',
 'base_url'=>'http://127.0.0.1:8080'
];
