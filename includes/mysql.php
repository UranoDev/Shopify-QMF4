<?php
require_once "vendor/autoload.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));



DEFINE("DB_HOST", "qmf4.urano.dev");
DEFINE("DB_NAME", "uranodev_qmf4");
DEFINE("DB_USER", "uranodev_qmf4");
DEFINE("DB_PASS", "8Vflr319$");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    $log->error("Connection failed: " . $conn->connect_error);
}
$conn->select_db(DB_NAME);
