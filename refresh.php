<?php
global $token, $shop, $conn;
include_once 'includes/utils.php';
include_once 'vendor/autoload.php';
include_once 'includes/mysql.php';

$shop = $_GET['shop'];

$sql = "SELECT * from shops WHERE shop = '$shop' and uninstalled IS NULL";
$result = $conn->query($sql);
//echo $sql .'<br>';
//echo "Result SQL: " . print_r($result, true) . '<br>';
$row = $result->fetch_assoc();
//echo "Result SQL: " . print_r($row, true) . '<br>';
$token = $row['token'];


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Órdenes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Últimas 20 Órdenes</h1>
            <button onclick="submitSelectedOrders()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                Reenviar Pedidos seleccionados
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" id="selectAll"
                               class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out"
                               onclick="toggleAll(this)">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <!-- Ejemplo de datos - Reemplazar con datos reales -->
                    <?php
                    $qry ='{"query":"{\\r\\n  orders(first: 20, sortKey: CREATED_AT, reverse: true) {\\r\\n    edges {\\r\\n      node {\\r\\n        id\\r\\n        name\\r\\n        name\\r\\n        totalPriceSet {\\r\\n          shopMoney {\\r\\n            amount\\r\\n            currencyCode\\r\\n          }\\r\\n        }\\r\\n        createdAt\\r\\n        displayFinancialStatus\\r\\n         customer {\\r\\n          displayName\\r\\n        }\\r\\n      }\\r\\n    }\\r\\n  }\\r\\n}\\r\\n","variables":{}}';
                    $response = shopify_call($token, $shop, "/admin/api/graphql.json", $qry, 'POST');
                    if ($response['http_code']==200) {
                        $response = json_decode($response['response'], true);
                    }
                    ?>
                    <?php $i = 0;?>
                    <?php foreach ($response['data']['orders']['edges'] as $node):
                        $i++;
                    $date = new DateTime($node['node']['createdAt']);
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox"
                                   class="order-checkbox form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out"
                                   data-order-id="<?= $i ?>">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= print_r($node['node']['name'], true) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $node['node']['customer']['displayName'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?= $node['node']['totalPriceSet']['shopMoney']['amount'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $date->format('Y-m-d')  ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <?= $node['node']['displayFinancialStatus'] ?>
                                </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-6 flex justify-between items-center">
            <div class="text-sm text-gray-700">
                Mostrando página 1 de 2
            </div>
            <div class="flex space-x-2">
                <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Anterior
                </button>
                <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Siguiente
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Selección/Deselección total
    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
        });
    }

    // Envío de órdenes seleccionadas
    async function submitSelectedOrders() {
        const checkboxes = document.querySelectorAll('.order-checkbox:checked');
        const orderIds = Array.from(checkboxes).map(checkbox => checkbox.dataset.orderId);

        if (orderIds.length === 0) {
            alert('Selecciona al menos una orden');
            return;
        }

        try {
            const response = await fetch('https://example.com/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ orders: orderIds })
            });

            const result = await response.json();
            if (result.success) {
                alert(`${orderIds.length} órdenes actualizadas correctamente`);
                // Actualizar interfaz si es necesario
            } else {
                alert('Error al actualizar las órdenes');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión');
        }
    }

    // Control automático del checkbox "Seleccionar todos"
    document.querySelector('#selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        this.checked = allChecked;
    });
</script>
</body>
</html>
