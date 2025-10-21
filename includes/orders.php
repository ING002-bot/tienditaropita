<?php
require_once __DIR__ . '/db.php';

function fs_get_order(int $orderId) {
  $pdo = fs_pdo();
  $st = $pdo->prepare('SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=? LIMIT 1');
  $st->execute([$orderId]);
  $order = $st->fetch();
  if (!$order) return null;
  $sti = $pdo->prepare('SELECT oi.*, p.sku, p.name AS product_name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? ORDER BY oi.id ASC');
  $sti->execute([$orderId]);
  $order['items'] = $sti->fetchAll();
  return $order;
}
