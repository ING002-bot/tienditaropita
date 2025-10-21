<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$u = fs_current_user();
if(!$u || !in_array($u['role_name'], ['admin','empleado'], true)){
  http_response_code(403); echo json_encode(['error'=>'Acceso denegado']); exit;
}

$pdo = fs_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? $input['action'] ?? 'list';

try{
  if ($action === 'list'){
    $status = $_GET['status'] ?? '';
    $pay = $_GET['payment'] ?? '';
    $where = [];$params=[];
    if($status!==''){ $where[]='o.status=?'; $params[]=$status; }
    if($pay!==''){ $where[]='o.payment_status=?'; $params[]=$pay; }
    $sqlWhere = $where?('WHERE '.implode(' AND ',$where)) : '';
    $st = $pdo->prepare("SELECT o.id,o.user_id,u.name AS user_name,u.email AS user_email,o.status,o.payment_status,o.total,o.created_at,o.payment_ref,o.voucher_path FROM orders o JOIN users u ON u.id=o.user_id $sqlWhere ORDER BY o.id DESC LIMIT 200");
    $st->execute($params);
    echo json_encode(['orders'=>$st->fetchAll()]);
    exit;
  }

  if ($action === 'update_status' && $method==='POST'){
    if(!fs_csrf_check($input['csrf'] ?? '')){ http_response_code(400); echo json_encode(['error'=>'CSRF inválido']); exit; }
    $id = (int)($input['id'] ?? 0);
    $status = (string)($input['status'] ?? 'pending');
    $pay = (string)($input['payment_status'] ?? 'pending');
    if($id<=0){ http_response_code(400); echo json_encode(['error'=>'ID inválido']); exit; }

    $pdo->beginTransaction();
    $cur = $pdo->prepare('SELECT payment_status FROM orders WHERE id=? FOR UPDATE');
    $cur->execute([$id]);
    $row = $cur->fetch();
    if(!$row){ $pdo->rollBack(); http_response_code(404); echo json_encode(['error'=>'Pedido no encontrado']); exit; }
    $wasPaid = ($row['payment_status']==='paid');

    $up = $pdo->prepare('UPDATE orders SET status=?, payment_status=? WHERE id=?');
    $up->execute([$status,$pay,$id]);

    if(!$wasPaid && $pay==='paid'){
      // descontar stock una sola vez cuando pasa a "paid"
      $sti = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id=?');
      $sti->execute([$id]);
      $rows = $sti->fetchAll();
      foreach($rows as $it){
        $upd = $pdo->prepare('UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id=?');
        $upd->execute([(int)$it['quantity'], (int)$it['product_id']]);
      }
    }

    $pdo->commit();
    echo json_encode(['ok'=>true]);
    exit;
  }

  http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
}catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'Error interno']); }
