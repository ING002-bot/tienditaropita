<?php
function fs_pdf_escape($s){
  return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\(','\)','',''], $s);
}
function fs_pdf_text_lines(array $lines){
  $y = 800; $out = [];
  $out[] = "BT\n/F1 12 Tf\n";
  foreach($lines as $line){
    $safe = fs_pdf_escape($line);
    $out[] = sprintf("50 %.2f Td (%s) Tj\n", $y, $safe);
    $y -= 18;
    $out[] = "0 %.2f Td\n"; // reset x with new line (we'll actually move absolute each time)
  }
  $out[] = "ET\n";
  // Replace the Td reset placeholder with correct value (hack avoided by absolute Td each time)
  return implode('', array_filter($out, fn($l)=>!str_contains($l, '%.2f')));
}
function fs_render_invoice_pdf(array $order){
  $lines = [];
  $lines[] = 'FERRE STYLE - BOLETA';
  $lines[] = '----------------------------------------';
  $lines[] = 'Pedido: #' . $order['id'];
  $lines[] = 'Cliente: ' . ($order['user_name'] ?? '');
  $lines[] = 'Email: ' . ($order['user_email'] ?? '');
  $lines[] = 'Fecha: ' . ($order['created_at'] ?? date('Y-m-d H:i'));
  $lines[] = '';
  $lines[] = 'Items:';
  foreach(($order['items'] ?? []) as $it){
    $lines[] = sprintf('- %s x%d  $%.2f', $it['product_name'] ?? ($it['name'] ?? 'Producto'), (int)$it['quantity'], (float)$it['subtotal']);
  }
  $lines[] = '';
  $lines[] = sprintf('TOTAL: $%.2f', (float)$order['total']);
  $lines[] = 'Pago: Yape (' . ($order['payment_status'] ?? 'pending') . ')';
  if (!empty($order['payment_ref'])) $lines[] = 'Ref: ' . $order['payment_ref'];

  $content = fs_pdf_text_lines($lines);
  $objects = [];
  $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"; // Catalog
  $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"; // Pages
  $pageDict = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
  $objects[] = "3 0 obj $pageDict endobj\n"; // Page
  $objects[] = "4 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "endstream endobj\n"; // Content
  $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n"; // Font

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
