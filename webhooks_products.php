<?php
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
$log->info("Recibiendo info Products: ",json_decode($data, true));
$log->info("Headers: " . print_r($h, true));
http_response_code(200);
header("Content-Type: application/json");
// extract info from Shopify
switch ($h['X-Shopify-Topic']) {
    case 'products/create':
        $log->info("Processing orders/paid");
        break;
    case 'products/update':
        $log->info("Processing orders/update");
        break;
}
// Opcional: devolver respuesta HTTP 200 a Shopify
http_response_code(200);
exit;


/*
 * Headers siempre presentes
 * X-Shopify-Topic	The name of the topic. Use the webhooks references to match this to the enum value when configuring webhook subscriptions using the Admin API.
 * X-Shopify-Hmac-Sha256	Verification, when using HTTPS delivery.
 * X-Shopify-Shop-Domain	Identifying the associated store. Especially useful when configuring webhook subscriptions using the Admin API.
 * X-Shopify-Webhook-Id	Identifying unique webhooks.
 * X-Shopify-Triggered-At	Identifying the date and time when Shopify triggered the webhook.
 * X-Shopify-Event-Id	Identifying the event that occurred.
 * Shopify recommends using timestamps provided in the header (X-Shopify-Triggered-At) or in the payload itself (updated_at) to organize webhooks.
 * The header X-Shopify-API-Version specifies which version of the Admin API was used to serialize the webhook event payload.
 * In rare instances, your app may receive the same webhook event more than once. Shopify recommends detecting duplicate webhook events by comparing the X-Shopify-Event-Id header to previous events' X-Shopify-Event-Id header.
 */