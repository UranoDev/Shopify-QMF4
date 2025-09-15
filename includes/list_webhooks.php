<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
require_once "../vendor/autoload.php";
include_once "utils.php";

$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));
$log->debug("Inicio - Lista de Webhooks");

global $token, $shop;

// GET request to retrieve all webhooks
$response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", null, 'GET');
$log->debug("Respuesta obtener lista de Webhooks: ", $response);

if ($response && isset($response['response'])) {
    $webhooks_data = json_decode($response['response'], true);
    
    if (isset($webhooks_data['webhooks'])) {
        $webhooks = $webhooks_data['webhooks'];
        
        echo "<h2>Lista de Webhooks Registrados</h2>\n";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr style='background-color: #f2f2f2;'>\n";
        echo "<th>ID</th>\n";
        echo "<th>Tópico</th>\n";
        echo "<th>Dirección</th>\n";
        echo "<th>Formato</th>\n";
        echo "<th>Creado</th>\n";
        echo "<th>Actualizado</th>\n";
        echo "</tr>\n";
        
        if (empty($webhooks)) {
            echo "<tr><td colspan='6' style='text-align: center;'>No hay webhooks registrados</td></tr>\n";
        } else {
            foreach ($webhooks as $webhook) {
                echo "<tr>\n";
                echo "<td>" . htmlspecialchars($webhook['id']) . "</td>\n";
                echo "<td>" . htmlspecialchars($webhook['topic']) . "</td>\n";
                echo "<td>" . htmlspecialchars($webhook['address']) . "</td>\n";
                echo "<td>" . htmlspecialchars($webhook['format']) . "</td>\n";
                echo "<td>" . htmlspecialchars($webhook['created_at']) . "</td>\n";
                echo "<td>" . htmlspecialchars($webhook['updated_at']) . "</td>\n";
                echo "</tr>\n";
            }
        }
        
        echo "</table>\n";
        echo "<p><strong>Total de webhooks:</strong> " . count($webhooks) . "</p>\n";
        
        $log->info("Se encontraron " . count($webhooks) . " webhooks registrados");
    } else {
        echo "<p>Error: No se pudieron obtener los webhooks</p>\n";
        $log->error("Error en la respuesta de la API - no se encontró el campo 'webhooks'");
    }
} else {
    echo "<p>Error: No se pudo conectar con la API de Shopify</p>\n";
    $log->error("Error al hacer la llamada a la API de Shopify");
}

$log->debug("Fin - Lista de Webhooks");