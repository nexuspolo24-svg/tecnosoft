'use strict';
const $ = (s, root=document) => root.querySelector(s);
const $$ = (s, root=document) => [...root.querySelectorAll(s)];
const money = value => 'C$ ' + new Intl.NumberFormat('es-NI',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(value));
const esc = value => String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let products=[], services=[], csrf='', user=null, category='all', games=false, register=false, toastTimer;
let cart={}, requestKey=null, storeSettings={};
try { requestKey=localStorage.getItem('tecnosoft-checkout-key'); } catch {}
try {const stored=JSON.parse(localStorage.getItem('tecnosoft-cart')||'{}'); if(stored && typeof stored==='object' && !Array.isArray(stored)) for(const [id,n] of Object.entries(stored)) if(/^\d+$/.test(id)&&Number.isInteger(n)&&n>0&&n<=99) cart[id]=n;} catch {}
function saveCart(changed=true){try{localStorage.setItem('tecnosoft-cart',JSON.stringify(cart));if(changed)localStorage.removeItem('tecnosoft-checkout-key');}catch{} if(changed)requestKey=null; $('#cart-count').textContent=Object.values(cart).reduce((a,b)=>a+b,0);}
function toast(message){$('#toast').textContent=message;$('#toast').classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(()=>$('#toast').classList.remove('show'),3500);}
async function api(action,data){const response=await fetch('api.php?action='+action,{method:data?'POST':'GET',credentials:'same-origin',headers:data?{'Content-Type':'application/json','X-CSRF-Token':csrf}:{},body:data?JSON.stringify(data):undefined}); let result;try{result=await response.json();}catch{throw Error('El servidor no devolvió una respuesta válida.');}if(!response.ok)throw Error(result.error||'No se pudo completar la operación.');if(result.csrf)csrf=result.csrf;return result;}
function feedback(form,message,error=false){const node=$('.form-feedback',form);node.textContent=message;node.classList.toggle('error',error);}
function validateInput(input,show=false){
  const v=TecnoSoftValidation;
  let error='';
  if(!input.disabled){
    const value=input.name==='password'?input.value:input.value.trim();
    if(input.required && value==='') error='Completa este campo.';
    else if(value!==''){
      if(input.name==='email') error=v.email(value);
      else if(input.name==='name') error=v.name(value);
      else if(input.name==='phone') error=v.phone(value);
      else if(input.name==='password') error=v.password(value,register);
      else error=v.text(value);
      if(!error && input.maxLength>0 && [...value].length>input.maxLength) error='El campo es demasiado largo.';
    }
  }
  input.setCustomValidity(error);
  input.setAttribute('aria-invalid',error?'true':'false');
  const note=document.getElementById(input.id+'-error');
  if(note){note.textContent=show?error:'';note.hidden=!show||!error;}
  return !error;
}
function validateForm(form){
  const fields=$$('input:not([name="website"]),textarea',form);
  fields.forEach(input=>{if(input.name!=='password')input.value=input.value.trim();validateInput(input,true);});
  if(!form.checkValidity()){
    const input=fields.find(field=>!field.disabled&&!field.validity.valid);
    feedback(form,input?.validationMessage||'Revisa los campos marcados.',true);
    form.reportValidity();
    return false;
  }
  return true;
}
function setupValidation(){
  ['auth-form','contact-form','checkout-form'].forEach(id=>{
    const form=$('#'+id);
    $$('input:not([name="website"]),textarea',form).forEach(input=>{
      input.id ||= id+'-'+input.name;
      if(input.labels?.[0] && !input.hasAttribute('aria-label'))input.setAttribute('aria-label',input.labels[0].textContent.trim());
      const note=document.createElement('small');
      note.id=input.id+'-error';note.className='field-validation';note.hidden=true;
      input.insertAdjacentElement('afterend',note);
      input.setAttribute('aria-describedby',((input.getAttribute('aria-describedby')||'')+' '+note.id).trim());
      input.addEventListener('input',()=>validateInput(input,true));
      input.addEventListener('blur',()=>{if(input.name!=='password')input.value=input.value.trim();validateInput(input,true);});
    });
    form.addEventListener('invalid',event=>{
      if(event.target.matches('input,textarea')){
        validateInput(event.target,true);
        feedback(form,event.target.validationMessage||'Revisa los campos marcados.',true);
      }
    },true);
    form.addEventListener('reset',()=>{
      $$('input,textarea',form).forEach(input=>{
        input.setCustomValidity('');input.removeAttribute('aria-invalid');
        const note=document.getElementById(input.id+'-error');if(note){note.textContent='';note.hidden=true;}
      });
    });
  });
}
async function submit(form,task){const button=$('button[type="submit"]',form);button.disabled=true;feedback(form,'Procesando…');try{await task();}catch(error){feedback(form,error.message,true);}finally{button.disabled=false;}}
function image(p){return esc(p.image||'assets/producto.svg');}
function fixImages(root=document){$$('img[data-fallback]',root).forEach(img=>{img.addEventListener('error',()=>{img.src='assets/producto.svg';},{once:true});});}
function renderProducts(){let list=products.filter(p=>(games?p.slug==='juegos':p.slug!=='juegos')&&(category==='all'||p.slug===category));const query=$('#search').value.trim().toLocaleLowerCase('es');if(query)list=list.filter(p=>(p.name+' '+p.description).toLocaleLowerCase('es').includes(query));const budget=$('#budget').value;list=list.filter(p=>budget==='all'||(budget==='low'?Number(p.price)<=1000:budget==='mid'?Number(p.price)>1000&&Number(p.price)<2500:Number(p.price)>=2500));const sort=$('#sort').value;if(sort!=='default')list.sort((a,b)=>sort==='low'?a.price-b.price:b.price-a.price);$('#product-count').textContent=list.length+' productos disponibles';$('#product-grid').innerHTML=list.length?list.map(p=>`<article class="product-card"><button class="product-picture" data-detail="${p.id}" aria-label="Ver detalles de ${esc(p.name)}"><img src="${image(p)}" alt="${esc(p.name)}" loading="lazy" data-fallback></button><div class="product-meta"><span>${esc(p.category).toUpperCase()}</span><span>${p.stock>0?'DISPONIBLE':'AGOTADO'}</span></div><h3>${esc(p.name)}</h3><p class="product-desc">${esc(p.description)}</p><div class="product-bottom"><strong>${money(p.price)}</strong><button class="add" data-add="${p.id}" ${p.stock>0?'':'disabled'} aria-label="Agregar ${esc(p.name)} al carrito">+ AGREGAR</button></div></article>`).join(''):'<p class="empty">No encontramos productos con estos filtros.</p>';fixImages($('#product-grid'));}
function renderServices(){$('#service-grid').innerHTML=services.map((s,i)=>`<article class="service-card"><span class="service-num">0${i+1} /</span><h3>${esc(s.name)}</h3><ul>${s.description.split(';').map(part=>`<li>${esc(part.trim())}</li>`).join('')}</ul><button data-service="${s.id}">SOLICITAR ↗</button></article>`).join('');$('#service-select').innerHTML='<option value="">Consulta de producto</option>'+services.map(s=>`<option value="${s.id}">${esc(s.name)}</option>`).join('');}
function add(id){const p=products.find(p=>Number(p.id)===Number(id));if(!p)return;if((cart[id]||0)>=Math.min(Number(p.stock),99)){toast('Llegaste a la cantidad disponible.');return;}cart[id]=(cart[id]||0)+1;saveCart();toast('Agregado: '+p.name);if($('#cart-dialog').open)renderCart();}
function renderCart(){const lines=Object.entries(cart).map(([id,n])=>({p:products.find(p=>Number(p.id)===Number(id)),n})).filter(x=>x.p);$('#cart-lines').innerHTML=lines.length?lines.map(({p,n})=>`<div class="cart-line"><img src="${image(p)}" alt="" data-fallback><div><h3>${esc(p.name)}</h3><p>${money(p.price)}</p><div class="qty"><button data-qty="${p.id}" data-delta="-1" aria-label="Quitar una unidad de ${esc(p.name)}">−</button><span>${n}</span><button data-qty="${p.id}" data-delta="1" aria-label="Añadir una unidad de ${esc(p.name)}">+</button></div></div><button class="remove" data-remove="${p.id}" aria-label="Eliminar ${esc(p.name)}">×</button></div>`).join(''):'<p class="empty">Tu carrito está esperando una buena idea.<br>Explora el catálogo para comenzar.</p>';$('#cart-total').textContent=money(lines.reduce((sum,{p,n})=>sum+Number(p.price)*n,0));$('#checkout-form').hidden=!lines.length;fixImages($('#cart-lines'));checkoutOptions();}
function showAuth(){register=false;renderAuth();$('#auth-dialog').showModal();}
function renderAuth(){$('#name-label').hidden=!register;const name=$('#name-label input');name.required=register;name.disabled=!register;validateInput(name,false);$('#password-note').hidden=!register;$('#auth-title').innerHTML=register?'Tu mundo,<br><em>conectado.</em>':'Bienvenido<br><em>de nuevo.</em>';$('#auth-submit').textContent=register?'CREAR CUENTA ↗':'INICIAR SESIÓN ↗';$('#switch-auth').textContent=register?'Ya tengo cuenta. Iniciar sesión':'¿Primera vez? Crea una cuenta';const password=$('#auth-form [name="password"]');password.autocomplete=register?'new-password':'current-password';password.minLength=register?10:1;validateInput(password,false);feedback($('#auth-form'),'');}
function updateUser(){$('#account').textContent=user?user.name.split(' ')[0]+' · Mi cuenta':'Iniciar sesión';$('#admin-link').hidden=!user?.is_admin;}
const statuses={pending:'Pendiente',confirmed:'Confirmado',preparing:'En preparación',shipped:'Enviado',completed:'Completado',cancelled:'Cancelado',new:'Nueva',diagnosing:'En diagnóstico',quoted:'Cotizada',approved:'Aprobada',in_progress:'En curso'};
async function showAccount(){$('#account-dialog').showModal();$('#account-name').textContent=user.name;$('#account-content').textContent='Cargando tus pedidos…';try{const d=await api('orders');user=d.user;updateUser();emailState();$('#account-content').innerHTML='<h3>Mis pedidos</h3>'+(d.orders.length?d.orders.map(o=>`<article class="order-row"><header><span>Pedido #${o.id} · ${esc(statuses[o.status]||o.status)}</span><span>${money(o.total)}</span></header><p>${esc(o.created_at)} UTC · ${o.payment_method==='transfer'?'Transferencia':'Efectivo'} · ${o.payment_status==='paid'?'Pago recibido':'Pago pendiente'}</p><a class="text-link" href="${esc(o.whatsapp_url)}" target="_blank" rel="noopener noreferrer">COORDINAR POR WHATSAPP ↗</a><p>${o.items.map(i=>`${i.quantity} × ${esc(i.product_name)}`).join('<br>')}</p></article>`).join(''):'<p class="muted">Aún no tienes pedidos.</p>')+'<h3>Mis solicitudes de servicio</h3>'+(d.requests.length?d.requests.map(r=>`<article class="order-row">#${r.id} · ${esc(r.service)} · ${esc(statuses[r.status]||r.status)}</article>`).join(''):'<p class="muted">Las solicitudes enviadas con la sesión iniciada aparecerán aquí.</p>');}catch(e){$('#account-content').textContent=e.message;}}
document.addEventListener('click',event=>{const close=event.target.closest('[data-close]');if(close)close.closest('dialog').close();const addButton=event.target.closest('[data-add]');if(addButton)add(Number(addButton.dataset.add));const detail=event.target.closest('[data-detail]');if(detail){const p=products.find(p=>Number(p.id)===Number(detail.dataset.detail));$('#detail-content').innerHTML=`<img class="detail-image" src="${image(p)}" alt="${esc(p.name)}" data-fallback><p class="eyebrow">${esc(p.category)} · ${p.kind==='digital'?'DIGITAL':'FÍSICO'}</p><h2 id="detail-title">${esc(p.name)}</h2><p class="lead">${esc(p.description)}</p><p class="lead"><strong>${money(p.price)}</strong></p><p class="form-note">${p.stock} unidades de ejemplo disponibles. Fotografía ilustrativa del prototipo; confirma modelo y disponibilidad antes de comprar.</p><button class="button" data-add="${p.id}" ${p.stock>0?'':'disabled'}>AGREGAR AL CARRITO ↗</button>`;fixImages($('#detail-content'));$('#detail-dialog').showModal();}const service=event.target.closest('[data-service]');if(service){$('#service-select').value=service.dataset.service;$('#contacto').scrollIntoView({behavior:'smooth'});$('#contact-form [name="name"]').focus({preventScroll:true});}const cat=event.target.closest('[data-category]');if(cat){category=cat.dataset.category;games=false;$('#games').classList.remove('active');$$('[data-category]').forEach(b=>b.classList.toggle('active',b===cat));renderProducts();}const qty=event.target.closest('[data-qty]');if(qty){const id=qty.dataset.qty;if(qty.dataset.delta==='1')add(id);else{cart[id]--;if(cart[id]<=0)delete cart[id];saveCart();renderCart();}}const remove=event.target.closest('[data-remove]');if(remove){delete cart[remove.dataset.remove];saveCart();renderCart();}});
$('#games').addEventListener('click',()=>{games=!games;category='all';$('#games').classList.toggle('active',games);$$('[data-category]').forEach(b=>b.classList.toggle('active',!games&&b.dataset.category==='all'));renderProducts();});
['search','budget','sort'].forEach(id=>$('#'+id).addEventListener(id==='search'?'input':'change',renderProducts));
$('#open-cart').addEventListener('click',()=>{renderCart();feedback($('#checkout-form'),'');$('#cart-dialog').showModal();});
$('#account').addEventListener('click',()=>user?showAccount():showAuth());
$('#switch-auth').addEventListener('click',()=>{register=!register;renderAuth();});
$('#auth-form').addEventListener('submit',event=>{event.preventDefault();const form=event.currentTarget;if(!validateForm(form))return;submit(form,async()=>{const d=await api(register?'register':'login',Object.fromEntries(new FormData(form)));user=d.user;updateUser();form.reset();$('#auth-dialog').close();toast('Bienvenido, '+user.name+'.');});});
$('#logout').addEventListener('click',async()=>{try{await api('logout',{});user=null;updateUser();$('#account-dialog').close();toast('Sesión cerrada.');}catch(e){toast(e.message);}});
$('#checkout-form').addEventListener('submit',event=>{event.preventDefault();if(!user){$('#cart-dialog').close();showAuth();toast('Inicia sesión y vuelve al carrito para registrar tu pedido.');return;}const form=event.currentTarget;if(!validateForm(form))return;submit(form,async()=>{requestKey??=crypto.randomUUID();try{localStorage.setItem('tecnosoft-checkout-key',requestKey);}catch{}const d=await api('checkout',{...Object.fromEntries(new FormData(form)),request_key:requestKey,items:Object.entries(cart).map(([id,quantity])=>({id:Number(id),quantity}))});cart={};saveCart();renderCart();form.reset();$('#cart-dialog').close();toast('Pedido #'+d.order.id+' registrado. Pago pendiente.');const fresh=await api('catalog');products=fresh.products;renderProducts();await showAccount();});});
$('#contact-form').addEventListener('submit',event=>{event.preventDefault();const form=event.currentTarget;if(!validateForm(form))return;submit(form,async()=>{const d=await api('contact',Object.fromEntries(new FormData(form)));form.reset();feedback(form,d.type+' #'+d.id+' guardada correctamente.');const link=document.createElement('a');link.href=d.whatsapp_url;link.target='_blank';link.rel='noopener noreferrer';link.className='text-link';link.textContent=' ENVIAR CONSULTA POR WHATSAPP ↗';$('.form-feedback',form).append(link);});});
async function init(){saveCart(false);try{const s=await api('session');user=s.user;updateUser();const d=await api('catalog');products=d.products;services=d.services;storeSettings=d.settings||{};let removed=false;for(const id of Object.keys(cart))if(!products.some(p=>Number(p.id)===Number(id))){delete cart[id];removed=true;}if(removed)saveCart();renderProducts();renderServices();}catch(error){$('#product-count').textContent='Catálogo pendiente de conexión';$('#product-grid').innerHTML=`<p class="empty">${esc(error.message)}</p>`;toast(error.message);}}

