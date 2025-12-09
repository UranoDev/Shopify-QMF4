<?php
require_once "vendor/autoload.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));

/*$API_KEY = 'bb1676b724383d143f714c00ca67e2e4'; //Client ID qmf4
$API_KEY = 'a1612c6cb5cb2242a13299267cf896ec'; //Client ID qmf priv
$API_KEY = "f6a941d5e69cc48fa158d27a8ae3f003"; //qmf4 priv 001
$API_KEY = '2e6100ad86bcd154bfd560133248367a'; //qmf4 priv 002*/
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['SHOPIFY_API_KEY', ]);
$API_KEY = $_ENV['SHOPIFY_API_KEY'];
$shop = $_GET['shop'];
//https://shopify.dev/docs/api/usage/access-scopes
$scopes = "read_products,write_products,read_orders, read_all_orders, write_orders,read_metaobjects, write_metaobjects, read_metaobject_definitions, write_metaobject_definitions";

$redirect_uri = 'https://'.$_SERVER['HTTP_HOST']. "/token.php";
try {
    $nonce = bin2hex(random_bytes(12));
} catch (\Random\RandomException $e) {
    $log->error("Error en bin2hex" . $e->getMessage());
}

$oauth_url = "https://" . $shop .  "/admin/oauth/authorize?client_id=" . $API_KEY . "&scope=" . $scopes . "&redirect_uri=" . $redirect_uri . "&nonce=" . $nonce ;
$log->debug("Install redirect URL: " . $oauth_url);
header('Location: ' . $oauth_url);
exit();
