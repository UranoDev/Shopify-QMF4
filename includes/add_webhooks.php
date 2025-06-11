<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
require_once "../vendor/autoload.php";
include_once "utils.php";
$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));
$log->debug("Inicio");


global $token, $shop;
$qry = '{"webhook": {"topic": "orders/paid", "address": "https://qmf4.urano.dev/webhooks_orders.php", "format": "json"}}';
$response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", $qry, 'POST');
$log->debug("Respuesta suscribir a Webhook (orders/paid): ", $response);

$qry = '{"webhook": {"topic": "products/create", "address": "https://qmf4.urano.dev/webhooks_products.php", "format": "json"}}';
$response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", $qry, 'POST');
$log->debug("Respuesta suscribir a Webhook (products/create): ", $response);

$qry = '{"webhook": {"topic": "products/update", "address": "https://qmf4.urano.dev/webhooks_products.php", "format": "json"}}';
$response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", $qry, 'POST');
$log->debug("Respuesta suscribir a Webhook (products/update): ", $response);