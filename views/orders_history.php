<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/orders.php';

$u = fs_current_user();
if(!$u){ header('Location: '.FS_BASE_URL.'/index.php?page=login'); exit; }
$pdo = fs_pdo();
$st = $pdo->prepare("SELECT o.id, o.status, o.total, o.created_at,
  ed.receiver_name AS delivery_receiver_name,
  ed.delivered_at AS delivery_time,
  ed.photo_path   AS delivery_photo,
  ed.comment      AS delivery_comment
  FROM orders o
  LEFT JOIN entregas ed ON ed.id = (SELECT MAX(e2.id) FROM entregas e2 WHERE e2.order_id = o.id)
  WHERE o.user_id=? ORDER BY o.id DESC");
$st->execute([(int)$u['id']]);
$orders = $st->fetchAll();

function oh_status_es($s){
  $map = ['pending'=>'pendiente','paid'=>'pagado','delivered'=>'entregado','cancelled'=>'cancelado','shipped'=>'entregado'];
  return $map[$s] ?? $s;
}
?>
<section>
  <h2>Mis pedidos</h2>
  <?php if(!$orders): ?>
    <p class="muted">Aún no tienes pedidos.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table" id="tbl-oh">
        <thead><tr><th>#</th><th>Fecha</th><th>Estado</th><th>Total</th><th>Entrega</th><th>Boleta</th></tr></thead>
        <tbody>
          <?php foreach($orders as $o): $hasDelivery = !empty($o['delivery_time']) || !empty($o['delivery_photo']); ?>
            <tr>
              <td><?php echo (int)$o['id']; ?></td>
              <td><?php echo htmlspecialchars($o['created_at']); ?></td>
              <td><?php echo htmlspecialchars(oh_status_es($o['status'] ?: ($hasDelivery?'delivered':''))); ?></td>
              <td>$<?php echo number_format($o['total'],2); ?></td>
              <td>
                <?php if($hasDelivery): ?>
                  <button class="btn secondary" data-photo="<?php echo $o['delivery_photo']? (FS_BASE_URL.'/'.$o['delivery_photo']) : ''; ?>" data-info="<?php echo htmlspecialchars(urlencode(json_encode(['recv'=>$o['delivery_receiver_name'],'time'=>$o['delivery_time'],'comment'=>$o['delivery_comment']]))); ?>">Ver</button>
                <?php endif; ?>
              </td>
              <td><a class="btn secondary" href="<?php echo FS_BASE_URL; ?>/api/order_pdf.php?order_id=<?php echo (int)$o['id']; ?>">PDF</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<div id="oh-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;padding:2rem">
  <div style="max-width:90vw;max-height:90vh;background:#0e121a;border:1px solid rgba(255,255,255,.08);border-radius:10px;position:relative;overflow:hidden">
    <button id="oh-close" class="btn secondary" style="position:absolute;top:.5rem;right:.5rem;z-index:2">Cerrar</button>
    <div id="oh-content" style="width:80vw;height:80vh;display:flex;align-items:center;justify-content:center;background:#000"></div>
  </div>
</div>
<script>
(function(){
  const modal=document.getElementById('oh-modal');
  const content=document.getElementById('oh-content');
  const closeBtn=document.getElementById('oh-close');
  document.querySelectorAll('#tbl-oh [data-photo]')?.forEach(b=>b.addEventListener('click',()=>{
    const url=b.getAttribute('data-photo');
    const info=JSON.parse(decodeURIComponent(b.getAttribute('data-info')||'%7B%7D'));
    content.innerHTML='';
    if(url){ const img=document.createElement('img'); img.src=url; img.style.maxWidth='80vw'; img.style.maxHeight='80vh'; content.appendChild(img); }
    const box=document.createElement('div');
    box.style.position='absolute'; box.style.bottom='0'; box.style.left='0'; box.style.right='0'; box.style.background='rgba(0,0,0,.6)'; box.style.color='#ddd'; box.style.padding='.75rem 1rem';
    box.innerHTML = `${info.time?`<div><strong>Fecha:</strong> ${escapeHtml(info.time)}</div>`:''}${info.recv?`<div><strong>Recibió:</strong> ${escapeHtml(info.recv)}</div>`:''}${info.comment?`<div><strong>Comentario:</strong> ${escapeHtml(info.comment)}</div>`:''}`;
    content.appendChild(box);
    modal.style.display='flex';
    const onEsc=(e)=>{ if(e.key==='Escape'){ close(); } };
    document.addEventListener('keydown', onEsc, {once:true});
    modal.addEventListener('click', (e)=>{ if(e.target===modal){ close(); } }, {once:true});
    closeBtn.onclick=close;
    function close(){ modal.style.display='none'; content.innerHTML=''; }
  }));
  function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",
    ">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));}
})();
</script>
