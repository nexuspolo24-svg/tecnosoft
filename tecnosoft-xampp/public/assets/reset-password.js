'use strict';
(()=>{
 const form=document.querySelector('#reset-password-form'),password=form.elements.password,confirm=form.elements.password_confirm,note=document.querySelector('#reset-feedback');
 const token=new URLSearchParams(location.hash.slice(1)).get('token')||'';
 history.replaceState(null,'','reset-password.php');
 if(!/^[a-f0-9]{64}$/.test(token)){note.textContent='El enlace no es válido. Solicita uno nuevo desde Olvidé mi contraseña.';return;}
 form.hidden=false;
 function validate(){
  const error=TecnoSoftValidation.password(password.value,true);
  const mismatch=confirm.value===password.value?'':'Las contraseñas no coinciden.';
  for(const [input,message,id]of [[password,error,'reset-password-error'],[confirm,mismatch,'reset-confirm-error']]){
   input.setCustomValidity(message);input.setAttribute('aria-invalid',message?'true':'false');
   const help=document.getElementById(id);help.textContent=message;help.hidden=!message;
  }
  return form.checkValidity();
 }
 password.addEventListener('input',validate);confirm.addEventListener('input',validate);
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(!validate()){form.reportValidity();return;}
  const button=form.querySelector('button[type=submit]');button.disabled=true;note.textContent='Actualizando contraseña…';
  try{
   const sr=await fetch('api.php?action=session',{credentials:'same-origin'});const session=await sr.json();if(!sr.ok)throw Error(session.error||'No se pudo iniciar la solicitud.');
   const response=await fetch('api.php?action=reset_password',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':session.csrf},body:JSON.stringify({token,password:password.value,password_confirm:confirm.value})});
   const data=await response.json();if(!response.ok)throw Error(data.error||'No se pudo actualizar la contraseña.');
   form.reset();form.hidden=true;note.textContent=data.message;
  }catch(error){note.textContent=error.message;}
  finally{button.disabled=false;}
 });
})();
