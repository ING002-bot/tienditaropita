<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

// Solo admin y empleado
$u = fs_current_user();
if(!$u || !in_array($u['role_name'], ['admin','empleado'], true)){
  http_response_code(403);
  echo json_encode(['error'=>'Acceso denegado']);
  exit;
}

$pdo = fs_pdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? $input['action'] ?? 'fetch';

try{
  if($action === 'rooms'){
    $rows = $pdo->query('SELECT id, name FROM staff_rooms ORDER BY id ASC')->fetchAll();
    echo json_encode(['rooms'=>$rows]);
    exit;
  }

  if($action === 'send' && $method === 'POST'){
    $roomId = (int)($input['room_id'] ?? 1);
    $message = trim((string)($input['message'] ?? ''));
    if($message === ''){ http_response_code(400); echo json_encode(['error'=>'Mensaje vacío']); exit; }
    $st = $pdo->prepare('INSERT INTO staff_messages (room_id, user_id, message) VALUES (?,?,?)');
    $st->execute([$roomId, (int)$u['id'], $message]);
    echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
    exit;
  }

  if($action === 'fetch'){
    $roomId = (int)($_GET['room_id'] ?? ($input['room_id'] ?? 1));
    $afterId = (int)($_GET['after_id'] ?? ($input['after_id'] ?? 0));
    if($afterId > 0){
      $st = $pdo->prepare('SELECT m.id, m.user_id, u.name, m.message, m.created_at FROM staff_messages m JOIN users u ON u.id=m.user_id WHERE m.room_id=? AND m.id>? ORDER BY m.id ASC');
      $st->execute([$roomId, $afterId]);
    } else {
      $st = $pdo->prepare('SELECT m.id, m.user_id, u.name, m.message, m.created_at FROM staff_messages m JOIN users u ON u.id=m.user_id WHERE m.room_id=? ORDER BY m.id DESC LIMIT 100');
      $st->execute([$roomId]);
      $rows = array_reverse($st->fetchAll());
      echo json_encode(['messages'=>$rows]);
      exit;
    }
    echo json_encode(['messages'=>$st->fetchAll()]);
    exit;
  }

  http_response_code(400);
  echo json_encode(['error'=>'Acción no válida']);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['error'=>'Error interno']);
}
