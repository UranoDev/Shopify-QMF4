<?php
global $conn;
error_reporting(E_ALL);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once "vendor/autoload.php";
include_once "includes/utils.php";
include_once "includes/mysql.php";

// Configuración de logging
$log = new Logger('resend-qmf');
$log->pushHandler(new StreamHandler('logs/resend-qmf.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));

// Configuración de Shopify por defecto (se pueden sobrescribir con parámetros)
$defaultShopDomain = 'tu-tienda.myshopify.com'; // Cambia por tu dominio por defecto
$defaultApiToken = 'tu-api-token'; // Cambia por tu token por defecto

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

/**
 * Obtiene el detalle de una orden desde Shopify
 */
function getShopifyOrder($orderId, $shopDomain, $apiToken, $log) {
    $url = "https://{$shopDomain}/admin/api/2023-07/orders/{$orderId}.json";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Shopify-Access-Token: {$apiToken}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $log->error("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        $log->error("HTTP Error {$httpCode}: {$response}");
        return null;
    }

    $data = json_decode($response, true);
    return $data['order'] ?? null;
}

/**
 * Obtiene la configuración de la tienda desde la base de datos
 */
function getShopConfig($shopDomain, $conn, $log) {
    $sql = "SELECT * from shops WHERE shop = '$shopDomain' and uninstalled IS NULL";
    $result = $conn->query($sql);
    $log->debug("SQL query $sql");
    $log->debug("Select shop config: " . print_r($result, true));

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Actualiza el número de serie de orden en la base de datos
 */
function updateOrderSerial($shopDomain, $conn, $log) {
    $sql = "SELECT order_serial from shops WHERE shop = '$shopDomain' and uninstalled IS NULL";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $num_order = $row['order_serial'] + 1;

        $sql = "UPDATE shops SET order_serial = '$num_order' WHERE shop = '$shopDomain'";
        $result = $conn->query($sql);
        $log->debug("Update serial order: " . print_r($result, true));

        return $num_order;
    }

    return 1;
}

/**
 * Genera el XML para QMF usando la misma estructura que process_order.php
 */
function generateQMFXML($data, $shopConfig, $log) {
    global $conn;

    $log->info("Generando XML para orden ID: " . $data['id']);

    // Obtener y actualizar el serial de orden
    $shop = $shopConfig['shop'];
    $prefix = $shopConfig['prefix-order'];
    $num_order = updateOrderSerial($shop, $conn, $log);

    // Generar XML con DOM - Estructura exacta de process_order.php
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
    $request->appendChild($doc->createElement("USUARIO", $shopConfig['qmf4_user']));
    $request->appendChild($doc->createElement("STATUS", "ALMACENAR"));

    // === Datos del pedido ===
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
    $buyerName = ($data['customer']['first_name']??'' . $data['customer']['last_name']??'')?? 'VENTAS AL PUBLICO EN GENERAL';
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

    $totalShippingCost = 0.0;
    foreach ($data['shipping_lines'] ?? [] as $shipping_line) {
        $totalShippingCost = $totalShippingCost + $shipping_line['price'];
    }
    if ($totalShippingCost > 0) {
        $product_name = mb_convert_encoding('Gastos de envío', "UTF-8", 'ISO-8859-1');
        $orderItem = $doc->createElement("OrderItem");
        $orderItem->appendChild($doc->createElement("ASIN", '01Ship' ?: 'SIN-SKU'));
        $orderItem->appendChild($doc->createElement("Title", $product_name));
        $orderItem->appendChild($doc->createElement("QuantityShipped", 1));
        $itemPrice = $doc->createElement("ItemPrice");
        $price_of_item = number_format($totalShippingCost, 2, '.','');
        $itemPrice->appendChild($doc->createElement("Amount", $price_of_item));
        $orderItem->appendChild($itemPrice);
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



    foreach ($data['line_items'] ?? [] as $item) {
        $product_name = mb_convert_encoding($item['title'], "UTF-8", 'ISO-8859-1');
        $orderItem = $doc->createElement("OrderItem");
        $orderItem->appendChild($doc->createElement("ASIN", $item['sku'] ?: 'SIN-SKU'));
        $orderItem->appendChild($doc->createElement("Title", $product_name));
        $orderItem->appendChild($doc->createElement("QuantityShipped", $item['quantity']));

        $tot_discount = 0.00;
        if(!empty($item['discount_allocations'])){
            foreach ($item['discount_allocations'] as $discount){
                $tot_discount = $tot_discount +  $discount['amount'];
            }
        }

        $itemPrice = $doc->createElement("ItemPrice");
        $price_of_item = number_format($item['price'] * $item['quantity'] - $tot_discount, 2, '.','');
        $itemPrice->appendChild($doc->createElement("Amount", $price_of_item));
        $orderItem->appendChild($itemPrice);

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
    $filename = $folder . '/resend_order_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $orderId) . '_' . time() . '.xml';
    file_put_contents($filename, $xmlString);
    $log->info("XML guardado en: " . $filename);

    // Guardar JSON de Shopify
    $filename = $folder . '/JSON_order_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $orderId) . '_' . time() . '.json';
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    $log->info("JSON guardado en: " . $filename);

    return $xmlString;
}

/**
 * Muestra la ayuda de uso del script
 */
function showUsage() {
    echo "Uso: php resend_order_to_qmf.php <order_id> [shop_domain] [api_token]\n";
    echo "\n";
    echo "Parámetros:\n";
    echo "  order_id    : ID de la orden de Shopify (requerido)\n";
    echo "  shop_domain : Dominio de la tienda Shopify (opcional)\n";
    echo "  api_token   : Token de acceso de la API de Shopify (opcional)\n";
    echo "\n";
    echo "Ejemplos:\n";
    echo "  php resend_order_to_qmf.php 5234567890\n";
    echo "  php resend_order_to_qmf.php 5234567890 mi-tienda.myshopify.com\n";
    echo "  php resend_order_to_qmf.php 5234567890 mi-tienda.myshopify.com shpat_1234567890abcdef\n";
    echo "\n";
    echo "Nota: Si no se proporcionan shop_domain o api_token, se usarán los valores\n";
    echo "      configurados por defecto en el script.\n";
}

// Script principal
if ($argc < 2) {
    showUsage();
    exit(1);
}

$orderId = $argv[1];
$shopDomain = $argv[2] ?? $defaultShopDomain;
$apiToken = $argv[3] ?? $defaultApiToken;

// Validar que los parámetros esenciales no estén vacíos
if (empty($orderId)) {
    echo "Error: El Order ID es requerido.\n\n";
    showUsage();
    exit(1);
}

if (empty($shopDomain) || $shopDomain === 'tu-tienda.myshopify.com') {
    echo "Error: Debe configurar un shop_domain válido en el script o pasarlo como parámetro.\n\n";
    showUsage();
    exit(1);
}


$log->info("Iniciando proceso de reenvío para Order ID: {$orderId}");
$log->info("Shop Domain: {$shopDomain}");
$log->info("API Token: " . substr($apiToken, 0, 10) . "..." . substr($apiToken, -4)); // Log parcial por seguridad

try {
    // 1. Obtener configuración de la tienda
    $shopConfig = getShopConfig($shopDomain, $conn, $log);
    if (!$shopConfig) {
        $log->error("No se encontró configuración para la tienda: {$shopDomain}");
        echo "Error: No se encontró configuración para la tienda: {$shopDomain}\n";
        echo "Asegúrate de que la tienda esté registrada en la base de datos.\n";
        exit(1);
    }

    $apiToken = $shopConfig['token'] ?? $apiToken;
    if (empty($apiToken) || $apiToken === 'tu-api-token') {
        echo "Error: Debe configurar un api_token válido en el script o pasarlo como parámetro.\n\n";
        showUsage();
        exit(1);
    }

    $log->info("Configuración de tienda obtenida", ['prefix' => $shopConfig['prefix-order']]);

    // 2. Obtener orden de Shopify
    $log->info("Obteniendo orden {$orderId} desde Shopify");
    $order = getShopifyOrder($orderId, $shopDomain, $apiToken, $log);

    if (!$order) {
        $log->error("No se pudo obtener la orden {$orderId}");
        echo "Error: No se pudo obtener la orden {$orderId}\n";
        echo "Verifica que:\n";
        echo "  - El Order ID sea correcto\n";
        echo "  - El shop_domain sea válido\n";
        echo "  - El api_token tenga los permisos necesarios\n";
        exit(1);
    }

    $log->info("Orden obtenida exitosamente", [
        'order_number' => $order['order_number'],
        'total_price' => $order['total_price'],
        'currency' => $order['currency']
    ]);

    // 3. Generar XML usando la misma lógica que process_order.php
    $log->info("Generando XML para QMF");
    $xmlString = generateQMFXML($order, $shopConfig, $log);

    if (!$xmlString) {
        $log->error("Error al generar XML");
        echo "Error: No se pudo generar el XML\n";
        exit(1);
    }

    $log->debug("XML generado exitosamente");

    // 4. Enviar a QMF usando la misma función que process_order.php
    $log->info("Enviando XML a QMF");
    $response = postXML('https://quieromifactura.mx/PROD/web_services/servidorMarket.php', $xmlString);
    $log->info('Respuesta de QMF: ', $response);

    if ($response['error']) {
        echo "❌ Error de conexión: " . $response['error'] . "\n";
        exit(1);
    }

    if ($response['http_code'] === 200) {
        echo "✅ XML enviado exitosamente a QMF\n";
        echo "📋 Detalles:\n";
        echo "   - Order ID: {$orderId}\n";
        echo "   - Shop: {$shopDomain}\n";
        echo "   - Order Number: " . ($order['order_number'] ?? 'N/A') . "\n";
        echo "   - Total: " . ($order['total_price'] ?? 'N/A') . " " . ($order['currency'] ?? 'N/A') . "\n";
        echo "   - HTTP Code: {$response['http_code']}\n";
        echo "📄 Respuesta del servidor:\n";
        echo "   - " . $response['response'] . "\n";

    } else {
        echo "❌ Error HTTP {$response['http_code']}\n";
        echo "Respuesta: " . $response['response'] . "\n";
        exit(1);
    }

} catch (Exception $e) {
    $log->error("Excepción: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$log->info("Proceso de reenvío completado para Order ID: {$orderId}");
echo "✅ Proceso completado exitosamente\n";
