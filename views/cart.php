<?php
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
  <h2>Carrito</h2>
  <?php if(empty($items)): ?>
    <p class="muted">Tu carrito está vacío.</p>
    <a class="btn" href="<?php echo FS_BASE_URL; ?>/index.php?page=catalog">Ir al catálogo</a>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>Producto</th><th>Precio</th><th>Cant.</th><th>Subtotal</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach($items as $it): ?>
            <tr>
              <td><?php echo htmlspecialchars($it['name']); ?></td>
              <td>$<?php echo number_format($it['price'],2); ?></td>
              <td><?php echo (int)$it['qty']; ?></td>
              <td>$<?php echo number_format($it['subtotal'],2); ?></td>
              <td><button class="btn secondary" data-remove data-id="<?php echo $it['id']; ?>">Quitar</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="summary">
      <strong>Total: $<?php echo number_format($total,2); ?></strong>
      <div class="controls">
        <button class="btn secondary" id="clear-cart">Vaciar</button>
        <a class="btn" href="<?php echo FS_BASE_URL; ?>/index.php?page=checkout">Proceder al pago</a>
      </div>
    </div>
  <?php endif; ?>
</section>
