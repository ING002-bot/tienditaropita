<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
fs_require_role(['admin','empleado']);
?>
<section>
  <h2>Confirmar entrega de pedido</h2>
  <?php if(isset($_GET['ok'])): ?><div class="alert success">Entrega registrada.</div><?php endif; ?>
  <form class="form" method="post" action="<?php echo FS_BASE_URL; ?>/api/deliveries.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo fs_csrf_token(); ?>">

    <label>ID del pedido</label>
    <div class="controls">
      <select class="input" id="order_id" name="order_id" required></select>
      <button class="btn secondary" type="button" id="reload-orders">Recargar</button>
    </div>

    <label>Nombre de quien recibió el pedido</label>
    <input class="input" type="text" name="receiver_name" placeholder="Nombre completo" required>

    <label>Fecha y hora de entrega</label>
    <input class="input" type="text" value="<?php echo date('Y-m-d H:i:s'); ?>" disabled>

    <label>Foto del cliente recibiendo el producto</label>
    <input class="input" type="file" name="photo" accept="image/*">

    <label>Comentario (opcional)</label>
    <textarea class="input" name="comment" rows="3" placeholder="Observaciones"></textarea>

    <div class="controls"><button class="btn" type="submit">Confirmar entrega</button></div>
  </form>
</section>
<script>
(function(){
  const sel = document.getElementById('order_id');
  const btn = document.getElementById('reload-orders');
  async function load(){
    const url = new URL('<?php echo FS_BASE_URL; ?>/api/admin_orders.php', location.origin);
    url.searchParams.set('action','list');
    url.searchParams.set('status','paid'); // pedidos verificados, pendientes de entrega
    const res = await fetch(url);
    const data = await res.json();
    const orders = (data.orders||[]).sort((a,b)=>b.id-a.id);
    sel.innerHTML = '';
    orders.forEach(o=>{
      const opt = document.createElement('option');
      opt.value = o.id; opt.textContent = `#${o.id}`; sel.appendChild(opt);
    });
  }
  btn.addEventListener('click', load);
  load();
})();
</script>
