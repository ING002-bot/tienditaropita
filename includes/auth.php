<?php
require_once __DIR__ . '/db.php';

function fs_validate_email(string $email): bool {
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function fs_user_by_email(string $email) {
  $pdo = fs_pdo();
  $st = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? LIMIT 1');
  $st->execute([$email]);
  return $st->fetch();
}

function fs_user_by_id(int $id) {
  $pdo = fs_pdo();
  $st = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1');
  $st->execute([$id]);
  return $st->fetch();
}

function fs_user_by_google_sub(string $sub) {
  $pdo = fs_pdo();
  $st = $pdo->prepare('SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.google_sub = ? LIMIT 1');
  $st->execute([$sub]);
  return $st->fetch();
}

function fs_register(string $name, string $email, string $password, string $role = 'cliente') {
  if (!fs_validate_email($email)) throw new Exception('Correo inválido');
  if (strlen($password) < 8) throw new Exception('La contraseña debe tener al menos 8 caracteres');
  $pdo = fs_pdo();
  $pdo->beginTransaction();
  try {
    $roleId = fs_role_id($role);
    if (!$roleId) throw new Exception('Rol inválido');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $st = $pdo->prepare('INSERT INTO users (role_id, name, email, password_hash) VALUES (?,?,?,?)');
    $st->execute([$roleId, $name, strtolower($email), $hash]);
    $id = (int)$pdo->lastInsertId();
    $pdo->commit();
    return fs_user_by_id($id);
  } catch (Throwable $e) {
    $pdo->rollBack();
    if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
      throw new Exception('El correo ya está registrado');
    }
    throw $e;
  }
}

function fs_role_id(string $name) {
  $pdo = fs_pdo();
  $st = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
  $st->execute([$name]);
  $row = $st->fetch();
  return $row ? (int)$row['id'] : null;
}

function fs_login(string $email, string $password) {
  $user = fs_user_by_email(strtolower($email));
  if (!$user || !password_verify($password, $user['password_hash'])) {
    throw new Exception('Credenciales inválidas');
  }
  $_SESSION['user_id'] = (int)$user['id'];
  fs_record_session((int)$user['id']);
  return $user;
}

function fs_logout() {
  if (isset($_SESSION['user_id'])) {
    fs_revoke_session((int)$_SESSION['user_id']);
  }
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function fs_current_user() {
  if (!empty($_SESSION['user_id'])) {
    return fs_user_by_id((int)$_SESSION['user_id']);
  }
  return null;
}

function fs_require_role(array $roles) {
  $u = fs_current_user();
  if (!$u || !in_array($u['role_name'], $roles, true)) {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
  }
}

function fs_record_session(int $userId) {
  $pdo = fs_pdo();
  $st = $pdo->prepare('INSERT INTO sessions (user_id, session_id, ip, user_agent, last_activity) VALUES (?,?,?,?,NOW())
    ON DUPLICATE KEY UPDATE last_activity = VALUES(last_activity), revoked = 0');
  $st->execute([
    $userId,
    session_id(),
    $_SERVER['REMOTE_ADDR'] ?? null,
    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
  ]);
}

function fs_revoke_session(int $userId) {
  $pdo = fs_pdo();
  $st = $pdo->prepare('UPDATE sessions SET revoked = 1 WHERE user_id = ? AND session_id = ?');
  $st->execute([$userId, session_id()]);
}

// --- Google OAuth helpers ---
function fs_login_by_id(int $id) {
  $user = fs_user_by_id($id);
  if(!$user) throw new Exception('Usuario no encontrado');
  $_SESSION['user_id'] = (int)$user['id'];
  fs_record_session((int)$user['id']);
  return $user;
}

function fs_login_google_profile(array $profile) {
  // profile: sub, name, email, picture, email_verified
  if (empty($profile['sub'])) throw new Exception('Perfil inválido');
  $pdo = fs_pdo();
  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare('SELECT id FROM users WHERE google_sub = ? LIMIT 1');
    $st->execute([$profile['sub']]);
    $row = $st->fetch();
    if ($row) {
      $up = $pdo->prepare('UPDATE users SET avatar_url = ?, email_verified = ? WHERE id = ?');
      $up->execute([
        $profile['picture'] ?? null,
        !empty($profile['email_verified']) ? 1 : 0,
        (int)$row['id']
      ]);
      $pdo->commit();
      return fs_login_by_id((int)$row['id']);
    }

    $hasUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    $role = $hasUsers ? 'cliente' : 'admin';
    $roleId = fs_role_id($role);
    $ins = $pdo->prepare('INSERT INTO users (role_id, name, email, google_sub, avatar_url, email_verified, password_hash) VALUES (?,?,?,?,?,?,?)');
    $dummy = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $ins->execute([
      $roleId,
      $profile['name'] ?? 'Usuario',
      strtolower((string)($profile['email'] ?? ('google_'.$profile['sub'].'@example.invalid'))),
      $profile['sub'],
      $profile['picture'] ?? null,
      !empty($profile['email_verified']) ? 1 : 0,
      $dummy
    ]);
    $newId = (int)$pdo->lastInsertId();
    $pdo->commit();
    return fs_login_by_id($newId);
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}
