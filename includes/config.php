<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

define('FS_NAME', 'Ferre Style');

// Detectar base URL automáticamente según el path del script
$__fs_script_dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
define('FS_BASE_URL', ($__fs_script_dir === '' || $__fs_script_dir === '/') ? '' : $__fs_script_dir);

define('FS_PRODUCTS_FILE', __DIR__ . '/../data/products.json');

// DB config (ajusta según tu XAMPP)
define('FS_DB_HOST', '127.0.0.1');
define('FS_DB_NAME', 'ferre_style');
define('FS_DB_USER', 'root');
define('FS_DB_PASS', '');
define('FS_DB_CHARSET', 'utf8mb4');

// CSRF utils
function fs_csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}
function fs_csrf_check($token) {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Google OAuth: usa valores LITERALES aquí, o define variables de entorno con nombres FS_GOOGLE_*
define('FS_GOOGLE_CLIENT_ID', '444293560484-ktfob812fvu6e02uut9j9euprpls7h1r.apps.googleusercontent.com');
define('FS_GOOGLE_CLIENT_SECRET', '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');
define('FS_GOOGLE_REDIRECT_URI', 'http://localhost/tiendaropa/api/google_callback.php');

// Yape config (número o QR). Ajusta estos valores.
define('FS_YAPE_NUMBER',('912112380'));
define('FS_YAPE_QR_URL', FS_BASE_URL . '/uploads/qr/yape.png');

function fs_load_products() {
  $file = FS_PRODUCTS_FILE;
  if (!file_exists($file)) return [];
  $json = file_get_contents($file);
  $data = json_decode($json, true);
  return is_array($data) ? $data : [];
}

function fs_find_product($id) {
  foreach (fs_load_products() as $p) {
    if ((string)$p['id'] === (string)$id) return $p;
  }
  // Fallback: buscar en DB por SKU
  try {
    require_once __DIR__ . '/db.php';
    $pdo = fs_pdo();
    $st = $pdo->prepare('SELECT sku, name, price, image FROM products WHERE sku = ? LIMIT 1');
    $st->execute([(string)$id]);
    $row = $st->fetch();
    if ($row) {
      return ['id'=>$row['sku'], 'name'=>$row['name'], 'price'=>(float)$row['price'], 'image'=>$row['image']];
    }
  } catch (Throwable $e) { /* ignore */ }
  return null;
}
