<?php
require_once __DIR__ . '/../includes/config.php';

if (!FS_GOOGLE_CLIENT_ID || !FS_GOOGLE_REDIRECT_URI) {
  http_response_code(500);
  echo 'Google OAuth no está configurado. Define FS_GOOGLE_CLIENT_ID/FS_GOOGLE_REDIRECT_URI.';
  exit;
}

$state = bin2hex(random_bytes(16));
$nonce = bin2hex(random_bytes(16));
$_SESSION['oauth2_state'] = $state;
$_SESSION['oauth2_nonce'] = $nonce;

$params = [
  'client_id' => FS_GOOGLE_CLIENT_ID,
  'redirect_uri' => FS_GOOGLE_REDIRECT_URI,
  'response_type' => 'code',
  'scope' => 'openid email profile',
  'state' => $state,
  'nonce' => $nonce,
  'access_type' => 'offline',
  'prompt' => 'consent'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
