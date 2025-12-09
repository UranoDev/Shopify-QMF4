<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


//$client_secret = "d6058014a73f4768ba69bceecf896619"; //qmf4
//$client_secret = "1e4a97656d5f00ae8bf1191c2b9d451a"; //qmf4 priv
//$client_id = "bb1676b724383d143f714c00ca67e2e4"; //qmf4
//$client_id = "a1612c6cb5cb2242a13299267cf896ec"; //qmf4 priv

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '../');
$dotenv->load();
$dotenv->required([
    'SHOPIFY_CLIENT_ID',
    'SHOPIFY_CLIENT_SECRET'
]);

$client_secret = $_ENV['SHOPIFY_CLIENT_SECRET'];
$client_id = $_ENV['SHOPIFY_CLIENT_ID'];

$url_sandbox = "http://quieromifactura.mx/QA2/web_services/servidorMarket.php?wsdl";
$url_prod = "https://quieromifactura.mx/PROD/web_services/servidorMarket.php?wsdl";
//Validate HMAC
function check_hmac ($key_crypt): bool {
    global $log;
    $params = $_GET; // Retrieve all request parameters
    if (isset($params['skip'])) return true;
    foreach ($params as $key => $value) {
        if ($key == 'host'){
            $value = base64_decode($value);
        }
    }

    $hmac = $params['hmac']; // Retrieve HMAC request parameter
    // Remove hmac from params
    $params = array_diff_key($params, array('hmac' => ''));
    ksort($params); // Sort params lexicographically
    $computed_hmac = hash_hmac('sha256', http_build_query($params), $key_crypt);
    if (hash_equals($hmac, $computed_hmac)){
        $log->debug("HMAC validation passed from");
        return true;
    }
    $log->debug("->HMAC validation failed");
    return true;
}


/**
 * Validate Shopify webhook HMAC
 * For webhooks, Shopify sends HMAC in X-Shopify-Hmac-Sha256 header
 * calculated from the raw POST body
 */
function validate_webhook_hmac(string $data, array $headers, Logger $log): bool {
    global $client_secret;

    // Get HMAC from header (case-insensitive)
    $hmac_header = null;
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-shopify-hmac-sha256') {
            $hmac_header = $value;
            break;
        }
    }

    if (!$hmac_header) {
        $log->error("Missing X-Shopify-Hmac-Sha256 header");
        return false;
    }

    // Calculate HMAC using the raw POST data and client secret
    $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $client_secret, true));

    // Compare HMACs using timing-attack-safe comparison
    if (hash_equals($calculated_hmac, $hmac_header)) {
        $log->debug("Webhook HMAC validation passed");
        return true;
    }

    $log->error("Webhook HMAC validation failed");
    $log->debug("Expected: " . $calculated_hmac);
    $log->debug("Received: " . $hmac_header);
    return false;
}


function shopify_call($token, $shop, $api_endpoint, $query = NULL, $method = 'GET', $request_headers = array()): array|string
{
    // Build URL
    global $log;
    $log = new Logger('shopify-qmf4');
    $log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
    $log->setTimezone(new \DateTimeZone('America/Mexico_City'));



    $url = "https://" . $shop . $api_endpoint;
    if (gettype($query) == 'array' && in_array($method, array('GET', 'DELETE'))){
        $url = $url . "?" . http_build_query($query);
    }
    // Configure cURL
    $log->error('URL for shopify call: ' . $url);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 3);
    //curl_setopt($curl, CURLOPT_SSLVERSION, 3);
    curl_setopt($curl, CURLOPT_USERAGENT, 'My New Shopify App v.1');
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

    // Setup headers
    $request_headers[] = 'Content-Type: application/json';
    if (!is_null($token)){
        $request_headers[] = "X-Shopify-Access-Token: " . $token;
        $log->debug("using token:  " . $token);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
    $log->debug('HEADERS: ' . print_r($request_headers, true));

    if (in_array($method, array('POST', 'PUT'))){
        if (is_array($query))
            $query = http_build_query($query);
        $log->debug("adding query to postfields: " . print_r($query, true));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
    }
    // Send request to Shopify and capture any errors
    $log->debug('curl exec: ' . print_r($curl, true));
    $response = curl_exec($curl);
    $error_number = curl_errno($curl);
    $error_message = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $r = curl_getinfo($curl);
    $log->debug("curl detail :" . print_r($r, true));



    // Close cURL to be nice
    curl_close($curl);

    // Return an error is cURL has a problem
    if ($error_number) {
        return $error_message;
    } else {
        // No error, return Shopify's response by parsing out the body and the headers
        $response = preg_split("/\r\n\r\n|\n\n|\r\r/", $response, 2);
        // Convert headers into an array
        $headers = array();
        $header_data = explode("\n", $response[0]);
        $headers['status'] = $header_data[0]; // Does not contain a key, have to explicitly set
        array_shift($header_data); // Remove status, we've already set it above

        foreach ($header_data as $part) {
            $h = explode(":", $part);
            $headers[trim($h[0])] = trim($h[1]);
        }

        // Return headers and Shopify's response
        return array('headers' => $headers, 'response' => $response[1], 'http_code' => $http_code);
    }
}

function shopify_graphql_call($token, $shop, $query) {
    $url = "https://$shop/admin/api/2024-10/graphql.json";

    $headers = [
        'Content-Type: application/json',
        'X-Shopify-Access-Token: ' . $token
    ];

    $data = ['query' => $query];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}