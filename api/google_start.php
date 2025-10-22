<?php
require_once __DIR__ . '/../includes/config.php';

// Debug temprano: inspeccionar constantes y base URL
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'defined_FS_GOOGLE_CLIENT_ID' => defined('FS_GOOGLE_CLIENT_ID'),
    'defined_FS_GOOGLE_REDIRECT_URI' => defined('FS_GOOGLE_REDIRECT_URI'),
    'FS_GOOGLE_CLIENT_ID' => defined('FS_GOOGLE_CLIENT_ID') ? FS_GOOGLE_CLIENT_ID : null,
    'FS_GOOGLE_REDIRECT_URI' => defined('FS_GOOGLE_REDIRECT_URI') ? FS_GOOGLE_REDIRECT_URI : null,
    'FS_BASE_URL' => defined('FS_BASE_URL') ? FS_BASE_URL : null,
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
    'HTTPS' => $_SERVER['HTTPS'] ?? null,
  ], JSON_PRETTY_PRINT);
  exit;
}

try {
  if (!defined('FS_GOOGLE_CLIENT_ID') || !defined('FS_GOOGLE_REDIRECT_URI') || !FS_GOOGLE_CLIENT_ID || !FS_GOOGLE_REDIRECT_URI) {
    throw new RuntimeException('Google OAuth no está configurado. Define FS_GOOGLE_CLIENT_ID/FS_GOOGLE_REDIRECT_URI.');
  }

  $rand = function(int $len){
    try { return random_bytes($len); }
    catch (Throwable $e) {
      if (function_exists('openssl_random_pseudo_bytes')) return openssl_random_pseudo_bytes($len);
      return substr(hash('sha256', uniqid('', true)), 0, $len);
    }
  };

  $state = bin2hex($rand(16));
  $nonce = bin2hex($rand(16));
  $_SESSION['oauth2_state'] = $state;
  $_SESSION['oauth2_nonce'] = $nonce;

  $params = [
    'client_id' => FS_GOOGLE_CLIENT_ID,
    'redirect_uri' => FS_GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'nonce'  => $nonce,
    'access_type' => 'offline',
    'prompt' => 'consent'
  ];

  if (isset($_GET['debug'])) {
    header('Content-Type: application/json');
    echo json_encode([
      'client_id' => FS_GOOGLE_CLIENT_ID,
      'redirect_uri' => FS_GOOGLE_REDIRECT_URI,
      'state' => $state,
      'nonce' => $nonce,
      'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params)
    ], JSON_PRETTY_PRINT);
    exit;
  }

  $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
  header('Location: ' . $authUrl);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Error en inicio de Google OAuth: ' . $e->getMessage();
  exit;
}
