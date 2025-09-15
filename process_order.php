<?php
global $conn, $log, $h, $data;
include_once 'includes/utils.php';
include_once 'includes/mysql.php';

function postXML(string $url, string $xmlString, array $headersExtra = []): array
{
    // Preparar cURL
    $ch = curl_init($url);

    // Headers base
    $headers = [
        "Content-Type: text/xml",
        "Content-Length: " . strlen($xmlString)
    ];

    // Si el usuario pasa headers adicionales, los agregamos
    if (!empty($headersExtra)) {
        $headers = array_merge($headers, $headersExtra);
    }

    // Configuración de cURL
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // OJO: cambiar a true en producción

    // Ejecutar POST
    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Capturar errores si los hubiera
    $error = null;
    if (curl_errno($ch)) {
        $error = curl_error($ch);
    }

    // Cerrar conexión
    curl_close($ch);

    // Devolver como array
    return [
        'http_code' => $httpCode,
        'response' => $responseBody,
        'error' => $error,
    ];
}

$shop = $h['X-Shopify-Shop-Domain'];
$sql = "SELECT * from shops WHERE shop = '$shop' and uninstalled IS NULL";
$result = $conn->query($sql);
$log->debug("SQL query $sql");
$log->debug("Select serial order: " . print_r($result, true));
$row = $result->fetch_assoc();
$sandbox_mode = $row["env_prod"] !=1;
$prefix = $row['prefix_order'];
$num_order = $row['order_serial'] + 1;
$sql = "UPDATE shops SET order_serial = '$num_order' WHERE shop = '$shop'";
$result = $conn->query($sql);
$log->debug("Update serial order: " . print_r($result, true));


// Generar XML con DOM
$doc = new DOMDocument('1.0', 'ISO-8859-1');
$doc->formatOutput = true;

// Envelope y namespaces
$env = $doc->createElementNS("http://schemas.xmlsoap.org/soap/envelope/", "SOAP-ENV:Envelope");
$env->setAttribute("SOAP-ENV:encodingStyle", "http://schemas.xmlsoap.org/soap/encoding/");
$doc->appendChild($env);

$body = $doc->createElement("SOAP-ENV:Body");
$env->appendChild($body);

// Método que espera el WSDL
$r = random_int(1001, 9999);
$request = $doc->createElement("ns$r:RequestXMLCFDIimpuestos");
$request->setAttribute("xmlns:ns$r", "http://tempuri.org");
$body->appendChild($request);

// Parámetros de autenticación
$request->appendChild($doc->createElement("USUARIO", $row['qmf4_user']));
$request->appendChild($doc->createElement("STATUS", "ALMACENAR"));

// === Datos del pedido ===
/*id": 1234567890,
  "name": "#1001",
  "order_number": 1001,
  "financial_status": "paid",
  "subtotal_price": "120.00",
  "total_tax": "30.00",
  "total_price": "150.00",
  "currency": "USD",*/

$shopify_id = $data['id'];
$orderId = $data['order_number'] ?? 'TEST-ORDER';
$date = new DateTime(date('Y-n-j', strtotime($data['processed_at'] ?? 'now')));
$purchaseDate = $date->format('Y-m-d');
$orderStatus = '';
switch (strtolower($data['financial_status'])) {
    case 'partially refund':
    case 'paid':
        $orderStatus = 'paid';
        break;
    case 'partially paid':
    case 'pending':
    case 'authorized':
        $orderStatus = 'pending';
        break;
    case 'voided':
    case 'refunded' :
        $orderStatus = 'cancelled';
        break;
}

$salesChannel = 'Shopify';
$totalAmount = $data['total_price'] ?? '0.00';
$currency = $data['currency'] ?? 'MXN';
$email = $data['email'] ?? 'no-email@dummy.com';
$buyerName = mb_convert_encoding($data['customer']['first_name']??'' . $data['customer']['last_name']??'',"UTF-8", 'ISO-8859-1')?? 'VENTAS AL PUBLICO EN GENERAL';
$usoCFDI = 'G03';
$formaPago = '31';

$order = $doc->createElement("Order");
$order->appendChild($doc->createElement("RFC", "XAXX010101000"));

$order->appendChild($doc->createElement("NombreReceptor", ""));
$order->appendChild($doc->createElement("RegimenFiscalReceptor", ""));
$order->appendChild($doc->createElement("codigoPostal", ""));

