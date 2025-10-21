<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/orders.php';
require_once __DIR__ . '/../includes/pdf_simple.php';

$u = fs_current_user();
if(!$u){ http_response_code(401); echo 'No autenticado'; exit; }

$orderId = (int)($_GET['order_id'] ?? 0);
if($orderId<=0){ http_response_code(400); echo 'ID inválido'; exit; }

$order = fs_get_order($orderId);
if(!$order){ http_response_code(404); echo 'Pedido no encontrado'; exit; }

// Solo propietario o admin/empleado pueden ver
if($order['user_id'] != $u['id'] && !in_array($u['role_name'], ['admin','empleado'], true)){
  http_response_code(403); echo 'Acceso denegado'; exit;
}

$pdf = fs_render_invoice_pdf($order);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="boleta_'.$orderId.'.pdf"');
echo $pdf;
