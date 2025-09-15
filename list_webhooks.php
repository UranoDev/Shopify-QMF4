<?php
require_once "includes/mysql.php";
require_once "vendor/autoload.php";
require_once "includes/utils.php";
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

include "html/header.php";

global $client_secret;
global $conn;

$log = new Logger('shopify-qmf4');
$log->pushHandler(new StreamHandler('logs/shopify-qmf4.log'));
$log->setTimezone(new \DateTimeZone('America/Mexico_City'));
$log->debug("Inicio - Lista de Webhooks página");

// Get shop and token from database
$shop = $_GET['shop'] ?? '';
$token = '';

if ($shop) {
    $sql = "SELECT * FROM shops WHERE shop = '$shop' AND uninstalled IS NULL";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $token = $row['token'];
        $shop = $row['shop'];
    }
}

?>
<div class="max-w-6xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-center">Lista de Webhooks Registrados</h1>
    
    <?php if ($token && $shop): ?>
        <?php
        // GET request to retrieve all webhooks
        $response = shopify_call($token, $shop, "/admin/api/2020-04/webhooks.json", null, 'GET');
        $log->debug("Respuesta obtener lista de Webhooks: ", $response);

        if ($response && isset($response['response'])) {
            $webhooks_data = json_decode($response['response'], true);
            
            if (isset($webhooks_data['webhooks'])) {
                $webhooks = $webhooks_data['webhooks'];
                ?>
                <div class="mb-4">
                    <p class="text-gray-600"><strong>Tienda:</strong> <?= htmlspecialchars($shop) ?></p>
                    <p class="text-gray-600"><strong>Total de webhooks:</strong> <?= count($webhooks) ?></p>
                </div>
                
                <?php if (empty($webhooks)): ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                        <p>No hay webhooks registrados para esta tienda.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Tópico</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Dirección</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Formato</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Creado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Actualizado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($webhooks as $webhook): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($webhook['id']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($webhook['topic']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900 break-all"><?= htmlspecialchars($webhook['address']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($webhook['format']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('Y-m-d H:i:s', strtotime($webhook['created_at'])) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('Y-m-d H:i:s', strtotime($webhook['updated_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
                <?php
                $log->info("Se encontraron " . count($webhooks) . " webhooks registrados para la tienda: " . $shop);
            } else {
                ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p>Error: No se pudieron obtener los webhooks. Respuesta de API inválida.</p>
                </div>
                <?php
                $log->error("Error en la respuesta de la API - no se encontró el campo 'webhooks'");
            }
        } else {
            ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <p>Error: No se pudo conectar con la API de Shopify.</p>
            </div>
            <?php
            $log->error("Error al hacer la llamada a la API de Shopify");
        }
        ?>
    <?php else: ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            <p>No se pudo obtener la información de la tienda. Asegúrate de acceder desde la aplicación en Shopify.</p>
        </div>
    <?php endif; ?>
    
    <div class="mt-6 text-center">
        <a href="index.php?shop=<?= urlencode($shop) ?>&host=<?= urlencode($_GET['host'] ?? '') ?>" 
           class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-medium hover:bg-blue-700 transition">
            Volver al Panel Principal
        </a>
    </div>
</div>

<?php
$log->debug("Fin - Lista de Webhooks página");
include "html/footer.php";
?>