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

// Obtener y mostrar todos los webhooks registrados
echo "<h3>Webhooks Registrados:</h3>";
$result = shopify_call($token, $shop, '/admin/api/2025-04/webhooks.json');
$log->debug("Consulta de webhooks registrados: " . print_r($result, true));

if ($result && isset($result['response'])) {
    $r = json_decode($result['response'], true);
    
    if (isset($r['webhooks']) && is_array($r['webhooks'])) {
        $n = count($r['webhooks']);
        
        if ($n > 0) {
            echo "<p>Total de webhooks registrados: $n</p>";
            echo "<ul>";
            foreach ($r['webhooks'] as $webhook) {
                echo "<li>ID: {$webhook['id']}, Tópico: {$webhook['topic']}, Dirección: {$webhook['address']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No hay webhooks registrados</p>";
        }
    } else {
        echo "<p>Error al obtener la información de webhooks</p>";
        $log->error("Error en la respuesta de webhooks: " . print_r($r, true));
    }
} else {
    echo "<p>Error al consultar webhooks</p>";
    $log->error("Error en la llamada a shopify_call para webhooks");
}
?>