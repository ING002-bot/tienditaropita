<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
fs_require_role(['admin','empleado']);
$csrf = fs_csrf_token();
?>
<section>
  <h2>Pedidos (Admin)</h2>
  <div class="controls" style="margin-bottom:.5rem">
    <select id="f-status" class="input">
      <option value="">Todos los estados</option>
      <option value="pending">pendiente</option>
      <option value="paid">pagado</option>
      <option value="cancelled">cancelado</option>
      <option value="delivered">entregado</option>
    </select>
    <button class="btn" id="btn-filtrar">Filtrar</button>
  </div>
  <div class="table-wrap">
    <table class="table" id="tbl">
      <thead>
        <tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th>Total</th><th>Voucher</th><th>Acciones</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</section>

<!-- Modal visor de voucher -->
<div id="voucher-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;padding:2rem">
  <div id="voucher-box" style="max-width:90vw;max-height:90vh;background:#0e121a;border:1px solid rgba(255,255,255,.08);border-radius:10px;position:relative;overflow:hidden">
    <button id="voucher-close" class="btn secondary" style="position:absolute;top:.5rem;right:.5rem;z-index:2">Cerrar</button>
    <div id="voucher-content" style="width:80vw;height:80vh;display:flex;align-items:center;justify-content:center;background:#000"></div>
  </div>
</div>
<script>
(function(){
  const csrf = <?php echo json_encode($csrf); ?>;
  const tBody = document.querySelector('#tbl tbody');
  const fStatus = document.getElementById('f-status');
  const btn = document.getElementById('btn-filtrar');
  const modal = document.getElementById('voucher-modal');
  const box = document.getElementById('voucher-box');
  const content = document.getElementById('voucher-content');
  const closeBtn = document.getElementById('voucher-close');

  async function load(){
    const url = new URL('<?php echo FS_BASE_URL; ?>/api/admin_orders.php', location.origin);
    url.searchParams.set('action','list');
    if(fStatus.value) url.searchParams.set('status', fStatus.value);
    const res = await fetch(url); const data = await res.json();
    render(data.orders||[]);
  }

  function render(rows){
    tBody.innerHTML = '';
    rows.forEach(o=>{
      const tr = document.createElement('tr');
      const st = o.status || (o.delivery_time? 'delivered' : '');
      tr.innerHTML = `
        <td>${o.id}</td>
        <td>${escapeHtml(o.created_at||'')}</td>
        <td>${escapeHtml(o.user_name||'')}<br><small class="muted">${escapeHtml(o.user_email||'')}</small></td>
        <td>${escapeHtml(tStatus(st))}</td>
        <td>$${Number(o.total||0).toFixed(2)}</td>
        <td>
          ${o.voucher_path?`<button class="btn secondary" data-view="<?php echo FS_BASE_URL; ?>/${o.voucher_path}">Voucher</button>`:''}
          ${o.delivery_photo?` <button class="btn secondary" data-view="<?php echo FS_BASE_URL; ?>/${o.delivery_photo}">Foto</button>`:''}
          ${o.delivery_photo?` <button class="btn secondary" data-deliveryinfo='${encodeURIComponent(JSON.stringify({by:o.delivery_by,recv:o.delivery_receiver_name,time:o.delivery_time,comment:o.delivery_comment}))}'>Info</button>`:''}
        </td>
        <td>
          <div class="controls">
            ${st!=='paid' && st!=='delivered' && st!=='cancelled' ? `<button class="btn" data-paid='${o.id}'>Marcar pagado</button>` : ''}
            ${st!=='delivered' && st!=='cancelled' ? `<button class="btn secondary" data-reject='${o.id}'>Rechazar pago</button>` : ''}
          </div>
        </td>`;
      tBody.appendChild(tr);
    });
    document.querySelectorAll('[data-paid]')?.forEach(b=>b.addEventListener('click',()=>update(b.getAttribute('data-paid'),'paid')));
    document.querySelectorAll('[data-reject]')?.forEach(b=>b.addEventListener('click',()=>update(b.getAttribute('data-reject'),'cancelled')));
    document.querySelectorAll('[data-view]')?.forEach(b=>b.addEventListener('click',()=>openViewer(b.getAttribute('data-view'))));
    document.querySelectorAll('[data-deliveryinfo]')?.forEach(b=>b.addEventListener('click',()=>showDeliveryInfo(b.getAttribute('data-deliveryinfo'))));
  }

  async function update(id, status){
    const res = await fetch('<?php echo FS_BASE_URL; ?>/api/admin_orders.php',{
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'update_status', csrf, id:Number(id), status})
    });
    const data = await res.json(); if(data.ok){ load(); }
  }

  function openViewer(url){
    // limpiar contenido previo
    content.innerHTML = '';
    const isPdf = /\.pdf($|\?)/i.test(url);
    if(isPdf){
      const iframe = document.createElement('iframe');
      iframe.src = url;
      iframe.style.width='80vw';
      iframe.style.height='80vh';
      iframe.style.border='0';
      content.appendChild(iframe);
    } else {
      const img = document.createElement('img');
      img.src = url;
      img.style.maxWidth='80vw';
      img.style.maxHeight='80vh';
      img.style.display='block';
      content.appendChild(img);
    }
    modal.style.display = 'flex';
    const onEsc = (e)=>{ if(e.key==='Escape'){ closeViewer(); } };
    document.addEventListener('keydown', onEsc, {once:true});
    // Cerrar al hacer click fuera del cuadro
    modal.addEventListener('click', (e)=>{ if(e.target===modal){ closeViewer(); } }, {once:true});
    closeBtn.onclick = closeViewer;
    function closeViewer(){ modal.style.display='none'; content.innerHTML=''; }
  }

  function showDeliveryInfo(payload){
    try{
      const info = JSON.parse(decodeURIComponent(payload));
      const lines = [];
      if(info.time) lines.push(`<div><strong>Fecha:</strong> ${escapeHtml(info.time)}</div>`);
      if(info.recv) lines.push(`<div><strong>Recibió:</strong> ${escapeHtml(info.recv)}</div>`);
      if(info.by) lines.push(`<div><strong>Registrado por:</strong> ${escapeHtml(info.by)}</div>`);
      if(info.comment) lines.push(`<div><strong>Comentario:</strong> ${escapeHtml(info.comment)}</div>`);
      content.innerHTML = `<div style='padding:1rem;color:#ddd'>${lines.join('')}</div>`;
      modal.style.display = 'flex';
      const onEsc = (e)=>{ if(e.key==='Escape'){ close(); } };
      document.addEventListener('keydown', onEsc, {once:true});
      modal.addEventListener('click', (e)=>{ if(e.target===modal){ close(); } }, {once:true});
      closeBtn.onclick = close;
      function close(){ modal.style.display='none'; content.innerHTML=''; }
    }catch(e){/*noop*/}
  }

  function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",
    ">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));}

  function tStatus(s){
    const map = { pending:'pendiente', paid:'pagado', delivered:'entregado', cancelled:'cancelado', shipped:'entregado' };
    return map[s] || s;
  }

  btn.addEventListener('click', load);
  load();
})();
</script>
