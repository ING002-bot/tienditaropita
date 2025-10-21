<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

function fs_product_search(array $opts){
  $pdo = fs_pdo();
  $q = trim((string)($opts['q'] ?? ''));
  $min = isset($opts['min']) ? (float)$opts['min'] : null;
  $max = isset($opts['max']) ? (float)$opts['max'] : null;
  $page = max(1, (int)($opts['page'] ?? 1));
  $size = min(48, max(1, (int)($opts['size'] ?? 12)));
  $off = ($page-1)*$size;
  $where = ['active=1']; $params = [];
  if ($q !== '') { $where[] = '(name LIKE ? OR sku LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
  if ($min !== null) { $where[] = 'price >= ?'; $params[] = $min; }
  if ($max !== null) { $where[] = 'price <= ?'; $params[] = $max; }
  $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
  $st = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id, sku, name, price, stock, image FROM products $sqlWhere ORDER BY id DESC LIMIT $size OFFSET $off");
  $st->execute($params);
  $rows = $st->fetchAll();
  $total = (int)$pdo->query('SELECT FOUND_ROWS()')->fetchColumn();
  return ['items'=>$rows, 'total'=>$total, 'page'=>$page, 'size'=>$size];
}

function fs_admin_upsert_product(array $p){
  $pdo = fs_pdo();
  $id = isset($p['id']) ? (int)$p['id'] : 0;
  $sku = trim((string)($p['sku'] ?? ''));
  $name = trim((string)($p['name'] ?? ''));
  $price = (float)($p['price'] ?? 0);
  $stock = (int)($p['stock'] ?? 0);
  $image = (string)($p['image'] ?? null);
  $active = isset($p['active']) ? (int)$p['active'] : 1;
  if ($sku === '' || $name === '') throw new Exception('SKU y nombre requeridos');
  if ($id>0){
    $st = $pdo->prepare('UPDATE products SET sku=?, name=?, price=?, stock=?, image=?, active=? WHERE id=?');
    $st->execute([$sku,$name,$price,$stock,$image,$active,$id]);
    return $id;
  } else {
    $st = $pdo->prepare('INSERT INTO products (sku,name,price,stock,image,active) VALUES (?,?,?,?,?,?)');
    $st->execute([$sku,$name,$price,$stock,$image,$active]);
    return (int)$pdo->lastInsertId();
  }
}

function fs_admin_delete_product(int $id){
  $pdo = fs_pdo();
  $st = $pdo->prepare('DELETE FROM products WHERE id=?');
  $st->execute([$id]);
}

function fs_admin_import_json(){
  $file = FS_PRODUCTS_FILE;
  if(!file_exists($file)) return 0;
  $data = json_decode(file_get_contents($file), true) ?: [];
  $pdo = fs_pdo(); $count=0;
  foreach($data as $p){
    $sku = (string)$p['id'];
    $st = $pdo->prepare('SELECT id FROM products WHERE sku=?');
    $st->execute([$sku]);
    if($st->fetch()) continue;
    $ins = $pdo->prepare('INSERT INTO products (sku,name,price,stock,image,active) VALUES (?,?,?,?,?,1)');
    $ins->execute([$sku,$p['name'],(float)$p['price'],9999,$p['image'] ?? null]);
    $count++;
  }
  return $count;
}
