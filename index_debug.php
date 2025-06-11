<?php
require_once "includes/mysql.php";
require_once "vendor/autoload.php";
require_once "includes/utils.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Shopify invoke this one with query
/*
 hmac = 600b23e98e63b7910594341a57efb77665600206d3c818db1f76b9788ccb16a6
host = admin.shopify.com/store/qmfdemo02
shop = qmfdemo02.myshopify.com
timestamp = 1742255269
HMAC validation passed from qmfdemo02.myshopify.com
*/

include "html/header.php";

global $client_secret;
global $conn;

?>
    <header>Quiero Mi Factura V4</header>
<?php
$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));
$log->error("Inicio");

//Validations
if (!isset($_GET['hmac'])){
    $log->debug("Hola.Esta App debe ser invocada desde Shopify");
    ?>
    <div class="alert">
        <dl>
            <dt>Esta App debe ser invocada desde Shopify</dt>
        </dl>
    </div>
<?php
    die();
}

if (!check_hmac($client_secret)){
    $log->debug("El HMAC no coincide.");
    ?>
    <div class="alert">
        <dl>
            <dt>El HMAC no coincide.</dt>
        </dl>
    </div>
<?php
    die();
}

$log->debug("empezamos...");
$shop = $_GET['shop'];
$token = '';
$sql = "SELECT * from shops WHERE shop = '$shop' and uninstalled IS NULL";
$log->debug($sql);
$result = $conn->query($sql);
$row = null;
$RFC_emisor = '';
$user_qmf4 = '';
$cp_sucursal = '';
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $log->debug("Tienda encontrada en DB: {$row['shop']}<br>");
    $token = $row['token'];
    $shop = $row['shop'];
    $RFC_emisor = $row['rfc'];
    $user_qmf4 = $row["qmf4_user"];
    $cp_sucursal = $row["qmf4_suc"];
}
$log->debug(print_r($result, true));
if (!$result || ($result->num_rows ==0)) {
    $log->debug("Redirecting to Install...");
    header('Location: install.php?shop='.$shop);
    exit;
}


/*
 * Register webhooks
 */
$qry = '{"webhook": {"topic": "orders/paid", "address": "https://qmf4.urano.dev/webhooks_orders.php", "format": "json"}}';
$response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", json_decode($qry,true), 'POST');
$log->debug("Respuesta suscribir a Webhook (orders/paid): ", $response);

$qry = '{"webhook": {"topic": "products/create", "address": "https://qmf4.urano.dev/webhooks_products.php", "format": "json"}}';
$response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", json_decode($qry,true), 'POST');
$log->debug("Respuesta suscribir a Webhook (products/create): ", $response);

$qry = '{"webhook": {"topic": "products/update", "address": "https://qmf4.urano.dev/webhooks_products.php", "format": "json"}}';
$response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", json_decode($qry,true), 'POST');
$log->debug("Respuesta suscribir a Webhook (products/update): ", $response);

$url_sandbox = "http://quieromifactura.mx/QA2/web_services/servidorMarket.php?wsdl";
$url_prod = "https://quieromifactura.mx/PROD/web_services/servidorMarket.php?wsdl";
$log->debug("Start of page");
?>
    <article>
        <div class="card has-sections columns six">
            <div class="card-section align-left">
                <?php
                $result = shopify_call ($token, $shop, '/admin/api/2025-04/webhooks.json');
                $log->debug("call of webhooks" . print_r($result,true));
                echo "<br>Usuario de Quiero mi Factura: $user_qmf4<br>";
                echo '(IP server: ' . $_SERVER['REMOTE_ADDR'] . ')<br>';
                echo "RFC del Emisor: $RFC_emisor<br>";
                echo "CP Sucursal: $cp_sucursal<br>";
                echo "URL Sandbox: $url_sandbox<br>";
                echo "URL Prod: $url_prod<br>";
                echo "Token: $token<br>";
                $r = json_decode($result['response'], true);
                $n = count($r['webhooks']);
                echo "Webhooks procesando: $n <br>";
                foreach ($r['webhooks'] as $webhook) {
                    echo "{$webhook['id']}, {$webhook['topic']}, {$webhook['address']} <br>";
                }
                ?>
            </div>
        </div>
    </article>

    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow-md text-center">
        <h1 class="text-2xl font-bold mb-4">Bienvenido a tu App CFDI</h1>
        <p class="mb-6 text-gray-600">Aquí podrás configurar los datos fiscales de tu tienda.</p>

        <a href="index_old.php?shop=<?= urlencode($shop) ?>&host=<?= urlencode($_GET['host']) ?>"
           class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-medium hover:bg-blue-700 transition">
            Configuración CFDI
        </a>
    </div>
<?php


include "html/footer.php";