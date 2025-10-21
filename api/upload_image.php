<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$u = fs_current_user();
if(!$u || $u['role_name']!=='admin'){
  http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error'=>'Acceso denegado']); exit;
}

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'){
  http_response_code(405); header('Allow: POST'); echo 'Método no permitido'; exit;
}

if(!fs_csrf_check($_POST['csrf'] ?? '')){ http_response_code(400); header('Content-Type: application/json'); echo json_encode(['error'=>'CSRF inválido']); exit; }

if(empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])){
  http_response_code(400); header('Content-Type: application/json'); echo json_encode(['error'=>'Archivo faltante']); exit;
}

$allowed = ['jpg','jpeg','png','gif','webp'];
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if(!in_array($ext, $allowed, true)){
  http_response_code(400); header('Content-Type: application/json'); echo json_encode(['error'=>'Extensión no permitida']); exit;
}

$dir = __DIR__ . '/../uploads/products';
if(!is_dir($dir)) { @mkdir($dir, 0777, true); }

$filename = 'p_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest = $dir . '/' . $filename;
if(!move_uploaded_file($_FILES['file']['tmp_name'], $dest)){
  http_response_code(500); header('Content-Type: application/json'); echo json_encode(['error'=>'No se pudo guardar']); exit;
}

header('Content-Type: application/json');
echo json_encode(['ok'=>true, 'path'=>'uploads/products/' . $filename]);
