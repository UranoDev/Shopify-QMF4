<?php
global $conn, $client_id;
require_once "vendor/autoload.php";
require_once "includes/utils.php";
require_once "includes/mysql.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));

$API_KEY = $client_id; //Client ID
$shop = $_GET['shop'];
$sql = "SELECT * from shops WHERE shop = '$shop'";
$result = $conn->query($sql);
echo $sql .'<br>';
echo "Result SQL: " . print_r($result, true) . '<br>';
$row = $result->fetch_assoc();
echo "Result SQL: " . print_r($row, true) . '<br>';

//https://shopify.dev/docs/api/usage/access-scopes
$scopes = "read_customers, read_products,write_products,read_orders, write_orders,read_metaobjects, write_metaobjects, read_metaobject_definitions, write_metaobject_definitions";

$redirect_uri = 'https://'.$_SERVER['HTTP_HOST'].'/index.php';
try {
    $nonce = bin2hex(random_bytes(12));
} catch (\Random\RandomException $e) {
    $log->error("Error en bin2hex" . $e->getMessage());
}

$oauth_url = "https://" . $shop .  ".myshopify.com/admin/oauth/authorize?client_id=" . $API_KEY . "&scope=" . $scopes . "&redirect_uri=" . $redirect_uri . "&nonce=" . $nonce ;
$log->debug("Install redirect URL: " . $oauth_url);
header('Location: ' . $oauth_url);
exit();

