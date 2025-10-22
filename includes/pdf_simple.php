<?php
function fs_pdf_escape($s){
  return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\(','\)','',''], (string)$s);
}

function fs_to_ascii($s){
  // Transliteración simple a ASCII para Type1 (sin Unicode)
  $map = [
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
    'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N','Ü'=>'U',
  ];
  $s = strtr((string)$s, $map);
  // Eliminar cualquier no-ASCII restante
  return preg_replace('/[^\x20-\x7E]/', '', $s);
}

function fs_pdf_text_at($x, $y, $text, $font = 'F1', $size = 11, $fillGray = null){
  $safe = fs_pdf_escape(fs_to_ascii($text));
  $ops = [];
  if ($fillGray !== null) { $ops[] = sprintf("%.2f g\n", max(0,min(1,$fillGray))); }
  $ops[] = "BT\n";
  $ops[] = sprintf("/%s %d Tf\n", $font, (int)$size);
  $ops[] = sprintf("%.2f %.2f Td (%s) Tj\n", $x, $y, $safe);
  $ops[] = "ET\n";
  if ($fillGray !== null) { $ops[] = "0 g\n"; }
  return implode('', $ops);
}

function fs_pad($text, $len, $alignRight=false){
  $s = (string)$text; $l = mb_strlen($s);
  if ($l >= $len) return mb_substr($s, 0, $len);
  $pad = str_repeat(' ', $len - $l);
  return $alignRight ? ($pad.$s) : ($s.$pad);
}

