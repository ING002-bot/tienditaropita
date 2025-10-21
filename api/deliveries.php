<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$u = fs_current_user();
if(!$u || !in_array($u['role_name'], ['admin','empleado'], true)){
  http_response_code(403); echo 'Acceso denegado'; exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'){
  http_response_code(405); echo 'Método no permitido'; exit;
}

if(!fs_csrf_check($_POST['csrf'] ?? '')){ http_response_code(400); echo 'CSRF inválido'; exit; }

$order_id = (int)($_POST['order_id'] ?? 0);
$receiver_name = trim((string)($_POST['receiver_name'] ?? ''));
$comment = trim((string)($_POST['comment'] ?? ''));
$delivered_at = date('Y-m-d H:i:s'); // automática

if($order_id<=0 || $receiver_name===''){ http_response_code(400); echo 'Datos inválidos'; exit; }

$pdo = fs_pdo();
// validar que el pedido exista y esté al menos pagado o pendiente de entrega
$st = $pdo->prepare('SELECT id FROM orders WHERE id=? LIMIT 1');
$st->execute([$order_id]);
if(!$st->fetch()){ http_response_code(404); echo 'Pedido no encontrado'; exit; }

// subir foto si viene
$photoPath = null;
if(!empty($_FILES['photo']) && is_uploaded_file($_FILES['photo']['tmp_name'])){
  $allowed = ['jpg','jpeg','png','webp'];
  $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
  if(!in_array($ext,$allowed,true)){ http_response_code(400); echo 'Formato de imagen no permitido'; exit; }
  $dir = __DIR__ . '/../uploads/entregas';
  if(!is_dir($dir)) { @mkdir($dir, 0777, true); }
  $filename = 'entrega_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  $dest = $dir . '/' . $filename;
  if(!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)){
    http_response_code(500); echo 'No se pudo guardar la imagen'; exit;
  }
  $photoPath = 'uploads/entregas/' . $filename;
}

try{
  $ins = $pdo->prepare('INSERT INTO entregas (order_id,user_id,receiver_name,delivered_at,photo_path,comment) VALUES (?,?,?,?,?,?)');
  $ins->execute([$order_id,(int)$u['id'],$receiver_name,$delivered_at,$photoPath,$comment?:null]);
  // actualizar estado del pedido a "delivered"
  $pdo->prepare("UPDATE orders SET status='delivered' WHERE id=? AND status<>'delivered'")->execute([$order_id]);
  header('Location: ' . FS_BASE_URL . '/index.php?page=confirm_delivery&ok=1');
} catch(Throwable $e){
  http_response_code(500); echo 'Error interno';
}
