<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/orders.php';

$u = fs_current_user();
if(!$u){ header('Location: '.FS_BASE_URL.'/index.php?page=login'); exit; }
$pdo = fs_pdo();
$st = $pdo->prepare('SELECT id, status, total, payment_status, created_at FROM orders WHERE user_id=? ORDER BY id DESC');
$st->execute([(int)$u['id']]);
$orders = $st->fetchAll();
?>
<section>
  <h2>Mis pedidos</h2>
  <?php if(!$orders): ?>
    <p class="muted">Aún no tienes pedidos.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>#</th><th>Fecha</th><th>Estado</th><th>Pago</th><th>Total</th><th>Boleta</th></tr></thead>
        <tbody>
          <?php foreach($orders as $o): ?>
            <tr>
              <td><?php echo (int)$o['id']; ?></td>
              <td><?php echo htmlspecialchars($o['created_at']); ?></td>
              <td><?php echo htmlspecialchars($o['status']); ?></td>
              <td><?php echo htmlspecialchars($o['payment_status']); ?></td>
              <td>$<?php echo number_format($o['total'],2); ?></td>
              <td><a class="btn secondary" href="<?php echo FS_BASE_URL; ?>/api/order_pdf.php?order_id=<?php echo (int)$o['id']; ?>">PDF</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
