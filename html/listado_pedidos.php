<?php
global $shop, $conn;
require_once "../vendor/autoload.php";
require_once "../includes/mysql.php";
include_once '../includes/utils.php';
include_once 'header.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));
$log->debug("Inicio " . __FILE__);

// Configuración de la app
$sql = "SELECT token, shop from shops WHERE shop = '$shop' and uninstalled IS NULL";
$log->debug($sql);
$result = $conn->query($sql);
$row = $result->fetch_assoc();
if ($result->num_rows == 1) {
    $token = $row['token'];
    $shop = $row['shop'];
} else {
    echo "Shop is not registered";
    exit;
}

$api_version = '2023-10';
$limit = 10;

// Función para extraer page_info de URLs de paginación
function extract_page_info($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    return $params['page_info'] ?? '';
}

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedOrders = json_decode(file_get_contents('php://input'), true)['orders'] ?? [];
    $selectedOrders = array_slice($selectedOrders, 0, 1000);

    // Lógica de procesamiento aquí
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'selected_orders' => $selectedOrders
    ]);
    exit;
}

// Paginación
$page_info = $_GET['page_info'] ?? '';
$prev_page = $next_page = '';

// Obtener órdenes desde Shopify API
$orders = [];
$url = "https://$shop/admin/api/$api_version/orders.json?status=any&limit=$limit" .
    ($page_info ? "&page_info=$page_info" : "");

$response =shopify_call ($token, $shop, $url);

if ($response['http_code'] == 200) {
    $data = json_decode($response['response'], true);
    $orders = $data['orders'];

    // Extraer paginación
    preg_match('/<([^>]+)>; rel="previous"/', $headers, $prev_match);
    preg_match('/<([^>]+)>; rel="next"/', $headers, $next_match);

    $prev_page = $prev_match[1] ?? '';
    $next_page = $next_match[1] ?? '';
}
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Órdenes Recientes</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
    <div class="flex">
        <!-- Menú lateral -->
        <nav class="w-64 h-screen bg-gray-800 p-4 fixed left-0 top-0">
            <div class="text-white text-xl font-bold mb-8">Mi App Shopify</div>
            <ul class="space-y-4">
                <li>
                    <a href="index.php" class="flex items-center space-x-2 text-gray-300 hover:bg-gray-700 px-4 py-2 rounded">
                        <span>🏠</span>
                        <span>Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 bg-gray-700 text-white px-4 py-2 rounded">
                        <span>📦</span>
                        <span>Órdenes</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Contenido principal -->
        <main class="ml-64 p-8 w-full">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Órdenes Recientes</h1>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <form id="ordersForm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="order_ids[]"
                                           value="<?= htmlspecialchars($order['id']) ?>"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($order['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($order['total_price']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= htmlspecialchars($order['customer']['first_name'] . ' ' . $order['customer']['last_name']) ?? 'N/A' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Paginación -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <?php if ($prev_page): ?>
                                    <a href="?page_info=<?= urlencode(extract_page_info($prev_page)) ?>"
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        ← Anterior
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($next_page): ?>
                                    <a href="?page_info=<?= urlencode(extract_page_info($next_page)) ?>"
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Siguiente →
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50">
                        <button type="button"
                                onclick="submitSelectedOrders()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            📤 Enviar Órdenes Seleccionadas
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function submitSelectedOrders() {
            const checkboxes = document.querySelectorAll('input[name="order_ids[]"]:checked');
            const selectedOrders = Array.from(checkboxes).map(cb => cb.value).slice(0, 1000);

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ orders: selectedOrders })
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Respuesta:', data);
                    alert('Órdenes enviadas: ' + data.selected_orders.join(', '));
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
    </body>
    </html>




<?php
include_once 'footer.php';
