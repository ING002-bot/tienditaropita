<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/products.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $_GET['action'] ?? $input['action'] ?? 'search';

try{
  if ($action === 'search'){
    $q = $_GET['q'] ?? '';
    $min = isset($_GET['min']) ? (float)$_GET['min'] : null;
    $max = isset($_GET['max']) ? (float)$_GET['max'] : null;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $size = isset($_GET['size']) ? (int)$_GET['size'] : 12;
    echo json_encode(fs_product_search(compact('q','min','max','page','size')));
    exit;
  }

  // Admin-only actions
  $u = fs_current_user();
  if(!$u || $u['role_name'] !== 'admin'){
    http_response_code(403); echo json_encode(['error'=>'Acceso denegado']); exit;
  }

  if ($action === 'upsert' && $method === 'POST'){
    if (!fs_csrf_check($input['csrf'] ?? '')){ http_response_code(400); echo json_encode(['error'=>'CSRF inválido']); exit; }
    $id = fs_admin_upsert_product($input);
    echo json_encode(['ok'=>true,'id'=>$id]);
    exit;
  }

  if ($action === 'delete' && $method === 'POST'){
    if (!fs_csrf_check($input['csrf'] ?? '')){ http_response_code(400); echo json_encode(['error'=>'CSRF inválido']); exit; }
    $id = (int)($input['id'] ?? 0); if($id<=0){ http_response_code(400); echo json_encode(['error'=>'ID inválido']); exit; }
    fs_admin_delete_product($id);
    echo json_encode(['ok'=>true]);
    exit;
  }

  http_response_code(400); echo json_encode(['error'=>'Acción no válida']);
}catch(Throwable $e){ http_response_code(500); echo json_encode(['error'=>'Error interno']); }
