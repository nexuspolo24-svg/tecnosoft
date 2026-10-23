'use strict';
const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>[...r.querySelectorAll(s)];
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money=v=>'C$ '+Number(v||0).toLocaleString('es-NI',{minimumFractionDigits:2,maximumFractionDigits:2});
const names={pending:'Pendiente',confirmed:'Confirmado',preparing:'En preparación',shipped:'Enviado',completed:'Completado',cancelled:'Cancelado',paid:'Recibido',failed:'Anulado',cash:'Efectivo',transfer:'Transferencia',pickup:'Retiro',delivery:'Domicilio',digital:'Digital',new:'Nueva',read:'Leída',resolved:'Resuelta',diagnosing:'En diagnóstico',quoted:'Cotizado',approved:'Aprobado',in_progress:'En curso',web:'Web',store:'Presencial'};
const label=v=>esc(names[v]||v);
let csrf='',data=null,tab='summary',sale={},saleKey=crypto.randomUUID(),busy=false;
let refreshing=null,liveInputRevision=0,lastLiveUpdate=null;
const liveDirtyForms=new Set();
function notify(s,error=false){$('#admin-feedback').textContent=s;$('#admin-feedback').classList.toggle('error',error);}
async function api(action,body){const r=await fetch('api.php?action='+action,{method:body?'POST':'GET',credentials:'same-origin',headers:body?{'Content-Type':'application/json','X-CSRF-Token':csrf}:{},body:body?JSON.stringify(body):undefined});const d=await r.json();if(!r.ok)throw Error(d.error||'Error del servidor.');if(d.csrf)csrf=d.csrf;return d;}
function table(headers,rows){return rows.length?'<div class="table-wrap"><table><thead><tr>'+headers.map(h=>'<th>'+esc(h)+'</th>').join('')+'</tr></thead><tbody>'+rows.map(row=>'<tr>'+row.map(v=>'<td>'+v+'</td>').join('')+'</tr>').join('')+'</tbody></table></div>':'<p class="muted">Sin registros.</p>';}
function values(form,source){for(const [k,v]of Object.entries(source))if(form.elements.namedItem(k)&&v!==null)form.elements.namedItem(k).value=String(v);}
function date(v){return new Date(v.replace(' ','T')+'Z').toLocaleString('es-NI',{timeZone:'America/Managua'});}
function canApplyLiveRefresh(){
 return Boolean(data)&&!document.hidden&&!busy&&!$('dialog[open]')&&!liveDirtyForms.size&&!['sale','settings','inbox'].includes(tab)&&!document.activeElement?.matches('input,textarea,select,[contenteditable=true]');
}
function liveStatus(message){
 const n=$('#live-status');if(n)n.textContent=message;
}
function reload({automatic=false}={}){
 if(refreshing)return automatic?refreshing:refreshing.then(()=>reload());
 const revision=liveInputRevision;
 refreshing=(async()=>{
  const q=new URLSearchParams(new FormData($('#report-filter')));
  const fresh=await api('admin_data&'+q);
  if(automatic&&(revision!==liveInputRevision||!canApplyLiveRefresh()))return;
  data=fresh;render();lastLiveUpdate=new Date();
  liveStatus('Actualización cada 15 s · Última: '+lastLiveUpdate.toLocaleTimeString('es-NI',{timeZone:'America/Managua'}));
 })().finally(()=>{refreshing=null;});
 return refreshing;
}
function render(){
 const r=data.report;
 $('#metrics').innerHTML=[['VENTAS COBRADAS',r.sales],['TOTAL COBRADO',money(r.revenue)],['MARGEN BRUTO CONOCIDO',money(r.known_margin)],['UNIDADES SIN COSTO',r.units_without_cost]].map(([k,v])=>'<article class="metric"><small>'+k+'</small><strong>'+v+'</strong></article>').join('');
 $('#top-products').innerHTML=table(['Producto','Unidades','Venta'],data.top.map(p=>[esc(p.product_name),esc(p.quantity),money(p.revenue)]));
 $('#daily-sales').innerHTML=table(['Día','Ventas','Cobrado'],data.daily.map(p=>[esc(p.day),esc(p.sales),money(p.revenue)]));
 $('#low-stock').innerHTML=table(['Producto','Stock','Estado'],data.products.filter(p=>p.active==1&&p.stock<=5).map(p=>[esc(p.name),esc(p.stock),p.stock==0?'Agotado':'Bajo']));
 renderProducts();renderOrders();
 $('#customers-table').innerHTML=table(['Cliente','Correo','Teléfono','Correo confirmado','Pedidos web'],data.customers.map(u=>[esc(u.name),esc(u.email),esc(u.phone||'—'),u.email_verified_at?'Sí':'Pendiente',esc(u.orders)]));
 $('#sale-product').innerHTML=data.products.filter(p=>p.active==1&&p.stock>0).map(p=>'<option value="'+p.id+'">'+esc(p.name)+' · '+money(p.price)+' · stock '+p.stock+'</option>').join('');
 renderSale();
 values($('#settings-form'),data.settings);values($('#smtp-form'),data.smtp);$('#smtp-form [name=password]').value='';
 $('#smtp-status').textContent=data.smtp.password_saved?'Contraseña guardada. Déjala vacía para conservarla.':'SMTP pendiente: introduce tu contraseña de aplicación.';
 $('#inventory-table').innerHTML=table(['Fecha','Producto','Cambio','Motivo'],data.movements.map(m=>[esc(date(m.created_at)),esc(m.product),esc(m.quantity),esc(m.reason)]));
 $('#audit-table').innerHTML=table(['Fecha','Administrador','Acción','Registro'],data.audit.map(a=>[esc(date(a.created_at)),esc(a.actor),esc(a.action),esc(a.entity)+' #'+a.entity_id]));
 renderInbox();
}
function renderProducts(){const q=$('#product-search').value.toLocaleLowerCase('es');$('#products-table').innerHTML=table(['Producto / SKU','Precio','Costo','Stock','Visible',''],data.products.filter(p=>(p.name+' '+p.sku).toLocaleLowerCase('es').includes(q)).map(p=>[esc(p.name)+'<br><small>'+esc(p.sku)+'</small>',money(p.price),p.cost_price===null?'Sin registrar':money(p.cost_price),esc(p.stock),p.active==1?'Sí':'Oculto','<button data-edit="'+p.id+'">EDITAR</button>']));}
function renderOrders(){const status=$('#order-filter').value;$('#orders-list').innerHTML=table(['Pedido','Cliente','Origen','Total','Estado','Pago',''],data.orders.filter(o=>!status||o.status===status).map(o=>['#'+o.id+'<br><small>'+esc(date(o.created_at))+'</small>',esc(o.customer_name||o.email),label(o.sales_channel),money(o.total),label(o.status),label(o.payment_method)+'<br>'+label(o.payment_status),'<button data-order="'+o.id+'">VER / GESTIONAR</button>']));}
function openProduct(id){const p=data.products.find(x=>Number(x.id)===id);const form=$('#product-form');form.reset();$('#product-form [name=category_id]').innerHTML=data.categories.map(c=>'<option value="'+c.id+'">'+esc(c.name)+'</option>').join('');values(form,p||{id:0,revision:1,stock:0,active:1,price:'',cost_price:'',kind:'physical'});if(!p?.cost_price)form.elements.cost_price.value=p?.cost_price??'';form.elements.image.value=p?.image||'';$('.form-feedback',form).textContent='';$('#product-title').textContent=p?'Editar producto':'Agregar producto';$('#product-dialog').showModal();}
const flow={pending:['confirmed'],confirmed:['preparing','completed'],preparing:['shipped','completed'],shipped:['completed'],completed:[],cancelled:[]};
function openOrder(id){const o=data.orders.find(x=>Number(x.id)===id);$('#order-title').textContent='Pedido #'+id;$('#order-detail').innerHTML='<p>'+esc(o.customer_name||o.email)+' · '+esc(o.email)+'<br>'+esc(o.customer_phone||'')+'</p><p>'+label(o.status)+' · '+label(o.payment_method)+' · '+label(o.payment_status)+'<br>'+label(o.delivery_method)+' · '+label(o.sales_channel)+'</p>'+table(['Producto','Cantidad','Total'],o.items.map(i=>[esc(i.product_name),esc(i.quantity),money(i.line_total)]))+'<p><strong>Total: '+money(o.total)+'</strong> · Envío '+money(o.shipping_total)+'</p>'+(o.address?'<p>Dirección: '+esc(o.address.department)+', '+esc(o.address.city)+', '+esc(o.address.address_line)+'</p>':'<p>Entrega: '+esc(o.delivery_method==='digital'?'Coordinación digital después del pago':data.settings.pickup_point)+'</p>')+'<p>Notas: '+esc(o.notes||'—')+'</p><p>Referencia: '+esc(o.provider_reference||'Pendiente')+'</p>';
 const form=$('#order-form');form.reset();form.elements.id.value=id;form.elements.status.innerHTML=(flow[o.status]||[]).filter(s=>s!=='shipped'||o.delivery_method==='delivery').map(s=>'<option value="'+s+'">'+label(s)+'</option>').join('');$('.form-feedback',form).textContent='';$('button[type=submit]',form).disabled=o.status==='cancelled'||o.status==='completed';$('#order-dialog').showModal();}
