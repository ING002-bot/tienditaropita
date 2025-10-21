<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
$u = fs_current_user();
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>
<section>
  <h2>¡Gracias por tu compra!</h2>
  <?php if($orderId>0): ?>
    <p>Tu pedido #<?php echo $orderId; ?> ha sido registrado. Validaremos el pago Yape en breve.</p>
    <div class="controls">
      <a class="btn" href="<?php echo FS_BASE_URL; ?>/api/order_pdf.php?order_id=<?php echo $orderId; ?>">Descargar boleta (PDF)</a>
      <a class="btn secondary" href="<?php echo FS_BASE_URL; ?>/index.php?page=catalog">Seguir comprando</a>
    </div>
  <?php else: ?>
    <p>Pedido registrado.</p>
  <?php endif; ?>
</section>