function emailState(){
 const verified=Boolean(user?.email_verified_at);
 $('#account-email-state').textContent=(user?.email||'')+' · '+(verified?'Correo confirmado':'Correo sin confirmar');
 $$('.email-card [data-verify-email]').forEach(b=>b.hidden=verified);
 $('#email-status').textContent=verified?'Correo confirmado. El administrador revisará tu transferencia.':'Debes confirmar tu correo para usar transferencia. Después pulsa Actualizar confirmación en Mi cuenta.';
 $('#transfer-info [data-verify-email]').hidden=verified;
}
function checkoutOptions(){
 const form=$('#checkout-form'),delivery=form.elements.delivery_method.value;
 const physical=Object.keys(cart).some(id=>products.find(p=>Number(p.id)===Number(id))?.kind==='physical');
 form.elements.delivery_method.querySelector('[value=digital]').disabled=physical;
 if(physical&&delivery==='digital')form.elements.delivery_method.value='pickup';
 const mode=form.elements.delivery_method.value;
 $('#delivery-fields').hidden=mode!=='delivery';
 $$('input,textarea',$('#delivery-fields')).forEach(i=>i.disabled=mode!=='delivery');
 $('#transfer-info').hidden=form.elements.payment_method.value!=='transfer';
 $('#transfer-instructions').textContent=storeSettings.transfer_instructions||'';
 $('#checkout-info').textContent='Demostración académica. No realices pagos reales. El pedido reserva existencias hasta que se atienda o cancele.';
 $('#delivery-summary').textContent=mode==='delivery'?'Envío: '+money(storeSettings.delivery_fee||0):mode==='digital'?'La entrega digital se coordina manualmente después de confirmar el pago.':storeSettings.pickup_point||'Punto de retiro a coordinar.';
 const subtotal=Object.entries(cart).reduce((s,[id,n])=>s+Number(products.find(p=>Number(p.id)===Number(id))?.price||0)*n,0);
 $('#checkout-amount').textContent='Total estimado: '+money(subtotal+(mode==='delivery'?Number(storeSettings.delivery_fee||0):0));
 emailState();
}
$('#checkout-form').addEventListener('change',()=>{checkoutOptions();});
$('#refresh-account').onclick=()=>showAccount();
document.addEventListener('click',async event=>{
 const button=event.target.closest('[data-verify-email]');if(!button)return;
 if(!user){showAuth();return;}
 button.disabled=true;const root=button.closest('.email-card')||$('#transfer-info');const note=$('.verification-feedback',root);note.textContent='Enviando…';
 try{const d=await api('request_verification',{});note.textContent=d.message;}catch(e){note.textContent=e.message;}finally{button.disabled=false;}
});

setupValidation();
init();

