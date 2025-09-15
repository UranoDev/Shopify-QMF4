<?php


function postXML(string $url, string $xmlString, array $headersExtra = []): array
{
    // Preparar cURL
    $ch = curl_init($url);

    // Headers base
    $headers = [
        "Content-Type: text/xml",
        "Content-Length: " . strlen($xmlString)
    ];

    // Si el usuario pasa headers adicionales, los agregamos
    if (!empty($headersExtra)) {
        $headers = array_merge($headers, $headersExtra);
    }

    // Configuración de cURL
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // OJO: cambiar a true en producción

    // Ejecutar POST
    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Capturar errores si los hubiera
    $error = null;
    if (curl_errno($ch)) {
        $error = curl_error($ch);
    }

    // Cerrar conexión
    curl_close($ch);

    // Devolver como array
    return [
        'http_code' => $httpCode,
        'response' => $responseBody,
        'error' => $error,
    ];
}



// Verificar que se haya proporcionado un argumento
if ($argc < 2) {
    echo "Uso: php resend-xml.php <nombre_del_archivo>\n";
    exit(1);
}

// Obtener el nombre del archivo desde los argumentos de línea de comandos
$filename = $argv[1];

// Verificar si el filename tiene extensión, si no agregarle .xml
$pathInfo = pathinfo($filename);
if (empty($pathInfo['extension'])) {
    $filename .= '.xml';
}

$basePath = __DIR__; // o getcwd() si prefieres el directorio actual
$fullPath = $basePath . DIRECTORY_SEPARATOR . $filename;
echo "Ruta completa del archivo: $fullPath\n";
// Verificar que el archivo existe usando realpath para resolver la ruta
$realPath = realpath($fullPath);

if ($realPath === false) {
    echo "Error: El archivo '$filename' no existe o no es accesible.\n";
    exit(1);
}

// Verificar que el archivo es legible
if (!is_readable($filename)) {
    echo "Error: No se puede leer el archivo '$filename'.\n";
    exit(1);
}

try {
    // Cargar el contenido del archivo como string
    $content = file_get_contents($filename);

    // Verificar que se pudo leer el archivo
    if ($content === false) {
        echo "Error: No se pudo leer el contenido del archivo '$filename'.\n";
        exit(1);
    }

    // Mostrar información sobre el archivo cargado
    echo "Archivo cargado exitosamente: $filename\n";
    echo "Tamaño del contenido: " . strlen($content) . " bytes\n";
    echo "Número de líneas: " . substr_count($content, "\n") + 1 . "\n";
    echo "inspecting utf characters...\n";
    $content = mb_convert_encoding($content, "UTF-8", 'ISO-8859-1');
    /*echo "\n--- Contenido del archivo ---\n";
    echo $content;*/

} catch (Exception $e) {
    echo "Error al procesar el archivo: " . $e->getMessage() . "\n";
    exit(1);
}
$response = postXML('https://quieromifactura.mx/QA2/web_services/servidorMarket.php', $content);
echo ('Result POST: ' . "\n" . print_r($response, true));