function renderSale(){const lines=Object.entries(sale).map(([id,n])=>({p:data.products.find(p=>Number(p.id)===Number(id)),n}));$('#sale-lines').innerHTML=table(['Producto','Cantidad','Subtotal',''],lines.map(({p,n})=>[esc(p.name),n,money(p.price*n),'<button type="button" data-sale-remove="'+p.id+'">QUITAR</button>']))+'<p class="sale-total">'+money(lines.reduce((s,{p,n})=>s+p.price*n,0))+'</p>';}
const serviceFlow={new:['diagnosing','cancelled'],diagnosing:['quoted','cancelled'],quoted:['approved','cancelled'],approved:['in_progress','cancelled'],in_progress:['completed','cancelled'],completed:[],cancelled:[]};
function renderInbox(){
 $('#requests-list').innerHTML=data.requests.map(r=>'<article class="admin-card"><div class="row-heading"><h2>#'+r.id+' · '+esc(r.service)+'</h2><span class="badge">'+label(r.status)+'</span></div><p>'+esc(r.name)+' · '+esc(r.email)+' · '+esc(r.phone)+'</p><p>'+esc(r.description)+'</p><p>Cotización: '+(r.quoted_total===null?'Pendiente':money(r.quoted_total))+'</p>'+(serviceFlow[r.status].length?'<form data-request="'+r.id+'"><div class="form-row"><label>SIGUIENTE ESTADO<select name="status">'+serviceFlow[r.status].map(s=>'<option value="'+s+'">'+label(s)+'</option>').join('')+'</select></label><label>COTIZACIÓN C$<input name="quoted_total" type="number" min="0" step=".01" value="'+esc(r.quoted_total??'')+'"></label></div><label>NOTA<input name="note" maxlength="500"></label><button class="button" type="submit">ACTUALIZAR SERVICIO</button></form>':'')+'</article>').join('')||'<p>Sin solicitudes.</p>';
 $('#messages-list').innerHTML=data.messages.map(m=>'<article class="admin-card"><h2>Consulta #'+m.id+' · '+label(m.status)+'</h2><p>'+esc(m.name)+' · '+esc(m.email)+' · '+esc(m.phone)+'</p><p>'+esc(m.message)+'</p><button data-message="'+m.id+'" data-state="read">MARCAR LEÍDA</button> <button data-message="'+m.id+'" data-state="resolved">RESOLVER</button></article>').join('')||'<p>Sin consultas.</p>';
}
async function mutation(action,body,form){
 if(busy)return;busy=true;const button=form?.querySelector('button[type=submit]');if(button)button.disabled=true;
 const note=form?.querySelector('.form-feedback');notify('Guardando…');if(note)note.textContent='Guardando…';
 try{const d=await api(action,body);if(form)liveDirtyForms.delete(form);if(form?.closest('dialog'))form.closest('dialog').close();await reload();notify(d.message||(d.order?'Venta #'+d.order.id+' registrada.':'Guardado.'));return d;}
 catch(e){notify(e.message,true);if(note)note.textContent=e.message;}
 finally{busy=false;if(button)button.disabled=false;}
}
document.addEventListener('click',async e=>{
 const t=e.target.closest('[data-tab]');if(t){liveInputRevision++;tab=t.dataset.tab;$$('[data-view]').forEach(v=>v.hidden=v.dataset.view!==tab);$$('[data-tab]').forEach(b=>b.classList.toggle('selected',b===t));$('#view-title').textContent=t.textContent;notify('');}
 const close=e.target.closest('[data-close]');if(close)close.closest('dialog').close();
 const p=e.target.closest('[data-edit]');if(p)openProduct(Number(p.dataset.edit));
 const o=e.target.closest('[data-order]');if(o)openOrder(Number(o.dataset.order));
 const remove=e.target.closest('[data-sale-remove]');if(remove){delete sale[remove.dataset.saleRemove];saleKey=crypto.randomUUID();renderSale();}
 const m=e.target.closest('[data-message]');if(m)await mutation('admin_message',{id:Number(m.dataset.message),status:m.dataset.state});
});
$('#new-product').onclick=()=>openProduct(0);
$('#product-search').oninput=()=>data&&renderProducts();
$('#order-filter').onchange=()=>data&&renderOrders();
$('#refresh').onclick=async()=>{try{await reload();notify('Datos actualizados.');}catch(e){notify(e.message,true);}};
$('#report-filter').onsubmit=async e=>{e.preventDefault();try{await reload();notify('Reporte actualizado.');}catch(e){notify(e.message,true);}};
$('#product-form').onsubmit=e=>{e.preventDefault();mutation('admin_product',Object.fromEntries(new FormData(e.target)),e.target);};
$('#order-form').onsubmit=e=>{e.preventDefault();mutation('admin_order',Object.fromEntries(new FormData(e.target)),e.target);};
$('#settings-form').onsubmit=e=>{e.preventDefault();mutation('admin_settings',Object.fromEntries(new FormData(e.target)),e.target);};
$('#smtp-form').onsubmit=e=>{e.preventDefault();mutation('admin_smtp',Object.fromEntries(new FormData(e.target)),e.target);};
$('#test-smtp').onclick=async()=>{if(busy)return;busy=true;$('#test-smtp').disabled=true;notify('Enviando prueba SMTP…');try{const d=await api('admin_smtp_test',{});notify(d.message);}catch(e){notify(e.message,true);}finally{busy=false;$('#test-smtp').disabled=false;}};
$('#sale-add').onclick=()=>{const id=$('#sale-product').value,n=Number($('#sale-quantity').value),p=data.products.find(p=>String(p.id)===id);if(!p||!Number.isInteger(n)||n<1||(sale[id]||0)+n>Math.min(99,p.stock)){notify('Revisa la cantidad y el stock.',true);return;}sale[id]=(sale[id]||0)+n;saleKey=crypto.randomUUID();renderSale();};
$('#sale-form').onsubmit=async e=>{e.preventDefault();const d=await mutation('admin_sale',{...Object.fromEntries(new FormData(e.target)),request_key:saleKey,items:Object.entries(sale).map(([id,quantity])=>({id:Number(id),quantity}))},e.target);if(d){sale={};saleKey=crypto.randomUUID();e.target.reset();renderSale();}};
$('#requests-list').onsubmit=e=>{e.preventDefault();const form=e.target;mutation('admin_request',{...Object.fromEntries(new FormData(form)),id:Number(form.dataset.request)},form);};
$('#export').onclick=()=>{
 const cell=v=>{let s=String(v??'');if(/^[=+@\-\t\r]/.test(s))s="'"+s;return '"'+s.replace(/"/g,'""')+'"';};
 const rows=[['Reporte de pagos recibidos','NIO','Zona: America/Managua'],['Desde',$('#report-filter [name=from]').value,'Hasta',$('#report-filter [name=to]').value],['Ventas','Total cobrado','Envío','Margen bruto conocido','Unidades sin costo'],[data.report.sales,data.report.revenue,data.report.shipping,data.report.known_margin,data.report.units_without_cost],[],['Producto','Unidades','Venta'],...data.top.map(p=>[p.product_name,p.quantity,p.revenue]),[],['Día','Ventas','Cobrado'],...data.daily.map(d=>[d.day,d.sales,d.revenue])];
 const url=URL.createObjectURL(new Blob(['\ufeff'+rows.map(r=>r.map(cell).join(',')).join('\r\n')],{type:'text/csv;charset=utf-8'})),a=document.createElement('a');a.href=url;a.download='tecnosoft-reporte.csv';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
};
(async()=>{const today=new Intl.DateTimeFormat('en-CA',{timeZone:'America/Managua',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());$('#report-filter [name=from]').value=today.slice(0,8)+'01';$('#report-filter [name=to]').value=today;try{const s=await api('session');if(!s.user?.is_admin)throw Error('Inicia sesión con una cuenta administradora.');await reload();}catch(e){notify(e.message,true);}})();

// Consultas de lectura mientras el administrador mantiene abierto el panel.
// Se pausa al editar para conservar los formularios y las ventas pendientes.
for(const eventName of ['input','change'])document.addEventListener(eventName,event=>{
 liveInputRevision++;
 const form=event.target.closest('form');
 if(form&&form.id!=='report-filter')liveDirtyForms.add(form);
});
document.addEventListener('reset',event=>{liveDirtyForms.delete(event.target);liveInputRevision++;},true);
document.addEventListener('close',event=>{if(event.target.matches('dialog')){for(const form of event.target.querySelectorAll('form'))liveDirtyForms.delete(form);liveInputRevision++;}},true);
async function pollAdminUpdates(){
 if(!data||refreshing)return;
 if(!canApplyLiveRefresh()){liveStatus('Actualización automática en pausa mientras editas.');return;}
 try{await reload({automatic:true});}
 catch(error){liveStatus('No se pudo actualizar. Se reintentará automáticamente.');}
}
setInterval(pollAdminUpdates,15000);
document.addEventListener('visibilitychange',()=>{if(!document.hidden)pollAdminUpdates();});
