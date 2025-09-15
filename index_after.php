<?php

require_once "includes/mysql.php";
require_once "includes/utils.php";
require_once "vendor/autoload.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
global $conn, $client_id, $shop;


$shop = $_GET['shop'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /*    $stmt = $conn->prepare("UPDATE shops SET qmf4_user = ?, rfc = ? WHERE shop = ?");

        $stmt->execute([
            $_POST['qmf4_user'],
            $_POST['rfc'],
            $shop_domain
        ]);*/

    $mensaje = "Configuración guardada correctamente.";
}

$sql = "SELECT * from shops WHERE shop = '$shop' and uninstalled IS NULL";
$result = $conn->query($sql);
//echo $sql .'<br>';
//echo "Result SQL: " . print_r($result, true) . '<br>';
$row = $result->fetch_assoc();
//echo "Result SQL: " . print_r($row, true) . '<br>';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Integración</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Configuración de Integración</h1>

    <form class="space-y-6">
        <!-- Campo de Usuario -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
            <input type="text"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="Ingrese su usuario"
                   required
            value="<?=$row['qmf4_user'];?>">
        </div>

        <!-- Campo RFC -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">RFC</label>
            <input type="text"
                   pattern="^[A-ZÑ&]{3,4}\d{6}[A-V1-9][0-9A-Z]([0-9A])?$"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                   placeholder="Ingrese su RFC"
                   required
                   value="<?=$row['rfc'] ?>"
            >
        </div>

        <!-- Código Postal -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código Postal</label>
            <input type="number"
                   maxlength="5"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="Ingrese su CP"
                   required
                   value="<?=$row['qmf4_suc'];?>"
            >
        </div>

        <!-- Uso CFDI -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Uso CFDI</label>
            <select class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                <option value="">Seleccione una opción</option>
                <option value="G03">G03 - Gastos en general</option>
                <option value="P01">P01 - Por definir</option>
                <option value="G01">G01 - Adquisición de mercancías</option>
                <option value="D01">D01 - Honorarios médicos</option>
            </select>
        </div>

        <!-- URL Sandbox -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL Sandbox</label>
            <div class="flex rounded-md shadow-sm">
                <input type="text"
                       value="https://quieromifactura.mx/PROD/web_services/servidorMarket.php?wsdl"
                       class="flex-1 block w-full rounded-none rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-gray-100"
                       readonly>
<!--                <button type="button"
                        class="inline-flex items-center px-3 rounded-r-md bg-gray-200 text-gray-600 hover:bg-gray-300 copy-btn"
                        data-clipboard-text="https://quieromifactura.mx/PROD/web_services/servidorMarket.php?wsdl">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
-->            </div>
        </div>

        <!-- URL Producción -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">URL Producción</label>
            <div class="flex rounded-md shadow-sm">
                <input type="text"
                       value="https://api.production.example.com/v1"
                       class="flex-1 block w-full rounded-none rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 bg-gray-100"
                       readonly>
                <!--<button type="button"
                        class="inline-flex items-center px-3 rounded-r-md bg-gray-200 text-gray-600 hover:bg-gray-300 copy-btn"
                        data-clipboard-text="https://api.production.example.com/v1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>-->
            </div>
        </div>

        <!-- Botón de Envío -->
        <div class="pt-4">
            <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>
</body>
</html>