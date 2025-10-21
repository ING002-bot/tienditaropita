<?php
require_once __DIR__ . '/includes/config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed = ['home','catalog','cart','profile','contact','login','register','staff_chat','checkout','order_success','orders_history','admin_dashboard','admin_products','admin_orders'];
if (!in_array($page, $allowed)) { $page = 'home'; }

$titleMap = [
  'home' => 'Inicio',
  'catalog' => 'Catálogo',
  'cart' => 'Carrito',
  'profile' => 'Perfil',
  'contact' => 'Contacto',
  'login' => 'Iniciar sesión',
  'register' => 'Crear cuenta'
  ,'staff_chat' => 'Chat interno'
  ,'checkout' => 'Checkout'
  ,'order_success' => 'Pedido realizado'
  ,'orders_history' => 'Mis pedidos'
  ,'admin_dashboard' => 'Administración'
  ,'admin_products' => 'Productos (Admin)'
  ,'admin_orders' => 'Pedidos (Admin)'
];
$title = 'Ferre Style · ' . $titleMap[$page];

include __DIR__ . '/includes/header.php';
include __DIR__ . '/views/' . $page . '.php';
include __DIR__ . '/includes/footer.php';
