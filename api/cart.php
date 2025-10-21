<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function cart_list(){
  $resp = ['items'=>[], 'total'=>0];
  foreach(($_SESSION['cart'] ?? []) as $id=>$qty){
    $p = fs_find_product($id);
    if(!$p) continue;
    $line = [
      'id'=>$id,
      'name'=>$p['name'],
      'price'=>$p['price'],
      'qty'=>$qty,
      'subtotal'=>$p['price']*$qty
    ];
    $resp['items'][]=$line;
    $resp['total']+=$line['subtotal'];
  }
  return $resp;
}

function cart_count(){
  $c=0; foreach(($_SESSION['cart'] ?? []) as $q){ $c+=$q; } return $c;
}

$payload = json_decode(file_get_contents('php://input'), true) ?: [];

switch($action){
  case 'add':
    $id = $payload['id'] ?? null; $qty = max(1, (int)($payload['qty'] ?? 1));
    $prod = $id ? fs_find_product($id) : null;
    if(!$prod){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Producto inválido']); exit; }
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    echo json_encode(['ok'=>true,'count'=>cart_count()]);
    break;
  case 'remove':
    $id = $payload['id'] ?? null; if($id && isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
    echo json_encode(['ok'=>true,'count'=>cart_count()]);
    break;
  case 'clear':
    $_SESSION['cart'] = [];
    echo json_encode(['ok'=>true,'count'=>0]);
    break;
  case 'count':
    echo json_encode(['count'=>cart_count()]);
    break;
  case 'list':
  default:
    echo json_encode(cart_list());
}
