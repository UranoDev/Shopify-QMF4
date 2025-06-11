<?php

namespace includes;
use mysqli;

DEFINE("DB_HOST", "qmf4.urano.dev");
DEFINE("DB_NAME", "uranodev_qmf4");
DEFINE("DB_USER", "uranodev_qmf4");
DEFINE("DB_PASS", "8Vflr319$");


class Valid_HMAC {
    public function Check_HMAC(string $hmac, string $key, array $get): bool {
        $params = $_GET; // Retrieve all request parameters
        $hmac = $_GET['hmac']; // Retrieve HMAC request parameter
        $shop = $_GET['shop'];
        // Remove hmac from params
        $params = array_diff_key($params, array('hmac' => ''));
        ksort($params); // Sort params lexographically
        $computed_hmac = hash_hmac('sha256', http_build_query($params), $key);
        if (!hash_equals($hmac, $computed_hmac)){
            echo "HMAC validation failed: " . $shop, 'error';
            die('This request is NOT from Shopify!');
        }
        echo "HMAC validation passed from $shop";
        return true;
    }
}

class Shop {
    private $mysql_conn;
    private $shop;
    private $token;
    public $app_installed = false;

    public function __construct(string $shop)
    {
        $this->shop = $shop;
    }

    public function valid_hmac (){

    }
}
class DB_Class
{
    private $mysql_conn;
    private $shop;
    private $token;
    public $app_installed = false;
    public function __construct($shop)
    {
        //connect DB
        $this->mysql_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->mysql_conn->select_db(DB_NAME);
        $sql = "SELECT * FROM `shops` WHERE `shop` = '" . $shop . "'";
        $result = $this->mysql_conn->query($sql);
        if ($result->num_rows == 1) {
            $this->app_installed = true;
            $row = $result->fetch_assoc();
            $this->shop = $row["shop"];
            $this->token = $row["token"];
        }else {
            $sql = "INSERT INTO `shops` (`shop`, `token`) VALUES ('" . $shop . "', '" . $this->token . "')";
        }
    }

    public function shopify_call ($api_endpoint, $query = array(), $method = 'GET', $request_headers = array()){
        // Build URL
        $url = "https://" . $this->shop . $api_endpoint;
        if (!is_null($query) && in_array($method, array('GET', 	'DELETE'))) {
            $url = $url . "?" . http_build_query($query);
        }

        // Configure cURL
        $curl = curl_init($url);

        if (!empty($query) && in_array($method, array('POST', 'PUT'))) {
            if (is_array($query))
                $query = http_build_query($query);
            curl_setopt ($curl, CURLOPT_POSTFIELDS, $query);
        }
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, 'QMF4 Shopify App v.1.0');
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        // Setup headers
        $request_headers = array();
        if (!is_null($this->token)) {
            $request_headers[] = "X-Shopify-Access-Token: " . $this->token;
            curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
        }

        // Send request to Shopify and capture any errors
        $response = curl_exec($curl);
        $error_number = curl_errno($curl);
        $error_message = curl_error($curl);

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
            $header_data = explode("\n",$response[0]);
            $headers['status'] = $header_data[0]; // Does not contain a key, have to explicitly set
            array_shift($header_data); // Remove status, we've already set it above
            foreach($header_data as $part) {
                $h = explode(":", $part);
                $headers[trim($h[0])] = trim($h[1]);
            }

            // Return headers and Shopify's response
            return array('headers' => $headers, 'response' => $response[1]);
        }
    }
}