const S=[['192.168.2.178','IFACTURE',['Comprobantes33','ComprobantesPagos','Data']],['192.168.2.203','AUDICTAMEN',['Audictamen','datafiscal','datahub','MSSQL']],['192.168.1.2','Compaqi (Tulum)',['Compac','Data']],['192.168.2.210','Compaqi (Mérida)',['base_contabilidad','base_nominas','Carpeta_Contpaq','Compac']],['192.168.2.252','Compaqi (Auditoría)',['Compac','Data']]];
const q=s=>document.querySelector(s),esc=(s='')=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fresh=name=>({name,logName:name,available:false,done:false,date:'',errors:[],issues:[],failed:false,message:'Leyendo',processed:'',source:'',destination:'',duration:''});
let state=[],generatedAt=new Date(),reportId='',reading=false,saving=false,dirty=false,seen=new Set(),selected=null,selectedServer=null,epoch=0;
const isBad=p=>p.failed||p.errors.length>0||p.issues.length>0;
const list=t=>t.split(/[\n;]+/).map(x=>x.trim()).filter(Boolean);
function defaults(){return S.map(([ip,name,profiles])=>({sourceIp:ip,ip,name,profiles:profiles.map(fresh)}))}
function filename(){let d=new Date();return `Reporte_de_Respaldos_${String(d.getDate()).padStart(2,'0')}-${String(d.getMonth()+1).padStart(2,'0')}-${d.getFullYear()}_${String(d.getTime()).slice(-6)}`}
const key=(s,p)=>s.sourceIp+'|'+p.logName;
function counts(s){return [s.profiles.filter(p=>p.available&&!isBad(p)).length,s.profiles.filter(p=>p.available&&isBad(p)).length,s.profiles.filter(p=>!p.available).length]}
function graph(){
  const max=Math.max(1,...state.map(s=>s.profiles.length)),height=state.length*25+5;
  return `<svg class="chart" viewBox="0 0 720 ${height}" role="img" aria-labelledby="chartTitle chartDesc"><title id="chartTitle">Estado de perfiles por servidor</title><desc id="chartDesc">Perfiles correctos, con error y sin datos. Cada segmento usa la misma escala.</desc>${state.map((s,i)=>{
    let [ok,bad,unknown]=counts(s),y=i*25+5,x=164,parts='';
    [[ok,'#285b72'],[bad,'#aa4533'],[unknown,'#c48b18']].forEach(([n,color])=>{let w=n/max*235;if(w)parts+=`<rect x="${x}" y="${y}" width="${w}" height="12" fill="${color}"/>`;x+=w});
    let name=s.name.replace('Compaqi (','').replace(')','');
    return `<text x="0" y="${y+10}" font-family="Courier New, Courier, monospace" font-size="11" fill="#1c2938">${esc(name)}</text>${parts}<text x="418" y="${y+10}" font-family="Courier New, Courier, monospace" font-size="10" fill="#667585">${ok} correctos / ${bad} error / ${unknown} sin datos</text>`
  }).join('')}</svg>`;
}
function incident(p){
  if(!p.available||!isBad(p))return '';
  return `<div class="incidents"><p class="incident-label">Pendientes / incidencias:</p>${p.issues.map(v=>`<p>${esc(v)}</p>`).join('')}${p.errors.length?`<ul>${p.errors.map(v=>`<li>${esc(v.replace(/_/g,' '))}</li>`).join('')}</ul>`:''}${!p.issues.length&&!p.errors.length?'<p>Error indicado por el responsable.</p>':''}${p.issues.some(v=>/configuraci.n inv.lida|directorio.*inv.lido/i.test(v))&&p.destination?`<p class="technical-path">Origen: ${esc(p.source)}<br>Destino: ${esc(p.destination)}</p>`:''}</div>`;
}
function render(){
  let all=state.flatMap(s=>s.profiles),known=all.filter(p=>p.available),bad=known.filter(isBad),included=state.filter(s=>s.profiles.some(p=>p.available));
  q('#docMeta').innerHTML=`<span>${esc(BackupPDF.longDate(generatedAt))}</span><span>${esc(reportId?reportId+'.pdf':'')}</span>`;
  q('#metrics').innerHTML=[['Servidores con datos',included.length],['Perfiles revisados',known.length],['Perfiles con error',bad.length],['Empresas afectadas',bad.reduce((n,p)=>n+p.errors.length,0)]].map(([label,n])=>`<div class="metric"><strong>${n}</strong><span>${label}</span></div>`).join('');
  q('#chart').innerHTML=graph();
  q('#coverage').textContent=`Cobertura: ${known.length} de ${all.length} perfiles con datos. Los pendientes o inaccesibles no se contabilizan como errores de respaldo.`;
  q('#results').innerHTML=state.map((s,si)=>{
    let available=s.profiles.filter(p=>p.available),errors=available.filter(isBad),pending=s.profiles.some(p=>!p.done),label=pending?'Leyendo':!available.length?'Sin datos':errors.length?'Con incidencias':'Todo realizado';
    return `<section class="server ${available.length?'':'omit-print'}"><header class="server-head"><h3>${esc(s.name)}<small>${esc(s.ip)}</small></h3><div><span class="status ${pending||!available.length?'unknown':errors.length?'bad':'ok'}">${label}</span> <button class="edit-only edit-button" data-action="server" data-si="${si}">Editar servidor</button></div></header><div class="responsive-table"><table><thead><tr><th>Perfil</th><th>Última ejecución</th><th>Hecho</th><th>Resultado</th><th class="edit-only capture"></th></tr></thead>${s.profiles.map((p,pi)=>{
      const detail=incident(p);
      return `<tbody class="profile-group ${p.available?'':'not-included'}"><tr class="profile-row"><td>${esc(p.name)}</td><td>${p.available?esc(p.date||'Sin fecha'):p.done?'Sin datos':'Leyendo…'}</td><td class="mono">${p.available?esc(p.processed||'—'):'—'}</td><td class="${p.available?(isBad(p)?'bad':'ok'):'unknown'}">${p.available?(isBad(p)?'Error':p.processed==='0/0'?'Sin cambios':'Todo realizado'):p.done?'No disponible':'Pendiente'}</td><td class="edit-only capture"><button class="edit-button" data-action="profile" data-si="${si}" data-pi="${pi}">Editar</button></td></tr>${detail?`<tr class="profile-details"><td colspan="5">${detail}</td></tr>`:''}</tbody>`
    }).join('')}</table></div><div class="edit-only capture server-add"><button class="edit-button" data-action="addProfile" data-si="${si}">Agregar perfil</button></div></section>`
  }).join('');
  q('#print').disabled=saving||!known.length;
}
function openProfile(si,pi,isNew=false){
  let s=state[si],p=isNew?{...fresh(''),done:true,available:true,message:''}:s.profiles[pi];selected={s,p,isNew};
  q('#profileName').value=p.name;q('#profileDate').value=p.date;q('#profileErrors').value=p.errors.join('\n');q('#profileIssues').value=p.issues.join('\n');q('#profileInclude').checked=p.available;q('#deleteProfile').hidden=isNew;
  q('#technical').innerHTML=[['Origen',p.source],['Destino',p.destination],['Hecho',p.processed],['Duración',p.duration],['Lectura',p.readSeconds!==undefined?p.readSeconds+' s':''],['Transferencia',p.totalBytes?`${Math.round(p.readBytes/1024)} KB leídos / ${Math.round(p.totalBytes/1024)} KB de historial`:''],['Aviso',p.message]].map(([k,v])=>`<dt>${k}</dt><dd>${esc(v||'—')}</dd>`).join('');q('#profileDialog').showModal();
}
q('#profileForm').onsubmit=e=>{e.preventDefault();let {s,p,isNew}=selected;p.name=q('#profileName').value.trim();p.date=q('#profileDate').value.trim();p.errors=list(q('#profileErrors').value);p.issues=list(q('#profileIssues').value);p.failed=!!(p.errors.length||p.issues.length);p.available=q('#profileInclude').checked;p.done=true;p.message=p.available?'Ajustado por el responsable':'Excluido por el responsable';if(isNew){p.logName='manual-'+Date.now();s.profiles.push(p)}seen.add(key(s,p));dirty=true;q('#profileDialog').close();render()};
q('#deleteProfile').onclick=()=>{if(!confirm('¿Eliminar este perfil del reporte?'))return;let {s,p}=selected;seen.add(key(s,p));s.profiles.splice(s.profiles.indexOf(p),1);dirty=true;q('#profileDialog').close();render()};q('#closeProfile').onclick=()=>q('#profileDialog').close();
function openServer(si){selectedServer=si===null?null:state[si];q('#serverDialogTitle').textContent=selectedServer?'Editar servidor':'Agregar servidor';q('#serverName').value=selectedServer?.name||'';q('#serverIp').value=selectedServer?.ip||'';q('#deleteServer').hidden=!selectedServer;q('#serverDialog').showModal()}
q('#serverForm').onsubmit=e=>{e.preventDefault();if(selectedServer){selectedServer.name=q('#serverName').value.trim();selectedServer.ip=q('#serverIp').value.trim()}else state.push({sourceIp:'manual-'+Date.now(),name:q('#serverName').value.trim(),ip:q('#serverIp').value.trim(),profiles:[{...fresh('Nuevo perfil'),done:true,available:true,message:''}]});dirty=true;q('#serverDialog').close();render()};q('#deleteServer').onclick=()=>{if(confirm('¿Eliminar este servidor del reporte?')){state.splice(state.indexOf(selectedServer),1);dirty=true;q('#serverDialog').close();render()}};q('#closeServer').onclick=()=>q('#serverDialog').close();q('#addServer').onclick=()=>openServer(null);
q('#results').onclick=e=>{let b=e.target.closest('[data-action]');if(!b)return;let si=+b.dataset.si;switch(b.dataset.action){case'profile':openProfile(si,+b.dataset.pi);break;case'addProfile':openProfile(si,0,true);break;case'server':openServer(si);break}};
q('#edit').onclick=()=>{let editing=document.body.classList.toggle('editing');q('#edit').textContent=editing?'Terminar edición':'Editar';q('#edit').setAttribute('aria-pressed',String(editing))};
async function request(path){let r=await fetch(path,{cache:'no-store'});if(!r.ok)throw new Error('El lector local no respondió');let x=await r.json();if(x.version!==3)throw new Error('Cierra la ventana negra anterior y vuelve a abrir el programa actualizado');return x}
function merge(x){x.servers.forEach(server=>{let s=state.find(s=>s.sourceIp===server.ip);if(!s)return;server.profiles.forEach(p=>{let current=s.profiles.find(z=>z.logName===p.profile);if(!current||!p.done||seen.has(key(s,current)))return;Object.assign(current,{...p,name:current.name,logName:current.logName,errors:p.errors||[],issues:p.issues||[],failed:!!p.failed});seen.add(key(s,current))})});render();q('#progress').max=x.totalProfiles;q('#progress').value=x.doneProfiles;q('#count').textContent=`${x.doneProfiles} / ${x.totalProfiles}`;q('#readStatus').textContent=S.map(([ip,name])=>{let snapshot=x.servers.find(s=>s.ip===ip),n=snapshot?.profiles.filter(p=>p.done).length||0,total=S.find(s=>s[0]===ip)[2].length;return `${name.replace('Compaqi (','').replace(')','')} ${n}/${total}`}).join(' · ')}
async function readLogs(){if(reading)return;if(dirty&&!confirm('Una nueva lectura reemplazará los ajustes de este reporte. ¿Continuar?'))return;const run=++epoch;reading=true;dirty=false;seen=new Set();state=defaults();generatedAt=new Date();reportId=filename();q('#read').disabled=true;q('#cancel').hidden=false;render();try{let x=await request('/api/start'),op=x.operation;while(run===epoch){merge(x);q('#notice').textContent=x.done===x.total?'Lectura terminada. Puedes revisar o editar el reporte.':'Leyendo perfiles por separado - sin límite de espera.';if(x.done===x.total)break;await new Promise(r=>setTimeout(r,1000));if(run!==epoch)break;x=await request('/api/status');if(x.operation!==op)throw new Error('Otra ventana inició una lectura. Actualiza para continuar')}}catch(e){if(run===epoch){q('#notice').textContent=e.message;state.forEach(s=>s.profiles.forEach(p=>{if(!p.done){p.done=true;p.message='Lectura interrumpida'}}));render()}}finally{if(run===epoch){reading=false;q('#read').disabled=false;q('#cancel').hidden=true}}}
q('#cancel').onclick=async()=>{++epoch;try{let x=await request('/api/cancel');merge(x);q('#notice').textContent='Lectura detenida. Se conservaron los resultados disponibles.'}catch(e){q('#notice').textContent=e.message}finally{reading=false;q('#read').disabled=false;q('#cancel').hidden=true}};
q('#read').onclick=readLogs;
q('#print').onclick=async()=>{
  if(saving)return;saving=true;generatedAt=new Date();reportId=filename();q('#print').textContent='Generando…';render();
  try{
    const bytes=await BackupPDF.createReport(JSON.parse(JSON.stringify(state)),{filename:reportId+'.pdf',generatedAt});
    const url=URL.createObjectURL(new Blob([bytes],{type:'application/pdf'})),link=document.createElement('a');
    link.href=url;link.download=reportId+'.pdf';document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);
  }catch(e){q('#notice').textContent='No se pudo generar el PDF: '+e.message}
  finally{saving=false;q('#print').textContent='Guardar PDF';render()}
};
state=defaults();render();readLogs();
