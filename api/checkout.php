<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

function redirect_to($page, $params=[]) {
  $q = http_build_query($params);
  header('Location: ' . FS_BASE_URL . '/index.php?page=' . urlencode($page) . ($q?('&'.$q):''));
  exit;
}

$u = fs_current_user();
if(!$u){ redirect_to('login', ['error'=>'Inicia sesión para continuar']); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  redirect_to('checkout');
}

$csrf = $_POST['csrf'] ?? '';
if (!fs_csrf_check($csrf)) { redirect_to('checkout', ['error'=>'CSRF inválido']); }

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) { redirect_to('cart', ['error'=>'Carrito vacío']); }

$pdo = fs_pdo();

// Construir ítems desde el catálogo JSON y asegurar productos en DB por SKU (id de JSON como sku)
$items = [];
$total = 0;
foreach ($cart as $id => $qty) {
  $p = fs_find_product($id);
  if (!$p) continue;
  $sku = (string)$id;
  $name = $p['name'];
  $price = (float)$p['price'];
  $qty = max(1, (int)$qty);
  // upsert producto por sku
  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare('SELECT id FROM products WHERE sku = ? LIMIT 1');
    $st->execute([$sku]);
    $row = $st->fetch();
    if ($row) {
      $prodId = (int)$row['id'];
      $up = $pdo->prepare('UPDATE products SET name=?, price=?, image=? WHERE id=?');
      $up->execute([$name, $price, $p['image'] ?? null, $prodId]);
    } else {
      $ins = $pdo->prepare('INSERT INTO products (sku, name, description, price, stock, image, active) VALUES (?,?,?,?,?,?,1)');
      $ins->execute([$sku, $name, null, $price, 9999, $p['image'] ?? null]);
      $prodId = (int)$pdo->lastInsertId();
    }
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack(); throw $e;
  }
  $line = [
    'product_id' => $prodId,
    'name' => $name,
    'price' => $price,
    'qty' => $qty,
    'subtotal' => $price * $qty,
  ];
  $total += $line['subtotal'];
  $items[] = $line;
}

if (empty($items)) { redirect_to('cart', ['error'=>'Carrito inválido']); }

// Manejo de voucher
$payment_ref = trim((string)($_POST['payment_ref'] ?? ''));
$voucherPath = null;
if (!empty($_FILES['voucher']) && is_uploaded_file($_FILES['voucher']['tmp_name'])) {
  $dir = __DIR__ . '/../uploads/vouchers';
  if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
  $ext = pathinfo($_FILES['voucher']['name'], PATHINFO_EXTENSION);
  if ($ext === '') $ext = 'bin';
  $tmpName = $_FILES['voucher']['tmp_name'];
  $filename = 'voucher_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . preg_replace('/[^a-zA-Z0-9]+/','', $ext);
  $dest = $dir . '/' . $filename;
  if (move_uploaded_file($tmpName, $dest)) {
    $voucherPath = 'uploads/vouchers/' . $filename;
  }
}

// Crear pedido
$pdo->beginTransaction();
try {
  $st = $pdo->prepare('INSERT INTO orders (user_id, status, total, payment_method, payment_status, payment_ref, voucher_path) VALUES (?,?,?,?,?,?,?)');
  $st->execute([(int)$u['id'], 'pending', $total, 'yape', $voucherPath ? 'pending' : 'pending', $payment_ref ?: null, $voucherPath]);
  $orderId = (int)$pdo->lastInsertId();

  $sti = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?,?,?,?,?)');
  foreach ($items as $it) {
    $sti->execute([$orderId, $it['product_id'], $it['qty'], $it['price'], $it['subtotal']]);
  }
  $pdo->commit();

  // Limpiar carrito
  $_SESSION['cart'] = [];

  redirect_to('order_success', ['order_id' => $orderId]);
} catch (Throwable $e) {
  $pdo->rollBack();
  redirect_to('checkout', ['error'=>'No se pudo crear el pedido']);
}
