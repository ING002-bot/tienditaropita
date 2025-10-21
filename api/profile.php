<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$u = fs_current_user();
if(!$u){ http_response_code(401); echo 'No autenticado'; exit; }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if($method !== 'POST'){ http_response_code(405); echo 'Método no permitido'; exit; }

if(!fs_csrf_check($_POST['csrf'] ?? '')){ http_response_code(400); echo 'CSRF inválido'; exit; }

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
if($name===''){ http_response_code(400); echo 'Nombre requerido'; exit; }
if($email===''){ http_response_code(400); echo 'Email requerido'; exit; }

try{
  $pdo = fs_pdo();
  // Verificar email duplicado (si lo cambió)
  $st = $pdo->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
  $st->execute([$email, (int)$u['id']]);
  if($st->fetch()){
    http_response_code(409); echo 'El correo ya está en uso'; exit;
  }
  $upd = $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?');
  $upd->execute([$name, $email, (int)$u['id']]);
  // Actualizar datos en sesión
  $_SESSION['user']['name'] = $name;
  $_SESSION['user']['email'] = $email;
  header('Location: ' . FS_BASE_URL . '/index.php?page=profile&ok=1');
} catch(Throwable $e){
  http_response_code(500);
  echo 'Error interno';
}
