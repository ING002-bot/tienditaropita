<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
fs_require_role(['cliente','empleado','admin']);

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;
foreach ($cart as $id => $qty) {
  $p = fs_find_product($id);
  if (!$p) continue;
  $line = [
    'id' => $id,
    'name' => $p['name'],
    'price' => $p['price'],
    'qty' => $qty,
    'subtotal' => $p['price'] * $qty,
  ];
  $total += $line['subtotal'];
  $items[] = $line;
}
?>
<section>
  <h2>Checkout</h2>
  <?php if(empty($items)): ?>
    <p class="muted">Tu carrito está vacío.</p>
    <a class="btn" href="<?php echo FS_BASE_URL; ?>/index.php?page=catalog">Ir al catálogo</a>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Producto</th><th>Precio</th><th>Cant.</th><th>Subtotal</th></tr></thead>
        <tbody>
          <?php foreach($items as $it): ?>
            <tr>
              <td><?php echo htmlspecialchars($it['name']); ?></td>
              <td>$<?php echo number_format($it['price'],2); ?></td>
              <td><?php echo (int)$it['qty']; ?></td>
              <td>$<?php echo number_format($it['subtotal'],2); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="summary"><strong>Total: $<?php echo number_format($total,2); ?></strong></div>

    <h3 style="margin-top:1rem">Pago con Yape</h3>
    <div class="card" style="padding:1rem">
      <p class="muted">Escanea el QR o paga al número Yape indicado. Luego sube el comprobante.</p>
      <div class="controls">
        <?php if(FS_YAPE_QR_URL): ?>
          <img src="<?php echo htmlspecialchars(FS_YAPE_QR_URL); ?>" alt="QR Yape" style="max-width:160px;border-radius:8px;border:1px solid rgba(255,255,255,.08)">
        <?php endif; ?>
        <?php if(FS_YAPE_NUMBER): ?>
          <div>Número Yape: <strong><?php echo htmlspecialchars(FS_YAPE_NUMBER); ?></strong></div>
        <?php endif; ?>
      </div>
      <form class="form" method="post" action="<?php echo FS_BASE_URL; ?>/api/checkout.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?php echo fs_csrf_token(); ?>">
        <input type="text" name="payment_ref" placeholder="Referencia de pago (opcional)">
        <input type="file" name="voucher" accept="image/*,application/pdf">
        <button class="btn" type="submit">Confirmar pedido</button>
      </form>
    </div>
  <?php endif; ?>
</section>