function fs_render_invoice_pdf(array $order){
  $pageW = 595; $pageH = 842; // A4 portrait
  $marginL = 50; $marginR = 50; $marginT = 50; $marginB = 50;
  $y = $pageH - $marginT; // start from top

  // Datos base
  $storeName = defined('FS_NAME') ? FS_NAME : 'Ferre Style';
  $ruc = 'RUC: 00000000000'; // Placeholder, ajusta si tienes RUC real
  $orderId = $order['id'] ?? '';
  $createdAt = $order['created_at'] ?? date('Y-m-d H:i');
  $customerName = $order['user_name'] ?? '';
  $customerEmail = $order['user_email'] ?? '';
  $paymentMethod = strtoupper($order['payment_method'] ?? 'Yape');
  $paymentStatus = strtoupper($order['payment_status'] ?? 'PENDING');
  $paymentRef = $order['payment_ref'] ?? '';

  // Totales (sub-total, IGV 18%, total)
  $subtotal = round((float)($order['total'] ?? 0), 2);
  $igv = round($subtotal * 0.18, 2);
  $grand = round($subtotal + $igv, 2);

  // Construcción del contenido
  $content = '';

  // Encabezado: LOGO (placeholder) + Nombre + RUC + título
  $content .= fs_pdf_text_at($marginL, $y, '[LOGO]', 'F2', 12);
  $content .= fs_pdf_text_at($marginL + 70, $y, $storeName, 'F2', 16);
  $y -= 18;
  $content .= fs_pdf_text_at($marginL + 70, $y, $ruc, 'F1', 11);
  $content .= fs_pdf_text_at($pageW - $marginR - 160, $y + 18, sprintf('BOLETA #%s', $orderId), 'F2', 14);
  $y -= 22;
  $content .= fs_pdf_text_at($marginL, $y, str_repeat('-', 80), 'F1', 10);
  $y -= 16;

  // Datos del cliente
  $content .= fs_pdf_text_at($marginL, $y, 'Datos del cliente', 'F2', 12);
  $y -= 14;
  $content .= fs_pdf_text_at($marginL, $y, 'Nombre: ' . $customerName, 'F1', 11);
  $y -= 14;
  $content .= fs_pdf_text_at($marginL, $y, 'Correo: ' . $customerEmail, 'F1', 11);
  $y -= 14;
  $content .= fs_pdf_text_at($marginL, $y, 'Fecha: ' . $createdAt, 'F1', 11);
  $y -= 18;

  // Tabla de articulos
  $content .= fs_pdf_text_at($marginL, $y, 'Detalle de articulos', 'F2', 12);
  $y -= 14;
  // Reducimos anchos para que quepa "Importe" en la página
  $cols = [
    ['w'=>6, 'title'=>'Cant.'],
    ['w'=>38,'title'=>'Producto'],
    ['w'=>12,'title'=>'P.Unit.'],
    ['w'=>12,'title'=>'Imp.'],
  ];
  $header = fs_to_ascii(fs_pad($cols[0]['title'], $cols[0]['w'])) .
            fs_to_ascii(fs_pad($cols[1]['title'], $cols[1]['w'])) .
            fs_to_ascii(fs_pad($cols[2]['title'], $cols[2]['w'], true)) .
            fs_to_ascii(fs_pad($cols[3]['title'], $cols[3]['w'], true));
  $content .= fs_pdf_text_at($marginL, $y, $header, 'F2', 10);
  $y -= 12;
  $content .= fs_pdf_text_at($marginL, $y, str_repeat('-', 70), 'F1', 10);
  $y -= 14;

  foreach(($order['items'] ?? []) as $it){
    $qty = (int)($it['quantity'] ?? 1);
    $name = (string)($it['product_name'] ?? ($it['name'] ?? 'Producto'));
    $unit = number_format((float)($it['unit_price'] ?? 0), 2);
    $imp = number_format((float)($it['subtotal'] ?? ($qty * (float)($it['unit_price'] ?? 0))), 2);
    // Cortar nombre si excede
    $name = fs_to_ascii(mb_substr($name, 0, 38));
    $line = fs_pad($qty, $cols[0]['w']) .
            fs_pad($name, $cols[1]['w']) .
            fs_pad($unit, $cols[2]['w'], true) .
            fs_pad($imp, $cols[3]['w'], true);
    $content .= fs_pdf_text_at($marginL, $y, $line, 'F1', 10);
    $y -= 14;
    if ($y < $marginB + 120) { /* sin salto de página (simple) */ break; }
  }

  $y -= 4;
  $content .= fs_pdf_text_at($marginL, $y, str_repeat('-', 70), 'F1', 10);
  $y -= 20;

  // Totales (alineados a la derecha)
  $rightX = $pageW - $marginR - 180;
  $content .= fs_pdf_text_at($rightX, $y, 'Sub-total: ' . number_format($subtotal, 2), 'F1', 11);
  $y -= 14;
  $content .= fs_pdf_text_at($rightX, $y, 'IGV (18%): ' . number_format($igv, 2), 'F1', 11);
  $y -= 14;
  $content .= fs_pdf_text_at($rightX, $y, 'Total: ' . number_format($grand, 2), 'F2', 12);
  $y -= 22;

  // Forma de pago
  $content .= fs_pdf_text_at($marginL, $y, 'Forma de pago: ' . $paymentMethod . '  (' . $paymentStatus . ')', 'F1', 11);
  $y -= 14;
  if ($paymentRef) { $content .= fs_pdf_text_at($marginL, $y, 'Referencia: ' . $paymentRef, 'F1', 11); $y -= 14; }

  $y -= 6;
  $content .= fs_pdf_text_at($marginL, $y, str_repeat('-', 70), 'F1', 10);
  $y -= 18;

  // Mensaje de agradecimiento
  $content .= fs_pdf_text_at($marginL, $y, 'Gracias por tu compra, esperamos verte pronto.', 'F1', 11);

  // Ensamblar PDF
  $objects = [];
  $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"; // Catalog
  $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"; // Pages
  $pageDict = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 $pageW $pageH] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>";
  $objects[] = "3 0 obj $pageDict endobj\n"; // Page
  $objects[] = "4 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "endstream endobj\n"; // Content
  // Fonts: F1 = Courier (monoespaciada para alinear tabla), F2 = Courier-Bold
  $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Courier >> endobj\n";
  $objects[] = "6 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >> endobj\n";

  $pdf = "%PDF-1.4\n";
  $xrefs = [];
  $pos = strlen($pdf);
  foreach($objects as $obj){
    $xrefs[] = $pos;
    $pdf .= $obj;
    $pos = strlen($pdf);
  }
  $xrefPos = $pos;
  $pdf .= "xref\n0 " . (count($objects)+1) . "\n";
  $pdf .= sprintf("%010d %05d f \n", 0, 65535);
  for($i=0;$i<count($xrefs);$i++){
    $pdf .= sprintf("%010d %05d n \n", $xrefs[$i], 0);
  }
  $pdf .= "trailer << /Size " . (count($objects)+1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
  return $pdf;
}
