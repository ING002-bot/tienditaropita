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
      <option value="pending">pending</option>
      <option value="paid">paid</option>
      <option value="shipped">shipped</option>
      <option value="cancelled">cancelled</option>
      <option value="refunded">refunded</option>
    </select>
    <select id="f-pay" class="input">
      <option value="">Todos los pagos</option>
      <option value="pending">pending</option>
      <option value="paid">paid</option>
      <option value="rejected">rejected</option>
    </select>
    <button class="btn" id="btn-filtrar">Filtrar</button>
  </div>
  <div class="table-wrap">
    <table class="table" id="tbl">
      <thead>
        <tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th>Pago</th><th>Total</th><th>Voucher</th><th>Acciones</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</section>
<script>
(function(){
  const csrf = <?php echo json_encode($csrf); ?>;
  const tBody = document.querySelector('#tbl tbody');
  const fStatus = document.getElementById('f-status');
  const fPay = document.getElementById('f-pay');
  const btn = document.getElementById('btn-filtrar');

  async function load(){
    const url = new URL('<?php echo FS_BASE_URL; ?>/api/admin_orders.php', location.origin);
    url.searchParams.set('action','list');
    if(fStatus.value) url.searchParams.set('status', fStatus.value);
    if(fPay.value) url.searchParams.set('payment', fPay.value);
    const res = await fetch(url); const data = await res.json();
    render(data.orders||[]);
  }

  function render(rows){
    tBody.innerHTML = '';
    rows.forEach(o=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${o.id}</td>
        <td>${escapeHtml(o.created_at||'')}</td>
        <td>${escapeHtml(o.user_name||'')}<br><small class="muted">${escapeHtml(o.user_email||'')}</small></td>
        <td>${escapeHtml(o.status||'')}</td>
        <td>${escapeHtml(o.payment_status||'')}</td>
        <td>$${Number(o.total||0).toFixed(2)}</td>
        <td>${o.voucher_path?`<a class="btn secondary" target="_blank" href="<?php echo FS_BASE_URL; ?>/${o.voucher_path}">Ver</a>`:''}</td>
        <td>
          <div class="controls">
            <button class="btn" data-paid='${o.id}'>Marcar pagado</button>
            <button class="btn secondary" data-reject='${o.id}'>Rechazar pago</button>
          </div>
        </td>`;
      tBody.appendChild(tr);
    });
    document.querySelectorAll('[data-paid]')?.forEach(b=>b.addEventListener('click',()=>update(b.getAttribute('data-paid'),'paid','paid')));
    document.querySelectorAll('[data-reject]')?.forEach(b=>b.addEventListener('click',()=>update(b.getAttribute('data-reject'),'cancelled','rejected')));
  }

  async function update(id, status, payment_status){
    const res = await fetch('<?php echo FS_BASE_URL; ?>/api/admin_orders.php',{
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'update_status', csrf, id:Number(id), status, payment_status})
    });
    const data = await res.json(); if(data.ok){ load(); }
  }

  function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",
    ">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));}

  btn.addEventListener('click', load);
  load();
})();
</script>
