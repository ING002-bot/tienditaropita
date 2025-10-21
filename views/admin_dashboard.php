<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
fs_require_role(['admin']);
$pdo = fs_pdo();
$stats = [
  'products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
  'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
  'orders_pending' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status='pending'")->fetchColumn(),
  'orders_paid' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status='paid'")->fetchColumn(),
  'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
  'low_stock' => (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 5 AND active=1')->fetchColumn(),
];
?>
<section>
  <h2>Panel de administración</h2>
  <div class="card-grid" style="margin-top:1rem">
    <div class="card"><div class="card-body"><strong>Productos</strong><div><?php echo $stats['products']; ?></div></div></div>
    <div class="card"><div class="card-body"><strong>Pedidos</strong><div><?php echo $stats['orders']; ?> totales</div><small class="muted">Pendientes: <?php echo $stats['orders_pending']; ?> · Pagados: <?php echo $stats['orders_paid']; ?></small></div></div>
    <div class="card"><div class="card-body"><strong>Usuarios</strong><div><?php echo $stats['users']; ?></div></div></div>
    <div class="card"><div class="card-body"><strong>Bajo stock</strong><div><?php echo $stats['low_stock']; ?></div></div></div>
  </div>
  <div class="controls" style="margin-top:1rem">
    <a class="btn" href="<?php echo FS_BASE_URL; ?>/index.php?page=admin_products">Gestionar productos</a>
    <a class="btn secondary" href="<?php echo FS_BASE_URL; ?>/index.php?page=admin_orders">Gestionar pedidos</a>
  </div>
</section>
