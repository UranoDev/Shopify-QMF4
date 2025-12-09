<?php
error_reporting(E_ALL);
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
require_once "vendor/autoload.php";
include_once "includes/utils.php";
$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));
$data = file_get_contents('php://input');
$h = apache_request_headers();
$topic = $h['X-Shopify-Topic'];

$log->info("Inicio " . str_repeat('=', 50));
// Validate HMAC from Shopify webhook
if (!validate_webhook_hmac($data, $h, $log)) {
    $log->error("HMAC validation failed - Unauthorized webhook request");
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$log->info("HMAC validation passed - Webhook is authentic");

$log->info("Topic: " . $topic);
$log->info("Recibiendo info Orders: ",json_decode($data, true));
$log->info("Headers: " . print_r($h, true));
http_response_code(200);
header("Content-Type: application/json");
echo json_encode(['status' => 'received OK']);
flush(); // Asegura que se envíe la respuesta inmediatamente

// extract info from Shopify
switch ($topic) {
    //todo: agregar funcionalidad cambios, devoluciones, cancelaciones
    case 'orders/paid':
    case 'orders/cancelled':
    case 'orders/fulfilled':
    case 'orders/updated':
        $log->info("Processing {$topic}");
        $data = json_decode($data, true);
        $log->debug("Before " . __FILE__);
        include_once "process_order.php";
        $log->debug("After " . __FILE__);
        break;
    default:
            $log->error("Topic not processed: $topic");
}
$log->info("Fin " . str_repeat('=', 50));
exit;