$order->appendChild($doc->createElement("SellerOrderId", $prefix . $orderId));
$order->appendChild($doc->createElement("PurchaseDate", $purchaseDate));
$order->appendChild($doc->createElement("OrderStatus", ucfirst($orderStatus)));
$order->appendChild($doc->createElement("SalesChannel", $salesChannel));

$orderTotal = $doc->createElement("OrderTotal");
$orderTotal->appendChild($doc->createElement("CurrencyCode", $currency));
$orderTotal->appendChild($doc->createElement("Amount", $totalAmount));
$order->appendChild($orderTotal);

$order->appendChild($doc->createElement("NumberOfItemsShipped", count($data['line_items'] ?? [])));
$order->appendChild($doc->createElement("BuyerEmail", $email));
$order->appendChild($doc->createElement("BuyerName", $buyerName));
$order->appendChild($doc->createElement("UsoCFDI", $usoCFDI));
$order->appendChild($doc->createElement("FormaPago", $formaPago));

$request->appendChild($order);

// === Items del pedido ===
$itemsResponse = $doc->createElement("ListOrderItemsResponse");
$itemsResult = $doc->createElement("ListOrderItemsResult");
$orderItems = $doc->createElement("OrderItems");
$first_item = true;
foreach ($data['line_items'] ?? [] as $item) {
    $product_name = mb_convert_encoding($item['title'], "UTF-8", 'ISO-8859-1');
    $orderItem = $doc->createElement("OrderItem");
    $orderItem->appendChild($doc->createElement("ASIN", $item['sku'] ?: 'SIN-SKU'));
    $orderItem->appendChild($doc->createElement("Title", $product_name));

    $tot_discount = 0.00;
    if(!empty($item['discount_allocations'])){
        foreach ($item['discount_allocations'] as $discount){
            $tot_discount = $tot_discount +  $discount['amount'];
        }
    }
    $orderItem->appendChild($doc->createElement("QuantityShipped", $item['quantity']));
    $itemPrice = $doc->createElement("ItemPrice");
    $itemPrice->appendChild($doc->createElement("Amount", number_format($item['price'] * $item['quantity'] - $tot_discount, 2, '.', "")));
    $orderItem->appendChild($itemPrice);

    $shippingPrice = $doc->createElement("ShippingPrice");
    if ($first_item) {
        $t = 0.0;
        foreach ($data['shipping_lines'] ?? [] as $shipping_line) {
            $t = $t + $shipping_line['price'];
        }
        $shippingPrice->appendChild($doc->createElement("Amount", number_format($t, 2, '.', '')));
    }else{
        $shippingPrice->appendChild($doc->createElement("Amount", "0.00"));
    }
    $orderItem->appendChild($shippingPrice);

    $discount = $doc->createElement("PromotionDiscount");
    $discount->appendChild($doc->createElement("Amount", "0.00"));
    $orderItem->appendChild($discount);

    $impuestos = $doc->createElement("Impuestos");
    $traslados = $impuestos->appendChild($doc->createElement("Traslados", ""));
    $traslado = $traslados->appendChild($doc->createElement("Traslado", ""));
    $traslado->appendChild($doc->createElement("Impuesto", "002"));
    $traslado->appendChild($doc->createElement("TipoFactor", "Tasa"));
    $traslado->appendChild($doc->createElement("TasaOCuota", "0.16"));
    $orderItem->appendChild($impuestos);

    $orderItems->appendChild($orderItem);
}

$itemsResult->appendChild($orderItems);
$itemsResponse->appendChild($itemsResult);
$request->appendChild($itemsResponse);

// Convertir DOM a string
$xmlString = $doc->saveXML();

// Guardar copia local del XML
$folder = __DIR__ . '/logs';
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}
$filename = $folder . '/order_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $orderId) . '_' . time() . '.xml';
file_put_contents($filename, $xmlString);


//enviar por POST
if ($sandbox_mode) {
    $url_qmf = 'https://quieromifactura.mx/QA/web_services/servidorMarket.php';
}else{
    $url_qmf = 'https://quieromifactura.mx/PROD/web_services/servidorMarket.php';
}

$response = postXML($url_qmf, $xmlString);
$log->debug('Result POST a QMF: ' . print_r($response, true));