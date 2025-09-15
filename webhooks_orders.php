<?php
error_reporting(E_ALL);
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
require_once "vendor/autoload.php";
include_once "includes/utils.php";
$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));
$log->debug("Inicio " . __FILE__);

$data = file_get_contents('php://input');
$h = apache_request_headers();
$log->info("Recibiendo info Orders: ",json_decode($data, true));
$log->info("Headers: " . print_r($h, true));
http_response_code(200);
header("Content-Type: application/json");
// extract info from Shopify
switch ($h['X-Shopify-Topic']) {
    //todo: agregar funcionalidad cambios, devoluciones, cancelaciones
    case 'orders/paid':
    case 'orders/cancelled':
    case 'orders/fulfilled':
        $log->info("Processing {$h['X-Shopify-Topic']}");
        $data = json_decode($data, true);
        $log->debug("Before " . __FILE__);
        include_once "process_order.php";
        $log->debug("After " . __FILE__);
        break;
}

exit;