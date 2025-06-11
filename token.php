<?php
require_once "vendor/autoload.php";
require_once "includes/utils.php";
require_once "includes/mysql.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new DateTimeZone('America/Mexico_City'));

$log->debug('Iniciando Token');

global $client_secret;
global $client_id;
global $conn;

if (check_hmac($client_secret)){
    $token_endpoint = "https://" . $_GET['shop'] . "/admin/oauth/access_token";
    $var = array("client_id" => $client_id,
        "client_secret" => $client_secret,
        "code" => $_GET['code'],
    );

    $ch = curl_init($token_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($var));

    $log->debug('url: ' . $token_endpoint);
    $log->debug('Params: ' . print_r($var, true));
    $response = curl_exec($ch);
    if ($response === FALSE) {
        $log->error('Error endpoint : ' . $token_endpoint);
    }
    $log->debug('Token recibido: ' .$response);
    $log->error('Error: ' . curl_error($ch) . "(" . curl_errno($ch) . ")");
    $response = json_decode($response, true);
    $log->debug('Token recibido, array: ' . print_r($response, true));
    curl_close($ch);

    if (count($response) == 0){
        $log->error("Token expirado o duplicado");
        die ("Token expirado");
    }



    $shop = $_GET['shop'];
    $access_token = $response['access_token'];
    $hmac = $_GET['hmac'];
    $sql = "INSERT INTO shops (shop, token, hmac, nonce, api_key) VALUES ('$shop', '$access_token', '$hmac' , '', '') ON duplicate key update token = '$access_token', hmac='$hmac'";
    $result = $conn->query($sql);
    if ($result) {
        header('Location: https://' . $shop . "/admin/apps");
    }

}