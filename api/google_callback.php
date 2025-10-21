<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

function redirect_to($page, $params=[]) {
  $q = http_build_query($params);
  header('Location: ' . FS_BASE_URL . '/index.php?page=' . urlencode($page) . ($q?('&'.$q):''));
  exit;
}

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (!$code || !$state || !isset($_SESSION['oauth2_state']) || !hash_equals($_SESSION['oauth2_state'], $state)) {
  redirect_to('login', ['error' => 'Estado inválido en OAuth']);
}

unset($_SESSION['oauth2_state']);
$nonceExpected = $_SESSION['oauth2_nonce'] ?? null;
unset($_SESSION['oauth2_nonce']);

if (!FS_GOOGLE_CLIENT_ID || !FS_GOOGLE_CLIENT_SECRET || !FS_GOOGLE_REDIRECT_URI) {
  redirect_to('login', ['error' => 'OAuth no configurado']);
}

// Intercambiar el code por tokens
$tokenRes = null;
try {
  $ch = curl_init('https://oauth2.googleapis.com/token');
  $payload = http_build_query([
    'code' => $code,
    'client_id' => FS_GOOGLE_CLIENT_ID,
    'client_secret' => FS_GOOGLE_CLIENT_SECRET,
    'redirect_uri' => FS_GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
  ]);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
  ]);
  $resp = curl_exec($ch);
  if ($resp === false) throw new Exception('Error de red al solicitar token');
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($status < 200 || $status >= 300) throw new Exception('Error al obtener token');
  $tokenRes = json_decode($resp, true);
} catch (Throwable $e) {
  redirect_to('login', ['error' => 'No se pudo autenticar con Google']);
}

$accessToken = $tokenRes['access_token'] ?? '';
$idToken = $tokenRes['id_token'] ?? '';
if (!$accessToken) {
  redirect_to('login', ['error' => 'Token inválido']);
}

// Obtener perfil del usuario desde el endpoint OpenID UserInfo
try {
  $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
  ]);
  $uinfo = curl_exec($ch);
  if ($uinfo === false) throw new Exception('Error de red al pedir perfil');
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($status !== 200) throw new Exception('No se pudo obtener el perfil');
  $profile = json_decode($uinfo, true);

  // Validación básica del nonce si está presente en id_token
  if ($idToken && $nonceExpected) {
    $parts = explode('.', $idToken);
    if (count($parts) >= 2) {
      $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
      if (!empty($payload['nonce']) && !hash_equals($nonceExpected, $payload['nonce'])) {
        throw new Exception('Nonce inválido');
      }
    }
  }

  fs_login_google_profile([
    'sub' => $profile['sub'] ?? null,
    'name' => $profile['name'] ?? ($profile['given_name'] ?? 'Usuario'),
    'email' => $profile['email'] ?? null,
    'picture' => $profile['picture'] ?? null,
    'email_verified' => !empty($profile['email_verified'])
  ]);
  redirect_to('profile', ['msg' => 'Sesión iniciada con Google']);
} catch (Throwable $e) {
  redirect_to('login', ['error' => 'Error al obtener perfil']);
}
