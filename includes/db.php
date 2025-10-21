<?php
require_once __DIR__ . '/config.php';

function fs_pdo(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  $dsn = 'mysql:host='.FS_DB_HOST.';dbname='.FS_DB_NAME.';charset='.FS_DB_CHARSET;
  $opts = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];
  $pdo = new PDO($dsn, FS_DB_USER, FS_DB_PASS, $opts);
  return $pdo;
}
