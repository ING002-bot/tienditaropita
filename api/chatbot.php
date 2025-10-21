<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? $input['action'] ?? 'send';

$pdo = fs_pdo();

function ensure_session($pdo, $sessionKey) {
  $st = $pdo->prepare('SELECT id FROM chatbot_sessions WHERE session_key = ?');
  $st->execute([$sessionKey]);
  $row = $st->fetch();
  if ($row) return (int)$row['id'];
  $ins = $pdo->prepare('INSERT INTO chatbot_sessions (session_key, last_activity) VALUES (?, NOW())');
  $ins->execute([$sessionKey]);
  return (int)$pdo->lastInsertId();
}

function bot_reply($text) {
  $q = mb_strtolower(trim($text));
  $faqs = [
    ['k'=>['hola','buenas','hey'],'a'=>'¡Hola! Soy el asistente de Ferre Style. ¿En qué puedo ayudarte?'],
    ['k'=>['envio','envío','entrega'],'a'=>'Envíos a todo el país en 24-72h. Gratis desde $60.'],
    ['k'=>['devolucion','devolución','cambio'],'a'=>'Tienes 30 días para cambios o devoluciones. Solo conserva tu comprobante.'],
    ['k'=>['tallas','talle','size','medidas'],'a'=>'Nuestras tallas son estándar. Si dudas entre dos, elige la mayor.'],
    ['k'=>['pago','pagos','metodos'],'a'=>'Aceptamos tarjetas, transferencias y pagos digitales (MercadoPago).'],
    ['k'=>['horario','atencion','tienda'],'a'=>'Atención: L-V 9:00–18:00. Soporte online todos los días.'],
  ];
  foreach($faqs as $f){
    foreach($f['k'] as $k){ if (str_contains($q, $k)) return $f['a']; }
  }
  return 'No estoy seguro, pero puedo pasarte con un asesor. ¿Quieres que te contacte un agente humano?';
}

try {
  if ($action === 'send') {
    $sessionKey = $input['session'] ?? $_GET['session'] ?? '';
    $text = trim((string)($input['text'] ?? ''));
    if ($sessionKey === '' || $text === '') { http_response_code(400); echo json_encode(['error'=>'Parámetros inválidos']); exit; }
    $sid = ensure_session($pdo, $sessionKey);
    $pdo->beginTransaction();
    $ins = $pdo->prepare('INSERT INTO chatbot_messages (session_id, sender, message) VALUES (?,?,?)');
    $ins->execute([$sid, 'user', $text]);
    $reply = bot_reply($text);
    $ins->execute([$sid, 'bot', $reply]);
    $pdo->prepare('UPDATE chatbot_sessions SET last_activity = NOW() WHERE id = ?')->execute([$sid]);
    $pdo->commit();
    echo json_encode(['ok'=>true,'reply'=>$reply]);
    exit;
  }
  if ($action === 'history') {
    $sessionKey = $_GET['session'] ?? '';
    if ($sessionKey === '') { http_response_code(400); echo json_encode(['error'=>'Falta session']); exit; }
    $sid = ensure_session($pdo, $sessionKey);
    $st = $pdo->prepare('SELECT sender, message, created_at FROM chatbot_messages WHERE session_id = ? ORDER BY id ASC');
    $st->execute([$sid]);
    echo json_encode(['messages'=>$st->fetchAll()]);
    exit;
  }
  http_response_code(400);
  echo json_encode(['error'=>'Acción no válida']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'Error interno']);
}
