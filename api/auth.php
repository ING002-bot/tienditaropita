<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

function redirect_with($page, $params = []){
  $q = http_build_query($params);
  $loc = FS_BASE_URL . '/index.php?page=' . urlencode($page) . ($q ? ('&'.$q) : '');
  header('Location: ' . $loc);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
  if ($method === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!fs_csrf_check($csrf)) {
      throw new Exception('CSRF inválido');
    }
  }

  switch ($action) {
    case 'register':
      if ($method !== 'POST') throw new Exception('Método no permitido');
      $name = trim((string)($_POST['name'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $password = (string)($_POST['password'] ?? '');
      $role = (string)($_POST['role'] ?? 'cliente');

      if ($name === '' || $email === '' || $password === '') throw new Exception('Datos incompletos');

      // Bootstrap de primer usuario: si no hay usuarios, crear admin
      $pdo = fs_pdo();
      $hasUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
      if (!$hasUsers) { $role = 'admin'; }

      // Si no es admin, solo puede asignar "cliente" para sí mismo
      $current = fs_current_user();
      if ($role !== 'cliente') {
        if (!$current || $current['role_name'] !== 'admin') {
          $role = 'cliente';
        }
      }

      fs_register($name, $email, $password, $role);
      // Autologin tras registro
      fs_login($email, $password);
      redirect_with('profile', ['msg' => 'Cuenta creada']);

    case 'login':
      if ($method !== 'POST') throw new Exception('Método no permitido');
      $email = trim((string)($_POST['email'] ?? ''));
      $password = (string)($_POST['password'] ?? '');
      if ($email === '' || $password === '') throw new Exception('Datos incompletos');
      fs_login($email, $password);
      redirect_with('profile', ['msg' => 'Sesión iniciada']);

    case 'logout':
      if ($method !== 'POST') throw new Exception('Método no permitido');
      fs_logout();
      redirect_with('login', ['msg' => 'Sesión cerrada']);

    default:
      http_response_code(400);
      echo 'Acción no válida';
  }
} catch (Throwable $e) {
  $msg = $e->getMessage();
  if ($action === 'register') redirect_with('register', ['error' => $msg]);
  if ($action === 'login') redirect_with('login', ['error' => $msg]);
  redirect_with('login', ['error' => $msg]);
}
